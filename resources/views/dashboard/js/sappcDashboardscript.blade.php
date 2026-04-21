<script>
    (function() {
        'use strict';

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
                if (v !== undefined && v !== null) {
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

            var statsRoot = document.getElementById('sappcDocStatsRoot');
            if (statsRoot) {
                var monthlyUrl = statsRoot.getAttribute('data-monthly-url');
                var defaultYear = parseInt(statsRoot.getAttribute('data-default-year'), 10);
                var docTypeLabels = {
                    christening: 'Christening',
                    confirmation: 'Confirmation',
                    wedding: 'Wedding',
                    burial: 'Burial',
                };
                var currentDocType = 'christening';
                var modalEl = document.getElementById('sappcStatMonthlyModal');
                var bsModal =
                    modalEl && typeof bootstrap !== 'undefined' ?
                    bootstrap.Modal.getOrCreateInstance(modalEl) :
                    null;

                function loadMonthlyBreakdown() {
                    if (!monthlyUrl || !currentDocType) return;
                    var year = parseInt(
                        document.getElementById('sappcStatMonthlyYear').value,
                        10
                    );
                    var grid = document.getElementById('sappcStatMonthlyGrid');
                    var errEl = document.getElementById('sappcStatMonthlyError');
                    errEl.classList.add('d-none');
                    errEl.textContent = '';
                    grid.setAttribute('aria-busy', 'true');
                    grid.innerHTML =
                        '<p class="text-muted small mb-0 py-3 text-center">Loading…</p>';

                    var u = buildQueryUrl(monthlyUrl, {
                        type: currentDocType,
                        year: year
                    });
                    fetchJson(u, {
                            Accept: 'application/json'
                        })
                        .then(function(res) {
                            var html = '';
                            (res.months || []).forEach(function(m) {
                                html +=
                                    '<div class="sappc-stat-monthly-tile" role="listitem"><span class="sappc-stat-monthly-tile_label">' +
                                    esc(m.label) +
                                    '</span><span class="sappc-stat-monthly-tile_value">' +
                                    esc(String(m.count)) +
                                    '</span></div>';
                            });
                            grid.innerHTML = html;
                        })
                        .catch(function() {
                            errEl.textContent = 'Could not load statistics.';
                            errEl.classList.remove('d-none');
                            grid.innerHTML = '';
                        })
                        .finally(function() {
                            grid.setAttribute('aria-busy', 'false');
                        });
                }

                statsRoot.addEventListener('click', function(e) {
                    var btn = e.target.closest('.sappc-doc-stat_clickable');
                    if (!btn) return;
                    currentDocType = btn.getAttribute('data-doc-type');
                    document.getElementById('sappcStatMonthlyModalTitle').textContent =
                        docTypeLabels[currentDocType] || currentDocType;
                    document.getElementById('sappcStatMonthlyYear').value = String(defaultYear);
                    loadMonthlyBreakdown();
                    if (bsModal) bsModal.show();
                });

                document
                    .getElementById('sappcStatMonthlyYear')
                    .addEventListener('change', loadMonthlyBreakdown);
            }

            var panel = document.getElementById('sappcRecordsPanel');
            if (!panel) return;

            var url = panel.getAttribute('data-records-url');
            if (!url) return;

            var searchInput = document.getElementById('sappcSearch');
            if (!searchInput) return;

            var meta0 = initialTablePayload.meta || {};

            var state = {
                page: meta0.current_page || 1,
                per_page: typeof sappcNormalizePerPage === 'function' ?
                    sappcNormalizePerPage(meta0.per_page || 10) :
                    meta0.per_page || 10,
                search: (searchInput.value || '').trim(),
                letter: @json(request('letter', '')),
                date_from: @json(request('date_from', '')),
                date_to: @json(request('date_to', '')),
            };

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
                    '<td>' +
                    esc(row.documentType) +
                    '</td>' +
                    '<td>' +
                    esc(row.dateCreated) +
                    '</td>' +
                    '<td class="text-center text-nowrap">' +
                    '<button type="button" class="btn btn-link btn-sm sappc-action-edit p-0 me-2" title="Edit" aria-label="Edit" data-record-id="' +
                    esc(row.recordId) +
                    '" data-document-type="' +
                    esc(row.documentType) +
                    '"><i class="fa-solid fa-pen-to-square"></i></button>' +
                    '<button type="button" class="btn btn-link btn-sm sappc-action-delete p-0" title="Delete" aria-label="Delete" data-record-id="' +
                    esc(row.recordId) +
                    '" data-document-type="' +
                    esc(row.documentType) +
                    '"><i class="fa-solid fa-trash"></i></button>' +
                    '</td></tr>'
                );
            }

            function renderTable(res) {
                var tbody = document.getElementById('sappcTableBody');
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
                var info = document.getElementById('sappcTableFooterInfo');
                if (!m.total) {
                    info.textContent = 'Showing 0 entries';
                } else {
                    info.textContent =
                        'Showing ' + m.from + ' to ' + m.to + ' of ' + m.total + ' entries';
                }

                var nav = document.getElementById('sappcPagination');
                nav.innerHTML = '';
                var last = Math.max(1, m.last_page || 1);
                var cur = m.current_page || 1;

                function appendBtn(html) {
                    nav.insertAdjacentHTML('beforeend', html);
                }

                appendBtn(
                    '<button type="button" class="sappc-pagination_btn sappc-page-prev" data-page="' +
                    (cur - 1) +
                    '" ' +
                    (cur <= 1 ? 'disabled' : '') +
                    ' aria-label="Previous">&lt;</button>'
                );
                for (var p = 1; p <= last; p++) {
                    var active = p === cur ? ' is-active' : '';
                    var aria = p === cur ? ' aria-current="page"' : '';
                    appendBtn(
                        '<button type="button" class="sappc-pagination_btn sappc-page-num' +
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
                    '<button type="button" class="sappc-pagination_btn sappc-page-next" data-page="' +
                    (cur + 1) +
                    '" ' +
                    (cur >= last ? 'disabled' : '') +
                    ' aria-label="Next">&gt;</button>'
                );
            }

            renderTable(initialTablePayload);

            function fetchRecords() {
                var tbody = document.getElementById('sappcTableBody');
                tbody.innerHTML =
                    '<tr class="sappc-table-loading"><td colspan="9" class="text-center text-muted py-4">Loading…</td></tr>';

                var reqUrl = buildQueryUrl(url, {
                    page: state.page,
                    per_page: state.per_page,
                    search: state.search,
                    letter: state.letter,
                    date_from: state.date_from,
                    date_to: state.date_to,
                });

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

            document.getElementById('sappcPagination').addEventListener('click', function(e) {
                var btn = e.target.closest('.sappc-pagination_btn:not(:disabled)');
                if (!btn) return;
                var p = parseInt(btn.getAttribute('data-page'), 10);
                if (!isNaN(p) && p >= 1) {
                    state.page = p;
                    fetchRecords();
                }
            });

            document.getElementById('sappcEntries').addEventListener('change', function() {
                var v = this.value;
                state.per_page =
                    typeof sappcNormalizePerPage === 'function' ?
                    sappcNormalizePerPage(v) :
                    parseInt(v, 10) || 10;
                state.page = 1;
                fetchRecords();
            });

            var dateFilterBtn = document.getElementById('sappcDateFilterBtn');
            if (dateFilterBtn) {
                dateFilterBtn.addEventListener('click', function() {
                    state.date_from = document.getElementById('sappcDateFrom').value || '';
                    state.date_to = document.getElementById('sappcDateTo').value || '';
                    state.page = 1;
                    fetchRecords();
                });
            }

            var reloadBtn = document.getElementById('sappcReloadRecords');
            if (reloadBtn) {
                reloadBtn.addEventListener('click', function() {
                    window.location.reload();
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

            document.querySelectorAll('.sappc-letter-filter_btn').forEach(function(el) {
                el.addEventListener('click', function() {
                    var L = el.getAttribute('data-letter');
                    if (el.classList.contains('is-active')) {
                        el.classList.remove('is-active');
                        state.letter = '';
                    } else {
                        document.querySelectorAll('.sappc-letter-filter_btn').forEach(
                            function(b) {
                                b.classList.remove('is-active');
                            });
                        el.classList.add('is-active');
                        state.letter = L;
                    }
                    state.page = 1;
                    fetchRecords();
                });
            });

            var deleteUrl = panel.getAttribute('data-delete-url');

            function deleteRegistryRow(recordId, documentType) {
                if (!deleteUrl) return;
                var postHeaders = Object.assign({}, jsonHeaders, {
                    'Content-Type': 'application/json',
                });
                fetch(deleteUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: postHeaders,
                        body: JSON.stringify({
                            record_id: parseInt(recordId, 10),
                            document_type: documentType,
                        }),
                    })
                    .then(function(r) {
                        return r.json().then(function(data) {
                            return { ok: r.ok, status: r.status, data: data };
                        });
                    })
                    .then(function(result) {
                        var d = result.data || {};
                        if (result.ok && d.status === 'success') {
                            fetchRecords();
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: d.message || 'Record deleted.',
                                    timer: 1800,
                                    showConfirmButton: false,
                                });
                            }
                            return;
                        }
                        var msg =
                            d.message ||
                            (result.status === 404 ?
                                'Record not found.' :
                                'Could not delete this record.');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Cannot delete', text: msg });
                        } else {
                            window.alert(msg);
                        }
                    })
                    .catch(function() {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Could not reach the server.',
                            });
                        } else {
                            window.alert('Could not reach the server.');
                        }
                    });
            }

            panel.addEventListener('click', function(e) {
                var btn = e.target.closest('.sappc-action-delete');
                if (!btn) return;
                e.preventDefault();
                var rid = btn.getAttribute('data-record-id');
                var dtype = btn.getAttribute('data-document-type');
                if (!rid || !dtype || !deleteUrl) return;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Delete this record?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        cancelButtonText: 'Cancel',
                        focusCancel: true,
                    }).then(function(res) {
                        if (res.isConfirmed) deleteRegistryRow(rid, dtype);
                    });
                } else if (
                    window.confirm('Delete this record? This action cannot be undone.')
                ) {
                    deleteRegistryRow(rid, dtype);
                }
            });
        });

        (function enableMouseDragScroll() {
            var $el = $('.sappc-letter-filter_letters');
            if (!$el.length) return;

            var isDown = false;
            var startX = 0;
            var startScrollLeft = 0;

            $el.on('mousedown', function(e) {
                isDown = true;
                $el.addClass('is-dragging');
                startX = e.pageX - $el.offset().left;
                startScrollLeft = $el.scrollLeft();
            });

            $(window).on('mouseup', function() {
                isDown = false;
                $el.removeClass('is-dragging');
            });

            $el.on('mouseleave', function() {
                isDown = false;
                $el.removeClass('is-dragging');
            });

            $el.on('mousemove', function(e) {
                if (!isDown) return;
                e.preventDefault();
                var x = e.pageX - $el.offset().left;
                var walk = (x - startX) * 1.2;
                $el.scrollLeft(startScrollLeft - walk);
            });
        })();
    })();
</script>
