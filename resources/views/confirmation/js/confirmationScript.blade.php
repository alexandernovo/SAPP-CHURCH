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

        function sappcSwalSelectConfirmationRowFirst() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a record',
                    text: 'Select a confirmation row in the table first.',
                    confirmButtonText: 'OK',
                });
            } else {
                window.alert('Select a confirmation row in the table first.');
            }
        }

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
                '<td>' + esc(row.dateCreated) + '</td>' +
                '<td class="text-center"><div class="sappc-icon-action_group">' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="View" aria-label="View record"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--edit" title="Edit" aria-label="Edit record"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>' +
                '<button type="button" class="sappc-icon-action sappc-icon-action--delete" title="Delete" aria-label="Delete record"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>' +
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

            var $panel = $('#confirmationRecordsPanel');
            if (!$panel.length) return;

            var url = $panel.attr('data-records-url');
            if (!url) return;

            var csrf = getMetaCsrf();
            var jsonHeaders = {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            };

            var state = {
                page: 1,
                per_page: 10,
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
                        '<tr class="sappc-table-empty"><td colspan="8" class="text-center text-muted py-4">No records found.</td></tr>';
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
                    '<tr class="sappc-table-loading"><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>'
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
                    .done(renderTable)
                    .fail(function(xhr, textStatus, errorThrown) {
                        var msg =
                            (xhr && xhr.status) ||
                            errorThrown ||
                            textStatus ||
                            '?';
                        $body.html(
                            '<tr><td colspan="8" class="text-center text-danger py-3">Could not load records (' +
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
                    reference_code: ($('#cnPaymentRefCode').val() || '').trim(),
                    client: ($('#cnPaymentClient').val() || '').trim(),
                    contact_number: ($('#cnPaymentContact').val() || '').trim(),
                    address: ($('#cnPaymentAddress').val() || '').trim(),
                    fee_rows: collectConfirmationPaymentFeeRowsFromDom(),
                };
            }

            function applyConfirmationPaymentFeeFormObject(data) {
                if (!data || typeof data !== 'object') return;
                $('#cnPaymentRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
                $('#cnPaymentClient').val(data.client != null ? String(data.client) : '');
                $('#cnPaymentContact').val(data.contact_number != null ? String(data.contact_number) : '');
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
                    var cid = ($('#cnScheduleConfirmationId').val() || '').trim();
                    if (!cid) {
                        sappcSwalSelectConfirmationRowFirst();
                        return;
                    }
                    if (!paymentDetailsUrl) {
                        window.alert('Payment load is not configured.');
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
                    var cid = ($('#cnScheduleConfirmationId').val() || '').trim();
                    if (!cid) {
                        sappcSwalSelectConfirmationRowFirst();
                        return;
                    }
                    var payload = serializeConfirmationPaymentFeeToObject();
                    payload.confirmation_id = parseInt(cid, 10);
                    if (isNaN(payload.confirmation_id)) {
                        window.alert('Invalid record.');
                        return;
                    }
                    var $saveBtn = $('#confirmationPaymentFeeSaveBtn');
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

            (function initConfirmationKompirmaModals() {
                var applicationFieldNames = {
                    'first_name': 1, 'middle_name': 1, 'family_name': 1, 'date_of_birth': 1, 'place_of_birth': 1,
                    'father_name': 1, 'mother_maiden': 1, 'address': 1, 'baptism_date': 1, 'baptism_place': 1,
                    'minister_baptism': 1, 'book_no': 1, 'page_no': 1, 'registry_no': 1, 'confirmation_date': 1,
                    'confirmation_minister': 1, 'godparent_1': 1, 'godparent_2': 1, 'godparent_3': 1, 'godparent_4': 1,
                };
                var arancelFieldNames = {
                    'amt_arancel': 1, 'amt_candle': 1, 'amt_godparents': 1, 'other_label_1': 1, 'other_label_2': 1,
                    'other_label_3': 1, 'amt_other_1': 1, 'amt_other_2': 1, 'amt_other_3': 1, 'total_payment': 1,
                    'sig_bpc_chairman': 1, 'sig_parish_secretary': 1, 'sig_presacramental_instructor': 1, 'sig_parish_priest': 1,
                };

                function applyFormObject($f, d) {
                    if (!$f || !$f.length || !d || typeof d !== 'object') {
                        return;
                    }
                    $f.find('input, textarea, select').each(function() {
                        var n = this.name;
                        if (!n) {
                            return;
                        }
                        if (!(n in d) || d[n] == null) {
                            return;
                        }
                        if (this.type === 'checkbox') {
                            $(this).prop('checked', d[n] == 1 || d[n] === true || d[n] === '1' || d[n] === 'on');
                        } else {
                            $(this).val(String(d[n]));
                        }
                    });
                }

                function collectFormObject($f) {
                    var o = {};
                    if (!$f || !$f.length) {
                        return o;
                    }
                    $f.find('input, textarea, select').each(function() {
                        var n = this.name;
                        if (!n || n === '_token') {
                            return;
                        }
                        if (this.type === 'checkbox') {
                            if ($(this).is(':checked')) {
                                o[n] = this.value;
                            }
                            return;
                        }
                        o[n] = $(this).val() == null ? '' : String($(this).val());
                    });
                    return o;
                }

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

                var $mApp = $('#confirmationApplicationModal');
                var $fApp = $('#confirmationApplicationForm');

                if (typeof bootstrap !== 'undefined' && $mApp.length && $fApp.length && $appFormBtn.length) {
                    $mApp.on('shown.bs.modal', function() {
                        $appFormBtn.attr('aria-expanded', 'true');
                    });
                    $mApp.on('hidden.bs.modal', function() {
                        $appFormBtn.attr('aria-expanded', 'false');
                    });
                    $appFormBtn.on('click', function(e) {
                        e.preventDefault();
                        var cid = ($('#cnScheduleConfirmationId').val() || '').trim();
                        if (!cid) {
                            sappcSwalSelectConfirmationRowFirst();
                            return;
                        }
                        if (!confirmationAppDetailsUrl) {
                            window.alert('Application form is not configured.');
                            return;
                        }
                        if (!confirmationArancelDetailsUrl) {
                            window.alert('Arancel is not configured.');
                            return;
                        }
                        var mbs = bootstrap.Modal.getOrCreateInstance($mApp[0]);
                        var dApp = fetchJson(buildQueryUrl(confirmationAppDetailsUrl, {
                            confirmation_id: cid
                        }), jsonHeaders);
                        var dAr = fetchJson(buildQueryUrl(confirmationArancelDetailsUrl, {
                            confirmation_id: cid
                        }), jsonHeaders);
                        $.when(dApp, dAr)
                            .done(function(resA, resB) {
                                var r1 = (resA && resA[0]) ? resA[0] : resA;
                                var r2 = (resB && resB[0]) ? resB[0] : resB;
                                if (r1 && r1.ok && r2 && r2.ok) {
                                    if ($fApp[0]) {
                                        $fApp[0].reset();
                                    }
                                    $('#cnApplicationConfirmationId').val(cid);
                                    applyFormObject($fApp, r1.data || {});
                                    applyFormObject($fApp, r2.data || {});
                                    mbs.show();
                                } else {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Could not load the form data.'
                                        });
                                    } else {
                                        window.alert('Could not load the form data.');
                                    }
                                }
                            })
                            .fail(function() {
                                var msg = 'Could not load confirmation application and arancel.';
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
                    $('#confirmationApplicationSaveBtn').on('click', function() {
                        if (!confirmationAppSaveUrl || !confirmationArancelSaveUrl) {
                            return;
                        }
                        var wid = ($('#cnApplicationConfirmationId').val() || '').trim() || ($('#cnScheduleConfirmationId')
                            .val() || '').trim();
                        if (!wid) {
                            sappcSwalSelectConfirmationRowFirst();
                            return;
                        }
                        var wn = parseInt(wid, 10);
                        if (isNaN(wn) || wn < 1) {
                            window.alert('Invalid record.');
                            return;
                        }
                        var all = collectFormObject($fApp);
                        var pApp = pickFields(all, applicationFieldNames);
                        var pAr = pickFields(all, arancelFieldNames);
                        pApp.confirmation_id = wn;
                        pAr.confirmation_id = wn;
                        var $s = $('#confirmationApplicationSaveBtn');
                        $s.prop('disabled', true);
                        fetchPostJson(confirmationAppSaveUrl, pApp, csrf)
                            .done(function(r1) {
                                if (!r1 || !r1.ok) {
                                    var m1 = (r1 && r1.message) ? r1.message : 'Application could not be saved.';
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({ icon: 'error', title: 'Error', text: m1 });
                                    } else {
                                        window.alert(m1);
                                    }
                                    $s.prop('disabled', false);
                                    return;
                                }
                                fetchPostJson(confirmationArancelSaveUrl, pAr, csrf)
                                    .done(function(r2) {
                                        if (r2 && r2.ok) {
                                            if (typeof bootstrap !== 'undefined' && $mApp.length) {
                                                var instM = bootstrap.Modal.getInstance($mApp[0]);
                                                if (instM) {
                                                    instM.hide();
                                                }
                                            }
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Saved',
                                                    text: (r2 && r2.message) ? r2.message : 'Application and arancel saved.',
                                                    confirmButtonText: 'OK',
                                                });
                                            } else {
                                                window.alert('Saved.');
                                            }
                                        } else {
                                            var m2 = (r2 && r2.message) ? r2.message : 'Arancel could not be saved.';
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({ icon: 'error', title: 'Error', text: m2 });
                                            } else {
                                                window.alert(m2);
                                            }
                                        }
                                    })
                                    .fail(function(xhr) {
                                        var msg = 'Arancel could not be saved.';
                                        var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                                        if (d && d.message) {
                                            msg = d.message;
                                        }
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({ icon: 'error', title: 'Error', text: msg });
                                        } else {
                                            window.alert(msg);
                                        }
                                    })
                                    .always(function() {
                                        $s.prop('disabled', false);
                                    });
                            })
                            .fail(function(xhr) {
                                var msg = 'Application could not be saved.';
                                var d = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                                if (d && d.message) {
                                    msg = d.message;
                                }
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                                } else {
                                    window.alert(msg);
                                }
                                $s.prop('disabled', false);
                            });
                    });
                }
            })();

            var $scheduleForm = $('#confirmationScheduleRequestForm');
            var $scheduleBtn = $('#confirmationScheduleRequestBtn');
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
                $('#cnScheduleConfirmationId').val('');
                $('#cnScheduleRefCode').val($scheduleForm.attr('data-default-reference-code') || '');
                $('#cnScheduleContact').val('');
                $('#cnScheduleClient').val('');
                $('#cnScheduleAddress').val('');
                $('#cnScheduleSex').val('');
                $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
                $scheduleTimeInput.val('10:00');
                $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                var sel = parseIsoDate($scheduleDateInput.val());
                if (sel) {
                    calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
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
                $('#confirmationTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $tr.addClass('is-schedule-selected');
                if (($tr.attr('data-document-type') || '').trim() !== 'Confirmation') {
                    $('#cnScheduleConfirmationId').val('');
                    return;
                }
                var $tds = $tr.find('td');
                if ($tds.length < 6) return;
                $('#cnScheduleConfirmationId').val($tr.attr('data-record-id') || '');
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
                $('#cnScheduleContact').val((rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' : rawContact);
            });

            if ($scheduleForm.length && scheduleSaveUrl) {
                $scheduleForm.on('submit', function(e) {
                    e.preventDefault();
                    var cid = ($('#cnScheduleConfirmationId').val() || '').trim();
                    var payload = {
                        schedule_date: $('#cnScheduleDate').val(),
                        schedule_time: $('#cnScheduleTime24').val(),
                        client: ($('#cnScheduleClient').val() || '').trim(),
                        sex: ($('#cnScheduleSex').val() || '').trim(),
                        contact_number: ($('#cnScheduleContact').val() || '').trim(),
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

            $scheduleBtn.on('click', function() {
                resetScheduleRequestFormForNewEntry();
            });

            if ($scheduleBtn.length && $scheduleModal.length) {
                $scheduleModal.on('shown.bs.modal', function() {
                    $scheduleBtn.attr('aria-expanded', 'true');
                    if (!$scheduleDateInput.val()) $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
                    if (!$scheduleTimeInput.val()) $scheduleTimeInput.val('10:00');
                    var selectedDate = parseIsoDate($scheduleDateInput.val());
                    if (selectedDate) {
                        calendarViewDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                    }
                    syncCalendarHeader();
                    renderCalendarDayGrid();
                });
                $scheduleModal.on('hidden.bs.modal', function() {
                    $scheduleBtn.attr('aria-expanded', 'false');
                });
            }

            fetchRecords();
        });
    })();
</script>
