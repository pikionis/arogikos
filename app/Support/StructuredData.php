<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\Article;
use Illuminate\Support\Carbon;

/**
 * schema.org JSON-LD for the public site.
 *
 * Everything here is assembled from the Settings table, so the markup Google
 * reads can never drift from what a visitor sees on the page — change the
 * phone number in the panel and the structured data follows.
 *
 * Kept in PHP rather than hand-written into Blade so it can be asserted
 * against in tests; a typo in a JSON-LD block fails silently in production.
 */
class StructuredData
{
    /** Stable node ids, so the graph can reference itself instead of repeating. */
    private const ORG_ID = '#organization';

    private const SITE_ID = '#website';

    /**
     * The site-wide graph: who the centre is, where it is, what it offers.
     *
     * @return array<string, mixed>
     */
    public static function site(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                self::localBusiness(),
                self::website(),
                ...self::services(),
            ])),
        ];
    }

    /* ---------------------------------------------------------- the centre */

    /**
     * EducationalOrganization + LocalBusiness.
     *
     * Both types on one node on purpose: the centre is a school-like service
     * AND a place you visit, and the two drive different Google surfaces
     * (knowledge panel vs the local map pack).
     *
     * @return array<string, mixed>
     */
    public static function localBusiness(): array
    {
        $node = [
            '@type' => ['EducationalOrganization', 'LocalBusiness'],
            '@id' => url('/').self::ORG_ID,
            'name' => setting('org_name', 'Αρωγή'),
            'url' => url('/'),
            'description' => setting('footer_tagline'),
            'inLanguage' => 'el',
        ];

        if (filled($phone = setting('phone_href', setting('phone')))) {
            $node['telephone'] = $phone;
        }

        if (filled($email = setting('email'))) {
            $node['email'] = $email;
        }

        if ($address = self::postalAddress()) {
            $node['address'] = $address;
        }

        if ($geo = self::geo()) {
            $node['geo'] = $geo;
        }

        if (filled($map = setting('maps_url'))) {
            $node['hasMap'] = $map;
        }

        if (filled($hours = setting('opening_hours_schema'))) {
            // "Mo-Fr 13:00-21:00" — the machine-readable twin of the human
            // string shown on the contact page.
            $node['openingHours'] = $hours;
        }

        if ($social = self::socialProfiles()) {
            $node['sameAs'] = $social;
        }

        if ($image = self::shareImage()) {
            $node['image'] = $image;
        }

        $node['areaServed'] = ['@type' => 'Place', 'name' => setting('locality', 'Κως')];

        return $node;
    }

    /** @return array<string, mixed>|null */
    private static function postalAddress(): ?array
    {
        $street = setting('street_address');
        $locality = setting('locality');
        $postal = setting('postal_code');

        // Without at least a street and a town this is worth less than the
        // plain-text address, so fall back rather than emit a stub.
        if (blank($street) || blank($locality)) {
            return filled($whole = setting('address'))
                ? ['@type' => 'PostalAddress', 'streetAddress' => $whole, 'addressCountry' => 'GR']
                : null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => $locality,
            'postalCode' => $postal,
            'addressRegion' => setting('region'),
            'addressCountry' => 'GR',
        ]);
    }

    /** @return array<string, mixed>|null */
    private static function geo(): ?array
    {
        $lat = setting('geo_lat');
        $lng = setting('geo_lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /** @return array<int, string> */
    private static function socialProfiles(): array
    {
        return array_values(array_filter([
            setting('instagram'),
            setting('facebook'),
        ], fn (?string $u) => filled($u)));
    }

    private static function shareImage(): ?string
    {
        // Same picture the social cards use, so the graph and a shared link
        // never show two different images for the same centre.
        return ShareImage::resolve()['url'];
    }

    /* -------------------------------------------------------------- website */

    /** @return array<string, mixed> */
    private static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').self::SITE_ID,
            'url' => url('/'),
            'name' => setting('org_name', 'Αρωγή'),
            'inLanguage' => 'el',
            'publisher' => ['@id' => url('/').self::ORG_ID],
        ];
    }

    /* ------------------------------------------------------------- services */

    /**
     * One Service node per service the centre offers.
     *
     * Reads the same shared.services content the pages render, so adding a
     * service in the panel puts it in the structured data too.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function services(): array
    {
        return collect(content_items('shared.services'))
            ->filter(fn (array $s) => filled($s['name'] ?? null))
            ->map(fn (array $s) => array_filter([
                '@type' => 'Service',
                'name' => $s['name'],
                'description' => $s['lead'] ?? $s['card_body'] ?? null,
                'url' => route('services'),
                'provider' => ['@id' => url('/').self::ORG_ID],
                'areaServed' => ['@type' => 'Place', 'name' => setting('locality', 'Κως')],
                'serviceType' => $s['name'],
            ]))
            ->values()
            ->all();
    }

    /* -------------------------------------------------------------- article */

    /**
     * BlogPosting for a single article.
     *
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $article->title,
            'url' => route('blog.show', $article->slug),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('blog.show', $article->slug)],
            'inLanguage' => 'el',
            'publisher' => ['@id' => url('/').self::ORG_ID],
        ];

        if (filled($article->excerpt)) {
            $node['description'] = $article->excerpt;
        }

        if ($article->published_at instanceof Carbon) {
            $node['datePublished'] = $article->published_at->toIso8601String();
        }

        if ($article->updated_at instanceof Carbon) {
            $node['dateModified'] = $article->updated_at->toIso8601String();
        }

        if ($article->author) {
            $node['author'] = array_filter([
                '@type' => 'Person',
                'name' => $article->author->name,
                'jobTitle' => $article->author->role_title,
                'image' => $article->author->photoUrl('web'),
            ]);
        }

        if (filled($cover = $article->getFirstMediaUrl('cover', 'web'))) {
            $node['image'] = $cover;
        }

        if (filled($article->category_label)) {
            $node['articleSection'] = $article->category_label;
        }

        return $node;
    }

    /* ------------------------------------------------------------------ faq */

    /**
     * FAQPage for the services page.
     *
     * Only the filled-in pairs: an empty slot is how staff remove a question,
     * and a blank entry here would be invalid markup.
     *
     * @return array<string, mixed>|null
     */
    public static function faq(): ?array
    {
        $entries = collect(content_items('services.faq'))
            ->filter(fn (array $f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null))
            ->values();

        if ($entries->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entries->map(fn (array $f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ])->all(),
        ];
    }

    /* ---------------------------------------------------------- breadcrumbs */

    /**
     * @param  array<int, array{name: string, url: string|null}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)
                ->values()
                ->map(fn (array $c, int $i) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $c['name'],
                    'item' => $c['url'] ?? null,
                ]))
                ->all(),
        ];
    }
}
