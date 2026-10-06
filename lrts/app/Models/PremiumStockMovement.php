<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumStockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'premium_product_id', 'type', 'quantity', 'balance_after', 'reference_type',
        'reference_id', 'user_id', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function premiumProduct()
    {
        return $this->belongsTo(PremiumProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
