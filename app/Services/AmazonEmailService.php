<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Models\AsinTemplate;
use App\Models\ExcludedAsin;
use App\Models\SentEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderReviewRequest;
use Carbon\Carbon;

class AmazonEmailService
{
    /**
     * Process new orders from Amazon SP-API and schedule emails
     *
     * @param array $amazonOrders Array of orders from Amazon SP-API
     * @param User $user The user whose orders are being processed
     * @return int Number of orders processed
     */
    public function processNewOrders(array $amazonOrders, User $user)
    {
        $count = 0;
        $emailSettings = $user->emailSetting;
        
        // If email sending is disabled, just save the orders
        $sendEmails = $emailSettings->send_emails ?? false;
        
        // Get excluded ASINs
        $excludedAsins = $user->excludedAsins()->pluck('asin')->toArray();
        
        foreach ($amazonOrders as $amazonOrder) {
            // Skip this ASIN if it's in the excluded list
            if (in_array($amazonOrder['asin'], $excludedAsins)) {
                continue;
            }
            
            // Check if order already exists to avoid duplicates
            $orderExists = Order::where('user_id', $user->id)
                ->where('order_id', $amazonOrder['order_id'])
                ->where('asin', $amazonOrder['asin'])
                ->exists();
                
            if ($orderExists) {
                continue;
            }
            
            // Determine if email should be sent based on settings
            $shouldSendEmail = $sendEmails;
            
            // Check FBA/self-ship/used settings
            if ($amazonOrder['is_fba'] && !$emailSettings->send_fba) {
                $shouldSendEmail = false;
            } elseif (!$amazonOrder['is_fba'] && !$emailSettings->send_self_ship) {
                $shouldSendEmail = false;
            } elseif ($amazonOrder['is_used'] && !$emailSettings->send_used_items) {
                $shouldSendEmail = false;
            }
            
            // Create the order record
            $order = new Order([
                'order_id' => $amazonOrder['order_id'],
                'asin' => $amazonOrder['asin'],
                'product_name' => $amazonOrder['product_name'],
                'order_date' => $amazonOrder['order_date'],
                'shipping_date' => $amazonOrder['shipping_date'] ?? null,
                'buyer_email' => $amazonOrder['buyer_email'] ?? null,
                'is_fba' => $amazonOrder['is_fba'] ?? false,
                'is_used' => $amazonOrder['is_used'] ?? false,
                'is_canceled' => $amazonOrder['is_canceled'] ?? false,
                'email_sent' => false,
                'send_email' => $shouldSendEmail
            ]);
            
            $user->orders()->save($order);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Send pending review request emails
     *
     * @return int Number of emails sent
     */
    public function sendPendingEmails()
    {
        $emailCount = 0;
        
        // Get all users with active email settings
        $users = User::whereHas('emailSetting', function ($query) {
            $query->where('send_emails', true);
        })->where('is_active', true)->get();
        
        foreach ($users as $user) {
            $emailSettings = $user->emailSetting;
            $currentHour = Carbon::now()->hour;
            
            // Check if current time is within allowed sending hours
            if ($currentHour < $emailSettings->send_time_start || $currentHour > $emailSettings->send_time_end) {
                continue;
            }
            
            // Find orders ready for email based on the send_days_after setting
            $readyDate = Carbon::now()->subDays($emailSettings->send_days_after);
            
            $pendingOrders = $user->orders()
                ->where('send_email', true)
                ->where('email_sent', false)
                ->where('is_canceled', false)
                ->whereNotNull('buyer_email')
                ->whereNotNull('shipping_date')
                ->where('shipping_date', '<=', $readyDate)
                ->get();
            
            foreach ($pendingOrders as $order) {
                if ($this->sendOrderEmail($order, $user, $emailSettings)) {
                    $emailCount++;
                }
            }
        }
        
        return $emailCount;
    }
    
    /**
     * Send an email for a specific order
     *
     * @param Order $order The order to send email for
     * @param User $user The user/seller
     * @param EmailSetting $emailSettings User's email settings
     * @return bool Whether the email was sent successfully
     */
    protected function sendOrderEmail(Order $order, User $user, EmailSetting $emailSettings)
    {
        try {
            // Get the appropriate email template
            $template = $this->getEmailTemplate($order, $user);
            
            if (!$template) {
                Log::warning("No template found for order #{$order->order_id}, ASIN: {$order->asin}");
                return false;
            }
            
            // Prepare email content with placeholders replaced
            $subject = $this->replacePlaceholders($template['subject'], $order, $user);
            $content = $this->replacePlaceholders($template['content'], $order, $user);
            
            // Send the email
            Mail::to($order->buyer_email)
                ->send(new OrderReviewRequest($subject, $content, $user->store_name));
            
            // Copy to self if setting enabled
            if ($emailSettings->send_copy_to_self) {
                Mail::to($user->email)
                    ->send(new OrderReviewRequest($subject, $content, $user->store_name, true));
            }
            
            // Record the sent email
            SentEmail::create([
                'user_id' => $user->id,
                'order_id' => $order->order_id,
                'asin' => $order->asin,
                'email_subject' => $subject,
                'email_content' => $content,
                'sent_date' => Carbon::now()
            ]);
            
            // Update the order status
            $order->email_sent = true;
            $order->save();
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email for order #{$order->order_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the appropriate email template for an order
     *
     * @param Order $order The order
     * @param User $user The user/seller
     * @return array|null Template data with subject and content
     */
    protected function getEmailTemplate(Order $order, User $user)
    {
        // First check for ASIN-specific template
        $asinTemplate = $user->asinTemplates()
            ->where('asin', $order->asin)
            ->first();
        
        if ($asinTemplate) {
            // If ASIN template has custom content, use it
            if ($asinTemplate->subject && $asinTemplate->content) {
                return [
                    'subject' => $asinTemplate->subject,
                    'content' => $asinTemplate->content
                ];
            }
            
            // If ASIN template references another template
            if ($asinTemplate->template_id) {
                $referencedTemplate = EmailTemplate::find($asinTemplate->template_id);
                if ($referencedTemplate) {
                    return [
                        'subject' => $referencedTemplate->subject,
                        'content' => $referencedTemplate->content
                    ];
                }
            }
        }
        
        // Fall back to default template
        $defaultTemplate = $user->emailTemplates()
            ->where('is_default', true)
            ->first();
        
        if ($defaultTemplate) {
            return [
                'subject' => $defaultTemplate->subject,
                'content' => $defaultTemplate->content
            ];
        }
        
        // If no default template, use the first template
        $anyTemplate = $user->emailTemplates()->first();
        if ($anyTemplate) {
            return [
                'subject' => $anyTemplate->subject,
                'content' => $anyTemplate->content
            ];
        }
        
        // No templates available
        return null;
    }
    
    /**
     * Replace placeholders in email templates
     *
     * @param string $text Template text with placeholders
     * @param Order $order The order
     * @param User $user The user/seller
     * @return string Text with placeholders replaced
     */
    protected function replacePlaceholders($text, Order $order, User $user)
    {
        $replacements = [
            '{store_name}' => $user->store_name,
            '{product_name}' => $order->product_name,
            '{order_id}' => $order->order_id,
            '{order_date}' => $order->order_date->format('d/m/Y'),
            '{customer_name}' => '', // Would need to be extracted from buyer info if available
            '{seller_name}' => $user->name,
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}