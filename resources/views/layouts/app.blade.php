<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    @include('layouts.cdn')
    <link rel="stylesheet" href="{{ asset('css/headerNav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landingPage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    @stack('styles')
</head>
<body class="sapp-site @yield('body-class')">
    @yield('content')
    @stack('scripts')
</body>
</html>
