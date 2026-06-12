<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard — ' . config('app.name', 'SAPP Church'))</title>
    @include('layouts.cdn')
    <link rel="stylesheet" href="{{ asset('css/sappcDashboard/sappcDashboard.css') }}">
    @stack('styles')
</head>
<body
    class="sappc-dash{{ request()->boolean('embed') ? ' sappc-dash--registry-embed' : '' }}"
    data-sappc-sidebar-collapsed="0">
    <div class="sappc-dash_backdrop" id="sappcSidebarBackdrop" aria-hidden="true"></div>

    <div class="sappc-dash_layout">
        @include('partials.adminSidebar')

        <div class="sappc-dash_main">
            <header class="sappc-topbar">
                <button type="button" class="sappc-topbar_menu" id="sappcSidebarToggle" aria-label="Toggle sidebar" aria-expanded="true" aria-controls="sappcSidebar">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <div class="sappc-topbar_spacer"></div>
                <div class="dropdown sappc-topbar_dropdown">
                    <button type="button" class="sappc-topbar_user-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-haspopup="true" id="sappcAdminMenuBtn">
                        <span class="sappc-topbar_avatar" aria-hidden="true">
                            <i class="fa-solid fa-circle-user"></i>
                        </span>
                        <span class="sappc-topbar_admin">Admin</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end sappc-topbar_dropdown-menu" aria-labelledby="sappcAdminMenuBtn">
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}" class="sappc-topbar_logout-form">
                                @csrf
                                <button type="submit" class="dropdown-item sappc-topbar_logout-item">
                                    <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>
                                    Log out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="sappc-content">
                @yield('content')
            </main>

            <footer class="sappc-site-footer">
                <p class="sappc-site-footer_text mb-0">© Copyright 2026. Developed by IS-TECH UNSTOPPABLE. All Rights Reserved</p>
            </footer>
        </div>
    </div>

    <script src="{{ asset('js/adminSidebar.js') }}"></script>
    @include('partials.registry.clientNameFormatScript')
    @stack('scripts')
</body>
</html>
