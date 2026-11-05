@props([
    // The footer's assembling mark.
    'animated' => false,
])

@if ($animated)
    <svg {{ $attributes->merge(['class' => 'logo-mark']) }} viewBox="0 0 100 100" width="44" height="44" aria-hidden="true">
        <circle class="fa-circle" cx="50" cy="27" r="11.5" fill="none" stroke="#F4B62A" stroke-width="5.5"/>
        <rect class="fa-line" x="29" y="47" width="42" height="5.5" rx="2.75" fill="#1E9E58"/>
        <path class="fa-arch" d="M31 76 A19 19 0 0 1 69 76" fill="none" stroke="#E26D5A" stroke-width="5.5" stroke-linecap="round"/>
    </svg>
@else
    <svg {{ $attributes->merge(['class' => 'logo-mark']) }} viewBox="0 0 100 100" aria-hidden="true">
        <circle cx="50" cy="27" r="11.5" fill="none" stroke="#F4B62A" stroke-width="5.5"/>
        <rect x="29" y="47" width="42" height="5.5" rx="2.75" fill="#1E9E58"/>
        <path d="M31 76 A19 19 0 0 1 69 76" fill="none" stroke="#E26D5A" stroke-width="5.5" stroke-linecap="round"/>
    </svg>
@endif
