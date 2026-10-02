<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
    'title', 'description', 'buy_product_name', 'premium_product_id',
    'required_quantity', 'reward_quantity', 'start_date', 'end_date', 'is_active'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function premiumProduct() {
        return $this->belongsTo(PremiumProduct::class);
    }
}
