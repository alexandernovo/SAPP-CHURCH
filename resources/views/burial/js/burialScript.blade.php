@php
    $initialTablePayload = $initialTablePayload ?? null;
    $activeSection = $activeSection ?? 'schedule';
@endphp
<script>
    (function() {
        'use strict';

        var initialTablePayload = @json($initialTablePayload);
        var activeSection = @json($activeSection);

        function getSelectedBurialId() {
            var cid = ($('#brSelectedBurialId').val() || '').trim();
            if (cid) {
                return cid;
            }
            cid = ($('#brScheduleBurialId').val() || '').trim();
            if (cid) {
                return cid;
            }
            var $sel = $('#burialTableBody tr.is-schedule-selected');
            if ($sel.length) {
                return ($sel.first().attr('data-record-id') || '').trim();
            }
            return '';
        }

        function setSelectedBurialId(id) {
            id = id == null ? '' : String(id).trim();
            $('#brSelectedBurialId').val(id);
            if ($('#brScheduleBurialId').length) {
                $('#brScheduleBurialId').val(id);
            }
        }

        function registryWorkflowNextUrl(currentStep) {
            var $panel = $('#burialRecordsPanel');
            if (!$panel.length) {
                return '';
            }
            var hasCert = ($panel.attr('data-workflow-has-certification') || '0') === '1';
            var steps = hasCert
                ? ['application', 'payment', 'certification', 'schedule']
                : ['application', 'payment', 'schedule'];
            var idx = steps.indexOf(currentStep);
            if (idx < 0 || idx >= steps.length - 1) {
                return '';
            }
            return ($panel.attr('data-workflow-' + steps[idx + 1] + '-url') || '').trim();
        }

        function advanceRegistryWorkflow(currentStep, recordId) {
            var url = registryWorkflowNextUrl(currentStep);
            var id = String(recordId == null ? getSelectedBurialId() : recordId).trim();
            if (!url || !id) {
                return false;
            }
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            window.location.href = url + sep + 'sappc_record=' + encodeURIComponent(id);
            return true;
        }

        function tryOpenRecordFromWorkflowQuery() {
            try {
                var u = new URL(window.location.href);
                var id = (u.searchParams.get('sappc_record') || '').trim();
                if (!id) {
                    return;
                }
                u.searchParams.delete('sappc_record');
                var q = u.searchParams.toString();
                window.history.replaceState({}, '', u.pathname + (q ? '?' + q : '') + u.hash);
                setTimeout(function() {
                    if (typeof window.sappcRegistryWorkflowOpenRecord === 'function') {
                        window.sappcRegistryWorkflowOpenRecord(id);
                        return;
                    }
                    setSelectedBurialId(id);
                    $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                    $('#burialTableBody tr').each(function() {
                        if (($(this).attr('data-record-id') || '').trim() === id) {
                            $(this).addClass('is-schedule-selected');
                            return false;
                        }
                    });
                }, 0);
            } catch (e1) {}
        }

        function ensureRegistryApplicationSaved(recordId, thenFn) {
            if (typeof thenFn === 'function') {
                thenFn(true);
            }
        }

        function swalRegistryPaymentRequired(messageText) {
            var msg = messageText || 'Complete payment first. All fees must be marked Paid before you can continue to the next step.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Payment required', text: msg, confirmButtonText: 'OK' });
            } else {
                window.alert(msg);
            }
        }

        function registryWorkflowHasCertification() {
            return ($('#burialRecordsPanel').attr('data-workflow-has-certification') || '0') === '1';
        }

        function workflowChecksForStep(targetStep) {
            var checks = [];
            if (targetStep === 'certification' || targetStep === 'schedule') {
                checks.push('payment');
            }
            if (registryWorkflowHasCertification() && targetStep === 'schedule') {
                checks.push('certification');
            }
            return checks;
        }

        function ensureRegistryPaymentComplete(recordId, thenFn) {
            recordId = String(recordId == null ? '' : recordId).trim();
            if (!recordId) {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            var payUrl = ($('#burialRecordsPanel').attr('data-payment-details-url') || '').trim();
            if (!payUrl) {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            fetchJson(buildQueryUrl(payUrl, { burial_id: recordId }), {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }).done(function(res) {
                var paid = !!(res && res.ok && (res.payment_complete === true ||
                    (res.data && String(res.data.payment_status || '').toLowerCase() === 'paid')));
                if (paid) {
                    if (typeof thenFn === 'function') thenFn(true);
                    return;
                }
                swalRegistryPaymentRequired(res && res.message ? String(res.message) : '');
                if (typeof thenFn === 'function') thenFn(false);
            }).fail(function(xhr) {
                var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                swalRegistryPaymentRequired(data && data.message ? String(data.message) : '');
                if (typeof thenFn === 'function') thenFn(false);
            });
        }

        function runRegistryWorkflowChecks(checks, index, recordId, thenFn) {
            if (index >= checks.length) {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            var check = checks[index];
            if (check === 'application') {
                ensureRegistryApplicationSaved(recordId, function(ok) {
                    if (!ok) {
                        if (typeof thenFn === 'function') thenFn(false);
                        return;
                    }
                    runRegistryWorkflowChecks(checks, index + 1, recordId, thenFn);
                });
                return;
            }
            if (check === 'payment') {
                ensureRegistryPaymentComplete(recordId, function(ok) {
                    if (!ok) {
                        if (typeof thenFn === 'function') thenFn(false);
                        return;
                    }
                    runRegistryWorkflowChecks(checks, index + 1, recordId, thenFn);
                });
                return;
            }
            runRegistryWorkflowChecks(checks, index + 1, recordId, thenFn);
        }

        function ensureRegistryWorkflowStep(targetStep, recordId, thenFn) {
            recordId = String(recordId == null ? '' : recordId).trim();
            if (!recordId || targetStep === 'application') {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            runRegistryWorkflowChecks(workflowChecksForStep(targetStep), 0, recordId, thenFn);
        }

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function getMetaCsrf() {
            return $('meta[name="csrf-token"]').attr('content') || '';
        }

        function buildQueryUrl(base, params) {
            var q = {};
            Object.keys(params).forEach(function(k) {
                var v = params[k];
                if (v !== undefined && v !== null && String(v) !== '') {
                    q[k] = v;
                }
            });
            var sep = base.indexOf('?') >= 0 ? '&' : '?';
            return base + sep + $.param(q);
        }

        function fetchPostJson(url, bodyObj, csrfToken) {
            return $.ajax({
                url: url,
                method: 'POST',
                data: JSON.stringify(bodyObj),
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
        }

        function sappcBrSwal(cfg) {
            if (typeof Swal !== 'undefined') {
                return Swal.fire(cfg);
            }
            var msg = '';
            if (cfg && cfg.text != null && String(cfg.text) !== '') {
                msg = String(cfg.text);
            } else if (cfg && cfg.title != null && String(cfg.title) !== '') {
                msg = String(cfg.title);
            }
            window.alert(msg);
            return Promise.resolve({
                isConfirmed: true,
            });
        }

        function sappcBrConfirm(cfg) {
            cfg = cfg || {};
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    icon: cfg.icon || 'warning',
                    title: cfg.title || '',
                    text: cfg.text || '',
                    showCancelButton: true,
                    confirmButtonColor: cfg.confirmButtonColor || '#950d16',
                    cancelButtonColor: cfg.cancelButtonColor || '#6c757d',
                    confirmButtonText: cfg.confirmButtonText || 'OK',
                    cancelButtonText: cfg.cancelButtonText || 'Cancel',
                });
            }
            var ok = window.confirm(String(cfg.text || cfg.title || ''));
            return Promise.resolve({
                isConfirmed: ok,
            });
        }

        function sappcBrConfirmDeleteDocument(firstCfg, onFinalConfirm) {
            sappcBrConfirm(firstCfg).then(function(r) {
                if (!r.isConfirmed) {
                    return;
                }
                sappcBrConfirm({
                    title: 'Are you sure?',
                    text: 'Do you really want to delete this document? This action cannot be undone.',
                    confirmButtonText: 'Yes, delete document',
                }).then(function(r2) {
                    if (r2.isConfirmed && typeof onFinalConfirm === 'function') {
                        onFinalConfirm();
                    }
                });
            });
        }

        function fetchJson(url, headers) {
            return $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: headers || {},
            });
        }

        function paymentStatusCell(raw) {
            var s = String(raw == null ? '' : raw).trim();
            var lower = s.toLowerCase();
            if (!s || s === '-') {
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

        function sappcSwalSelectBurialRowFirst() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a record',
                    text: 'Select a burial row in the table first.',
                    confirmButtonText: 'OK',
                });
            } else {
                window.alert('Select a burial row in the table first.');
            }
        }

        function sappcPhMobileDigitsOnly(value) {
            return String(value == null ? '' : value).replace(/\D/g, '');
        }

        function formatPhMobileDisplay(value) {
            var d = sappcPhMobileDigitsOnly(value);
            if (!d) return '';
            if (d.slice(0, 2) === '63') {
                d = '0' + d.slice(2);
            } else if (d.charAt(0) === '9' && d.length <= 10) {
                d = '0' + d;
            }
            if (d.length > 11) {
                d = d.slice(0, 11);
            }
            if (d.length >= 2 && d.charAt(0) === '0' && d.charAt(1) === '9') {
                var a = d.slice(0, 4);
                var b = d.slice(4, 7);
                var c = d.slice(7, 11);
                if (!b) return a;
                if (!c) return a + ' ' + b;
                return a + ' ' + b + ' ' + c;
            }
            if (d.charAt(0) === '0') {
                return d;
            }
            return d.slice(0, 15);
        }

        $(document).on('input', '#brScheduleContact, #brPaymentContact', function() {
            var $el = $(this);
            var before = $el.val();
            var formatted = formatPhMobileDisplay(before);
            if (formatted !== before) {
                $el.val(formatted);
            }
        });

        function rowActionCell(recordId) {
            var viewLabel = activeSection === 'certification' ? 'View certificate' : 'View record';
            return '<td class="text-center"><div class="sappc-icon-action_group">' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="' + viewLabel + '" aria-label="' + viewLabel + '" data-record-id="' + esc(recordId) +
                '"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--edit" title="Edit" aria-label="Edit record" data-record-id="' + esc(recordId) +
                '"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>' +
                '<button type="button" class="sappc-icon-action sappc-icon-action--delete" title="Delete" aria-label="Delete record" data-record-id="' + esc(recordId) +
                '"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div></td>';
        }

        function rowHtml(row) {
            var base = '<tr data-record-id="' + esc(row.recordId) + '" data-document-type="' + esc(row.documentType) + '">' +
                '<td>' + esc(row.rowNumber) + '</td><td>' + esc(row.referenceCode) + '</td><td>' + esc(row.client) + '</td><td>' + esc(typeof sappcFormatAddress === 'function' ? sappcFormatAddress(row.address) : row.address) + '</td>';
            if (activeSection === 'certification') {
                return base + '<td>' + esc(row.contactNum) + '</td><td>' + esc(row.dateCreated) + '</td>' + rowActionCell(row.recordId) + '</tr>';
            }
            if (activeSection === 'application') {
                return base + '<td>' + esc(row.sex) + '</td><td>' + esc(row.contactNum) + '</td><td>' + esc(row.dateCreated) + '</td>' + rowActionCell(row.recordId) + '</tr>';
            }
            if (activeSection === 'payment') {
                return base + '<td>' + esc(row.contactNum) + '</td><td class="text-center align-middle">' + paymentStatusCell(row.paymentStatus) + '</td><td>' + esc(row.dateCreated) + '</td>' + rowActionCell(row.recordId) + '</tr>';
            }
            return base + '<td>' + esc(row.sex) + '</td><td>' + esc(row.contactNum) + '</td><td class="text-center align-middle">' + paymentStatusCell(row.paymentStatus) + '</td><td>' + esc(row.dateCreated) + '</td>' + rowActionCell(row.recordId) + '</tr>';
        }

        $(function() {
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

            (function applyBurialFieldFormatGuides() {
                function ph(sel, val) {
                    var $el = $(sel);
                    if ($el.length) {
                        $el.attr('placeholder', val);
                    }
                }
                ph('#brCertChildFirst', 'Juan');
                ph('#brCertChildMiddle', 'D.');
                ph('#brCertChildLast', 'Cruz');
                ph('#brCertBirthplace', 'Barbaza, Antique');
                ph('#brCertFatherFirst', 'Juan');
                ph('#brCertFatherMiddle', 'D.');
                ph('#brCertFatherLast', 'Cruz');
                ph('#brCertMotherFirst', 'Maria');
                ph('#brCertMotherMiddle', 'D.');
                ph('#brCertMotherLast', 'Cruz');
                ph('#brCertPriest', 'Rev. name');
                ph('#brCertSponsors', 'Juan D. Cruz; Maria D. Cruz');
                ph('#brCertPurpose', 'e.g. funeral service, estate');
                ph('#brAppDeceasedName', 'Cruz, Juan D.');
                ph('#brAppSpouseName', 'Cruz, Juan D.');
                ph('#brAppClaimantName', 'Cruz, Juan D.');
                ph('#brAppMinorFather', 'Cruz, Juan D.');
                ph('#brAppMinorMother', 'Cruz, Juan D.');
            })();

            var $panel = $('#burialRecordsPanel');
            if (!$panel.length) return;

            var tableColspan = parseInt($panel.attr('data-table-colspan'), 10);
            if (isNaN(tableColspan) || tableColspan < 1) {
                tableColspan = 9;
            }

            var url = $panel.attr('data-records-url');
            if (!url) return;

            function tryOpenBurialApplicationFromDashboardQuery() {
                try {
                    var u = new URL(window.location.href);
                    var id = (u.searchParams.get('sappc_dash_app') || '').trim();
                    if (!id) {
                        return;
                    }
                    u.searchParams.delete('sappc_dash_app');
                    var q = u.searchParams.toString();
                    window.history.replaceState({}, '', u.pathname + (q ? '?' + q : '') + u.hash);
                    setSelectedBurialId(id);
                    $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                    $('#burialTableBody tr').each(function() {
                        if (($(this).attr('data-record-id') || '').trim() === id) {
                            $(this).addClass('is-schedule-selected');
                            return false;
                        }
                    });
                    setTimeout(function() {
                        $('#burialApplicationFormBtn').trigger('click');
                    }, 0);
                } catch (e1) {}
            }

            function isDashboardEmbeddedAppContext() {
                try {
                    var u = new URL(window.location.href);
                    return (u.searchParams.get('embed') || '').trim() === '1';
                } catch (e1) {
                    return false;
                }
            }

            tryOpenBurialApplicationFromDashboardQuery();

            var csrf = getMetaCsrf();
            var burialDeleteUrl = ($panel.attr('data-burial-delete-url') || '').trim();
            var jsonHeaders = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };

            var burialAppDetailsUrl = ($panel.attr('data-burial-application-details-url') || '').trim();
            var burialAppSaveUrl = ($panel.attr('data-burial-application-save-url') || '').trim();
            var certificationSaveUrl = ($panel.attr('data-certification-save-url') || '').trim();
            var certificationDetailsUrl = ($panel.attr('data-certification-details-url') || '').trim();
            var scheduleDetailsUrl = ($panel.attr('data-schedule-details-url') || '').trim();
            var meta0 = (initialTablePayload && initialTablePayload.meta) ? initialTablePayload.meta : {};
            var state = {
                page: meta0.current_page || 1,
                per_page: meta0.per_page || 10,
                search: '',
                letter: '',
                date_from: '',
                date_to: '',
            };

            var $searchInput = $('#burialSearch');
            var $body = $('#burialTableBody');
            var $info = $('#burialTableFooterInfo');
            var $nav = $('#burialPagination');

            function renderTable(res) {
                var html = '';
                if (!res || !res.data || !res.data.length) {
                    html =
                        '<tr class="sappc-table-empty"><td colspan="' + tableColspan + '" class="text-center text-muted py-4">No records found.</td></tr>';
                } else {
                    res.data.forEach(function(row) {
                        html += rowHtml(row);
                    });
                }
                $body.html(html);

                var m = res && res.meta ? res.meta : {};
                if (!m.total) {
                    $info.text('Showing 0 entries');
                } else {
                    $info.text('Showing ' + m.from + ' to ' + m.to + ' of ' + m.total + ' entries');
                }

                $nav.empty();
                var last = Math.max(1, m.last_page || 1);
                var cur = m.current_page || 1;

                function appendBtn(h) {
                    $nav.append(h);
                }

                appendBtn(
                    '<button type="button" class="sappc-pagination_btn" data-page="' + (cur - 1) +
                    '" ' + (cur <= 1 ? 'disabled' : '') + ' aria-label="Previous">&lt;</button>'
                );
                for (var p = 1; p <= last; p++) {
                    var active = p === cur ? ' is-active' : '';
                    var aria = p === cur ? ' aria-current="page"' : '';
                    appendBtn('<button type="button" class="sappc-pagination_btn' + active +
                        '" data-page="' + p + '"' + aria + '>' + p + '</button>');
                }
                appendBtn(
                    '<button type="button" class="sappc-pagination_btn" data-page="' + (cur + 1) +
                    '" ' + (cur >= last ? 'disabled' : '') + ' aria-label="Next">&gt;</button>'
                );
            }

            function fetchRecords() {
                $body.html(
                    '<tr class="sappc-table-loading"><td colspan="' + tableColspan + '" class="text-center text-muted py-4">Loading...</td></tr>'
                );
                var reqUrl = buildQueryUrl(url, {
                    page: state.page,
                    per_page: state.per_page,
                    search: state.search,
                    letter: state.letter,
                    date_from: state.date_from,
                    date_to: state.date_to,
                    registry_type: 'burial',
                    sort_order: ($panel.attr('data-sort-order') || 'desc').trim(),
                });

                $.ajax({
                    url: reqUrl,
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .done(function(res) {
                        renderTable(res);
                        tryOpenRecordFromWorkflowQuery();
                        tryOpenBurialApplicationFromDashboardQuery();
                    })
                    .fail(function(xhr, textStatus, errorThrown) {
                        var msg =
                            (xhr && xhr.status) ||
                            errorThrown ||
                            textStatus ||
                            '?';
                        $body.html(
                            '<tr><td colspan="' + tableColspan + '" class="text-center text-danger py-3">Could not load records (' +
                            esc(String(msg)) +
                            ').</td></tr>'
                        );
                    });
            }

            var searchDebounceTimer;
            $searchInput.on('input', function() {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    state.search = ($searchInput.val() || '').trim();
                    state.page = 1;
                    fetchRecords();
                }, 400);
            });

            $searchInput.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    state.search = ($searchInput.val() || '').trim();
                    state.page = 1;
                    fetchRecords();
                }
            });

            $('#burialEntries').on('change', function() {
                state.per_page = parseInt($(this).val(), 10) || 10;
                state.page = 1;
                fetchRecords();
            });

            $panel.find('.sappc-toolbar-date-strip_btn').on('click', function() {
                state.date_from = $('#burialDateFrom').val() || '';
                state.date_to = $('#burialDateTo').val() || '';
                state.page = 1;
                fetchRecords();
            });

            $panel.find('.sappc-letter-filter_btn').on('click', function() {
                var $btn = $(this);
                var letter = $btn.attr('data-letter');
                if ($btn.hasClass('is-active')) {
                    $btn.removeClass('is-active');
                    state.letter = '';
                } else {
                    $panel.find('.sappc-letter-filter_btn').removeClass('is-active');
                    $btn.addClass('is-active');
                    state.letter = letter;
                }
                state.page = 1;
                fetchRecords();
            });

            $nav.on('click', function(e) {
                var $btn = $(e.target).closest('.sappc-pagination_btn:not(:disabled)');
                if (!$btn.length) return;
                var p = parseInt($btn.attr('data-page'), 10);
                if (!isNaN(p) && p >= 1) {
                    state.page = p;
                    fetchRecords();
                }
            });

            var $reloadBtn = $('#burialReloadBtn');
            $panel.closest('.sappc-registry-page').find('.sappc-registry-toolbar a.sappc-registry-toolbar_btn[data-workflow-step]').on('click', function(e) {
                var step = ($(this).attr('data-workflow-step') || '').trim();
                var cid = getSelectedBurialId();
                if (!cid || !step || step === 'application') {
                    return;
                }
                e.preventDefault();
                var href = $(this).attr('href');
                ensureRegistryWorkflowStep(step, cid, function(ok) {
                    if (ok && href) {
                        window.location.href = href;
                    }
                });
            });
            if ($reloadBtn.length) {
                $reloadBtn.on('click', fetchRecords);
            }

            if (initialTablePayload) {
                renderTable(initialTablePayload);
                tryOpenRecordFromWorkflowQuery();
                tryOpenBurialApplicationFromDashboardQuery();
            } else {
                fetchRecords();
            }

            var paymentDetailsUrl = ($panel.attr('data-payment-details-url') || '').trim();
            var paymentSaveUrlPanel = ($panel.attr('data-payment-save-url') || '').trim();

            var $paymentModal = $('#burialPaymentFeeModal');
            var $paymentBtn = $('#burialPaymentFeeBtn');
            var $paymentFeeForm = $('#burialPaymentFeeForm');
            var $feeItemsBody = $('#burialPaymentFeeItemsBody');
            var $addFeeBtn = $('#burialPaymentFeeAddItemBtn');

            function renumberConfirmationFeeRows() {
                $feeItemsBody.find('[data-fee-row]').each(function(i) {
                    $(this).find('.sappcPaymentFeeModalCellNo').text(i + 1);
                    $(this).find('.sappcPaymentFeeModalItemInput').attr('aria-label', 'Fee item ' + (i + 1));
                });
            }

            function newConfirmationFeeRowHtml() {
                return '' +
                    '<tr class="sappcPaymentFeeModalRow" data-fee-row>' +
                    '<td class="sappcPaymentFeeModalCellNo"></td>' +
                    '<td><input type="text" class="sappcPaymentFeeModalItemInput" name="fee_items[]" value="" aria-label="Fee item"></td>' +
                    '<td><span class="sappcPaymentFeeModalStatus sappcPaymentFeeModalStatusUnpaid">Unpaid</span></td>' +
                    '<td><span class="sappcPaymentFeeModalDatePaid" data-date-paid="">\u2014</span></td>' +
                    '<td class="text-center"><div class="sappcPaymentFeeModalActions">' +
                    '<button type="button" class="sappcPaymentFeeModalTogglePaid">Paid</button>' +
                    '<button type="button" class="sappcPaymentFeeModalBtnRemove" aria-label="Remove row">' +
                    '<i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>' +
                    '</div></td></tr>';
            }

            function formatPaymentFeeDateDisplay(isoYmd) {
                if (!isoYmd || String(isoYmd).length < 8) return '\u2014';
                try {
                    var d = new Date(String(isoYmd).slice(0, 10) + 'T12:00:00');
                    if (isNaN(d.getTime())) return String(isoYmd);
                    return d.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    });
                } catch (e) {
                    return String(isoYmd);
                }
            }

            function collectConfirmationPaymentFeeRowsFromDom() {
                var rows = [];
                $feeItemsBody.find('[data-fee-row]').each(function() {
                    var $row = $(this);
                    var label = ($row.find('.sappcPaymentFeeModalItemInput').val() || '').trim();
                    var paid = $row.find('.sappcPaymentFeeModalStatus').hasClass('sappcPaymentFeeModalStatusPaid');
                    var $date = $row.find('.sappcPaymentFeeModalDatePaid');
                    var datePaid = ($date.attr('data-date-paid') || '').trim();
                    if (!paid) {
                        datePaid = '';
                    }
                    rows.push({
                        label: label,
                        paid: paid,
                        date_paid: datePaid || null,
                    });
                });
                return rows;
            }

            function buildConfirmationPaymentFeeRowFromData(row) {
                var label = (row && row.label != null) ? String(row.label) : '';
                var paid = !!(row && row.paid);
                var dateIso = (row && row.date_paid) ? String(row.date_paid).slice(0, 10) : '';
                var $tr = $(newConfirmationFeeRowHtml());
                $tr.find('.sappcPaymentFeeModalItemInput').val(label);
                var $status = $tr.find('.sappcPaymentFeeModalStatus');
                var $date = $tr.find('.sappcPaymentFeeModalDatePaid');
                var $toggle = $tr.find('.sappcPaymentFeeModalTogglePaid, .sappcPaymentFeeModalToggleUnpaid');
                if (paid) {
                    $status.removeClass('sappcPaymentFeeModalStatusUnpaid').addClass('sappcPaymentFeeModalStatusPaid').text('Paid');
                    $toggle.removeClass('sappcPaymentFeeModalTogglePaid').addClass('sappcPaymentFeeModalToggleUnpaid').text('Unpaid');
                    if (dateIso) {
                        $date.attr('data-date-paid', dateIso);
                        $date.text(formatPaymentFeeDateDisplay(dateIso));
                    } else {
                        var today = new Date().toISOString().slice(0, 10);
                        $date.attr('data-date-paid', today);
                        $date.text(formatPaymentFeeDateDisplay(today));
                    }
                } else {
                    $status.removeClass('sappcPaymentFeeModalStatusPaid').addClass('sappcPaymentFeeModalStatusUnpaid').text('Unpaid');
                    $toggle.removeClass('sappcPaymentFeeModalToggleUnpaid').addClass('sappcPaymentFeeModalTogglePaid').text('Paid');
                    $date.removeAttr('data-date-paid');
                    $date.text('\u2014');
                }
                return $tr;
            }

            function serializeConfirmationPaymentFeeToObject() {
                return {
                    reference_code: ($('#brPaymentRefCode').val() || '').trim(),
                    client: ($('#brPaymentClient').val() || '').trim(),
                    contact_number: sappcPhMobileDigitsOnly($('#brPaymentContact').val()),
                    address: ($('#brPaymentAddress').val() || '').trim(),
                    fee_rows: collectConfirmationPaymentFeeRowsFromDom(),
                };
            }

            function applyConfirmationPaymentFeeFormObject(data) {
                if (!data || typeof data !== 'object') return;
                $('#brPaymentRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
                $('#brPaymentClient').val(data.client != null ? String(data.client) : '');
                $('#brPaymentContact').val(
                    data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
                );
                $('#brPaymentAddress').val(data.address != null ? String(data.address) : '');
                var feeRows = data.fee_rows;
                if (!Array.isArray(feeRows) || !feeRows.length) {
                    feeRows = [{}];
                }
                $feeItemsBody.empty();
                feeRows.forEach(function(fr) {
                    $feeItemsBody.append(buildConfirmationPaymentFeeRowFromData(fr));
                });
                renumberConfirmationFeeRows();
            }

            function resetBurialPaymentFormForNewEntry() {
                setSelectedBurialId('');
                $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                applyConfirmationPaymentFeeFormObject({
                    reference_code: ($paymentFeeForm.attr('data-default-reference-code') || '').trim(),
                    client: '',
                    contact_number: '',
                    address: '',
                    fee_rows: null,
                });
            }

            $addFeeBtn.on('click', function() {
                var $tr = $(newConfirmationFeeRowHtml());
                $feeItemsBody.append($tr);
                renumberConfirmationFeeRows();
                $tr.find('.sappcPaymentFeeModalItemInput').trigger('focus');
            });

            $feeItemsBody.on('click', '.sappcPaymentFeeModalBtnRemove', function() {
                if ($feeItemsBody.find('[data-fee-row]').length > 1) {
                    $(this).closest('[data-fee-row]').remove();
                    renumberConfirmationFeeRows();
                }
            });

            $feeItemsBody.on('click', '.sappcPaymentFeeModalTogglePaid, .sappcPaymentFeeModalToggleUnpaid', function() {
                var $btn = $(this);
                var $row = $btn.closest('[data-fee-row]');
                var $status = $row.find('.sappcPaymentFeeModalStatus');
                var $date = $row.find('.sappcPaymentFeeModalDatePaid');
                var isPaid = $status.hasClass('sappcPaymentFeeModalStatusPaid');
                if (isPaid) {
                    $status.removeClass('sappcPaymentFeeModalStatusPaid').addClass('sappcPaymentFeeModalStatusUnpaid').text('Unpaid');
                    $btn.removeClass('sappcPaymentFeeModalToggleUnpaid').addClass('sappcPaymentFeeModalTogglePaid').text('Paid');
                    $date.removeAttr('data-date-paid');
                    $date.text('\u2014');
                } else {
                    var iso = new Date().toISOString().slice(0, 10);
                    $status.addClass('sappcPaymentFeeModalStatusPaid').removeClass('sappcPaymentFeeModalStatusUnpaid').text('Paid');
                    $btn.removeClass('sappcPaymentFeeModalTogglePaid').addClass('sappcPaymentFeeModalToggleUnpaid').text('Unpaid');
                    $date.attr('data-date-paid', iso);
                    $date.text(formatPaymentFeeDateDisplay(iso));
                }
            });

            if ($paymentModal.length && $paymentBtn.length && typeof bootstrap !== 'undefined') {
                var paymentBsModal = bootstrap.Modal.getOrCreateInstance($paymentModal[0]);

                $paymentModal.on('shown.bs.modal', function() {
                    $paymentBtn.attr('aria-expanded', 'true');
                });
                $paymentModal.on('hidden.bs.modal', function() {
                    $paymentBtn.attr('aria-expanded', 'false');
                });

                $paymentBtn.on('click', function(e) {
                    e.preventDefault();
                    var cid = getSelectedBurialId();
                    if (!cid) {
                        resetBurialPaymentFormForNewEntry();
                        paymentBsModal.show();
                        return;
                    }
                    if (!paymentDetailsUrl) {
                        window.alert('Payment load is not configured.');
                        return;
                    }
                    ensureRegistryWorkflowStep('payment', cid, function(ok) {
                        if (!ok) {
                            return;
                        }
                    fetchJson(buildQueryUrl(paymentDetailsUrl, {
                        burial_id: cid
                    }), jsonHeaders)
                        .done(function(res) {
                            if (res && res.ok && res.data) {
                                applyConfirmationPaymentFeeFormObject(res.data);
                                paymentBsModal.show();
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Could not load payment details.';
                            var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (data && data.message) msg = data.message;
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg
                                });
                            } else {
                                window.alert(msg);
                            }
                        });
                    });
                });

                $paymentFeeForm.on('submit', function(e) {
                    e.preventDefault();
                    var saveUrl = ($paymentFeeForm.attr('data-save-url') || paymentSaveUrlPanel || '').trim();
                    if (!saveUrl) return;
                    var cid = getSelectedBurialId();
                    var payload = serializeConfirmationPaymentFeeToObject();
                    if (cid) {
                        payload.burial_id = parseInt(cid, 10);
                        if (isNaN(payload.burial_id)) {
                            window.alert('Invalid record.');
                            return;
                        }
                    }
                    var $saveBtn = $('#burialPaymentFeeSaveBtn');
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(saveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                if (res.data && res.data.burial_id) {
                                    setSelectedBurialId(String(res.data.burial_id));
                                }
                                if (typeof bootstrap !== 'undefined' && $paymentModal.length) {
                                    var inst = bootstrap.Modal.getInstance($paymentModal[0]);
                                    if (inst) inst.hide();
                                }
                                var msg = (res && res.message) ? res.message : 'Payment record saved.';
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Saved',
                                        text: msg,
                                        confirmButtonText: 'OK',
                                    });
                                } else {
                                    window.alert(msg);
                                }
                                fetchRecords();
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Payment could not be saved.';
                            var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (data && data.errors) {
                                var vals = Object.values(data.errors);
                                if (vals.length && Array.isArray(vals[0]) && vals[0][0]) msg = vals[0][0];
                            } else if (data && data.message) {
                                msg = data.message;
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg
                                });
                            } else {
                                window.alert(msg);
                            }
                        })
                        .always(function() {
                            $saveBtn.prop('disabled', false);
                        });
                });
            }

            (function initBurialCertificationModal() {
                try {
                var $certModal = $('#burialCertificationModal');
                var $certBtn = $('#burialCertificationBtn');
                var $certForm = $('#burialCertificationForm');
                if (!$certModal.length || !$certBtn.length || !$certForm.length || typeof bootstrap === 'undefined') {
                    return;
                }

                var certBsModal = bootstrap.Modal.getOrCreateInstance($certModal[0]);
                var parishLogoUrl = @json(asset('assets/logos/SAPPC.png'));
                var burialCertBgUrl = @json(asset('assets/certificates/burialCert.jpg'));

                function applyBurialCertificationTopFromPayment(data) {
                    if (!data || typeof data !== 'object') return;
                    $('#brCertRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
                    $('#brCertClient').val(data.client != null ? String(data.client) : '');
                    $('#brCertContact').val(
                        data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
                    );
                    $('#brCertTopAddress').val(data.address != null ? String(data.address) : '');
                }

                function applyBurialCertificationFromDetails(data) {
                    if (!data || typeof data !== 'object') return;
                    $('#brCertChildFirst').val(data.first_name != null ? String(data.first_name) : '');
                    $('#brCertChildMiddle').val(data.middle_name != null ? String(data.middle_name) : '');
                    $('#brCertChildLast').val(data.family_name != null ? String(data.family_name) : '');
                    $('#brCertBirthday').val(data.date_of_birth != null ? String(data.date_of_birth) : '');
                    $('#brCertBirthplace').val(data.place_of_birth != null ? String(data.place_of_birth) : '');
                    $('#brCertFatherFirst').val(data.father_first_name != null ? String(data.father_first_name) : '');
                    $('#brCertFatherMiddle').val(data.father_middle_name != null ? String(data.father_middle_name) : '');
                    $('#brCertFatherLast').val(data.father_last_name != null ? String(data.father_last_name) : '');
                    $('#brCertMotherFirst').val(data.mother_first_name != null ? String(data.mother_first_name) : '');
                    $('#brCertMotherMiddle').val(data.mother_middle_name != null ? String(data.mother_middle_name) : '');
                    $('#brCertMotherLast').val(data.mother_last_name != null ? String(data.mother_last_name) : '');
                    $('#brCertBarangay').val(data.barangay != null ? String(data.barangay) : '');
                    $('#brCertMunicipality').val(data.municipality != null ? String(data.municipality) : '');
                    $('#brCertProvince').val(data.province != null ? String(data.province) : 'Antique');
                    $('#brCertDateReceived').val(data.date_received != null ? String(data.date_received) : '');
                    $('#brCertDateIssued').val(data.date_issued != null ? String(data.date_issued) : '');
                    $('#brCertBookNo').val(data.book_no != null ? String(data.book_no) : '');
                    $('#brCertRegisterNo').val(data.register_no != null ? String(data.register_no) : '');
                    $('#brCertPageNo').val(data.page_no != null ? String(data.page_no) : '');
                    $('#brCertPriest').val(data.priest != null ? String(data.priest) : '');
                    $('#brCertSponsors').val(data.sponsors != null ? String(data.sponsors) : '');
                    $('#brCertPurpose').val(data.purpose != null ? String(data.purpose) : '');
                }

                $certModal.on('shown.bs.modal', function() {
                    $certBtn.attr('aria-expanded', 'true');
                });
                $certModal.on('hidden.bs.modal', function() {
                    $certBtn.attr('aria-expanded', 'false');
                });

                $certBtn.on('click', function(e) {
                    e.preventDefault();
                    var bid = getSelectedBurialId();
                    if (!bid) {
                        certBsModal.show();
                        return;
                    }
                    loadBurialCertificationForRecord(bid, function() {
                        certBsModal.show();
                    }, function(msg) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg
                            });
                        } else {
                            window.alert(msg);
                        }
                    });
                });

                function certFieldValue(sel) {
                    return ($(sel).val() || '').toString().trim();
                }

                function formatPrintDate(iso) {
                    if (!iso) return '';
                    try {
                        var d = new Date(String(iso).slice(0, 10) + 'T12:00:00');
                        if (isNaN(d.getTime())) return iso;
                        return d.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        });
                    } catch (e) {
                        return iso;
                    }
                }

                function certLineText() {
                    var fullName = [certFieldValue('#brCertChildFirst'), certFieldValue('#brCertChildMiddle'), certFieldValue('#brCertChildLast')].join(' ').replace(/\s+/g, ' ').trim();
                    var father = [certFieldValue('#brCertFatherFirst'), certFieldValue('#brCertFatherMiddle'), certFieldValue('#brCertFatherLast')].join(' ').replace(/\s+/g, ' ').trim();
                    var mother = [certFieldValue('#brCertMotherFirst'), certFieldValue('#brCertMotherMiddle'), certFieldValue('#brCertMotherLast')].join(' ').replace(/\s+/g, ' ').trim();
                    var address = [certFieldValue('#brCertBarangay'), certFieldValue('#brCertMunicipality'), certFieldValue('#brCertProvince')].filter(function(v) {
                        return v !== '';
                    }).join(', ');
                    return {
                        full_name: fullName,
                        birth_date: formatPrintDate(certFieldValue('#brCertBirthday')),
                        birth_place: certFieldValue('#brCertBirthplace'),
                        father: father,
                        mother: mother,
                        address: address,
                        priest: certFieldValue('#brCertPriest'),
                        sponsors: certFieldValue('#brCertSponsors'),
                        purpose: certFieldValue('#brCertPurpose'),
                        book_no: certFieldValue('#brCertBookNo'),
                        register_no: certFieldValue('#brCertRegisterNo'),
                        page_no: certFieldValue('#brCertPageNo'),
                        date_received: formatPrintDate(certFieldValue('#brCertDateReceived')),
                        date_issued: formatPrintDate(certFieldValue('#brCertDateIssued')),
                    };
                }

                function collectBurialCertificatePrintData() {
                    var d = certLineText();
                    var rawBirth = certFieldValue('#brCertBirthday');
                    var birthDay = '';
                    var birthMonthYear = '';
                    if (rawBirth && rawBirth.length >= 10) {
                        var p = rawBirth.split('-');
                        if (p.length === 3) {
                            birthDay = p[2];
                            var mIdx = parseInt(p[1], 10) - 1;
                            var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            var mName = (mIdx >= 0 && mIdx < 12) ? months[mIdx] : p[1];
                            birthMonthYear = mName + ' ' + p[0];
                        }
                    }
                    return {
                        full_name: d.full_name || '',
                        birth_day: birthDay,
                        birth_month_year: birthMonthYear,
                        place_of_birth: d.birth_place || '',
                        father_name: d.father || '',
                        mother_name: d.mother || '',
                        address: d.address || '',
                        baptism_date: d.date_received || '',
                        priest_name: d.priest || '',
                        sponsors: d.sponsors || '',
                        purpose: d.purpose || '',
                        book_no: d.book_no || '',
                        page_no: d.page_no || '',
                        register_no: d.register_no || '',
                        date_issued: d.date_issued || '',
                    };
                }

                function populateBurialCertificateClone(root, printData) {
                    var sheet = root.querySelector('.bc-sheet');
                    if (sheet) {
                        sheet.style.backgroundImage = 'url(' + burialCertBgUrl + ')';
                        sheet.style.backgroundSize = 'cover';
                        sheet.style.backgroundPosition = 'center';
                        sheet.style.backgroundRepeat = 'no-repeat';
                    }

                    function setVal(id, v) {
                        var el = root.querySelector('#' + id);
                        if (!el) {
                            return;
                        }
                        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                            el.value = v || '';
                        } else {
                            el.textContent = v || '';
                        }
                    }

                    setVal('bcFullName', printData.full_name);
                    setVal('bcBirthDay', printData.birth_day);
                    setVal('bcBirthMonthYear', printData.birth_month_year);
                    setVal('bcBirthplace', printData.place_of_birth);
                    setVal('bcFatherName', printData.father_name);
                    setVal('bcMotherName', printData.mother_name);
                    setVal('bcAddress', printData.address);
                    setVal('bcBaptismDate', printData.baptism_date);
                    setVal('bcPriestName', printData.priest_name);
                    setVal('bcSponsors', printData.sponsors);
                    setVal('bcPurpose', printData.purpose);
                    setVal('bcBookNo', printData.book_no);
                    setVal('bcPageNo', printData.page_no);
                    setVal('bcRegisterNo', printData.register_no);
                    setVal('bcDateIssued', printData.date_issued);
                }

                function mountBurialCertificatePreview(mountEl, printData) {
                    var tplNode = document.getElementById('burialCertificatePrintableTemplate');
                    if (!tplNode || !tplNode.content || !mountEl) {
                        window.alert('Certificate preview template not found.');
                        return false;
                    }
                    var tplStyleNode = tplNode.content.querySelector('style');
                    var tplSheetNode = tplNode.content.querySelector('.bc-sheet');
                    if (!tplStyleNode || !tplSheetNode) {
                        window.alert('Certificate preview template is incomplete.');
                        return false;
                    }

                    mountEl.innerHTML = '';

                    var styleEl = document.createElement('style');
                    styleEl.textContent = tplStyleNode.textContent || '';
                    mountEl.appendChild(styleEl);

                    var sheetHolder = document.createElement('div');
                    sheetHolder.className = 'sappcCertPreviewSheet sappcCertPreviewSheet--burial';

                    var tplSheetClone = tplSheetNode.cloneNode(true);
                    populateBurialCertificateClone(tplSheetClone, printData);
                    sheetHolder.appendChild(tplSheetClone);
                    mountEl.appendChild(sheetHolder);
                    return true;
                }

                function loadBurialCertificationForRecord(id, doneFn, failFn) {
                    if (!id) {
                        return;
                    }
                    if (!paymentDetailsUrl || !certificationDetailsUrl) {
                        window.alert('Certification load is not configured.');
                        return;
                    }

                    setSelectedBurialId(id);
                    selectBurialTableRow(id);

                    ensureRegistryWorkflowStep('certification', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        $.when(
                            fetchJson(buildQueryUrl(paymentDetailsUrl, {
                                burial_id: id
                            }), jsonHeaders),
                            fetchJson(buildQueryUrl(certificationDetailsUrl, {
                                burial_id: id
                            }), jsonHeaders)
                        ).done(function(payTuple, certTuple) {
                            var pay = payTuple && payTuple[0] ? payTuple[0] : null;
                            var cert = certTuple && certTuple[0] ? certTuple[0] : null;
                            if (pay && pay.ok && pay.data) {
                                applyBurialCertificationTopFromPayment(pay.data);
                            }
                            if (cert && cert.ok && cert.data) {
                                applyBurialCertificationFromDetails(cert.data);
                            }
                            if (typeof doneFn === 'function') {
                                doneFn(cert);
                            }
                        }).fail(function(xhr) {
                            var msg = 'Could not load certification record.';
                            var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (data && data.message) {
                                msg = data.message;
                            }
                            if (typeof failFn === 'function') {
                                failFn(msg);
                                return;
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg
                                });
                            } else {
                                window.alert(msg);
                            }
                        });
                    });
                }

                function showBurialCertificatePreview(id) {
                    loadBurialCertificationForRecord(id, function() {
                        if (typeof window.sappcShowCertificatePreview !== 'function') {
                            window.alert('Certificate preview is not available on this page.');
                            return;
                        }
                        window.sappcShowCertificatePreview({
                            title: 'Burial Certificate',
                            render: function(mountEl) {
                                mountBurialCertificatePreview(mountEl, collectBurialCertificatePrintData());
                            },
                            onPrint: function() {
                                printBurialCertificationSheet();
                            }
                        });
                    });
                }

                window.sappcShowBurialCertificatePreview = showBurialCertificatePreview;

                function saveBurialCertificationRecord() {
                    var bid = getSelectedBurialId();
                    if (!certificationSaveUrl) {
                        return $.Deferred().reject({
                            responseJSON: {
                                message: 'Certification save is not configured.'
                            }
                        }).promise();
                    }

                    var payload = {
                        burial_id: bid ? parseInt(bid, 10) : null,
                        reference_code: certFieldValue('#brCertRefCode'),
                        client: certFieldValue('#brCertClient'),
                        contact_number: sappcPhMobileDigitsOnly(certFieldValue('#brCertContact')),
                        top_address: certFieldValue('#brCertTopAddress'),
                        date_issued: certFieldValue('#brCertDateIssued'),
                    };

                    return fetchPostJson(certificationSaveUrl, payload, csrf);
                }

                function printBurialCertificationSheet() {
                    var printData = collectBurialCertificatePrintData();
                    var tplNode = document.getElementById('burialCertificatePrintableTemplate');
                    if (!tplNode || !tplNode.content) {
                        window.alert('Print template not found.');
                        return;
                    }
                    var tplStyleNode = tplNode.content.querySelector('style');
                    var tplWrapNode = tplNode.content.querySelector('.bc-wrap');
                    if (!tplStyleNode || !tplWrapNode) {
                        window.alert('Print template is incomplete.');
                        return;
                    }
                    var tplCss = tplStyleNode.textContent || '';
                    var tplBody = tplWrapNode.outerHTML || '';
                    var printWin = window.open('', '_blank');
                    if (!printWin) {
                        window.alert('Pop-up blocked. Please allow pop-ups to print the certificate.');
                        return;
                    }
                    var html = '<!doctype html><html><head><meta charset="utf-8"><title> </title><style>' +
                        tplCss +
                        '</style></head><body>' + tplBody + '</body></html>';
                    printWin.document.open();
                    printWin.document.write(html);
                    printWin.document.close();
                    printWin.onload = function() {
                        var wrap = printWin.document.querySelector('.bc-wrap');
                        if (wrap) {
                            populateBurialCertificateClone(wrap, printData);
                        }
                    };
                    printWin.focus();
                    setTimeout(function() {
                        printWin.print();
                    }, 350);
                }

                $certForm.on('submit', function(e) {
                    e.preventDefault();
                    printBurialCertificationSheet();
                });

                $('#brCertAddRecordBtn').on('click', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    saveBurialCertificationRecord()
                        .done(function(res) {
                            printBurialCertificationSheet();
                            var msg = (res && res.message) ? res.message : 'Certification record saved.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: msg
                                });
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Certification could not be saved.';
                            var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (data && data.errors) {
                                var vals = Object.values(data.errors);
                                if (vals.length && Array.isArray(vals[0]) && vals[0][0]) {
                                    msg = vals[0][0];
                                }
                            } else if (data && data.message) {
                                msg = data.message;
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg
                                });
                            } else {
                                window.alert(msg);
                            }
                        })
                        .always(function() {
                            $btn.prop('disabled', false);
                        });
                });
                } catch (err) {
                    if (window.console && typeof window.console.error === 'function') {
                        window.console.error('Burial certification modal init failed:', err);
                    }
                }
            })();

            var $scheduleForm = $('#burialScheduleRequestForm');
            var $scheduleBtn = $('#burialScheduleRequestBtn');
            var $scheduleNewBtn = $('#burialNewRecordBtn');
            var scheduleSaveUrl = $scheduleForm.attr('data-schedule-save-url') || $scheduleBtn.attr('data-schedule-save-url') || '';
            var scheduleReservedUrl = ($scheduleForm.attr('data-schedule-reserved-url') || '').trim();
            var calendarReservedLookup = {};
            var $scheduleModal = $('#burialScheduleRequestModal');
            var $calMonthSel = $('#brCalMonth');
            var $calYearSel = $('#brCalYear');
            var $calMonthNumEl = $('#brCalMonthNum');
            var $calDayCells = $('#brCalDayCells');
            var $scheduleDateInput = $('#brScheduleDate');
            var $scheduleTimeInput = $('#brScheduleTime24');

            function toIsoDate(y, m0, d) {
                return String(y) + '-' + String(m0 + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            }

            function parseIsoDate(v) {
                if (!v || typeof v !== 'string') return null;
                var p = v.split('-');
                if (p.length !== 3) return null;
                var y = parseInt(p[0], 10);
                var m = parseInt(p[1], 10) - 1;
                var d = parseInt(p[2], 10);
                if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
                var dt = new Date(y, m, d);
                if (dt.getFullYear() !== y || dt.getMonth() !== m || dt.getDate() !== d) return null;
                return dt;
            }

            function monthNameFromIndex(m0) {
                return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
                    'September', 'October', 'November', 'December'
                ][m0] || 'January';
            }

            var calendarViewDate = (function() {
                var src = parseIsoDate($scheduleDateInput.val());
                if (src) return new Date(src.getFullYear(), src.getMonth(), 1);
                var now = new Date();
                return new Date(now.getFullYear(), now.getMonth(), 1);
            })();

            function populateCalendarSelectors() {
                if (!$calMonthSel.length || !$calYearSel.length) return;
                var monthHtml = '';
                for (var m = 0; m < 12; m++) {
                    monthHtml += '<option value="' + m + '">' + monthNameFromIndex(m) + '</option>';
                }
                $calMonthSel.html(monthHtml);
                var baseYear = new Date().getFullYear();
                var yearHtml = '';
                for (var y = baseYear - 2; y <= baseYear + 5; y++) {
                    yearHtml += '<option value="' + y + '">' + y + '</option>';
                }
                $calYearSel.html(yearHtml);
            }

            function syncCalendarHeader() {
                $calMonthSel.val(String(calendarViewDate.getMonth()));
                $calYearSel.val(String(calendarViewDate.getFullYear()));
                $calMonthNumEl.text(String(calendarViewDate.getMonth() + 1));
            }

            function fetchReservedDatesForMonth(year, month0, done) {
                calendarReservedLookup = {};
                if (!scheduleReservedUrl) {
                    done();
                    return;
                }
                $.ajax({
                    url: buildQueryUrl(scheduleReservedUrl, {
                        year: year,
                        month: month0 + 1
                    }),
                    method: 'GET',
                    dataType: 'json',
                    headers: jsonHeaders,
                }).done(function(res) {
                    if (res && res.ok && res.dates && res.dates.length) {
                        res.dates.forEach(function(d) {
                            if (d) calendarReservedLookup[String(d)] = true;
                        });
                    }
                }).always(function() {
                    done();
                });
            }

            function renderCalendarDayGridPaint() {
                if (!$calDayCells.length) return;
                var year = calendarViewDate.getFullYear();
                var month = calendarViewDate.getMonth();
                var firstDow = new Date(year, month, 1).getDay();
                var daysInMonth = new Date(year, month + 1, 0).getDate();
                var selected = parseIsoDate($scheduleDateInput.val());
                var html = '';
                for (var i = 0; i < firstDow; i++) {
                    html += '<span class="sappcScheduleDayPad" aria-hidden="true"></span>';
                }
                for (var day = 1; day <= daysInMonth; day++) {
                    var current = new Date(year, month, day);
                    var dow = current.getDay();
                    var iso = toIsoDate(year, month, day);
                    var classes = 'sappcScheduleDay';
                    if (dow === 0) classes += ' is-sunday';
                    if (dow === 6) classes += ' is-saturday';
                    var isSel = selected && selected.getFullYear() === year && selected.getMonth() === month && selected.getDate() === day;
                    var isReserved = !!calendarReservedLookup[iso];
                    if (isSel) {
                        classes += ' is-selected';
                    } else if (isReserved) {
                        classes += ' is-reserved';
                    }
                    var label = monthNameFromIndex(month) + ' ' + day + ', ' + year;
                    if (isSel || isReserved) {
                        label += ', reserved';
                    }
                    var inner;
                    if (isSel || isReserved) {
                        inner = '<span class="sappcScheduleDayNum" aria-hidden="true">' + day +
                            '</span><span class="sappcScheduleDayLabel" aria-hidden="true">Reserved</span>';
                    } else {
                        inner = String(day);
                    }
                    html += '<button type="button" class="' + classes + '" data-date="' + esc(iso) + '" aria-label="' + esc(label) + '">' + inner + '</button>';
                }
                $calDayCells.html(html);
            }

            function renderCalendarDayGrid() {
                if (!$calDayCells.length) return;
                var year = calendarViewDate.getFullYear();
                var month = calendarViewDate.getMonth();
                fetchReservedDatesForMonth(year, month, function() {
                    renderCalendarDayGridPaint();
                });
            }

            function resetScheduleRequestFormForNewEntry() {
                if (!$scheduleForm.length) return;
                setSelectedBurialId('');
                $('#brScheduleRefCode').val($scheduleForm.attr('data-default-reference-code') || '');
                $('#brScheduleContact').val('');
                $('#brScheduleClient').val('');
                $('#brScheduleAddress').val('');
                $('#brScheduleSex').val('');
                $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
                $scheduleTimeInput.val('10:00');
                $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                var sel = parseIsoDate($scheduleDateInput.val());
                if (sel) {
                    calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                }
                syncCalendarHeader();
                renderCalendarDayGrid();
            }

            function initScheduleCalendar() {
                if (!$calMonthSel.length || !$calYearSel.length || !$('#brCalPrev').length || !$('#brCalNext').length || !$calDayCells.length || !$scheduleDateInput.length) {
                    return;
                }
                populateCalendarSelectors();
                syncCalendarHeader();
                renderCalendarDayGrid();
                $('#brCalPrev').on('click', function() {
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate.getMonth() - 1, 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $('#brCalNext').on('click', function() {
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate.getMonth() + 1, 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $calMonthSel.on('change', function() {
                    var m = parseInt($(this).val(), 10);
                    if (isNaN(m)) return;
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), m, 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $calYearSel.on('change', function() {
                    var y = parseInt($(this).val(), 10);
                    if (isNaN(y)) return;
                    calendarViewDate = new Date(y, calendarViewDate.getMonth(), 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $calDayCells.on('click', 'button.sappcScheduleDay', function() {
                    var iso = $(this).attr('data-date') || '';
                    if (!iso) return;
                    $scheduleDateInput.val(iso);
                    var sel = parseIsoDate(iso);
                    if (sel) {
                        calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                    }
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $scheduleDateInput.on('change', function() {
                    var sel = parseIsoDate($(this).val());
                    if (!sel) return;
                    calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
            }

            initScheduleCalendar();

            $('#burialTableBody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a,button').length) return;
                var $tr = $(this);
                if ($tr.hasClass('sappc-table-loading') || $tr.hasClass('sappc-table-empty')) return;
                if ($tr.hasClass('is-schedule-selected')) {
                    if ($scheduleForm.length) {
                        resetScheduleRequestFormForNewEntry();
                    } else {
                        $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                        setSelectedBurialId('');
                    }
                    return;
                }
                $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $tr.addClass('is-schedule-selected');
                if (($tr.attr('data-document-type') || '').trim() !== 'Burial') {
                    setSelectedBurialId('');
                    return;
                }
                setSelectedBurialId($tr.attr('data-record-id') || '');
                if (activeSection !== 'schedule' || !$scheduleForm.length) {
                    return;
                }
                var $tds = $tr.find('td');
                if ($tds.length < 6) return;
                $('#brScheduleRefCode').val(($tds.eq(1).text() || '').trim());
                $('#brScheduleClient').val(($tds.eq(2).text() || '').trim());
                $('#brScheduleAddress').val(($tds.eq(3).text() || '').trim());
                var rawSex = ($tds.eq(4).text() || '').trim();
                if (rawSex === '\u2014' || rawSex === '-' || rawSex === '') {
                    $('#brScheduleSex').val('');
                } else {
                    $('#brScheduleSex').val(rawSex);
                }
                var rawContact = ($tds.eq(5).text() || '').trim();
                $('#brScheduleContact').val(
                    (rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' : formatPhMobileDisplay(rawContact)
                );
            });

            if ($scheduleForm.length && scheduleSaveUrl) {
                $scheduleForm.on('submit', function(e) {
                    e.preventDefault();
                    var cid = getSelectedBurialId();
                    var payload = {
                        schedule_date: $('#brScheduleDate').val(),
                        schedule_time: $('#brScheduleTime24').val(),
                        client: ($('#brScheduleClient').val() || '').trim(),
                        sex: ($('#brScheduleSex').val() || '').trim(),
                        contact_number: sappcPhMobileDigitsOnly($('#brScheduleContact').val()),
                        address: ($('#brScheduleAddress').val() || '').trim(),
                        reference_code: ($('#brScheduleRefCode').val() || '').trim(),
                    };
                    if (cid) {
                        var n = parseInt(cid, 10);
                        if (!isNaN(n)) payload.burial_id = n;
                    }
                    var $submitBtn = $scheduleForm.find('button[type="submit"], input[type="submit"]').first();
                    $submitBtn.prop('disabled', true);
                    fetchPostJson(scheduleSaveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                if (typeof bootstrap !== 'undefined' && $scheduleModal.length) {
                                    var inst = bootstrap.Modal.getInstance($scheduleModal[0]);
                                    if (inst) inst.hide();
                                }
                                fetchRecords();
                                var okMsgBr =
                                    res && res.message ? String(res.message) : 'Schedule reserved successfully.';
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Reserved',
                                        text: okMsgBr,
                                        confirmButtonText: 'OK',
                                    });
                                } else {
                                    window.alert(okMsgBr);
                                }
                            }
                        })
                        .fail(function(xhr) {
                            var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            var msg = 'Schedule could not be saved.';
                            var lines = [];
                            if (data && data.errors && typeof data.errors === 'object') {
                                Object.keys(data.errors).forEach(function(k) {
                                    var arr = data.errors[k];
                                    if (Array.isArray(arr) && arr.length && arr[0]) {
                                        lines.push(String(arr[0]));
                                    }
                                });
                                if (lines.length) msg = lines.join('\n');
                            }
                            if (lines.length === 0 && data && data.message) {
                                msg = String(data.message);
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Cannot save schedule',
                                    text: msg,
                                });
                            } else {
                                window.alert(msg);
                            }
                        })
                        .always(function() {
                            $submitBtn.prop('disabled', false);
                        });
                });
            }

            function applyBurialScheduleDetailsToForm(d) {
                if (!d || typeof d !== 'object') return;
                if (d.burial_id != null && String(d.burial_id).trim() !== '') {
                    setSelectedBurialId(String(d.burial_id).trim());
                }
                $('#brScheduleRefCode').val(d.reference_code != null ? String(d.reference_code) : '');
                $('#brScheduleClient').val(d.client != null ? String(d.client) : '');
                $('#brScheduleAddress').val(d.address != null ? String(d.address) : '');
                $('#brScheduleSex').val(d.sex != null ? String(d.sex) : '');
                var cn = d.contact_number != null ? String(d.contact_number).trim() : '';
                $('#brScheduleContact').val(cn !== '' ? formatPhMobileDisplay(cn) : '');
                var sd = d.schedule_date != null ? String(d.schedule_date).trim().slice(0, 10) : '';
                $('#brScheduleDate').val(sd);
                var st = d.schedule_time != null ? String(d.schedule_time).trim() : '';
                if (st.length >= 5) {
                    st = st.slice(0, 5);
                }
                $('#brScheduleTime24').val(st || '10:00');
            }

            function syncBurialScheduleModalCalendarFromInputs() {
                if (!$scheduleDateInput.val()) {
                    $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
                }
                if (!$scheduleTimeInput.val()) {
                    $scheduleTimeInput.val('10:00');
                }
                var selectedDate = parseIsoDate($scheduleDateInput.val());
                if (selectedDate) {
                    calendarViewDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                } else {
                    var nowHeader = new Date();
                    calendarViewDate = new Date(nowHeader.getFullYear(), nowHeader.getMonth(), 1);
                }
                syncCalendarHeader();
                renderCalendarDayGrid();
            }

            function onBurialScheduleToolbarClick() {
                var cid = getSelectedBurialId();
                var $sel = $('#burialTableBody tr.is-schedule-selected');
                if (!cid && $sel.length) {
                    var doc = ($sel.attr('data-document-type') || '').trim();
                    if (doc === 'Burial') {
                        var rid = ($sel.attr('data-record-id') || '').trim();
                        if (rid) {
                            setSelectedBurialId(rid);
                            cid = rid;
                        }
                    }
                }
                if (!cid) {
                    resetScheduleRequestFormForNewEntry();
                }
            }

            $scheduleBtn.on('click', onBurialScheduleToolbarClick);
            $scheduleNewBtn.on('click', onBurialScheduleToolbarClick);

            if ($scheduleModal.length) {
                $scheduleModal.on('show.bs.modal', function(e) {
                    var cid = getSelectedBurialId();
                    if (!cid) {
                        resetScheduleRequestFormForNewEntry();
                        return;
                    }
                    if ($scheduleModal.data('workflow-gate-ok')) {
                        $scheduleModal.removeData('workflow-gate-ok');
                        return;
                    }
                    e.preventDefault();
                    ensureRegistryWorkflowStep('schedule', cid, function(ok) {
                        if (!ok) {
                            return;
                        }
                        $scheduleModal.data('workflow-gate-ok', true);
                        bootstrap.Modal.getOrCreateInstance($scheduleModal[0]).show();
                    });
                });
                $scheduleModal.on('shown.bs.modal', function() {
                    if ($scheduleBtn.length) $scheduleBtn.attr('aria-expanded', 'true');
                    if ($scheduleNewBtn.length) $scheduleNewBtn.attr('aria-expanded', 'true');
                    var cid = getSelectedBurialId();
                    if (cid && scheduleDetailsUrl) {
                        fetchJson(buildQueryUrl(scheduleDetailsUrl, {
                            burial_id: cid,
                        }), jsonHeaders)
                            .done(function(res) {
                                if (res && res.ok && res.data) {
                                    applyBurialScheduleDetailsToForm(res.data);
                                }
                            })
                            .fail(function(xhr) {
                                var msg = 'Could not load schedule details.';
                                var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                                if (data && data.message) {
                                    msg = String(data.message);
                                }
                                sappcBrSwal({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg,
                                });
                            })
                            .always(function() {
                                syncBurialScheduleModalCalendarFromInputs();
                            });
                    } else {
                        syncBurialScheduleModalCalendarFromInputs();
                    }
                });
                $scheduleModal.on('hidden.bs.modal', function() {
                    if ($scheduleBtn.length) $scheduleBtn.attr('aria-expanded', 'false');
                    if ($scheduleNewBtn.length) $scheduleNewBtn.attr('aria-expanded', 'false');
                });
            }

            $('#burialTableBody').on('click', '.sappc-icon-action--delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = ($(this).attr('data-record-id') || '').trim();
                if (!id || !burialDeleteUrl) return;

                function runDelete() {
                    fetchPostJson(
                            burialDeleteUrl, {
                                burial_id: parseInt(id, 10),
                            },
                            csrf
                        )
                        .done(function(res) {
                            if (res && res.ok) {
                                if (getSelectedBurialId() === id) {
                                    setSelectedBurialId('');
                                }
                                var msg = res && res.message ? res.message : 'Removed.';
                                sappcBrSwal({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: msg,
                                });
                                fetchRecords();
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Could not delete.';
                            var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (data && data.message) msg = data.message;
                            sappcBrSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        });
                }

                sappcBrConfirmDeleteDocument({
                    title: 'Delete burial record?',
                    text: 'This permanently deletes this burial row from the registry (including schedule and payment data).',
                    confirmButtonText: 'Yes, delete',
                }, runDelete);
            });

            function selectBurialTableRow(id) {
                setSelectedBurialId(id);
                $('#brAppBurialId').val(id);
                $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $('#burialTableBody tr').each(function() {
                    if (($(this).attr('data-record-id') || '').trim() === id) {
                        $(this).addClass('is-schedule-selected');
                        return false;
                    }
                });
            }

            function openBurialSectionRecord(id) {
                if (!id) return;
                selectBurialTableRow(id);
                if (activeSection === 'schedule') {
                    ensureRegistryWorkflowStep('schedule', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        if (typeof bootstrap !== 'undefined' && $('#burialScheduleRequestModal').length) {
                            bootstrap.Modal.getOrCreateInstance($('#burialScheduleRequestModal')[0]).show();
                        }
                    });
                    return;
                }
                if (activeSection === 'payment') {
                    ensureRegistryWorkflowStep('payment', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        $('#burialPaymentFeeBtn').trigger('click');
                    });
                    return;
                }
                if (activeSection === 'certification') {
                    ensureRegistryWorkflowStep('certification', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        $('#burialCertificationBtn').trigger('click');
                    });
                    return;
                }
                if (!burialAppDetailsUrl) {
                    sappcBrSwal({ icon: 'warning', title: 'Not configured', text: 'Burial application is not configured.' });
                    return;
                }
                $('#burialApplicationFormBtn').trigger('click');
            }
            window.sappcRegistryWorkflowOpenRecord = openBurialSectionRecord;

            $('#burialTableBody').on('click', '.sappc-icon-action--view', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = ($(this).attr('data-record-id') || '').trim();
                if (activeSection === 'certification') {
                    if (typeof window.sappcShowBurialCertificatePreview === 'function') {
                        window.sappcShowBurialCertificatePreview(id);
                    }
                    return;
                }
                openBurialSectionRecord(id);
            });

            $('#burialTableBody').on('click', '.sappc-icon-action--edit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openBurialSectionRecord(($(this).attr('data-record-id') || '').trim());
            });

            (function initBurialApplicationModal() {
                var $burialAppModal = $('#burialApplicationFormModal');
                var $burialAppForm = $('#burialApplicationForm');
                var $burialAppBtn = $('#burialApplicationFormBtn');
                if (!$burialAppModal.length || !$burialAppForm.length || !$burialAppBtn.length) {
                    return;
                }

                function applyBurialApplicationData(data) {
                    if (!data || typeof data !== 'object') {
                        return;
                    }
                    var $f = $burialAppForm;
                    if ($f[0]) {
                        $f[0].reset();
                    }
                    $f.find('input[type=radio]').prop('checked', false);
                    Object.keys(data).forEach(function(key) {
                        if (key === '_token') {
                            return;
                        }
                        var val = data[key];
                        if (val === undefined || val === null) {
                            return;
                        }
                        var escName = String(key).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                        var $fields = $f.find('[name="' + escName + '"]');
                        if (!$fields.length) {
                            return;
                        }
                        var el0 = $fields[0];
                        if (el0.type === 'radio') {
                            var s = String(val);
                            $fields.each(function() {
                                if (this.value === s) {
                                    $(this).prop('checked', true);
                                }
                            });
                        } else if (!el0.readOnly) {
                            $fields.val(String(val));
                        }
                    });
                }

                function collectBurialApplicationPayload() {
                    var $f = $burialAppForm;
                    var out = {};
                    $f.find('input, select, textarea').each(function() {
                        var n = this.name;
                        if (!n || n === '_token') {
                            return;
                        }
                        if (this.type === 'radio') {
                            if ($(this).is(':checked')) {
                                out[n] = this.value;
                            }
                            return;
                        }
                        out[n] = $(this).val() == null ? '' : String($(this).val());
                    });
                    return out;
                }

                $burialAppModal.on('shown.bs.modal', function() {
                    $burialAppBtn.attr('aria-expanded', 'true');
                });
                $burialAppModal.on('hidden.bs.modal', function() {
                    $burialAppBtn.attr('aria-expanded', 'false');
                });

                $burialAppBtn.on('click', function(e) {
                    e.preventDefault();
                    if (typeof bootstrap === 'undefined') {
                        window.alert('Bootstrap is required for this dialog.');
                        return;
                    }
                    var cid = getSelectedBurialId();
                    if (!cid) {
                        setSelectedBurialId('');
                        $('#burialTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                        if ($burialAppForm[0]) {
                            $burialAppForm[0].reset();
                        }
                        $burialAppForm.find('input[type=radio]').prop('checked', false);
                        $('#brAppBurialId').val('');
                        applyBurialApplicationData({});
                        bootstrap.Modal.getOrCreateInstance($burialAppModal[0]).show();
                        return;
                    }
                    if (!burialAppDetailsUrl) {
                        window.alert('Burial application is not configured.');
                        return;
                    }
                    var bsModal = bootstrap.Modal.getOrCreateInstance($burialAppModal[0]);
                    fetchJson(buildQueryUrl(burialAppDetailsUrl, {
                        burial_id: cid,
                    }), jsonHeaders)
                        .done(function(res) {
                            if (res && res.ok) {
                                if ($burialAppForm[0]) {
                                    $burialAppForm[0].reset();
                                }
                                $burialAppForm.find('input[type=radio]').prop('checked', false);
                                $('#brAppBurialId').val(cid);
                                applyBurialApplicationData(res.data || {});
                                bsModal.show();
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Could not load burial application.';
                            var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (d && d.message) {
                                msg = d.message;
                            }
                            sappcBrSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        });
                });

                $('#burialApplicationFormSaveBtn').on('click', function() {
                    if (!burialAppSaveUrl) {
                        return;
                    }
                    if (typeof bootstrap === 'undefined') {
                        return;
                    }
                    var bid = ($('#brAppBurialId').val() || '').trim() || getSelectedBurialId();
                    var n = bid ? parseInt(bid, 10) : 0;
                    if (bid && (isNaN(n) || n < 1)) {
                        window.alert('Invalid record.');
                        return;
                    }
                    var payload = collectBurialApplicationPayload();
                    if (n > 0) {
                        payload.burial_id = n;
                    }
                    var $saveBtn = $('#burialApplicationFormSaveBtn');
                    var bsModal = bootstrap.Modal.getOrCreateInstance($burialAppModal[0]);
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(burialAppSaveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                var savedId = (res.data && res.data.burial_id != null) ?
                                    String(res.data.burial_id).trim() :
                                    (n > 0 ? String(n) : '');
                                if (savedId) {
                                    setSelectedBurialId(savedId);
                                    $('#brAppBurialId').val(savedId);
                                    if (typeof fetchRecords === 'function') {
                                        fetchRecords();
                                    }
                                }
                                var shouldReopenFromDashboard = isDashboardEmbeddedAppContext();
                                bsModal.hide();
                                var msg = res && res.message ? res.message : 'Burial application saved.';
                                sappcBrSwal({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: msg,
                                });
                                if (shouldReopenFromDashboard) {
                                    setTimeout(function() {
                                        $('#burialApplicationFormBtn').trigger('click');
                                    }, 120);
                                }
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Could not save burial application.';
                            var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (d && d.errors) {
                                var vals = Object.values(d.errors);
                                if (vals.length && Array.isArray(vals[0]) && vals[0][0]) {
                                    msg = vals[0][0];
                                }
                            } else if (d && d.message) {
                                msg = d.message;
                            }
                            sappcBrSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        })
                        .always(function() {
                            $saveBtn.prop('disabled', false);
                        });
                });
            })();

        });
    })();
</script>
