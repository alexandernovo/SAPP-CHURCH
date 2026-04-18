@php
    $chApplicationFormConfig = array_merge(
        [
            'letterSlots' => 25,
            'nameGroupEndIndices' => [7, 15],
            'contactSlots' => 11,
            'godparentLines' => 10,
        ],
        $chApplicationFormConfig ?? [],
    );
@endphp
<script>
    (function($) {
        'use strict';

        var initialTablePayload = @json($initialTablePayload);
        var chApplicationFormConfig = @json($chApplicationFormConfig);

        function esc(s) {
            return $('<div/>').text(s == null ? '' : String(s)).html();
        }

        function getMetaCsrf() {
            return $('meta[name="csrf-token"]').attr('content') || '';
        }

        function buildQueryUrl(base, params) {
            var q = new URLSearchParams();
            $.each(params, function(k, v) {
                if (v !== undefined && v !== null && String(v) !== '') {
                    q.set(k, String(v));
                }
            });
            var sep = base.indexOf('?') >= 0 ? '&' : '?';
            return base + sep + q.toString();
        }

        function fetchJson(url, headers) {
            return $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: headers || {},
            });
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

        function sappcSwalSelectChristeningRowFirst() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a record',
                    text: 'Select a christening row in the table first.',
                    confirmButtonText: 'OK',
                });
            } else {
                window.alert('Select a christening row in the table first.');
            }
        }

        function normalizePerPage(value) {
            var allowed = [10, 25, 50, 100];
            var $panel = $('#christeningRecordsPanel');
            if ($panel.length) {
                try {
                    var raw = $panel.attr('data-per-page-options');
                    var parsed = raw ? JSON.parse(raw) : [];
                    if (Array.isArray(parsed) && parsed.length) {
                        allowed = parsed;
                    }
                } catch (e) {}
            }
            var n = parseInt(value, 10);
            return allowed.indexOf(n) !== -1 ? n : allowed[0];
        }

        function initChristeningApplicationFormGrids() {
            var cfg = chApplicationFormConfig || {};
            var letterSlots = parseInt(cfg.letterSlots, 10) || 25;
            var groupEnds = Array.isArray(cfg.nameGroupEndIndices) ? cfg.nameGroupEndIndices : [7, 15];
            var contactSlots = parseInt(cfg.contactSlots, 10) || 11;
            var godparentLines = parseInt(cfg.godparentLines, 10) || 10;

            function isGroupEnd(i) {
                return groupEnds.indexOf(i) !== -1;
            }

            function prependNameCells(wrapId, inputId) {
                var $wrap = $('#' + wrapId);
                if (!$wrap.length) return;
                var $input = inputId ? $('#' + inputId) : $wrap.find('.sappcChOfficialCellInput').first();
                if (!$input.length) return;
                $wrap.css('--ch-name-slots', String(letterSlots));
                for (var i = 0; i < letterSlots; i++) {
                    $('<span/>', {
                        class: 'sappcChOfficialCell' + (isGroupEnd(i) ? ' sappcChOfficialCellGroupEnd' : ''),
                        'aria-hidden': 'true',
                    }).insertBefore($input);
                }
            }

            prependNameCells('chAppCellsFirst', 'chAppFirstName');
            prependNameCells('chAppCellsMiddle', 'chAppMiddleName');
            prependNameCells('chAppCellsFamily', 'chAppFamilyName');

            $('#chAppFirstName').attr({
                maxlength: letterSlots,
                'aria-label': 'First name (' + letterSlots + ' letters max)',
            });
            $('#chAppMiddleName').attr({
                maxlength: letterSlots,
                'aria-label': 'Middle name (' + letterSlots + ' letters max)',
            });
            $('#chAppFamilyName').attr({
                maxlength: letterSlots,
                'aria-label': 'Family name (' + letterSlots + ' letters max)',
            });

            var $contactWrap = $('#chAppCellsContact');
            var $contactInput = $('#chAppGuardianContact');
            if ($contactWrap.length && $contactInput.length && $.contains($contactWrap[0], $contactInput[0])) {
                $contactWrap.css('--ch-contact-slots', String(contactSlots));
                for (var j = 0; j < contactSlots; j++) {
                    $('<span/>', {
                        class: 'sappcChOfficialCell',
                        'aria-hidden': 'true',
                    }).insertBefore($contactInput);
                }
                $contactInput.attr('maxlength', contactSlots);
            }

            var $colA = $('#chAppGpColA');
            var $colB = $('#chAppGpColB');
            if ($colA.length && $colB.length) {
                $colA.empty();
                $colB.empty();
                for (var g = 1; g <= godparentLines; g++) {
                    $('<input/>', {
                        type: 'text',
                        class: 'sappcChOfficialGpLine',
                        name: 'godparent_' + g + 'a',
                        'aria-label': 'Godparent line ' + g + ' (left)',
                    }).appendTo($colA);

                    $('<input/>', {
                        type: 'text',
                        class: 'sappcChOfficialGpLine',
                        name: 'godparent_' + g + 'b',
                        'aria-label': 'Godparent line ' + g + ' (right)',
                    }).appendTo($colB);
                }
            }
        }

        initChristeningApplicationFormGrids();

        var chApplicationDraftsByChristeningId = {};
        var chApplicationDraftSaveTimer = null;
        var chPaymentDraftsByChristeningId = {};
        var chPaymentDraftSaveTimer = null;

        function serializeChristeningApplicationFormToObject() {
            var $form = $('#christeningApplicationForm');
            if (!$form.length) return {};
            var arr = $form.serializeArray();
            var payload = {};
            $.each(arr, function(i, field) {
                var n = field.name;
                if (n.slice(-2) === '[]') {
                    var base = n.slice(0, -2);
                    if (!payload[base]) payload[base] = [];
                    payload[base].push(field.value);
                } else if (payload[n] !== undefined) {
                    if (!Array.isArray(payload[n])) payload[n] = [payload[n]];
                    payload[n].push(field.value);
                } else {
                    payload[n] = field.value;
                }
            });
            return payload;
        }

        function clearChristeningApplicationFormFields() {
            var $form = $('#christeningApplicationForm');
            if (!$form.length) return;
            $form.find('input[type="text"], textarea').val('');
            $form.find('input[type="checkbox"]').prop('checked', false);
        }

        function applyChristeningApplicationFormObject(snap) {
            if (!snap || typeof snap !== 'object') return;
            var $form = $('#christeningApplicationForm');
            if (!$form.length) return;
            clearChristeningApplicationFormFields();
            $.each(snap, function(key, val) {
                if (key === 'parent_status' && Array.isArray(val)) {
                    val.forEach(function(v) {
                        $form.find('input[name="parent_status[]"]').filter(function() {
                            return String($(this).val()) === String(v);
                        }).prop('checked', true);
                    });
                    return;
                }
                var $fields = $form.find('[name]').filter(function() {
                    return $(this).attr('name') === key;
                });
                if (!$fields.length) return;
                if ($fields.first().attr('type') === 'checkbox') return;
                if (Array.isArray(val)) {
                    $fields.each(function(i) {
                        if (val[i] !== undefined) $(this).val(val[i]);
                    });
                } else {
                    $fields.val(val);
                }
            });
        }

        function christeningApplicationDraftKey() {
            var cid = ($('#chScheduleChristeningId').val() || '').trim();
            return cid || '_none';
        }

        function snapshotChristeningApplicationDraft() {
            var $form = $('#christeningApplicationForm');
            if (!$form.length) return;
            chApplicationDraftsByChristeningId[christeningApplicationDraftKey()] =
                serializeChristeningApplicationFormToObject();
        }

        function restoreChristeningApplicationDraftForCurrentRow() {
            var key = christeningApplicationDraftKey();
            var snap = chApplicationDraftsByChristeningId[key];
            if (snap && Object.keys(snap).length) {
                applyChristeningApplicationFormObject(snap);
            } else {
                clearChristeningApplicationFormFields();
            }
        }

        var $appModal = $('#christeningApplicationFormModal');
        var $appBtn = $('#christeningApplicationFormBtn');
        if ($appModal.length && $appBtn.length && typeof bootstrap !== 'undefined') {
            var bsModal = bootstrap.Modal.getOrCreateInstance($appModal[0]);
            var pendingFocusSelector = null;

            $('#christeningApplicationForm').on('input change', 'input, textarea', function() {
                clearTimeout(chApplicationDraftSaveTimer);
                chApplicationDraftSaveTimer = setTimeout(function() {
                    snapshotChristeningApplicationDraft();
                }, 300);
            });

            $appModal.on('shown.bs.modal', function() {
                $appBtn.attr('aria-expanded', 'true');
                restoreChristeningApplicationDraftForCurrentRow();
                if (pendingFocusSelector) {
                    var $el = $appModal.find(pendingFocusSelector).first();
                    if ($el.length) $el.trigger('focus');
                    pendingFocusSelector = null;
                }
            });

            $appModal.on('hidden.bs.modal', function() {
                $appBtn.attr('aria-expanded', 'false');
                snapshotChristeningApplicationDraft();
            });

            window.sappcChristeningApplicationFormOpen = function(open, opts) {
                opts = opts || {};
                if (open !== false) {
                    pendingFocusSelector = opts.focusSelector || null;
                    bsModal.show();
                } else {
                    bsModal.hide();
                }
            };

            $appBtn.on('click', function() {
                bsModal.toggle();
            });

            $('#christeningApplicationForm').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var url = $form.attr('data-save-url') || $form.attr('action');
                if (!url) return;
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (!cid) {
                    sappcSwalSelectChristeningRowFirst();
                    return;
                }
                var arr = $form.serializeArray();
                var payload = {};
                $.each(arr, function(i, field) {
                    var n = field.name;
                    if (n.slice(-2) === '[]') {
                        var base = n.slice(0, -2);
                        if (!payload[base]) payload[base] = [];
                        payload[base].push(field.value);
                    } else if (payload[n] !== undefined) {
                        if (!Array.isArray(payload[n])) payload[n] = [payload[n]];
                        payload[n].push(field.value);
                    } else {
                        payload[n] = field.value;
                    }
                });
                payload.christening_id = parseInt(cid, 10);
                if (isNaN(payload.christening_id)) {
                    window.alert('Invalid record.');
                    return;
                }
                var $saveBtn = $('#christeningApplicationFormSaveBtn');
                $saveBtn.prop('disabled', true);
                fetchPostJson(url, payload, csrf)
                    .done(function(res) {
                        if (res && res.ok) {
                            if (typeof bootstrap !== 'undefined' && $appModal.length) {
                                var inst = bootstrap.Modal.getInstance($appModal[0]);
                                if (inst) inst.hide();
                            }
                            var msg = (res && res.message) ? res.message : 'Application details saved.';
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
                        var msg = 'Application could not be saved.';
                        var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                        if (data && data.errors) {
                            var vals = Object.values(data.errors);
                            if (vals.length && Array.isArray(vals[0]) && vals[0][0]) msg = vals[0][0];
                        } else if (data && data.message) {
                            msg = data.message;
                        }
                        window.alert(msg);
                    })
                    .always(function() {
                        $saveBtn.prop('disabled', false);
                    });
            });
        }

        var $paymentModal = $('#christeningPaymentFeeModal');
        var $paymentBtn = $('#christeningPaymentFeeBtn');
        var $paymentFeeForm = $('#christeningPaymentFeeForm');
        var $feeItemsBody = $('#christeningPaymentFeeItemsBody');
        var $addFeeBtn = $('#christeningPaymentFeeAddItemBtn');

        function renumberChristeningFeeRows() {
            $feeItemsBody.find('[data-fee-row]').each(function(i) {
                $(this).find('.sappcPaymentFeeModalCellNo').text(i + 1);
                $(this).find('.sappcPaymentFeeModalItemInput').attr('aria-label', 'Fee item ' + (i + 1));
            });
        }

        function newChristeningFeeRowHtml() {
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

        function collectChristeningPaymentFeeRowsFromDom() {
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

        function buildChristeningPaymentFeeRowFromData(row) {
            var label = (row && row.label != null) ? String(row.label) : '';
            var paid = !!(row && row.paid);
            var dateIso = (row && row.date_paid) ? String(row.date_paid).slice(0, 10) : '';
            var $tr = $(newChristeningFeeRowHtml());
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

        function serializeChristeningPaymentFeeToObject() {
            return {
                reference_code: ($('#chPaymentRefCode').val() || '').trim(),
                client: ($('#chPaymentClient').val() || '').trim(),
                contact_number: ($('#chPaymentContact').val() || '').trim(),
                address: ($('#chPaymentAddress').val() || '').trim(),
                fee_rows: collectChristeningPaymentFeeRowsFromDom(),
            };
        }

        function applyChristeningPaymentFeeFormObject(data) {
            if (!data || typeof data !== 'object') return;
            $('#chPaymentRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
            $('#chPaymentClient').val(data.client != null ? String(data.client) : '');
            $('#chPaymentContact').val(data.contact_number != null ? String(data.contact_number) : '');
            $('#chPaymentAddress').val(data.address != null ? String(data.address) : '');
            var feeRows = data.fee_rows;
            if (!Array.isArray(feeRows) || !feeRows.length) {
                feeRows = [{}];
            }
            $feeItemsBody.empty();
            feeRows.forEach(function(fr) {
                $feeItemsBody.append(buildChristeningPaymentFeeRowFromData(fr));
            });
            renumberChristeningFeeRows();
        }

        function snapshotChristeningPaymentDraft() {
            var key = christeningApplicationDraftKey();
            chPaymentDraftsByChristeningId[key] = serializeChristeningPaymentFeeToObject();
        }

        $addFeeBtn.on('click', function() {
            var $tr = $(newChristeningFeeRowHtml());
            $feeItemsBody.append($tr);
            renumberChristeningFeeRows();
            $tr.find('.sappcPaymentFeeModalItemInput').trigger('focus');
        });

        $feeItemsBody.on('click', '.sappcPaymentFeeModalBtnRemove', function() {
            if ($feeItemsBody.find('[data-fee-row]').length > 1) {
                $(this).closest('[data-fee-row]').remove();
                renumberChristeningFeeRows();
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

        var csrf = getMetaCsrf();
        var jsonHeaders = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        };

        var $panel = $('#christeningRecordsPanel');
        if (!$panel.length) return;

        var url = $panel.attr('data-records-url');
        var registryType = ($panel.attr('data-registry-type') || '').trim();
        var applicationDetailsUrl = ($panel.attr('data-application-details-url') || '').trim();
        var paymentDetailsUrl = ($panel.attr('data-payment-details-url') || '').trim();
        var paymentSaveUrl = ($panel.attr('data-payment-save-url') || '').trim();
        var certificationDetailsUrl = ($panel.attr('data-certification-details-url') || '').trim();
        var christeningDeleteUrl = ($panel.attr('data-christening-delete-url') || '').trim();
        if (!url) return;

        if ($paymentModal.length && $paymentBtn.length && typeof bootstrap !== 'undefined') {
            var paymentBsModal = bootstrap.Modal.getOrCreateInstance($paymentModal[0]);

            $paymentModal.on('shown.bs.modal', function() {
                $paymentBtn.attr('aria-expanded', 'true');
            });
            $paymentModal.on('hidden.bs.modal', function() {
                $paymentBtn.attr('aria-expanded', 'false');
                snapshotChristeningPaymentDraft();
            });

            $paymentFeeForm.on('input change', 'input, textarea', function() {
                clearTimeout(chPaymentDraftSaveTimer);
                chPaymentDraftSaveTimer = setTimeout(function() {
                    snapshotChristeningPaymentDraft();
                }, 300);
            });

            $paymentBtn.on('click', function(e) {
                e.preventDefault();
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (!cid) {
                    sappcSwalSelectChristeningRowFirst();
                    return;
                }
                if (!paymentDetailsUrl) {
                    window.alert('Payment load is not configured.');
                    return;
                }
                fetchJson(buildQueryUrl(paymentDetailsUrl, {
                    christening_id: cid
                }), jsonHeaders)
                    .done(function(res) {
                        if (res && res.ok && res.data) {
                            applyChristeningPaymentFeeFormObject(res.data);
                            chPaymentDraftsByChristeningId[String(cid)] =
                                serializeChristeningPaymentFeeToObject();
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
                var saveUrl = ($paymentFeeForm.attr('data-save-url') || paymentSaveUrl || '').trim();
                if (!saveUrl) return;
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (!cid) {
                    sappcSwalSelectChristeningRowFirst();
                    return;
                }
                var payload = serializeChristeningPaymentFeeToObject();
                payload.christening_id = parseInt(cid, 10);
                if (isNaN(payload.christening_id)) {
                    window.alert('Invalid record.');
                    return;
                }
                var $saveBtn = $('#christeningPaymentFeeSaveBtn');
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

        var $certModal = $('#christeningCertificationModal');
        var $certBtn = $('#christeningCertificationBtn');
        var $certForm = $('#christeningCertificationForm');
        var certificationSaveUrl = ($panel.attr('data-certification-save-url') || '').trim();

        function applyChristeningCertificationTopFromPayment(data) {
            if (!data || typeof data !== 'object') return;
            $('#chCertRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
            $('#chCertClient').val(data.client != null ? String(data.client) : '');
            $('#chCertContact').val(data.contact_number != null ? String(data.contact_number) : '');
            $('#chCertTopAddress').val(data.address != null ? String(data.address) : '');
        }

        function applyChristeningCertificationFromApplicationDetails(data) {
            if (!data || typeof data !== 'object') return;
            $('#chCertChildFirst').val(data.first_name != null ? String(data.first_name) : '');
            $('#chCertChildMiddle').val(data.middle_name != null ? String(data.middle_name) : '');
            $('#chCertChildLast').val(data.family_name != null ? String(data.family_name) : '');
            $('#chCertBirthday').val(data.date_of_birth != null ? String(data.date_of_birth) : '');
            $('#chCertBirthplace').val(data.place_of_birth != null ? String(data.place_of_birth) : '');
            $('#chCertFatherFirst').val(data.father_first_name != null ? String(data.father_first_name) : '');
            $('#chCertFatherMiddle').val(data.father_middle_name != null ? String(data.father_middle_name) : '');
            $('#chCertFatherLast').val(data.father_last_name != null ? String(data.father_last_name) : '');
            if (!$('#chCertFatherFirst').val() && !$('#chCertFatherMiddle').val() && !$('#chCertFatherLast').val() &&
                data.father_name) {
                $('#chCertFatherFirst').val(String(data.father_name));
            }
            $('#chCertMotherFirst').val(data.mother_first_name != null ? String(data.mother_first_name) : '');
            $('#chCertMotherMiddle').val(data.mother_middle_name != null ? String(data.mother_middle_name) : '');
            $('#chCertMotherLast').val(data.mother_last_name != null ? String(data.mother_last_name) : '');
            if (!$('#chCertMotherFirst').val() && !$('#chCertMotherMiddle').val() && !$('#chCertMotherLast').val() &&
                data.mother_maiden_name) {
                $('#chCertMotherFirst').val(String(data.mother_maiden_name));
            }
            $('#chCertPriest').val(data.minister != null ? String(data.minister) : '');
            $('#chCertBarangay').val(data.barangay != null ? String(data.barangay) : '');
            $('#chCertMunicipality').val(data.municipality != null ? String(data.municipality) : '');
            $('#chCertProvince').val(data.province != null ? String(data.province) : '');
            if (!$('#chCertBarangay').val() && !$('#chCertMunicipality').val() && !$('#chCertProvince').val()) {
                var pad = data.parent_address != null ? String(data.parent_address) : '';
                if (pad) {
                    var bits = pad.split(',').map(function(s) {
                        return s.trim();
                    });
                    if (bits.length >= 1) $('#chCertBarangay').val(bits[0]);
                    if (bits.length >= 2) $('#chCertMunicipality').val(bits[1]);
                    if (bits.length >= 3) $('#chCertProvince').val(bits.slice(2).join(', '));
                }
            }
            $('#chCertDateReceived').val(data.date_received != null ? String(data.date_received) : '');
            $('#chCertDateIssued').val(data.date_issued != null ? String(data.date_issued) : '');
            $('#chCertBookNo').val(data.book_no != null ? String(data.book_no) : '');
            $('#chCertRegisterNo').val(data.register_no != null ? String(data.register_no) : '');
            $('#chCertPageNo').val(data.page_no != null ? String(data.page_no) : '');
            $('#chCertSponsors').val(data.sponsors != null ? String(data.sponsors) : '');
            $('#chCertPurpose').val(data.purpose != null ? String(data.purpose) : '');
        }

        if ($certModal.length && $certBtn.length && typeof bootstrap !== 'undefined') {
            var certBsModal = bootstrap.Modal.getOrCreateInstance($certModal[0]);

            $certModal.on('shown.bs.modal', function() {
                $certBtn.attr('aria-expanded', 'true');
            });
            $certModal.on('hidden.bs.modal', function() {
                $certBtn.attr('aria-expanded', 'false');
            });

            $certBtn.on('click', function(e) {
                e.preventDefault();
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (!cid) {
                    sappcSwalSelectChristeningRowFirst();
                    return;
                }
                if (!paymentDetailsUrl || !applicationDetailsUrl || !certificationDetailsUrl) {
                    window.alert('Certification load is not configured.');
                    return;
                }
                $.when(
                    fetchJson(buildQueryUrl(paymentDetailsUrl, {
                        christening_id: cid
                    }), jsonHeaders),
                    fetchJson(buildQueryUrl(applicationDetailsUrl, {
                        christening_id: cid
                    }), jsonHeaders),
                    fetchJson(buildQueryUrl(certificationDetailsUrl, {
                        christening_id: cid
                    }), jsonHeaders)
                ).done(function(payTuple, appTuple, certTuple) {
                    var pay = payTuple && payTuple[0] ? payTuple[0] : null;
                    var app = appTuple && appTuple[0] ? appTuple[0] : null;
                    var cert = certTuple && certTuple[0] ? certTuple[0] : null;
                    if (pay && pay.ok && pay.data) applyChristeningCertificationTopFromPayment(pay.data);
                    if (app && app.ok && app.data) applyChristeningCertificationFromApplicationDetails(app.data);
                    if (cert && cert.ok && cert.has_saved_cert && cert.data) {
                        applyChristeningCertificationFromApplicationDetails(cert.data);
                    }
                    certBsModal.show();
                }).fail(function(xhr) {
                    var msg = 'Could not load record for certification.';
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

            $certForm.on('submit', function(e) {
                e.preventDefault();
                var saveUrl = ($certForm.attr('data-save-url') || certificationSaveUrl || '').trim();
                if (!saveUrl) return;
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (!cid) {
                    sappcSwalSelectChristeningRowFirst();
                    return;
                }
                var arr = $certForm.serializeArray();
                var payload = {};
                $.each(arr, function(i, field) {
                    var n = field.name;
                    if (n.slice(-2) === '[]') {
                        var base = n.slice(0, -2);
                        if (!payload[base]) payload[base] = [];
                        payload[base].push(field.value);
                    } else if (payload[n] !== undefined) {
                        if (!Array.isArray(payload[n])) payload[n] = [payload[n]];
                        payload[n].push(field.value);
                    } else {
                        payload[n] = field.value;
                    }
                });
                payload.christening_id = parseInt(cid, 10);
                if (isNaN(payload.christening_id)) {
                    window.alert('Invalid record.');
                    return;
                }
                var $saveBtn = $('#chCertAddRecordBtn');
                $saveBtn.prop('disabled', true);
                fetchPostJson(saveUrl, payload, csrf)
                    .done(function(res) {
                        if (res && res.ok) {
                            if (typeof bootstrap !== 'undefined' && $certModal.length) {
                                var inst = bootstrap.Modal.getInstance($certModal[0]);
                                if (inst) inst.hide();
                            }
                            var msg = (res && res.message) ? res.message : 'Certification saved.';
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
                        var msg = 'Certification could not be saved.';
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

        var $searchInput = $('#christeningSearch');
        if (!$searchInput.length) return;

        var meta0 = initialTablePayload.meta || {};
        var state = {
            page: meta0.current_page || 1,
            per_page: normalizePerPage(meta0.per_page || 10),
            search: ($searchInput.val() || '').trim(),
            letter: @json(request('letter', '')),
            date_from: @json(request('date_from', '')),
            date_to: @json(request('date_to', '')),
        };

        function paymentStatusCell(raw) {
            var s = String(raw == null ? '' : raw).trim();
            var lower = s.toLowerCase();
            if (!s || s === '\u2014') return '<span class="text-muted">\u2014</span>';
            if (lower === 'paid') return '<span class="sappc-payment-badge sappc-payment-badge--paid">Paid</span>';
            if (lower === 'unpaid') return '<span class="sappc-payment-badge sappc-payment-badge--unpaid">Unpaid</span>';
            return esc(s);
        }

        function rowHtml(row) {
            return '' +
                '<tr data-record-id="' + esc(row.recordId) + '" data-document-type="' + esc(row.documentType) + '">' +
                '<td>' + esc(row.rowNumber) + '</td>' +
                '<td>' + esc(row.referenceCode) + '</td>' +
                '<td>' + esc(row.client) + '</td>' +
                '<td>' + esc(row.address) + '</td>' +
                '<td>' + esc(row.sex) + '</td>' +
                '<td>' + esc(row.contactNum) + '</td>' +
                '<td class="text-center align-middle">' + paymentStatusCell(row.paymentStatus) + '</td>' +
                '<td>' + esc(row.dateCreated) + '</td>' +
                '<td class="text-center"><div class="sappc-icon-action_group">' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--view" title="View" aria-label="View record" data-record-id="' + esc(row.recordId) + '"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>' +
                '<a href="#" class="sappc-icon-action sappc-icon-action--edit" title="Edit" aria-label="Edit record" data-record-id="' + esc(row.recordId) + '"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>' +
                '<button type="button" class="sappc-icon-action sappc-icon-action--delete" title="Delete" aria-label="Delete record" data-record-id="' + esc(row.recordId) + '"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>' +
                '</div></td></tr>';
        }

        function renderTable(res) {
            var $tbody = $('#christeningTableBody');
            if (!res || !res.data || !res.data.length) {
                $tbody.html('<tr class="sappc-table-empty"><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>');
            } else {
                var html = '';
                $.each(res.data, function(_, row) {
                    html += rowHtml(row);
                });
                $tbody.html(html);
            }

            var m = (res && res.meta) ? res.meta : {};
            if (!m.total) {
                $('#christeningTableFooterInfo').text('Showing 0 entries');
            } else {
                $('#christeningTableFooterInfo').text('Showing ' + m.from + ' to ' + m.to + ' of ' + m.total + ' entries');
            }

            var last = Math.max(1, m.last_page || 1);
            var cur = m.current_page || 1;
            var navHtml = '';
            navHtml += '<button type="button" class="sappc-pagination_btn sappcChPagePrev" data-page="' + (cur - 1) + '" ' + (cur <= 1 ? 'disabled' : '') + ' aria-label="Previous">&lt;</button>';
            for (var p = 1; p <= last; p++) {
                navHtml += '<button type="button" class="sappc-pagination_btn sappcChPageNum' + (p === cur ? ' is-active' : '') + '" data-page="' + p + '"' + (p === cur ? ' aria-current="page"' : '') + '>' + p + '</button>';
            }
            navHtml += '<button type="button" class="sappc-pagination_btn sappcChPageNext" data-page="' + (cur + 1) + '" ' + (cur >= last ? 'disabled' : '') + ' aria-label="Next">&gt;</button>';
            $('#christeningPagination').html(navHtml);
        }

        renderTable(initialTablePayload);

        $('#christeningTableBody').on('click', '.sappc-icon-action--view, .sappc-icon-action--edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = ($(this).attr('data-record-id') || '').trim();
            if (!id || !applicationDetailsUrl) return;
            $('#chScheduleChristeningId').val(id);
            $('#christeningTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
            $(this).closest('tr').addClass('is-schedule-selected');
            fetchJson(buildQueryUrl(applicationDetailsUrl, {
                christening_id: id
            }), jsonHeaders)
                .done(function(res) {
                    if (res && res.ok && res.data) {
                        applyChristeningApplicationFormObject(res.data);
                        chApplicationDraftsByChristeningId[String(id)] =
                            serializeChristeningApplicationFormToObject();
                        if (typeof window.sappcChristeningApplicationFormOpen === 'function') {
                            window.sappcChristeningApplicationFormOpen(true, {});
                        }
                    }
                })
                .fail(function(xhr) {
                    var msg = 'Could not load application details.';
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

        $('#christeningTableBody').on('click', '.sappc-icon-action--delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = ($(this).attr('data-record-id') || '').trim();
            if (!id || !christeningDeleteUrl) return;

            function runDelete() {
                fetchPostJson(christeningDeleteUrl, {
                    christening_id: parseInt(id, 10)
                }, csrf)
                    .done(function(res) {
                        if (res && res.ok) {
                            delete chApplicationDraftsByChristeningId[String(id)];
                            delete chPaymentDraftsByChristeningId[String(id)];
                            if (($('#chScheduleChristeningId').val() || '').trim() === id) {
                                $('#chScheduleChristeningId').val('');
                            }
                            var msg = (res && res.message) ? res.message : 'Removed.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: msg
                                });
                            } else {
                                window.alert(msg);
                            }
                            fetchRecords();
                        }
                    })
                    .fail(function(xhr) {
                        var msg = 'Could not delete.';
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
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete application & schedule?',
                    text: 'This removes saved application details and clears the reserved schedule for this record.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#950d16',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete',
                }).then(function(r) {
                    if (r.isConfirmed) runDelete();
                });
            } else if (window.confirm('Remove application details and schedule reservation for this record?')) {
                runDelete();
            }
        });

        function fetchQueryParams() {
            var q = {
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                letter: state.letter,
                date_from: state.date_from,
                date_to: state.date_to,
            };
            if (registryType) q.registry_type = registryType;
            return q;
        }

        function fetchRecords() {
            $('#christeningTableBody').html('<tr class="sappc-table-loading"><td colspan="9" class="text-center text-muted py-4">Loading…</td></tr>');
            var reqUrl = buildQueryUrl(url, fetchQueryParams());
            fetchJson(reqUrl, jsonHeaders)
                .done(renderTable)
                .fail(function(xhr) {
                    var msg = xhr && xhr.status ? xhr.status : '?';
                    $('#christeningTableBody').html('<tr><td colspan="9" class="text-center text-danger py-3">Could not load records (' + msg + ').</td></tr>');
                });
        }

        function applySearchFromInput() {
            state.search = ($searchInput.val() || '').trim();
            state.page = 1;
            fetchRecords();
        }

        var searchDebounceTimer = null;
        function scheduleSearchFromInput() {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(applySearchFromInput, 400);
        }

        $('#christeningPagination').on('click', '.sappc-pagination_btn:not(:disabled)', function() {
            var p = parseInt($(this).attr('data-page'), 10);
            if (!isNaN(p) && p >= 1) {
                state.page = p;
                fetchRecords();
            }
        });

        $('#christeningEntries').on('change', function() {
            state.per_page = normalizePerPage($(this).val());
            state.page = 1;
            fetchRecords();
        });

        $panel.find('.sappc-toolbar-date-strip_btn').on('click', function() {
            state.date_from = $('#christeningDateFrom').val() || '';
            state.date_to = $('#christeningDateTo').val() || '';
            state.page = 1;
            fetchRecords();
        });

        $searchInput.on('input', scheduleSearchFromInput).on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                applySearchFromInput();
            }
        });

        $panel.find('.sappc-letter-filter_btn').on('click', function() {
            var $btn = $(this);
            var L = $btn.attr('data-letter');
            if ($btn.hasClass('is-active')) {
                $btn.removeClass('is-active');
                state.letter = '';
            } else {
                $panel.find('.sappc-letter-filter_btn').removeClass('is-active');
                $btn.addClass('is-active');
                state.letter = L;
            }
            state.page = 1;
            fetchRecords();
        });

        $('#christeningReloadBtn').on('click', fetchRecords);

        var $scheduleForm = $('#christeningScheduleRequestForm');
        var $scheduleBtn = $('#christeningScheduleRequestBtn');
        var scheduleSaveUrl = $scheduleForm.attr('data-schedule-save-url') || $scheduleBtn.attr('data-schedule-save-url') || '';
        var scheduleReservedUrl = ($scheduleForm.attr('data-schedule-reserved-url') || '').trim();
        /** ISO date (Y-m-d) -> true for current calendar month view */
        var calendarReservedLookup = {};
        var $scheduleModal = $('#christeningScheduleRequestModal');
        var $calMonthSel = $('#chCalMonth');
        var $calYearSel = $('#chCalYear');
        var $calMonthNumEl = $('#chCalMonthNum');
        var $calDayCells = $('#chCalDayCells');
        var $scheduleDateInput = $('#chScheduleDate');
        var $scheduleTimeInput = $('#chScheduleTime24');

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

        function renderCalendarDayGrid() {
            if (!$calDayCells.length) return;
            var year = calendarViewDate.getFullYear();
            var month = calendarViewDate.getMonth();
            fetchReservedDatesForMonth(year, month, function() {
                renderCalendarDayGridPaint();
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

        function resetScheduleRequestFormForNewEntry() {
            if (!$scheduleForm.length) return;
            $('#chScheduleChristeningId').val('');
            $('#chScheduleRefCode').val($scheduleForm.attr('data-default-reference-code') || '');
            $('#chScheduleContact').val('');
            $('#chScheduleClient').val('');
            $('#chScheduleAddress').val('');
            $scheduleDateInput.val(new Date().toISOString().slice(0, 10));
            $scheduleTimeInput.val('10:00');
            $('#christeningTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
            var sel = parseIsoDate($scheduleDateInput.val());
            if (sel) {
                calendarViewDate = new Date(sel.getFullYear(), sel.getMonth(), 1);
            }
            syncCalendarHeader();
            renderCalendarDayGrid();
        }

        function initScheduleCalendar() {
            if (!$calMonthSel.length || !$calYearSel.length || !$('#chCalPrev').length || !$('#chCalNext').length || !$calDayCells.length || !$scheduleDateInput.length) {
                return;
            }
            populateCalendarSelectors();
            syncCalendarHeader();
            renderCalendarDayGrid();
            $('#chCalPrev').on('click', function() {
                calendarViewDate = new Date(calendarViewDate.getFullYear(), calendarViewDate.getMonth() - 1, 1);
                syncCalendarHeader();
                renderCalendarDayGrid();
            });
            $('#chCalNext').on('click', function() {
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

        $('#christeningTableBody').on('click', 'tr', function(e) {
            if ($(e.target).closest('a,button').length) return;
            var $tr = $(this);
            if ($tr.hasClass('sappc-table-loading') || $tr.hasClass('sappc-table-empty')) return;
            $('#christeningTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
            $tr.addClass('is-schedule-selected');
            if (($tr.attr('data-document-type') || '').trim() !== 'Christening') {
                $('#chScheduleChristeningId').val('');
                return;
            }
            var $tds = $tr.find('td');
            if ($tds.length < 6) return;
            $('#chScheduleChristeningId').val($tr.attr('data-record-id') || '');
            $('#chScheduleRefCode').val(($tds.eq(1).text() || '').trim());
            $('#chScheduleClient').val(($tds.eq(2).text() || '').trim());
            $('#chScheduleAddress').val(($tds.eq(3).text() || '').trim());
            var rawContact = ($tds.eq(5).text() || '').trim();
            $('#chScheduleContact').val((rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' : rawContact);
        });

        if ($scheduleForm.length && scheduleSaveUrl) {
            $scheduleForm.on('submit', function(e) {
                e.preventDefault();
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                var payload = {
                    schedule_date: $('#chScheduleDate').val(),
                    schedule_time: $('#chScheduleTime24').val(),
                    client: ($('#chScheduleClient').val() || '').trim(),
                    sex: ($('#chScheduleSex').val() || '').trim(),
                    contact_number: ($('#chScheduleContact').val() || '').trim(),
                    address: ($('#chScheduleAddress').val() || '').trim(),
                    reference_code: ($('#chScheduleRefCode').val() || '').trim(),
                };
                if (cid) {
                    var n = parseInt(cid, 10);
                    if (!isNaN(n)) payload.christening_id = n;
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
    })(jQuery);
</script>
