<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="sappc-dash-html sappc-dash-html--dashboard">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard — {{ config('app.name', 'SAPP Church') }}</title>
    @include('layouts.cdn')
    <link rel="stylesheet" href="{{ asset('css/sappcDashboard/sappcDashboard.css') }}?v={{ filemtime(public_path('css/sappcDashboard/sappcDashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/confirmation/confirmationKompirmaModals.css') }}">
    <link rel="stylesheet" href="{{ asset('css/wedding/marriageApplicationKasal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/burial/burialApplication.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/app-typography.css') }}?v={{ filemtime(public_path('css/app-typography.css')) }}">
</head>

<body class="sappc-dash sappc-dash--dashboard" data-sappc-sidebar-collapsed="0">
    <div class="sappc-dash_backdrop" id="sappcSidebarBackdrop" aria-hidden="true"></div>

    <div class="sappc-dash_layout">
        @include('partials.adminSidebar')

        <div class="sappc-dash_main">
            <header class="sappc-topbar">
                <button type="button" class="sappc-topbar_menu" id="sappcSidebarToggle" aria-label="Toggle sidebar"
                    aria-expanded="true" aria-controls="sappcSidebar">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <div class="sappc-topbar_spacer"></div>
                <div class="dropdown sappc-topbar_dropdown">
                    <button type="button" class="sappc-topbar_user-btn dropdown-toggle" data-bs-toggle="dropdown"
                        data-bs-display="static" aria-expanded="false" aria-haspopup="true" id="sappcAdminMenuBtn">
                        <span class="sappc-topbar_avatar" aria-hidden="true">
                            <i class="fa-solid fa-circle-user"></i>
                        </span>
                        <span class="sappc-topbar_admin">Admin</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end sappc-topbar_dropdown-menu"
                        aria-labelledby="sappcAdminMenuBtn">
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

                <div id="sappcDocStatsRoot" class="row g-3 sappc-doc-stats"
                    data-monthly-url="{{ route('admin.dashboard.stats.monthly') }}"
                    data-default-year="{{ $statsYear }}">
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100"
                            data-doc-type="christening" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Christening <span class="sappc-doc-stat_pipe">|</span> Year
                                <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i
                                        class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['christening'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100"
                            data-doc-type="confirmation" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Confirmation <span class="sappc-doc-stat_pipe">|</span> Year
                                <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i
                                        class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['confirmation'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100"
                            data-doc-type="wedding" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Wedding <span class="sappc-doc-stat_pipe">|</span> Year
                                <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i
                                        class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['wedding'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <button type="button" class="sappc-doc-stat sappc-doc-stat_clickable w-100 h-100"
                            data-doc-type="burial" aria-haspopup="dialog" aria-controls="sappcStatMonthlyModal">
                            <p class="sappc-doc-stat_label">Burial <span class="sappc-doc-stat_pipe">|</span> Year
                                <span class="sappc-doc-stat_year">({{ $statsYear }})</span></p>
                            <div class="sappc-doc-stat_body">
                                <div class="sappc-doc-stat_icon" aria-hidden="true"><i
                                        class="fa-solid fa-file-lines"></i></div>
                                <p class="sappc-doc-stat_value">{{ $stats['burial'] ?? 0 }}</p>
                            </div>
                            <span class="visually-hidden">Open monthly breakdown</span>
                        </button>
                    </div>
                </div>

                <div class="modal fade" id="sappcStatMonthlyModal" tabindex="-1"
                    aria-labelledby="sappcStatMonthlyModalTitle" aria-hidden="true">
                    <div
                        class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable sappc-stat-monthly-dialog">
                        <div class="modal-content sappc-stat-monthly-modal">
                            <div class="sappc-stat-monthly-modal__header">
                                <h2 class="visually-hidden" id="sappcStatMonthlyModalTitle">Document</h2>
                                <div class="sappc-stat-monthly-modal__head-row">
                                    <div class="sappc-stat-monthly-modal__controls">
                                        <span class="sappc-stat-monthly-cal-badge" aria-hidden="true">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </span>
                                        <label class="visually-hidden" for="sappcStatMonthlyYear">Year</label>
                                        <select id="sappcStatMonthlyYear"
                                            class="form-select form-select-sm sappc-stat-monthly-year-select"
                                            aria-label="Filter by year">
                                            @foreach ($statsYearOptions as $y)
                                                <option value="{{ $y }}" @selected($y === $statsYear)>
                                                    {{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn-close sappc-stat-monthly-modal__close"
                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                            </div>
                            <div class="modal-body sappc-stat-monthly-modal__body">
                                <div class="sappc-stat-monthly-grid" id="sappcStatMonthlyGrid" role="list"
                                    aria-busy="false"></div>
                                <p class="sappc-stat-monthly-error small text-danger mb-0 d-none"
                                    id="sappcStatMonthlyError" role="alert"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="sappc-table-panel sappc-table-panel--below-overview" id="sappcRecordsPanel"
                    data-records-url="{{ route('admin.dashboard.records') }}"
                    data-delete-url="{{ route('admin.dashboard.records.delete') }}"
                    data-per-page-options="{{ json_encode($perPageOptions) }}"
                    data-url-christening="{{ route('admin.christening.application') }}"
                    data-url-confirmation="{{ route('admin.confirmation.application') }}"
                    data-url-wedding="{{ route('admin.wedding.application') }}"
                    data-url-burial="{{ route('admin.burial.application') }}">
                    <div class="sappc-table-toolbar">
                        <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
                            <div class="sappc-table-toolbar_entries">
                                <label class="visually-hidden" for="sappcEntries">Entries per page</label>
                                <select id="sappcEntries"
                                    class="form-select form-select-sm sappc-table-toolbar_select"
                                    aria-label="Entries per page">
                                    @foreach ($perPageOptions as $n)
                                        <option value="{{ $n }}" @selected($records->perPage() === $n)>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                                <span class="sappc-toolbar-date-strip_label">From:</span>
                                <input type="date" id="sappcDateFrom" class="sappc-toolbar-date-strip_input"
                                    name="date_from" value="{{ request('date_from') }}" aria-label="From date">
                                <span class="sappc-toolbar-date-strip_label">To:</span>
                                <input type="date" id="sappcDateTo" class="sappc-toolbar-date-strip_input"
                                    name="date_to" value="{{ request('date_to') }}" aria-label="To date">
                                <button type="button" id="sappcDateFilterBtn"
                                    class="sappc-toolbar-date-strip_btn">Filter</button>
                            </div>
                            <div class="sappc-table-toolbar_letters" role="group"
                                aria-label="Filter by first letter of client last name">
                                <span class="visually-hidden">Filter by first letter of last name A through Z; scroll
                                    horizontally to see all letters.</span>
                                <div class="sappc-letter-filter_letters">
                                    @foreach ($letterOptions as $letter)
                                        <button type="button"
                                            class="sappc-letter-filter_btn {{ request('letter') === $letter ? 'is-active' : '' }}"
                                            data-letter="{{ $letter }}">{{ $letter }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sappc-table-toolbar_search" role="search">
                                <label class="sappc-table-toolbar_search-heading" for="sappcSearch">Search:</label>
                                <div class="sappc-table-toolbar_search-wrap">
                                    <input type="search" id="sappcSearch"
                                        class="form-control form-control-sm sappc-table-toolbar_search-input"
                                        value="{{ request('search') }}" placeholder="" autocomplete="off"
                                        aria-label="Search registry" aria-controls="sappcTableBody">
                                    <i class="fa-solid fa-magnifying-glass sappc-table-toolbar_search-icon"
                                        aria-hidden="true"></i>
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

                @include('christening.partials.applicationModal')
                @include('confirmation.partials.applicationModal')
                @include('wedding.partials.marriageApplicationModal')
                @include('burial.partials.burialApplicationModal')

                <div class="d-none" aria-hidden="true">
                    <input type="hidden" id="chScheduleChristeningId" value="">
                    <button type="button" id="christeningApplicationFormBtn"
                        data-load-url="{{ route('admin.christening.application-details') }}"></button>
                    <section id="christeningRecordsPanel"
                        data-records-url="{{ route('admin.dashboard.records') }}"
                        data-registry-type="christening"
                        data-application-details-url="{{ route('admin.christening.application-details') }}"
                        data-payment-details-url="{{ route('admin.christening.payment-details') }}"
                        data-payment-save-url="{{ route('admin.christening.payment-save') }}"
                        data-certification-save-url="{{ route('admin.christening.certification-form') }}"
                        data-certification-details-url="{{ route('admin.christening.certification-details') }}"
                        data-christening-delete-url="{{ route('admin.christening.record-delete') }}"
                        data-schedule-details-url="{{ route('admin.christening.schedule-details') }}">
                    </section>
                </div>

                <div class="d-none" aria-hidden="true">
                    <input type="hidden" id="cnScheduleConfirmationId" value="">
                    <button type="button" id="confirmationApplicationFormBtn"
                        data-confirmation-application-details-url="{{ route('admin.confirmation.application-details') }}"
                        data-confirmation-arancel-details-url="{{ route('admin.confirmation.arancel-details') }}"></button>
                    <section id="confirmationRecordsPanel"
                        data-records-url="{{ route('admin.dashboard.records') }}"
                        data-registry-type="confirmation"
                        data-payment-details-url="{{ route('admin.confirmation.payment-details') }}"
                        data-payment-save-url="{{ route('admin.confirmation.payment-save') }}"
                        data-confirmation-application-details-url="{{ route('admin.confirmation.application-details') }}"
                        data-confirmation-application-save-url="{{ route('admin.confirmation.application-save') }}"
                        data-confirmation-arancel-details-url="{{ route('admin.confirmation.arancel-details') }}"
                        data-confirmation-arancel-save-url="{{ route('admin.confirmation.arancel-save') }}"
                        data-confirmation-certification-details-url="{{ route('admin.confirmation.certification-details') }}"
                        data-confirmation-delete-url="{{ route('admin.confirmation.record-delete') }}"
                        data-schedule-details-url="{{ route('admin.confirmation.schedule-details') }}">
                    </section>
                </div>

                <div class="d-none" aria-hidden="true">
                    <input type="hidden" id="wdScheduleWeddingId" value="">
                    <button type="button" id="weddingApplicationFormBtn"
                        data-marriage-application-details-url="{{ route('admin.wedding.marriage-application-details') }}"
                        data-marriage-application-save-url="{{ route('admin.wedding.marriage-application-save') }}"></button>
                    <section id="weddingRecordsPanel"
                        data-records-url="{{ route('admin.dashboard.records') }}"
                        data-registry-type="wedding"
                        data-payment-details-url="{{ route('admin.wedding.payment-details') }}"
                        data-payment-save-url="{{ route('admin.wedding.payment-save') }}"
                        data-marriage-application-details-url="{{ route('admin.wedding.marriage-application-details') }}"
                        data-marriage-application-save-url="{{ route('admin.wedding.marriage-application-save') }}"
                        data-certification-details-url="{{ route('admin.wedding.certification-details') }}"
                        data-wedding-delete-url="{{ route('admin.wedding.record-delete') }}"
                        data-schedule-details-url="{{ route('admin.wedding.schedule-details') }}">
                    </section>
                </div>

                <div class="d-none" aria-hidden="true">
                    <input type="hidden" id="brScheduleBurialId" value="">
                    <button type="button" id="burialApplicationFormBtn"></button>
                    <section id="burialRecordsPanel"
                        data-records-url="{{ route('admin.dashboard.records') }}"
                        data-registry-type="burial"
                        data-payment-details-url="{{ route('admin.burial.payment-details') }}"
                        data-payment-save-url="{{ route('admin.burial.payment-save') }}"
                        data-burial-delete-url="{{ route('admin.burial.record-delete') }}"
                        data-burial-application-details-url="{{ route('admin.burial.application-details') }}"
                        data-burial-application-save-url="{{ route('admin.burial.application-save') }}"
                        data-certification-save-url="{{ route('admin.burial.certification-form') }}"
                        data-certification-details-url="{{ route('admin.burial.certification-details') }}"
                        data-schedule-details-url="{{ route('admin.burial.schedule-details') }}">
                    </section>
                </div>

                <button type="button" id="sappcReloadRecords" class="sappc-table-reload-bar_btn"
                    title="Reload page" aria-label="Reload page">
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    <span class="sappc-table-reload-bar_text">Reload</span>
                </button>
                <section class="sappc-chart-section" id="sappcDocChartRoot"
                    data-monthly-url="{{ route('admin.dashboard.stats.monthly') }}">
                    <h2 class="sappc-chart-section_title">STATISTIC DATA CHART</h2>
                    <div class="sappc-chart-card">
                        <div class="sappc-chart-card_head">
                            <div class="sappc-chart-card_head-lead" aria-hidden="true"></div>
                            <h3 class="sappc-chart-card_subtitle">Number of document requests by month</h3>
                            <div class="sappc-chart-card_filters">
                                <label class="visually-hidden" for="sappcDocChartCategory">Document type</label>
                                <select id="sappcDocChartCategory" class="form-select form-select-sm"
                                    aria-label="Document type">
                                    <option value="all" selected>All types of services</option>
                                    <option value="christening">Christening</option>
                                    <option value="confirmation">Confirmation</option>
                                    <option value="wedding">Wedding</option>
                                    <option value="burial">Burial</option>
                                </select>
                                <label class="visually-hidden" for="sappcDocChartYear">Year</label>
                                <select id="sappcDocChartYear" class="form-select form-select-sm" aria-label="Year">
                                    @foreach ($statsYearOptions as $y)
                                        <option value="{{ $y }}" @selected((int) $y === (int) $statsYear)>{{ $y }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="sappc-chart-card_canvas">
                            <canvas id="sappcDocChart" height="280"
                                aria-label="Bar chart of document requests by month"></canvas>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="sappc-site-footer">
                <p class="sappc-site-footer_text mb-0">© Copyright {{ date('Y') }}. Developed by IS-TECH UNSTOPPABLE. All Rights Reserved</p>
            </footer>
        </div>
    </div>

    <script src="{{ asset('js/adminSidebar.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/sappcDashboardDataTable.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.adminLogoutConfirmScript')
    @include('christening.js.christeningScript', ['initialTablePayload' => $initialTablePayload])
    @include('confirmation.js.confirmationScript')
    @include('wedding.js.weddingScript')
    @include('burial.js.burialScript')
    @include('dashboard.js.sappcDashboardscript', ['initialTablePayload' => $initialTablePayload])
</body>

</html>
