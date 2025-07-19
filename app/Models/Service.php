<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service Model
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $team_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $visibility
 * @property int $order
 * @property int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\ServiceFactory factory(...$parameters)
 */
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'team_id',
        'name',
        'description',
        'status',
        'visibility',
        'order',
        'created_by',
    ];

    protected $casts = [
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the service.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the team that owns the service (if any).
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the service.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the incidents for the service.
     */
    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class)
                    ->withTimestamps();
    }

    /**
     * Get the maintenances for the service.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * Get the status updates for the service.
     */
    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    /**
     * Get the status logs for the service.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(\App\Models\ServiceStatusLog::class);
    }

    /**
     * Scope a query to only include services of a given organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include public services.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope a query to only include private services.
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Scope a query to only include services assigned to a team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope a query to only include organization-wide services (not assigned to any team).
     */
    public function scopeOrganizationWide($query)
    {
        return $query->whereNull('team_id');
    }

    /**
     * Scope a query to order services by their display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Get services by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if the service is operational.
     */
    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    /**
     * Check if the service is experiencing issues.
     */
    public function hasIssues(): bool
    {
        return in_array($this->status, ['degraded', 'partial_outage', 'major_outage']);
    }

    /**
     * Check if the service is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Check if the service is private.
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /**
     * Get the current active incidents for this service.
     */
    public function activeIncidents()
    {
        return $this->incidents()->whereNotIn('status', ['resolved']);
    }

    /**
     * Get the upcoming maintenances for this service.
     */
    public function upcomingMaintenances()
    {
        return $this->maintenances()
                    ->where('scheduled_start', '>', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_start');
    }

    /**
     * Update the service status and fire events.
     */
    public function updateStatus(string $status): bool
    {
        $oldStatus = $this->status;
        $updated = $this->update(['status' => $status]);
        
        if ($updated && $oldStatus !== $status) {
            event(new \App\Events\ServiceStatusChanged($this));
        }
        
        return $updated;
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'operational' => 'text-green-600',
            'degraded' => 'text-yellow-600',
            'partial_outage' => 'text-orange-600',
            'major_outage' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get the status icon for UI display.
     */
    public function getStatusIcon(): string
    {
        return match($this->status) {
            'operational' => 'Check',
            'degraded' => 'Clock',
            'partial_outage' => 'AlertTriangle',
            'major_outage' => 'XCircle',
            default => 'HelpCircle',
        };
    }
} 