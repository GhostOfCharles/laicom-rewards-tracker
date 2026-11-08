<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CustomerNotification;
use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DashboardHeartbeat
{
    private const TTL = 8;

    public function adminCounts(): array
    {
        return Cache::remember('rts-pulse:admin', 5, fn () => [
            'receipts' => Receipt::where('status', 'pending')->count(),
            'claims' => EarnedReward::where('claim_status', 'claim_requested')->distinct('receipt_id')->count('receipt_id'),
            'tickets' => Ticket::unarchived()->whereIn('status', ['open', 'pending', 'in_progress'])->count(),
            'premium-expiry' => PremiumProduct::where('is_perishable', true)->whereDate('expiry_date', '<=', \App\Services\BusinessDate::today()->addDays(30))->count(),
            'version' => hash('sha256', json_encode([
                Receipt::orderBy('id')->get(['id', 'status', 'updated_at']),
                EarnedReward::orderBy('id')->get(['id', 'claim_status', 'updated_at']),
                Ticket::orderBy('id')->get(['id', 'status', 'archived_at', 'updated_at']),
                TicketReply::max('id'),
            ])),
        ]);
    }

    public function snapshot(User $user): array
    {
        $admin = $user->role === 'admin';

        $snapshot = Cache::remember($admin ? 'dashboard:admin:heartbeat' : 'dashboard:customer:'.$user->id, self::TTL, function () use ($user, $admin) {
            $scope = fn (Builder $query) => $admin ? $query : $query->where('user_id', $user->id);
            $versions = [
                'receipts' => $this->version($scope(Receipt::query()), 'updated_at', 'status'),
                'rewards' => $this->version($scope(EarnedReward::query()), 'updated_at', 'claim_status'),
                'tickets' => $this->version($scope(Ticket::query()), 'updated_at', 'status'),
                'replies' => $this->version(TicketReply::query()->when(! $admin, fn ($query) => $query
                    ->where('is_internal_note', false)->whereHas('ticket', fn ($ticket) => $ticket->where('user_id', $user->id)))),
                'premium_products' => $this->version(PremiumProduct::query()),
                'promotions' => $this->version(Promotion::query()),
                'inventory' => $this->version(Inventory::query()),
                // Include the date so scheduled promotion/expiry boundaries also refresh.
                'date' => today()->toDateString(),
            ];

            if ($admin) {
                $counts = $this->adminCounts();
                $versions['activity'] = $this->version(ActivityLog::query(), 'created_at');
            } else {
                $notifications = CustomerNotification::where('user_id', $user->id);
                $counts = ['notifications' => (clone $notifications)->whereNull('read_at')->count()];
                $versions['notifications'] = $this->version($notifications, 'created_at');
            }

            $data = ['scope' => $admin ? 'admin' : 'customer:'.$user->id, 'counts' => $counts, 'versions' => $versions];

            return $data + ['fingerprint' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))];
        });

        // Share cached admin queries while keeping conditional requests tied to the signed-in account.
        $snapshot['scope'] = $user->role.':'.$user->id;
        $snapshot['fingerprint'] = hash('sha256', $snapshot['fingerprint'].':'.$snapshot['scope']);

        return $snapshot;
    }

    private function version(Builder $query, string $timestamp = 'updated_at', ?string $state = null): array
    {
        $row = (clone $query)->reorder()->selectRaw("COUNT(*) AS total, MAX(id) AS latest_id, MAX($timestamp) AS latest_update")->first();
        $version = [
            'count' => (int) $row->total,
            'id' => (int) $row->latest_id,
            'updated_at' => $row->getRawOriginal('latest_update'),
        ];

        // State counts catch transitions even when two writes share a timestamp.
        if ($state) {
            $version['states'] = (clone $query)->reorder()->selectRaw("$state, COUNT(*) AS total")
                ->groupBy($state)->orderBy($state)->pluck('total', $state)->all();
        }

        return $version;
    }
}
