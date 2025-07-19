<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'service_id',
        'incident_id',
        'maintenance_id',
        'type',
        'title',
        'description',
        'old_status',
        'new_status',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the status update.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the status update.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the related service (if applicable).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the related incident (if applicable).
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Get the related maintenance (if applicable).
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    /**
     * Scope a query to only include status updates of a given organization.
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope a query to only include service status updates.
     */
    public function scopeServiceUpdates($query)
    {
        return $query->where('type', 'service_status');
    }

    /**
     * Scope a query to only include incident updates.
     */
    public function scopeIncidentUpdates($query)
    {
        return $query->where('type', 'incident');
    }

    /**
     * Scope a query to only include maintenance updates.
     */
    public function scopeMaintenanceUpdates($query)
    {
        return $query->where('type', 'maintenance');
    }

    /**
     * Get the related entity based on the type.
     */
    public function getRelatedEntityAttribute()
    {
        return match($this->type) {
            'service_status' => $this->service,
            'incident' => $this->incident,
            'maintenance' => $this->maintenance,
            default => null,
        };
    }
}
