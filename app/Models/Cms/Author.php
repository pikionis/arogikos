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
use Spatie\MediaLibrary\Conversions\Conversion;
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

    protected $fillable = [
        'name', 'role_title', 'bio', 'is_active', 'user_id', 'position',
        'circle_x', 'circle_y', 'circle_w', 'circle_h',
        'arch_x', 'arch_y', 'arch_w', 'arch_h',
    ];

    /**
     * The two shapes a photograph is shown in, and what each one is.
     *
     * `circle` is the small round face beside a byline; `arch` is the tall
     * frame on the team page, next to an article and in the dialog. The ratio
     * is width over height of the frame the site actually draws.
     */
    public const FRAMES = [
        'circle' => ['ratio' => 1.0, 'width' => 600, 'label' => 'Στρογγυλή'],
        'arch' => ['ratio' => 0.9, 'width' => 800, 'label' => 'Στην ομάδα'],
    ];

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

    /**
     * Build the conversions from THIS author, not from a blank one.
     *
     * Medialibrary makes a bare instance of the model to read the
     * conversions off, and a bare one has no crop on it — so the chosen
     * square was quietly ignored and every photo came out uncropped. One
     * extra query, on a screen somebody opens to change a photograph.
     */
    public bool $registerMediaConversionsUsingModelInstance = true;

    public function registerMediaConversions(?Media $media = null): void
    {
        // One conversion per shape, each cut to its own frame. The crop comes
        // first and the resize after, so the picture on the site is the one
        // that was chosen at every size it is shown.
        // The whole picture, uncropped: the panel shows it above the framing
        // tool, where what you are about to frame is the point.
        $this->addMediaConversion('thumb')->width(400)->nonQueued();

        foreach (self::FRAMES as $frame => $spec) {
            $this->cropped($this->addMediaConversion($frame), $frame)
                ->width($spec['width'])
                ->nonQueued();
        }
    }

    /** Has somebody framed the photograph for this shape? */
    public function hasCrop(string $frame): bool
    {
        return (int) $this->{$frame.'_w'} > 0 && (int) $this->{$frame.'_h'} > 0;
    }

    private function cropped(Conversion $conversion, string $frame): Conversion
    {
        if (! $this->hasCrop($frame)) {
            return $conversion;
        }

        return $conversion->manualCrop(
            (int) $this->{$frame.'_w'},
            (int) $this->{$frame.'_h'},
            (int) $this->{$frame.'_x'},
            (int) $this->{$frame.'_y'},
        );
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

    public function photoUrl(string $conversion = 'arch'): ?string
    {
        $url = $this->getFirstMediaUrl('photo', $conversion);

        return $url !== '' ? $url : null;
    }
}
