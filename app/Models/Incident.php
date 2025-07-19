<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Incident Model
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $service_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $severity
 * @property int $created_by
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\IncidentFactory factory(...$parameters)
 */
class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'service_id',
        'title',
        'description',
        'status',
        'severity',
        'created_by',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the incident.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the primary service affected by the incident.
     * This is kept for backward compatibility.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get all services affected by the incident (many-to-many).
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
                    ->withTimestamps();
    }

    /**
     * Get the user who created the incident.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who resolved the incident.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the updates for the incident.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    /**
     * Get the status updates for the incident.
     */
    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    /**
     * Scope a query to only include incidents of a given organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include active incidents.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['resolved']);
    }

    /**
     * Scope a query to only include resolved incidents.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope a query to filter by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if the incident is active.
     */
    public function isActive(): bool
    {
        return !in_array($this->status, ['resolved']);
    }

    /**
     * Check if the incident is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if the incident is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Resolve the incident.
     */
    public function resolve(?User $user = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $user ? $user->id : null,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Add a service to this incident.
     */
    public function addService(Service $service): void
    {
        $this->services()->syncWithoutDetaching([$service->id]);
    }

    /**
     * Remove a service from this incident.
     */
    public function removeService(Service $service): void
    {
        $this->services()->detach($service->id);
    }

    /**
     * Get the latest update for this incident.
     */
    public function latestUpdate()
    {
        return $this->updates()->latest()->first();
    }

    /**
     * Get the duration of the incident (if resolved).
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at);
    }
} 