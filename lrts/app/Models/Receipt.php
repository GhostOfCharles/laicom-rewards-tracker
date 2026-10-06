<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $hidden = ['slip_path', 'slip_hash'];
    protected $fillable = [
        'user_id', 'salesman_order_number', 'status', 'submitted_at', 'order_date', 'slip_path',
        'slip_hash', 'customer_note', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'order_date' => 'date', 'reviewed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function items() {
        return $this->hasMany(ReceiptItem::class);
    }

    public function earnedRewards() {
        return $this->hasMany(EarnedReward::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function notifications()
    {
        return $this->hasMany(CustomerNotification::class);
    }
}
