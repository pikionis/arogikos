<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Creates (or promotes) the panel owner — the one account that cannot be made
 * through the invitation flow, because there is nobody to send it.
 *
 * Run once per environment over SSH.
 */
class MakeOwner extends Command
{
    protected $signature = 'cms:make-owner
                            {email? : The owner email address}
                            {--name= : Display name}
                            {--password= : Set a password directly instead of emailing a reset link}
                            {--send-link : Email a password-setup link instead of prompting}';

    protected $description = 'Δημιουργεί ή προάγει τον ιδιοκτήτη του admin panel';

    public function handle(): int
    {
        $email = Str::lower(
            (string) ($this->argument('email') ?? $this->ask('Email ιδιοκτήτη'))
        );

        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:160'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info("Βρέθηκε λογαριασμός: {$user->name} <{$user->email}>");
        }

        $name = (string) ($this->option('name')
            ?? $user?->name
            ?? $this->ask('Όνομα', 'Διαχειριστής'));

        $password = $this->resolvePassword();

        if ($password === false) {
            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($user, $email, $name, $password): User {
            $user ??= new User;
            $user->name = $name;
            $user->email = $email;
            $user->role = Role::Owner;
            $user->is_active = true;
            $user->email_verified_at ??= now();

            if (is_string($password)) {
                $user->password = $password;
            } elseif (! $user->exists) {
                // No password chosen and the account is new — park an
                // unusable hash so the row is valid; the reset link sets it.
                $user->password = Str::random(64);
            }

            $user->save();

            Author::firstOrCreate(
                ['user_id' => $user->getKey()],
                ['name' => $user->name, 'is_active' => false],
            );

            return $user;
        });

        $this->info("Ο ιδιοκτήτης ορίστηκε: {$user->email}");

        if (! is_string($password)) {
            $status = Password::sendResetLink(['email' => $user->email]);
            $this->line($status === Password::RESET_LINK_SENT
                ? 'Στάλθηκε σύνδεσμος ορισμού κωδικού.'
                : "Η αποστολή συνδέσμου απέτυχε: {$status}");

            if (config('mail.default') === 'log') {
                $this->warn('MAIL_MAILER=log — ο σύνδεσμος γράφτηκε στο storage/logs, δεν στάλθηκε email.');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return string|false|null password string, false on validation failure,
     *                           null to email a reset link instead
     */
    private function resolvePassword(): string|false|null
    {
        if ($this->option('send-link')) {
            return null;
        }

        $password = $this->option('password')
            ?: ($this->input->isInteractive()
                ? $this->secret('Κωδικός (κενό για αποστολή συνδέσμου)')
                : null);

        if (! $password) {
            return null;
        }

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', PasswordRule::default()],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return false;
        }

        return $password;
    }
}
