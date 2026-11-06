<?php

namespace App\Features\Stock\Policies;

use App\Features\Stock\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMember();
    }

    public function create(User $user): bool
    {
        return $user->isMember();
    }

    public function view(User $user, Stock $stock): bool
    {
        return $this->owns($user, $stock);
    }

    public function update(User $user, Stock $stock): bool
    {
        return $this->owns($user, $stock);
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $this->owns($user, $stock);
    }

    private function owns(User $user, Stock $stock): bool
    {
        return $user->isMember() && $user->id === $stock->user_id;
    }
}
