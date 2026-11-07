<?php

namespace Database\Factories;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptItemFactory extends Factory
{
    public function definition(): array
    {
        return ['receipt_id' => Receipt::factory(), 'product_name' => 'Sample product', 'quantity' => 2, 'unit_price' => 0];
    }
}
