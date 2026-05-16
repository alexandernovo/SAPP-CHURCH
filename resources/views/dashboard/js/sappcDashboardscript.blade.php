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

        function sappcDashConfirmDelete(firstOpts, onFinalConfirm) {
            var secondOpts = {
                title: 'Are you sure?',
                text: 'Do you really want to delete this document? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete document',
                cancelButtonText: 'Cancel',
                focusCancel: true,
            };
            if (typeof Swal === 'undefined') {
                var firstMsg = (firstOpts && firstOpts.text) || (firstOpts && firstOpts.title) || 'Delete this record?';
                if (!window.confirm(firstMsg)) {
                    return;
                }
                if (window.confirm(secondOpts.text)) {
                    if (typeof onFinalConfirm === 'function') {
                        onFinalConfirm();
                    }
                }
                return;
            }
            Swal.fire(firstOpts).then(function(res) {
                if (!res.isConfirmed) {
                    return;
                }
                Swal.fire(secondOpts).then(function(res2) {
                    if (res2.isConfirmed && typeof onFinalConfirm === 'function') {
                        onFinalConfirm();
                    }
                });
            });
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

            (function initDashboardDocChart() {
                var root = document.getElementById('sappcDocChartRoot');
                var canvas = document.getElementById('sappcDocChart');
                if (!root || !canvas || typeof Chart === 'undefined') {
                    return;
                }
                var monthlyBase = root.getAttribute('data-monthly-url');
                if (!monthlyBase) {
                    return;
                }
                var catSel = document.getElementById('sappcDocChartCategory');
                var yearSel = document.getElementById('sappcDocChartYear');
                if (!catSel || !yearSel) {
                    return;
                }

                var docChart = null;
                var monthFullNames = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December',
                ];

                function emptyTwelve() {
                    var z = [];
                    for (var i = 0; i < 12; i++) {
                        z.push(0);
                    }
                    return z;
                }

                function applyChartData(labels, counts, yearNum) {
                    var maxVal = 0;
                    counts.forEach(function(c) {
                        var n = typeof c === 'number' ? c : parseInt(c, 10);
                        if (!isNaN(n) && n > maxVal) {
                            maxVal = n;
                        }
                    });
                    var suggested = maxVal <= 0 ? 5 : Math.ceil(maxVal * 1.12);

                    if (!docChart) {
                        docChart = new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Requests',
                                    data: counts,
                                    backgroundColor: '#616161',
                                    borderColor: '#424242',
                                    borderWidth: 1,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                    tooltip: {
                                        callbacks: {
                                            title: function(items) {
                                                var ix = items.length && items[0] ? items[0].dataIndex : 0;
                                                var name = monthFullNames[ix] || '';
                                                return name + ' ' + yearNum;
                                            },
                                        },
                                    },
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        suggestedMax: suggested,
                                        ticks: {
                                            precision: 0,
                                        },
                                    },
                                    x: {
                                        ticks: {
                                            maxRotation: 45,
                                            minRotation: 0,
                                            font: {
                                                size: 10,
                                            },
                                        },
                                    },
                                },
                            },
                        });
                        return;
                    }
                    docChart.data.labels = labels;
                    docChart.data.datasets[0].data = counts;
                    if (docChart.options.scales && docChart.options.scales.y) {
                        docChart.options.scales.y.suggestedMax = suggested;
                    }
                    docChart.update();
                }

                function loadChart() {
                    var type = (catSel.value || 'all').toLowerCase();
                    var year = parseInt(yearSel.value, 10);
                    if (isNaN(year)) {
                        year = new Date().getFullYear();
                    }
                    var u = buildQueryUrl(monthlyBase, {
                        type: type,
                        year: year,
                    });
                    fetchJson(u, {
                            Accept: 'application/json',
                        })
                        .then(function(res) {
                            var months = res.months || [];
                            var labels = months.map(function(m) {
                                return m.label != null ? String(m.label) : '';
                            });
                            var counts = months.map(function(m) {
                                var c = m.count;
                                return typeof c === 'number' ? c : parseInt(c, 10) || 0;
                            });
                            if (labels.length !== 12 || counts.length !== 12) {
                                labels = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                                counts = emptyTwelve();
                            }
                            applyChartData(labels, counts, year);
                        })
                        .catch(function() {
                            applyChartData(
                                ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
                                emptyTwelve(),
                                year
                            );
                        });
                }

                catSel.addEventListener('change', loadChart);
                yearSel.addEventListener('change', loadChart);
                loadChart();
            })();

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

            function dashboardModuleUrl(documentType) {
                var map = {
                    Christening: panel.getAttribute('data-url-christening'),
                    Confirmation: panel.getAttribute('data-url-confirmation'),
                    Wedding: panel.getAttribute('data-url-wedding'),
                    Burial: panel.getAttribute('data-url-burial'),
                };
                return (map[documentType] || '').trim();
            }

            function dashboardActionRowContext(btn) {
                var tr = btn.closest('tr');
                if (!tr) {
                    return null;
                }
                var rid = (btn.getAttribute('data-record-id') || '').trim();
                var trid = (tr.getAttribute('data-record-id') || '').trim();
                if (!rid || rid !== trid) {
                    return null;
                }
                var documentType = (tr.getAttribute('data-document-type') || '').trim();
                if (!documentType) {
                    return null;
                }
                return {
                    recordId: rid,
                    documentType: documentType,
                };
            }

            function openDashboardInlineApplication(ctx) {
                var docType = (ctx.documentType || '').trim();
                var id = (ctx.recordId || '').trim();
                if (!docType || !id) {
                    return false;
                }

                if (docType === 'Christening') {
                    var chIdEl = document.getElementById('chScheduleChristeningId');
                    var chBtn = document.getElementById('christeningApplicationFormBtn');
                    if (!chIdEl || !chBtn) return false;
                    chIdEl.value = id;
                    chBtn.click();
                    return true;
                }

                if (docType === 'Confirmation') {
                    var cnIdEl = document.getElementById('cnScheduleConfirmationId');
                    var cnBtn = document.getElementById('confirmationApplicationFormBtn');
                    if (!cnIdEl || !cnBtn) return false;
                    cnIdEl.value = id;
                    cnBtn.click();
                    return true;
                }

                if (docType === 'Wedding') {
                    var wdIdEl = document.getElementById('wdScheduleWeddingId');
                    var wdBtn = document.getElementById('weddingApplicationFormBtn');
                    if (!wdIdEl || !wdBtn) return false;
                    wdIdEl.value = id;
                    wdBtn.click();
                    return true;
                }

                if (docType === 'Burial') {
                    var brIdEl = document.getElementById('brScheduleBurialId');
                    var brBtn = document.getElementById('burialApplicationFormBtn');
                    if (!brIdEl || !brBtn) return false;
                    brIdEl.value = id;
                    brBtn.click();
                    return true;
                }

                return false;
            }

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
                var delBtn = e.target.closest('.sappc-action-delete');
                var editBtn = e.target.closest('.sappc-action-edit');
                if (!delBtn && !editBtn) {
                    return;
                }
                e.preventDefault();
                var btn = delBtn || editBtn;
                var ctx = dashboardActionRowContext(btn);
                if (!ctx) {
                    return;
                }

                if (editBtn) {
                    if (!openDashboardInlineApplication(ctx)) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cannot open',
                                text: 'Application modal is not available for this document type.',
                            });
                        } else {
                            window.alert('Application modal is not available for this document type.');
                        }
                    }
                    return;
                }

                if (!deleteUrl) {
                    return;
                }
                sappcDashConfirmDelete({
                    title: 'Delete this record?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    focusCancel: true,
                }, function() {
                    deleteRegistryRow(ctx.recordId, ctx.documentType);
                });
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
