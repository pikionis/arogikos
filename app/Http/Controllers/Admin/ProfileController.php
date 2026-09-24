<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * "Το προφίλ μου" — the account's own name, email and password.
 *
 * Not a nicety: production runs MAIL_MAILER=log, so the forgot-password flow
 * cannot deliver anything. Without this screen a user has no way at all to
 * change the password they were handed.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->getKey())],
        ]);

        // Assigned explicitly — `role` and `is_active` are not mass-assignable
        // and must never be reachable from a self-service form.
        $user->name = $validated['name'];
        $user->email = Str::lower($validated['email']);
        $user->save();

        return back()->with('status', 'Τα στοιχεία σας ενημερώθηκαν.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            // Proves it is really them — a borrowed unlocked session should not
            // be enough to take the account over.
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Ο τρέχων κωδικός δεν είναι σωστός.']);
        }

        $user->forceFill([
            'password' => $validated['password'],
            // Invalidates "remember me" cookies anywhere else.
            'remember_token' => Str::random(60),
        ])->save();

        // Keep this session signed in while retiring the old session id.
        $request->session()->regenerate();

        return back()->with('status', 'Ο κωδικός σας άλλαξε.');
    }
}
