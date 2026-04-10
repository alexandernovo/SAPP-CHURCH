<script>
    (function() {
        'use strict';

        // Cursor auto-accept test #3 — remove after testing.

        var initialTablePayload = @json($initialTablePayload);

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function getMetaCsrf() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute('content') || '' : '';
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

        function fetchJson(url, headers) {
            return fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: headers || {},
            }).then(function(r) {
                if (!r.ok) {
                    throw new Error(String(r.status));
                }
                return r.json();
            });
        }

        function normalizePerPage(value) {
            var panel = document.getElementById('christeningRecordsPanel');
            var allowed = [10, 25, 50, 100];
            if (panel) {
                try {
                    var raw = panel.getAttribute('data-per-page-options');
                    var parsed = raw ? JSON.parse(raw) : [];
                    if (Array.isArray(parsed) && parsed.length) {
                        allowed = parsed;
                    }
                } catch (e) {}
            }
            var n = parseInt(value, 10);
            return allowed.indexOf(n) !== -1 ? n : allowed[0];
        }

        function whenDomReady(fn) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fn);
            } else {
                fn();
            }
        }

        whenDomReady(function() {
            var csrf = getMetaCsrf();
            var jsonHeaders = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };

            var panel = document.getElementById('christeningRecordsPanel');
            if (!panel) return;

            var url = panel.getAttribute('data-records-url');
            var registryType = (panel.getAttribute('data-registry-type') || '').trim();
            if (!url) return;

            var searchInput = document.getElementById('christeningSearch');
            if (!searchInput) return;

            var meta0 = initialTablePayload.meta || {};
            var state = {
                page: meta0.current_page || 1,
                per_page: normalizePerPage(meta0.per_page || 10),
                search: (searchInput.value || '').trim(),
                letter: @json(request('letter', '')),
                date_from: @json(request('date_from', '')),
                date_to: @json(request('date_to', '')),
            };

            function paymentStatusCell(raw) {
                var s = String(raw == null ? '' : raw).trim();
                var lower = s.toLowerCase();
                if (!s || s === '\u2014') {
                    return '<span class="text-muted">\u2014</span>';
                }
                if (lower === 'paid') {
                    return '<span class="sappc-payment-badge sappc-payment-badge--paid">Paid</span>';
                }
                if (lower === 'unpaid') {
                    return '<span class="sappc-payment-badge sappc-payment-badge--unpaid">Unpaid</span>';
                }
                return esc(s);
            }

            function rowHtml(row) {
                return (
                    '<tr data-record-id="' +
                    esc(row.recordId) +
                    '" data-document-type="' +
                    esc(row.documentType) +
                    '">' +
                    '<td>' +
                    esc(row.rowNumber) +
                    '</td>' +
                    '<td>' +
                    esc(row.referenceCode) +
                    '</td>' +
                    '<td>' +
                    esc(row.client) +
                    '</td>' +
                    '<td>' +
                    esc(row.address) +
                    '</td>' +
                    '<td>' +
                    esc(row.sex) +
                    '</td>' +
                    '<td>' +
                    esc(row.contactNum) +
                    '</td>' +
                    '<td class="text-center align-middle">' +
                    paymentStatusCell(row.paymentStatus) +
                    '</td>' +
                    '<td>' +
                    esc(row.dateCreated) +
                    '</td>' +
                    '<td class="text-center">' +
                    '<div class="sappc-icon-action_group">' +
                    '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="View" aria-label="View record" data-record-id="' +
                    esc(row.recordId) +
                    '"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>' +
                    '<a href="#" class="sappc-icon-action sappc-icon-action--edit" title="Edit" aria-label="Edit record" data-record-id="' +
                    esc(row.recordId) +
                    '"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>' +
                    '<button type="button" class="sappc-icon-action sappc-icon-action--delete" title="Delete" aria-label="Delete record" data-record-id="' +
                    esc(row.recordId) +
                    '"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>' +
                    '</div></td></tr>'
                );
            }

            function renderTable(res) {
                var tbody = document.getElementById('christeningTableBody');
                var html = '';
                if (!res || !res.data || !res.data.length) {
                    html =
                        '<tr class="sappc-table-empty"><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>';
                } else {
                    res.data.forEach(function(row) {
                        html += rowHtml(row);
                    });
                }
                tbody.innerHTML = html;

                var m = res && res.meta ? res.meta : {};
                var info = document.getElementById('christeningTableFooterInfo');
                if (!m.total) {
                    info.textContent = 'Showing 0 entries';
                } else {
                    info.textContent =
                        'Showing ' + m.from + ' to ' + m.to + ' of ' + m.total + ' entries';
                }

                var nav = document.getElementById('christeningPagination');
                nav.innerHTML = '';
                var last = Math.max(1, m.last_page || 1);
                var cur = m.current_page || 1;

                function appendBtn(h) {
                    nav.insertAdjacentHTML('beforeend', h);
                }

                appendBtn(
                    '<button type="button" class="sappc-pagination_btn sappc-ch-page-prev" data-page="' +
                    (cur - 1) +
                    '" ' +
                    (cur <= 1 ? 'disabled' : '') +
                    ' aria-label="Previous">&lt;</button>'
                );
                for (var p = 1; p <= last; p++) {
                    var active = p === cur ? ' is-active' : '';
                    var aria = p === cur ? ' aria-current="page"' : '';
                    appendBtn(
                        '<button type="button" class="sappc-pagination_btn sappc-ch-page-num' +
                        active +
                        '" data-page="' +
                        p +
                        '"' +
                        aria +
                        '>' +
                        p +
                        '</button>'
                    );
                }
                appendBtn(
                    '<button type="button" class="sappc-pagination_btn sappc-ch-page-next" data-page="' +
                    (cur + 1) +
                    '" ' +
                    (cur >= last ? 'disabled' : '') +
                    ' aria-label="Next">&gt;</button>'
                );
            }

            renderTable(initialTablePayload);

            function fetchQueryParams() {
                var q = {
                    page: state.page,
                    per_page: state.per_page,
                    search: state.search,
                    letter: state.letter,
                    date_from: state.date_from,
                    date_to: state.date_to,
                };
                if (registryType) {
                    q.registry_type = registryType;
                }
                return q;
            }

            function fetchRecords() {
                var tbody = document.getElementById('christeningTableBody');
                tbody.innerHTML =
                    '<tr class="sappc-table-loading"><td colspan="9" class="text-center text-muted py-4">Loading…</td></tr>';

                var reqUrl = buildQueryUrl(url, fetchQueryParams());

                fetchJson(reqUrl, jsonHeaders)
                    .then(renderTable)
                    .catch(function(err) {
                        tbody.innerHTML =
                            '<tr><td colspan="9" class="text-center text-danger py-3">Could not load records (' +
                            (err.message || '?') +
                            ').</td></tr>';
                    });
            }

            function applySearchFromInput() {
                state.search = (searchInput.value || '').trim();
                state.page = 1;
                fetchRecords();
            }

            var searchDebounceTimer;

            function scheduleSearchFromInput() {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(applySearchFromInput, 400);
            }

            document.getElementById('christeningPagination').addEventListener('click', function(e) {
                var btn = e.target.closest('.sappc-pagination_btn:not(:disabled)');
                if (!btn) return;
                var p = parseInt(btn.getAttribute('data-page'), 10);
                if (!isNaN(p) && p >= 1) {
                    state.page = p;
                    fetchRecords();
                }
            });

            document.getElementById('christeningEntries').addEventListener('change', function() {
                state.per_page = normalizePerPage(this.value);
                state.page = 1;
                fetchRecords();
            });

            var dateFilterBtn = panel.querySelector('.sappc-toolbar-date-strip_btn');
            if (dateFilterBtn) {
                dateFilterBtn.addEventListener('click', function() {
                    state.date_from = document.getElementById('christeningDateFrom').value || '';
                    state.date_to = document.getElementById('christeningDateTo').value || '';
                    state.page = 1;
                    fetchRecords();
                });
            }

            searchInput.addEventListener('input', scheduleSearchFromInput);
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    applySearchFromInput();
                }
            });

            panel.querySelectorAll('.sappc-letter-filter_btn').forEach(function(el) {
                el.addEventListener('click', function() {
                    var L = el.getAttribute('data-letter');
                    if (el.classList.contains('is-active')) {
                        el.classList.remove('is-active');
                        state.letter = '';
                    } else {
                        panel.querySelectorAll('.sappc-letter-filter_btn').forEach(function(
                            b) {
                            b.classList.remove('is-active');
                        });
                        el.classList.add('is-active');
                        state.letter = L;
                    }
                    state.page = 1;
                    fetchRecords();
                });
            });

            var reloadBtn = document.getElementById('christeningReloadBtn');
            if (reloadBtn) {
                reloadBtn.addEventListener('click', function() {
                    fetchRecords();
                });
            }
        });
    })();
</script>
