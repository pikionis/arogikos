@extends('admin.layouts.app')

@section('title', 'Gallery')
@section('heading', 'Gallery')
@section('subheading', 'Άλμπουμ φωτογραφιών του site.')

@section('content')

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px">
        <a class="a-btn a-btn-primary" href="{{ route('admin.gallery.create') }}">+ Νέο άλμπουμ</a>
    </div>

    @if ($albums->isEmpty())
        <section class="a-card">
            <p style="text-align:center;color:var(--color-ink-400);margin:0;padding:20px">
                Δεν υπάρχουν άλμπουμ ακόμα.
            </p>
        </section>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
            @foreach ($albums as $album)
                <section class="a-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div>
                            <h2 style="font-size:1rem;margin:0 0 3px">{{ $album->title }}</h2>
                            <p style="color:var(--color-ink-400);font-size:.82rem;margin:0">
                                {{ $album->photos_count }}
                                {{ $album->photos_count === 1 ? 'φωτογραφία' : 'φωτογραφίες' }}
                            </p>
                        </div>
                        <span class="a-badge a-badge-grey">#{{ $album->sort_order }}</span>
                    </div>

                    @if ($album->description)
                        <p style="color:var(--color-ink-600);font-size:.86rem;margin:10px 0 0">
                            {{ Str::limit($album->description, 90) }}
                        </p>
                    @endif

                    <div style="display:flex;gap:8px;margin-top:14px">
                        <a class="a-btn a-btn-ghost a-btn-sm" style="flex:1"
                           href="{{ route('admin.gallery.edit', $album) }}">Επεξεργασία</a>

                        <form method="POST" action="{{ route('admin.gallery.destroy', $album) }}"
                              x-data="confirmable(@js($album->photos_count > 0
                                  ? "Το άλμπουμ «{$album->title}» περιέχει {$album->photos_count} φωτογραφίες που θα διαγραφούν κι αυτές. Συνέχεια;"
                                  : "Διαγραφή του άλμπουμ «{$album->title}»;"))"
                              @submit="confirm($event)">
                            @csrf @method('DELETE')
                            <button class="a-btn a-btn-danger a-btn-sm" type="submit">Διαγραφή</button>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>
    @endif

@endsection
