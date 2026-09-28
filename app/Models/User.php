<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\Concerns\HasInitials;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A panel account.
 *
 * NOTE: `role` and `is_active` are deliberately NOT mass-assignable. They are
 * privilege fields — assign them explicitly in the controller so that a stray
 * User::create($request->validated()) can never hand out an owner account.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasInitials, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * The public-facing author profile for this account, if one exists.
     *
     * Created (inactive) when an invitation is accepted, so the person can be
     * picked as an article author without appearing on the About page team
     * grid until someone deliberately activates them.
     */
    public function author(): HasOne
    {
        return $this->hasOne(Author::class);
    }

    public function isOwner(): bool
    {
        return $this->role === Role::Owner;
    }

    /** Owner or admin — everything except the owner-only surfaces. */
    public function isAdmin(): bool
    {
        return in_array($this->role, [Role::Owner, Role::Admin], true);
    }

    public function isEditor(): bool
    {
        return $this->role === Role::Editor;
    }
}
