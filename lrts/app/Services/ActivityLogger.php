<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function record(string $action, string $description, ?Model $subject = null, array $properties = []): ActivityLog
    {
        $user = Auth::user() ?? (app()->runningInConsole() ? null : request()->user());
        return $this->recordAs($user, $action, $description, $subject, $properties);
    }

    public function recordAs(?User $user, string $action, string $description, ?Model $subject = null, array $properties = []): ActivityLog
    {
        $request = app()->runningInConsole() ? null : request();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'actor_role' => $user?->role ?? (app()->runningInConsole() ? 'system' : 'guest'),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => mb_substr($description, 0, 500),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }
}
