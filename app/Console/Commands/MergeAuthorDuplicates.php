<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cms\Author;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repairs accounts that ended up with two author profiles.
 *
 * Before invitations learned to adopt an existing profile, accepting one
 * always opened a blank author beside the real one the centre had already
 * written. The person then had two: the site kept showing the one with the
 * photo and the articles, while their account pointed at the empty one, so
 * editing "their" profile changed nothing anybody could see.
 *
 * The blank one goes and the real one takes over the account link. A profile
 * is only ever deleted when it is provably empty — no photo, no bio, no role,
 * no articles — and only when a real namesake exists to take its place.
 * Everything else is reported and left alone.
 */
class MergeAuthorDuplicates extends Command
{
    protected $signature = 'cms:merge-author-duplicates {--apply : Actually make the changes}';

    protected $description = 'Join an account to the author profile that already has its photo and articles';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $unlinked = Author::query()->whereNull('user_id')->withCount('articles')->get();
        $merges = [];
        $notes = [];

        foreach (User::with('author')->get() as $user) {
            $blank = $user->author;

            if (! $blank) {
                continue;
            }

            $wanted = Author::normaliseName($blank->name);

            $real = $unlinked->first(fn (Author $a) => Author::normaliseName($a->name) === $wanted);

            if (! $real) {
                continue;
            }

            if (! $this->isEmpty($blank)) {
                // Two profiles that both hold something is not ours to decide.
                $notes[] = "{$user->email}: both «{$blank->name}» profiles hold content — left alone";

                continue;
            }

            $merges[] = ['user' => $user, 'blank' => $blank, 'real' => $real];
            $unlinked = $unlinked->reject(fn (Author $a) => $a->is($real));
        }

        foreach ($notes as $note) {
            $this->warn('  '.$note);
        }

        if ($merges === []) {
            $this->info('  Nothing to merge.');

            return self::SUCCESS;
        }

        foreach ($merges as $merge) {
            $this->line(sprintf(
                '  %s: keep #%d «%s» (%d άρθρα%s), drop empty #%d',
                $merge['user']->email,
                $merge['real']->id,
                $merge['real']->name,
                $merge['real']->articles_count,
                $merge['real']->photoUrl('thumb') ? ', με φωτογραφία' : '',
                $merge['blank']->id,
            ));
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('  Dry run. Re-run with --apply to make these changes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($merges) {
            foreach ($merges as $merge) {
                // Unlink first: user_id is unique, so the real one cannot take
                // it while the blank one still holds it.
                $merge['blank']->forceFill(['user_id' => null])->save();
                $merge['real']->forceFill(['user_id' => $merge['user']->getKey()])->save();
                $merge['blank']->delete();
            }
        });

        $this->newLine();
        $this->info('  Merged '.count($merges).'.');

        return self::SUCCESS;
    }

    /** Nothing anybody would miss: no photo, no words, no articles. */
    private function isEmpty(Author $author): bool
    {
        return blank($author->role_title)
            && blank($author->bio)
            && $author->articles()->count() === 0
            && $author->photoUrl('thumb') === null;
    }
}
