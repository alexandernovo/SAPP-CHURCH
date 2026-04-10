<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — {{ config('app.name', 'SAPP Church') }}</title>
    @include('layouts.cdn')
    <link rel="stylesheet" href="{{ asset('css/sappcDashboard/sappcDashboard.css') }}">
    @stack('styles')
</head>
<body class="sappc-dash" data-sappc-sidebar-collapsed="0">
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
                <h1 class="sappc-page-title">
                    <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                    DASHBOARD
                </h1>

                <div
                    id="sappcDocStatsRoot"
                    class="row g-3 sappc-doc-stats"
                    data-monthly-url="{{ route('admin.dashboard.stats.monthly') }}"
                    data-default-year="{{ $statsYear }}"
                >
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100" data-doc-type="christening" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Christening <span class="sappc-doc-stat_pipe">|</span> Year <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['christening'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100" data-doc-type="confirmation" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Confirmation <span class="sappc-doc-stat_pipe">|</span> Year <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['confirmation'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100" data-doc-type="wedding" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Wedding <span class="sappc-doc-stat_pipe">|</span> Year <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['wedding'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100" data-doc-type="burial" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Burial <span class="sappc-doc-stat_pipe">|</span> Year <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['burial'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                </div>

                <div class="modal fade" id="sappcStatMonthlyModal" tabindex="-1" aria-labelledby="sappcStatMonthlyModalTitle" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content sappc-stat-monthly-modal">
                            <div class="modal-header border-0 pb-0 align-items-center flex-wrap gap-2">
                                <h2 class="modal-title h5 mb-0 flex-grow-1" id="sappcStatMonthlyModalTitle">Document</h2>
                                <div class="d-flex align-items-center gap-2 sappc-stat-monthly-toolbar">
                                    <i class="fa-solid fa-calendar-days text-secondary" aria-hidden="true"></i>
                                    <label class="visually-hidden" for="sappcStatMonthlyYear">Year</label>
                                    <select id="sappcStatMonthlyYear" class="form-select form-select-sm sappc-stat-monthly-year-select" aria-label="Filter by year">
                                        @foreach ($statsYearOptions as $y)
                                            <option value="{{ $y }}" @selected($y === $statsYear)>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <p class="small text-muted mb-3" id="sappcStatMonthlySubtitle">Total for <span id="sappcStatMonthlyYearLabel">{{ $statsYear }}</span>: <strong id="sappcStatMonthlyTotal">0</strong></p>
                                <div class="sappc-stat-monthly-grid" id="sappcStatMonthlyGrid" role="list" aria-busy="false"></div>
                                <p class="small text-danger mb-0 d-none" id="sappcStatMonthlyError" role="alert"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <section
                    class="sappc-table-panel"
                    id="sappcRecordsPanel"
                    data-records-url="{{ route('admin.dashboard.records') }}"
                    data-per-page-options="{{ json_encode($perPageOptions) }}"
                >
                    <div class="sappc-table-toolbar">
                        <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
                            <div class="sappc-table-toolbar_entries">
                                <label class="visually-hidden" for="sappcEntries">Entries per page</label>
                                <select id="sappcEntries" class="form-select form-select-sm sappc-table-toolbar_select" aria-label="Entries per page">
                                    @foreach ($perPageOptions as $n)
                                        <option value="{{ $n }}" @selected($records->perPage() === $n)>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                                <span class="sappc-toolbar-date-strip_label">From:</span>
                                <input type="date" id="sappcDateFrom" class="sappc-toolbar-date-strip_input" name="date_from" value="{{ request('date_from') }}" aria-label="From date">
                                <span class="sappc-toolbar-date-strip_label">To:</span>
                                <input type="date" id="sappcDateTo" class="sappc-toolbar-date-strip_input" name="date_to" value="{{ request('date_to') }}" aria-label="To date">
                                <button type="button" class="sappc-toolbar-date-strip_btn">Filter</button>
                            </div>
                            <div class="sappc-table-toolbar_letters" role="group" aria-label="Filter by first letter of client last name">
                                <span class="visually-hidden">Filter by first letter of last name A through Z; scroll horizontally to see all letters.</span>
                                <div class="sappc-letter-filter_letters">
                                    @foreach ($letterOptions as $letter)
                                        <button type="button" class="sappc-letter-filter_btn {{ request('letter') === $letter ? 'is-active' : '' }}" data-letter="{{ $letter }}">{{ $letter }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sappc-table-toolbar_search" role="search">
                                <label class="sappc-table-toolbar_search-heading" for="sappcSearch">Search:</label>
                                <div class="sappc-table-toolbar_search-wrap">
                                    <input type="search" id="sappcSearch" class="form-control form-control-sm sappc-table-toolbar_search-input" value="{{ request('search') }}" placeholder="" autocomplete="off" aria-label="Search registry" aria-controls="sappcTableBody">
                                    <i class="fa-solid fa-magnifying-glass sappc-table-toolbar_search-icon" aria-hidden="true"></i>
                                </div>
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
                            <tbody id="sappcTableBody" aria-live="polite" aria-relevant="additions text"></tbody>
                        </table>
                    </div>

                    <div class="sappc-table-footer">
                        <p class="sappc-table-footer_info mb-0" id="sappcTableFooterInfo"></p>
                        <nav class="sappc-pagination" id="sappcPagination" aria-label="Table pagination"></nav>
                    </div>
                </section>

                <section class="sappc-chart-section">
                    <h2 class="sappc-chart-section_title">STATISTIC DATA CHART</h2>
                    <div class="sappc-chart-card">
                        <div class="sappc-chart-card_head">
                            <div class="sappc-chart-card_head-lead" aria-hidden="true"></div>
                            <h3 class="sappc-chart-card_subtitle">Number of Document Request</h3>
                            <div class="sappc-chart-card_filters">
                                <select class="form-select form-select-sm" aria-label="Category">
                                    <option>Category</option>
                                    <option>Christening</option>
                                    <option>Confirmation</option>
                                    <option>Wedding</option>
                                    <option>Burial</option>
                                </select>
                                <select class="form-select form-select-sm" aria-label="Months">
                                    <option>Months</option>
                                    @foreach ($chartMonthLabels as $m)
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
                        <div class="sappc-chart-card_canvas">
                            <canvas id="sappcDocChart" height="280" aria-label="Bar chart of document requests by month"></canvas>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="sappc-site-footer">
                <p class="sappc-site-footer_text mb-0">© Copyright 2026. Developed by IS-TECH UNSTOPPABLE. All Rights Reserved</p>
            </footer>
        </div>
    </div>

    <script src="{{ asset('js/adminSidebar.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/sappcDashboardDataTable.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;
            var el = document.getElementById('sappcDocChart');
            if (!el) return;
            var months = @json($chartMonthLabels);
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
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } },
                        x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } } }
                    }
                }
            });
        });
    </script>
    @include('dashboard.js.sappcDashboardscript', ['initialTablePayload' => $initialTablePayload])
</body>
</html>
