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
