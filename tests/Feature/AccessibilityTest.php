<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use Database\Seeders\DatabaseSeeder;

/*
|--------------------------------------------------------------------------
| Accessibility
|--------------------------------------------------------------------------
| The audience is families of children with learning difficulties, so this is
| not box-ticking. These cover the structural defects a screen reader hits and
| the colour contrasts the palette has to clear — both of which are easy to
| reintroduce by accident in a template or a token.
*/

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

/** Every heading on the page, in document order, as [level, text]. */
function headings(string $html): array
{
    preg_match_all('/<h([1-6])\b([^>]*)>(.*?)<\/h\1>/s', $html, $m, PREG_SET_ORDER);

    return array_map(function (array $tag) {
        // An explicit aria-level overrides the tag for assistive tech.
        $level = preg_match('/aria-level="(\d)"/', $tag[2], $a)
            ? (int) $a[1]
            : (int) $tag[1];

        return [$level, trim(strip_tags($tag[3]))];
    }, $m);
}

/* ------------------------------------------------------------------ headings */

it('gives every page exactly one h1', function (string $routeName) {
    $levels = array_column(headings($this->get(route($routeName))->assertOk()->getContent()), 0);

    expect(array_count_values($levels)[1] ?? 0)->toBe(1);
})->with(['home', 'about', 'services', 'blog.index', 'events', 'gallery', 'contact']);

it('never skips a heading level', function (string $routeName) {
    // Jumping h1 -> h3 tells a screen-reader user a section is missing.
    $headings = headings($this->get(route($routeName))->assertOk()->getContent());

    $skips = [];
    $previous = 0;

    foreach ($headings as [$level, $text]) {
        if ($previous > 0 && $level > $previous + 1) {
            $skips[] = "h{$level} μετά από h{$previous}: ".mb_substr($text, 0, 40);
        }
        $previous = $level;
    }

    expect($skips)->toBe([]);
})->with(['home', 'about', 'services', 'blog.index', 'events', 'gallery', 'contact']);

it('never skips a heading level on an article page', function () {
    $article = Article::published()->firstOrFail();

    $headings = headings($this->get(route('blog.show', $article->slug))->assertOk()->getContent());

    $skips = [];
    $previous = 0;

    foreach ($headings as [$level, $text]) {
        if ($previous > 0 && $level > $previous + 1) {
            $skips[] = "h{$level} μετά από h{$previous}";
        }
        $previous = $level;
    }

    expect($skips)->toBe([]);
});

/* -------------------------------------------------------------------- images */

it('gives every image an alt attribute', function (string $routeName) {
    preg_match_all('/<img\b[^>]*>/', $this->get(route($routeName))->getContent(), $m);

    $missing = array_values(array_filter($m[0], fn (string $tag) => ! str_contains($tag, 'alt=')));

    expect($missing)->toBe([]);
})->with(['home', 'about', 'blog.index', 'gallery']);

/* --------------------------------------------------------------------- links */

it('gives every link a name a screen reader can announce', function (string $routeName) {
    preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/s', $this->get(route($routeName))->getContent(), $m, PREG_SET_ORDER);

    $nameless = array_values(array_filter($m, function (array $link) {
        $hasText = trim(strip_tags($link[2])) !== '';
        $hasLabel = str_contains($link[1], 'aria-label') || str_contains($link[1], 'title=');

        return ! $hasText && ! $hasLabel;
    }));

    expect($nameless)->toBe([]);
})->with(['home', 'contact', 'blog.index', 'gallery']);

/* ------------------------------------------------------------------ contrast */

/** WCAG relative luminance. */
function luminance(string $hex): float
{
    [$r, $g, $b] = array_map(
        fn (string $c) => hexdec($c) / 255,
        str_split(ltrim($hex, '#'), 2)
    );

    $channel = fn (float $c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

    return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
}

function contrast(string $a, string $b): float
{
    $la = luminance($a);
    $lb = luminance($b);

    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

/** Read a custom property straight out of the stylesheet. */
function token(string $name): string
{
    $css = file_get_contents(resource_path('css/arogi.css'));

    preg_match('/--'.preg_quote($name, '/').':\s*(#[0-9A-Fa-f]{6})/', $css, $m);

    return $m[1];
}

it('clears AA for body text on both page backgrounds', function (string $name) {
    // 4.5:1 is the threshold for text below 18.66px bold.
    expect(contrast(token($name), '#FFFFFF'))->toBeGreaterThanOrEqual(4.5)
        ->and(contrast(token($name), token('off-white')))->toBeGreaterThanOrEqual(4.5);
})->with(['ink', 'ink-600', 'ink-400']);

it('clears AA for form error text', function () {
    // Nobody can fix a form error they cannot read.
    expect(contrast(token('coral-700'), '#FFFFFF'))->toBeGreaterThanOrEqual(4.5);
});

it('clears AA for link text on white', function () {
    expect(contrast(token('green-700'), '#FFFFFF'))->toBeGreaterThanOrEqual(4.5);
});

it('does not put white on the yellow avatar', function () {
    // White on this yellow is 2.46:1 — it fails at every size, so the rule
    // sets ink instead.
    $css = file_get_contents(resource_path('css/arogi.css'));

    preg_match('/\.avatar\.y\s*\{([^}]*)\}/', $css, $m);

    expect($m[1])->toContain('var(--ink)');
    expect(contrast(token('ink'), token('yellow')))->toBeGreaterThanOrEqual(4.5);
});

it('keeps the admin palette in step with the site palette', function () {
    // The panel mirrors these as Tailwind tokens; a fix applied to one and not
    // the other quietly reintroduces the problem for staff.
    $admin = file_get_contents(resource_path('css/_tokens.css'));

    foreach (['ink-400', 'coral-700'] as $name) {
        expect(strtolower($admin))->toContain('--color-'.$name.': '.strtolower(token($name)));
    }
});

it('draws the menu icon on whole pixels', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    preg_match('/\.burger span \{[^}]*height: ([\d.]+)px/', $css, $height);
    preg_match('/\.burger span \+ span \{[^}]*margin-top: ([\d.]+)px/', $css, $gap);

    // Three bars at a half-pixel height each land on a different part of a
    // device pixel, so the screen rounds each one differently and the middle
    // one comes out visibly fainter. It read as a design decision; it was
    // rounding.
    expect((float) $height[1])->toBe(floor((float) $height[1]))
        ->and((float) $gap[1])->toBe(floor((float) $gap[1]));

    // And the open state has to move bar one exactly onto bar two.
    preg_match('/nth-child\(1\) \{ transform: translateY\(([\d.]+)px\)/', $css, $shift);

    expect((float) $shift[1])->toBe((float) $height[1] + (float) $gap[1]);
});
