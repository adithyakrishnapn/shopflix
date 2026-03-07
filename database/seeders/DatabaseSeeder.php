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
        if (env('BAGISTO_SEED_BASE_DATA', false)) {
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

        if (File::exists(storage_path('installed'))) {
            File::delete(storage_path('installed'));
        }

        $this->command?->info('Installer mode enabled: skipped Bagisto base data seeding. Open /install to continue setup.');
    }
}
