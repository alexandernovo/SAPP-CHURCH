@extends('layouts.app')

@section('title', 'Developers — ' . config('app.name', 'SAPP Church'))

@section('body-class', 'bg-light')

@section('content')
    @include('layouts.landingPageHeader')

    <main class="container py-5">
        <h1 class="h3">Developers</h1>
        <p class="text-muted">Developer resources and documentation will go here.</p>
    </main>

    @include('layouts.landingPageFooter')
@endsection
