<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A pending invitation to the admin panel.
 *
 * Only the sha256 of the token is stored; the raw value exists once, inside
 * the emailed link. Single-use (accepted_at) and time-limited (expires_at).
 */
class Invitation extends Model
{
    public const EXPIRES_AFTER_DAYS = 7;

    protected $fillable = ['email', 'name', 'role', 'token_hash', 'invited_by', 'expires_at'];

    protected $casts = [
        'role' => Role::class,
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected $hidden = ['token_hash'];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /* ---------------------------------------------------------------- tokens */

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Create an invitation and return it alongside the one-time raw token.
     *
     * @return array{0: static, 1: string}
     */
    public static function issue(string $email, string $name, Role $role, ?User $inviter = null): array
    {
        $token = Str::random(64);

        $invitation = static::create([
            'email' => Str::lower($email),
            'name' => $name,
            'role' => $role,
            'token_hash' => static::hashToken($token),
            'invited_by' => $inviter?->getKey(),
            'expires_at' => Carbon::now()->addDays(self::EXPIRES_AFTER_DAYS),
        ]);

        return [$invitation, $token];
    }

    /** Look up a pending invitation by its raw token, or null. */
    public static function findPendingByToken(string $token): ?static
    {
        return static::query()
            ->pending()
            ->where('token_hash', static::hashToken($token))
            ->first();
    }

    /* ---------------------------------------------------------------- state */

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', Carbon::now());
    }

    public function acceptUrl(string $token): string
    {
        return route('admin.invitation.show', $token);
    }
}
