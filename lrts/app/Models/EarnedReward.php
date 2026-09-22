<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EarnedReward extends Model
{
    protected $fillable = [
        'user_id', 'receipt_id', 'promotion_id', 'premium_product_id', 
        'reward_quantity', 'claim_status', 'claimed_at'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function premiumProduct() {
        return $this->belongsTo(PremiumProduct::class);
    }
}