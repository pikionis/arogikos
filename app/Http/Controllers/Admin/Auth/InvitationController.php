<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cms\Author;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Accepting an invitation: the only way a new account is ever created.
 *
 * There is no public registration form — see the panel's Users screen for the
 * inviting half.
 */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = $this->pendingOrFail($token);

        return view('admin.auth.invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->pendingOrFail($token);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Someone may have signed up for this address between the invite and
        // the click.
        if (User::where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Υπάρχει ήδη λογαριασμός με αυτό το email.',
            ]);
        }

        $user = DB::transaction(function () use ($invitation, $validated): User {
            $user = new User;
            $user->name = $validated['name'];
            $user->email = $invitation->email;
            $user->password = $validated['password'];
            // Privilege fields are never mass-assigned; the role comes from the
            // invitation row, not from anything the visitor can post.
            $user->role = $invitation->role;
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();

            // A display profile so they can be picked as an article author.
            // INACTIVE on purpose: Author::active() drives the public About
            // page team grid, and inviting a colleague must never publish them
            // on the site by itself.
            Author::create([
                'user_id' => $user->getKey(),
                'name' => $user->name,
                'is_active' => false,
            ]);

            $invitation->forceFill(['accepted_at' => now()])->save();

            // Burn any other pending invitations for the same address.
            Invitation::query()
                ->pending()
                ->where('email', $invitation->email)
                ->whereKeyNot($invitation->getKey())
                ->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Καλώς ήρθατε! Ο λογαριασμός σας είναι έτοιμος.');
    }

    private function pendingOrFail(string $token): Invitation
    {
        $invitation = Invitation::findPendingByToken($token);

        if (! $invitation) {
            // Expired, already used, or simply wrong — all the same to the
            // visitor. The branded 404 explains nothing further.
            throw new NotFoundHttpException;
        }

        return $invitation;
    }
}
