<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * IncidentUpdate Model
 *
 * @property int $id
 * @property int $incident_id
 * @property string $message
 * @property string $status
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
        'message',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the incident that owns the update.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Get the update's status.
     *
     * @param string $value
     * @return string
     */
    public function getStatusAttribute($value)
    {
        return strtolower($value);
    }

    /**
     * Set the update's status.
     *
     * @param string $value
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = strtolower($value);
    }
} 