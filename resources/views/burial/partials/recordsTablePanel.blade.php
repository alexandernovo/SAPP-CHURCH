@php
    $tableColumns = $tableColumns ?? [
        'NO.', 'REFERENCE CODE', 'CLIENT', 'ADDRESS', 'CONTACT NUMBER', 'DATE CREATED', 'ACTION',
    ];
    $tableColspan = $tableColspan ?? count($tableColumns);
@endphp

<input type="hidden" id="brSelectedBurialId" value="">

<section class="sappc-table-panel" id="burialRecordsPanel"
    data-table-colspan="{{ $tableColspan }}"
    data-records-url="{{ route('admin.dashboard.records') }}" data-registry-type="burial"
    data-section="{{ $activeSection ?? 'application' }}"
    data-next-reference-url="{{ route('admin.burial.next-reference-code') }}"
    data-sort-order="{{ $sortOrder ?? 'desc' }}"
    data-workflow-has-certification="{{ !empty($workflowHasCertification) ? '1' : '0' }}"
    data-workflow-application-url="{{ route('admin.burial.application') }}"
    data-workflow-payment-url="{{ route('admin.burial.payment') }}"
    data-workflow-schedule-url="{{ route('admin.burial.schedule') }}"
    data-payment-details-url="{{ route('admin.burial.payment-details') }}"
    data-payment-save-url="{{ route('admin.burial.payment-save') }}"
    data-burial-delete-url="{{ route('admin.burial.record-delete') }}"
    data-burial-application-details-url="{{ route('admin.burial.application-details') }}"
    data-burial-application-save-url="{{ route('admin.burial.application-save') }}"
    data-certification-save-url="{{ route('admin.burial.certification-form') }}"
    data-certification-details-url="{{ route('admin.burial.certification-details') }}"
    data-schedule-details-url="{{ route('admin.burial.schedule-details') }}"
    data-per-page-options="{{ json_encode($perPageOptions) }}"
    aria-label="Burial {{ $sectionLabel ?? 'records' }}">
    <div class="sappc-table-toolbar">
        <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
            <div class="sappc-table-toolbar_entries">
                <label class="visually-hidden" for="burialEntries">Entries per page</label>
                <select id="burialEntries" class="form-select form-select-sm sappc-table-toolbar_select" aria-label="Entries per page">
                    @foreach ($perPageOptions as $n)
                        <option value="{{ $n }}" @selected($records->perPage() === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                <span class="sappc-toolbar-date-strip_label">From:</span>
                <input type="date" id="burialDateFrom" class="sappc-toolbar-date-strip_input" name="date_from" value="{{ request('date_from') }}" aria-label="From date">
                <span class="sappc-toolbar-date-strip_label">To:</span>
                <input type="date" id="burialDateTo" class="sappc-toolbar-date-strip_input" name="date_to" value="{{ request('date_to') }}" aria-label="To date">
                <button type="button" class="sappc-toolbar-date-strip_btn">Filter</button>
            </div>
            <div class="sappc-table-toolbar_letters" role="group" aria-label="Filter by first letter of client last name">
                <span class="visually-hidden">Filter by first letter of last name A through Z.</span>
                <div class="sappc-letter-filter_letters">
                    @foreach ($letterOptions as $letter)
                        <button type="button" class="sappc-letter-filter_btn {{ request('letter') === $letter ? 'is-active' : '' }}" data-letter="{{ $letter }}">{{ $letter }}</button>
                    @endforeach
                </div>
            </div>
            <div class="sappc-table-toolbar_search" role="search">
                <label class="sappc-table-toolbar_search-heading" for="burialSearch">Search:</label>
                <div class="sappc-table-toolbar_search-wrap">
                    <input type="search" id="burialSearch" class="form-control form-control-sm sappc-table-toolbar_search-input" value="{{ request('search') }}" placeholder="" autocomplete="off" aria-label="Search burial records" aria-controls="burialTableBody">
                    <i class="fa-solid fa-magnifying-glass sappc-table-toolbar_search-icon" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive sappc-table-panel_scroll">
        <table class="table table-bordered mb-0 sappc-data-table">
            <thead>
                <tr>
                    @foreach ($tableColumns as $col)
                        <th scope="col" @if ($col === 'ACTION') class="text-center" @endif>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="burialTableBody" aria-live="polite" aria-relevant="additions text"></tbody>
        </table>
    </div>
    <div class="sappc-table-footer">
        <p class="sappc-table-footer_info mb-0" id="burialTableFooterInfo"></p>
        <nav class="sappc-pagination" id="burialPagination" aria-label="Table pagination"></nav>
    </div>
</section>
