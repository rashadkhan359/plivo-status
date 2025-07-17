<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organization Model
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string|null $logo
 * @property array|null $settings
 * @property string $timezone
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Incident> $incidents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Maintenance> $maintenances
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $teams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StatusUpdate> $statusUpdates
 *
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 */
class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'logo',
        'settings',
        'timezone',
        'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created the organization.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users that belong to the organization.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('role', 'permissions', 'invited_by', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get the organization owners.
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'owner');
    }

    /**
     * Get the organization admins.
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    /**
     * Get the organization members.
     */
    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'member');
    }

    /**
     * Get the organization team leads.
     */
    public function teamLeads(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'team_lead');
    }

    /**
     * Get the teams for the organization.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get the services for the organization.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the incidents for the organization.
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Get the maintenances for the organization.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * Get the status updates for the organization.
     */
    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    /**
     * Check if a user belongs to this organization.
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if a user has a specific role in this organization.
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $this->users()
                    ->where('users.id', $user->id)
                    ->wherePivot('role', $role)
                    ->exists();
    }

    /**
     * Get a user's role in this organization.
     */
    public function getUserRole(User $user): ?string
    {
        $pivot = $this->users()->where('users.id', $user->id)->first()?->pivot;
        return $pivot?->role;
    }

    /**
     * Add a user to this organization with a specific role.
     */
    public function addUser(User $user, string $role = 'member', ?User $invitedBy = null, ?array $permissions = null): void
    {
        $this->users()->attach($user->id, [
            'role' => $role,
            'permissions' => $permissions,
            'invited_by' => $invitedBy?->id,
            'joined_at' => now(),
        ]);
    }

    /**
     * Update a user's role in this organization.
     */
    public function updateUserRole(User $user, string $role, ?array $permissions = null): bool
    {
        return $this->users()->updateExistingPivot($user->id, [
            'role' => $role,
            'permissions' => $permissions,
        ]) > 0;
    }

    /**
     * Remove a user from this organization.
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Get the organization setting.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a organization setting.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }
} 