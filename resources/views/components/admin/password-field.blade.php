@props([
    'name',
    'label',
    'id' => null,
    // current-password on a login, new-password everywhere a password is set;
    // it is what tells a password manager whether to offer or to generate.
    'autocomplete' => 'new-password',
    'hint' => null,
    'autofocus' => false,
])

@php $id = $id ?: $name; @endphp

<div class="a-field" x-data="{ shown: false }">
    <label class="a-label" for="{{ $id }}">{{ $label }}</label>

    <div class="a-password">
        {{-- type is password in the markup and only then bound: if Alpine is
             slow or blocked, the field stays hidden rather than starting
             readable. --}}
        <input class="a-input" id="{{ $id }}" name="{{ $name }}" type="password"
               :type="shown ? 'text' : 'password'"
               required autocomplete="{{ $autocomplete }}"
               @if ($autofocus) autofocus @endif
               @error($name) aria-invalid="true" @enderror>

        <button type="button" class="a-password-toggle"
                @click="shown = !shown" :aria-pressed="shown"
                x-text="shown ? 'Απόκρυψη' : 'Εμφάνιση'">Εμφάνιση</button>
    </div>

    @if ($hint)
        <span class="a-hint">{{ $hint }}</span>
    @endif
</div>
