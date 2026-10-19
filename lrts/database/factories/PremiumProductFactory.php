<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PremiumProductFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->bothify('Reward ???? ####'), 'item_code' => fake()->unique()->uuid(), 'category' => 'home_care', 'stock' => 20];
    }
}
