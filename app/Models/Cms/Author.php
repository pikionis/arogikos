<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Models\Concerns\HasInitials;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    use HasInitials;
    use InteractsWithMedia;

    protected $fillable = ['name', 'role_title', 'bio', 'is_active', 'user_id', 'position'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        // Someone who has just been invited belongs at the end of the team,
        // not silently in front of it. Every path that makes a profile — the
        // form, an accepted invitation, the seeders — goes through here.
        static::creating(function (self $author) {
            if ((int) $author->position === 0) {
                $author->position = ((int) static::query()->max('position')) + 1;
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
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

    /**
     * The order the panel put them in.
     *
     * Top of the list in the panel is first on the page, which reads
     * left-to-right across the team grid. The name is the tie-breaker so two
     * profiles that were never dragged still come out in a stable order
     * rather than in whatever order the database felt like.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
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

    /**
     * A name reduced to what two spellings of the same person share.
     *
     * Greek uppercase drops the tonos, so "ΡΑΦΑΕΛΑ" and "Ραφαέλα" are the same
     * name written twice — lowercasing alone leaves them different and a
     * duplicate profile gets created. Final sigma goes the same way, since
     * "Γιάννης"/"ΓΙΑΝΝΗΣ" differ in it too.
     */
    public static function normaliseName(?string $name): string
    {
        $lower = Str::lower(trim((string) $name));

        $folded = strtr($lower, [
            'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ΐ' => 'ι', 'ϊ' => 'ι',
            'ό' => 'ο', 'ύ' => 'υ', 'ΰ' => 'υ', 'ϋ' => 'υ', 'ώ' => 'ω', 'ς' => 'σ',
        ]);

        return (string) preg_replace('/\s+/u', ' ', $folded);
    }

    public function photoUrl(string $conversion = 'web'): ?string
    {
        $url = $this->getFirstMediaUrl('photo', $conversion);

        return $url !== '' ? $url : null;
    }
}
