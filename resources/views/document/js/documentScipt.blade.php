@php
    $burialReportUrl = $burialReportUrl ?? route('admin.document.burial-report');
@endphp
<script>
    (function ($) {
        var url = @json($burialReportUrl);
        var $body = $('#sappcDocTableBody');
        var $label = $('#sappcDocReportLabel');
        var $month = $('#sappcDocReportMonth');

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function renderTable(rows) {
            $body.empty();
            if (!rows || !rows.length) {
                $body.append(
                    '<tr><td colspan="7" class="text-center py-3">No records for this month.</td></tr>'
                );
                return;
            }
            rows.forEach(function (r) {
                $body.append(
                    '<tr><td>' +
                        esc(r.no) +
                        '</td><td>' +
                        esc(r.reference_code) +
                        '</td><td>' +
                        esc(r.client) +
                        '</td><td>' +
                        esc(r.address) +
                        '</td><td>' +
                        esc(r.sex) +
                        '</td><td>' +
                        esc(r.contact_number) +
                        '</td><td>' +
                        esc(r.date) +
                        '</td></tr>'
                );
            });
        }

        function loadReport() {
            var m = $month.val();
            if (!m) {
                return;
            }
            $body.html(
                '<tr><td colspan="7" class="text-center py-3">Loading…</td></tr>'
            );
            $.ajax({
                url: url,
                method: 'GET',
                data: { month: m },
                dataType: 'json',
            })
                .done(function (res) {
                    if (!res || !res.ok) {
                        $body.html(
                            '<tr><td colspan="7" class="text-center py-3">Invalid response.</td></tr>'
                        );
                        return;
                    }
                    $label.text(String(res.report_label || '').toUpperCase());
                    renderTable(res.rows || []);
                })
                .fail(function () {
                    $body.html(
                        '<tr><td colspan="7" class="text-center py-3">Could not load data.</td></tr>'
                    );
                });
        }

        $month.on('change', function () {
            var m = $month.val();
            if (m) {
                var u = new URL(window.location.href);
                u.searchParams.set('month', m);
                window.history.replaceState({}, '', u);
            }
            loadReport();
        });

        loadReport();
    })(jQuery);
</script>
