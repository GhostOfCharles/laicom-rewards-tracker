<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'description',
        'properties', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Activity log entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Activity log entries are immutable.'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
