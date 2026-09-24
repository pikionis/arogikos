<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use App\Models\Cms\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // Honeypot tripped: pretend it worked, persist/send nothing.
        if ($request->isSpam()) {
            return redirect()->route('contact')->with('contact_sent', true);
        }

        /** @var class-string<ContactMessage> $messageModel */
        $messageModel = cms_model('contact_message');

        $message = $messageModel::create($request->validated());

        $recipient = setting('contact_form_recipient', setting('email'));

        if (filled($recipient)) {
            // A mail failure must not break the visitor's success experience.
            try {
                Mail::to($recipient)->send(new ContactMessageMail($message));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('contact')->with('contact_sent', true);
    }
}
