<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'salesman_order_number' => fake()->unique()->uuid(), 'status' => 'pending', 'order_date' => today(), 'submitted_at' => now()];
    }
}
