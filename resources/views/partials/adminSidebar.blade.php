<aside class="sappc-sidebar" id="sappcSidebar" aria-label="Main navigation">
    <div class="sappc-sidebar__brand">
        <img src="{{ asset('assets/landingPage/SAPPC.png') }}" alt="Parish seal" class="sappc-sidebar__logo" width="96" height="96" decoding="async">
        <p class="sappc-sidebar__church">SAPP CHURCH</p>
    </div>

    <nav class="sappc-sidebar__nav">
        <a href="{{ route('admin.dashboard') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" title="Dashboard">
            <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Dashboard</span>
        </a>

        <p class="sappc-sidebar__group-label">DOCUMENT</p>
        <a href="{{ route('admin.christening') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.christening') ? 'is-active' : '' }}" title="Christening">
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Christening</span>
        </a>
        <a href="{{ route('admin.confirmation') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.confirmation') ? 'is-active' : '' }}" title="Confirmation">
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Confirmation</span>
        </a>
        <a href="{{ route('admin.wedding') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.wedding') ? 'is-active' : '' }}" title="Wedding">
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Wedding</span>
        </a>
        <a href="{{ route('admin.burial') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.burial') ? 'is-active' : '' }}" title="Burial">
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Burial</span>
        </a>

        <p class="sappc-sidebar__group-label">REPORT</p>
        <a href="{{ route('admin.document') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.document') ? 'is-active' : '' }}" title="Document report">
            <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Document</span>
        </a>
        <a href="{{ route('admin.certification') }}" class="sappc-sidebar__link {{ request()->routeIs('admin.certification') ? 'is-active' : '' }}" title="Certification">
            <i class="fa-solid fa-certificate" aria-hidden="true"></i>
            <span class="sappc-sidebar__link-text">Certification</span>
        </a>
    </nav>
</aside>
