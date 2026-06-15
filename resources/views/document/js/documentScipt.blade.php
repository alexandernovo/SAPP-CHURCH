@php
    $applicationReportUrl = $applicationReportUrl ?? route('admin.document.application-form-report');
@endphp
<script>
    (function ($) {
        var url = @json($applicationReportUrl);
        var $root = $('#sappcDocPageRoot');
        var $picker = $('#sappcDocPickerWrap');
        var $sheet = $('#sappcDocumentSheet');
        var $type = $('#sappcDocType');
        var $btn = $('#sappcDocViewReportBtn');
        var $monthPicker = $('#sappcDocReportMonth');
        var $monthToolbar = $('#sappcDocReportMonthToolbar');
        var $tbody = $('#sappcDocTableBody');
        var $service = $('#sappcDocReportService');
        var $label = $('#sappcDocReportLabel');

        function esc(v) {
            return $('<div/>').text(v == null ? '' : String(v)).html();
        }

        function syncViewBtn() {
            var ok = $type.val() !== '' && $type.val() != null;
            $btn.prop('disabled', !ok);
        }

        function showSheet() {
            $picker.addClass('sappc-doc-picker-wrap--hidden');
            $sheet.show();
            $root.addClass('sappc-doc-page--report-active');
        }

        function formatClient(value) {
            if (typeof sappcFormatClientDisplayName === 'function') {
                return sappcFormatClientDisplayName(value);
            }
            return value == null ? '' : String(value);
        }

        function formatAddress(value) {
            if (typeof sappcFormatAddress === 'function') {
                return sappcFormatAddress(value);
            }
            return value == null ? '' : String(value);
        }

        function renderRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                $tbody.html(
                    '<tr><td colspan="6" class="text-center text-muted py-3">No records for this month.</td></tr>'
                );
                return;
            }

            var html = '';
            rows.forEach(function (r) {
                html +=
                    '<tr>' +
                    '<td class="text-center">' + esc(r.no) + '</td>' +
                    '<td class="text-center">' + esc(r.reference_code) + '</td>' +
                    '<td class="text-center">' + esc(formatClient(r.client)) + '</td>' +
                    '<td class="text-center">' + esc(formatAddress(r.address)) + '</td>' +
                    '<td class="text-center">' + esc(r.contact_number) + '</td>' +
                    '<td class="text-center">' + esc(r.date) + '</td>' +
                    '</tr>';
            });
            $tbody.html(html);
        }

        function monthLabelFromYm(ym) {
            var parts = String(ym || '').split('-');
            if (parts.length !== 2) {
                return '';
            }
            var year = parseInt(parts[0], 10);
            var monthNum = parseInt(parts[1], 10);
            if (isNaN(year) || isNaN(monthNum) || monthNum < 1 || monthNum > 12) {
                return '';
            }
            var names = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return names[monthNum - 1] + ' ' + year;
        }

        function updateReportLabel(monthVal, reportLabel) {
            var labelText = reportLabel || monthLabelFromYm(monthVal);
            if ($label.length && labelText) {
                $label.text(String(labelText).toUpperCase());
            }
        }

        function syncMonthInputs(monthVal) {
            if (!monthVal) {
                return;
            }
            if ($monthPicker.length) {
                $monthPicker.val(monthVal);
            }
            if ($monthToolbar.length) {
                $monthToolbar.val(monthVal);
            }
            updateReportLabel(monthVal);
        }

        function pushMonthToUrl(monthVal) {
            if (!monthVal) {
                return;
            }
            var u = new URL(window.location.href);
            u.searchParams.set('month', monthVal);
            window.history.replaceState({}, '', u);
        }

        function loadReport(forcedMonth) {
            var typeVal = ($type.val() || '').toString().trim();
            var monthVal = (forcedMonth || '').toString().trim();
            if (!monthVal) {
                monthVal = ($monthToolbar.val() || $monthPicker.val() || '').toString().trim();
            }
            if (!typeVal || !monthVal) {
                return;
            }

            syncMonthInputs(monthVal);
            pushMonthToUrl(monthVal);

            $tbody.html(
                '<tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>'
            );

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                cache: false,
                data: {
                    month: monthVal,
                    service_type: typeVal,
                    _: Date.now(),
                },
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .done(function (res) {
                    if (!res || !res.ok) {
                        $tbody.html(
                            '<tr><td colspan="6" class="text-center text-danger py-3">Invalid response.</td></tr>'
                        );
                        return;
                    }
                    if ($service.length && res.service_heading) {
                        $service.text(String(res.service_heading).toUpperCase());
                    }
                    updateReportLabel(monthVal, res.report_label || '');
                    renderRows(res.rows || []);
                })
                .fail(function () {
                    $tbody.html(
                        '<tr><td colspan="6" class="text-center text-danger py-3">Could not load data.</td></tr>'
                    );
                });
        }

        $type.on('change', syncViewBtn);
        syncViewBtn();

        var initialMonth = ($monthPicker.val() || $monthToolbar.val() || '').toString().trim();
        if (initialMonth) {
            syncMonthInputs(initialMonth);
        }

        $monthToolbar.on('change input', function () {
            var v = ($(this).val() || '').toString().trim();
            if (!v) {
                return;
            }
            syncMonthInputs(v);
            pushMonthToUrl(v);
            if ($sheet.is(':visible')) {
                loadReport(v);
            }
        });

        $monthPicker.on('change', function () {
            var m = $(this).val() || '';
            if (!m) {
                return;
            }
            syncMonthInputs(m);
            pushMonthToUrl(m);
            if ($sheet.is(':visible')) {
                loadReport(m);
            }
        });

        $btn.on('click', function () {
            var typeVal = ($type.val() || '').toString().trim();
            if (!typeVal) {
                return;
            }
            var monthVal = ($monthPicker.val() || '').toString().trim();
            syncMonthInputs(monthVal);
            showSheet();
            loadReport(monthVal);
        });

        $('#sappcDocPrintBtn').on('click', function () {
            window.print();
        });

        (function () {
            var savedPrintTitle = '';

            window.addEventListener('beforeprint', function () {
                savedPrintTitle = document.title;
                document.title = ' ';
            });

            window.addEventListener('afterprint', function () {
                if (savedPrintTitle !== '') {
                    document.title = savedPrintTitle;
                    savedPrintTitle = '';
                }
            });
        })();

        $(document).on('click', '[data-doc-export]', function () {
            var fmt = $(this).attr('data-doc-export') || '';
            fmt = fmt.toLowerCase();
            if (fmt === 'pdf' || fmt === 'docx' || fmt === 'xlsx') {
                window.alert('Download ' + fmt.toUpperCase() + ' is not wired yet. Use Print Report for a paper copy.');
            }
        });
    })(jQuery);
</script>
