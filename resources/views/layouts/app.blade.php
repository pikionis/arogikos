@php
    $defaultTitle = setting('org_name', 'Αρωγή').' — Κέντρο Μάθησης & Συμβουλευτικής στην Κω';
    $defaultDescription = 'Εξειδικευμένη μαθησιακή υποστήριξη, συμβουλευτική και επαγγελματικός προσανατολισμός για παιδιά και εφήβους στην Κω.';
@endphp
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('meta_title', $defaultTitle)</title>
    <meta name="description" content="@yield('meta_description', $defaultDescription)">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / social --}}
    <meta property="og:site_name" content="{{ setting('org_name', 'Αρωγή') }}">
    <meta property="og:locale" content="el_GR">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('meta_title', $defaultTitle)">
    <meta property="og:description" content="@yield('meta_description', $defaultDescription)">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    <meta name="theme-color" content="#1E9E58">

    @include('partials.analytics')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="@yield('body_page', '')">
    <x-site-header />

    <main>
        @yield('content')
    </main>

    <x-site-footer />
</body>
</html>
