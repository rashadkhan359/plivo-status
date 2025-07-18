<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'invited_by',
        'email',
        'name',
        'role',
        'message',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the organization that sent the invitation.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }

    /**
     * Check if the invitation is valid (not expired and not accepted).
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isAccepted();
    }

    /**
     * Generate a unique token for the invitation.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Create a new invitation.
     */
    public static function createInvitation(array $data): self
    {
        return static::create([
            'organization_id' => $data['organization_id'],
            'invited_by' => $data['invited_by'],
            'email' => $data['email'],
            'name' => $data['name'],
            'role' => $data['role'] ?? 'member',
            'message' => $data['message'] ?? null,
            'token' => static::generateToken(),
            'expires_at' => now()->addDays(7), // Invitations expire in 7 days
        ]);
    }
}
