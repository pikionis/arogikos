<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Initials for an avatar chip, e.g. "Μαρία Κωνσταντίνου" → "ΜΚ".
 *
 * Both the public author and the panel account show one, and both need the
 * same rule about the tonos, so the rule lives in one place.
 */
trait HasInitials
{
    public function initials(): string
    {
        $initials = Str::of((string) $this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        // Greek uppercase drops the tonos (e.g. "Άννα" → "Α", not "Ά").
        return strtr($initials, [
            'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι',
            'Ό' => 'Ο', 'Ύ' => 'Υ', 'Ώ' => 'Ω', 'Ϊ' => 'Ι', 'Ϋ' => 'Υ',
        ]);
    }
}
