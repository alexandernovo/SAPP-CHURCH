@extends('layouts.adminDashboard')

@section('title', 'Burial — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/burial/burialApplication.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
    <input type="hidden" id="brSelectedBurialId" value="">

    <h1 class="sappc-page-title">
        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
        BURIAL
    </h1>
    <p class="sappc-page-breadcrumb mb-0">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
        <span>Burial</span>
    </p>

    <div class="sappc-registry-toolbar" role="toolbar" aria-label="Burial record actions">
        <span class="sappc-registry-toolbar_record">RECORD</span>
        <div class="sappc-registry-toolbar_actions">
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--reload" id="burialReloadBtn" title="Reload" aria-label="Reload table">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reload
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                id="burialScheduleRequestBtn"
                data-schedule-save-url="{{ route('admin.burial.schedule-request') }}" title="Schedule request"
                aria-label="Open schedule request" aria-expanded="false" aria-controls="burialScheduleRequestModal"
                data-bs-toggle="modal" data-bs-target="#burialScheduleRequestModal">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Schedule Request
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="burialPaymentFeeBtn" title="Payment fee" aria-label="Open payment fee" aria-expanded="false"
                aria-controls="burialPaymentFeeModal">
                <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                Payment Fee
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="burialApplicationFormBtn" title="Burial application form"
                aria-label="Open burial application form" aria-expanded="false"
                aria-controls="burialApplicationFormModal">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                Application Form
            </button>
        </div>
    </div>

    @include('burial.partials.burialApplicationModal')

    <div class="sappcPaymentFeeModal">
        <div class="modal fade" id="burialPaymentFeeModal" tabindex="-1"
            aria-labelledby="burialPaymentFeeModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcPaymentFeeModalDialog">
                <div class="modal-content sappcPaymentFeeModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden"
                            id="burialPaymentFeeModalTitle">Payment fee record</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcPaymentFeeModalForm" id="burialPaymentFeeForm" action="#"
                            method="post" autocomplete="off"
                            data-save-url="{{ route('admin.burial.payment-save') }}">
                            <div class="sappcChOfficial sappcPaymentFeeModalOfficial">
                                <header class="sappcChOfficialHeader">
                                    <div class="sappcChOfficialLogo sappcChOfficialLogoLeft">
                                        <img src="{{ asset('assets/logos/DSA.jpg') }}" width="72" height="72"
                                            alt="Diocese of San Jose de Antique" class="sappcChOfficialLogoImg">
                                    </div>
                                    <div class="sappcChOfficialMasthead">
                                        <p class="sappcChOfficialMastheadLine sappcChOfficialMastheadLineStrong">
                                            The Roman Catholic Parish of St. Anthony of Padua</p>
                                        <p class="sappcChOfficialMastheadLine">Diocese of San Jose de Antique</p>
                                        <p class="sappcChOfficialMastheadLine">Barbaza, 5706, Antique, Philippines</p>
                                    </div>
                                    <div class="sappcChOfficialLogo sappcChOfficialLogoRight sappcChOfficialLogoParishSeal">
                                        <img src="{{ asset('assets/logos/SAPPC.png') }}" width="72" height="72"
                                            alt="Parish of St. Anthony of Padua, Barbaza"
                                            class="sappcChOfficialLogoImg sappcChOfficialLogoImgParishSeal">
                                    </div>
                                </header>

                                <div class="sappcPaymentFeeModalFields">
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="brPaymentRefCode">Reference
                                            Code</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="brPaymentRefCode"
                                            name="reference_code" value="" readonly
                                            title="System-generated; use when creating a new record">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="brPaymentClient">Client</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="brPaymentClient"
                                            name="client" value="">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="brPaymentContact">Contact
                                            Number</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="brPaymentContact"
                                            name="contact_number" value="" inputmode="tel">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="brPaymentAddress">Address</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="brPaymentAddress"
                                            name="address" value="">
                                    </div>
                                </div>

                                <h3 class="sappcPaymentFeeModalFeeHeading">Arancel kang lubong</h3>

                                <div class="table-responsive sappcPaymentFeeModalTableWrap">
                                    <table class="table table-bordered mb-0 sappcPaymentFeeModalTable">
                                        <thead>
                                            <tr>
                                                <th scope="col"
                                                    class="sappcPaymentFeeModalTh sappcPaymentFeeModalThNo">No.</th>
                                                <th scope="col" class="sappcPaymentFeeModalTh">Item</th>
                                                <th scope="col" class="sappcPaymentFeeModalTh">Status Fee</th>
                                                <th scope="col" class="sappcPaymentFeeModalTh">Date of Paid</th>
                                                <th scope="col"
                                                    class="sappcPaymentFeeModalTh sappcPaymentFeeModalThAction text-center">
                                                    Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="burialPaymentFeeItemsBody">
                                            <tr class="sappcPaymentFeeModalRow" data-fee-row>
                                                <td class="sappcPaymentFeeModalCellNo">1</td>
                                                <td>
                                                    <input type="text" class="sappcPaymentFeeModalItemInput"
                                                        name="fee_items[]" value="" aria-label="Fee item 1">
                                                </td>
                                                <td>
                                                    <span
                                                        class="sappcPaymentFeeModalStatus sappcPaymentFeeModalStatusUnpaid">Unpaid</span>
                                                </td>
                                                <td>
                                                    <span class="sappcPaymentFeeModalDatePaid" data-date-paid="">&#8212;</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="sappcPaymentFeeModalActions">
                                                        <button type="button"
                                                            class="sappcPaymentFeeModalToggleUnpaid">Paid</button>
                                                        <button type="button" class="sappcPaymentFeeModalBtnRemove"
                                                            aria-label="Remove row">
                                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="sappcPaymentFeeModalBelowTable">
                                    <button type="button" class="sappcPaymentFeeModalBtnAddItem"
                                        id="burialPaymentFeeAddItemBtn">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        Add item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcPaymentFeeModalFooter sappcChristeningAppModalFooter">
                        <button type="submit" form="burialPaymentFeeForm"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                            id="burialPaymentFeeSaveBtn">
                            Save
                        </button>
                        <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnCancel"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section
        class="sappc-table-panel"
        id="burialRecordsPanel"
        data-records-url="{{ route('admin.dashboard.records') }}"
        data-registry-type="burial"
        data-payment-details-url="{{ route('admin.burial.payment-details') }}"
        data-payment-save-url="{{ route('admin.burial.payment-save') }}"
        data-burial-delete-url="{{ route('admin.burial.record-delete') }}"
        data-burial-application-details-url="{{ route('admin.burial.application-details') }}"
        data-burial-application-save-url="{{ route('admin.burial.application-save') }}"
        data-schedule-details-url="{{ route('admin.burial.schedule-details') }}"
        aria-label="Burial records"
    >
        <div class="sappc-table-toolbar">
            <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
                <div class="sappc-table-toolbar_entries">
                    <label class="visually-hidden" for="burialEntries">Entries per page</label>
                    <select id="burialEntries" class="form-select form-select-sm sappc-table-toolbar_select" aria-label="Entries per page">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                    <span class="sappc-toolbar-date-strip_label">From:</span>
                    <input type="date" id="burialDateFrom" class="sappc-toolbar-date-strip_input" name="date_from" aria-label="From date">
                    <span class="sappc-toolbar-date-strip_label">To:</span>
                    <input type="date" id="burialDateTo" class="sappc-toolbar-date-strip_input" name="date_to" aria-label="To date">
                    <button type="button" class="sappc-toolbar-date-strip_btn">Filter</button>
                </div>
                <div class="sappc-table-toolbar_letters" role="group" aria-label="Filter by first letter of client last name">
                    <span class="visually-hidden">Filter by first letter of last name A through Z; scroll horizontally to see all letters.</span>
                    <div class="sappc-letter-filter_letters">
                        @foreach (range('A', 'Z') as $letter)
                            <button type="button" class="sappc-letter-filter_btn" data-letter="{{ $letter }}">{{ $letter }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="sappc-table-toolbar_search" role="search">
                    <label class="sappc-table-toolbar_search-heading" for="burialSearch">Search:</label>
                    <div class="sappc-table-toolbar_search-wrap">
                        <input type="search" id="burialSearch" class="form-control form-control-sm sappc-table-toolbar_search-input" placeholder="" autocomplete="off" aria-label="Search burial records" aria-controls="burialTableBody">
                        <i class="fa-solid fa-magnifying-glass sappc-table-toolbar_search-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive sappc-table-panel_scroll">
            <table class="table table-bordered mb-0 sappc-data-table">
                <thead>
                    <tr>
                        <th scope="col">NO.</th>
                        <th scope="col">REFERENCE CODE</th>
                        <th scope="col">CLIENT</th>
                        <th scope="col">ADDRESS</th>
                        <th scope="col">SEX</th>
                        <th scope="col">CONTACT NUMBER</th>
                        <th scope="col">PAYMENT STATUS</th>
                        <th scope="col">DATE CREATED</th>
                        <th scope="col" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody id="burialTableBody" aria-live="polite" aria-relevant="additions text">
                    <tr class="sappc-table-loading">
                        <td colspan="9" class="text-center text-muted py-4">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sappc-table-footer">
            <p class="sappc-table-footer_info mb-0" id="burialTableFooterInfo">Showing 0 entries</p>
            <nav class="sappc-pagination" id="burialPagination" aria-label="Table pagination"></nav>
        </div>
    </section>

    <div class="modal fade" id="burialScheduleRequestModal" tabindex="-1"
        aria-labelledby="burialScheduleRequestModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content sappcScheduleRequestModal">
                <div class="modal-body">
                    <button type="button" class="btn-close sappcScheduleRequestModalClose" data-bs-dismiss="modal"
                        aria-label="Close"></button>

                    <div class="sappcScheduleRequestLayout">
                        <section class="sappcScheduleCalendarCard" aria-label="Calendar">
                            <div class="sappcScheduleCalendarHead sappcScheduleCalendarHead--interactive">
                                <button type="button" class="sappcScheduleCalNav" id="brCalPrev"
                                    aria-label="Previous month">‹</button>
                                <span class="sappcScheduleCalendarMonthNo" id="brCalMonthNum">3</span>
                                <select class="sappcScheduleCalendarMonth" id="brCalMonth" aria-label="Month"></select>
                                <select class="sappcScheduleCalendarYear" id="brCalYear" aria-label="Year"></select>
                                <button type="button" class="sappcScheduleCalNav" id="brCalNext"
                                    aria-label="Next month">›</button>
                            </div>

                            <div class="sappcScheduleCalendarGrid" role="grid" aria-label="Select burial date"
                                id="brCalGrid">
                                <div class="sappcScheduleCalendarWeekday sunday">SUN</div>
                                <div class="sappcScheduleCalendarWeekday">MON</div>
                                <div class="sappcScheduleCalendarWeekday">TUE</div>
                                <div class="sappcScheduleCalendarWeekday">WED</div>
                                <div class="sappcScheduleCalendarWeekday">THU</div>
                                <div class="sappcScheduleCalendarWeekday">FRI</div>
                                <div class="sappcScheduleCalendarWeekday saturday">SAT</div>
                                <div id="brCalDayCells"></div>
                            </div>
                        </section>

                        <section class="sappcScheduleFormCard">
                            <header class="sappcScheduleFormHeader">
                                <img src="{{ asset('assets/logos/SAPPC.png') }}" alt="Parish logo"
                                    class="sappcScheduleFormLogo">
                                <h2 id="burialScheduleRequestModalTitle">Burial Schedule Request Form</h2>
                            </header>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <form id="burialScheduleRequestForm" method="post" autocomplete="off"
                                data-schedule-save-url="{{ route('admin.burial.schedule-request') }}"
                                data-schedule-reserved-url="{{ route('admin.burial.schedule-reserved-dates') }}"
                                data-default-reference-code="{{ $generatedReferenceCode ?? '' }}">
                                @csrf
                                <div class="sappcScheduleFormField">
                                    <input type="hidden" name="burial_id" id="brScheduleBurialId" value="">
                                    <label for="brScheduleRefCode">Reference Code:</label>
                                    <input type="text" name="reference_code" id="brScheduleRefCode"
                                        value="{{ $generatedReferenceCode ?? '' }}"
                                        placeholder="System default; edit or pick a table row"
                                        title="Generated on page load. Click a row to use that record's code.">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="brScheduleContact">Contact Number:</label>
                                    <input type="text" name="contact_number" id="brScheduleContact" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="brScheduleClient">Client:</label>
                                    <input type="text" name="client" id="brScheduleClient" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="brScheduleAddress">Address:</label>
                                    <input type="text" name="address" id="brScheduleAddress" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="brScheduleSex">Sex:</label>
                                    <select name="sex" id="brScheduleSex" class="form-select">
                                        <option value="">Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="brScheduleDate">Date:</label>
                                    <input type="date" name="schedule_date" id="brScheduleDate" required
                                        class="sappcScheduleNativeInput">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="brScheduleTime24">Time:</label>
                                    <input type="time" name="schedule_time" id="brScheduleTime24" required
                                        step="60" class="sappcScheduleNativeInput">
                                </div>
                            </form>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <div class="sappcScheduleFormActions">
                                <button type="button" class="sappcScheduleActionBtn is-cancel"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="burialScheduleRequestForm"
                                    class="sappcScheduleActionBtn is-reserve">Reserved Schedule</button>
                                <button type="button" class="sappcScheduleActionBtn is-calendar">View Calendar</button>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('burial.js.burialScript', [
        'initialTablePayload' => $initialTablePayload ?? null,
        'activeSection' => $activeSection ?? 'application',
    ])
@endpush
