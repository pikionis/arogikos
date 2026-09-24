<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Cms\ContentBlock;
use App\Models\User;
use App\Support\Content;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerGates();
        $this->registerMediaCacheBusting();

        // The broker's default link points at a route named "password.reset";
        // ours lives under the admin prefix.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => route(
            'admin.password.reset',
            ['token' => $token, 'email' => $user->getEmailForPasswordReset()]
        ));

        ResetPassword::toMailUsing(fn (User $user, string $url) => (new MailMessage)
            ->replyTo(setting('email', config('mail.from.address')))
            ->subject('Επαναφορά κωδικού — '.setting('org_name', 'Αρωγή'))
            ->greeting('Γεια σας '.$user->name.',')
            ->line('Λάβαμε αίτημα επαναφοράς του κωδικού σας για τη διαχείριση του site.')
            ->action('Ορισμός νέου κωδικού', $url)
            ->line('Ο σύνδεσμος λήγει σε '.config('auth.passwords.users.expire').' λεπτά.')
            ->line('Αν δεν ζητήσατε εσείς την επαναφορά, αγνοήστε αυτό το email.')
            ->salutation('— '.setting('org_name', 'Αρωγή')));
    }

    /**
     * Throttles for the unauthenticated admin surfaces.
     *
     * Laravel 11+ has no RouteServiceProvider, so limiters are declared here.
     * Each one pairs a per-identity limit (stops credential stuffing against a
     * single account) with a looser per-IP limit (stops a spray across many).
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('admin-login', fn ($request) => [
            Limit::perMinute(5)->by($this->emailKey($request)),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('admin-password', fn ($request) => [
            Limit::perMinute(3)->by($this->emailKey($request)),
            Limit::perMinute(10)->by($request->ip()),
        ]);

        // Guessing a 64-char token is hopeless, but an unthrottled endpoint is
        // still free CPU for an attacker.
        RateLimiter::for('admin-invitation', fn ($request) => [
            Limit::perMinute(10)->by($request->ip()),
        ]);
    }

    private function emailKey($request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    /**
     * Panel abilities.
     *
     * Gate::before short-circuits everything for the owner, so new abilities
     * never need an explicit owner branch.
     */
    private function registerGates(): void
    {
        // The owner passes everything EXCEPT the account-targeting abilities.
        // Those carry the lockout guards (no self-deletion, no demoting the
        // last owner) and a blanket `true` here would sail straight past them.
        $accountAbilities = ['update-user', 'delete-user', 'change-user-role'];

        Gate::before(function (User $user, string $ability) use ($accountAbilities) {
            if (in_array($ability, $accountAbilities, true)) {
                return null;
            }

            return $user->isOwner() && $user->is_active ? true : null;
        });

        // Content anyone with panel access may edit.
        Gate::define('manage-content', fn (User $user) => $user->is_active);

        // Page copy and site settings — structural, admins and up.
        Gate::define('manage-pages', fn (User $user) => $user->is_active && $user->isAdmin());
        Gate::define('manage-settings', fn (User $user) => $user->is_active && $user->isAdmin());

        // Accounts. Admins may invite and manage editors; owners, everyone.
        Gate::define('manage-users', fn (User $user) => $user->is_active && $user->isAdmin());

        Gate::define('update-user', function (User $user, User $target) {
            if (! $user->is_active || ! $user->isAdmin()) {
                return false;
            }

            // Everyone may edit their own name/password.
            if ($user->is($target)) {
                return true;
            }

            if ($user->isOwner()) {
                // An owner may edit anyone but a fellow owner.
                return $target->role !== Role::Owner;
            }

            // An admin may only edit editors.
            return $target->role === Role::Editor;
        });

        /**
         * Changing someone's role — the one that can lock everybody out.
         */
        Gate::define('change-user-role', function (User $user, User $target) {
            if (! $user->is_active || ! $user->isAdmin()) {
                return false;
            }

            // Nobody changes their own role, owner included. Otherwise the
            // sole owner can demote themselves and no route exists to undo it.
            if ($user->is($target)) {
                return false;
            }

            if ($target->role === Role::Owner) {
                // Demoting an owner is only possible if another active one
                // remains.
                return $user->isOwner() && $this->otherActiveOwnersExist($target);
            }

            return $user->isOwner() || $target->role === Role::Editor;
        });

        Gate::define('delete-user', function (User $user, User $target) {
            if (! $user->is_active || ! $user->isAdmin()) {
                return false;
            }

            // You cannot delete yourself — that is what locks people out.
            if ($user->is($target)) {
                return false;
            }

            if ($target->role === Role::Owner) {
                return $user->isOwner() && $this->otherActiveOwnersExist($target);
            }

            return $user->isOwner() || $target->role === Role::Editor;
        });
    }

    /**
     * Replacing a content block's image writes to the `media` table, not to
     * content_blocks — so ContentBlock::saved never fires and the forever
     * cache would keep serving the old URL. Watch Media itself.
     */
    private function registerMediaCacheBusting(): void
    {
        $bust = function (Media $media): void {
            if ($media->model_type === ContentBlock::class) {
                Content::flush();
            }
        };

        Media::saved($bust);
        Media::deleted($bust);
    }

    /** Is there an active owner other than $except? */
    private function otherActiveOwnersExist(User $except): bool
    {
        return User::query()
            ->where('role', Role::Owner->value)
            ->where('is_active', true)
            ->whereKeyNot($except->getKey())
            ->exists();
    }
}
