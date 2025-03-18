<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderReviewRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subject;
    public $content;
    public $storeName;
    public $isCopy;

    /**
     * Create a new message instance.
     *
     * @param string $subject
     * @param string $content
     * @param string $storeName
     * @param bool $isCopy
     * @return void
     */
    public function __construct($subject, $content, $storeName, $isCopy = false)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->storeName = $storeName;
        $this->isCopy = $isCopy;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->subject($this->subject)
            ->view('emails.review-request')
            ->with([
                'content' => $this->content,
                'storeName' => $this->storeName,
                'isCopy' => $this->isCopy
            ]);
            
        if ($this->isCopy) {
            $mail->with([
                'copyNote' => 'This is a copy of an email sent to a customer'
            ]);
        }
        
        return $mail;
    }
}