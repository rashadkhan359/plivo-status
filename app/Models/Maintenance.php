<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maintenance Model
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $service_id
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $scheduled_start
 * @property \Illuminate\Support\Carbon $scheduled_end
 * @property \Illuminate\Support\Carbon|null $actual_start
 * @property \Illuminate\Support\Carbon|null $actual_end
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\MaintenanceFactory factory(...$parameters)
 */
class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'service_id',
        'title',
        'description',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'status',
        'created_by',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the maintenance.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the service that the maintenance affects (if any).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who created the maintenance.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the status updates for the maintenance.
     */
    public function statusUpdates(): HasMany
    {
        return $this->hasMany(StatusUpdate::class);
    }

    /**
     * Scope a query to only include maintenances of a given organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include scheduled maintenances.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include maintenances in progress.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed maintenances.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled maintenances.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include upcoming maintenances.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_start', '>', now())
                    ->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include active maintenances.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    /**
     * Scope a query to filter by service.
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Scope a query to only include organization-wide maintenances.
     */
    public function scopeOrganizationWide($query)
    {
        return $query->whereNull('service_id');
    }

    /**
     * Check if the maintenance is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the maintenance is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the maintenance is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the maintenance is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the maintenance is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->scheduled_start > now() && $this->isScheduled();
    }

    /**
     * Check if the maintenance is currently active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    /**
     * Start the maintenance.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start' => now(),
        ]);
    }

    /**
     * Complete the maintenance.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'actual_end' => now(),
        ]);
    }

    /**
     * Cancel the maintenance.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Get the scheduled duration in minutes.
     */
    public function getScheduledDurationAttribute(): int
    {
        return $this->scheduled_start->diffInMinutes($this->scheduled_end);
    }

    /**
     * Get the actual duration in minutes (if maintenance is completed).
     */
    public function getActualDurationAttribute(): ?int
    {
        if (!$this->actual_start || !$this->actual_end) {
            return null;
        }

        return $this->actual_start->diffInMinutes($this->actual_end);
    }

    /**
     * Check if the maintenance is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_end < now() && !$this->isCompleted() && !$this->isCancelled();
    }
} 