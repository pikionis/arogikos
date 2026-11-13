<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\ContentBlock;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| The services page
|--------------------------------------------------------------------------
| This is the page that has to convert a worried parent into a phone call,
| and it carried 198 words — a card, not a page. It now answers the three
| questions a parent actually arrives with: is this us, what happens, and
| what will it be like.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function pageWords(string $html): int
{
    preg_match('/<main.*?<\/main>/s', $html, $m);
    $body = preg_replace('/<(script|style|svg).*?<\/\1>/s', '', $m[0] ?? $html);

    return count(preg_split('/\s+/', trim(strip_tags($body)), -1, PREG_SPLIT_NO_EMPTY));
}

it('carries enough content to stand as a page', function () {
    $words = pageWords($this->get(route('services'))->assertOk()->getContent());

    expect($words)->toBeGreaterThan(500);
});

it('tells a parent when to get in touch, for every service', function () {
    $html = $this->get(route('services'))->assertOk()->getContent();

    expect(substr_count($html, 'Πότε να απευθυνθείτε'))->toBe(count(content_items('services.services')));

    foreach (content_items('services.services') as $service) {
        foreach ($service['signs'] as $sign) {
            expect($html)->toContain(e($sign));
        }
    }
});

it('explains what actually happens, for every service', function () {
    $html = $this->get(route('services'))->assertOk()->getContent();

    expect(substr_count($html, 'Πώς δουλεύουμε'))->toBe(count(content_items('services.services')));

    foreach (content_items('services.services') as $service) {
        foreach ($service['process'] as $step) {
            expect($html)->toContain(e($step));
        }
    }
});

/* ---------------------------------------------------------------------- faq */

it('shows the questions parents ask', function () {
    $this->get(route('services'))
        ->assertOk()
        ->assertSeeText('Συχνές ερωτήσεις')
        ->assertSeeText('Πόσο κοστίζει η πρώτη γνωριμία;');
});

it('hides the blank question slots', function () {
    // Eight slots ship; only the filled ones may reach the page.
    $filled = collect(content_items('services.faq'))
        ->filter(fn (array $f) => filled($f['question']))
        ->count();

    $html = $this->get(route('services'))->getContent();

    expect(substr_count($html, 'class="faq-item'))->toBe($filled)
        ->and($filled)->toBeLessThan(8);
});

it('declares the questions to Google as an FAQPage', function () {
    $html = $this->get(route('services'))->getContent();

    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    $faq = null;
    foreach ($m[1] as $json) {
        $node = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($node['@type'] ?? null) === 'FAQPage') {
            $faq = $node;
        }
    }

    expect($faq)->not->toBeNull()
        ->and($faq['mainEntity'][0]['@type'])->toBe('Question')
        ->and($faq['mainEntity'][0]['acceptedAnswer']['@type'])->toBe('Answer');

    // Never a blank question — that would be invalid markup.
    foreach ($faq['mainEntity'] as $q) {
        expect(trim($q['name']))->not->toBe('')
            ->and(trim($q['acceptedAnswer']['text']))->not->toBe('');
    }
});

it('lets staff rewrite a question from the panel', function () {
    $faq = content_items('services.faq');
    $faq[0]['question'] = 'Μια δική μας ερώτηση;';
    $faq[0]['answer'] = 'Και η δική μας απάντηση.';

    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'faq',
        'locale' => 'el',
        'value' => json_encode($faq, JSON_UNESCAPED_UNICODE),
    ]);

    $this->get(route('services'))
        ->assertOk()
        ->assertSeeText('Μια δική μας ερώτηση;')
        ->assertSeeText('Και η δική μας απάντηση.');
});

it('lets staff add a question in a blank slot', function () {
    $faq = content_items('services.faq');
    $faq[7] = ['question' => 'Προσθήκη σε κενή θέση;', 'answer' => 'Εμφανίζεται κανονικά.'];

    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'faq',
        'locale' => 'el',
        'value' => json_encode($faq, JSON_UNESCAPED_UNICODE),
    ]);

    $this->get(route('services'))->assertOk()->assertSeeText('Προσθήκη σε κενή θέση;');
});

it('drops the whole section when every question is cleared', function () {
    $faq = array_map(fn () => ['question' => '', 'answer' => ''], range(1, 8));

    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'faq',
        'locale' => 'el',
        'value' => json_encode($faq, JSON_UNESCAPED_UNICODE),
    ]);

    $html = $this->get(route('services'))->assertOk()->getContent();

    expect($html)->not->toContain('class="faq-item')
        ->and($html)->not->toContain('FAQPage');
});

it('offers every new field in the panel', function () {
    $this->actingAs(
        tap(User::factory()->create(), fn ($u) => $u->forceFill([
            'role' => Role::Owner, 'is_active' => true,
        ])->save())->refresh()
    );

    // Everything about a service is now managed on its own page, next to the
    // headings it shares and the questions that follow it.
    $html = $this->get(route('admin.pages.edit', 'services'))->assertOk()->getContent();

    expect($html)->toContain('fields[faq]')
        ->and($html)->toContain('fields[services][0][name]')
        ->and($html)->toContain('fields[services][0][signs]')
        ->and($html)->toContain('fields[services][0][on_home]')
        ->and($html)->toContain('fields[services][0][icon]')
        ->and($html)->toContain(e('Πότε να απευθυνθείτε'));
});
