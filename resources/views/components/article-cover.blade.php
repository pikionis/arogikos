@props([
    // One of the keys in config/cms-covers.php.
    'scene',
    // Motion plays on the article's own page, not in a list of nine of them.
    'live' => false,
])

@if (view()->exists("covers.$scene"))
    <div @class(['cv', "cv-$scene", 'is-live' => $live])>
        @include("covers.$scene")
    </div>
@endif
