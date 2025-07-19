<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ServiceStatusLog Model
 *
 * @property int $id
 * @property int $service_id
 * @property string|null $status_from
 * @property string $status_to
 * @property \Illuminate\Support\Carbon $changed_at
 * @property int|null $changed_by
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ServiceStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'status_from',
        'status_to',
        'changed_at',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service that this log entry belongs to.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who made the status change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope to get logs for a specific service.
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Scope to get logs within a date range.
     */
    public function scopeWithinDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }

    /**
     * Check if this status change indicates the service went down.
     */
    public function isDowntimeStart(): bool
    {
        return $this->status_to !== 'operational';
    }

    /**
     * Check if this status change indicates the service came back up.
     */
    public function isUptimeStart(): bool
    {
        return $this->status_to === 'operational' && $this->status_from !== 'operational';
    }

    /**
     * Create a status log entry for a service.
     */
    public static function logStatusChange(Service $service, ?string $fromStatus, string $toStatus, ?int $changedBy = null, ?string $reason = null): self
    {
        return self::create([
            'service_id' => $service->id,
            'status_from' => $fromStatus,
            'status_to' => $toStatus,
            'changed_at' => now(),
            'changed_by' => $changedBy,
            'reason' => $reason,
        ]);
    }
}
