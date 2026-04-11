@extends('layouts.adminDashboard')

@section('title', 'Burial — ' . config('app.name', 'SAPP Church'))

@section('content')
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
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Schedule Request
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline">
                <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                Certification
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline">
                <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                Payment Fee
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                Application Form
            </button>
        </div>
    </div>

    <section
        class="sappc-table-panel"
        id="burialRecordsPanel"
        data-records-url="{{ route('admin.dashboard.records') }}"
        data-registry-type="burial"
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
                        <th scope="col">DATE CREATED</th>
                        <th scope="col" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody id="burialTableBody" aria-live="polite" aria-relevant="additions text">
                    <tr class="sappc-table-loading">
                        <td colspan="8" class="text-center text-muted py-4">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sappc-table-footer">
            <p class="sappc-table-footer_info mb-0" id="burialTableFooterInfo">Showing 0 entries</p>
            <nav class="sappc-pagination" id="burialPagination" aria-label="Table pagination"></nav>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function() {
            'use strict';

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }

            function buildQueryUrl(base, params) {
                var q = new URLSearchParams();
                Object.keys(params).forEach(function(k) {
                    var v = params[k];
                    if (v !== undefined && v !== null && String(v) !== '') {
                        q.set(k, String(v));
                    }
                });
                var sep = base.indexOf('?') >= 0 ? '&' : '?';
                return base + sep + q.toString();
            }

            function fetchJson(url) {
                return fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(function(r) {
                    if (!r.ok) throw new Error(String(r.status));
                    return r.json();
                });
            }

            function rowHtml(row) {
                return (
                    '<tr data-record-id="' + esc(row.recordId) + '" data-document-type="' + esc(row.documentType) + '">' +
                    '<td>' + esc(row.rowNumber) + '</td>' +
                    '<td>' + esc(row.referenceCode) + '</td>' +
                    '<td>' + esc(row.client) + '</td>' +
                    '<td>' + esc(row.address) + '</td>' +
                    '<td>' + esc(row.sex) + '</td>' +
                    '<td>' + esc(row.contactNum) + '</td>' +
                    '<td>' + esc(row.dateCreated) + '</td>' +
                    '<td class="text-center"><div class="sappc-icon-action_group">' +
                    '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="View" aria-label="View record"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>' +
                    '<a href="#" class="sappc-icon-action sappc-icon-action--edit" title="Edit" aria-label="Edit record"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>' +
                    '<button type="button" class="sappc-icon-action sappc-icon-action--delete" title="Delete" aria-label="Delete record"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>' +
                    '</div></td></tr>'
                );
            }

            function whenDomReady(fn) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    fn();
                }
            }

            whenDomReady(function() {
                var panel = document.getElementById('burialRecordsPanel');
                if (!panel) return;

                var url = panel.getAttribute('data-records-url');
                if (!url) return;

                var state = {
                    page: 1,
                    per_page: 10,
                    search: '',
                    letter: '',
                    date_from: '',
                    date_to: '',
                };

                var searchInput = document.getElementById('burialSearch');
                var body = document.getElementById('burialTableBody');
                var info = document.getElementById('burialTableFooterInfo');
                var nav = document.getElementById('burialPagination');

                function renderTable(res) {
                    var html = '';
                    if (!res || !res.data || !res.data.length) {
                        html = '<tr class="sappc-table-empty"><td colspan="8" class="text-center text-muted py-4">No records found.</td></tr>';
                    } else {
                        res.data.forEach(function(row) {
                            html += rowHtml(row);
                        });
                    }
                    body.innerHTML = html;

                    var m = res && res.meta ? res.meta : {};
                    if (!m.total) {
                        info.textContent = 'Showing 0 entries';
                    } else {
                        info.textContent = 'Showing ' + m.from + ' to ' + m.to + ' of ' + m.total + ' entries';
                    }

                    nav.innerHTML = '';
                    var last = Math.max(1, m.last_page || 1);
                    var cur = m.current_page || 1;

                    function appendBtn(h) {
                        nav.insertAdjacentHTML('beforeend', h);
                    }

                    appendBtn(
                        '<button type="button" class="sappc-pagination_btn" data-page="' + (cur - 1) + '" ' + (cur <= 1 ? 'disabled' : '') + ' aria-label="Previous">&lt;</button>'
                    );
                    for (var p = 1; p <= last; p++) {
                        var active = p === cur ? ' is-active' : '';
                        var aria = p === cur ? ' aria-current="page"' : '';
                        appendBtn('<button type="button" class="sappc-pagination_btn' + active + '" data-page="' + p + '"' + aria + '>' + p + '</button>');
                    }
                    appendBtn(
                        '<button type="button" class="sappc-pagination_btn" data-page="' + (cur + 1) + '" ' + (cur >= last ? 'disabled' : '') + ' aria-label="Next">&gt;</button>'
                    );
                }

                function fetchRecords() {
                    body.innerHTML = '<tr class="sappc-table-loading"><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>';
                    var reqUrl = buildQueryUrl(url, {
                        page: state.page,
                        per_page: state.per_page,
                        search: state.search,
                        letter: state.letter,
                        date_from: state.date_from,
                        date_to: state.date_to,
                        registry_type: 'burial',
                    });

                    fetchJson(reqUrl)
                        .then(renderTable)
                        .catch(function(err) {
                            body.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-3">Could not load records (' + esc(err.message || '?') + ').</td></tr>';
                        });
                }

                var searchDebounceTimer;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(function() {
                        state.search = (searchInput.value || '').trim();
                        state.page = 1;
                        fetchRecords();
                    }, 400);
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(searchDebounceTimer);
                        state.search = (searchInput.value || '').trim();
                        state.page = 1;
                        fetchRecords();
                    }
                });

                document.getElementById('burialEntries').addEventListener('change', function() {
                    state.per_page = parseInt(this.value, 10) || 10;
                    state.page = 1;
                    fetchRecords();
                });

                panel.querySelector('.sappc-toolbar-date-strip_btn').addEventListener('click', function() {
                    state.date_from = document.getElementById('burialDateFrom').value || '';
                    state.date_to = document.getElementById('burialDateTo').value || '';
                    state.page = 1;
                    fetchRecords();
                });

                panel.querySelectorAll('.sappc-letter-filter_btn').forEach(function(el) {
                    el.addEventListener('click', function() {
                        var letter = el.getAttribute('data-letter');
                        if (el.classList.contains('is-active')) {
                            el.classList.remove('is-active');
                            state.letter = '';
                        } else {
                            panel.querySelectorAll('.sappc-letter-filter_btn').forEach(function(btn) {
                                btn.classList.remove('is-active');
                            });
                            el.classList.add('is-active');
                            state.letter = letter;
                        }
                        state.page = 1;
                        fetchRecords();
                    });
                });

                nav.addEventListener('click', function(e) {
                    var btn = e.target.closest('.sappc-pagination_btn:not(:disabled)');
                    if (!btn) return;
                    var p = parseInt(btn.getAttribute('data-page'), 10);
                    if (!isNaN(p) && p >= 1) {
                        state.page = p;
                        fetchRecords();
                    }
                });

                var reloadBtn = document.getElementById('burialReloadBtn');
                if (reloadBtn) {
                    reloadBtn.addEventListener('click', fetchRecords);
                }

                fetchRecords();
            });
        })();
    </script>
@endpush
