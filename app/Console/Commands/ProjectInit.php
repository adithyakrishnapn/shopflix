<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProjectInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:init {--clean : Truncate transactional data (orders, carts, addresses)}';

    protected $description = 'Initialize the project from the master SQL template and optionally clean transactional data.';

    public function handle()
    {
        $this->info("Wiping existing database tables...");
        try {
            \Artisan::call('db:wipe', ['--force' => true]);
            $this->info("Database wiped successfully.");
        } catch (\Exception $e) {
            $this->warn("Could not wipe database (maybe it's already empty?): " . $e->getMessage());
        }

        $filePath = base_path('database/master_template.sql');

        if (! file_exists($filePath)) {
            $this->error("Master template not found at {$filePath}");
            return;
        }

        $this->info("Initializing database from master template...");

        try {
            $this->comment("Reading master template...");
            $sql = file_get_contents($filePath);

            $this->comment("Cleaning SQL template...");
            // Strip database-specific and transaction statements before splitting
            // This ensures we don't try to create another DB or use it
            // We handle optional leading whitespace and different line endings
            $sql = preg_replace('/(?mi)^\s*(CREATE\s+DATABASE|USE|START\s+TRANSACTION|COMMIT|SET\s+FOREIGN_KEY_CHECKS).*?;/m', '', $sql);
            
            $this->comment("Diagnostic: First 500 chars of cleaned SQL:");
            $this->line(substr($sql, 0, 500));
            
            $this->comment("Splitting SQL statements...");
            $statements = preg_split('/;[\r\n]+/', $sql);
            
            $this->comment("Executing statements...");
            $count = 0;
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;

                try {
                    \DB::unprepared($statement . ';');
                    $count++;
                    
                    if ($count % 500 == 0) {
                        $this->line("Executed {$count} statements...");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed at statement {$count}: " . $e->getMessage());
                    $this->line("Statement snippet: " . substr($statement, 0, 250) . "...");
                    throw $e;
                }
            }
            
            $this->info("Database structure and data imported successfully ({$count} statements).");
        } catch (\Exception $e) {
            $this->error("Initialization aborted due to SQL error.");
            return 1;
        }

        if ($this->option('clean')) {
            $this->info("Cleaning demo and transactional data for a 'Basic Only' state...");
            
            $tables = [
                // Products & Categories
                'products', 'product_flat', 'product_images', 'product_categories', 
                'product_attribute_values', 'product_inventories', 'product_ordered_inventories', 
                'product_reviews', 'product_downloadable_links', 'product_downloadable_info', 
                'product_bundle_options', 'product_bundle_option_products', 'product_grouped_products', 
                'product_customer_group_prices',
                'categories', 'category_translations', 'category_filterable_attributes',
                
                // Marketing & CMS
                'sliders', 'cms_pages', 'cms_page_translations', 
                'marketing_campaigns', 'marketing_events', 'marketing_templates', 'subscribers_list',
                
                // Transactions
                'addresses', 'cart', 'cart_items', 'orders', 'order_items', 'order_comments', 
                'order_address', 'order_payment', 'invoices', 'invoice_items', 'shipments', 
                'shipment_items', 'refunds', 'refund_items', 'wishlist', 'customers',
            ];

            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($tables as $table) {
                if (\Schema::hasTable($table)) {
                    \DB::table($table)->truncate();
                    $this->line("Purged: {$table}");
                }
            }
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Update Site Name from .env
            $siteName = config('app.name', 'Bagisto');
            $this->info("Updating site name to: {$siteName}");
            \DB::table('channel_translations')->where('channel_id', 1)->update(['name' => $siteName]);

            $this->info("Demo data purged. Site name updated. Admins preserved.");
        }

        $this->info("Project initialization complete!");
    }
}
