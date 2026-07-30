<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\GroupSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\ProdusenDataSeeder;
use Database\Seeders\RujukanSeeder;
use Database\Seeders\KlasifikasiSeeder;
use Database\Seeders\AnomalyRuleSeeder;
use Database\Seeders\SubscriptionPackageSeeder;
use Database\Seeders\SatuanSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GroupSeeder::class,
            UserSeeder::class,
            ProdusenDataSeeder::class,
            RujukanSeeder::class,
            KlasifikasiSeeder::class,
            AnomalyRuleSeeder::class,
            SubscriptionPackageSeeder::class,
            SatuanSeeder::class,
        ]);
    }
}