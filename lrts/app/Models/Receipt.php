<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = ['user_id', 'salesman_order_number', 'status', 'submitted_at'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function items() {
        return $this->hasMany(ReceiptItem::class);
    }

    public function earnedRewards() {
        return $this->hasMany(EarnedReward::class);
    }
}