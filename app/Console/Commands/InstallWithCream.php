<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallWithCream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:cream 
                            {--force : Force installation without confirmation}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Install Bagisto with fresh cream setup (automated, no wizard)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Installing Bagisto with Fresh Cream Setup...');

        try {
            // Run migrations
            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => true]);

            // Seed with fresh cream data
            $this->info('Seeding database...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\FreshCreamSeeder',
                '--force' => true,
            ]);

            // Mark as installed
            File::ensureDirectoryExists(storage_path());
            File::put(storage_path('installed'), json_encode([
                'installed_at' => now()->toDateTimeString(),
                'setup_type'   => 'cream-automated',
            ]));

            $this->info('✓ Bagisto installed successfully with fresh cream setup!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Installation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
