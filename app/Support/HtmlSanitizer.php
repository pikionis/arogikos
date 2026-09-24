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

        $clean = trim(self::sanitizer()->sanitize($html));

        // Trix and most editors leave an empty paragraph behind when the user
        // clears the field; treat that as empty so `nullable` rules behave.
        return in_array($clean, ['', '<p></p>', '<br>', '<div></div>'], true) ? null : $clean;
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
                ->allowElement('a', ['href'])
                // Links: http/https/mailto/tel only — blocks javascript: and
                // data: URLs.
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->forceHttpsUrls(false)
                ->forceAttribute('a', 'rel', 'noopener nofollow')
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
