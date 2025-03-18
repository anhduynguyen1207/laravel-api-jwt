<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AmazonSpApiService;
use App\Services\AmazonEmailService;
use App\Models\User;
use Carbon\Carbon;

class SendReviewEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amazon:send-emails {--user=} {--days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch recent orders from Amazon and send review request emails';

    /**
     * The Amazon SP-API service.
     *
     * @var AmazonSpApiService
     */
    protected $spApiService;

    /**
     * The Amazon email service.
     *
     * @var AmazonEmailService
     */
    protected $emailService;

    /**
     * Create a new command instance.
     *
     * @param AmazonSpApiService $spApiService
     * @param AmazonEmailService $emailService
     * @return void
     */
    public function __construct(AmazonSpApiService $spApiService, AmazonEmailService $emailService)
    {
        parent::__construct();
        $this->spApiService = $spApiService;
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->option('user');
        $days = $this->option('days');
        
        $this->info('Starting Amazon review email process...');
        
        // Get users to process
        $users = [];
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $users[] = $user;
            } else {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        } else {
            $users = User::where('is_active', true)
                ->whereHas('amazonTokens')
                ->get();
        }
        
        if (count($users) === 0) {
            $this->info('No eligible users found.');
            return 0;
        }
        
        $startDate = Carbon::now()->subDays($days);
        $totalFetched = 0;
        $totalEmailsSent = 0;
        
        foreach ($users as $user) {
            $this->info("Processing user: {$user->name} (ID: {$user->id})");
            
            // Fetch recent orders
            $orders = $this->spApiService->fetchRecentOrders($user, $startDate);
            
            if ($orders === null) {
                $this->error("Failed to fetch orders for user {$user->id}");
                continue;
            }
            
            $this->info("Fetched " . count($orders) . " orders for user {$user->id}");
            
            // Process the orders
            $processed = $this->emailService->processNewOrders($orders, $user);
            $totalFetched += $processed;
            
            $this->info("Processed {$processed} new orders for user {$user->id}");
        }
        
        // Send pending emails
        $this->info('Sending pending review request emails...');
        $totalEmailsSent = $this->emailService->sendPendingEmails();
        
        $this->info("Summary:");
        $this->info("- Total new orders processed: {$totalFetched}");
        $this->info("- Total emails sent: {$totalEmailsSent}");
        
        return 0;
    }
}