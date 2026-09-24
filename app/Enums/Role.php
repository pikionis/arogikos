<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Admin panel access level.
 *
 * Backed by a plain string column rather than a DB enum: on SQLite (which is
 * what both dev and production use) $table->enum() compiles to varchar plus a
 * CHECK constraint, and adding a role later would mean a full table rebuild.
 */
enum Role: string
{
    /** Full access, including users and settings. Cannot be demoted away. */
    case Owner = 'owner';

    /** All content, plus inviting editors. Cannot touch owners. */
    case Admin = 'admin';

    /** Articles, events and gallery only. */
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Ιδιοκτήτης',
            self::Admin => 'Διαχειριστής',
            self::Editor => 'Συντάκτης',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Πλήρης πρόσβαση, συμπεριλαμβανομένων χρηστών και ρυθμίσεων.',
            self::Admin => 'Όλο το περιεχόμενο και οι σελίδες. Μπορεί να προσκαλεί συντάκτες.',
            self::Editor => 'Άρθρα, εκδηλώσεις και gallery.',
        };
    }

    /** Roles an existing user is allowed to hand out. */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::Owner => [self::Admin, self::Editor],
            self::Admin => [self::Editor],
            self::Editor => [],
        };
    }

    /** @return array<string, string> value => label, for <select> options. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $role) => $carry + [$role->value => $role->label()],
            []
        );
    }
}
