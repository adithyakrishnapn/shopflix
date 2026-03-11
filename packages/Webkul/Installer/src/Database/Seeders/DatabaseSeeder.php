<?php

namespace Webkul\Installer\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Installer\Database\Seeders\Attribute\DatabaseSeeder as AttributeSeeder;
use Webkul\Installer\Database\Seeders\Category\DatabaseSeeder as CategorySeeder;
use Webkul\Installer\Database\Seeders\CMS\DatabaseSeeder as CMSSeeder;
use Webkul\Installer\Database\Seeders\Core\DatabaseSeeder as CoreSeeder;
use Webkul\Installer\Database\Seeders\Customer\DatabaseSeeder as CustomerSeeder;
use Webkul\Installer\Database\Seeders\Inventory\DatabaseSeeder as InventorySeeder;
use Webkul\Installer\Database\Seeders\Shop\ThemeCustomizationTableSeeder as ShopSeeder;
use Webkul\Installer\Database\Seeders\SocialLogin\DatabaseSeeder as SocialLoginSeeder;
use Webkul\Installer\Database\Seeders\User\DatabaseSeeder as UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        // Check if this is a fresh cream installation
        if (env('BAGISTO_INSTALLATION_TYPE') === 'cream') {
            $this->runCreamInstallation($parameters);
            return;
        }

        // Standard Bagisto full installation with all sample data
        $this->runFullInstallation($parameters);
    }

    /**
     * Run full Bagisto installation with all sample data and CMS content.
     */
    private function runFullInstallation($parameters = []): void
    {
        $this->call(AttributeSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CategorySeeder::class, false, ['parameters' => $parameters]);
        $this->call(CoreSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CustomerSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CMSSeeder::class, false, ['parameters' => $parameters]);
        $this->call(InventorySeeder::class, false, ['parameters' => $parameters]);
        $this->call(SocialLoginSeeder::class, false, ['parameters' => $parameters]);
        $this->call(ShopSeeder::class, false, ['parameters' => $parameters]);
        $this->call(UserSeeder::class, false, ['parameters' => $parameters]);
    }

    /**
     * Run fresh cream installation with minimal but complete data.
     * This provides essential data for both frontend and backend to work.
     */
    private function runCreamInstallation($parameters = []): void
    {
        // Core foundation seeders (these are required for any installation)
        $this->call(AttributeSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CategorySeeder::class, false, ['parameters' => $parameters]);
        $this->call(CoreSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CustomerSeeder::class, false, ['parameters' => $parameters]);
        $this->call(InventorySeeder::class, false, ['parameters' => $parameters]);
        $this->call(UserSeeder::class, false, ['parameters' => $parameters]);

        // Optional: Cream-specific sample data for frontend/backend
        // These are loaded from the app's database/seeders directory
        if (class_exists('Database\\Seeders\\CreamProductSeeder')) {
            $this->call('Database\\Seeders\\CreamProductSeeder', false, ['parameters' => $parameters]);
        }

        if (class_exists('Database\\Seeders\\CreamFrontendPageSeeder')) {
            $this->call('Database\\Seeders\\CreamFrontendPageSeeder', false, ['parameters' => $parameters]);
        }

        $this->command?->info('✓ Fresh cream installation completed - minimal but complete setup ready.');
    }
}
