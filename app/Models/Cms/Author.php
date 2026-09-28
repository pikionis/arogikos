<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Author display profile (a teacher/specialist shown on the site) — NOT a
 * login account. Mirrors pikionis/cms-core's Author model.
 */
class Author extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = ['name', 'role_title', 'bio', 'is_active', 'user_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /** The panel account this profile belongs to, if any. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shown on the public About page team grid.
     *
     * NOTE: profiles auto-created for invited panel members start inactive, so
     * inviting someone never publishes them on the site by accident. The
     * article author picker must NOT use this scope.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Profiles backed by a panel account. */
    public function scopeLinkedToUser(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function hasPanelAccess(): bool
    {
        return $this->user_id !== null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->nonQueued();
        $this->addMediaConversion('web')->width(1600)->format('webp')->nonQueued();
    }

    /**
     * Is there more to this bio than a card can hold?
     *
     * Below this it fits whole, and offering to open it would promise
     * something that is not there. The number lives here rather than in the
     * three templates that ask, so the card, the dialog and the thing you
     * press can never disagree about whether there is a dialog at all.
     */
    public function hasLongBio(): bool
    {
        return mb_strlen(trim((string) $this->bio)) > 180;
    }

    /** Initials for the avatar chip, e.g. "Μαρία Κωνσταντίνου" → "ΜΚ". */
    public function initials(): string
    {
        $initials = Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        // Greek uppercase drops the tonos (e.g. "Άννα" → "Α", not "Ά").
        return strtr($initials, [
            'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι',
            'Ό' => 'Ο', 'Ύ' => 'Υ', 'Ώ' => 'Ω', 'Ϊ' => 'Ι', 'Ϋ' => 'Υ',
        ]);
    }

    public function photoUrl(string $conversion = 'web'): ?string
    {
        $url = $this->getFirstMediaUrl('photo', $conversion);

        return $url !== '' ? $url : null;
    }
}
