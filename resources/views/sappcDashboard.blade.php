<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — {{ config('app.name', 'SAPP Church') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/sappcDashboard.css') }}">
</head>
<body class="sappc-dash" data-sappc-sidebar-collapsed="0">
    <div class="sappc-dash__backdrop" id="sappcSidebarBackdrop" aria-hidden="true"></div>

    <div class="sappc-dash__layout">
        <aside class="sappc-sidebar" id="sappcSidebar" aria-label="Main navigation">
            <div class="sappc-sidebar__brand">
                <img src="{{ asset('assets/landingPage/SAPPC.png') }}" alt="Parish seal" class="sappc-sidebar__logo" width="96" height="96" decoding="async">
                <p class="sappc-sidebar__church">SAPP CHURCH</p>
            </div>

            <nav class="sappc-sidebar__nav">
                <a href="{{ route('admin.dashboard') }}" class="sappc-sidebar__link is-active" title="Dashboard">
                    <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Dashboard</span>
                </a>

                <p class="sappc-sidebar__group-label">DOCUMENT</p>
                <a href="#" class="sappc-sidebar__link" title="Christening">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Christening</span>
                </a>
                <a href="#" class="sappc-sidebar__link" title="Confirmation">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Confirmation</span>
                </a>
                <a href="#" class="sappc-sidebar__link" title="Wedding">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Wedding</span>
                </a>
                <a href="#" class="sappc-sidebar__link" title="Burial">
                    <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Burial</span>
                </a>

                <p class="sappc-sidebar__group-label">REPORT</p>
                <a href="#" class="sappc-sidebar__link" title="Document report">
                    <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Document</span>
                </a>
                <a href="#" class="sappc-sidebar__link" title="Certification">
                    <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                    <span class="sappc-sidebar__link-text">Certification</span>
                </a>
            </nav>
        </aside>

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
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                    DASHBOARD
                </h1>

                <div class="row g-3 sappc-doc-stats">
                    <div class="col-sm-6 col-xl-3">
                        <div class="sappc-doc-stat">
                            <div class="sappc-doc-stat__icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                            <p class="sappc-doc-stat__label">Christening <span class="sappc-doc-stat__pipe">|</span> Year</p>
                            <p class="sappc-doc-stat__value">232</p>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="sappc-doc-stat">
                            <div class="sappc-doc-stat__icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                            <p class="sappc-doc-stat__label">Confirmation <span class="sappc-doc-stat__pipe">|</span> Year</p>
                            <p class="sappc-doc-stat__value">20</p>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="sappc-doc-stat">
                            <div class="sappc-doc-stat__icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                            <p class="sappc-doc-stat__label">Wedding <span class="sappc-doc-stat__pipe">|</span> Year</p>
                            <p class="sappc-doc-stat__value">18</p>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="sappc-doc-stat">
                            <div class="sappc-doc-stat__icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                            <p class="sappc-doc-stat__label">Burial <span class="sappc-doc-stat__pipe">|</span> Year</p>
                            <p class="sappc-doc-stat__value">50</p>
                        </div>
                    </div>
                </div>

                <section class="sappc-table-panel">
                    <div class="sappc-table-toolbar">
                        <div class="sappc-table-toolbar__row sappc-table-toolbar__row--primary">
                            <div class="sappc-table-toolbar__entries">
                                <label class="sappc-table-toolbar__label" for="sappcEntries">Show</label>
                                <select id="sappcEntries" class="form-select form-select-sm sappc-table-toolbar__select" aria-label="Entries per page">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="sappc-table-toolbar__label">entries</span>
                            </div>
                            <div class="sappc-table-toolbar__dates">
                                <span class="sappc-table-toolbar__label">From:</span>
                                <input type="date" class="form-control form-control-sm sappc-table-toolbar__date" name="date_from" aria-label="From date">
                                <span class="sappc-table-toolbar__label">To:</span>
                                <input type="date" class="form-control form-control-sm sappc-table-toolbar__date" name="date_to" aria-label="To date">
                                <button type="button" class="btn btn-sm sappc-btn-filter">Filter</button>
                            </div>
                            <div class="sappc-table-toolbar__search">
                                <label class="sappc-table-toolbar__label" for="sappcSearch">Search:</label>
                                <div class="sappc-table-toolbar__search-wrap">
                                    <i class="fa-solid fa-magnifying-glass sappc-table-toolbar__search-icon" aria-hidden="true"></i>
                                    <input type="search" id="sappcSearch" class="form-control form-control-sm" placeholder="" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="sappc-letter-filter" role="group" aria-label="Filter by first letter of client name">
                            <span class="sappc-letter-filter__hint visually-hidden">Filter by letter</span>
                            <div class="sappc-letter-filter__letters">
                                @foreach (range('A', 'L') as $letter)
                                    <button type="button" class="sappc-letter-filter__btn {{ $letter === 'B' ? 'is-active' : '' }}" data-letter="{{ $letter }}">{{ $letter }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 sappc-data-table">
                            <thead>
                                <tr>
                                    <th scope="col">NO.</th>
                                    <th scope="col">REFERENCE CODE</th>
                                    <th scope="col">CLIENT</th>
                                    <th scope="col">ADDRESS</th>
                                    <th scope="col">SEX</th>
                                    <th scope="col">CONTACT NUMBER</th>
                                    <th scope="col">TYPE OF DOCUMENT</th>
                                    <th scope="col">DATE CREATED</th>
                                    <th scope="col" class="text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>2026-EJDHIEQ-T</td>
                                    <td>Rex P. Bernesto</td>
                                    <td>Gua, Barbaza, Antique</td>
                                    <td>Male</td>
                                    <td>09679050621</td>
                                    <td>Christening</td>
                                    <td>February 22, 2026</td>
                                    <td class="text-center text-nowrap">
                                        <button type="button" class="btn btn-link btn-sm sappc-action-edit p-0 me-2" title="Edit" aria-label="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-link btn-sm sappc-action-delete p-0" title="Delete" aria-label="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td class="text-center">—</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td class="text-center">—</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td class="text-center">—</td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td>—</td>
                                    <td class="text-center">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="sappc-table-footer">
                        <p class="sappc-table-footer__info mb-0">Showing 1 to 5 of 5 entries</p>
                        <nav class="sappc-pagination" aria-label="Table pagination">
                            <button type="button" class="sappc-pagination__btn" disabled aria-label="Previous">&lt;</button>
                            <button type="button" class="sappc-pagination__btn is-active" aria-current="page">1</button>
                            <button type="button" class="sappc-pagination__btn" disabled aria-label="Next">&gt;</button>
                        </nav>
                    </div>
                </section>

                <section class="sappc-chart-section">
                    <h2 class="sappc-chart-section__title">STATISTIC DATA CHART</h2>
                    <div class="sappc-chart-card">
                        <div class="sappc-chart-card__head">
                            <div class="sappc-chart-card__head-lead" aria-hidden="true"></div>
                            <h3 class="sappc-chart-card__subtitle">Number of Document Request</h3>
                            <div class="sappc-chart-card__filters">
                                <select class="form-select form-select-sm" aria-label="Category">
                                    <option>Category</option>
                                    <option>Christening</option>
                                    <option>Confirmation</option>
                                    <option>Wedding</option>
                                    <option>Burial</option>
                                </select>
                                <select class="form-select form-select-sm" aria-label="Months">
                                    <option>Months</option>
                                    @foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-sm" aria-label="Year">
                                    <option>Year</option>
                                    <option>2026</option>
                                    <option>2025</option>
                                </select>
                            </div>
                        </div>
                        <div class="sappc-chart-card__canvas">
                            <canvas id="sappcDocChart" height="280" aria-label="Bar chart of document requests by month"></canvas>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="sappc-site-footer">
                <p class="sappc-site-footer__text mb-0">© Copyright 2026. Developed by IS-TECH UNSTOPPABLE. All Rights Reserved</p>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous" defer></script>
    <script>
        (function () {
            var sidebar = document.getElementById('sappcSidebar');
            var toggle = document.getElementById('sappcSidebarToggle');
            var backdrop = document.getElementById('sappcSidebarBackdrop');
            if (sidebar && toggle) {
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
            }

            document.querySelectorAll('.sappc-letter-filter__btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.sappc-letter-filter__btn').forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                });
            });

            function initChart() {
                if (typeof Chart === 'undefined') return;
                var el = document.getElementById('sappcDocChart');
                if (!el) return;
                var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                new Chart(el, {
                    type: 'bar',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Requests',
                            data: [1, 2, 0, 3, 2, 4, 1, 2, 3, 2, 1, 2],
                            backgroundColor: '#4a4a4a',
                            borderColor: '#3a3a3a',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                ticks: { stepSize: 1 }
                            },
                            x: {
                                ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } }
                            }
                        }
                    }
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initChart);
            } else {
                initChart();
            }
        })();
    </script>
</body>
</html>
