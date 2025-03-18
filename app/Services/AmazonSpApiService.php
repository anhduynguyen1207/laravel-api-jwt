<?php

namespace App\Services;

use App\Models\User;
use App\Models\AmazonToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonSpApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $refreshUrl;
    protected $apiUrl;
    
    public function __construct()
    {
        $this->clientId = config('services.amazon.client_id');
        $this->clientSecret = config('services.amazon.client_secret');
        $this->refreshUrl = config('services.amazon.refresh_url');
        $this->apiUrl = config('services.amazon.api_url');
    }
    
    /**
     * Get fresh access token for Amazon SP-API
     *
     * @param User $user
     * @return string|null Access token or null if failed
     */
    public function getAccessToken(User $user)
    {
        $amazonToken = $user->amazonTokens()->latest()->first();
        
        if (!$amazonToken) {
            Log::error("No Amazon token found for user #{$user->id}");
            return null;
        }
        
        // Check if current token is still valid
        if ($amazonToken->token_expires && $amazonToken->token_expires->isFuture() && $amazonToken->access_token) {
            return $amazonToken->access_token;
        }
        
        // Need to refresh the token
        try {
            $response = Http::asForm()->post($this->refreshUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $amazonToken->refresh_token,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);
            
            if ($response->successful()) {
                $tokenData = $response->json();
                
                // Update token in database
                $amazonToken->access_token = $tokenData['access_token'];
                $amazonToken->token_expires = Carbon::now()->addSeconds($tokenData['expires_in']);
                $amazonToken->save();
                
                return $tokenData['access_token'];
            } else {
                Log::error("Failed to refresh Amazon token for user #{$user->id}: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Exception while refreshing Amazon token for user #{$user->id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch recent orders from Amazon SP-API
     *
     * @param User $user
     * @param Carbon $startDate
     * @return array|null Orders or null if failed
     */
    public function fetchRecentOrders(User $user, Carbon $startDate = null)
    {
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30);
        }
        
        $accessToken = $this->getAccessToken($user);
        if (!$accessToken) {
            return null;
        }
        
        try {
            // Call the Orders API
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'x-amz-date' => Carbon::now()->toIso8601String(),
                ])
                ->get($this->apiUrl . '/orders/v0/orders', [
                    'MarketplaceIds' => config('services.amazon.marketplace_id'),
                    'CreatedAfter' => $startDate->toIso8601String(),
                ]);
            
            if ($response->successful()) {
                $ordersData = $response->json();
                return $this->processOrdersData($ordersData, $user);
            } else {
                Log::error("Failed to fetch orders from Amazon for user #{$user->id}: " . $response->body());
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error("Exception while fetching orders from Amazon for user #{$user->id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process raw order data from Amazon SP-API
     *
     * @param array $ordersData Raw data from Amazon API
     * @param User $user
     * @return array Processed order data
     */
    protected function processOrdersData(array $ordersData, User $user)
    {
        $processedOrders = [];
        
        // Check if orders exist in the response
        if (!isset($ordersData['Orders']) || empty($ordersData['Orders'])) {
            return $processedOrders;
        }
        
        foreach ($ordersData['Orders'] as $amazonOrder) {
            // For each order, fetch order items
            $orderItems = $this->fetchOrderItems($amazonOrder['AmazonOrderId'], $user);
            
            if (!$orderItems) {
                continue;
            }
            
            $orderDate = Carbon::parse($amazonOrder['PurchaseDate']);
            $shippingDate = isset($amazonOrder['LastUpdateDate']) ? Carbon::parse($amazonOrder['LastUpdateDate']) : null;
            
            // Determine if the order is FBA (Fulfillment by Amazon)
            $isFBA = isset($amazonOrder['FulfillmentChannel']) && $amazonOrder['FulfillmentChannel'] === 'AFN';
            
            // Get buyer email if available
            $buyerEmail = null;
            if (isset($amazonOrder['BuyerInfo']['BuyerEmail'])) {
                $buyerEmail = $amazonOrder['BuyerInfo']['BuyerEmail'];
            } else {
                // We need to fetch buyer info separately as it may not be included in order data
                $buyerEmail = $this->fetchBuyerEmail($amazonOrder['AmazonOrderId'], $user);
            }
            
            // Process each item in the order
            foreach ($orderItems as $item) {
                $processedOrders[] = [
                    'order_id' => $amazonOrder['AmazonOrderId'],
                    'asin' => $item['ASIN'],
                    'product_name' => $item['Title'],
                    'order_date' => $orderDate,
                    'shipping_date' => $shippingDate,
                    'buyer_email' => $buyerEmail,
                    'is_fba' => $isFBA,
                    'is_used' => $item['IsGift'] ?? false, // This is a simplification; Amazon API may have a specific field for this
                    'is_canceled' => $amazonOrder['OrderStatus'] === 'Canceled',
                ];
            }
        }
        
        return $processedOrders;
    }
    
    /**
     * Fetch order items for a specific order
     *
     * @param string $orderId Amazon Order ID
     * @param User $user
     * @return array|null Order items or null if failed
     */
    protected function fetchOrderItems($orderId, User $user)
    {
        $accessToken = $this->getAccessToken($user);
        if (!$accessToken) {
            return null;
        }
        
        try {
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'x-amz-date' => Carbon::now()->toIso8601String(),
                ])
                ->get($this->apiUrl . "/orders/v0/orders/{$orderId}/orderItems");
            
            if ($response->successful()) {
                $itemsData = $response->json();
                return $itemsData['OrderItems'] ?? [];
            } else {
                Log::error("Failed to fetch order items for order #{$orderId}: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Exception while fetching order items for order #{$orderId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch buyer email for a specific order
     *
     * @param string $orderId Amazon Order ID
     * @param User $user
     * @return string|null Buyer email or null if failed
     */
    protected function fetchBuyerEmail($orderId, User $user)
    {
        $accessToken = $this->getAccessToken($user);
        if (!$accessToken) {
            return null;
        }
        
        try {
            // This endpoint may vary based on Amazon SP-API version and region
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'x-amz-date' => Carbon::now()->toIso8601String(),
                ])
                ->get($this->apiUrl . "/orders/v0/orders/{$orderId}/buyerInfo");
            
            if ($response->successful()) {
                $buyerInfo = $response->json();
                return $buyerInfo['BuyerEmail'] ?? null;
            } else {
                Log::error("Failed to fetch buyer info for order #{$orderId}: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Exception while fetching buyer info for order #{$orderId}: " . $e->getMessage());
            return null;
        }
    }
}