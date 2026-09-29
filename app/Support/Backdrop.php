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
    /** @var array<int, string>|null */
    private ?array $left = null;

    public function next(): ?string
    {
        if ($this->left === null || $this->left === []) {
            $this->left = array_keys(config('cms-covers', []));
            shuffle($this->left);
        }

        return array_pop($this->left);
    }
}
