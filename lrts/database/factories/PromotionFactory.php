<?php

namespace Database\Factories;

use App\Models\PremiumProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Purchase reward', 'buy_product_name' => 'Sample product', 'premium_product_id' => PremiumProduct::factory(), 'required_quantity' => 2, 'reward_quantity' => 1, 'start_date' => today()->subDay(), 'end_date' => today()->addMonth(), 'is_active' => true];
    }
}
