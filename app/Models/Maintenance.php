<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Maintenance Model
 *
 * @property int $id
 * @property int $organization_id
 * @property int $service_id
 * @property string $title
 * @property string $description
 * @property \Illuminate\Support\Carbon $scheduled_start
 * @property \Illuminate\Support\Carbon $scheduled_end
 * @property string $status
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
        'status',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'status' => MaintenanceStatus::class,
    ];

    /**
     * Get the organization that owns the maintenance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the service that owns the maintenance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Scope a query to only include maintenances of a given organization.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $organizationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
} 