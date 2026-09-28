<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Event;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Προεπισκόπηση πριν τη δημοσίευση
|--------------------------------------------------------------------------
| Το ζητούμενο: να δει κανείς το άρθρο ή την εκδήλωση όπως θα τη δει ο
| επισκέπτης, ενώ είναι ακόμη πρόχειρο. Άρα η προεπισκόπηση αποδίδει τα
| πραγματικά δημόσια templates — αλλιώς δείχνει μόνο πώς φαίνεται η ίδια.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

/* staff() is declared once for the whole suite, in Admin/ArticleCrudTest.php. */

function draftArticle(array $attributes = []): Article
{
    return Article::factory()->create($attributes + [
        'title' => 'Ένα πρόχειρο άρθρο',
        'is_published' => false,
        'published_at' => null,
    ]);
}

/* ----------------------------------------------------------------- guard */

it('keeps a draft away from the public', function () {
    $article = draftArticle();

    // The public route only ever serves published work.
    $this->get(route('blog.show', $article->slug))->assertNotFound();
});

it('sends a guest who asks for a preview to the login screen', function () {
    $article = draftArticle();

    $this->get(route('admin.preview.article', $article))
        ->assertRedirect(route('admin.login'));
});

it('refuses a deactivated account', function () {
    $user = staff();
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)
        ->get(route('admin.preview.article', draftArticle()))
        ->assertRedirect();
});

/* --------------------------------------------------------------- article */

it('shows a signed-in editor the draft in the real article template', function () {
    $article = draftArticle(['body' => '<p>Το κείμενο του πρόχειρου.</p>']);

    $this->actingAs(staff())
        ->get(route('admin.preview.article', $article))
        ->assertOk()
        ->assertSeeText('Ένα πρόχειρο άρθρο')
        ->assertSeeText('Το κείμενο του πρόχειρου.')
        // The real page furniture, not a stand-in.
        ->assertSee('site-header', false);
});

it('says plainly that a draft is not public yet', function () {
    $this->actingAs(staff())
        ->get(route('admin.preview.article', draftArticle()))
        ->assertOk()
        ->assertSee('preview-draft', false)
        ->assertSeeText('δεν είναι ορατό σε κανέναν επισκέπτη');
});

it('tells apart a draft from one that is merely dated ahead', function () {
    $scheduled = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->addWeek(),
    ]);

    $this->actingAs(staff())
        ->get(route('admin.preview.article', $scheduled))
        ->assertOk()
        ->assertSee('preview-scheduled', false)
        ->assertSeeText('θα εμφανιστεί μόνο του');
});

it('marks a preview of something already public as such', function () {
    $live = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs(staff())
        ->get(route('admin.preview.article', $live))
        ->assertOk()
        ->assertSee('preview-live', false);
});

it('never shows the preview bar on a public page', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);

    foreach (['home', 'blog.index', 'events', 'services'] as $route) {
        $this->get(route($route))->assertOk()->assertDontSee('preview-bar', false);
    }
});

/* ----------------------------------------------------------------- event */

it('drops a draft event into the list where it will land', function () {
    $event = Event::factory()->create([
        'title' => 'Μια πρόχειρη εκδήλωση',
        'is_published' => false,
        'starts_at' => now()->addMonth(),
    ]);

    $this->actingAs(staff())
        ->get(route('admin.preview.event', $event))
        ->assertOk()
        ->assertSeeText('Μια πρόχειρη εκδήλωση')
        ->assertSee('preview-draft', false);
});

it('puts a past draft event among the past ones', function () {
    $event = Event::factory()->create([
        'title' => 'Περσινή εκδήλωση',
        'is_published' => false,
        'starts_at' => now()->subYear(),
    ]);

    $html = $this->actingAs(staff())
        ->get(route('admin.preview.event', $event))
        ->assertOk()
        ->getContent();

    // The past band only renders when it has something in it.
    expect($html)->toContain(e('Περσινή εκδήλωση'))
        ->and($html)->toContain(e(content('events.past.title')));
});

it('does not leak a draft event onto the public list', function () {
    Event::factory()->create(['title' => 'Αόρατη εκδήλωση', 'is_published' => false, 'starts_at' => now()->addMonth()]);

    $this->get(route('events'))->assertOk()->assertDontSee('Αόρατη εκδήλωση');
});
