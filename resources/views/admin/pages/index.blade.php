@extends('admin.layouts.app')

@section('title', 'Κείμενα σελίδων')
@section('heading', 'Κείμενα σελίδων')
@section('subheading', 'Δείτε και αλλάξτε τα κείμενα κάθε σελίδας του site.')

@section('content')

    <div class="a-alert a-alert-warn" style="font-weight:500">
        Κάθε πεδίο ξεκινά με το κείμενο που υπάρχει σήμερα στο site. Ό,τι αλλάξετε
        αποθηκεύεται ως δική σας έκδοση· αν το επαναφέρετε στο αρχικό, η δική σας
        έκδοση διαγράφεται αυτόματα.
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach ($pages as $key => $page)
            @php
                $count = $customised[$key] ?? 0;
                $fieldCount = collect($page['groups'] ?? [])->sum(fn ($g) => count($g['fields'] ?? []));
            @endphp

            <a href="{{ route('admin.pages.edit', $key) }}"
               class="a-card" style="text-decoration:none;display:block">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                    <h2 style="font-size:1rem;margin:0">{{ $page['label'] }}</h2>
                    @if ($count > 0)
                        <span class="a-badge a-badge-green">{{ $count }} αλλαγμένα</span>
                    @endif
                </div>

                <p style="color:var(--color-ink-400);font-size:.84rem;margin:6px 0 0">
                    {{ $fieldCount }} {{ $fieldCount === 1 ? 'πεδίο' : 'πεδία' }}
                    @isset($page['route'])
                        · <span style="color:var(--color-green-700)">{{ route($page['route'], [], false) }}</span>
                    @endisset
                </p>

                @isset($page['hint'])
                    <p style="color:var(--color-ink-600);font-size:.84rem;margin:10px 0 0">
                        {{ Str::limit($page['hint'], 110) }}
                    </p>
                @endisset
            </a>
        @endforeach
    </div>

@endsection
