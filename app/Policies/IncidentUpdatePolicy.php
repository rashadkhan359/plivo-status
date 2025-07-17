<?php

namespace App\Policies;

use App\Models\IncidentUpdate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncidentUpdatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IncidentUpdate $incidentUpdate): bool
    {
        return $user->organization_id === $incidentUpdate->incident->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return !is_null($user->organization_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IncidentUpdate $incidentUpdate): bool
    {
        return $user->organization_id === $incidentUpdate->incident->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IncidentUpdate $incidentUpdate): bool
    {
        return $user->organization_id === $incidentUpdate->incident->organization_id;
    }
} 