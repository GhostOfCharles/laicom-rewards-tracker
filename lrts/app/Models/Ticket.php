<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id', 'subject', 'category', 'status',
        'priority', 'related_receipt_id', 'resolved_at', 'archived_at', 'archived_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeUnarchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function relatedReceipt()
    {
        return $this->belongsTo(Receipt::class, 'related_receipt_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'pending', 'in_progress'], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'OPEN',
            'pending' => 'PENDING',
            'in_progress' => 'IN PROGRESS',
            'resolved' => 'RESOLVED',
            'closed' => 'CLOSED',
            default => strtoupper($this->status),
        };
    }
}
