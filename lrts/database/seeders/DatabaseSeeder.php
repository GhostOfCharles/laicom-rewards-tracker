<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with repeatable demo data.
     */
    public function run(): void
    {
        // Users: one admin and three demo customers.
        User::updateOrCreate(
            ['email' => 'laicom@admin.com'],
            [
                'name' => 'Laicom Admin',
                'password' => Hash::make('Uniliver_123'),
                'role' => 'admin',
                'store_name' => null,
                'phone_number' => null,
            ]
        );

        $customers = [
            'Charles Klyde Namoc',
            'Sam Russell So',
            'John Vincent Sabino',
        ];

        foreach ($customers as $name) {
            $firstName = explode(' ', $name)[0];

            User::updateOrCreate(
                ['email' => strtolower($firstName) . '@example.com'],
                [
                    'name' => $name,
                    'password' => Hash::make('lrts_123'),
                    'role' => 'customer',
                    'store_name' => $firstName . ' Store',
                    'phone_number' => '09' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                ]
            );
        }

        // Premium products: promotional reward items with randomized stock and category.
        $premiumProductNames = [
            'Blue Dipper Big', 'Blue Dipper Small', 'Plastic Container Set', 'Ceramic Mug',
            'Water Tumbler', 'Storage Box', 'Laundry Basket', 'Cooking Pot Small',
            'Cooking Pot Large', 'Glass Jar Set', 'Cutting Board', 'Kitchen Knife',
            'Serving Spoon Set', 'Rice Container', 'Dish Rack', 'Utensils Set',
            'Spice Rack', 'Lunch Box', 'Pail With Cover', 'Dustpan Set',
        ];
        $premiumCategories = array_keys(PremiumProduct::CATEGORIES);

        foreach ($premiumProductNames as $name) {
            // Reuse a seeded product's code so updateOrCreate remains idempotent across runs.
            $existingProduct = PremiumProduct::where('name', $name)->first();
            $itemCode = $existingProduct?->item_code;

            if (! $itemCode) {
                do {
                    $suffix = '';
                    for ($i = 0; $i < 5; $i++) {
                        $suffix .= chr(random_int(65, 90));
                    }
                    $itemCode = 'PRM-' . $suffix;
                } while (PremiumProduct::where('item_code', $itemCode)->exists());
            }

            PremiumProduct::updateOrCreate(
                ['item_code' => $itemCode],
                [
                    'name' => $name,
                    'stock' => random_int(5, 200),
                    'description' => null,
                    'category' => $premiumCategories[array_rand($premiumCategories)],
                    'image_path' => null,
                ]
            );
        }

        // Inventory products: customer-purchased goods with randomized stock and dates.
        $inventoryProductNames = [
            'Dove Soap Beauty Bar 90G', 'Dove Shampoo 340ml', 'Dove Conditioner 340ml',
            'Dove Body Wash 500ml', 'Dove Deodorant Spray', 'Surf Powder Detergent 1kg',
            'Surf Liquid Detergent 1L', 'Breeze Powder Detergent 1kg', 'Rexona Deodorant Roll-On',
            'Rexona Deodorant Spray', 'Close-Up Toothpaste 150ml', 'Pepsodent Toothpaste 150ml',
            'Cream Silk Conditioner 340ml', 'Sunsilk Shampoo 340ml', 'Lifebuoy Soap 90G',
            'Lifebuoy Body Wash 500ml', 'Knorr Liquid Seasoning 250ml', 'Knorr Cubes',
            'Knorr Soup Mix', "Lady's Choice Mayonnaise 220ml", 'Best Foods Mayonnaise 220ml',
            'Lipton Tea Bags 25s', 'Lipton Yellow Label Tea', 'Selecta Ice Cream 1.5L',
            'Magnolia Ice Cream 1.5L', 'Alaska Evaporated Milk 370ml', 'Bear Brand Powdered Milk',
            'Nescafe Classic 50g', 'Milo Powder 300g', 'Milo Ready-to-Drink 300ml',
        ];
        $inventoryCategories = array_keys(Inventory::CATEGORIES);

        foreach ($inventoryProductNames as $name) {
            Inventory::updateOrCreate(
                ['name' => $name],
                [
                    'category' => $inventoryCategories[array_rand($inventoryCategories)],
                    'stock_balance' => random_int(20, 500),
                    'reserved_stock' => 0,
                    'image_path' => null,
                    'entry_date' => now()->subDays(random_int(1, 60))->toDateString(),
                    'expiry_date' => now()->addMonths(random_int(1, 24))->toDateString(),
                ]
            );
        }
    }
}