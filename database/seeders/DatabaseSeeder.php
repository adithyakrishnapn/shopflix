<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Check for cream installation mode first
        $installationType = env('BAGISTO_INSTALLATION_TYPE', 'default');
        
        if ($installationType === 'cream' || env('BAGISTO_SEED_BASE_DATA', false)) {
            $this->call(BagistoDatabaseSeeder::class, false, [
                'parameters' => [
                    'default_locale'     => config('app.locale', 'en'),
                    'allowed_locales'    => [config('app.locale', 'en')],
                    'default_currency'   => config('app.currency', 'USD'),
                    'allowed_currencies' => [config('app.currency', 'USD')],
                ],
            ]);

            return;
        }

        // Installer mode: clean slate for web installer
        if (File::exists(storage_path('installed'))) {
            File::delete(storage_path('installed'));
        }

        $this->command?->info('Installer mode enabled: Open /install to complete setup.');
    }
}
