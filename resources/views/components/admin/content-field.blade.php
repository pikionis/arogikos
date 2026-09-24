@props([
    'field',
    'value' => null,
    'name',
    'id' => null,
])

@php
    $fieldId = $id ?? 'f-'.Str::slug($name, '_');
    $type = $field['type'];
@endphp

<div class="a-field">
    <label class="a-label" for="{{ $fieldId }}">{{ $field['label'] }}</label>

    @switch($type)

        @case('textarea')
            <textarea class="a-textarea" id="{{ $fieldId }}" name="{{ $name }}"
                      style="min-height:86px">{{ $value }}</textarea>
            @break

        @case('html')
            <x-admin.rich-text :name="$name" :value="$value" :id="$fieldId" />
            @break

        @case('lines')
            <textarea class="a-textarea" id="{{ $fieldId }}" name="{{ $name }}"
                      style="min-height:110px;font-family:var(--font-body)">{{ $value }}</textarea>
            <span class="a-hint">Ένα στοιχείο ανά γραμμή. Οι κενές γραμμές αγνοούνται.</span>
            @break

        @case('image')
            @php $imageName = 'image_'.Str::slug($field['key'], '_'); @endphp
            <div x-data="{ preview: null }">
                @if ($value)
                    <div style="border:1px solid var(--admin-border);border-radius:9px;overflow:hidden;aspect-ratio:16/9;margin-bottom:10px">
                        <img src="{{ $value }}" alt="" style="width:100%;height:100%;object-fit:cover">
                    </div>
                @endif

                <template x-if="preview">
                    <div style="border:1px solid var(--admin-border);border-radius:9px;overflow:hidden;aspect-ratio:16/9;margin-bottom:10px">
                        <img :src="preview" alt="" style="width:100%;height:100%;object-fit:cover">
                    </div>
                </template>

                <input class="a-input" id="{{ $fieldId }}" name="{{ $imageName }}" type="file"
                       accept="image/jpeg,image/png,image/webp"
                       style="padding:7px 9px;font-size:.85rem"
                       @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">

                @if ($value)
                    <label class="a-check" style="margin-top:10px">
                        <input type="checkbox" name="remove_{{ $imageName }}" value="1">
                        <span style="font-weight:600;font-size:.85rem">Αφαίρεση της εικόνας</span>
                    </label>
                @endif
            </div>
            @break

        @default
            <input class="a-input" id="{{ $fieldId }}" name="{{ $name }}" type="text"
                   value="{{ $value }}">
    @endswitch

    @isset($field['hint'])
        <span class="a-hint">{{ $field['hint'] }}</span>
    @endisset
</div>
