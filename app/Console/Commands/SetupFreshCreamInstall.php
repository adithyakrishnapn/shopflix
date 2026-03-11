<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SetupFreshCreamInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:fresh-cream 
                            {--force : Force setup without confirmation}
                            {--skip-migrations : Skip running migrations}
                            {--skip-seed : Skip running seeders}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Setup a fresh cream installation with minimal but complete data for both frontend and backend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🍨 Bagisto Fresh Cream Installation Setup');
        $this->info('========================================');
        $this->newLine();

        if (!$this->option('force')) {
            $this->warn('This will:');
            $this->warn('  • Reset the database (all tables)');
            $this->warn('  • Run migrations');
            $this->warn('  • Seed with minimal but complete data');
            $this->warn('  • Create admin user (admin@example.com / admin123)');
            $this->newLine();

            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Setup cancelled.');
                return Command::FAILURE;
            }
        }

        try {
            // Step 1: Clear database
            $this->info('Step 1: Clearing database...');
            if (!$this->option('skip-migrations')) {
                Artisan::call('migrate:fresh', ['--force' => true]);
                $this->info('✓ Database cleared and migrations completed');
            } else {
                $this->info('⊘ Skipped migrations');
            }

            $this->newLine();

            // Step 2: Run seeders
            $this->info('Step 2: Seeding database with fresh cream data...');
            if (!$this->option('skip-seed')) {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\FreshCreamSeeder',
                    '--force' => true,
                ]);
                $this->info('✓ Database seeded with fresh cream data');
            } else {
                $this->info('⊘ Skipped seeding');
            }

            $this->newLine();

            // Step 3: Create installation marker
            $this->info('Step 3: Marking installation as complete...');
            File::ensureDirectoryExists(storage_path());
            File::put(storage_path('installed'), json_encode([
                'installed_at' => now()->toDateTimeString(),
                'setup_type'   => 'fresh-cream',
            ]));
            $this->info('✓ Installation marked as complete');

            $this->newLine();

            // Success message
            $this->info('========================================');
            $this->info('✓ Fresh cream installation completed successfully!');
            $this->newLine();
            $this->info('📋 Setup Summary:');
            $this->info('');
            $this->info('Admin Credentials:');
            $this->line('  Email:    admin@example.com');
            $this->line('  Password: admin123');
            $this->newLine();
            $this->info('Sample Data Created:');
            $this->line('  • 4 simple products (various price points)');
            $this->line('  • 1 grouped product');
            $this->line('  • Product categories');
            $this->line('  • Basic CMS pages (About, Contact, Privacy, etc.)');
            $this->line('  • Default channel and locale');
            $this->line('  • Inventory source');
            $this->newLine();
            $this->info('📚 Next Steps:');
            $this->line('  1. Start your server: php artisan serve');
            $this->line('  2. Visit http://localhost:8000');
            $this->line('  3. Admin dashboard: http://localhost:8000/admin');
            $this->line('  4. Login with admin credentials above');
            $this->line('  5. Customize products, categories, and settings');
            $this->newLine();
            $this->info('💡 Both frontend and backend are ready to use!');
            $this->newLine();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Setup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
