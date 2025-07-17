<?php

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\IncidentUpdate;

/**
 * Incident Model
 *
 * @property int $id
 * @property int $organization_id
 * @property int $service_id
 * @property string $title
 * @property string $description
 * @property string $status
 * @property string $severity
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
        'resolved_at',
    ];

    protected $casts = [
        'status' => IncidentStatus::class,
        'severity' => IncidentSeverity::class,
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the incident.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the service that owns the incident.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the updates for the incident.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    /**
     * Scope a query to only include incidents of a given organization.
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