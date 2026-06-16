@php
    $initialTablePayload = $initialTablePayload ?? null;
    $activeSection = $activeSection ?? 'schedule';
@endphp
<script>
    (function() {
        'use strict';

        var initialTablePayload = @json($initialTablePayload);
        var activeSection = @json($activeSection);

        function getSelectedWeddingId() {
            var cid = ($('#wdSelectedWeddingId').val() || '').trim();
            if (cid) {
                return cid;
            }
            cid = ($('#wdScheduleWeddingId').val() || '').trim();
            if (cid) {
                return cid;
            }
            var $sel = $('#weddingTableBody tr.is-schedule-selected');
            if ($sel.length) {
                return ($sel.first().attr('data-record-id') || '').trim();
            }
            return '';
        }

        function setSelectedWeddingId(id) {
            id = id == null ? '' : String(id).trim();
            $('#wdSelectedWeddingId').val(id);
            if ($('#wdScheduleWeddingId').length) {
                $('#wdScheduleWeddingId').val(id);
            }
        }

        function registryWorkflowNextUrl(currentStep) {
            var $panel = $('#weddingRecordsPanel');
            if (!$panel.length) {
                return '';
            }
            var hasCert = ($panel.attr('data-workflow-has-certification') || '0') === '1';
            var steps = hasCert ?
                ['application', 'payment', 'certification', 'schedule'] :
                ['application', 'payment', 'schedule'];
            var idx = steps.indexOf(currentStep);
            if (idx < 0 || idx >= steps.length - 1) {
                return '';
            }
            return ($panel.attr('data-workflow-' + steps[idx + 1] + '-url') || '').trim();
        }

        function advanceRegistryWorkflow(currentStep, recordId) {
            var url = registryWorkflowNextUrl(currentStep);
            var id = String(recordId == null ? getSelectedWeddingId() : recordId).trim();
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
                    setSelectedWeddingId(id);
                    $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                    $('#weddingTableBody tr').each(function() {
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
            var msg = messageText ||
                'Complete payment first. All fees must be marked Paid before you can continue to the next step.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Payment required',
                    text: msg,
                    confirmButtonText: 'OK'
                });
            } else {
                window.alert(msg);
            }
        }

        function swalRegistryCertificationRequired(messageText) {
            var msg = messageText ||
                'Complete and save the certification first before you can continue to the next step.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Certification required',
                    text: msg,
                    confirmButtonText: 'OK'
                });
            } else {
                window.alert(msg);
            }
        }

        function registryWorkflowHasCertification() {
            return ($('#weddingRecordsPanel').attr('data-workflow-has-certification') || '0') === '1';
        }

        function workflowChecksForStep(targetStep) {
            var checks = [];
            if (registryWorkflowHasCertification() && targetStep === 'schedule') {
                checks.push('certification');
            }
            return checks;
        }

        function ensureRegistryPaymentComplete(recordId, thenFn) {
            if (typeof thenFn === 'function') thenFn(true);
        }

        function ensureRegistryCertificationSaved(recordId, thenFn) {
            recordId = String(recordId == null ? '' : recordId).trim();
            if (!recordId || !registryWorkflowHasCertification()) {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            var certUrl = ($('#weddingRecordsPanel').attr('data-certification-details-url') || '').trim();
            if (!certUrl) {
                if (typeof thenFn === 'function') thenFn(true);
                return;
            }
            fetchJson(buildQueryUrl(certUrl, {
                wedding_id: recordId
            }), {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }).done(function(res) {
                var saved = !!(res && res.ok && (res.certification_saved === true || res.has_saved_cert ===
                    true));
                if (saved) {
                    if (typeof thenFn === 'function') thenFn(true);
                    return;
                }
                swalRegistryCertificationRequired(res && res.message ? String(res.message) : '');
                if (typeof thenFn === 'function') thenFn(false);
            }).fail(function(xhr) {
                var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                swalRegistryCertificationRequired(data && data.message ? String(data.message) : '');
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
            if (check === 'certification') {
                ensureRegistryCertificationSaved(recordId, function(ok) {
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

        function sappcWdSwal(cfg) {
            if (typeof window !== 'undefined' && typeof window.Swal !== 'undefined') {
                return window.Swal.fire(cfg);
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

        function sappcWdConfirm(cfg) {
            cfg = cfg || {};
            if (typeof window !== 'undefined' && typeof window.Swal !== 'undefined') {
                return window.Swal.fire({
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

        function sappcWdConfirmDeleteDocument(firstCfg, onFinalConfirm) {
            sappcWdConfirm(firstCfg).then(function(r) {
                if (!r.isConfirmed) {
                    return;
                }
                sappcWdConfirm({
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

        function sappcSwalSelectWeddingRowFirst() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a record',
                    text: 'Select a wedding row in the table first.',
                    confirmButtonText: 'OK',
                });
            } else {
                window.alert('Select a wedding row in the table first.');
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

        $(document).on('input', '#wdScheduleContact, #wdPaymentContact, #wdCertContact', function() {
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
                '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="' + viewLabel + '" aria-label="' + viewLabel + '" data-record-id="' +
                esc(recordId) +
                '"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--edit" title="Edit" aria-label="Edit record" data-record-id="' +
                esc(recordId) +
                '"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>' +
                '<button type="button" class="sappc-icon-action sappc-icon-action--delete" title="Delete" aria-label="Delete record" data-record-id="' +
                esc(recordId) +
                '"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>' +
                '</div></td>';
        }

        function rowHtml(row) {
            var base = '<tr data-record-id="' + esc(row.recordId) + '" data-document-type="' + esc(row
                .documentType) + '">' +
                '<td>' + esc(row.rowNumber) + '</td>' +
                '<td>' + esc(row.referenceCode) + '</td>' +
                '<td>' + esc(row.client) + '</td>' +
                '<td>' + esc(typeof sappcFormatAddress === 'function' ? sappcFormatAddress(row.address) : row
                    .address) + '</td>';
            return base + '<td>' + esc(row.contactNum) + '</td>' +
                (activeSection === 'payment' ? '<td>' + paymentStatusCell(row.paymentStatus) + '</td>' : '') +
                '<td>' + esc(row.dateCreated) + '</td>' +
                rowActionCell(row.recordId) + '</tr>';
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

            (function applyWeddingFieldFormatGuides() {
                function ph(sel, val) {
                    var $el = $(sel);
                    if ($el.length) {
                        $el.attr('placeholder', val);
                    }
                }
                ['Groom', 'Bride'].forEach(function(p) {
                    ph('#wdApp' + p + 'Name', 'Cruz, Juan D.');
                    ph('#wdApp' + p + 'Pob', 'Barbaza, Antique');
                    ph('#wdApp' + p + 'Address', 'Street, Barangay, Municipality');
                    ph('#wdApp' + p + 'Father', 'Juan D. Cruz');
                    ph('#wdApp' + p + 'Mother', 'Maria D. Cruz');
                    ph('#wdApp' + p + 'Religion', 'Roman Catholic');
                    ph('#wdApp' + p + 'BapPlace', 'Parish church name');
                    ph('#wdApp' + p + 'Contact', '09XX XXX XXXX');
                });
                ph('#wdAppCivilMarriagePlace', 'Municipality / registry office');
                ph('#wdAppChurchWeddingPlace', 'St. Anthony of Padua Parish, Barbaza');
                ph('#wdAppOfficiatingPriest', 'Rev. name');
                ph('#wdAppSponsorLine1', 'Juan D. Cruz');
                ph('#wdAppSponsorLine2', 'Maria D. Cruz');
                ph('#wdAppSponsorLine3', 'Juan D. Cruz');
                ph('#wdCertChildFirst', 'Juan');
                ph('#wdCertChildMiddle', 'D.');
                ph('#wdCertChildLast', 'Cruz');
                ph('#wdCertBirthplace', 'Barbaza, Antique');
                ph('#wdCertFatherFirst', 'Juan');
                ph('#wdCertFatherMiddle', 'D.');
                ph('#wdCertFatherLast', 'Cruz');
                ph('#wdCertMotherFirst', 'Maria');
                ph('#wdCertMotherMiddle', 'D.');
                ph('#wdCertMotherLast', 'Cruz');
                ph('#wdCertPriest', 'Rev. name');
                ph('#wdCertSponsors', 'Juan D. Cruz; Maria D. Cruz');
                ph('#wdCertPurpose', 'e.g. civil registry, visa');
            })();

            var DEFAULT_CERT_PURPOSE = 'For all legal purposes';

            var $panel = $('#weddingRecordsPanel');
            if (!$panel.length) return;

            var tableColspan = parseInt($panel.attr('data-table-colspan'), 10);
            if (isNaN(tableColspan) || tableColspan < 1) {
                tableColspan = 7;
            }

            var csrf = getMetaCsrf();
            var jsonHeaders = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };

            var recordsUrl = ($panel.attr('data-records-url') || '').trim();
            var registrySection = ($panel.attr('data-section') || activeSection || '').trim();
            var nextReferenceUrl = ($panel.attr('data-next-reference-url') || '').trim();

            function fetchNextReferenceCode(done) {
                if (typeof done !== 'function') {
                    return;
                }
                if (!nextReferenceUrl) {
                    done('');
                    return;
                }
                fetchJson(nextReferenceUrl, jsonHeaders)
                    .done(function(res) {
                        done(res && res.reference_code != null ? String(res.reference_code) : '');
                    })
                    .fail(function() {
                        done('');
                    });
            }

            function ensureCertificationReferenceCode($refInput, $form, doneFn) {
                if (!$refInput.length) {
                    if (typeof doneFn === 'function') {
                        doneFn('');
                    }
                    return;
                }
                var current = ($refInput.val() || '').trim();
                if (current) {
                    if (typeof doneFn === 'function') {
                        doneFn(current);
                    }
                    return;
                }
                fetchNextReferenceCode(function(ref) {
                    var code = ref || ($form && $form.length ? ($form.attr('data-default-reference-code') || '').trim() : '');
                    if (code && $form && $form.length) {
                        $form.attr('data-default-reference-code', code);
                        $refInput.val(code);
                    }
                    if (typeof doneFn === 'function') {
                        doneFn(code);
                    }
                });
            }
            var paymentDetailsUrl = ($panel.attr('data-payment-details-url') || '').trim();
            var paymentSaveUrlPanel = ($panel.attr('data-payment-save-url') || '').trim();
            var $weddingAppFormBtn = $('#weddingApplicationFormBtn');
            var marriageAppDetailsUrl = ($panel.attr('data-marriage-application-details-url') ||
                $weddingAppFormBtn.attr(
                    'data-marriage-application-details-url') || '').trim();
            var marriageAppSaveUrl = ($panel.attr('data-marriage-application-save-url') ||
                $weddingAppFormBtn.attr(
                    'data-marriage-application-save-url') || '').trim();
            var weddingDeleteUrl = ($panel.attr('data-wedding-delete-url') || '').trim();
            var scheduleDetailsUrl = ($panel.attr('data-schedule-details-url') || '').trim();
            var certificationDetailsUrl = ($panel.attr('data-certification-details-url') || '').trim();
            var certificationSaveUrl = ($panel.attr('data-certification-save-url') || '').trim();

            function selectWeddingTableRow(id) {
                setSelectedWeddingId(id);
                $('#wdMarriageAppWeddingId').val(id);
                $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $('#weddingTableBody tr').each(function() {
                    if (($(this).attr('data-record-id') || '').trim() === id) {
                        $(this).addClass('is-schedule-selected');
                        return false;
                    }
                });
            }

            var fetchRecords = function() {};

            if (recordsUrl) {
                function tryOpenWeddingApplicationFromDashboardQuery() {
                    try {
                        var u = new URL(window.location.href);
                        var id = (u.searchParams.get('sappc_dash_app') || '').trim();
                        if (!id) {
                            return;
                        }
                        u.searchParams.delete('sappc_dash_app');
                        var q = u.searchParams.toString();
                        window.history.replaceState({}, '', u.pathname + (q ? '?' + q : '') + u.hash);
                        setSelectedWeddingId(id);
                        $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                        $('#weddingTableBody tr').each(function() {
                            if (($(this).attr('data-record-id') || '').trim() === id) {
                                $(this).addClass('is-schedule-selected');
                                return false;
                            }
                        });
                        setTimeout(function() {
                            $('#weddingApplicationFormBtn').trigger('click');
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

                tryOpenWeddingApplicationFromDashboardQuery();

                var meta0 = (initialTablePayload && initialTablePayload.meta) ? initialTablePayload.meta :
                    {};
                var state = {
                    page: meta0.current_page || 1,
                    per_page: meta0.per_page || 10,
                    search: '',
                    letter: '',
                    date_from: '',
                    date_to: '',
                };

                var $searchInput = $('#weddingSearch');
                var $body = $('#weddingTableBody');
                var $info = $('#weddingTableFooterInfo');
                var $nav = $('#weddingPagination');

                function renderTable(res) {
                    var html = '';
                    if (!res || !res.data || !res.data.length) {
                        html =
                            '<tr class="sappc-table-empty"><td colspan="' + tableColspan +
                            '" class="text-center text-muted py-4">No records found.</td></tr>';
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

                fetchRecords = function() {
                    $body.html(
                        '<tr class="sappc-table-loading"><td colspan="' + tableColspan +
                        '" class="text-center text-muted py-4">Loading...</td></tr>'
                    );
                    var reqUrl = buildQueryUrl(recordsUrl, {
                        page: state.page,
                        per_page: state.per_page,
                        search: state.search,
                        letter: state.letter,
                        date_from: state.date_from,
                        date_to: state.date_to,
                        registry_type: 'wedding',
                        registry_section: registrySection,
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
                            tryOpenWeddingApplicationFromDashboardQuery();
                        })
                        .fail(function(xhr, textStatus, errorThrown) {
                            var msg =
                                (xhr && xhr.status) ||
                                errorThrown ||
                                textStatus ||
                                '?';
                            $body.html(
                                '<tr><td colspan="' + tableColspan +
                                '" class="text-center text-danger py-3">Could not load records (' +
                                esc(String(msg)) +
                                ').</td></tr>'
                            );
                        });
                };

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

                $('#weddingEntries').on('change', function() {
                    state.per_page = parseInt($(this).val(), 10) || 10;
                    state.page = 1;
                    fetchRecords();
                });

                $panel.find('.sappc-toolbar-date-strip_btn').on('click', function() {
                    state.date_from = $('#weddingDateFrom').val() || '';
                    state.date_to = $('#weddingDateTo').val() || '';
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

                var $reloadBtn = $('#weddingReloadBtn');
                $panel.closest('.sappc-registry-page').find(
                    '.sappc-registry-toolbar a.sappc-registry-toolbar_btn[data-workflow-step]').on(
                    'click',
                    function(e) {
                        var step = ($(this).attr('data-workflow-step') || '').trim();
                        var cid = getSelectedWeddingId();
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

                $('#weddingTableBody').on('click', '.sappc-icon-action--delete', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var id = ($(this).attr('data-record-id') || '').trim();
                    if (!id || !weddingDeleteUrl) return;

                    function runDelete() {
                        fetchPostJson(
                                weddingDeleteUrl, {
                                    wedding_id: parseInt(id, 10),
                                },
                                csrf
                            )
                            .done(function(res) {
                                if (res && res.ok) {
                                    if (getSelectedWeddingId() === id) {
                                        setSelectedWeddingId('');
                                    }
                                    if (($('#wdMarriageAppWeddingId').val() || '').trim() ===
                                        id) {
                                        $('#wdMarriageAppWeddingId').val('');
                                    }
                                    var msg = res && res.message ? res.message : 'Removed.';
                                    sappcWdSwal({
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
                                sappcWdSwal({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg,
                                });
                            });
                    }

                    sappcWdConfirmDeleteDocument({
                        title: 'Delete wedding record?',
                        text: 'This permanently deletes this wedding row from the registry (including schedule and marriage application data).',
                        confirmButtonText: 'Yes, delete',
                    }, runDelete);
                });

                function openWeddingSectionRecord(id) {
                    if (!id) return;
                    selectWeddingTableRow(id);
                    if (activeSection === 'schedule') {
                        ensureRegistryWorkflowStep('schedule', id, function(ok) {
                            if (!ok) {
                                return;
                            }
                            if (typeof bootstrap !== 'undefined' && $(
                                    '#weddingScheduleRequestModal').length) {
                                bootstrap.Modal.getOrCreateInstance($(
                                    '#weddingScheduleRequestModal')[0]).show();
                            }
                        });
                        return;
                    }
                    if (activeSection === 'payment') {
                        ensureRegistryWorkflowStep('payment', id, function(ok) {
                            if (!ok) {
                                return;
                            }
                            $('#weddingPaymentFeeBtn').trigger('click');
                        });
                        return;
                    }
                    if (activeSection === 'certification') {
                        ensureRegistryWorkflowStep('certification', id, function(ok) {
                            if (!ok) {
                                return;
                            }
                            $('#weddingCertificationBtn').trigger('click');
                        });
                        return;
                    }
                    if (!marriageAppDetailsUrl) {
                        sappcWdSwal({
                            icon: 'warning',
                            title: 'Not configured',
                            text: 'Marriage application is not configured.'
                        });
                        return;
                    }
                    $('#weddingApplicationFormBtn').trigger('click');
                }
                window.sappcRegistryWorkflowOpenRecord = openWeddingSectionRecord;

                $('#weddingTableBody').on('click', '.sappc-icon-action--view', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var id = ($(this).attr('data-record-id') || '').trim();
                        if (activeSection === 'certification') {
                            if (typeof window.sappcShowWeddingCertificatePreview === 'function') {
                                window.sappcShowWeddingCertificatePreview(id);
                            } else {
                                window.alert('Certificate preview is not available on this page.');
                            }
                            return;
                        }
                        openWeddingSectionRecord(id);
                    });

                $('#weddingTableBody').on('click', '.sappc-icon-action--edit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        openWeddingSectionRecord(($(this).attr('data-record-id') || '').trim());
                    });

                if (initialTablePayload) {
                    renderTable(initialTablePayload);
                    tryOpenRecordFromWorkflowQuery();
                    tryOpenWeddingApplicationFromDashboardQuery();
                } else {
                    fetchRecords();
                }

            }

            var $paymentModal = $('#weddingPaymentFeeModal');
            var $paymentBtn = $('#weddingPaymentFeeBtn');
            var $paymentFeeForm = $('#weddingPaymentFeeForm');
            var $feeItemsBody = $('#weddingPaymentFeeItemsBody');
            var $addFeeBtn = $('#weddingPaymentFeeAddItemBtn');

            function renumberConfirmationFeeRows() {
                $feeItemsBody.find('[data-fee-row]').each(function(i) {
                    $(this).find('.sappcPaymentFeeModalCellNo').text(i + 1);
                    $(this).find('.sappcPaymentFeeModalItemInput').attr('aria-label', 'Fee item ' +
                        (i + 1));
                });
            }

            function syncWeddingPaymentFeeRowBadgeColors($row) {
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

            function syncAllWeddingPaymentFeeRowBadgeColors() {
                $feeItemsBody.find('[data-fee-row]').each(function() {
                    syncWeddingPaymentFeeRowBadgeColors($(this));
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
                    var paid = $row.find('.sappcPaymentFeeModalStatus').hasClass(
                        'sappcPaymentFeeModalStatusPaid');
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
                    $status.removeClass('sappcPaymentFeeModalStatusUnpaid').addClass(
                        'sappcPaymentFeeModalStatusPaid').text('Paid');
                    $toggle.removeClass('sappcPaymentFeeModalTogglePaid').addClass(
                        'sappcPaymentFeeModalToggleUnpaid').text('Unpaid');
                    if (dateIso) {
                        $date.attr('data-date-paid', dateIso);
                        $date.text(formatPaymentFeeDateDisplay(dateIso));
                    } else {
                        var today = new Date().toISOString().slice(0, 10);
                        $date.attr('data-date-paid', today);
                        $date.text(formatPaymentFeeDateDisplay(today));
                    }
                } else {
                    $status.removeClass('sappcPaymentFeeModalStatusPaid').addClass(
                        'sappcPaymentFeeModalStatusUnpaid').text('Unpaid');
                    $toggle.removeClass('sappcPaymentFeeModalToggleUnpaid').addClass(
                        'sappcPaymentFeeModalTogglePaid').text('Paid');
                    $date.removeAttr('data-date-paid');
                    $date.text('\u2014');
                }
                syncWeddingPaymentFeeRowBadgeColors($tr);
                return $tr;
            }

            function serializeConfirmationPaymentFeeToObject() {
                return {
                    reference_code: ($('#wdPaymentRefCode').val() || '').trim(),
                    client: ($('#wdPaymentClient').val() || '').trim(),
                    contact_number: sappcPhMobileDigitsOnly($('#wdPaymentContact').val()),
                    address: ($('#wdPaymentAddress').val() || '').trim(),
                    fee_rows: collectConfirmationPaymentFeeRowsFromDom(),
                };
            }

            function applyConfirmationPaymentFeeFormObject(data) {
                if (!data || typeof data !== 'object') return;
                $('#wdPaymentRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
                $('#wdPaymentClient').val(data.client != null ? String(data.client) : '');
                $('#wdPaymentContact').val(
                    data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) :
                    ''
                );
                $('#wdPaymentAddress').val(data.address != null ? String(data.address) : '');
                var feeRows = data.fee_rows;
                if (!Array.isArray(feeRows) || !feeRows.length) {
                    feeRows = [{}];
                }
                $feeItemsBody.empty();
                feeRows.forEach(function(fr) {
                    $feeItemsBody.append(buildConfirmationPaymentFeeRowFromData(fr));
                });
                renumberConfirmationFeeRows();
                syncAllWeddingPaymentFeeRowBadgeColors();
            }

            function resetWeddingPaymentFormForNewEntry() {
                setSelectedWeddingId('');
                $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                fetchNextReferenceCode(function(ref) {
                    var code = ref || ($paymentFeeForm.attr('data-default-reference-code') || '').trim();
                    if ($paymentFeeForm.length && code) {
                        $paymentFeeForm.attr('data-default-reference-code', code);
                    }
                    applyConfirmationPaymentFeeFormObject({
                        reference_code: code,
                        client: '',
                        contact_number: '',
                        address: '',
                        fee_rows: null,
                    });
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
                    $status.removeClass('sappcPaymentFeeModalStatusPaid').addClass(
                        'sappcPaymentFeeModalStatusUnpaid').text('Unpaid');
                    $btn.removeClass('sappcPaymentFeeModalToggleUnpaid').addClass(
                        'sappcPaymentFeeModalTogglePaid').text('Paid');
                    $date.removeAttr('data-date-paid');
                    $date.text('\u2014');
                } else {
                    var iso = new Date().toISOString().slice(0, 10);
                    $status.addClass('sappcPaymentFeeModalStatusPaid').removeClass(
                        'sappcPaymentFeeModalStatusUnpaid').text('Paid');
                    $btn.removeClass('sappcPaymentFeeModalTogglePaid').addClass(
                        'sappcPaymentFeeModalToggleUnpaid').text('Unpaid');
                    $date.attr('data-date-paid', iso);
                    $date.text(formatPaymentFeeDateDisplay(iso));
                }
                syncWeddingPaymentFeeRowBadgeColors($row);
            });

            if ($paymentModal.length && $paymentBtn.length && typeof bootstrap !== 'undefined') {
                var paymentBsModal = bootstrap.Modal.getOrCreateInstance($paymentModal[0]);

                $paymentModal.on('shown.bs.modal', function() {
                    $paymentBtn.attr('aria-expanded', 'true');
                    syncAllWeddingPaymentFeeRowBadgeColors();
                });
                $paymentModal.on('hidden.bs.modal', function() {
                    $paymentBtn.attr('aria-expanded', 'false');
                });

                $paymentBtn.on('click', function(e) {
                    e.preventDefault();
                    var cid = getSelectedWeddingId();
                    if (!cid) {
                        resetWeddingPaymentFormForNewEntry();
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
                                wedding_id: cid
                            }), jsonHeaders)
                            .done(function(res) {
                                if (res && res.ok && res.data) {
                                    applyConfirmationPaymentFeeFormObject(res.data);
                                    paymentBsModal.show();
                                }
                            })
                            .fail(function(xhr) {
                                var msg = 'Could not load payment details.';
                                var data = xhr && xhr.responseJSON ? xhr.responseJSON :
                                    null;
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
                    var saveUrl = ($paymentFeeForm.attr('data-save-url') || paymentSaveUrlPanel ||
                        '').trim();
                    if (!saveUrl) return;
                    var cid = getSelectedWeddingId();
                    var payload = serializeConfirmationPaymentFeeToObject();
                    if (cid) {
                        payload.wedding_id = parseInt(cid, 10);
                        if (isNaN(payload.wedding_id)) {
                            window.alert('Invalid record.');
                            return;
                        }
                    }
                    var $saveBtn = $('#weddingPaymentFeeSaveBtn');
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(saveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                if (res.data && res.data.wedding_id) {
                                    setSelectedWeddingId(String(res.data.wedding_id));
                                }
                                if (typeof bootstrap !== 'undefined' && $paymentModal.length) {
                                    var inst = bootstrap.Modal.getInstance($paymentModal[0]);
                                    if (inst) inst.hide();
                                }
                                var msg = (res && res.message) ? res.message :
                                    'Payment record saved.';
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
                                if (vals.length && Array.isArray(vals[0]) && vals[0][0]) msg =
                                    vals[0][0];
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

            (function initWeddingCertificationModal() {
                var $certModal = $('#weddingCertificationModal');
                var $certBtn = $('#weddingCertificationBtn');
                var $certForm = $('#weddingCertificationForm');

                function applyWeddingCertificationTopFromPayment(data) {
                    if (!data || typeof data !== 'object') return;
                    $('#wdCertRefCode').val(data.reference_code != null ? String(data.reference_code) :
                        '');
                    $('#wdCertClient').val(data.client != null ? String(data.client) : '');
                    $('#wdCertContact').val(
                        data.contact_number != null ? formatPhMobileDisplay(String(data
                            .contact_number)) : ''
                    );
                    $('#wdCertTopAddress').val(
                        data.address != null ?
                        (typeof sappcFormatAddress === 'function' ? sappcFormatAddress(String(data
                            .address)) : String(data.address)) :
                        ''
                    );
                    ensureCertificationReferenceCode($('#wdCertRefCode'), $certForm);
                }

                function applyWeddingCertificationFromDetails(data) {
                    if (!data || typeof data !== 'object') return;
                    $('#wdCertChildFirst').val(data.first_name != null ? String(data.first_name) : '');
                    $('#wdCertChildMiddle').val(data.middle_name != null ? String(data.middle_name) :
                        '');
                    $('#wdCertChildLast').val(data.family_name != null ? String(data.family_name) : '');
                    $('#wdCertBirthday').val(data.date_of_birth != null ? String(data.date_of_birth) :
                        '');
                    $('#wdCertBirthplace').val(data.place_of_birth != null ? String(data
                        .place_of_birth) : '');
                    $('#wdCertFatherFirst').val(data.father_first_name != null ? String(data
                        .father_first_name) : '');
                    $('#wdCertFatherMiddle').val(data.father_middle_name != null ? String(data
                        .father_middle_name) : '');
                    $('#wdCertFatherLast').val(data.father_last_name != null ? String(data
                        .father_last_name) : '');
                    $('#wdCertMotherFirst').val(data.mother_first_name != null ? String(data
                        .mother_first_name) : '');
                    $('#wdCertMotherMiddle').val(data.mother_middle_name != null ? String(data
                        .mother_middle_name) : '');
                    $('#wdCertMotherLast').val(data.mother_last_name != null ? String(data
                        .mother_last_name) : '');
                    $('#wdCertBarangay').val(data.barangay != null ? String(data.barangay) : '');
                    $('#wdCertMunicipality').val(data.municipality != null ? String(data.municipality) :
                        '');
                    $('#wdCertProvince').val(data.province != null ? String(data.province) : 'Antique');
                    $('#wdCertDateReceived').val(data.date_received != null ? String(data
                        .date_received) : '');
                    $('#wdCertDateIssued').val(data.date_issued != null ? String(data.date_issued) :
                    '');
                    $('#wdCertBookNo').val(data.book_no != null ? String(data.book_no) : '');
                    $('#wdCertRegisterNo').val(data.register_no != null ? String(data.register_no) :
                    '');
                    $('#wdCertPageNo').val(data.page_no != null ? String(data.page_no) : '');
                    $('#wdCertPriest').val(data.priest != null ? String(data.priest) : '');
                    $('#wdCertSponsors').val(data.sponsors != null ? String(data.sponsors) : '');
                    $('#wdCertPurpose').val(
                        data.purpose != null && String(data.purpose).trim() !== '' ?
                        String(data.purpose) :
                        DEFAULT_CERT_PURPOSE
                    );
                }

                function stashWeddingCertPrintExtras(data) {
                    if (!data || typeof data !== 'object') return;
                    var bride = data.bride && typeof data.bride === 'object' ? data.bride : {};
                    var marriage = data.marriage && typeof data.marriage === 'object' ? data.marriage :
                    {};
                    var rh = data.registry_header && typeof data.registry_header === 'object' ? data
                        .registry_header : {};
                    $panel.data('weddingCertPrintExtra', {
                        bride: bride,
                        marriage: marriage,
                        registry_header: rh,
                        groom_sex: data.groom_sex || 'Male',
                        bride_sex: data.bride_sex || 'Female',
                        groom_citizenship: data.groom_citizenship || 'Filipino',
                        bride_citizenship: data.bride_citizenship || 'Filipino',
                        groom_age: data.groom_age || '',
                        bride_age: data.bride_age || '',
                        groom_religion: data.groom_religion || '',
                        bride_religion: data.bride_religion || '',
                        groom_civil_status: data.groom_civil_status || '',
                        bride_civil_status: data.bride_civil_status || '',
                    });
                }

                function wdCertField(sel) {
                    return ($(sel).val() || '').toString().trim();
                }

                function wdCertJoinThree(selF, selM, selL) {
                    return [wdCertField(selF), wdCertField(selM), wdCertField(selL)].filter(function(
                    p) {
                        return p !== '';
                    }).join(' ');
                }

                function wdCertJoinNameObj(o) {
                    o = o || {};
                    return [o.first_name, o.middle_name, o.family_name].map(function(x) {
                        return x != null ? String(x).trim() : '';
                    }).filter(function(x) {
                        return x !== '';
                    }).join(' ');
                }

                function wdCertJoinAddr(b, mu, pr) {
                    return [b, mu, pr].map(function(x) {
                        return x != null ? String(x).trim() : '';
                    }).filter(function(x) {
                        return x !== '';
                    }).join(', ');
                }

                function wdCertFormatLongDate(iso) {
                    if (!iso || String(iso).length < 10) return '';
                    var p = String(iso).slice(0, 10).split('-');
                    if (p.length !== 3) return '';
                    var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                        'August', 'September', 'October', 'November', 'December'
                    ];
                    var mi = parseInt(p[1], 10) - 1;
                    var mon = (mi >= 0 && mi < 12) ? months[mi] : p[1];
                    return mon + ' ' + String(parseInt(p[2], 10)) + ', ' + p[0];
                }

                function wdCertComputeAge(iso) {
                    if (!iso || String(iso).length < 10) return '';
                    var p = String(iso).slice(0, 10).split('-');
                    if (p.length !== 3) return '';
                    var y = parseInt(p[0], 10),
                        mo = parseInt(p[1], 10),
                        d = parseInt(p[2], 10);
                    if (isNaN(y) || isNaN(mo) || isNaN(d)) return '';
                    var bd = new Date(y, mo - 1, d);
                    var now = new Date();
                    var age = now.getFullYear() - bd.getFullYear();
                    var dm = now.getMonth() - bd.getMonth();
                    if (dm < 0 || (dm === 0 && now.getDate() < bd.getDate())) {
                        age--;
                    }
                    if (age < 0 || age > 120) return '';
                    return String(age);
                }

                var marriagePrintWindow = null;
                var marriagePrintBlobUrl = '';

                function applyMarriagePrintDataToClone(root, printData) {
                    function set(id, val) {
                        var el = root.querySelector('#' + id);
                        if (el) {
                            el.textContent = val != null ? String(val) : '';
                        }
                    }
                    set('mcHdrProvince', printData.hdr_province);
                    set('mcHdrCity', printData.hdr_city);
                    set('mcHdrRegistry', printData.hdr_registry);
                    set('mcHName', printData.h_name);
                    set('mcWName', printData.w_name);
                    set('mcHDob', printData.h_dob);
                    set('mcWDob', printData.w_dob);
                    set('mcHAge', printData.h_age ? 'Age: ' + printData.h_age : '');
                    set('mcWAge', printData.w_age ? 'Age: ' + printData.w_age : '');
                    set('mcHPob', printData.h_pob);
                    set('mcWPob', printData.w_pob);
                    set('mcHSex', printData.h_sex);
                    set('mcWSex', printData.w_sex);
                    set('mcHCitz', printData.h_citz);
                    set('mcWCitz', printData.w_citz);
                    set('mcHRes', printData.h_res);
                    set('mcWRes', printData.w_res);
                    set('mcHRel', printData.h_rel);
                    set('mcWRel', printData.w_rel);
                    set('mcHCivil', printData.h_civil);
                    set('mcWCivil', printData.w_civil);
                    set('mcHFather', printData.h_father);
                    set('mcWFather', printData.w_father);
                    set('mcHFatherCitz', printData.h_father_citz);
                    set('mcWFatherCitz', printData.w_father_citz);
                    set('mcHMother', printData.h_mother);
                    set('mcWMother', printData.w_mother);
                    set('mcHMotherCitz', printData.h_mother_citz);
                    set('mcWMotherCitz', printData.w_mother_citz);
                    set('mcHConsentName', printData.h_consent_name);
                    set('mcWConsentName', printData.w_consent_name);
                    set('mcHConsentRel', printData.h_consent_rel);
                    set('mcWConsentRel', printData.w_consent_rel);
                    set('mcHConsentRes', printData.h_consent_res);
                    set('mcWConsentRes', printData.w_consent_res);
                    set('mcPlaceMarriage', printData.marriage_place);
                    set('mcDateMarriage', printData.marriage_date);
                    set('mcTimeMarriage', printData.marriage_time);
                    set('mcSolemnizer', printData.solemnizer);
                    set('mc18HName', printData.h_name);
                    set('mc18WName', printData.w_name);
                    (function fillWitnessLines(raw) {
                        var s = raw != null ? String(raw) : '';
                        var parts = s.split(/\r\n|\r|\n|;/g).map(function(x) {
                            return x.trim();
                        }).filter(Boolean);
                        var i;
                        for (i = 0; i < 4; i++) {
                            set('mcWitness' + (i + 1), parts[i] || '');
                        }
                    })(printData.witnesses);
                    set('mcBookNo', printData.book_no);
                    set('mcPageNo', printData.page_no);
                    set('mcRegisterNo', printData.register_no);
                    set('mcDateIssued', printData.date_issued);
                    set('mcPurpose', printData.purpose);
                    set('mcRefCode', printData.ref_code);
                }

                function mountMarriageCertificatePreview(mountEl, printData) {
                    var tplNode = document.getElementById('marriageCertificatePrintableTemplate');
                    if (!tplNode || !tplNode.content || !mountEl) {
                        window.alert('Certificate preview template not found.');
                        return false;
                    }
                    var tplStyleNode = tplNode.content.querySelector('style');
                    var tplFrameNode = tplNode.content.querySelector('.mc-screen-frame');
                    if (!tplStyleNode || !tplFrameNode) {
                        window.alert('Certificate preview template is incomplete.');
                        return false;
                    }

                    mountEl.innerHTML = '';

                    var styleEl = document.createElement('style');
                    styleEl.textContent = tplStyleNode.textContent || '';
                    mountEl.appendChild(styleEl);

                    var frameEl = document.createElement('div');
                    frameEl.className = 'sappcChristeningCertFrame';

                    var sheetHolder = document.createElement('div');
                    sheetHolder.className = 'sappcCertPreviewSheet sappcCertPreviewSheet--marriage';

                    var tplFrameClone = tplFrameNode.cloneNode(true);
                    applyMarriagePrintDataToClone(tplFrameClone, printData);
                    sheetHolder.appendChild(tplFrameClone);
                    frameEl.appendChild(sheetHolder);
                    mountEl.appendChild(frameEl);
                    return true;
                }

                function loadWeddingCertificationForRecord(id, doneFn, failFn) {
                    if (!id) {
                        return;
                    }
                    if (!paymentDetailsUrl || !certificationDetailsUrl) {
                        window.alert('Certification load is not configured.');
                        return;
                    }

                    setSelectedWeddingId(id);
                    selectWeddingTableRow(id);

                    ensureRegistryWorkflowStep('certification', id, function(ok) {
                        if (!ok) {
                            return;
                        }
                        $.when(
                            fetchJson(buildQueryUrl(paymentDetailsUrl, {
                                wedding_id: id
                            }), jsonHeaders),
                            fetchJson(buildQueryUrl(certificationDetailsUrl, {
                                wedding_id: id
                            }), jsonHeaders)
                        ).done(function(payTuple, certTuple) {
                            var pay = payTuple && payTuple[0] ? payTuple[0] : null;
                            var cert = certTuple && certTuple[0] ? certTuple[0] : null;
                            if (pay && pay.ok && pay.data) {
                                applyWeddingCertificationTopFromPayment(pay.data);
                            }
                            if (cert && cert.ok && cert.data) {
                                applyWeddingCertificationFromDetails(cert.data);
                                stashWeddingCertPrintExtras(cert.data);
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

                function showWeddingCertificatePreview(id) {
                    loadWeddingCertificationForRecord(id, function() {
                        if (typeof window.sappcShowCertificatePreview !== 'function') {
                            window.alert('Certificate preview is not available on this page.');
                            return;
                        }
                        window.sappcShowCertificatePreview({
                            title: 'Certificate of Marriage',
                            render: function(mountEl) {
                                mountMarriageCertificatePreview(mountEl, collectMarriageCertificatePrintData());
                            },
                            onPrint: function() {
                                printMarriageCertificateSheet(null, true);
                            }
                        });
                    });
                }

                function collectMarriageCertificatePrintData() {
                    var extra = $panel.data('weddingCertPrintExtra') || {};
                    var bride = extra.bride || {};
                    var marriage = extra.marriage || {};
                    var rh = extra.registry_header || {};
                    var hDobIso = wdCertField('#wdCertBirthday');
                    var wDobIso = (bride.date_of_birth != null ? String(bride.date_of_birth) : '')
                        .slice(0, 10);
                    var hAge = extra.groom_age ? String(extra.groom_age) : wdCertComputeAge(hDobIso);
                    var wAge = extra.bride_age ? String(extra.bride_age) : wdCertComputeAge(wDobIso);
                    var marriageDateStr = marriage.date ? wdCertFormatLongDate(marriage.date) : '';
                    if (!marriageDateStr) marriageDateStr = wdCertFormatLongDate(wdCertField(
                        '#wdCertDateReceived'));

                    return {
                        hdr_province: (rh.province || '').trim() || wdCertField('#wdCertProvince') ||
                            'Antique',
                        hdr_city: (rh.city_municipality || '').trim() || wdCertField(
                            '#wdCertMunicipality') || '',
                        hdr_registry: wdCertField('#wdCertRegisterNo'),
                        h_name: wdCertJoinThree('#wdCertChildFirst', '#wdCertChildMiddle',
                            '#wdCertChildLast'),
                        w_name: wdCertJoinNameObj(bride),
                        h_dob: wdCertFormatLongDate(hDobIso),
                        w_dob: wdCertFormatLongDate(wDobIso),
                        h_age: hAge,
                        w_age: wAge,
                        h_pob: wdCertField('#wdCertBirthplace'),
                        w_pob: (bride.place_of_birth != null ? String(bride.place_of_birth) : '')
                        .trim(),
                        h_sex: extra.groom_sex || 'Male',
                        w_sex: extra.bride_sex || 'Female',
                        h_citz: extra.groom_citizenship || 'Filipino',
                        w_citz: extra.bride_citizenship || 'Filipino',
                        h_res: wdCertJoinAddr(wdCertField('#wdCertBarangay'), wdCertField(
                            '#wdCertMunicipality'), wdCertField('#wdCertProvince')),
                        w_res: wdCertJoinAddr(bride.barangay, bride.municipality, bride.province),
                        h_rel: extra.groom_religion || '',
                        w_rel: extra.bride_religion || '',
                        h_civil: extra.groom_civil_status || '',
                        w_civil: extra.bride_civil_status || '',
                        h_father: wdCertJoinThree('#wdCertFatherFirst', '#wdCertFatherMiddle',
                            '#wdCertFatherLast'),
                        w_father: wdCertJoinNameObj({
                            first_name: bride.father_first_name,
                            middle_name: bride.father_middle_name,
                            family_name: bride.father_last_name,
                        }),
                        h_mother: wdCertJoinThree('#wdCertMotherFirst', '#wdCertMotherMiddle',
                            '#wdCertMotherLast'),
                        w_mother: wdCertJoinNameObj({
                            first_name: bride.mother_first_name,
                            middle_name: bride.mother_middle_name,
                            family_name: bride.mother_last_name,
                        }),
                        h_father_citz: 'Filipino',
                        w_father_citz: 'Filipino',
                        h_mother_citz: 'Filipino',
                        w_mother_citz: 'Filipino',
                        h_consent_name: '',
                        w_consent_name: '',
                        h_consent_rel: '',
                        w_consent_rel: '',
                        h_consent_res: '',
                        w_consent_res: '',
                        marriage_place: (marriage.place != null ? String(marriage.place) : '').trim(),
                        marriage_date: marriageDateStr,
                        marriage_time: (marriage.time != null ? String(marriage.time) : '').trim(),
                        solemnizer: wdCertField('#wdCertPriest'),
                        witnesses: wdCertField('#wdCertSponsors'),
                        book_no: wdCertField('#wdCertBookNo'),
                        page_no: wdCertField('#wdCertPageNo'),
                        register_no: wdCertField('#wdCertRegisterNo'),
                        date_issued: wdCertFormatLongDate(wdCertField('#wdCertDateIssued')),
                        purpose: wdCertField('#wdCertPurpose'),
                        ref_code: wdCertField('#wdCertRefCode'),
                    };
                }

                function printMarriageCertificateSheet(printWin, shouldPrint) {
                    var tplNode = document.getElementById('marriageCertificatePrintableTemplate');
                    if (!tplNode || !tplNode.content) {
                        window.alert('Print template not found.');
                        return false;
                    }
                    var tplStyleNode = tplNode.content.querySelector('style');
                    var tplWrapNode = tplNode.content.querySelector('.mc-wrap');
                    if (!tplStyleNode || !tplWrapNode) {
                        window.alert('Print template is incomplete.');
                        return false;
                    }
                    var openedHere = false;
                    if (!printWin && marriagePrintWindow && !marriagePrintWindow.closed) {
                        printWin = marriagePrintWindow;
                    }
                    if (!printWin || printWin.closed) {
                        printWin = window.open('', 'sappcMarriageCertificatePrint');
                        openedHere = true;
                    }
                    if (!printWin) {
                        window.alert('Pop-up blocked. Please allow pop-ups to print the certificate.');
                        return false;
                    }
                    marriagePrintWindow = printWin;
                    shouldPrint = shouldPrint !== false;
                    var printData = collectMarriageCertificatePrintData();
                    var tplWrapClone = tplWrapNode.cloneNode(true);
                    applyMarriagePrintDataToClone(tplWrapClone, printData);
                    var html =
                        '<!doctype html><html><head><meta charset="utf-8"><title>Certificate of Marriage</title><style>' +
                        (tplStyleNode.textContent || '') +
                        '</style></head><body>' + (tplWrapClone.outerHTML || '') + '</body></html>';
                    if (marriagePrintBlobUrl) {
                        try {
                            URL.revokeObjectURL(marriagePrintBlobUrl);
                        } catch (eRev) {}
                    }
                    marriagePrintBlobUrl = URL.createObjectURL(new Blob([html], {
                        type: 'text/html',
                    }));
                    var didPrint = false;

                    function populateAndPrint() {
                        if (didPrint) return;
                        didPrint = true;
                        printWin.focus();
                        if (shouldPrint) {
                            setTimeout(function() {
                                printWin.print();
                            }, 150);
                        }
                    }
                    printWin.onload = populateAndPrint;
                    printWin.location.href = marriagePrintBlobUrl;
                    setTimeout(populateAndPrint, openedHere ? 900 : 700);
                    return true;
                }

                window.sappcReloadMarriagePrintWindow = function(printWin) {
                    return printMarriageCertificateSheet(printWin || marriagePrintWindow, false);
                };

                function saveWeddingCertificationRecord() {
                    var wid = getSelectedWeddingId();
                    if (!certificationSaveUrl) {
                        return $.Deferred().reject({
                            responseJSON: {
                                message: 'Certification save is not configured.'
                            }
                        }).promise();
                    }

                    var payload = {
                        wedding_id: wid ? parseInt(wid, 10) : null,
                        reference_code: wdCertField('#wdCertRefCode'),
                        client: wdCertField('#wdCertClient'),
                        contact_number: sappcPhMobileDigitsOnly(wdCertField('#wdCertContact')),
                        top_address: wdCertField('#wdCertTopAddress'),
                        child_first_name: wdCertField('#wdCertChildFirst'),
                        child_middle_name: wdCertField('#wdCertChildMiddle'),
                        child_last_name: wdCertField('#wdCertChildLast'),
                        birthday: wdCertField('#wdCertBirthday'),
                        birthplace: wdCertField('#wdCertBirthplace'),
                        father_first_name: wdCertField('#wdCertFatherFirst'),
                        father_middle_name: wdCertField('#wdCertFatherMiddle'),
                        father_last_name: wdCertField('#wdCertFatherLast'),
                        mother_first_name: wdCertField('#wdCertMotherFirst'),
                        mother_middle_name: wdCertField('#wdCertMotherMiddle'),
                        mother_last_name: wdCertField('#wdCertMotherLast'),
                        barangay: wdCertField('#wdCertBarangay'),
                        municipality: wdCertField('#wdCertMunicipality'),
                        province: wdCertField('#wdCertProvince'),
                        date_received: wdCertField('#wdCertDateReceived'),
                        priest: wdCertField('#wdCertPriest'),
                        sponsors: wdCertField('#wdCertSponsors'),
                        purpose: wdCertField('#wdCertPurpose'),
                        book_no: wdCertField('#wdCertBookNo'),
                        register_no: wdCertField('#wdCertRegisterNo'),
                        page_no: wdCertField('#wdCertPageNo'),
                        date_issued: wdCertField('#wdCertDateIssued'),
                    };

                    return fetchPostJson(certificationSaveUrl, payload, csrf);
                }

                function runWeddingCertificationSaveAndPrint($btn) {
                    $btn = ($btn && $btn.length) ? $btn : $('#wdCertAddRecordBtn');
                    $btn.prop('disabled', true);
                    saveWeddingCertificationRecord()
                        .done(function(res) {
                            if (!res || res.ok === false) {
                                var badMsg = (res && res.message) ? res.message :
                                    'Certification could not be saved.';
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: badMsg
                                    });
                                } else {
                                    window.alert(badMsg);
                                }
                                return;
                            }
                            printMarriageCertificateSheet(null, true);
                            var msg = (res && res.message) ? res.message :
                                'Certification record saved.';
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
                }

                window.sappcShowWeddingCertificatePreview = showWeddingCertificatePreview;

                if (!$certModal.length || !$certBtn.length || !$certForm.length || typeof bootstrap ===
                    'undefined') {
                    return;
                }

                var certBsModal = bootstrap.Modal.getOrCreateInstance($certModal[0]);

                $certForm.on('submit', function(e) {
                    e.preventDefault();
                    runWeddingCertificationSaveAndPrint($('#wdCertAddRecordBtn'));
                });

                $('#wdCertAddRecordBtn').on('click', function(e) {
                    e.preventDefault();
                    runWeddingCertificationSaveAndPrint($(this));
                });

                $certModal.on('shown.bs.modal', function() {
                    $certBtn.attr('aria-expanded', 'true');
                });
                $certModal.on('hidden.bs.modal', function() {
                    $certBtn.attr('aria-expanded', 'false');
                });

                $certBtn.on('click', function(e) {
                    e.preventDefault();
                    var wid = getSelectedWeddingId();
                    if (!wid) {
                        setSelectedWeddingId('');
                        $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                        if ($certForm.length && $certForm[0]) {
                            $certForm[0].reset();
                        }
                        ensureCertificationReferenceCode($('#wdCertRefCode'), $certForm, function() {
                            certBsModal.show();
                        });
                        return;
                    }
                    loadWeddingCertificationForRecord(wid, function() {
                        ensureCertificationReferenceCode($('#wdCertRefCode'), $certForm, function() {
                            certBsModal.show();
                        });
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
            })();

            (function initMarriageApplicationModal() {
                var $marriageAppModal = $('#weddingMarriageApplicationModal');
                var $marriageAppForm = $('#weddingMarriageApplicationForm');
                var $marriageAppBtn = $('#weddingApplicationFormBtn');
                if (!$marriageAppModal.length || !$marriageAppForm.length || !$marriageAppBtn.length) {
                    return;
                }

                function fieldByName($f, n) {
                    if (!$f.length) return $();
                    return $f.find('[name="' + String(n).replace(/\\/g, '\\\\').replace(/"/g, '\\"') +
                        '"]');
                }

                function isDashboardEmbeddedAppContextLocal() {
                    try {
                        var u = new URL(window.location.href);
                        return (u.searchParams.get('embed') || '').trim() === '1';
                    } catch (e1) {
                        return false;
                    }
                }

                function applyMarriageApplicationData(data) {
                    if (!data || typeof data !== 'object') {
                        return;
                    }
                    var $f = $marriageAppForm;
                    $f.find('input[type="checkbox"]').each(function() {
                        var n = this.name;
                        if (!n) {
                            return;
                        }
                        var v = data[n];
                        $(this).prop('checked', v === 1 || v === '1' || v === true || v ===
                            'on');
                    });
                    $f.find('input:not([type=checkbox]), textarea, select').each(function() {
                        var n = this.name;
                        if (!n || n === '_token' || n.indexOf('precana[') === 0) {
                            return;
                        }
                        if (n.indexOf('marriage_sponsors[') === 0) {
                            return;
                        }
                        if (data[n] == null) {
                            return;
                        }
                        if (this.getAttribute('readonly') != null) {
                            return;
                        }
                        $(this).val(String(data[n]));
                    });
                    if (Array.isArray(data.precana)) {
                        data.precana.forEach(function(row, i) {
                            if (i < 0 || i > 6 || !row || typeof row !== 'object') {
                                return;
                            }
                            if (row.date) {
                                fieldByName($f, 'precana[' + i + '][date]').val(String(row
                                    .date));
                            }
                            if (row.topic) {
                                fieldByName($f, 'precana[' + i + '][topic]').val(String(row
                                    .topic));
                            }
                            if (row.signature) {
                                fieldByName($f, 'precana[' + i + '][signature]').val(String(row
                                    .signature));
                            }
                        });
                    }
                    if (data.sponsors && !data.sponsors_line1) {
                        var lines = String(data.sponsors).split(/\r?\n/);
                        fieldByName($f, 'sponsors_line1').val((lines[0] || '').trim());
                        if (lines[1]) {
                            fieldByName($f, 'sponsors_line2').val(String(lines[1]).trim());
                        }
                        if (lines[2]) {
                            fieldByName($f, 'sponsors_line3').val(String(lines[2]).trim());
                        }
                    }
                    if (data.marriage_sponsors && typeof data.marriage_sponsors === 'object') {
                        for (var g = 1; g <= 40; g++) {
                            var v = data.marriage_sponsors[String(g)];
                            if (v == null) {
                                v = data.marriage_sponsors[g];
                            }
                            if (v != null) {
                                fieldByName($f, 'marriage_sponsors[' + g + ']').val(String(v));
                            }
                        }
                    }
                }

                function collectMarriageApplicationPayload() {
                    var $f = $marriageAppForm;
                    var out = {};
                    $f.find('input, textarea, select').each(function() {
                        var $el = $(this);
                        var n = this.name;
                        if (!n || n === '_token' || n.indexOf('precana[') === 0 || n.indexOf(
                                'marriage_sponsors[') ===
                            0) {
                            return;
                        }
                        if (this.type === 'checkbox') {
                            if ($el.is(':checked')) {
                                out[n] = this.value;
                            }
                            return;
                        }
                        if (this.getAttribute('readonly') != null) {
                            return;
                        }
                        out[n] = $el.val() == null ? '' : String($el.val());
                    });
                    out.precana = [];
                    for (var i = 0; i < 7; i++) {
                        out.precana.push({
                            date: (fieldByName($f, 'precana[' + i + '][date]').val() || '')
                                .trim(),
                            topic: (fieldByName($f, 'precana[' + i + '][topic]').val() || '')
                                .trim(),
                            signature: (fieldByName($f, 'precana[' + i + '][signature]')
                            .val() || '').trim()
                        });
                    }
                    out.marriage_sponsors = {};
                    for (var g = 1; g <= 40; g++) {
                        out.marriage_sponsors[String(g)] = (fieldByName($f, 'marriage_sponsors[' + g +
                                ']').val() ||
                            '').trim();
                    }
                    return out;
                }

                $marriageAppModal.on('shown.bs.modal', function() {
                    $marriageAppBtn.attr('aria-expanded', 'true');
                });
                $marriageAppModal.on('hidden.bs.modal', function() {
                    $marriageAppBtn.attr('aria-expanded', 'false');
                });

                $marriageAppBtn.on('click', function(e) {
                    e.preventDefault();
                    if (typeof bootstrap === 'undefined') {
                        window.alert('Bootstrap is required for this dialog.');
                        return;
                    }
                    var cid = getSelectedWeddingId();
                    if (!cid) {
                        setSelectedWeddingId('');
                        $('#weddingTableBody tr.is-schedule-selected').removeClass(
                            'is-schedule-selected');
                        if ($marriageAppForm[0]) {
                            $marriageAppForm[0].reset();
                        }
                        $marriageAppForm.find('input[type=checkbox]').prop('checked', false);
                        $('#wdMarriageAppWeddingId').val('');
                        applyMarriageApplicationData({});
                        bootstrap.Modal.getOrCreateInstance($marriageAppModal[0]).show();
                        return;
                    }
                    if (!marriageAppDetailsUrl) {
                        window.alert('Marriage application is not configured.');
                        return;
                    }
                    var marriageBsModal = bootstrap.Modal.getOrCreateInstance($marriageAppModal[
                        0]);
                    fetchJson(buildQueryUrl(marriageAppDetailsUrl, {
                            wedding_id: cid
                        }), jsonHeaders)
                        .done(function(res) {
                            if (res && res.ok) {
                                if ($marriageAppForm[0]) {
                                    $marriageAppForm[0].reset();
                                }
                                $marriageAppForm.find('input[type=checkbox]').prop(
                                    'checked', false);
                                $('#wdMarriageAppWeddingId').val(cid);
                                applyMarriageApplicationData(res.data || {});
                                marriageBsModal.show();
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Could not load marriage application.';
                            var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (d && d.message) {
                                msg = d.message;
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

                function saveWeddingMarriageApplication() {
                    if (!marriageAppSaveUrl) {
                        return;
                    }
                    var wid = ($('#wdMarriageAppWeddingId').val() || '').trim() ||
                    getSelectedWeddingId();
                    var wn = wid ? parseInt(wid, 10) : 0;
                    if (wid && (isNaN(wn) || wn < 1)) {
                        window.alert('Invalid record.');
                        return;
                    }
                    var payload = collectMarriageApplicationPayload();
                    if (wn > 0) {
                        payload.wedding_id = wn;
                    }
                    var $saveBtn = $('#weddingMarriageAppSaveBtn');
                    var marriageBsModal =
                        (typeof bootstrap !== 'undefined' && $marriageAppModal.length) ?
                        bootstrap.Modal.getOrCreateInstance($marriageAppModal[0]) :
                        null;

                    function showWeddingApplicationSavedMessage(messageText) {
                        var msg = messageText || 'Marriage application saved.';
                        sappcWdSwal({
                            icon: 'success',
                            title: 'Saved',
                            text: msg,
                            confirmButtonText: 'OK',
                        });
                    }

                    $saveBtn.prop('disabled', true);
                    fetchPostJson(marriageAppSaveUrl, payload, csrf)
                        .done(function(res, textStatus, jqXhr) {
                            var isHttpOk = !!(jqXhr && jqXhr.status >= 200 && jqXhr.status < 300);
                            var isBusinessOk = !!(res && (res.ok === true || String(res.status ||
                                '').toLowerCase() === 'success'));
                            if (!isHttpOk || !isBusinessOk) {
                                var failMsg = (res && res.message) ? String(res.message) :
                                    'Could not save marriage application.';
                                sappcWdSwal({
                                    icon: 'error',
                                    title: 'Error',
                                    text: failMsg,
                                });
                                return;
                            }

                            var savedId = (res.data && res.data.wedding_id != null) ?
                                String(res.data.wedding_id).trim() :
                                (wn > 0 ? String(wn) : '');
                            if (savedId) {
                                setSelectedWeddingId(savedId);
                                $('#wdMarriageAppWeddingId').val(savedId);
                                if (typeof fetchRecords === 'function') {
                                    fetchRecords();
                                }
                            }

                            var shouldReopenFromDashboard = isDashboardEmbeddedAppContextLocal();
                            var msg = (res && res.message) ? res.message :
                                'Marriage application saved.';
                            var didNotifySaved = false;

                            function notifySavedOnce() {
                                if (didNotifySaved) {
                                    return;
                                }
                                didNotifySaved = true;
                                showWeddingApplicationSavedMessage(msg);
                                if (shouldReopenFromDashboard) {
                                    setTimeout(function() {
                                        $('#weddingApplicationFormBtn').trigger('click');
                                    }, 120);
                                }
                            }
                            notifySavedOnce();
                            if ($marriageAppModal.length && marriageBsModal) {
                                $marriageAppModal.one('hidden.bs.modal', function() {
                                    notifySavedOnce();
                                });
                                marriageBsModal.hide();
                            }
                        })
                        .fail(function(xhr) {
                            var msg = 'Could not save marriage application.';
                            var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                            if (d && d.errors) {
                                var vals = Object.values(d.errors);
                                if (vals.length && Array.isArray(vals[0]) && vals[0][0]) {
                                    msg = vals[0][0];
                                }
                            } else if (d && d.message) {
                                msg = d.message;
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
                }

                $marriageAppForm.on('submit', function(e) {
                    e.preventDefault();
                    saveWeddingMarriageApplication();
                });

                $('#weddingMarriageAppSaveBtn').on('click', function(e) {
                    e.preventDefault();
                    saveWeddingMarriageApplication();
                });
            })();

            var $scheduleForm = $('#weddingScheduleRequestForm');
            var $scheduleBtn = $('#weddingScheduleRequestBtn');
            var $scheduleNewBtn = $('#weddingNewRecordBtn');
            var scheduleSaveUrl = $scheduleForm.attr('data-schedule-save-url') || $scheduleBtn.attr(
                'data-schedule-save-url') || '';
            var scheduleReservedUrl = ($scheduleForm.attr('data-schedule-reserved-url') || '').trim();
            var calendarReservedLookup = {};
            var $scheduleModal = $('#weddingScheduleRequestModal');
            var $calMonthSel = $('#wdCalMonth');
            var $calYearSel = $('#wdCalYear');
            var $calMonthNumEl = $('#wdCalMonthNum');
            var $calDayCells = $('#wdCalDayCells');
            var $scheduleDateInput = $('#wdScheduleDate');
            var $scheduleTimeInput = $('#wdScheduleTime24');

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

            function formatTime12h(hhmm) {
                var raw = (hhmm || '').trim();
                if (!raw) return '';
                var parts = raw.split(':');
                if (parts.length < 2) return raw;
                var h = parseInt(parts[0], 10);
                var m = parseInt(parts[1], 10);
                if (isNaN(h) || isNaN(m)) return raw;
                var ampm = h >= 12 ? 'PM' : 'AM';
                var h12 = h % 12;
                if (h12 === 0) h12 = 12;
                return h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm;
            }

            function buildReservedDayCaptionHtml(timeText) {
                var time = (timeText || '').trim();
                var html = '<span class="sappcScheduleDayReserved">Reserved</span>';
                if (time) {
                    html += '<span class="sappcScheduleDayWhen">' + esc(time) + '</span>';
                }
                return html;
            }

            function reservedLookupTime(value) {
                if (value === true || value === false || value == null) return '';
                return String(value).trim();
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
                    if (res && res.ok && res.by_date && typeof res.by_date === 'object') {
                        Object.keys(res.by_date).forEach(function(d) {
                            calendarReservedLookup[d] = res.by_date[d] || true;
                        });
                    } else if (res && res.ok && res.dates && res.dates.length) {
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
                    var isSel = selected && selected.getFullYear() === year && selected.getMonth() ===
                        month && selected.getDate() === day;
                    var reservedEntry = calendarReservedLookup[iso];
                    var isReserved = !!reservedEntry;
                    if (isSel) {
                        classes += ' is-selected';
                    } else if (isReserved) {
                        classes += ' is-reserved';
                    }
                    var captionTime = reservedLookupTime(reservedEntry);
                    if (isSel && !captionTime) {
                        captionTime = formatTime12h($scheduleTimeInput.val());
                    }
                    var label = monthNameFromIndex(month) + ' ' + day + ', ' + year;
                    if (isSel || isReserved) {
                        label += ', reserved';
                        if (captionTime) label += ' ' + captionTime;
                    }
                    var inner;
                    if (isSel || isReserved) {
                        inner = '<span class="sappcScheduleDayNum" aria-hidden="true">' + day +
                            '</span><span class="sappcScheduleDayLabel" aria-hidden="true">' +
                            buildReservedDayCaptionHtml(captionTime) + '</span>';
                    } else {
                        inner = String(day);
                    }
                    html += '<button type="button" class="' + classes + '" data-date="' + esc(iso) +
                        '" aria-label="' + esc(label) + '">' + inner + '</button>';
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
                setSelectedWeddingId('');
                $('#wdScheduleContact').val('');
                $('#wdScheduleClient').val('');
                $('#wdScheduleAddress').val('');
                $('#wdScheduleSex').val('');
                $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
                $scheduleTimeInput.val('10:00');
                $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                fetchNextReferenceCode(function(ref) {
                    var code = ref || ($scheduleForm.attr('data-default-reference-code') || '');
                    if (code) {
                        $scheduleForm.attr('data-default-reference-code', code);
                    }
                    $('#wdScheduleRefCode').val(code);
                });
                var sel = parseIsoDate($scheduleDateInput.val());
                if (sel) {
                    calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                }
                syncCalendarHeader();
                renderCalendarDayGrid();
            }

            function initScheduleCalendar() {
                if (!$calMonthSel.length || !$calYearSel.length || !$('#wdCalPrev').length || !$(
                        '#wdCalNext').length || !$calDayCells.length || !$scheduleDateInput.length) {
                    return;
                }
                populateCalendarSelectors();
                syncCalendarHeader();
                renderCalendarDayGrid();
                $('#wdCalPrev').on('click', function() {
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate
                        .getMonth() - 1, 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $('#wdCalNext').on('click', function() {
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate
                        .getMonth() + 1, 1);
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
                $scheduleTimeInput.on('change input', function() {
                    renderCalendarDayGridPaint();
                });
            }

            initScheduleCalendar();

            $('#weddingTableBody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a,button').length) return;
                var $tr = $(this);
                if ($tr.hasClass('sappc-table-loading') || $tr.hasClass('sappc-table-empty'))
            return;
                if ($tr.hasClass('is-schedule-selected')) {
                    if ($scheduleForm.length) {
                        resetScheduleRequestFormForNewEntry();
                    } else {
                        $('#weddingTableBody tr.is-schedule-selected').removeClass(
                            'is-schedule-selected');
                        setSelectedWeddingId('');
                    }
                    return;
                }
                $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $tr.addClass('is-schedule-selected');
                if (($tr.attr('data-document-type') || '').trim() !== 'Wedding') {
                    setSelectedWeddingId('');
                    return;
                }
                setSelectedWeddingId($tr.attr('data-record-id') || '');
                if (activeSection !== 'schedule' || !$scheduleForm.length) {
                    return;
                }
                var $tds = $tr.find('td');
                if ($tds.length < 6) return;
                $('#wdScheduleRefCode').val(($tds.eq(1).text() || '').trim());
                $('#wdScheduleClient').val(($tds.eq(2).text() || '').trim());
                $('#wdScheduleAddress').val(($tds.eq(3).text() || '').trim());
                var rawSex = ($tds.eq(4).text() || '').trim();
                if (rawSex === '\u2014' || rawSex === '-' || rawSex === '') {
                    $('#wdScheduleSex').val('');
                } else {
                    $('#wdScheduleSex').val(rawSex);
                }
                var rawContact = ($tds.eq(5).text() || '').trim();
                $('#wdScheduleContact').val(
                    (rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' :
                    formatPhMobileDisplay(rawContact)
                );
            });

            if ($scheduleForm.length && scheduleSaveUrl) {
                $scheduleForm.on('submit', function(e) {
                    e.preventDefault();
                    var cid = getSelectedWeddingId();
                    var payload = {
                        schedule_date: $('#wdScheduleDate').val(),
                        schedule_time: $('#wdScheduleTime24').val(),
                        client: ($('#wdScheduleClient').val() || '').trim(),
                        sex: ($('#wdScheduleSex').val() || '').trim(),
                        contact_number: sappcPhMobileDigitsOnly($('#wdScheduleContact').val()),
                        address: ($('#wdScheduleAddress').val() || '').trim(),
                        reference_code: ($('#wdScheduleRefCode').val() || '').trim(),
                    };
                    if (cid) {
                        var n = parseInt(cid, 10);
                        if (!isNaN(n)) payload.wedding_id = n;
                    }
                    var $submitBtn = $scheduleForm.find(
                        'button[type="submit"], input[type="submit"]').first();
                    $submitBtn.prop('disabled', true);
                    fetchPostJson(scheduleSaveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                if (typeof bootstrap !== 'undefined' && $scheduleModal.length) {
                                    var inst = bootstrap.Modal.getInstance($scheduleModal[0]);
                                    if (inst) inst.hide();
                                }
                                fetchRecords();
                                var okMsgWd =
                                    res && res.message ? String(res.message) :
                                    'Schedule reserved successfully.';
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Reserved',
                                        text: okMsgWd,
                                        confirmButtonText: 'OK',
                                    });
                                } else {
                                    window.alert(okMsgWd);
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

            function applyWeddingScheduleDetailsToForm(d) {
                if (!d || typeof d !== 'object') return;
                if (d.wedding_id != null && String(d.wedding_id).trim() !== '') {
                    setSelectedWeddingId(String(d.wedding_id).trim());
                }
                $('#wdScheduleRefCode').val(d.reference_code != null ? String(d.reference_code) : '');
                $('#wdScheduleClient').val(d.client != null ? String(d.client) : '');
                $('#wdScheduleAddress').val(d.address != null ? String(d.address) : '');
                $('#wdScheduleSex').val(d.sex != null ? String(d.sex) : '');
                var cn = d.contact_number != null ? String(d.contact_number).trim() : '';
                $('#wdScheduleContact').val(cn !== '' ? formatPhMobileDisplay(cn) : '');
                var sd = d.schedule_date != null ? String(d.schedule_date).trim().slice(0, 10) : '';
                $('#wdScheduleDate').val(sd);
                var st = d.schedule_time != null ? String(d.schedule_time).trim() : '';
                if (st.length >= 5) {
                    st = st.slice(0, 5);
                }
                $('#wdScheduleTime24').val(st || '10:00');
            }

            function syncWeddingScheduleModalCalendarFromInputs() {
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

            function onWeddingScheduleToolbarClick() {
                var cid = getSelectedWeddingId();
                var $sel = $('#weddingTableBody tr.is-schedule-selected');
                if (!cid && $sel.length) {
                    var doc = ($sel.attr('data-document-type') || '').trim();
                    if (doc === 'Wedding') {
                        var rid = ($sel.attr('data-record-id') || '').trim();
                        if (rid) {
                            setSelectedWeddingId(rid);
                            cid = rid;
                        }
                    }
                }
                if (!cid) {
                    resetScheduleRequestFormForNewEntry();
                }
            }

            $scheduleBtn.on('click', onWeddingScheduleToolbarClick);
            $scheduleNewBtn.on('click', onWeddingScheduleToolbarClick);

            if ($scheduleModal.length) {
                $scheduleModal.on('show.bs.modal', function(e) {
                    var cid = getSelectedWeddingId();
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
                    var cid = getSelectedWeddingId();
                    if (cid && scheduleDetailsUrl) {
                        fetchJson(buildQueryUrl(scheduleDetailsUrl, {
                                wedding_id: cid,
                            }), jsonHeaders)
                            .done(function(res) {
                                if (res && res.ok && res.data) {
                                    applyWeddingScheduleDetailsToForm(res.data);
                                }
                            })
                            .fail(function(xhr) {
                                var msg = 'Could not load schedule details.';
                                var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                                if (data && data.message) {
                                    msg = String(data.message);
                                }
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: msg,
                                    });
                                } else {
                                    window.alert(msg);
                                }
                            })
                            .always(function() {
                                syncWeddingScheduleModalCalendarFromInputs();
                            });
                    } else {
                        syncWeddingScheduleModalCalendarFromInputs();
                    }
                });
                $scheduleModal.on('hidden.bs.modal', function() {
                    if ($scheduleBtn.length) $scheduleBtn.attr('aria-expanded', 'false');
                    if ($scheduleNewBtn.length) $scheduleNewBtn.attr('aria-expanded', 'false');
                });
            }
        });
    })();
</script>
