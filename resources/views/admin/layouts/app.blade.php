<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Διαχείριση') — {{ setting('org_name', 'Αρωγή') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
@php
    $user = auth()->user();
@endphp
<div class="a-shell">

    <aside class="a-sidebar">
        <a class="a-brand" href="{{ route('admin.dashboard') }}">
            <svg width="26" height="26" viewBox="0 0 40 40" aria-hidden="true">
                <rect x="2" y="30" width="36" height="5" rx="2.5" fill="#1E9E58"/>
                <path d="M9 32 A11 11 0 0 1 31 32" fill="none" stroke="#E26D5A" stroke-width="5" stroke-linecap="round"/>
                <circle cx="20" cy="12" r="5" fill="#F4B62A"/>
            </svg>
            {{ setting('org_name', 'Αρωγή') }}
        </a>

        <x-admin.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="home">
            Επισκόπηση
        </x-admin.nav-link>

        <span class="a-navlabel">Περιεχόμενο</span>

        <x-admin.nav-link :href="route('admin.articles.index')"
                          :active="request()->routeIs('admin.articles.*')" icon="doc">Άρθρα</x-admin.nav-link>
        <x-admin.nav-link :href="route('admin.events.index')"
                          :active="request()->routeIs('admin.events.*')" icon="calendar">Εκδηλώσεις</x-admin.nav-link>
        <x-admin.nav-link :href="route('admin.gallery.index')"
                          :active="request()->routeIs('admin.gallery.*')" icon="image">Gallery</x-admin.nav-link>
        <x-admin.nav-link :href="route('admin.authors.index')"
                          :active="request()->routeIs('admin.authors.*')" icon="users">Συντάκτες</x-admin.nav-link>

        @can('manage-pages')
            <span class="a-navlabel">Σελίδες</span>
            <x-admin.nav-link :href="route('admin.pages.index')"
                              :active="request()->routeIs('admin.pages.*')" icon="layout">Κείμενα σελίδων</x-admin.nav-link>
        @endcan

        <span class="a-navlabel">Επικοινωνία</span>
        <x-admin.nav-link :href="route('admin.messages.index')"
                          :active="request()->routeIs('admin.messages.*')" icon="mail">Μηνύματα</x-admin.nav-link>

        @canany(['manage-settings', 'manage-users'])
            <span class="a-navlabel">Διαχείριση</span>
            @can('manage-users')
                <x-admin.nav-link :href="route('admin.users.index')"
                                  :active="request()->routeIs('admin.users.*')" icon="users">Χρήστες</x-admin.nav-link>
            @endcan
            @can('manage-settings')
                <x-admin.nav-link :href="route('admin.settings.edit')"
                          :active="request()->routeIs('admin.settings.*')" icon="cog">Ρυθμίσεις</x-admin.nav-link>
            @endcan
        @endcanany
    </aside>

    <div class="a-main">
        <header class="a-topbar">
            <div>
                <h1 style="font-size:1.15rem;margin:0">@yield('heading', 'Επισκόπηση')</h1>
                @hasSection('subheading')
                    <p style="color:var(--color-ink-400);font-size:.86rem;margin:2px 0 0">@yield('subheading')</p>
                @endif
            </div>

            <div style="display:flex;align-items:center;gap:14px">
                <a href="{{ route('home') }}" target="_blank" rel="noopener"
                   class="a-btn a-btn-ghost a-btn-sm">Προβολή site ↗</a>

                {{-- Ήταν δύο γραμμές κείμενο χωρίς κανένα σημάδι ότι πατιούνται:
                     μόνο ένα tooltip, που δεν υπάρχει καν στο κινητό. Τώρα
                     είναι κουμπί με περίγραμμα, αρχικά και βελάκι. --}}
                <a class="a-profile-link" href="{{ route('admin.profile.edit') }}"
                   @if (request()->routeIs('admin.profile.*')) aria-current="page" @endif>
                    <span class="a-profile-avatar" aria-hidden="true">{{ $user->initials() }}</span>
                    <span class="a-profile-who">
                        <strong>{{ $user->name }}</strong>
                        <em>{{ $user->role->label() }}</em>
                    </span>
                    <span class="a-profile-go" aria-hidden="true">›</span>
                    <span class="a-sr">Το προφίλ μου</span>
                </a>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="a-btn a-btn-ghost a-btn-sm">Έξοδος</button>
                </form>
            </div>
        </header>

        <main class="a-content">
            @if (session('status'))
                <div class="a-alert a-alert-ok">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="a-alert a-alert-err">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
