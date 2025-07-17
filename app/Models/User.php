<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * User Model
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $organization_id
 * @property string $role
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    /**
     * Get the primary organization that the user belongs to.
     * This is kept for backward compatibility.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all organizations that the user belongs to (many-to-many).
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
                    ->withPivot('role', 'permissions', 'invited_by', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get the teams that the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get the teams where the user is a lead.
     */
    public function ledTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('role', 'lead');
    }

    /**
     * Get the services created by this user.
     */
    public function createdServices(): HasMany
    {
        return $this->hasMany(Service::class, 'created_by');
    }

    /**
     * Get the incidents created by this user.
     */
    public function createdIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'created_by');
    }

    /**
     * Get the incidents resolved by this user.
     */
    public function resolvedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'resolved_by');
    }

    /**
     * Get the incident updates created by this user.
     */
    public function createdIncidentUpdates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class, 'created_by');
    }

    /**
     * Get the maintenances created by this user.
     */
    public function createdMaintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'created_by');
    }

    /**
     * Get the teams created by this user.
     */
    public function createdTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by');
    }

    /**
     * Get the organizations created by this user.
     */
    public function createdOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'created_by');
    }

    /**
     * Get the status updates created by this user.
     */
    public function createdStatusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class, 'created_by');
    }

    /**
     * Get the users invited by this user.
     */
    public function invitedUsers(): HasMany
    {
        return $this->hasMany(self::class, 'invited_by');
    }

    /**
     * Get the user who invited this user.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by');
    }

    /**
     * Scope a query to only include users of a given organization.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $organizationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Get the user's role.
     *
     * @param string $value
     * @return string
     */
    public function getRoleAttribute($value)
    {
        return strtolower($value);
    }

    /**
     * Set the user's role.
     *
     * @param string $value
     */
    public function setRoleAttribute($value)
    {
        $this->attributes['role'] = strtolower($value);
    }

    /**
     * Check if user has a specific role in an organization.
     */
    public function hasRoleInOrganization(Organization $organization, string $role): bool
    {
        return $organization->userHasRole($this, $role);
    }

    /**
     * Get user's role in a specific organization.
     */
    public function getRoleInOrganization(Organization $organization): ?string
    {
        return $organization->getUserRole($this);
    }

    /**
     * Check if user is an owner of any organization.
     */
    public function isOwner(): bool
    {
        return $this->organizations()->wherePivot('role', 'owner')->exists();
    }

    /**
     * Check if user is an admin in any organization.
     */
    public function isAdmin(): bool
    {
        return $this->organizations()->wherePivot('role', 'admin')->exists();
    }

    /**
     * Check if user is a team lead in any team.
     */
    public function isTeamLead(): bool
    {
        return $this->teams()->wherePivot('role', 'lead')->exists();
    }

    /**
     * Get all permissions for a user in an organization.
     */
    public function getPermissionsInOrganization(Organization $organization): array
    {
        $pivot = $this->organizations()->where('organizations.id', $organization->id)->first()?->pivot;
        return $pivot?->permissions ?? [];
    }

    /**
     * Check if user has a specific permission in an organization.
     */
    public function hasPermissionInOrganization(Organization $organization, string $permission): bool
    {
        $permissions = $this->getPermissionsInOrganization($organization);
        return in_array($permission, $permissions);
    }
}
