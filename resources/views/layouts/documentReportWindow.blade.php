<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Report — ' . config('app.name', 'SAPP Church'))</title>
    @include('layouts.cdn')
    <link rel="stylesheet" href="{{ asset('css/document/sappcDocumentLayout.css') }}">
    @stack('styles')
</head>
<body class="sappc-doc-report-window">
    <div class="sappc-doc-report-window_inner">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
