<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $hidden = ['slip_path', 'slip_hash'];

    protected $fillable = [
        'user_id', 'salesman_order_number', 'status', 'submitted_at', 'order_date', 'slip_path',
        'slip_hash', 'customer_note', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'cancelled_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'order_date' => 'date', 'reviewed_at' => 'datetime', 'cancelled_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function canBeArchived(): bool
    {
        return ! $this->archived_at
            && in_array($this->status, ['approved', 'rejected', 'cancelled'], true)
            && $this->earnedRewards->every(fn ($reward) => in_array($reward->claim_status, ['claimed', 'voided', 'expired'], true));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function earnedRewards()
    {
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
