@php
    $tableColumns = $tableColumns ?? [
        'NO.', 'REFERENCE CODE', 'CLIENT', 'ADDRESS', 'SEX', 'CONTACT NUMBER', 'DATE CREATED', 'ACTION',
    ];
    $tableColspan = $tableColspan ?? count($tableColumns);
@endphp

<input type="hidden" id="cnSelectedConfirmationId" value="">

<section class="sappc-table-panel" id="confirmationRecordsPanel"
    data-table-colspan="{{ $tableColspan }}"
    data-records-url="{{ route('admin.dashboard.records') }}" data-registry-type="confirmation"
    data-section="{{ $activeSection ?? 'application' }}"
    data-workflow-has-certification="0"
    data-workflow-application-url="{{ route('admin.confirmation.application') }}"
    data-workflow-payment-url="{{ route('admin.confirmation.payment') }}"
    data-workflow-schedule-url="{{ route('admin.confirmation.schedule') }}"
    data-payment-details-url="{{ route('admin.confirmation.payment-details') }}"
    data-payment-save-url="{{ route('admin.confirmation.payment-save') }}"
    data-confirmation-application-details-url="{{ route('admin.confirmation.application-details') }}"
    data-confirmation-application-save-url="{{ route('admin.confirmation.application-save') }}"
    data-confirmation-arancel-details-url="{{ route('admin.confirmation.arancel-details') }}"
    data-confirmation-arancel-save-url="{{ route('admin.confirmation.arancel-save') }}"
    data-confirmation-certification-details-url="{{ route('admin.confirmation.certification-details') }}"
    data-confirmation-delete-url="{{ route('admin.confirmation.record-delete') }}"
    data-schedule-details-url="{{ route('admin.confirmation.schedule-details') }}"
    data-per-page-options="{{ json_encode($perPageOptions) }}"
    aria-label="Confirmation {{ $sectionLabel ?? 'records' }}">
    <div class="sappc-table-toolbar">
        <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
            <div class="sappc-table-toolbar_entries">
                <label class="visually-hidden" for="confirmationEntries">Entries per page</label>
                <select id="confirmationEntries" class="form-select form-select-sm sappc-table-toolbar_select" aria-label="Entries per page">
                    @foreach ($perPageOptions as $n)
                        <option value="{{ $n }}" @selected($records->perPage() === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                <span class="sappc-toolbar-date-strip_label">From:</span>
                <input type="date" id="confirmationDateFrom" class="sappc-toolbar-date-strip_input" name="date_from" value="{{ request('date_from') }}" aria-label="From date">
                <span class="sappc-toolbar-date-strip_label">To:</span>
                <input type="date" id="confirmationDateTo" class="sappc-toolbar-date-strip_input" name="date_to" value="{{ request('date_to') }}" aria-label="To date">
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
                <label class="sappc-table-toolbar_search-heading" for="confirmationSearch">Search:</label>
                <div class="sappc-table-toolbar_search-wrap">
                    <input type="search" id="confirmationSearch" class="form-control form-control-sm sappc-table-toolbar_search-input" value="{{ request('search') }}" placeholder="" autocomplete="off" aria-label="Search confirmation records" aria-controls="confirmationTableBody">
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
            <tbody id="confirmationTableBody" aria-live="polite" aria-relevant="additions text"></tbody>
        </table>
    </div>
    <div class="sappc-table-footer">
        <p class="sappc-table-footer_info mb-0" id="confirmationTableFooterInfo"></p>
        <nav class="sappc-pagination" id="confirmationPagination" aria-label="Table pagination"></nav>
    </div>
</section>
