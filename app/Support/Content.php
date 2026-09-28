<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\ContentBlock;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Reader for editable page copy.
 *
 * Every field is declared in config/cms-content.php along with the Greek text
 * that used to be hard-coded in the Blade template. A content_blocks row is an
 * override; with an empty table the site renders byte-identical to before.
 *
 * Only the RAW database rows are cached — never the registry-merged result.
 * The defaults live in deployed config, so caching the merged array would make
 * a deploy of new default copy silently serve the old text until someone
 * flushed by hand.
 */
class Content
{
    public const CACHE_KEY = 'cms.content';

    /** @var array<string, mixed>|null */
    private ?array $rows = null;

    /**
     * page_key.field_key => stored value, for the current locale.
     *
     * @return array<string, string|null>
     */
    public function rows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        try {
            $this->rows = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => ContentBlock::query()
                    ->where('locale', config('app.locale', 'el'))
                    ->get(['page_key', 'field_key', 'value'])
                    ->mapWithKeys(fn (ContentBlock $block) => [
                        "{$block->page_key}.{$block->field_key}" => $block->value,
                    ])
                    ->all()
            );
        } catch (Throwable) {
            // The 404 page renders the header and footer, which read content();
            // if the database is unreachable the error page must still render
            // rather than 500 on top of the original failure.
            $this->rows = [];
        }

        return $this->rows;
    }

    /** Raw stored value for a dotted key, or null when not overridden. */
    public function raw(string $key): ?string
    {
        return $this->rows()[$key] ?? null;
    }

    /** The registry declaration for a dotted key, or null. */
    public static function field(string $key): ?array
    {
        [$pageKey, $fieldKey] = self::split($key);

        foreach (config("cms-content.{$pageKey}.groups", []) as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                if ($field['key'] === $fieldKey) {
                    return $field;
                }
            }
        }

        return null;
    }

    /** Scalar text: the stored override, else the registry default. */
    public function get(string $key, ?string $default = null): string
    {
        $stored = $this->raw($key);

        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        return (string) ($default ?? self::field($key)['default'] ?? '');
    }

    /**
     * A `lines` field as an array of non-empty trimmed strings.
     *
     * @return array<int, string>
     */
    public function lines(string $key): array
    {
        $stored = $this->raw($key);

        if ($stored === null || $stored === '') {
            $default = self::field($key)['default'] ?? [];

            return is_array($default) ? array_values($default) : [];
        }

        return self::splitLines($stored);
    }

    /**
     * A `repeater` field as a list of associative arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(string $key): array
    {
        $stored = $this->raw($key);

        if ($stored !== null && $stored !== '') {
            $decoded = json_decode($stored, true);

            if (is_array($decoded)) {
                // A repeater's layouts are built for a fixed number of items,
                // so an empty list there is a truncated submit rather than an
                // instruction — fall through to the shipped copy. A collection
                // is the opposite: emptying it is a decision somebody made.
                if ($decoded !== [] || (self::field($key)['type'] ?? '') === 'collection') {
                    return array_values($decoded);
                }
            }
        }

        $default = self::field($key)['default'] ?? [];

        return is_array($default) ? array_values($default) : [];
    }

    /** URL of an uploaded image for an `image` field, or null. */
    public function image(string $key, string $conversion = 'web'): ?string
    {
        [$pageKey, $fieldKey] = self::split($key);

        try {
            $block = ContentBlock::query()
                ->where('page_key', $pageKey)
                ->where('field_key', $fieldKey)
                ->where('locale', config('app.locale', 'el'))
                ->first();
        } catch (Throwable) {
            return null;
        }

        return $block?->imageUrl($conversion);
    }

    /* --------------------------------------------------------------- utils */

    /** @return array{0: string, 1: string} */
    public static function split(string $key): array
    {
        $pos = strpos($key, '.');

        return $pos === false
            ? [$key, '']
            : [substr($key, 0, $pos), substr($key, $pos + 1)];
    }

    /** @return array<int, string> */
    public static function splitLines(string $value): array
    {
        return array_values(array_filter(
            array_map(trim(...), preg_split('/\R/u', $value) ?: []),
            fn (string $line) => $line !== '',
        ));
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::class);
    }
}
