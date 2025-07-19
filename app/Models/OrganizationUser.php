<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    /**
     * Get the organization that the user belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user that belongs to the organization.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this user.
     */
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
} 