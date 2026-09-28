@props([
    // The registry entry: type `collection`, with `item` describing one row.
    'field',
    // The rows as they stand.
    'items' => [],
])

@php
    $sub = $field['item'] ?? [];

    // The blank row the "add" button clones. Rendered once, hidden, with a
    // placeholder index in its names — cloning real markup beats building it
    // in JavaScript, which would have to know every field type again and
    // would quietly fall behind the day a new one is added.
    $blank = [];
    foreach ($sub as $s) {
        $blank[$s['key']] = $s['type'] === 'lines' ? [] : ($s['type'] === 'toggle' ? false : '');
    }
@endphp

<div class="a-field" x-data="collectionField()" x-ref="root">
    <label class="a-label">{{ $field['label'] }}</label>
    @isset($field['hint'])
        <span class="a-hint" style="margin-bottom:12px">{{ $field['hint'] }}</span>
    @endisset

    <div class="a-collection" x-ref="list">
        @foreach ($items as $i => $item)
            @include('admin.partials.collection-row', [
                'field' => $field,
                'sub' => $sub,
                'item' => $item,
                'index' => $i,
                'isTemplate' => false,
            ])
        @endforeach
    </div>

    <template x-ref="blank">
        @include('admin.partials.collection-row', [
            'field' => $field,
            'sub' => $sub,
            'item' => $blank,
            'index' => '__NEW__',
            'isTemplate' => true,
        ])
    </template>

    <button type="button" class="a-btn a-btn-ghost a-btn-sm" style="margin-top:12px" @click="add()">
        + {{ $field['add_label'] ?? 'Προσθήκη' }}
    </button>
</div>
