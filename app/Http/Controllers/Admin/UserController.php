<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\PanelInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-users');

        return view('admin.users.index', [
            'users' => User::with('author')->orderByDesc('role')->orderBy('name')->get(),
            'invitations' => Invitation::with('inviter')->pending()->latest()->get(),
            'assignableRoles' => $request->user()->role->assignableRoles(),
            // Surfaced after issuing an invitation so the link can be passed on
            // by hand — production still runs MAIL_MAILER=log.
            'freshInviteUrl' => session('fresh_invite_url'),
        ]);
    }

    /** Issue an invitation. The only path to a new account. */
    public function invite(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $assignable = array_map(
            fn (Role $role) => $role->value,
            $request->user()->role->assignableRoles()
        );

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in($assignable)],
        ]);

        $email = Str::lower($validated['email']);

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Υπάρχει ήδη λογαριασμός με αυτό το email.',
            ]);
        }

        // Supersede any earlier pending invitation for this address, so only
        // the newest link works.
        Invitation::query()->pending()->where('email', $email)->update(['accepted_at' => now()]);

        [$invitation, $token] = Invitation::issue(
            $email,
            $validated['name'],
            Role::from($validated['role']),
            $request->user(),
        );

        $acceptUrl = route('admin.invitation.show', $token);

        Notification::route('mail', $email)
            ->notify(new PanelInvitation($invitation, $acceptUrl));

        return back()
            ->with('status', "Η πρόσκληση για {$email} δημιουργήθηκε.")
            ->with('fresh_invite_url', $acceptUrl);
    }

    public function revokeInvitation(Invitation $invitation): RedirectResponse
    {
        $this->authorize('manage-users');

        $invitation->forceFill(['accepted_at' => now()])->save();

        return back()->with('status', 'Η πρόσκληση ακυρώθηκε.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('change-user-role', $user);

        $assignable = array_map(
            fn (Role $role) => $role->value,
            $request->user()->role->assignableRoles()
        );

        $validated = $request->validate([
            'role' => ['required', Rule::in($assignable)],
        ]);

        $user->forceFill(['role' => Role::from($validated['role'])])->save();

        return back()->with('status', "Ο ρόλος του {$user->name} ενημερώθηκε.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update-user', $user);

        if ($request->user()->is($user)) {
            return back()->with('error', 'Δεν μπορείτε να απενεργοποιήσετε τον εαυτό σας.');
        }

        // Deactivating the last active owner would lock everyone out.
        if ($user->isOwner() && $user->is_active && ! $this->otherActiveOwnersExist($user)) {
            return back()->with('error', 'Πρέπει να παραμείνει τουλάχιστον ένας ενεργός ιδιοκτήτης.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status', $user->is_active
            ? "Ο λογαριασμός του {$user->name} ενεργοποιήθηκε."
            : "Ο λογαριασμός του {$user->name} απενεργοποιήθηκε.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete-user', $user);

        $name = $user->name;

        // The author profile survives (authors.user_id is nullOnDelete), so
        // articles keep their byline after the account is gone.
        $user->delete();

        return back()->with('status', "Ο λογαριασμός του {$name} διαγράφηκε.");
    }

    private function otherActiveOwnersExist(User $except): bool
    {
        return User::query()
            ->where('role', Role::Owner->value)
            ->where('is_active', true)
            ->whereKeyNot($except->getKey())
            ->exists();
    }
}
