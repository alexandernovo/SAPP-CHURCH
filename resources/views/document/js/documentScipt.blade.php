@php
    $applicationReportUrl = $applicationReportUrl ?? route('admin.document.application-form-report');
@endphp
<script>
    (function ($) {
        var url = @json($applicationReportUrl);
        var $table = $('#sappcDocDataTable');
        var $body = $('#sappcDocTableBody');
        var $label = $('#sappcDocReportLabel');
        var $service = $('#sappcDocReportService');
        var $month = $('#sappcDocReportMonth');
        var $type = $('#sappcDocType');

        function currentServiceType() {
            var t = $type.val();
            return t && t !== '' ? t : 'burial';
        }

        function destroyDocDataTable() {
            if (!$table.length || !$.fn.DataTable) {
                return;
            }
            if ($.fn.DataTable.isDataTable('#sappcDocDataTable')) {
                $table.DataTable().destroy();
            }
        }

        function showTableMessage(html) {
            destroyDocDataTable();
            $body.html(
                '<tr><td colspan="6" class="text-center py-3">' + html + '</td></tr>'
            );
        }

        function initDocDataTable(rows) {
            destroyDocDataTable();
            $body.empty();

            var data = Array.isArray(rows) ? rows : [];

            $table.DataTable({
                data: data,
                columns: [
                    { data: 'no', className: 'text-end' },
                    { data: 'reference_code' },
                    { data: 'client', render: function(data) {
                        var label = typeof sappcFormatClientDisplayName === 'function'
                            ? sappcFormatClientDisplayName(data)
                            : (data == null ? '' : String(data));
                        return $('<div/>').text(label).html();
                    }},
                    { data: 'address', render: function(data) {
                        var label = typeof sappcFormatAddress === 'function'
                            ? sappcFormatAddress(data)
                            : (data == null ? '' : String(data));
                        return $('<div/>').text(label).html();
                    }},
                    { data: 'contact_number' },
                    { data: 'date' },
                ],
                order: [[5, 'desc']],
                paging: false,
                ordering: false,
                info: false,
                autoWidth: false,
                deferRender: true,
                dom: "<'row align-items-center mb-2 g-2 sappc-doc-dt-controls'<'col-sm-12 col-md-6 ms-auto'f>>rt",
                language: {
                    emptyTable: 'No records for this month.',
                    zeroRecords: 'No matching records found.',
                    search: 'Search:',
                },
                columnDefs: [{ targets: 0, type: 'num' }],
            });
        }

        function loadReport(serviceType, monthOverride) {
            var m = monthOverride != null && monthOverride !== '' ? monthOverride : $month.val();
            var st = serviceType != null && serviceType !== '' ? serviceType : currentServiceType();
            if (!m) {
                return;
            }

            showTableMessage('Loading…');

            $.ajax({
                url: url,
                method: 'GET',
                data: { month: m, service_type: st },
                dataType: 'json',
            })
                .done(function (res) {
                    if (!res || !res.ok) {
                        showTableMessage('Invalid response.');
                        return;
                    }
                    if ($service.length) {
                        $service.text(String(res.service_heading || '').toUpperCase());
                    }
                    $label.text(String(res.report_label || '').toUpperCase());
                    try {
                        initDocDataTable(res.rows || []);
                    } catch (e) {
                        showTableMessage('Could not build the data table.');
                        return;
                    }
                })
                .fail(function () {
                    showTableMessage('Could not load data.');
                });
        }

        $month.on('change', function () {
            var m = $month.val();
            if (m) {
                var u = new URL(window.location.href);
                u.searchParams.set('month', m);
                window.history.replaceState({}, '', u);
            }
            if ($('#sappcDocumentSheet').is(':visible')) {
                loadReport(currentServiceType(), m);
            }
        });

        $(document).on('sappc:doc-report', function (_e, payload) {
            var st = payload && payload.type ? payload.type : currentServiceType();
            var mo = payload && payload.month != null ? payload.month : $month.val();
            loadReport(st, mo);
        });
    })(jQuery);
</script>
