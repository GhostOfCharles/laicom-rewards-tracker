<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $record): bool
    {
        return $user->role === 'admin' || $record->user_id === $user->id;
    }

    public function update(User $user, Ticket $record): bool
    {
        return $record->user_id === $user->id && $user->role === 'customer';
    }
}
