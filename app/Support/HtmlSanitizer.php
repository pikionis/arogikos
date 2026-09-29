<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Allowlist sanitiser for editor-supplied rich text.
 *
 * Article and page bodies are rendered with {!! !!} (see
 * resources/views/pages/blog/show.blade.php and pages/about.blade.php), so
 * unsanitised HTML from an `editor` account would be stored XSS against every
 * visitor — and against the owner's admin session, i.e. privilege escalation.
 *
 * Sanitise on write, keep the raw render on read.
 */
class HtmlSanitizer
{
    private static ?SymfonySanitizer $sanitizer = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $clean = trim(self::sanitizer()->sanitize(self::normalise($html)));

        // A cleared editor still posts a block with a line break inside it,
        // and each one writes that differently. Anything with no words left
        // in it is empty, so `nullable` rules behave.
        return trim(strip_tags($clean)) === '' ? null : $clean;
    }

    /**
     * Editors do not all write the same HTML.
     *
     * Trix — the editor in this panel — writes one <div> per paragraph, and
     * <h1> for its single heading button. Neither was on the allowlist, and an
     * element that is not on it is dropped with everything inside it. A whole
     * article typed into the editor therefore saved as nothing at all, which
     * is exactly what happened.
     *
     * They are translated rather than allowed through: <p> is the paragraph
     * this site styles, and the page already has its one h1 in the title, so
     * a second one in the body would break the heading outline.
     */
    private static function normalise(string $html): string
    {
        return preg_replace(
            ['/<div\b[^>]*>/i', '/<\/div>/i', '/<(\/?)h1\b([^>]*)>/i'],
            ['<p>', '</p>', '<$1h2$2>'],
            $html
        ) ?? $html;
    }

    private static function sanitizer(): SymfonySanitizer
    {
        return self::$sanitizer ??= new SymfonySanitizer(
            (new HtmlSanitizerConfig)
                // Structure the design actually styles. No <script>, <style>,
                // <iframe>, <form>, <object>, and no inline event handlers or
                // style attributes — none are in the allowlist, so they go.
                ->allowElement('p')
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('u')
                ->allowElement('h2')
                ->allowElement('h3')
                ->allowElement('h4')
                ->allowElement('ul')
                ->allowElement('ol')
                ->allowElement('li')
                ->allowElement('blockquote')
                // The rest of what the editor's buttons produce. Leaving one
                // out does not make it safer — it makes the text under it
                // disappear without a word.
                ->allowElement('del')
                ->allowElement('s')
                ->allowElement('pre')
                ->allowElement('code')
                ->allowElement('a', ['href'])
                // Links: http/https/mailto/tel only — blocks javascript: and
                // data: URLs.
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->forceHttpsUrls(false)
                ->forceAttribute('a', 'rel', 'noopener nofollow')
                // Unwrapped, not dropped: pasted text arrives inside all
                // sorts of containers, and losing the words inside one is a
                // worse failure than keeping them unstyled.
                ->blockElement('div')
                ->blockElement('span')
                ->blockElement('section')
                ->blockElement('article')
                ->blockElement('font')
                ->dropElement('script')
                ->dropElement('style')
                ->dropElement('iframe')
                ->dropElement('form')
                ->dropElement('object')
                ->dropElement('embed')
                ->withMaxInputLength(500_000)
        );
    }
}
