<?php

declare(strict_types=1);

use App\Models\Cms\Author;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Η ομάδα στο «Σχετικά με εμάς»
|--------------------------------------------------------------------------
| Τα βιογραφικά πήγαιναν από 14 έως 1656 χαρακτήρες. Σε τρεις στήλες, η μία
| γινόταν τοίχος κειμένου και η διπλανή δύο γραμμές. Τώρα το βιογραφικό ζει
| κομμένο σε κουτί σταθερού ύψους, και ολόκληρο σε ένα dialog.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function longBio(int $chars = 900): string
{
    return str_repeat('Εργάζεται με παιδιά και εφήβους στην Κω. ', (int) ceil($chars / 40));
}

it('puts every bio in a box under the photo', function () {
    Author::factory()->create(['is_active' => true, 'bio' => 'Σύντομο βιογραφικό.']);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('team-bio', false);
});

it('clamps a long bio and offers to open it', function () {
    $author = Author::factory()->create(['is_active' => true, 'bio' => longBio()]);

    $html = $this->get(route('about'))->assertOk()->getContent();

    expect($html)->toContain('is-clamped')
        ->and($html)->toContain('data-team-open="team-'.$author->id.'"')
        ->and($html)->toContain('Περισσότερα');
});

it('leaves a short bio alone', function () {
    Author::factory()->create(['is_active' => true, 'bio' => 'Λογοθεραπεύτρια.']);

    $html = $this->get(route('about'))->getContent();

    // Nothing to reveal, so no button and no dialog that would open empty.
    expect($html)->not->toContain('is-clamped')
        ->and($html)->not->toContain('Περισσότερα')
        ->and($html)->not->toContain('<dialog');
});

it('carries the whole bio in the dialog, not a truncated copy', function () {
    $bio = longBio().'ΤΕΛΕΥΤΑΙΑ ΠΡΟΤΑΣΗ ΠΟΥ ΠΡΕΠΕΙ ΝΑ ΦΑΙΝΕΤΑΙ.';
    Author::factory()->create(['is_active' => true, 'bio' => $bio]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSeeText('ΤΕΛΕΥΤΑΙΑ ΠΡΟΤΑΣΗ ΠΟΥ ΠΡΕΠΕΙ ΝΑ ΦΑΙΝΕΤΑΙ.');
});

it('shows the photo again inside the dialog', function () {
    Author::factory()->create(['is_active' => true, 'bio' => longBio()]);

    $html = $this->get(route('about'))->getContent();

    expect($html)->toContain('team-modal-photo');
});

/* --------------------------------------------------------- accessibility */

it('uses a real dialog and a real button', function () {
    $author = Author::factory()->create(['is_active' => true, 'bio' => longBio()]);

    $html = $this->get(route('about'))->getContent();

    expect($html)->toContain('<dialog id="team-'.$author->id.'"')
        ->and($html)->toContain('aria-haspopup="dialog"')
        // A div with a click handler is invisible to the keyboard.
        ->and($html)->toContain('<button type="button" class="team-more"');
});

it('names the dialog after the person it is about', function () {
    $author = Author::factory()->create(['is_active' => true, 'name' => 'Μαρία Παπαδοπούλου', 'bio' => longBio()]);

    $html = $this->get(route('about'))->getContent();

    expect($html)->toContain('aria-labelledby="team-'.$author->id.'-name"')
        ->and($html)->toContain('id="team-'.$author->id.'-name"');
});

it('escapes a bio that contains markup', function () {
    Author::factory()->create([
        'is_active' => true,
        'bio' => longBio().'<script>alert(1)</script>',
    ]);

    $html = $this->get(route('about'))->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;');
});

it('keeps the motion out of the way when the reader asked it to stop', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    preg_match_all('/@media \(prefers-reduced-motion: reduce\)\s*\{(.*?)\n\}/s', $css, $m);

    expect(implode("\n", $m[1]))->toContain('.team-modal-panel');
});

it('does not show a withdrawn author at all', function () {
    Author::factory()->create(['is_active' => false, 'name' => 'Αποσυρμένος', 'bio' => longBio()]);

    $this->get(route('about'))
        ->assertOk()
        ->assertDontSee('Αποσυρμένος');
});
