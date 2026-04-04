<nav class="navbar navbar-expand-lg navbar-dark sapp-header-nav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3 py-0" href="{{ url('/') }}">
            <img
                src="{{ asset('assets/landingPage/SAPPC.png') }}"
                alt="SAPP Church"
                class="sapp-header-logo rounded-circle"
            >
            <span>SAPP Church</span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sappNavbar"
            aria-controls="sappNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="sappNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a
                        class="nav-link @if(request()->routeIs('landingPage')) active @endif"
                        href="{{ route('landingPage') }}"
                        @if(request()->routeIs('landingPage')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-house" aria-hidden="true"></i>
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a
                        class="nav-link @if(request()->routeIs('developers')) active @endif"
                        href="{{ route('developers') }}"
                        @if(request()->routeIs('developers')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-laptop-code" aria-hidden="true"></i>
                        Developers
                    </a>
                </li>
                <li class="nav-item">
                    <a
                        class="nav-link @if(request()->routeIs('admin.login')) active @endif"
                        href="{{ route('admin.login') }}"
                        @if(request()->routeIs('admin.login')) aria-current="page" @endif
                    >
                        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                        Admin
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
