@props(['data' => null])

@if (filled($data))
    {{--
        JSON_HEX_TAG matters: without it a "</script>" inside any setting or
        article excerpt would close this block early and the rest of the page
        would be parsed as markup.
    --}}
    <script type="application/ld+json">{!! json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR
    ) !!}</script>
@endif
