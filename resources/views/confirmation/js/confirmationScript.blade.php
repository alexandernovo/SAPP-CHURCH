@php
    $initialTablePayload = $initialTablePayload ?? null;
    $activeSection = $activeSection ?? 'schedule';
@endphp
<script>
    (function() {
        'use strict';

        var initialTablePayload = @json($initialTablePayload);
        var activeSection = @json($activeSection);

        function getSelectedConfirmationId() {
            var cid = ($('#cnSelectedConfirmationId').val() || '').trim();
            if (cid) {
                return cid;
            }
            cid = ($('#cnScheduleConfirmationId').val() || '').trim();
            if (cid) {
                return cid;
            }
            var $sel = $('#confirmationTableBody tr.is-schedule-selected');
            if ($sel.length) {
                return ($sel.first().attr('data-record-id') || '').trim();
            }
            return '';
        }

        function setSelectedConfirmationId(id) {
            id = id == null ? '' : String(id).trim();
            $('#cnSelectedConfirmationId').val(id);
            if ($('#cnScheduleConfirmationId').length) {
                $('#cnScheduleConfirmationId').val(id);
            }
        }

        function registryWorkflowNextUrl(currentStep) {
            var $panel = $('#confirmationRecordsPanel');
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
            var id = String(recordId == null ? getSelectedConfirmationId() : recordId).trim();
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
                    setSelectedConfirmationId(id);
                    $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                    $('#confirmationTableBody tr').each(function() {
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
            sappcCnSwal({
                icon: 'warning',
                title: 'Payment required',
                text: msg,
                confirmButtonText: 'OK',
            });
        }

        function registryWorkflowHasCertification() {
            return ($('#confirmationRecordsPanel').attr('data-workflow-has-certification') || '0') === '1';
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
            var payUrl = ($('#confirmationRecordsPanel').attr('data-payment-details-url') || '').trim();
            if (!payUrl) {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            fetchJson(buildQueryUrl(payUrl, { confirmation_id: recordId }), {
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

        function fetchJson(url, headers) {
            return $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: headers || {},
            });
        }

        function sappcCnSwal(cfg) {
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

        function sappcCnConfirm(cfg) {
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

        function sappcCnConfirmDeleteDocument(firstCfg, onFinalConfirm) {
            sappcCnConfirm(firstCfg).then(function(r) {
                if (!r.isConfirmed) {
                    return;
                }
                sappcCnConfirm({
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

        function sappcSwalSelectConfirmationRowFirst() {
            sappcCnSwal({
                icon: 'warning',
                title: 'Select a record',
                text: 'Select a confirmation row in the table first (click the row so it is highlighted).',
                confirmButtonText: 'OK',
            });
        }

        function sappcConfirmationGetSelectedRecordIdStrict() {
            var rid = getSelectedConfirmationId();
            if (!rid) {
                return '';
            }
            var n = parseInt(rid, 10);
            if (isNaN(n) || n < 1 || String(n) !== rid) {
                return '';
            }
            var $row = $('#confirmationTableBody tr.is-schedule-selected').first();
            if ($row.length && ($row.attr('data-document-type') || '').trim() !== 'Confirmation') {
                return '';
            }
            return rid;
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

        $(document).on('input', '#cnScheduleContact, #cnPaymentContact', function() {
            var $el = $(this);
            var before = $el.val();
            var formatted = formatPhMobileDisplay(before);
            if (formatted !== before) {
                $el.val(formatted);
            }
        });

        function rowHtml(row) {
            var viewLabel = activeSection === 'certification' ? 'View certificate' : 'View record';
            return (
                '<tr data-record-id="' + esc(row.recordId) + '" data-document-type="' + esc(row.documentType) +
                '">' +
                '<td>' + esc(row.rowNumber) + '</td>' +
                '<td>' + esc(row.referenceCode) + '</td>' +
                '<td>' + esc(typeof sappcFormatClientDisplayName === 'function' ? sappcFormatClientDisplayName(row.client) : row.client) + '</td>' +
                '<td>' + esc(typeof sappcFormatAddress === 'function' ? sappcFormatAddress(row.address) : row.address) + '</td>' +
                '<td>' + esc(row.sex) + '</td>' +
                '<td>' + esc(row.contactNum) + '</td>' +
                '<td>' + esc(row.dateCreated) + '</td>' +
                '<td class="text-center"><div class="sappc-icon-action_group">' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="' + viewLabel + '" aria-label="' + viewLabel + '" data-record-id="' +
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

            (function applyConfirmationFieldFormatGuides() {
                function ph(sel, val) {
                    var $el = $(sel);
                    if ($el.length) {
                        $el.attr('placeholder', val);
                    }
                }
                ph('#cnAppFirstName', 'Juan');
                ph('#cnAppMiddleName', 'D.');
                ph('#cnAppFamilyName', 'Cruz');
                ph('#cnAppPob', 'Barbaza, Antique');
                ph('#cnAppFather', 'Juan D. Cruz');
                ph('#cnAppMother', 'Maria D. Cruz');
                ph('#cnAppAddress', 'Street, Barangay, Municipality');
                ph('#cnAppBapPlace', 'Parish church name');
                ph('#cnAppMinisterBap', 'Rev. name (optional)');
                ph('#cnAppBookNo', 'e.g. 1');
                ph('#cnAppPageNo', 'e.g. 12');
                ph('#cnAppRegistryNo', 'e.g. 45');
                ph('#cnAppConfMinister', 'Rev. name (optional)');
                ph('#cnAppGp1', 'Juan D. Cruz');
                ph('#cnAppGp2', 'Juan D. Cruz');
                ph('#cnAppGp3', 'Juan D. Cruz');
                ph('#cnAppGp4', 'Juan D. Cruz');
                ph('#cnCertChildFirst', 'Juan');
                ph('#cnCertChildMiddle', 'D.');
                ph('#cnCertChildLast', 'Cruz');
                ph('#cnCertBirthplace', 'Barbaza, Antique');
                ph('#cnCertFatherFirst', 'Juan');
                ph('#cnCertFatherMiddle', 'D.');
                ph('#cnCertFatherLast', 'Cruz');
                ph('#cnCertMotherFirst', 'Maria');
                ph('#cnCertMotherMiddle', 'D.');
                ph('#cnCertMotherLast', 'Cruz');
                ph('#cnCertPriest', 'Rev. name');
                ph('#cnCertSponsors', 'Juan D. Cruz; Maria D. Cruz');
                ph('#cnCertPurpose', 'e.g. school enrollment, passport');
            })();

            var $panel = $('#confirmationRecordsPanel');
            if (!$panel.length) return;

            var tableColspan = parseInt($panel.attr('data-table-colspan'), 10);
            if (isNaN(tableColspan) || tableColspan < 1) {
                tableColspan = 8;
            }

            var url = $panel.attr('data-records-url');
            if (!url) return;

            function tryOpenConfirmationApplicationFromDashboardQuery() {
                try {
                    var u = new URL(window.location.href);
                    var id = (u.searchParams.get('sappc_dash_app') || '').trim();
                    if (!id) {
                        return;
                    }
                    u.searchParams.delete('sappc_dash_app');
                    var q = u.searchParams.toString();
                    window.history.replaceState({}, '', u.pathname + (q ? '?' + q : '') + u.hash);
                    setSelectedConfirmationId(id);
                    $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                    $('#confirmationTableBody tr').each(function() {
                        if (($(this).attr('data-record-id') || '').trim() === id) {
                            $(this).addClass('is-schedule-selected');
                            return false;
                        }
                    });
                    setTimeout(function() {
                        $('#confirmationApplicationFormBtn').trigger('click');
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

            tryOpenConfirmationApplicationFromDashboardQuery();

            var csrf = getMetaCsrf();
            var jsonHeaders = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };

            var meta0 = (initialTablePayload && initialTablePayload.meta) ? initialTablePayload.meta : {};
            var state = {
                page: meta0.current_page || 1,
                per_page: meta0.per_page || 10,
                search: '',
                letter: '',
                date_from: '',
                date_to: '',
            };

            var $searchInput = $('#confirmationSearch');
            var $body = $('#confirmationTableBody');
            var $info = $('#confirmationTableFooterInfo');
            var $nav = $('#confirmationPagination');

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
                    registry_type: 'confirmation',
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
                        tryOpenConfirmationApplicationFromDashboardQuery();
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

            $('#confirmationEntries').on('change', function() {
                state.per_page = parseInt($(this).val(), 10) || 10;
                state.page = 1;
                fetchRecords();
            });

            $panel.find('.sappc-toolbar-date-strip_btn').on('click', function() {
                state.date_from = $('#confirmationDateFrom').val() || '';
                state.date_to = $('#confirmationDateTo').val() || '';
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

            var $reloadBtn = $('#confirmationReloadBtn');
            $panel.closest('.sappc-registry-page').find('.sappc-registry-toolbar a.sappc-registry-toolbar_btn[data-workflow-step]').on('click', function(e) {
                var step = ($(this).attr('data-workflow-step') || '').trim();
                var cid = getSelectedConfirmationId();
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

            var paymentDetailsUrl = ($panel.attr('data-payment-details-url') || '').trim();
            var paymentSaveUrlPanel = ($panel.attr('data-payment-save-url') || '').trim();
            var $appFormBtn = $('#confirmationApplicationFormBtn');
            var confirmationAppDetailsUrl = ($panel.attr('data-confirmation-application-details-url') || $appFormBtn.attr('data-confirmation-application-details-url') || '').trim();
            var confirmationAppSaveUrl = ($panel.attr('data-confirmation-application-save-url') || $appFormBtn.attr('data-confirmation-application-save-url') || '').trim();
            var confirmationArancelDetailsUrl = ($panel.attr('data-confirmation-arancel-details-url') || $appFormBtn.attr('data-confirmation-arancel-details-url') || '').trim();
            var confirmationArancelSaveUrl = ($panel.attr('data-confirmation-arancel-save-url') || $appFormBtn.attr('data-confirmation-arancel-save-url') || '').trim();
            var certificationDetailsUrl = ($panel.attr('data-confirmation-certification-details-url') || '').trim();
            var confirmationDeleteUrl = ($panel.attr('data-confirmation-delete-url') || '').trim();
            var scheduleDetailsUrl = ($panel.attr('data-schedule-details-url') || '').trim();

            var cnApplicationDraftsByConfirmationId = {};

            var $paymentModal = $('#confirmationPaymentFeeModal');
            var $paymentBtn = $('#confirmationPaymentFeeBtn');
            var $paymentFeeForm = $('#confirmationPaymentFeeForm');
            var $feeItemsBody = $('#confirmationPaymentFeeItemsBody');
            var $addFeeBtn = $('#confirmationPaymentFeeAddItemBtn');

            function renumberConfirmationFeeRows() {
                $feeItemsBody.find('[data-fee-row]').each(function(i) {
                    $(this).find('.sappcPaymentFeeModalCellNo').text(i + 1);
                    $(this).find('.sappcPaymentFeeModalItemInput').attr('aria-label', 'Fee item ' + (i + 1));
                });
            }

            function syncConfirmationPaymentFeeRowBadgeColors($row) {
                var $status = $row.find('.sappcPaymentFeeModalStatus');
                var $toggle = $row.find('.sappcPaymentFeeModalTogglePaid, .sappcPaymentFeeModalToggleUnpaid');
                var isPaid = $status.hasClass('sappcPaymentFeeModalStatusPaid');
                $status.addClass('sappc-payment-badge').removeClass('sappc-payment-badge--paid sappc-payment-badge--unpaid')
                    .addClass(isPaid ? 'sappc-payment-badge--paid' : 'sappc-payment-badge--unpaid');
                $toggle.addClass('sappc-payment-badge').removeClass(
                    'sappcPaymentFeeModalTogglePaid sappcPaymentFeeModalToggleUnpaid sappc-payment-badge--paid sappc-payment-badge--unpaid');
                if (isPaid) {
                    $toggle.addClass('sappcPaymentFeeModalToggleUnpaid sappc-payment-badge--unpaid');
                } else {
                    $toggle.addClass('sappcPaymentFeeModalTogglePaid sappc-payment-badge--paid');
                }
            }

            function syncAllConfirmationPaymentFeeRowBadgeColors() {
                $feeItemsBody.find('[data-fee-row]').each(function() {
                    syncConfirmationPaymentFeeRowBadgeColors($(this));
                });
            }

            function newConfirmationFeeRowHtml() {
                return '' +
                    '<tr class="sappcPaymentFeeModalRow" data-fee-row>' +
                    '<td class="sappcPaymentFeeModalCellNo"></td>' +
                    '<td><input type="text" class="sappcPaymentFeeModalItemInput" name="fee_items[]" value="" aria-label="Fee item"></td>' +
                    '<td><span class="sappc-payment-badge sappc-payment-badge--unpaid sappcPaymentFeeModalStatus sappcPaymentFeeModalStatusUnpaid">Unpaid</span></td>' +
                    '<td><span class="sappcPaymentFeeModalDatePaid" data-date-paid="">\u2014</span></td>' +
                    '<td class="text-center"><div class="sappcPaymentFeeModalActions">' +
                    '<button type="button" class="sappc-payment-badge sappc-payment-badge--paid sappcPaymentFeeModalTogglePaid">Paid</button>' +
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
                syncConfirmationPaymentFeeRowBadgeColors($tr);
                return $tr;
            }

            function serializeConfirmationPaymentFeeToObject() {
                return {
                    reference_code: ($('#cnPaymentRefCode').val() || '').trim(),
                    client: ($('#cnPaymentClient').val() || '').trim(),
                    contact_number: sappcPhMobileDigitsOnly($('#cnPaymentContact').val()),
                    address: ($('#cnPaymentAddress').val() || '').trim(),
                    fee_rows: collectConfirmationPaymentFeeRowsFromDom(),
                };
            }

            function applyConfirmationPaymentFeeFormObject(data) {
                if (!data || typeof data !== 'object') return;
                $('#cnPaymentRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
                $('#cnPaymentClient').val(data.client != null ? String(data.client) : '');
                $('#cnPaymentContact').val(
                    data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
                );
                $('#cnPaymentAddress').val(data.address != null ? String(data.address) : '');
                var feeRows = data.fee_rows;
                if (!Array.isArray(feeRows) || !feeRows.length) {
                    feeRows = [{}];
                }
                $feeItemsBody.empty();
                feeRows.forEach(function(fr) {
                    $feeItemsBody.append(buildConfirmationPaymentFeeRowFromData(fr));
                });
                renumberConfirmationFeeRows();
                syncAllConfirmationPaymentFeeRowBadgeColors();
            }

            function resetConfirmationPaymentFormForNewEntry() {
                setSelectedConfirmationId('');
                $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
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
                syncConfirmationPaymentFeeRowBadgeColors($row);
            });

            if ($paymentModal.length && $paymentBtn.length && typeof bootstrap !== 'undefined') {
                var paymentBsModal = bootstrap.Modal.getOrCreateInstance($paymentModal[0]);

                $paymentModal.on('shown.bs.modal', function() {
                    $paymentBtn.attr('aria-expanded', 'true');
                    syncAllConfirmationPaymentFeeRowBadgeColors();
                });
                $paymentModal.on('hidden.bs.modal', function() {
                    $paymentBtn.attr('aria-expanded', 'false');
                });

                $paymentBtn.on('click', function(e) {
                    e.preventDefault();
                    var cid = getSelectedConfirmationId();
                    if (!cid) {
                        resetConfirmationPaymentFormForNewEntry();
                        paymentBsModal.show();
                        return;
                    }
                    if (!paymentDetailsUrl) {
                        sappcCnSwal({
                            icon: 'warning',
                            title: 'Not configured',
                            text: 'Payment load is not configured.',
                        });
                        return;
                    }
                    ensureRegistryWorkflowStep('payment', cid, function(ok) {
                        if (!ok) {
                            return;
                        }
                    fetchJson(buildQueryUrl(paymentDetailsUrl, {
                        confirmation_id: cid
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
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        });
                    });
                });

                $paymentFeeForm.on('submit', function(e) {
                    e.preventDefault();
                    var saveUrl = ($paymentFeeForm.attr('data-save-url') || paymentSaveUrlPanel || '').trim();
                    if (!saveUrl) return;
                    var cid = getSelectedConfirmationId();
                    var payload = serializeConfirmationPaymentFeeToObject();
                    if (cid) {
                        payload.confirmation_id = parseInt(cid, 10);
                        if (isNaN(payload.confirmation_id)) {
                            sappcCnSwal({
                                icon: 'warning',
                                title: 'Invalid record',
                                text: 'Invalid record.',
                            });
                            return;
                        }
                    }
                    var $saveBtn = $('#confirmationPaymentFeeSaveBtn');
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(saveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                if (res.data && res.data.confirmation_id) {
                                    setSelectedConfirmationId(String(res.data.confirmation_id));
                                }
                                if (typeof bootstrap !== 'undefined' && $paymentModal.length) {
                                    var inst = bootstrap.Modal.getInstance($paymentModal[0]);
                                    if (inst) inst.hide();
                                }
                                var msg = (res && res.message) ? res.message : 'Payment record saved.';
                                sappcCnSwal({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: msg,
                                    confirmButtonText: 'OK',
                                });
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
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        })
                        .always(function() {
                            $saveBtn.prop('disabled', false);
                        });
                });
            }

            function selectConfirmationTableRow(id) {
                setSelectedConfirmationId(id);
                $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $('#confirmationTableBody tr').each(function() {
                    if (($(this).attr('data-record-id') || '').trim() === id) {
                        $(this).addClass('is-schedule-selected');
                        return false;
                    }
                });
            }

            function openConfirmationSectionRecord(id) {
                if (!id) {
                    return;
                }
                selectConfirmationTableRow(id);
                if (activeSection === 'schedule') {
                    ensureRegistryWorkflowStep('schedule', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        if (typeof bootstrap !== 'undefined' && $('#confirmationScheduleRequestModal').length) {
                            bootstrap.Modal.getOrCreateInstance($('#confirmationScheduleRequestModal')[0]).show();
                        }
                    });
                    return;
                }
                if (activeSection === 'payment') {
                    ensureRegistryWorkflowStep('payment', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        if ($('#confirmationPaymentFeeBtn').length) {
                            $('#confirmationPaymentFeeBtn').trigger('click');
                        }
                    });
                    return;
                }
                if (activeSection === 'certification') {
                    ensureRegistryWorkflowStep('certification', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        if ($('#confirmationCertificationBtn').length) {
                            $('#confirmationCertificationBtn').trigger('click');
                        }
                    });
                    return;
                }
                if ($('#confirmationApplicationFormBtn').length) {
                    $('#confirmationApplicationFormBtn').trigger('click');
                }
            }
            window.sappcRegistryWorkflowOpenRecord = openConfirmationSectionRecord;

            $('#confirmationTableBody').on('click', '.sappc-icon-action--view', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = ($(this).attr('data-record-id') || '').trim();
                if (activeSection === 'certification') {
                    if (typeof window.sappcShowConfirmationCertificatePreview === 'function') {
                        window.sappcShowConfirmationCertificatePreview(id);
                    }
                    return;
                }
                openConfirmationSectionRecord(id);
            });

            $('#confirmationTableBody').on('click', '.sappc-icon-action--edit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openConfirmationSectionRecord(($(this).attr('data-record-id') || '').trim());
            });

            (function initConfirmationKompirmaModals() {
                var applicationFieldNames = {
                    first_name: 1,
                    middle_name: 1,
                    family_name: 1,
                    date_of_birth: 1,
                    place_of_birth: 1,
                    father_name: 1,
                    mother_maiden: 1,
                    address: 1,
                    baptism_date: 1,
                    baptism_place: 1,
                    minister_baptism: 1,
                    book_no: 1,
                    page_no: 1,
                    registry_no: 1,
                    confirmation_date: 1,
                    confirmation_minister: 1,
                    godparent_1: 1,
                    godparent_2: 1,
                    godparent_3: 1,
                    godparent_4: 1,
                };
                var arancelFieldNames = {
                    amt_arancel: 1,
                    amt_candle: 1,
                    amt_godparents: 1,
                    other_label_1: 1,
                    other_label_2: 1,
                    other_label_3: 1,
                    amt_other_1: 1,
                    amt_other_2: 1,
                    amt_other_3: 1,
                    total_payment: 1,
                    sig_bpc_chairman: 1,
                    sig_parish_secretary: 1,
                    sig_presacramental_instructor: 1,
                    sig_parish_priest: 1,
                };

                function pickFields(src, set) {
                    var o = {};
                    if (!src || typeof src !== 'object') {
                        return o;
                    }
                    Object.keys(src).forEach(function(k) {
                        if (set[k]) {
                            o[k] = src[k];
                        }
                    });
                    return o;
                }

                var cnApplicationDraftSaveTimer = null;

                function serializeConfirmationApplicationFormToObject() {
                    var $form = $('#confirmationApplicationForm');
                    if (!$form.length) {
                        return {};
                    }
                    var arr = $form.serializeArray();
                    var payload = {};
                    $.each(arr, function(i, field) {
                        var n = field.name;
                        if (n.slice(-2) === '[]') {
                            var base = n.slice(0, -2);
                            if (!payload[base]) {
                                payload[base] = [];
                            }
                            payload[base].push(field.value);
                        } else if (payload[n] !== undefined) {
                            if (!Array.isArray(payload[n])) {
                                payload[n] = [payload[n]];
                            }
                            payload[n].push(field.value);
                        } else {
                            payload[n] = field.value;
                        }
                    });
                    return payload;
                }

                function clearConfirmationApplicationFormFields() {
                    var $form = $('#confirmationApplicationForm');
                    if (!$form.length) {
                        return;
                    }
                    $form.find('input[type="text"], input[type="date"], input[type="time"], textarea').val('');
                    $form.find('input[type="checkbox"]').prop('checked', false);
                    $form.find('input[type="hidden"]').each(function() {
                        if (this.name && this.name !== '_token') {
                            $(this).val('');
                        }
                    });
                    updateConfirmationArancelTotal();
                }

                function parseConfirmationArancelAmount(raw) {
                    if (raw == null) {
                        return 0;
                    }
                    var s = String(raw).replace(/,/g, '').trim();
                    if (s === '') {
                        return 0;
                    }
                    var n = parseFloat(s);
                    return isFinite(n) ? n : 0;
                }

                     function updateConfirmationArancelTotal() {
                    var $form = $('#confirmationApplicationForm');
                    if (!$form.length) {
                        return;
                    }
                    var $table = $form.find('.sappcCnArTable');
                    if (!$table.length) {
                        return;
                    }
                    var sum = 0;
                    var hasLine = false;
                    $table.find('input.sappcCnArAmt').each(function() {
                        if ($(this).attr('name') === 'total_payment') {
                            return;
                        }
                        var raw = $(this).val();
                        if (raw != null && String(raw).trim() !== '') {
                            hasLine = true;
                        }
                        sum += parseConfirmationArancelAmount(raw);
                    });
                    var $tot = $form.find('input[name="total_payment"]');
                    if (!$tot.length) {
                        return;
                    }
                    if (!hasLine && sum === 0) {
                        $tot.val('');
                        return;
                    }
                    $tot.val(sum.toFixed(2));
                }

                function applyConfirmationApplicationFormObject(snap) {
                    if (!snap || typeof snap !== 'object') {
                        return;
                    }
                    var $form = $('#confirmationApplicationForm');
                    if (!$form.length) {
                        return;
                    }
                    clearConfirmationApplicationFormFields();
                    $.each(snap, function(key, val) {
                        var $fields = $form.find('[name]').filter(function() {
                            return $(this).attr('name') === key;
                        });
                        if (!$fields.length) {
                            return;
                        }
                        if ($fields.first().attr('type') === 'checkbox') {
                            return;
                        }
                        if (Array.isArray(val)) {
                            $fields.each(function(i) {
                                if (val[i] !== undefined) {
                                    $(this).val(val[i]);
                                }
                            });
                        } else {
                            $fields.val(val != null ? String(val) : '');
                        }
                    });
                    updateConfirmationArancelTotal();
                }

                function confirmationApplicationDraftKey() {
                    var cid = getSelectedConfirmationId();
                    return cid || '_none';
                }

                function snapshotConfirmationApplicationDraft() {
                    var $form = $('#confirmationApplicationForm');
                    if (!$form.length) {
                        return;
                    }
                    cnApplicationDraftsByConfirmationId[confirmationApplicationDraftKey()] =
                        serializeConfirmationApplicationFormToObject();
                }

                function restoreConfirmationApplicationDraftForCurrentRow() {
                    var key = confirmationApplicationDraftKey();
                    var snap = cnApplicationDraftsByConfirmationId[key];
                    if (snap && Object.keys(snap).length) {
                        applyConfirmationApplicationFormObject(snap);
                    } else {
                        clearConfirmationApplicationFormFields();
                    }
                }

                /** Merges application + arancel payloads. Application JSON is built from confirmation_details + confirmationApplication; arancel from confirmation_details + confirmationArancel (server). */
                function mergeApplicationPayloads(dApp, dAr) {
                    var o = {};
                    if (dApp && typeof dApp === 'object') {
                        Object.keys(dApp).forEach(function(k) {
                            o[k] = dApp[k];
                        });
                    }
                    if (dAr && typeof dAr === 'object') {
                        Object.keys(dAr).forEach(function(k) {
                            o[k] = dAr[k];
                        });
                    }
                    return o;
                }

                var $mApp = $('#confirmationApplicationModal');
                var $fApp = $('#confirmationApplicationForm');

                if (!$mApp.length || !$fApp.length || !$appFormBtn.length || typeof bootstrap === 'undefined') {
                    return;
                }

                var bsCnAppModal = bootstrap.Modal.getOrCreateInstance($mApp[0]);
                var pendingFocusSelectorCnApp = null;

                $fApp.on('input change', 'input, textarea', function() {
                    clearTimeout(cnApplicationDraftSaveTimer);
                    cnApplicationDraftSaveTimer = setTimeout(function() {
                        snapshotConfirmationApplicationDraft();
                    }, 300);
                });

                $mApp.on('shown.bs.modal', function() {
                    $appFormBtn.attr('aria-expanded', 'true');
                    restoreConfirmationApplicationDraftForCurrentRow();
                    updateConfirmationArancelTotal();
                    if (pendingFocusSelectorCnApp) {
                        var $el = $mApp.find(pendingFocusSelectorCnApp).first();
                        if ($el.length) {
                            $el.trigger('focus');
                        }
                        pendingFocusSelectorCnApp = null;
                    }
                });

                $fApp.on('input change blur', '.sappcCnArTable input.sappcCnArAmt:not([name="total_payment"])', function() {
                    updateConfirmationArancelTotal();
                });

                $mApp.on('hidden.bs.modal', function() {
                    $appFormBtn.attr('aria-expanded', 'false');
                    snapshotConfirmationApplicationDraft();
                });

                window.sappcConfirmationApplicationFormOpen = function(open, opts) {
                    opts = opts || {};
                    if (open !== false) {
                        pendingFocusSelectorCnApp = opts.focusSelector || null;
                        bsCnAppModal.show();
                    } else {
                        bsCnAppModal.hide();
                    }
                };

                $appFormBtn.on('click', function(e) {
                    e.preventDefault();
                    var cid = getSelectedConfirmationId();
                    if (!cid) {
                        setSelectedConfirmationId('');
                        $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                        clearConfirmationApplicationFormFields();
                        $('#cnApplicationConfirmationId').val('');
                        if (typeof window.sappcConfirmationApplicationFormOpen === 'function') {
                            window.sappcConfirmationApplicationFormOpen(true, {});
                        } else {
                            bsCnAppModal.show();
                        }
                        return;
                    }
                    if (!confirmationAppDetailsUrl || !confirmationArancelDetailsUrl) {
                        sappcCnSwal({
                            icon: 'warning',
                            title: 'Not configured',
                            text: 'Application form is not configured.',
                        });
                        return;
                    }
                    $.when(
                        fetchJson(
                            buildQueryUrl(confirmationAppDetailsUrl, {
                                confirmation_id: cid,
                            }),
                            jsonHeaders
                        ),
                        fetchJson(
                            buildQueryUrl(confirmationArancelDetailsUrl, {
                                confirmation_id: cid,
                            }),
                            jsonHeaders
                        )
                    )
                        .done(function(resA, resB) {
                            var r1 = resA && resA[0] ? resA[0] : resA;
                            var r2 = resB && resB[0] ? resB[0] : resB;
                            if (r1 && r1.ok && r2 && r2.ok) {
                                var merged = mergeApplicationPayloads(r1.data || {}, r2.data || {});
                                applyConfirmationApplicationFormObject(merged);
                                $('#cnApplicationConfirmationId').val(cid);
                                cnApplicationDraftsByConfirmationId[String(cid)] =
                                    serializeConfirmationApplicationFormToObject();
                                if (typeof window.sappcConfirmationApplicationFormOpen === 'function') {
                                    window.sappcConfirmationApplicationFormOpen(true, {});
                                } else {
                                    bsCnAppModal.show();
                                }
                            } else {
                                sappcCnSwal({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Could not load the form data.',
                                });
                            }
                        })
                        .fail(function() {
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Error',
                                text: 'Could not load confirmation application and arancel.',
                            });
                        });
                });

                $fApp.on('submit', function(ev) {
                    ev.preventDefault();
                    if (!confirmationAppSaveUrl || !confirmationArancelSaveUrl) {
                        sappcCnSwal({
                            icon: 'warning',
                            title: 'Not configured',
                            text: 'Save URLs are not configured.',
                        });
                        return;
                    }
                    var cid = ($('#cnApplicationConfirmationId').val() || '').trim() || getSelectedConfirmationId();
                    var wn = cid ? parseInt(cid, 10) : 0;
                    if (cid && (isNaN(wn) || wn < 1)) {
                        sappcCnSwal({
                            icon: 'warning',
                            title: 'Invalid record',
                            text: 'Invalid record.',
                        });
                        return;
                    }
                    var arr = $fApp.serializeArray();
                    var payload = {};
                    $.each(arr, function(i, field) {
                        var n = field.name;
                        if (n.slice(-2) === '[]') {
                            var base = n.slice(0, -2);
                            if (!payload[base]) {
                                payload[base] = [];
                            }
                            payload[base].push(field.value);
                        } else if (payload[n] !== undefined) {
                            if (!Array.isArray(payload[n])) {
                                payload[n] = [payload[n]];
                            }
                            payload[n].push(field.value);
                        } else {
                            payload[n] = field.value;
                        }
                    });
                    var pApp = pickFields(payload, applicationFieldNames);
                    var pAr = pickFields(payload, arancelFieldNames);
                    if (wn > 0) {
                        pApp.confirmation_id = wn;
                        pAr.confirmation_id = wn;
                    }
                    var $saveBtn = $('#confirmationApplicationSaveBtn');
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(confirmationAppSaveUrl, pApp, csrf)
                        .done(function(r1) {
                            if (!r1 || !r1.ok) {
                                var m1 = r1 && r1.message ? r1.message : 'Application could not be saved.';
                                sappcCnSwal({
                                    icon: 'error',
                                    title: 'Error',
                                    text: m1,
                                });
                                $saveBtn.prop('disabled', false);
                                return;
                            }
                            var savedId = (r1.data && r1.data.confirmation_id != null) ?
                                parseInt(String(r1.data.confirmation_id), 10) :
                                wn;
                            if (!isNaN(savedId) && savedId > 0) {
                                setSelectedConfirmationId(String(savedId));
                                $('#cnApplicationConfirmationId').val(String(savedId));
                                pAr.confirmation_id = savedId;
                                if (typeof fetchRecords === 'function') {
                                    fetchRecords();
                                }
                            }
                            fetchPostJson(confirmationArancelSaveUrl, pAr, csrf)
                                .done(function(r2) {
                                    if (r2 && r2.ok) {
                                        var shouldReopenFromDashboard = isDashboardEmbeddedAppContext();
                                        var workflowId = (!isNaN(savedId) && savedId > 0) ? savedId : wn;
                                        cnApplicationDraftsByConfirmationId[String(workflowId)] =
                                            serializeConfirmationApplicationFormToObject();
                                        if (typeof bootstrap !== 'undefined' && $mApp.length) {
                                            var instM = bootstrap.Modal.getInstance($mApp[0]);
                                            if (instM) {
                                                instM.hide();
                                            }
                                        }
                                        var okMsg =
                                            r2 && r2.message ? r2.message : 'Confirmation application saved.';
                                        sappcCnSwal({
                                            icon: 'success',
                                            title: 'Saved',
                                            text: okMsg,
                                            confirmButtonText: 'OK',
                                        });
                                        if (shouldReopenFromDashboard) {
                                            setTimeout(function() {
                                                $('#confirmationApplicationFormBtn').trigger('click');
                                            }, 120);
                                        }
                                    } else {
                                        var m2 =
                                            r2 && r2.message ? r2.message : 'Arancel could not be saved.';
                                        sappcCnSwal({
                                            icon: 'error',
                                            title: 'Error',
                                            text: m2,
                                        });
                                    }
                                })
                                .fail(function(xhr) {
                                    var msg = 'Arancel could not be saved.';
                                    var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                                    if (d && d.message) {
                                        msg = d.message;
                                    }
                                    sappcCnSwal({
                                        icon: 'error',
                                        title: 'Error',
                                        text: msg,
                                    });
                                })
                                .always(function() {
                                    $saveBtn.prop('disabled', false);
                                });
                        })
                        .fail(function(xhr) {
                            var msg = 'Application could not be saved.';
                            var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (d && d.errors) {
                                var vals = Object.values(d.errors);
                                if (vals.length && Array.isArray(vals[0]) && vals[0][0]) {
                                    msg = vals[0][0];
                                }
                            } else if (d && d.message) {
                                msg = d.message;
                            }
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                            $saveBtn.prop('disabled', false);
                        });
                });
            })();

            (function initConfirmationCertificationModal() {
                var $certModal = $('#confirmationCertificationModal');
                var $certBtn = $('#confirmationCertificationBtn');
                var $certForm = $('#confirmationCertificationForm');
                if (!$certModal.length || !$certBtn.length || !$certForm.length || typeof bootstrap === 'undefined') {
                    return;
                }

                var certBsModal = bootstrap.Modal.getOrCreateInstance($certModal[0]);

                function applyConfirmationCertificationTopFromPayment(data) {
                    if (!data || typeof data !== 'object') return;
                    $('#cnCertRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
                    $('#cnCertClient').val(data.client != null ? String(data.client) : '');
                    $('#cnCertContact').val(
                        data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
                    );
                    $('#cnCertTopAddress').val(data.address != null ? String(data.address) : '');
                }

                function applyConfirmationCertificationFromDetails(data) {
                    if (!data || typeof data !== 'object') return;
                    $('#cnCertChildFirst').val(data.first_name != null ? String(data.first_name) : '');
                    $('#cnCertChildMiddle').val(data.middle_name != null ? String(data.middle_name) : '');
                    $('#cnCertChildLast').val(data.family_name != null ? String(data.family_name) : '');
                    $('#cnCertBirthday').val(data.date_of_birth != null ? String(data.date_of_birth) : '');
                    $('#cnCertBirthplace').val(data.place_of_birth != null ? String(data.place_of_birth) : '');
                    $('#cnCertFatherFirst').val(data.father_first_name != null ? String(data.father_first_name) : '');
                    $('#cnCertFatherMiddle').val(data.father_middle_name != null ? String(data.father_middle_name) : '');
                    $('#cnCertFatherLast').val(data.father_last_name != null ? String(data.father_last_name) : '');
                    $('#cnCertMotherFirst').val(data.mother_first_name != null ? String(data.mother_first_name) : '');
                    $('#cnCertMotherMiddle').val(data.mother_middle_name != null ? String(data.mother_middle_name) : '');
                    $('#cnCertMotherLast').val(data.mother_last_name != null ? String(data.mother_last_name) : '');
                    $('#cnCertBarangay').val(data.barangay != null ? String(data.barangay) : '');
                    $('#cnCertMunicipality').val(data.municipality != null ? String(data.municipality) : '');
                    $('#cnCertProvince').val(data.province != null ? String(data.province) : 'Antique');
                    $('#cnCertDateReceived').val(data.date_received != null ? String(data.date_received) : '');
                    $('#cnCertDateIssued').val(data.date_issued != null ? String(data.date_issued) : '');
                    $('#cnCertBookNo').val(data.book_no != null ? String(data.book_no) : '');
                    $('#cnCertRegisterNo').val(data.register_no != null ? String(data.register_no) : '');
                    $('#cnCertPageNo').val(data.page_no != null ? String(data.page_no) : '');
                    $('#cnCertPriest').val(data.priest != null ? String(data.priest) : '');
                    $('#cnCertSponsors').val(data.sponsors != null ? String(data.sponsors) : '');
                    $('#cnCertPurpose').val(data.purpose != null ? String(data.purpose) : '');
                }

                function loadConfirmationCertificationForRecord(id, doneFn, failFn) {
                    if (!id) {
                        return;
                    }
                    if (!paymentDetailsUrl || !certificationDetailsUrl) {
                        sappcCnSwal({
                            icon: 'warning',
                            title: 'Not configured',
                            text: 'Certification load is not configured.',
                        });
                        return;
                    }

                    setSelectedConfirmationId(id);
                    selectConfirmationTableRow(id);

                    ensureRegistryWorkflowStep('certification', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        $.when(
                            fetchJson(buildQueryUrl(paymentDetailsUrl, {
                                confirmation_id: id
                            }), jsonHeaders),
                            fetchJson(buildQueryUrl(certificationDetailsUrl, {
                                confirmation_id: id
                            }), jsonHeaders)
                        ).done(function(payTuple, certTuple) {
                            var pay = payTuple && payTuple[0] ? payTuple[0] : null;
                            var cert = certTuple && certTuple[0] ? certTuple[0] : null;
                            if (pay && pay.ok && pay.data) {
                                applyConfirmationCertificationTopFromPayment(pay.data);
                            }
                            if (cert && cert.ok && cert.data) {
                                applyConfirmationCertificationFromDetails(cert.data);
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
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        });
                    });
                }

                function showConfirmationCertificatePreview(id) {
                    loadConfirmationCertificationForRecord(id, function() {
                        if (typeof window.sappcShowCertificatePreview !== 'function') {
                            sappcCnSwal({
                                icon: 'info',
                                title: 'Certificate preview',
                                text: 'Certificate preview is not yet available for confirmation.',
                            });
                            return;
                        }
                        window.sappcShowCertificatePreview({
                            title: 'Confirmation Certification',
                            render: function(mountEl) {
                                mountEl.innerHTML =
                                    '<div class="alert alert-info mb-0 text-center">Confirmation certificate layout is not configured yet. Use the edit action to review or update certification details.</div>';
                            }
                        });
                    });
                }

                window.sappcShowConfirmationCertificatePreview = showConfirmationCertificatePreview;

                $certModal.on('shown.bs.modal', function() {
                    $certBtn.attr('aria-expanded', 'true');
                });
                $certModal.on('hidden.bs.modal', function() {
                    $certBtn.attr('aria-expanded', 'false');
                });

                $certBtn.on('click', function(e) {
                    e.preventDefault();
                    var cid = getSelectedConfirmationId();
                    if (!cid) {
                        certBsModal.show();
                        return;
                    }
                    loadConfirmationCertificationForRecord(cid, function() {
                        certBsModal.show();
                    }, function(msg) {
                        sappcCnSwal({
                            icon: 'error',
                            title: 'Error',
                            text: msg,
                        });
                    });
                });

                $certForm.on('submit', function(ev) {
                    ev.preventDefault();
                });
            })();

            var $scheduleForm = $('#confirmationScheduleRequestForm');
            var $scheduleBtn = $('#confirmationScheduleRequestBtn');
            var $scheduleNewBtn = $('#confirmationNewRecordBtn');
             var scheduleSaveUrl = $scheduleForm.attr('data-schedule-save-url') || $scheduleBtn.attr('data-schedule-save-url') || '';
            var scheduleReservedUrl = ($scheduleForm.attr('data-schedule-reserved-url') || '').trim();
            var calendarReservedLookup = {};
            var $scheduleModal = $('#confirmationScheduleRequestModal');
            var $calMonthSel = $('#cnCalMonth');
            var $calYearSel = $('#cnCalYear');
            var $calMonthNumEl = $('#cnCalMonthNum');
            var $calDayCells = $('#cnCalDayCells');
            var $scheduleDateInput = $('#cnScheduleDate');
            var $scheduleTimeInput = $('#cnScheduleTime24');

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
                setSelectedConfirmationId('');
                $('#cnScheduleRefCode').val($scheduleForm.attr('data-default-reference-code') || '');
                $('#cnScheduleContact').val('');
                $('#cnScheduleClient').val('');
                $('#cnScheduleAddress').val('');
                $('#cnScheduleSex').val('');
                $scheduleDateInput.val('');
                $scheduleTimeInput.val('10:00');
                $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                var sel = parseIsoDate($scheduleDateInput.val());
                if (sel) {
                    calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                } else {
                    var todayMonth = new Date();
                    calendarViewDate = new Date(todayMonth.getFullYear(), todayMonth.getMonth(), 1);
                }
                syncCalendarHeader();
                renderCalendarDayGrid();
            }

            function initScheduleCalendar() {
                if (!$calMonthSel.length || !$calYearSel.length || !$('#cnCalPrev').length || !$('#cnCalNext').length || !$calDayCells.length || !$scheduleDateInput.length) {
                    return;
                }
                populateCalendarSelectors();
                syncCalendarHeader();
                renderCalendarDayGrid();
                $('#cnCalPrev').on('click', function() {
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate.getMonth() - 1, 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $('#cnCalNext').on('click', function() {
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

            $('#confirmationTableBody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a,button').length) return;
                var $tr = $(this);
                if ($tr.hasClass('sappc-table-loading') || $tr.hasClass('sappc-table-empty')) return;
                if ($tr.hasClass('is-schedule-selected')) {
                    if ($scheduleForm.length) {
                        resetScheduleRequestFormForNewEntry();
                    } else {
                        $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                        setSelectedConfirmationId('');
                    }
                    return;
                }
                $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $tr.addClass('is-schedule-selected');
                if (($tr.attr('data-document-type') || '').trim() !== 'Confirmation') {
                    setSelectedConfirmationId('');
                    return;
                }
                setSelectedConfirmationId($tr.attr('data-record-id') || '');
                if (activeSection !== 'schedule' || !$scheduleForm.length) {
                    return;
                }
                var $tds = $tr.find('td');
                if ($tds.length < 6) return;
                $('#cnScheduleRefCode').val(($tds.eq(1).text() || '').trim());
                $('#cnScheduleClient').val(($tds.eq(2).text() || '').trim());
                $('#cnScheduleAddress').val(($tds.eq(3).text() || '').trim());
                var rawSex = ($tds.eq(4).text() || '').trim();
                if (rawSex === '\u2014' || rawSex === '-' || rawSex === '') {
                    $('#cnScheduleSex').val('');
                } else {
                    $('#cnScheduleSex').val(rawSex);
                }
                var rawContact = ($tds.eq(5).text() || '').trim();
                $('#cnScheduleContact').val(
                    (rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' : formatPhMobileDisplay(rawContact)
                );
            });

            if ($scheduleForm.length && scheduleSaveUrl) {
                $scheduleForm.on('submit', function(e) {
                    e.preventDefault();
                    var cid = getSelectedConfirmationId();
                    var payload = {
                        schedule_date: $('#cnScheduleDate').val(),
                        schedule_time: $('#cnScheduleTime24').val(),
                        client: ($('#cnScheduleClient').val() || '').trim(),
                        sex: ($('#cnScheduleSex').val() || '').trim(),
                        contact_number: sappcPhMobileDigitsOnly($('#cnScheduleContact').val()),
                        address: ($('#cnScheduleAddress').val() || '').trim(),
                        reference_code: ($('#cnScheduleRefCode').val() || '').trim(),
                    };
                    if (cid) {
                        var n = parseInt(cid, 10);
                        if (!isNaN(n)) payload.confirmation_id = n;
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
                                var okMsgCn =
                                    res && res.message ? String(res.message) : 'Schedule reserved successfully.';
                                sappcCnSwal({
                                    icon: 'success',
                                    title: 'Reserved',
                                    text: okMsgCn,
                                    confirmButtonText: 'OK',
                                });
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
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Cannot save schedule',
                                text: msg,
                            });
                        })
                        .always(function() {
                            $submitBtn.prop('disabled', false);
                        });
                });
            }

            function applyConfirmationScheduleDetailsToForm(d) {
                if (!d || typeof d !== 'object') return;
                if (d.confirmation_id != null && String(d.confirmation_id).trim() !== '') {
                    setSelectedConfirmationId(String(d.confirmation_id).trim());
                }
                $('#cnScheduleRefCode').val(d.reference_code != null ? String(d.reference_code) : '');
                $('#cnScheduleClient').val(d.client != null ? String(d.client) : '');
                $('#cnScheduleAddress').val(d.address != null ? String(d.address) : '');
                $('#cnScheduleSex').val(d.sex != null ? String(d.sex) : '');
                var cn = d.contact_number != null ? String(d.contact_number).trim() : '';
                $('#cnScheduleContact').val(cn !== '' ? formatPhMobileDisplay(cn) : '');
                var sd = d.schedule_date != null ? String(d.schedule_date).trim().slice(0, 10) : '';
                $('#cnScheduleDate').val(sd);
                var st = d.schedule_time != null ? String(d.schedule_time).trim() : '';
                if (st.length >= 5) {
                    st = st.slice(0, 5);
                }
                $('#cnScheduleTime24').val(st || '10:00');
            }

            function syncConfirmationScheduleModalCalendarFromInputs() {
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

            function onConfirmationScheduleToolbarClick() {
                var cid = getSelectedConfirmationId();
                var $sel = $('#confirmationTableBody tr.is-schedule-selected');
                if (!cid && $sel.length) {
                    var doc = ($sel.attr('data-document-type') || '').trim();
                    if (doc === 'Confirmation') {
                        var rid = ($sel.attr('data-record-id') || '').trim();
                        if (rid) {
                            setSelectedConfirmationId(rid);
                            cid = rid;
                        }
                    }
                }
                if (!cid) {
                    resetScheduleRequestFormForNewEntry();
                }
            }

            $scheduleBtn.on('click', onConfirmationScheduleToolbarClick);
            $scheduleNewBtn.on('click', onConfirmationScheduleToolbarClick);

            if ($scheduleModal.length) {
                $scheduleModal.on('show.bs.modal', function(e) {
                    var cid = getSelectedConfirmationId();
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
                    var cid = getSelectedConfirmationId();
                    if (cid && scheduleDetailsUrl) {
                        fetchJson(buildQueryUrl(scheduleDetailsUrl, {
                            confirmation_id: cid,
                        }), jsonHeaders)
                            .done(function(res) {
                                if (res && res.ok && res.data) {
                                    applyConfirmationScheduleDetailsToForm(res.data);
                                }
                            })
                            .fail(function(xhr) {
                                var msg = 'Could not load schedule details.';
                                var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                                if (data && data.message) {
                                    msg = String(data.message);
                                }
                                sappcCnSwal({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg,
                                });
                            })
                            .always(function() {
                                syncConfirmationScheduleModalCalendarFromInputs();
                            });
                    } else {
                        syncConfirmationScheduleModalCalendarFromInputs();
                    }
                });
                $scheduleModal.on('hidden.bs.modal', function() {
                    if ($scheduleBtn.length) $scheduleBtn.attr('aria-expanded', 'false');
                    if ($scheduleNewBtn.length) $scheduleNewBtn.attr('aria-expanded', 'false');
                });
            }

            $('#confirmationTableBody').on('click', '.sappc-icon-action--delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = ($(this).attr('data-record-id') || '').trim();
                if (!id || !confirmationDeleteUrl) {
                    return;
                }

                function runDelete() {
                    fetchPostJson(
                        confirmationDeleteUrl,
                        {
                            confirmation_id: parseInt(id, 10),
                        },
                        csrf
                    )
                        .done(function(res) {
                            if (res && res.ok) {
                                delete cnApplicationDraftsByConfirmationId[String(id)];
                                if (getSelectedConfirmationId() === id) {
                                    setSelectedConfirmationId('');
                                }
                                if (($('#cnApplicationConfirmationId').val() || '').trim() === id) {
                                    $('#cnApplicationConfirmationId').val('');
                                }
                                var msg = res && res.message ? res.message : 'Removed.';
                                sappcCnSwal({
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
                            if (data && data.message) {
                                msg = data.message;
                            }
                            sappcCnSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        });
                }

                sappcCnConfirmDeleteDocument({
                    title: 'Delete confirmation record?',
                    text: 'This permanently deletes this confirmation row from the registry and removes related rows in confirmation details.',
                    confirmButtonText: 'Yes, delete',
                }, runDelete);
            });

            if (initialTablePayload) {
                renderTable(initialTablePayload);
                tryOpenRecordFromWorkflowQuery();
                tryOpenConfirmationApplicationFromDashboardQuery();
            } else {
                fetchRecords();
            }
        });
    })();
</script>
