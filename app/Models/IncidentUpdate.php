<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IncidentUpdate Model
 *
 * @property int $id
 * @property int $incident_id
 * @property string|null $title
 * @property string $description
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\IncidentUpdateFactory factory(...$parameters)
 */
class IncidentUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'incident_id',
        'title',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the incident that owns the update.
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Get the user who created the update.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include updates for a given incident.
     */
    public function scopeForIncident($query, $incidentId)
    {
        return $query->where('incident_id', $incidentId);
    }

    /**
     * Scope a query to order updates by creation time (latest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if this is the latest update for the incident.
     */
    public function isLatest(): bool
    {
        return $this->incident->updates()->latest()->first()?->id === $this->id;
    }

    /**
     * Get the time elapsed since this update was created.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if this update changes the incident status
     */
    public function changesIncidentStatus(): bool
    {
        $incident = $this->incident;
        return $incident && $incident->status !== $this->status;
    }

    /**
     * Apply this update's status to the parent incident
     */
    public function applyToIncident(): void
    {
        $incident = $this->incident;
        if ($incident && $this->changesIncidentStatus()) {
            $originalStatus = $incident->status;
            
            // Update incident status
            $incident->update(['status' => $this->status]);
            
            // If resolved, update resolution fields
            if ($this->status === 'resolved' && $originalStatus !== 'resolved') {
                $incident->update([
                    'resolved_by' => $this->created_by,
                    'resolved_at' => $this->created_at,
                ]);
            }
        }
    }
} 