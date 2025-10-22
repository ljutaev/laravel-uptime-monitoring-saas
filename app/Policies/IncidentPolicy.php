<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Incident;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->id === $incident->monitor->user_id;
    }

    public function create(User $user): bool
    {
        return false;
    }
}
