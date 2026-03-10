<?php

namespace Webkul\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;

class DatabaseManager
{
    /**
     * Check Database Connection.
     */
    public function isInstalled()
    {
        if (! file_exists(base_path('.env'))) {
            return false;
        }

        try {
            DB::connection()->getPDO();

            $isConnected = (bool) DB::connection()->getDatabaseName();

            if (! $isConnected) {
                return false;
            }

            $hasTable = Schema::hasTable('admins');

            if (! $hasTable) {
                return false;
            }

            $userCount = DB::table('admins')->count();

            if (! $userCount) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Drop all the tables and migrate in the database
     *
     * @return void|string
     */
    public function migration()
    {
        try {
            // Check if migrations have already been run AND seeding has completed (admins table has records)
            if (Schema::hasTable('channels') && Schema::hasTable('admins') && DB::table('admins')->count() > 0) {
                return response()->json([
                    'data'   => true,
                    'output' => 'Migrations and seeding already completed.',
                ]);
            }

            // Migration can take longer on low-resource instances.
            set_time_limit(0);

            // Use incremental 'migrate' instead of 'migrate:fresh' (drop/recreate is very slow)
            Artisan::call('migrate', [
                '--force'          => true,
                '--no-interaction' => true,
            ]);

            return response()->json([
                'data'   => true,
                'output' => Artisan::output(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed the database.
     *
     * @return void|string
     */
    public function seeder($data)
    {
        $data['parameter'] = [
            'default_locale'     => $data['parameter']['default_locales'],
            'allowed_locales'    => $data['parameter']['allowed_locales'],
            'default_currency'   => $data['parameter']['default_currency'],
            'allowed_currencies' => $data['parameter']['allowed_currencies'],
        ];

        try {
            app(BagistoDatabaseSeeder::class)->run($data['parameter']);

            $this->storageLink();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Storage Link.
     */
    private function storageLink()
    {
        Artisan::call('storage:link');
    }

    /**
     * Generate New Application Key
     */
    public function generateKey()
    {
        try {
            Artisan::call('key:generate');
        } catch (Exception $e) {
        }
    }

    /**
     * Generate fake product data.
     *
     * @return void|string
     */
    public function seedSampleProducts($parameters)
    {
        try {
            app(ProductTableSeeder::class)->run($parameters);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
