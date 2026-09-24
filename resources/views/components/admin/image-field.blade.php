@props([
    'name',
    'model' => null,
    'collection' => null,
    'label' => null,
    'hint' => 'JPG, PNG ή WebP · έως 4 MB.',
])

@php
    $current = ($model && $model->exists && $collection)
        ? ($model->getFirstMediaUrl($collection, 'thumb') ?: null)
        : null;
@endphp

<div class="a-field" style="margin-bottom:0" x-data="{ preview: null }">
    @if ($label)
        <label class="a-label" for="{{ $name }}">{{ $label }}</label>
    @endif

    <div style="border:1px solid var(--admin-border);border-radius:9px;overflow:hidden;background:var(--color-soft-green);aspect-ratio:16/10;display:grid;place-items:center;margin-bottom:10px"
         x-show="preview || @js((bool) $current)">
        <template x-if="preview">
            <img :src="preview" alt="" style="width:100%;height:100%;object-fit:cover">
        </template>
        <template x-if="!preview">
            @if ($current)
                <img src="{{ $current }}" alt="" style="width:100%;height:100%;object-fit:cover">
            @endif
        </template>
    </div>

    <input class="a-input" id="{{ $name }}" name="{{ $name }}" type="file"
           accept="image/jpeg,image/png,image/webp"
           style="padding:7px 9px;font-size:.85rem"
           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">

    <span class="a-hint">{{ $hint }}</span>

    @if ($current)
        <label class="a-check" style="margin-top:10px">
            <input type="checkbox" name="remove_{{ $name }}" value="1">
            <span style="font-weight:600;font-size:.85rem">Αφαίρεση της τρέχουσας εικόνας</span>
        </label>
    @endif

    @error($name)<span class="a-error">{{ $message }}</span>@enderror
</div>
