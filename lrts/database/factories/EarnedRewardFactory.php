<?php

namespace Database\Factories;

use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EarnedRewardFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'receipt_id' => Receipt::factory(), 'promotion_id' => Promotion::factory(), 'premium_product_id' => PremiumProduct::factory(), 'reward_quantity' => 1, 'claim_status' => 'unclaimed', 'expires_at' => now()->addDays(30)];
    }
}
