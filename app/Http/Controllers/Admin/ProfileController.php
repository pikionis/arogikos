<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaUpload;
use App\Support\PhotoCrop;
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
        $user = Auth::user();

        return view('admin.profile.edit', [
            'user' => $user,
            // The public half of the same person: photo, role and bio, as the
            // blog byline and the team page show them.
            'author' => $user->author,
        ]);
    }

    /**
     * The public profile — photo, role title, bio.
     *
     * Their own and only their own: a colleague's profile is theirs to write,
     * and until now an editor could open anyone's from Συντάκτες.
     *
     * `is_active` is not here on purpose. Whether someone appears on the
     * public team page is the centre's call, not the colleague's, and a
     * self-service form must never be the way onto the website.
     */
    public function updateAuthor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $author = $user->author;

        if (! $author) {
            return back()->with('status', 'Δεν υπάρχει προφίλ συντάκτη για αυτόν τον λογαριασμό.');
        }

        $validated = $request->validate([
            'role_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => [
                'nullable', 'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                'dimensions:max_width=5000,max_height=5000',
            ],
            'remove_photo' => ['boolean'],
            // The chosen square; PhotoCrop checks it against the file.
            'crop_x' => ['nullable', 'integer', 'min:0'],
            'crop_y' => ['nullable', 'integer', 'min:0'],
            'crop_w' => ['nullable', 'integer', 'min:0'],
            'crop_h' => ['nullable', 'integer', 'min:0'],
        ]);

        // The display name follows the account, so there is one name for this
        // person rather than two that can disagree.
        $author->name = $user->name;
        $author->role_title = $validated['role_title'] ?? null;
        $author->bio = $validated['bio'] ?? null;
        $author->save();

        MediaUpload::sync($author, $request, 'photo');
        PhotoCrop::sync($author, $request);

        return back()->with('status', 'Το προφίλ σας ενημερώθηκε.');
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

        // One person, one name: the byline on their articles follows.
        $user->author?->forceFill(['name' => $user->name])->save();

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
