@props([
    'index' => 0,
    'size' => null,
])

{{--
    The animated icon for one of the three services, picked positionally.

    These stay in code on purpose: the class names (.ico-climb-dot,
    .ico-hug-arch, .ico-branch-up…) are what resources/js/app.js toggles to
    .play on scroll, and the brief protects the animations. Only the text of
    each service is editable — see shared.services in config/cms-content.php.
--}}
@php $style = $size ? "width:{$size};height:{$size}" : null; @endphp

<svg class="svc-icon" @if ($style) style="{{ $style }}" @endif viewBox="0 0 84 84" aria-hidden="true">
    @switch($index)
        @case(0)
            <rect x="8" y="56" width="20" height="20" rx="4" fill="#1E9E58"/>
            <rect x="30" y="44" width="20" height="32" rx="4" fill="#1E9E58"/>
            <rect x="52" y="32" width="20" height="44" rx="4" fill="#1E9E58"/>
            <circle class="ico-climb-dot" cx="18" cy="48" r="9" fill="#F4B62A"/>
            @break

        @case(1)
            <circle class="ico-hug-dot" cx="42" cy="50" r="12" fill="#F4B62A"/>
            <path class="ico-hug-arch" d="M16 62 A26 26 0 0 1 68 62" fill="none" stroke="#E26D5A" stroke-width="9" stroke-linecap="round"/>
            @break

        @default
            <path d="M12 50 H40" fill="none" stroke="#1E9E58" stroke-width="6" stroke-linecap="round"/>
            <path class="ico-branch-up" d="M40 50 C54 50 56 30 74 26" fill="none" stroke="#1E9E58" stroke-width="6" stroke-linecap="round"/>
            <path class="ico-branch-down" d="M40 50 C54 50 56 66 74 70" fill="none" stroke="#1E9E58" stroke-width="6" stroke-linecap="round"/>
            <circle class="ico-branch-dot" cx="12" cy="50" r="9" fill="#E26D5A"/>
    @endswitch
</svg>
