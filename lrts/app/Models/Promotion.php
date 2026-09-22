<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'title', 'description', 'premium_product_id', 'required_quantity', 
        'reward_quantity', 'start_date', 'end_date', 'is_active'
    ];

    public function premiumProduct() {
        return $this->belongsTo(PremiumProduct::class);
    }
}