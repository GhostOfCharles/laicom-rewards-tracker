<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\PremiumProduct;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }
        // Samples only; never reset stock or account passwords when seeding.
        Inventory::firstOrCreate(['name' => 'Daily Shampoo 250ml'], ['category' => 'personal_care', 'stock_balance' => 50]);
        Inventory::firstOrCreate(['name' => 'Laundry Soap 1kg'], ['category' => 'home_care', 'stock_balance' => 50]);
        PremiumProduct::firstOrCreate(['item_code' => 'RTS-DEMO-MUG'], ['name' => 'Reward Mug', 'category' => 'home_care', 'stock' => 20]);
    }
}
