<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Entry point untuk semua seeders.
 *
 * URUTAN WAJIB:
 * 1. InternalUsersSeeder  — employee accounts (idempotent, JANGAN truncate)
 * 2. FinalDemoSeeder      — pelanggan dummy + service_requests + payments + events
 *
 * Untuk demo environment:
 *   php artisan db:seed --class=FinalDemoSeeder
 *
 * Untuk fresh setup lengkap (HATI-HATI: akan reset pelanggan & SR demo):
 *   php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InternalUsersSeeder::class,
            FinalDemoSeeder::class,
        ]);
    }
}
