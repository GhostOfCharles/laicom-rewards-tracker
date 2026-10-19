<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function view(User $user, Receipt $record): bool
    {
        return $user->role === 'admin' || $record->user_id === $user->id;
    }

    public function update(User $user, Receipt $record): bool
    {
        return $record->user_id === $user->id && $user->role === 'customer';
    }
}
