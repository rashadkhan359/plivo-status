<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    protected $table = 'team_user';

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the team that the user belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that belongs to the team.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 