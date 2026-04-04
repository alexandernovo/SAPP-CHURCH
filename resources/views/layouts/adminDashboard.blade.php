<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard — ' . config('app.name', 'SAPP Church'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/sappcDashboard.css') }}">
    @stack('styles')
</head>
<body class="sappc-dash" data-sappc-sidebar-collapsed="0">
    <div class="sappc-dash__backdrop" id="sappcSidebarBackdrop" aria-hidden="true"></div>

    <div class="sappc-dash__layout">
        @include('partials.adminSidebar')

        <div class="sappc-dash__main">
            <header class="sappc-topbar">
                <button type="button" class="sappc-topbar__menu" id="sappcSidebarToggle" aria-label="Toggle sidebar" aria-expanded="true" aria-controls="sappcSidebar">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <div class="sappc-topbar__spacer"></div>
                <div class="dropdown sappc-topbar__dropdown">
                    <button type="button" class="sappc-topbar__user-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-haspopup="true" id="sappcAdminMenuBtn">
                        <span class="sappc-topbar__avatar" aria-hidden="true">
                            <i class="fa-solid fa-circle-user"></i>
                        </span>
                        <span class="sappc-topbar__admin">Admin</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end sappc-topbar__dropdown-menu" aria-labelledby="sappcAdminMenuBtn">
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}" class="sappc-topbar__logout-form">
                                @csrf
                                <button type="submit" class="dropdown-item sappc-topbar__logout-item">
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
                <p class="sappc-site-footer__text mb-0">© Copyright 2026. Developed by IS-TECH UNSTOPPABLE. All Rights Reserved</p>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>
    <script>
        (function () {
            var sidebar = document.getElementById('sappcSidebar');
            var toggle = document.getElementById('sappcSidebarToggle');
            var backdrop = document.getElementById('sappcSidebarBackdrop');
            if (!sidebar || !toggle) return;

            var mqMobile = window.matchMedia('(max-width: 991.98px)');

            function isMobile() {
                return mqMobile.matches;
            }

            function setMobileOpen(open) {
                document.body.classList.toggle('sappc-dash--sidebar-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            }

            function setDesktopCollapsed(collapsed) {
                document.body.classList.toggle('sappc-dash--sidebar-collapsed', collapsed);
                document.body.setAttribute('data-sappc-sidebar-collapsed', collapsed ? '1' : '0');
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            }

            function syncForViewport() {
                if (isMobile()) {
                    document.body.classList.remove('sappc-dash--sidebar-collapsed');
                    document.body.setAttribute('data-sappc-sidebar-collapsed', '0');
                    setMobileOpen(document.body.classList.contains('sappc-dash--sidebar-open'));
                } else {
                    document.body.classList.remove('sappc-dash--sidebar-open');
                    var collapsed = document.body.classList.contains('sappc-dash--sidebar-collapsed');
                    setDesktopCollapsed(collapsed);
                }
            }

            toggle.addEventListener('click', function () {
                if (isMobile()) {
                    setMobileOpen(!document.body.classList.contains('sappc-dash--sidebar-open'));
                } else {
                    setDesktopCollapsed(!document.body.classList.contains('sappc-dash--sidebar-collapsed'));
                }
            });

            backdrop && backdrop.addEventListener('click', function () {
                setMobileOpen(false);
            });

            mqMobile.addEventListener('change', syncForViewport);
            window.addEventListener('resize', syncForViewport);
            syncForViewport();
        })();
    </script>
    @stack('scripts')
</body>
</html>
