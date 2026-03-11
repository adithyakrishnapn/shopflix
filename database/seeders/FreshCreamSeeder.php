<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Installer\Database\Seeders\Attribute\DatabaseSeeder as AttributeSeeder;
use Webkul\Installer\Database\Seeders\Category\DatabaseSeeder as CategorySeeder;
use Webkul\Installer\Database\Seeders\Core\DatabaseSeeder as CoreSeeder;
use Webkul\Installer\Database\Seeders\Customer\DatabaseSeeder as CustomerSeeder;
use Webkul\Installer\Database\Seeders\Inventory\DatabaseSeeder as InventorySeeder;
use Webkul\Installer\Database\Seeders\User\DatabaseSeeder as UserSeeder;

/**
 * Fresh Cream Seeder
 * 
 * A minimal but complete installation that provides:
 * - Core system configuration (locales, currencies, channels)
 * - Essential attributes for products
 * - Basic product categories
 * - Customer groups and admin user
 * - Inventory source
 * - Sample products for both frontend and backend to work
 * 
 * This is a fresh, clean slate installation without all the CMS, social login,
 * and theme customization data that comes with the full seeder.
 * 
 * Both frontend and backend will work with this minimal setup.
 */
class FreshCreamSeeder extends Seeder
{
    /**
     * Seed the application's database with minimal but complete data.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        // 1. Attributes - Essential product attributes only
        $this->call(AttributeSeeder::class, false, ['parameters' => $parameters]);

        // 2. Categories - Basic product categories
        $this->call(CategorySeeder::class, false, ['parameters' => $parameters]);

        // 3. Core - Locales, currencies, channels, configuration
        $this->call(CoreSeeder::class, false, ['parameters' => $parameters]);

        // 4. Customer Groups
        $this->call(CustomerSeeder::class, false, ['parameters' => $parameters]);

        // 5. Inventory Source
        $this->call(InventorySeeder::class, false, ['parameters' => $parameters]);

        // 6. Admin user and roles
        $this->call(UserSeeder::class, false, ['parameters' => $parameters]);

        // 7. Minimal product sample data
        $this->call(CreamProductSeeder::class, false, ['parameters' => $parameters]);

        // 8. Frontend pages and CMS content
        $this->call(CreamFrontendPageSeeder::class, false, ['parameters' => $parameters]);

        $this->command?->info('Fresh cream installation completed: minimal but complete setup for both frontend and backend.');
    }
}
