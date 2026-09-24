@props([
    'name',
    'value' => null,
    'id' => null,
])

@php $fieldId = $id ?? $name; @endphp

{{--
    Trix editor.

    Attachments are disabled in resources/js/admin.js: by default Trix turns a
    pasted or dropped image into a blob: URL that dies the moment the page is
    left, and the sanitiser strips <img> anyway. Images belong in the dedicated
    image fields.
--}}
<input type="hidden" id="{{ $fieldId }}_input" name="{{ $name }}" value="{{ $value }}">
<trix-editor input="{{ $fieldId }}_input" class="a-trix" aria-label="Κείμενο"></trix-editor>
