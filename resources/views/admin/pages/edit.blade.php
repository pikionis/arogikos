@extends('admin.layouts.app')

@section('title', $page['label'])
@section('heading', $page['label'])
@section('subheading', 'Κείμενα της σελίδας. Τα πεδία ξεκινούν με ό,τι εμφανίζεται σήμερα στο site.')

@section('content')

    @if ($errors->any())
        <div class="a-alert a-alert-err">
            <strong>Ελέγξτε τα παρακάτω:</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-weight:500">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:18px">
        <a class="a-btn a-btn-ghost a-btn-sm" href="{{ route('admin.pages.index') }}">← Όλες οι σελίδες</a>

        @isset($page['route'])
            <a class="a-btn a-btn-ghost a-btn-sm" href="{{ route($page['route']) }}" target="_blank" rel="noopener">
                Προβολή σελίδας ↗
            </a>
        @endisset

        <form method="POST" action="{{ route('admin.pages.reset', $pageKey) }}" style="margin-left:auto"
              x-data="confirmable(@js('Επαναφορά όλων των κειμένων αυτής της σελίδας στις αρχικές τιμές;'))"
              @submit="confirm($event)">
            @csrf @method('DELETE')
            <button class="a-btn a-btn-ghost a-btn-sm" type="submit">Επαναφορά αρχικών</button>
        </form>
    </div>

    @isset($page['hint'])
        <div class="a-alert a-alert-warn" style="font-weight:500">{{ $page['hint'] }}</div>
    @endisset

    <form method="POST" action="{{ route('admin.pages.update', $pageKey) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div style="display:grid;gap:18px;max-width:840px">

            @foreach ($page['groups'] as $group)
                <section class="a-card">
                    <h2 style="font-size:1rem;margin:0 0 4px">{{ $group['label'] }}</h2>
                    @isset($group['hint'])
                        <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">{{ $group['hint'] }}</p>
                    @else
                        <div style="height:12px"></div>
                    @endisset

                    {{-- The SEO group gets the same live Google preview the
                         article form uses, so nobody has to guess at lengths. --}}
                    @if (collect($group['fields'])->pluck('key')->contains('seo.title'))
                        <x-admin.seo-preview
                            title-name="fields[seo.title]"
                            description-name="fields[seo.description]"
                            :url="isset($page['route']) ? route($page['route']) : url('/')" />
                    @endif

                    @foreach ($group['fields'] as $field)
                        @if ($field['type'] === 'repeater')
                            @php $items = $values[$field['key']] ?? []; @endphp

                            <div class="a-field">
                                <label class="a-label">{{ $field['label'] }}</label>
                                @isset($field['hint'])
                                    <span class="a-hint" style="margin-bottom:10px">{{ $field['hint'] }}</span>
                                @endisset

                                {{--
                                    Fixed count: the layouts are hand-built per
                                    item (colours, icons, the zig-zag rhythm),
                                    so there is no add/remove — only the text of
                                    each item is editable.
                                --}}
                                <div style="display:grid;gap:12px">
                                    @foreach ($items as $i => $item)
                                        <fieldset style="border:1px solid var(--admin-border);border-radius:9px;padding:14px;margin:0">
                                            <legend style="font-family:var(--font-head);font-weight:700;font-size:.82rem;padding:0 6px;color:var(--color-ink-400)">
                                                {{ $i + 1 }}
                                            </legend>

                                            @foreach ($field['item'] as $sub)
                                                @php
                                                    $subValue = $item[$sub['key']] ?? '';
                                                    if ($sub['type'] === 'lines' && is_array($subValue)) {
                                                        $subValue = implode("\n", $subValue);
                                                    }
                                                @endphp

                                                <x-admin.content-field
                                                    :field="$sub"
                                                    :value="$subValue"
                                                    :name="'fields['.$field['key'].']['.$i.']['.$sub['key'].']'"
                                                    :id="'f-'.Str::slug($field['key'].'-'.$i.'-'.$sub['key'], '_')" />
                                            @endforeach
                                        </fieldset>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <x-admin.content-field
                                :field="$field"
                                :value="$values[$field['key']] ?? ''"
                                :name="'fields['.$field['key'].']'" />
                        @endif
                    @endforeach
                </section>
            @endforeach

            {{-- The two pages that also own a `pages` row. --}}
            @if ($pageModel)
                <section class="a-card">
                    <h2 style="font-size:1rem;margin:0 0 4px">Κυρίως κείμενο</h2>
                    <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">
                        Το κείμενο με τη μορφοποίηση που εμφανίζεται μέσα στη σελίδα.
                        Ο τίτλος και η περιγραφή για τη Google ορίζονται παραπάνω.
                    </p>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="page-body">Κυρίως κείμενο</label>
                        <x-admin.rich-text name="page[body]" :value="$pageModel->body" id="page-body" />
                    </div>
                </section>
            @endif

            <div>
                <button class="a-btn a-btn-primary" type="submit">Αποθήκευση κειμένων</button>
            </div>
        </div>
    </form>

@endsection
