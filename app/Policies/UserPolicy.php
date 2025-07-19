<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Check if both users are in the same organization
        if (!$this->shareOrganization($user, $model)) {
            return false;
        }

        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin', 'team_lead']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Check if both users are in the same organization
        if (!$this->shareOrganization($user, $model)) {
            return false;
        }

        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Check if both users are in the same organization
        if (!$this->shareOrganization($user, $model)) {
            return false;
        }

        // Cannot delete organization owners
        if ($this->hasRole($model, ['owner'])) {
            return false;
        }

        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Users cannot change their own role
        if ($user->id === $model->id) {
            return false;
        }

        // Check if both users are in the same organization
        if (!$this->shareOrganization($user, $model)) {
            return false;
        }

        // Cannot change role of organization owner
        if ($this->hasRole($model, ['owner'])) {
            return false;
        }

        return $this->hasPermission($user, 'manage_users') || 
               $this->hasRole($user, ['owner', 'admin']);
    }

    /**
     * Check if users share an organization
     */
    protected function shareOrganization(User $user, User $model): bool
    {
        // Get user's organization IDs
        $userOrgIds = $user->organizations->pluck('id');
        if ($user->organization_id) {
            $userOrgIds->push($user->organization_id);
        }

        // Get model's organization IDs
        $modelOrgIds = $model->organizations->pluck('id');
        if ($model->organization_id) {
            $modelOrgIds->push($model->organization_id);
        }

        return $userOrgIds->intersect($modelOrgIds)->isNotEmpty();
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole(User $user, array $roles): bool
    {
        // Check current role from organization context
        if (isset($user->current_role) && in_array($user->current_role, $roles)) {
            return true;
        }

        // Fallback to legacy role
        return in_array($user->role, $roles);
    }

    /**
     * Check if user has specific permission
     */
    protected function hasPermission(User $user, string $permission): bool
    {
        if (isset($user->current_permissions) && 
            isset($user->current_permissions[$permission]) && 
            $user->current_permissions[$permission]) {
            return true;
        }

        return false;
    }
} 