<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Which scene runs faintly behind a band on this page load.
 *
 * The site has twenty-three of them and they are already drawn; using one as
 * a wash behind a section costs nothing and gives the page something that
 * moves. Which one is chosen at random per visit, so the same page is not the
 * same picture every time.
 *
 * Bound as a singleton, so within one page every band gets a different scene
 * and the next visitor gets a different set.
 */
class Backdrop
{
    /** @var array<int, string> */
    private array $used = [];

    /**
     * @param  string|null  $for  a key in config/cms-backdrops.php — the
     *                            scenes that suit that particular band
     */
    public function next(?string $for = null): ?string
    {
        $candidates = $this->candidates($for);

        if ($candidates === []) {
            return null;
        }

        // The same picture twice on one page reads as a mistake rather than a
        // decision, so what has already been used goes to the back.
        $fresh = array_values(array_diff($candidates, $this->used));

        $pool = $fresh !== [] ? $fresh : $candidates;
        $scene = $pool[array_rand($pool)];

        $this->used[] = $scene;

        return $scene;
    }

    /** @return array<int, string> */
    private function candidates(?string $for): array
    {
        $all = array_keys(config('cms-covers', []));

        if ($for === null) {
            return $all;
        }

        // Indexed straight into the array rather than through config()'s dot
        // notation: the keys are "about.values", one string with a dot in it,
        // not a nested array.
        $shortlist = (array) (config('cms-backdrops', [])[$for] ?? []);

        // A shortlist naming a scene that has since been retired would
        // otherwise leave the band empty.
        $shortlist = array_values(array_intersect($shortlist, $all));

        return $shortlist !== [] ? $shortlist : $all;
    }
}
