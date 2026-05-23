@php
    $chApplicationFormConfig = array_merge(
        [
            'letterSlots' => 22,
            'nameGroupEndIndices' => [7, 15],
            'contactSlots' => 11,
            'godparentLines' => 13,
        ],
        $chApplicationFormConfig ?? [],
    );
@endphp
<script>
    (function($) {
        'use strict';

        var initialTablePayload = @json($initialTablePayload);
        var chApplicationFormConfig = @json($chApplicationFormConfig);
        var christeningFixedBaptismPlace = 'Saint Anthony of Padua Parish Church';

        function esc(s) {
            return $('<div/>').text(s == null ? '' : String(s)).html();
        }

        /** First character uppercase, remaining letters lowercase (per name field). */
        function sappcCapitalizeNamePart(str) {
            var s = String(str == null ? '' : str).trim();
            if (!s.length) {
                return '';
            }
            return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
        }

        /** Title-case each whitespace-delimited word (for multi-token first/middle/last). */
        function sappcTitleCaseEachWord(str) {
            return String(str == null ? '' : str).trim().split(/\s+/).filter(function(x) {
                return x.length;
            }).map(function(w) {
                return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
            }).join(' ');
        }

        function sappcSplitFullNameToThreeParts(fullName) {
            var s = String(fullName == null ? '' : fullName).trim().replace(/\s+/g, ' ');
            if (!s.length) {
                return { first: '', middle: '', last: '' };
            }
            var parts = s.split(' ');
            if (parts.length === 1) {
                return { first: parts[0], middle: '', last: '' };
            }
            if (parts.length === 2) {
                return { first: parts[0], middle: '', last: parts[1] };
            }
            return {
                first: parts[0],
                middle: parts.slice(1, -1).join(' '),
                last: parts[parts.length - 1]
            };
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

        function sappcChSwal(cfg) {
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

        function sappcChConfirm(cfg) {
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

        function sappcChConfirmDeleteDocument(firstCfg, onFinalConfirm) {
            sappcChConfirm(firstCfg).then(function(r) {
                if (!r.isConfirmed) {
                    return;
                }
                sappcChConfirm({
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

        function sappcSwalSelectChristeningRowFirst() {
            sappcChSwal({
                icon: 'warning',
                title: 'Select a record',
                text: 'Select a christening row in the table first.',
                confirmButtonText: 'OK',
            });
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

        $(document).on('input', '#chScheduleContact, #chPaymentContact', function() {
            var $el = $(this);
            var before = $el.val();
            var formatted = formatPhMobileDisplay(before);
            if (formatted !== before) {
                $el.val(formatted);
            }
        });

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

        function ensureChristeningApplicationNameAndContactGrids() {
            var cfg = chApplicationFormConfig || {};
            var letterSlots = parseInt(cfg.letterSlots, 10) || 22;
            var groupEnds = Array.isArray(cfg.nameGroupEndIndices) ? cfg.nameGroupEndIndices : [7, 15];
            var contactSlots = parseInt(cfg.contactSlots, 10) || 11;

            function isGroupEnd(i) {
                return groupEnds.indexOf(i) !== -1;
            }

            function prependNameCells(wrapId, inputId) {
                var $wrap = $('#' + wrapId);
                if (!$wrap.length) return;
                var $input = inputId ? $('#' + inputId) : $wrap.find('.sappcChOfficialCellInput').first();
                if (!$input.length) return;
                if ($wrap.find('> span.sappcChOfficialCell').length) {
                    return;
                }
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
                placeholder: 'JUAN',
                title: 'Format: one letter per box (e.g. JUAN for Juan)',
            });
            $('#chAppMiddleName').attr({
                maxlength: letterSlots,
                'aria-label': 'Middle name (' + letterSlots + ' letters max)',
                placeholder: 'MARIA',
                title: 'Format: one letter per box (e.g. MARIA for middle name)',
            });
            $('#chAppFamilyName').attr({
                maxlength: letterSlots,
                'aria-label': 'Family name (' + letterSlots + ' letters max)',
                placeholder: 'CRUZ',
                title: 'Format: one letter per box (e.g. CRUZ)',
            });

            var $contactWrap = $('#chAppCellsContact');
            var $contactInput = $('#chAppGuardianContact');
            if ($contactWrap.length && $contactInput.length && $.contains($contactWrap[0], $contactInput[0])) {
                $contactWrap.css('--ch-contact-slots', String(contactSlots));
                var $existingContactCells = $contactWrap.find('> span.sappcChOfficialCell');
                if ($existingContactCells.length !== contactSlots) {
                    $existingContactCells.remove();
                    for (var j = 0; j < contactSlots; j++) {
                        $('<span/>', {
                            class: 'sappcChOfficialCell',
                            'aria-hidden': 'true',
                        }).insertBefore($contactInput);
                    }
                }
                $contactInput.attr('maxlength', contactSlots);
            }
        }

        function initChristeningApplicationGodparentGrid() {
            var cfg = chApplicationFormConfig || {};
            var godparentLines = parseInt(cfg.godparentLines, 10) || 13;
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
                        placeholder: 'Juan D. Cruz',
                    }).appendTo($colA);

                    $('<input/>', {
                        type: 'text',
                        class: 'sappcChOfficialGpLine',
                        name: 'godparent_' + g + 'b',
                        'aria-label': 'Godparent line ' + g + ' (right)',
                        placeholder: 'Juan D. Cruz',
                    }).appendTo($colB);
                }
            }
        }

        function initChristeningApplicationFormGrids() {
            ensureChristeningApplicationNameAndContactGrids();
            initChristeningApplicationGodparentGrid();
        }

        function applyChristeningFieldFormatGuides() {
            function ph(sel, val) {
                var $el = $(sel);
                if ($el.length) {
                    $el.attr('placeholder', val);
                }
            }

            ph('#chAppMiddleName', 'MARIA');
            ph('#chAppPob', 'Barbaza, Antique');
            ph('#chAppFather', 'Juan D. Cruz');
            ph('#chAppMother', 'Maria D. Cruz');
            ph('#chAppParentAddress', 'Street, Barangay, Municipality');
            ph('#chAppMinister', 'Rev. name (optional)');

            ph('#chCertChildFirst', 'Juan');
            ph('#chCertChildMiddle', 'D.');
            ph('#chCertChildLast', 'Cruz');
            ph('#chCertBirthplace', 'Barbaza, Antique');
            ph('#chCertFatherFirst', 'Juan');
            ph('#chCertFatherMiddle', 'D.');
            ph('#chCertFatherLast', 'Cruz');
            ph('#chCertMotherFirst', 'Maria');
            ph('#chCertMotherMiddle', 'D.');
            ph('#chCertMotherLast', 'Cruz');
            ph('#chCertPriest', 'Rev. name');
            ph('#chCertSponsors', 'Juan D. Cruz; Maria D. Cruz');
            ph('#chCertPurpose', 'e.g. school enrollment, passport');
        }

        function syncChristeningApplicationNameGridMetrics() {
            ['chAppCellsFirst', 'chAppCellsMiddle', 'chAppCellsFamily'].forEach(function(wrapId) {
                var wrap = document.getElementById(wrapId);
                if (!wrap) {
                    return;
                }
                var cell = wrap.querySelector('.sappcChOfficialCell');
                if (!cell) {
                    return;
                }
                var w = cell.getBoundingClientRect().width;
                if (!(w > 0)) {
                    return;
                }
                wrap.style.setProperty('--ch-name-cell-w', w.toFixed(3) + 'px');
                wrap.style.setProperty('--ch-name-pitch', (w + 1).toFixed(3) + 'px');
            });
        }

        function syncChristeningContactGridMetrics() {
            var wrap = document.getElementById('chAppCellsContact');
            if (!wrap) {
                return;
            }
            var cell = wrap.querySelector('.sappcChOfficialCell');
            if (!cell) {
                return;
            }
            var w = cell.getBoundingClientRect().width;
            if (!(w > 0)) {
                return;
            }
            wrap.style.setProperty('--ch-contact-cell-w', w.toFixed(3) + 'px');
            wrap.style.setProperty('--ch-contact-pitch', (w + 1).toFixed(3) + 'px');
        }

        function syncChristeningApplicationOfficeGridMetrics() {
            syncChristeningApplicationNameGridMetrics();
            syncChristeningContactGridMetrics();
        }

        initChristeningApplicationFormGrids();
        applyChristeningFieldFormatGuides();
        requestAnimationFrame(function() {
            requestAnimationFrame(syncChristeningApplicationOfficeGridMetrics);
        });

        var chNameGridMetricsResizeTimer = null;
        $(window).on('resize', function() {
            clearTimeout(chNameGridMetricsResizeTimer);
            chNameGridMetricsResizeTimer = setTimeout(syncChristeningApplicationOfficeGridMetrics, 120);
        });
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(syncChristeningApplicationOfficeGridMetrics);
        }

        function christeningApplicationNameSlotLimit() {
            var cfg = chApplicationFormConfig || {};
            return parseInt(cfg.letterSlots, 10) || 22;
        }

        function clampChristeningApplicationNameInputs() {
            var max = christeningApplicationNameSlotLimit();
            ['chAppFirstName', 'chAppMiddleName', 'chAppFamilyName'].forEach(function(id) {
                var $el = $('#' + id);
                if (!$el.length) return;
                var v = String($el.val() || '');
                if (v.length > max) {
                    $el.val(v.slice(0, max));
                }
            });
        }

        $('#christeningApplicationForm').on('input', '#chAppFirstName, #chAppMiddleName, #chAppFamilyName', function() {
            var max = christeningApplicationNameSlotLimit();
            var v = String($(this).val() || '');
            if (v.length > max) {
                $(this).val(v.slice(0, max));
            }
        });

        function parseChristeningApplicationFeeAmount(raw) {
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

        function updateChristeningApplicationFeeTotal() {
            var $form = $('#christeningApplicationForm');
            if (!$form.length) {
                return;
            }
            var $table = $form.find('.sappcChOfficialFeeTable');
            if (!$table.length) {
                return;
            }
            var sum = 0;
            var hasLine = false;
            $table.find('input.sappcChOfficialFeeInput').each(function() {
                var name = $(this).attr('name');
                if (name === 'fee_total') {
                    return;
                }
                var raw = $(this).val();
                if (raw != null && String(raw).trim() !== '') {
                    hasLine = true;
                }
                sum += parseChristeningApplicationFeeAmount(raw);
            });
            var $total = $form.find('input[name="fee_total"]');
            if (!$total.length) {
                return;
            }
            if (!hasLine && sum === 0) {
                $total.val('');
                return;
            }
            $total.val(sum.toFixed(2));
        }

        $('#christeningApplicationForm').on('input change blur', '.sappcChOfficialFeeTable input.sappcChOfficialFeeInput:not([name="fee_total"])', function() {
            updateChristeningApplicationFeeTotal();
        });

        $('#christeningApplicationFormModal').on('shown.bs.modal', function() {
            updateChristeningApplicationFeeTotal();
        });

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
            $form.find('input[type="text"], input[type="date"], input[type="email"], input[type="tel"], textarea').val('');
            $form.find('input[type="checkbox"]').prop('checked', false);
            updateChristeningApplicationFeeTotal();
            var $bp = $('#chAppBaptismPlace');
            if ($bp.length) {
                $bp.val(christeningFixedBaptismPlace);
            }
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
            clampChristeningApplicationNameInputs();
            updateChristeningApplicationFeeTotal();
            var $bp2 = $('#chAppBaptismPlace');
            if ($bp2.length) {
                $bp2.val(christeningFixedBaptismPlace);
            }
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
                ensureChristeningApplicationNameAndContactGrids();
                restoreChristeningApplicationDraftForCurrentRow();
                if (pendingFocusSelector) {
                    var $el = $appModal.find(pendingFocusSelector).first();
                    if ($el.length) $el.trigger('focus');
                    pendingFocusSelector = null;
                }
                requestAnimationFrame(function() {
                    requestAnimationFrame(syncChristeningApplicationOfficeGridMetrics);
                });
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

            $appBtn.on('click', function(e) {
                e.preventDefault();
                if ($appModal.hasClass('show')) {
                    bsModal.hide();
                    return;
                }
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (!cid) {
                    sappcSwalSelectChristeningRowFirst();
                    return;
                }
                if (!applicationDetailsUrl) {
                    window.alert('Application form is not configured.');
                    return;
                }
                $appBtn.prop('disabled', true);
                fetchJson(buildQueryUrl(applicationDetailsUrl, {
                    christening_id: cid
                }), jsonHeaders)
                    .done(function(res) {
                        if (res && res.ok && res.data) {
                            applyChristeningApplicationFormObject(res.data);
                            snapshotChristeningApplicationDraft();
                            ensureChristeningApplicationNameAndContactGrids();
                            bsModal.show();
                            requestAnimationFrame(function() {
                                requestAnimationFrame(syncChristeningApplicationOfficeGridMetrics);
                            });
                        } else {
                            var msg = (res && res.message) ? String(res.message) : 'Could not load application data.';
                            sappcChSwal({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                            });
                        }
                    })
                    .fail(function(xhr) {
                        var msg = 'Could not load application data.';
                        var data = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                        if (data && data.message) {
                            msg = String(data.message);
                        }
                        sappcChSwal({
                            icon: 'error',
                            title: 'Error',
                            text: msg,
                        });
                    })
                    .always(function() {
                        $appBtn.prop('disabled', false);
                    });
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
                            var shouldReopenFromDashboard = isDashboardEmbeddedAppContext();
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
                            if (shouldReopenFromDashboard) {
                                setTimeout(function() {
                                    $('#christeningApplicationFormBtn').trigger('click');
                                }, 120);
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
                contact_number: sappcPhMobileDigitsOnly($('#chPaymentContact').val()),
                address: ($('#chPaymentAddress').val() || '').trim(),
                fee_rows: collectChristeningPaymentFeeRowsFromDom(),
            };
        }

        function applyChristeningPaymentFeeFormObject(data) {
            if (!data || typeof data !== 'object') return;
            $('#chPaymentRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
            $('#chPaymentClient').val(data.client != null ? String(data.client) : '');
            $('#chPaymentContact').val(
                data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
            );
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
        var certificationSaveUrl = ($panel.attr('data-certification-save-url') || '').trim();
        var certificationDetailsUrl = ($panel.attr('data-certification-details-url') || '').trim();
        var christeningDeleteUrl = ($panel.attr('data-christening-delete-url') || '').trim();
        var scheduleDetailsUrl = ($panel.attr('data-schedule-details-url') || '').trim();
        if (!url) return;

        function isDashboardEmbeddedAppContext() {
            try {
                var u = new URL(window.location.href);
                return (u.searchParams.get('embed') || '').trim() === '1';
            } catch (e1) {
                return false;
            }
        }

        tryOpenChristeningApplicationFromDashboardQuery();

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
        var baptismCertBgUrl = @json(asset('assets/certificates/baptismCert.jpg'));

        function applyChristeningCertificationTopFromPayment(data) {
            if (!data || typeof data !== 'object') return;
            $('#chCertRefCode').val(data.reference_code != null ? String(data.reference_code) : '');
            $('#chCertClient').val(data.client != null ? String(data.client) : '');
            $('#chCertContact').val(
                data.contact_number != null ? formatPhMobileDisplay(String(data.contact_number)) : ''
            );
            $('#chCertTopAddress').val(data.address != null ? String(data.address) : '');
        }

        function applyChristeningCertificationFromApplicationDetails(data) {
            if (!data || typeof data !== 'object') return;
            $('#chCertChildFirst').val(sappcCapitalizeNamePart(data.first_name != null ? data.first_name : ''));
            $('#chCertChildMiddle').val(sappcCapitalizeNamePart(data.middle_name != null ? data.middle_name : ''));
            $('#chCertChildLast').val(sappcCapitalizeNamePart(data.family_name != null ? data.family_name : ''));
            $('#chCertBirthday').val(data.date_of_birth != null ? String(data.date_of_birth) : '');
            $('#chCertBirthplace').val(data.place_of_birth != null ? String(data.place_of_birth) : '');
            $('#chCertFatherFirst').val(sappcTitleCaseEachWord(data.father_first_name != null ? data.father_first_name : ''));
            $('#chCertFatherMiddle').val(sappcTitleCaseEachWord(data.father_middle_name != null ? data.father_middle_name : ''));
            $('#chCertFatherLast').val(sappcTitleCaseEachWord(data.father_last_name != null ? data.father_last_name : ''));
            if (!$('#chCertFatherFirst').val() && !$('#chCertFatherMiddle').val() && !$('#chCertFatherLast').val() &&
                data.father_name) {
                var fParts = sappcSplitFullNameToThreeParts(data.father_name);
                $('#chCertFatherFirst').val(sappcTitleCaseEachWord(fParts.first));
                $('#chCertFatherMiddle').val(sappcTitleCaseEachWord(fParts.middle));
                $('#chCertFatherLast').val(sappcTitleCaseEachWord(fParts.last));
            }
            $('#chCertMotherFirst').val(sappcTitleCaseEachWord(data.mother_first_name != null ? data.mother_first_name : ''));
            $('#chCertMotherMiddle').val(sappcTitleCaseEachWord(data.mother_middle_name != null ? data.mother_middle_name : ''));
            $('#chCertMotherLast').val(sappcTitleCaseEachWord(data.mother_last_name != null ? data.mother_last_name : ''));
            if (!$('#chCertMotherFirst').val() && !$('#chCertMotherMiddle').val() && !$('#chCertMotherLast').val() &&
                data.mother_maiden_name) {
                var mParts = sappcSplitFullNameToThreeParts(data.mother_maiden_name);
                $('#chCertMotherFirst').val(sappcTitleCaseEachWord(mParts.first));
                $('#chCertMotherMiddle').val(sappcTitleCaseEachWord(mParts.middle));
                $('#chCertMotherLast').val(sappcTitleCaseEachWord(mParts.last));
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

        function chCertFieldValue(sel) {
            return ($(sel).val() || '').toString().trim();
        }

        function chCertFullName(firstSel, middleSel, lastSel) {
            return [chCertFieldValue(firstSel), chCertFieldValue(middleSel), chCertFieldValue(lastSel)]
                .join(' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function splitCertDateParts(iso) {
            var out = { day: '', month: '', year: '' };
            if (!iso) return out;
            var s = String(iso).slice(0, 10);
            var p = s.split('-');
            if (p.length !== 3) return out;
            var mIdx = parseInt(p[1], 10) - 1;
            var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'
            ];
            out.day = String(parseInt(p[2], 10) || p[2]);
            out.month = (mIdx >= 0 && mIdx < 12) ? months[mIdx] : p[1];
            out.year = p[0];
            return out;
        }

        function ordinalDay(day) {
            var n = parseInt(day, 10);
            if (!n) return day || '';
            var mod100 = n % 100;
            if (mod100 >= 11 && mod100 <= 13) return n + 'th';
            var mod10 = n % 10;
            if (mod10 === 1) return n + 'st';
            if (mod10 === 2) return n + 'nd';
            if (mod10 === 3) return n + 'rd';
            return n + 'th';
        }

        function formatCertDate(iso) {
            if (!iso) return '';
            var parts = splitCertDateParts(iso);
            if (!parts.month || !parts.day || !parts.year) return String(iso);
            return parts.month + ' ' + parts.day + ', ' + parts.year;
        }

        function normalizeBaptismSponsors(value) {
            return String(value || '')
                .replace(/\s*[;|]\s*/g, ', ')
                .replace(/\s*,\s*/g, ', ')
                .replace(/\s+/g, ' ')
                .replace(/,\s*,+/g, ', ')
                .replace(/^,\s*|\s*,\s*$/g, '')
                .trim();
        }

        function splitBaptismSponsors(value) {
            var normalized = normalizeBaptismSponsors(value);
            var out = { line1: '', line2: '' };
            if (!normalized) return out;

            var line1Limit = 48;
            var parts = normalized.split(',').map(function(part) {
                return part.trim();
            }).filter(function(part) {
                return part !== '';
            });

            parts.forEach(function(part) {
                var candidate = out.line1 ? out.line1 + ', ' + part : part;
                if (!out.line2 && candidate.length <= line1Limit) {
                    out.line1 = candidate;
                    return;
                }
                out.line2 = out.line2 ? out.line2 + ', ' + part : part;
            });

            if (!out.line1 && out.line2.length > line1Limit) {
                var cutAt = out.line2.lastIndexOf(' ', line1Limit);
                if (cutAt < 18) cutAt = line1Limit;
                out.line1 = out.line2.slice(0, cutAt).replace(/\s*,\s*$/, '').trim();
                out.line2 = out.line2.slice(cutAt).replace(/^\s*,\s*/, '').trim();
            }

            return out;
        }

        var baptismPrintWindow = null;
        var baptismPrintBlobUrl = '';

        function prepareBaptismPrintWindow() {
            var printWin = window.open('', 'sappcBaptismCertificatePrint');
            if (!printWin) return null;
            baptismPrintWindow = printWin;
            return printWin;
        }

        function collectBaptismPrintData() {
            var birth = splitCertDateParts(chCertFieldValue('#chCertBirthday'));
            var baptism = splitCertDateParts(chCertFieldValue('#chCertDateReceived'));
            var sponsors = splitBaptismSponsors(chCertFieldValue('#chCertSponsors'));
            return {
                full_name: chCertFullName('#chCertChildFirst', '#chCertChildMiddle', '#chCertChildLast'),
                birth_day: ordinalDay(birth.day),
                birth_month: birth.month,
                birth_year: birth.year,
                birth_place: chCertFieldValue('#chCertBirthplace'),
                father: chCertFullName('#chCertFatherFirst', '#chCertFatherMiddle', '#chCertFatherLast'),
                mother: chCertFullName('#chCertMotherFirst', '#chCertMotherMiddle', '#chCertMotherLast'),
                address: [chCertFieldValue('#chCertBarangay'), chCertFieldValue('#chCertMunicipality'), chCertFieldValue('#chCertProvince')]
                    .filter(function(v) { return v !== ''; })
                    .join(', '),
                baptism_day: ordinalDay(baptism.day),
                baptism_month: baptism.month,
                baptism_year: baptism.year,
                priest: chCertFieldValue('#chCertPriest'),
                sponsors: sponsors.line1,
                sponsors_extra: sponsors.line2,
                purpose: chCertFieldValue('#chCertPurpose'),
                book_no: chCertFieldValue('#chCertBookNo'),
                page_no: chCertFieldValue('#chCertPageNo'),
                register_no: chCertFieldValue('#chCertRegisterNo'),
                date_issued: formatCertDate(chCertFieldValue('#chCertDateIssued')),
            };
        }

        function printBaptismCertificationSheet(printWin, shouldPrint) {
            var tplNode = document.getElementById('baptismCertificatePrintableTemplate');
            if (!tplNode || !tplNode.content) {
                window.alert('Print template not found.');
                return false;
            }
            var tplStyleNode = tplNode.content.querySelector('style');
            var tplWrapNode = tplNode.content.querySelector('.bap-wrap');
            if (!tplStyleNode || !tplWrapNode) {
                window.alert('Print template is incomplete.');
                return false;
            }

            var openedHere = false;
            if (!printWin && baptismPrintWindow && !baptismPrintWindow.closed) {
                printWin = baptismPrintWindow;
            }
            if (!printWin || printWin.closed) {
                printWin = prepareBaptismPrintWindow();
                openedHere = true;
            }
            if (!printWin) {
                window.alert('Pop-up blocked. Please allow pop-ups to print the certificate.');
                return false;
            }
            baptismPrintWindow = printWin;
            shouldPrint = shouldPrint !== false;

            var printData = collectBaptismPrintData();
            var tplWrapClone = tplWrapNode.cloneNode(true);
            var bg = tplWrapClone.querySelector('.bap-bg');
            if (bg) bg.setAttribute('src', baptismCertBgUrl);

            function setCloneVal(id, value) {
                var el = tplWrapClone.querySelector('#' + id);
                if (!el) return;
                var v = value || '';
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                    el.setAttribute('value', v);
                    el.value = v;
                } else {
                    el.textContent = v;
                }
            }

            function setClonePurpose(value) {
                var el = tplWrapClone.querySelector('#bapPurpose');
                if (!el) return;
                var v = String(value || '').trim();
                var isDefault = !v || v.toUpperCase() === 'FOR ALL LEGAL PURPOSES';
                if (isDefault) {
                    el.textContent = '';
                    el.classList.add('is-hidden');
                    return;
                }
                el.textContent = v;
                el.classList.remove('is-hidden');
            }

            setCloneVal('bapFullName', printData.full_name);
            setCloneVal('bapBirthDay', printData.birth_day);
            setCloneVal('bapBirthMonthYear', printData.birth_month);
            setCloneVal('bapBirthYear', printData.birth_year);
            setCloneVal('bapBirthplace', printData.birth_place);
            setCloneVal('bapFatherName', printData.father);
            setCloneVal('bapMotherName', printData.mother);
            setCloneVal('bapAddress', printData.address);
            setCloneVal('bapBaptismDay', printData.baptism_day);
            setCloneVal('bapBaptismMonthYear', printData.baptism_month);
            setCloneVal('bapBaptismYear', printData.baptism_year);
            setCloneVal('bapPriestName', printData.priest);
            setCloneVal('bapSponsors', printData.sponsors);
            setCloneVal('bapSponsorsExtra', printData.sponsors_extra);
            setClonePurpose(printData.purpose);
            setCloneVal('bapBookNo', printData.book_no);
            setCloneVal('bapPageNo', printData.page_no);
            setCloneVal('bapRegisterNo', printData.register_no);
            setCloneVal('bapDateIssued', printData.date_issued);

            var html = '<!doctype html><html><head><meta charset="utf-8"><title>Baptism Certificate</title><style>' +
                (tplStyleNode.textContent || '') +
                '</style></head><body>' + (tplWrapClone.outerHTML || '') + '</body></html>';
            baptismPrintBlobUrl = URL.createObjectURL(new Blob([html], { type: 'text/html' }));
            var didPrint = false;

            function populateAndPrint() {
                if (didPrint) {
                    return;
                }
                didPrint = true;
                printWin.focus();
                if (shouldPrint) {
                    setTimeout(function() {
                        printWin.print();
                    }, 350);
                }
            }

            printWin.onload = populateAndPrint;
            printWin.location.href = baptismPrintBlobUrl;
            setTimeout(function() {
                populateAndPrint();
            }, openedHere ? 1100 : 900);
            return true;
        }

        window.sappcReloadBaptismPrintWindow = function(printWin) {
            return printBaptismCertificationSheet(printWin || baptismPrintWindow, false);
        };

        function saveChristeningCertificationRecord() {
            var cid = ($('#chScheduleChristeningId').val() || '').trim();
            if (!cid) {
                sappcSwalSelectChristeningRowFirst();
                return $.Deferred().reject({
                    responseJSON: {
                        message: 'Please select a christening record first.'
                    }
                }).promise();
            }
            if (!certificationSaveUrl) {
                return $.Deferred().reject({
                    responseJSON: {
                        message: 'Certification save is not configured.'
                    }
                }).promise();
            }

            var payload = {
                christening_id: parseInt(cid, 10),
                reference_code: chCertFieldValue('#chCertRefCode'),
                client: chCertFieldValue('#chCertClient'),
                contact_number: sappcPhMobileDigitsOnly(chCertFieldValue('#chCertContact')),
                top_address: chCertFieldValue('#chCertTopAddress'),
                child_first_name: chCertFieldValue('#chCertChildFirst'),
                child_middle_name: chCertFieldValue('#chCertChildMiddle'),
                child_last_name: chCertFieldValue('#chCertChildLast'),
                birthday: chCertFieldValue('#chCertBirthday'),
                birthplace: chCertFieldValue('#chCertBirthplace'),
                father_first_name: chCertFieldValue('#chCertFatherFirst'),
                father_middle_name: chCertFieldValue('#chCertFatherMiddle'),
                father_last_name: chCertFieldValue('#chCertFatherLast'),
                mother_first_name: chCertFieldValue('#chCertMotherFirst'),
                mother_middle_name: chCertFieldValue('#chCertMotherMiddle'),
                mother_last_name: chCertFieldValue('#chCertMotherLast'),
                barangay: chCertFieldValue('#chCertBarangay'),
                municipality: chCertFieldValue('#chCertMunicipality'),
                province: chCertFieldValue('#chCertProvince'),
                date_received: chCertFieldValue('#chCertDateReceived'),
                priest: chCertFieldValue('#chCertPriest'),
                sponsors: chCertFieldValue('#chCertSponsors'),
                purpose: chCertFieldValue('#chCertPurpose'),
                book_no: chCertFieldValue('#chCertBookNo'),
                register_no: chCertFieldValue('#chCertRegisterNo'),
                page_no: chCertFieldValue('#chCertPageNo'),
                date_issued: chCertFieldValue('#chCertDateIssued'),
            };

            return fetchPostJson(certificationSaveUrl, payload, csrf);
        }

        $(document)
            .off('submit.sappcBaptismPrint', '#christeningCertificationForm')
            .on('submit.sappcBaptismPrint', '#christeningCertificationForm', function(e) {
                e.preventDefault();
                printBaptismCertificationSheet();
            });

        $(document)
            .off('click.sappcBaptismPrint', '#chCertAddRecordBtn')
            .on('click.sappcBaptismPrint', '#chCertAddRecordBtn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true);
                saveChristeningCertificationRecord()
                    .done(function(res) {
                        printBaptismCertificationSheet();
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

        if ($certModal.length && $certBtn.length && typeof bootstrap !== 'undefined') {
            var certBsModal = bootstrap.Modal.getOrCreateInstance($certModal[0]);

            $('#christeningCertificationForm').on('blur', '#chCertChildFirst, #chCertChildMiddle, #chCertChildLast', function() {
                var $el = $(this);
                $el.val(sappcCapitalizeNamePart($el.val()));
            });
            $('#christeningCertificationForm').on('blur', '#chCertFatherFirst, #chCertFatherMiddle, #chCertFatherLast, #chCertMotherFirst, #chCertMotherMiddle, #chCertMotherLast', function() {
                var $el = $(this);
                $el.val(sappcTitleCaseEachWord($el.val()));
            });

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
                if (!paymentDetailsUrl || !certificationDetailsUrl) {
                    window.alert('Certification load is not configured.');
                    return;
                }
                $.when(
                    fetchJson(buildQueryUrl(paymentDetailsUrl, {
                        christening_id: cid
                    }), jsonHeaders),
                    fetchJson(buildQueryUrl(certificationDetailsUrl, {
                        christening_id: cid
                    }), jsonHeaders)
                ).done(function(payTuple, certTuple) {
                    var pay = payTuple && payTuple[0] ? payTuple[0] : null;
                    var cert = certTuple && certTuple[0] ? certTuple[0] : null;
                    if (pay && pay.ok && pay.data) applyChristeningCertificationTopFromPayment(pay.data);
                    if (cert && cert.ok && cert.data && typeof cert.data === 'object') {
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

        }

        var $searchInput = $('#christeningSearch');

        var meta0 = initialTablePayload.meta || {};
        var state = {
            page: meta0.current_page || 1,
            per_page: normalizePerPage(meta0.per_page || 10),
            search: ($searchInput.length ? ($searchInput.val() || '').trim() : ''),
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
                            sappcChSwal({
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
                        sappcChSwal({
                            icon: 'error',
                            title: 'Error',
                            text: msg,
                        });
                    });
            }

            sappcChConfirmDeleteDocument({
                title: 'Delete christening record?',
                text: 'This permanently deletes this christening row from the registry and removes related certification and application detail rows.',
                confirmButtonText: 'Yes, delete',
            }, runDelete);
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

        function tryOpenChristeningApplicationFromDashboardQuery() {
            try {
                var u = new URL(window.location.href);
                var id = (u.searchParams.get('sappc_dash_app') || '').trim();
                if (!id) {
                    return;
                }
                u.searchParams.delete('sappc_dash_app');
                var q = u.searchParams.toString();
                window.history.replaceState({}, '', u.pathname + (q ? '?' + q : '') + u.hash);
                $('#chScheduleChristeningId').val(id);
                $('#christeningTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
                $('#christeningTableBody tr').each(function() {
                    if (($(this).attr('data-record-id') || '').trim() === id) {
                        $(this).addClass('is-schedule-selected');
                        return false;
                    }
                });
                setTimeout(function() {
                    $('#christeningApplicationFormBtn').trigger('click');
                }, 0);
            } catch (e1) {}
        }

        function fetchRecords() {
            $('#christeningTableBody').html('<tr class="sappc-table-loading"><td colspan="9" class="text-center text-muted py-4">Loading…</td></tr>');
            var reqUrl = buildQueryUrl(url, fetchQueryParams());
            fetchJson(reqUrl, jsonHeaders)
                .done(function(res) {
                    renderTable(res);
                    tryOpenChristeningApplicationFromDashboardQuery();
                })
                .fail(function(xhr) {
                    var msg = xhr && xhr.status ? xhr.status : '?';
                    $('#christeningTableBody').html('<tr><td colspan="9" class="text-center text-danger py-3">Could not load records (' + msg + ').</td></tr>');
                });
        }

        function applySearchFromInput() {
            if (!$searchInput.length) return;
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

        if ($searchInput.length) {
            $searchInput.on('input', scheduleSearchFromInput).on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    applySearchFromInput();
                }
            });
        }

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
        var scheduleReservedUrl = (
            $scheduleForm.attr('data-schedule-reserved-url') ||
            $scheduleBtn.attr('data-schedule-reserved-url') ||
            ''
        ).trim();
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
            $scheduleDateInput.val('');
            $scheduleTimeInput.val('10:00');
            $('#christeningTableBody tr.is-schedule-selected').removeClass('is-schedule-selected');
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
            if ($tr.hasClass('is-schedule-selected')) {
                resetScheduleRequestFormForNewEntry();
                return;
            }
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
            var rawSex = ($tds.eq(4).text() || '').trim();
            if (rawSex === '\u2014' || rawSex === '-' || rawSex === '') {
                $('#chScheduleSex').val('');
            } else {
                $('#chScheduleSex').val(rawSex);
            }
            var rawContact = ($tds.eq(5).text() || '').trim();
            $('#chScheduleContact').val(
                (rawContact === '\u2014' || rawContact === '-' || rawContact === '') ? '' : formatPhMobileDisplay(rawContact)
            );
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
                    contact_number: sappcPhMobileDigitsOnly($('#chScheduleContact').val()),
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
                            var okMsg =
                                res && res.message ? String(res.message) : 'Schedule reserved successfully.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Reserved',
                                    text: okMsg,
                                    confirmButtonText: 'OK',
                                });
                            } else {
                                window.alert(okMsg);
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

        function applyChristeningScheduleDetailsToForm(d) {
            if (!d || typeof d !== 'object') return;
            if (d.christening_id != null && String(d.christening_id).trim() !== '') {
                $('#chScheduleChristeningId').val(String(d.christening_id).trim());
            }
            $('#chScheduleRefCode').val(d.reference_code != null ? String(d.reference_code) : '');
            $('#chScheduleClient').val(d.client != null ? String(d.client) : '');
            $('#chScheduleAddress').val(d.address != null ? String(d.address) : '');
            $('#chScheduleSex').val(d.sex != null ? String(d.sex) : '');
            var cn = d.contact_number != null ? String(d.contact_number).trim() : '';
            $('#chScheduleContact').val(cn !== '' ? formatPhMobileDisplay(cn) : '');
            var sd = d.schedule_date != null ? String(d.schedule_date).trim().slice(0, 10) : '';
            $('#chScheduleDate').val(sd);
            var st = d.schedule_time != null ? String(d.schedule_time).trim() : '';
            if (st.length >= 5) {
                st = st.slice(0, 5);
            }
            $('#chScheduleTime24').val(st || '10:00');
        }

        function syncScheduleModalCalendarFromInputs() {
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
            var cid = ($('#chScheduleChristeningId').val() || '').trim();
            var $sel = $('#christeningTableBody tr.is-schedule-selected');
            if (!cid && $sel.length) {
                var doc = ($sel.attr('data-document-type') || '').trim();
                if (doc === 'Christening') {
                    var rid = ($sel.attr('data-record-id') || '').trim();
                    if (rid) {
                        $('#chScheduleChristeningId').val(rid);
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
                var cid = ($('#chScheduleChristeningId').val() || '').trim();
                if (cid && scheduleDetailsUrl) {
                    fetchJson(buildQueryUrl(scheduleDetailsUrl, {
                        christening_id: cid,
                    }), jsonHeaders)
                        .done(function(res) {
                            if (res && res.ok && res.data) {
                                applyChristeningScheduleDetailsToForm(res.data);
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
                            syncScheduleModalCalendarFromInputs();
                        });
                } else {
                    syncScheduleModalCalendarFromInputs();
                }
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
