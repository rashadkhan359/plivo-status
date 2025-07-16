<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Organization Model
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $domain
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @method static \Database\Factories\OrganizationFactory factory(...$parameters)
 */
class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
    ];

    /**
     * Get the users for the organization.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the services for the organization.
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the incidents for the organization.
     */
    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }
} 