<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Event;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Ό,τι γράφεται στον επεξεργαστή, φτάνει στο site
|--------------------------------------------------------------------------
| Ο καθαριστής HTML κρατούσε λίστα με τα επιτρεπόμενα στοιχεία, και ό,τι δεν
| ήταν στη λίστα πεταγόταν ΜΑΖΙ με το κείμενό του. Ο Trix — ο επεξεργαστής
| του panel — γράφει <div> για κάθε παράγραφο, που δεν ήταν στη λίστα: ένα
| ολόκληρο άρθρο γραμμένο στο panel αποθηκευόταν ως κενό.
|
| Τα προηγούμενα τεστ έστελναν <p> κατευθείαν, δηλαδή δοκίμαζαν κάτι που ο
| επεξεργαστής δεν παράγει ποτέ. Αυτά εδώ στέλνουν ό,τι στέλνει εκείνος.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function scribe(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    return $user->refresh();
}

/* ------------------------------------------------------- the sanitiser */

it('keeps a paragraph the editor wrote as a div', function () {
    expect(HtmlSanitizer::clean('<div>Το κείμενο του άρθρου.</div>'))
        ->toBe('<p>Το κείμενο του άρθρου.</p>');
});

it('keeps them apart when there are several', function () {
    expect(HtmlSanitizer::clean('<div>Πρώτη.</div><div>Δεύτερη.</div>'))
        ->toBe('<p>Πρώτη.</p><p>Δεύτερη.</p>');
});

it('keeps what the toolbar buttons produce', function () {
    $cases = [
        '<div>Λίγο <strong>έντονα</strong>.</div>' => 'έντονα',
        '<div>Και <em>πλάγια</em>.</div>' => 'πλάγια',
        '<div>Ένα <del>σβησμένο</del>.</div>' => 'σβησμένο',
        '<ul><li>ένα</li><li>δύο</li></ul>' => '<li>ένα</li>',
        '<blockquote>Ένα απόσπασμα.</blockquote>' => 'απόσπασμα',
        '<pre>κώδικας</pre>' => 'κώδικας',
        '<div>Δείτε <a href="https://arogikos.gr">εδώ</a>.</div>' => 'arogikos.gr',
    ];

    $lost = [];
    foreach ($cases as $written => $expected) {
        if (! str_contains((string) HtmlSanitizer::clean($written), $expected)) {
            $lost[] = $written;
        }
    }

    expect($lost)->toBe([]);
});

it('demotes the editor heading so the page keeps one h1', function () {
    // The article title is the h1. A second one breaks the outline, and there
    // is a test that counts them.
    expect(HtmlSanitizer::clean('<h1>Τίτλος μέσα στο κείμενο</h1>'))
        ->toBe('<h2>Τίτλος μέσα στο κείμενο</h2>');
});

it('keeps the words out of a container it does not know', function () {
    // Pasted from a word processor. Losing the paragraph style is a shame;
    // losing the sentence is a bug.
    expect(HtmlSanitizer::clean('<section><div>Από αλλού.</div></section>'))
        ->toContain('Από αλλού.');
});

it('still throws out what is dangerous', function () {
    $clean = (string) HtmlSanitizer::clean(
        '<div onclick="steal()">Κείμενο<script>alert(1)</script></div>'
    );

    expect($clean)->toContain('Κείμενο')
        ->and($clean)->not->toContain('script')
        ->and($clean)->not->toContain('onclick');

    expect((string) HtmlSanitizer::clean('<div><a href="javascript:alert(1)">κλικ</a></div>'))
        ->not->toContain('javascript:');
});

it('reads a cleared editor as empty', function () {
    foreach (['<div><br></div>', '<p></p>', '<div>   </div>', ''] as $emptied) {
        expect(HtmlSanitizer::clean($emptied))->toBeNull();
    }
});

/* ------------------------------------------------- through the real form */

it('puts an article written in the panel onto the site', function () {
    $this->actingAs(scribe())->post(route('admin.articles.store'), [
        'title' => 'Πώς στηρίζουμε τη μελέτη',
        'excerpt' => 'Πρακτικές ιδέες για γονείς.',
        // Exactly what the editor posts.
        'body' => '<div>Η πρώτη παράγραφος.</div><div>Και η δεύτερη.</div>',
        'category' => 'parents',
        'is_published' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $article = Article::firstOrFail();

    expect($article->body)->toContain('Η πρώτη παράγραφος.')
        ->and($article->body)->toContain('Και η δεύτερη.');

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Η πρώτη παράγραφος.')
        ->assertSeeText('Και η δεύτερη.');
});

it('puts an event written in the panel onto its page', function () {
    $owner = User::factory()->create();
    $owner->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    $this->actingAs($owner->refresh())->post(route('admin.events.store'), [
        'title' => 'Ημερίδα για εκπαιδευτικούς',
        'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        'body' => '<div>Τι θα συζητήσουμε.</div>',
        'is_published' => '1',
        'published_at' => now()->subDay()->format('Y-m-d\TH:i'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $event = Event::firstOrFail();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSeeText('Τι θα συζητήσουμε.');
});

/* -------------------------------------------------------- the excerpt */

it('shows the summary on the article, not only to Google', function () {
    $article = Article::factory()->create([
        'excerpt' => 'Δύο γραμμές που λένε αν αξίζει να το διαβάσεις.',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Δύο γραμμές που λένε αν αξίζει να το διαβάσεις.')
        ->assertSee('article-lead', false);
});

it('leaves the space alone when there is no summary', function () {
    $article = Article::factory()->create([
        'excerpt' => null,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($this->get(route('blog.show', $article->slug))->getContent())
        ->not->toContain('article-lead');
});

it('saves the summary the way it was typed', function () {
    $this->actingAs(scribe())->post(route('admin.articles.store'), [
        'title' => 'Με περίληψη',
        'excerpt' => 'Η περίληψη που γράψαμε.',
        'body' => '<div>Κείμενο.</div>',
        'category' => 'parents',
        'is_published' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Article::firstOrFail()->excerpt)->toBe('Η περίληψη που γράψαμε.');
});
