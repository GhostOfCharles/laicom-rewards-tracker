<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarnedReward extends Model
{
    protected $fillable = [
        'user_id', 'receipt_id', 'promotion_id', 'premium_product_id', 
        'reward_quantity', 'claim_status', 'claimed_at', 'claim_code', 'claim_requested_at', 'released_by',
        'release_note', 'voided_at', 'expired_at', 'voided_by', 'void_reason', 'expires_at', 'selected_by', 'promotion_title', 'reward_product_name',
        'buy_product_name', 'purchased_quantity', 'promo_required_quantity', 'promo_reward_quantity',
    ];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime', 'claim_requested_at' => 'datetime', 'voided_at' => 'datetime', 'expired_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function premiumProduct()
    {
        return $this->belongsTo(PremiumProduct::class, 'premium_product_id');
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function selector()
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function voider()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
