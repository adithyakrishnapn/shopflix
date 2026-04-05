<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupRazorpayOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'razorpay:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired Razorpay temporary order records (older than 24 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedCount = \DB::table('razorpay_orders')
            ->where('created_at', '<', now()->subDay())
            ->delete();

        $this->info("Cleaned up {$deletedCount} expired Razorpay order records.");

        return 0;
    }
}
