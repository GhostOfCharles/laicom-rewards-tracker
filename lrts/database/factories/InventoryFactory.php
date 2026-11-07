<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->bothify('Product ???? ####'), 'category' => 'home_care', 'stock_balance' => 50];
    }
}
