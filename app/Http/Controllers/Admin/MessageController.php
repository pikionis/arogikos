<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-content');

        /** @var class-string<ContactMessage> $model */
        $model = cms_model('contact_message');

        $filter = $request->query('filter');

        return view('admin.messages.index', [
            'messages' => $model::query()
                ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'unreadCount' => $model::whereNull('read_at')->count(),
            'filter' => $filter,
        ]);
    }

    public function show(ContactMessage $message): View
    {
        $this->authorize('manage-content');

        if (! $message->read_at) {
            $message->forceFill(['read_at' => now()])->save();
        }

        return view('admin.messages.show', compact('message'));
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $this->authorize('manage-content');

        $message->forceFill(['read_at' => $message->read_at ? null : now()])->save();

        return back()->with('status', $message->read_at
            ? 'Σημειώθηκε ως διαβασμένο.'
            : 'Σημειώθηκε ως αδιάβαστο.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $this->authorize('manage-content');

        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Το μήνυμα διαγράφηκε.');
    }
}
