<script>
    (function() {
        'use strict';

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

        function sappcWdConfirm(cfg) {
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

        $(document).on('input', '#wdScheduleContact, #wdPaymentContact', function() {
            var $el = $(this);
            var before = $el.val();
            var formatted = formatPhMobileDisplay(before);
            if (formatted !== before) {
                $el.val(formatted);
            }
        });

        function rowHtml(row) {
            return (
                '<tr data-record-id="' + esc(row.recordId) + '" data-document-type="' + esc(row.documentType) +
                '">' +
                '<td>' + esc(row.rowNumber) + '</td>' +
                '<td>' + esc(row.referenceCode) + '</td>' +
                '<td>' + esc(row.client) + '</td>' +
                '<td>' + esc(row.address) + '</td>' +
                '<td>' + esc(row.sex) + '</td>' +
                '<td>' + esc(row.contactNum) + '</td>' +
                '<td class="text-center align-middle">' + paymentStatusCell(row.paymentStatus) + '</td>' +
                '<td>' + esc(row.dateCreated) + '</td>' +
                '<td class="text-center"><div class="sappc-icon-action_group">' +
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

            var $panel = $('#weddingRecordsPanel');
            if (!$panel.length) return;

            var csrf = getMetaCsrf();
            var jsonHeaders = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };

            var recordsUrl = ($panel.attr('data-records-url') || '').trim();
            var paymentDetailsUrl = ($panel.attr('data-payment-details-url') || '').trim();
            var paymentSaveUrlPanel = ($panel.attr('data-payment-save-url') || '').trim();
            var $weddingAppFormBtn = $('#weddingApplicationFormBtn');
            var marriageAppDetailsUrl = ($panel.attr('data-marriage-application-details-url') || $weddingAppFormBtn.attr(
                'data-marriage-application-details-url') || '').trim();
            var marriageAppSaveUrl = ($panel.attr('data-marriage-application-save-url') || $weddingAppFormBtn.attr(
                'data-marriage-application-save-url') || '').trim();
            var weddingDeleteUrl = ($panel.attr('data-wedding-delete-url') || '').trim();
            var scheduleDetailsUrl = ($panel.attr('data-schedule-details-url') || '').trim();

            var fetchRecords = function() {};

            if (recordsUrl) {
            var state = {
                page: 1,
                per_page: 10,
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
                        '<tr class="sappc-table-empty"><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>';
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
                    '<tr class="sappc-table-loading"><td colspan="9" class="text-center text-muted py-4">Loading...</td></tr>'
                );
                var reqUrl = buildQueryUrl(recordsUrl, {
                    page: state.page,
                    per_page: state.per_page,
                    search: state.search,
                    letter: state.letter,
                    date_from: state.date_from,
                    date_to: state.date_to,
                    registry_type: 'wedding',
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
                    .done(renderTable)
                    .fail(function(xhr, textStatus, errorThrown) {
                        var msg =
                            (xhr && xhr.status) ||
                            errorThrown ||
                            textStatus ||
                            '?';
                        $body.html(
                            '<tr><td colspan="9" class="text-center text-danger py-3">Could not load records (' +
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
                                if (($('#wdScheduleWeddingId').val() || '').trim() === id) {
                                    $('#wdScheduleWeddingId').val('');
                                }
                                if (($('#wdMarriageAppWeddingId').val() || '').trim() === id) {
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

                sappcWdConfirm({
                    title: 'Delete wedding record?',
                    text: 'This permanently deletes this wedding row from the registry (including schedule and marriage application data).',
                    confirmButtonText: 'Yes, delete',
                }).then(function(r) {
                    if (r.isConfirmed) runDelete();
                });
            });

            fetchRecords();

            }

            var $paymentModal = $('#weddingPaymentFeeModal');
            var $paymentBtn = $('#weddingPaymentFeeBtn');
            var $paymentFeeForm = $('#weddingPaymentFeeForm');
            var $feeItemsBody = $('#weddingPaymentFeeItemsBody');
            var $addFeeBtn = $('#weddingPaymentFeeAddItemBtn');

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
                    '<button type="button" class="sappcPaymentFeeModalToggleUnpaid">Paid</button>' +
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
                var $toggle = $tr.find('.sappcPaymentFeeModalToggleUnpaid');
                if (paid) {
                    $status.removeClass('sappcPaymentFeeModalStatusUnpaid').addClass('sappcPaymentFeeModalStatusPaid').text('Paid');
                    $toggle.text('Unpaid');
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
                    $toggle.text('Paid');
                    $date.removeAttr('data-date-paid');
                    $date.text('\u2014');
                }
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
                    data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
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

            $feeItemsBody.on('click', '.sappcPaymentFeeModalToggleUnpaid', function() {
                var $btn = $(this);
                var $row = $btn.closest('[data-fee-row]');
                var $status = $row.find('.sappcPaymentFeeModalStatus');
                var $date = $row.find('.sappcPaymentFeeModalDatePaid');
                var isPaid = $status.hasClass('sappcPaymentFeeModalStatusPaid');
                if (isPaid) {
                    $status.removeClass('sappcPaymentFeeModalStatusPaid').addClass('sappcPaymentFeeModalStatusUnpaid').text('Unpaid');
                    $btn.text('Paid');
                    $date.removeAttr('data-date-paid');
                    $date.text('\u2014');
                } else {
                    var iso = new Date().toISOString().slice(0, 10);
                    $status.addClass('sappcPaymentFeeModalStatusPaid').removeClass('sappcPaymentFeeModalStatusUnpaid').text('Paid');
                    $btn.text('Unpaid');
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
                    var cid = ($('#wdScheduleWeddingId').val() || '').trim();
                    if (!cid) {
                        sappcSwalSelectWeddingRowFirst();
                        return;
                    }
                    if (!paymentDetailsUrl) {
                        window.alert('Payment load is not configured.');
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

                $paymentFeeForm.on('submit', function(e) {
                    e.preventDefault();
                    var saveUrl = ($paymentFeeForm.attr('data-save-url') || paymentSaveUrlPanel || '').trim();
                    if (!saveUrl) return;
                    var cid = ($('#wdScheduleWeddingId').val() || '').trim();
                    if (!cid) {
                        sappcSwalSelectWeddingRowFirst();
                        return;
                    }
                    var payload = serializeConfirmationPaymentFeeToObject();
                    payload.wedding_id = parseInt(cid, 10);
                    if (isNaN(payload.wedding_id)) {
                        window.alert('Invalid record.');
                        return;
                    }
                    var $saveBtn = $('#weddingPaymentFeeSaveBtn');
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(saveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
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

            (function initMarriageApplicationModal() {
                var $marriageAppModal = $('#weddingMarriageApplicationModal');
                var $marriageAppForm = $('#weddingMarriageApplicationForm');
                var $marriageAppBtn = $('#weddingApplicationFormBtn');
                if (!$marriageAppModal.length || !$marriageAppForm.length || !$marriageAppBtn.length) {
                    return;
                }

                function fieldByName($f, n) {
                    if (!$f.length) return $();
                    return $f.find('[name="' + String(n).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]');
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
                        $(this).prop('checked', v === 1 || v === '1' || v === true || v === 'on');
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
                                fieldByName($f, 'precana[' + i + '][date]').val(String(row.date));
                            }
                            if (row.topic) {
                                fieldByName($f, 'precana[' + i + '][topic]').val(String(row.topic));
                            }
                            if (row.signature) {
                                fieldByName($f, 'precana[' + i + '][signature]').val(String(row.signature));
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
                        if (!n || n === '_token' || n.indexOf('precana[') === 0 || n.indexOf('marriage_sponsors[') ===
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
                            date: (fieldByName($f, 'precana[' + i + '][date]').val() || '').trim(),
                            topic: (fieldByName($f, 'precana[' + i + '][topic]').val() || '').trim(),
                            signature: (fieldByName($f, 'precana[' + i + '][signature]').val() || '').trim()
                        });
                    }
                    out.marriage_sponsors = {};
                    for (var g = 1; g <= 40; g++) {
                        out.marriage_sponsors[String(g)] = (fieldByName($f, 'marriage_sponsors[' + g + ']').val() ||
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
                    var cid = ($('#wdScheduleWeddingId').val() || '').trim();
                    if (!cid) {
                        sappcSwalSelectWeddingRowFirst();
                        return;
                    }
                    if (!marriageAppDetailsUrl) {
                        window.alert('Marriage application is not configured.');
                        return;
                    }
                    var marriageBsModal = bootstrap.Modal.getOrCreateInstance($marriageAppModal[0]);
                    fetchJson(buildQueryUrl(marriageAppDetailsUrl, {
                        wedding_id: cid
                    }), jsonHeaders)
                        .done(function(res) {
                            if (res && res.ok) {
                                if ($marriageAppForm[0]) {
                                    $marriageAppForm[0].reset();
                                }
                                $marriageAppForm.find('input[type=checkbox]').prop('checked', false);
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

                $('#weddingMarriageAppSaveBtn').on('click', function() {
                    if (!marriageAppSaveUrl) {
                        return;
                    }
                    if (typeof bootstrap === 'undefined') {
                        return;
                    }
                    var wid = ($('#wdMarriageAppWeddingId').val() || '').trim() || ($('#wdScheduleWeddingId').val() ||
                        '').trim();
                    if (!wid) {
                        sappcSwalSelectWeddingRowFirst();
                        return;
                    }
                    var wn = parseInt(wid, 10);
                    if (isNaN(wn) || wn < 1) {
                        window.alert('Invalid record.');
                        return;
                    }
                    var payload = collectMarriageApplicationPayload();
                    payload.wedding_id = wn;
                    var $saveBtn = $('#weddingMarriageAppSaveBtn');
                    var marriageBsModal = bootstrap.Modal.getOrCreateInstance($marriageAppModal[0]);
                    $saveBtn.prop('disabled', true);
                    fetchPostJson(marriageAppSaveUrl, payload, csrf)
                        .done(function(res) {
                            if (res && res.ok) {
                                marriageBsModal.hide();
                                var msg = (res && res.message) ? res.message : 'Marriage application saved.';
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
                });
            })();

            var $scheduleForm = $('#weddingScheduleRequestForm');
            var $scheduleBtn = $('#weddingScheduleRequestBtn');
            var scheduleSaveUrl = $scheduleForm.attr('data-schedule-save-url') || $scheduleBtn.attr('data-schedule-save-url') || '';
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
                $('#wdScheduleWeddingId').val('');
                $('#wdScheduleRefCode').val($scheduleForm.attr('data-default-reference-code') || '');
                $('#wdScheduleContact').val('');
                $('#wdScheduleClient').val('');
                $('#wdScheduleAddress').val('');
                $('#wdScheduleSex').val('');
                $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
                $scheduleTimeInput.val('10:00');
                $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                var sel = parseIsoDate($scheduleDateInput.val());
                if (sel) {
                    calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
                }
                syncCalendarHeader();
                renderCalendarDayGrid();
            }

            function initScheduleCalendar() {
                if (!$calMonthSel.length || !$calYearSel.length || !$('#wdCalPrev').length || !$('#wdCalNext').length || !$calDayCells.length || !$scheduleDateInput.length) {
                    return;
                }
                populateCalendarSelectors();
                syncCalendarHeader();
                renderCalendarDayGrid();
                $('#wdCalPrev').on('click', function() {
                    calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate.getMonth() - 1, 1);
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $('#wdCalNext').on('click', function() {
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

            $('#weddingTableBody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a,button').length) return;
                var $tr = $(this);
                if ($tr.hasClass('sappc-table-loading') || $tr.hasClass('sappc-table-empty')) return;
                $('#weddingTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $tr.addClass('is-schedule-selected');
                if (($tr.attr('data-document-type') || '').trim() !== 'Wedding') {
                    $('#wdScheduleWeddingId').val('');
                    return;
                }
                var $tds = $tr.find('td');
                if ($tds.length < 6) return;
                $('#wdScheduleWeddingId').val($tr.attr('data-record-id') || '');
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
                    (rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' : formatPhMobileDisplay(rawContact)
                );
            });

            if ($scheduleForm.length && scheduleSaveUrl) {
                $scheduleForm.on('submit', function(e) {
                    e.preventDefault();
                    var cid = ($('#wdScheduleWeddingId').val() || '').trim();
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
                    $('#wdScheduleWeddingId').val(String(d.wedding_id).trim());
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

            $scheduleBtn.on('click', function() {
                var cid = ($('#wdScheduleWeddingId').val() || '').trim();
                var $sel = $('#weddingTableBody tr.is-schedule-selected');
                if (!cid && $sel.length) {
                    var doc = ($sel.attr('data-document-type') || '').trim();
                    if (doc === 'Wedding') {
                        var rid = ($sel.attr('data-record-id') || '').trim();
                        if (rid) {
                            $('#wdScheduleWeddingId').val(rid);
                            cid = rid;
                        }
                    }
                }
                if (!cid) {
                    resetScheduleRequestFormForNewEntry();
                }
            });

            if ($scheduleBtn.length && $scheduleModal.length) {
                $scheduleModal.on('shown.bs.modal', function() {
                    $scheduleBtn.attr('aria-expanded', 'true');
                    var cid = ($('#wdScheduleWeddingId').val() || '').trim();
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
                    $scheduleBtn.attr('aria-expanded', 'false');
                });
            }
        });
    })();
</script>
