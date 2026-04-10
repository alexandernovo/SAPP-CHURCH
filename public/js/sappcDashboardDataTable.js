/**
 * Registry data-table helpers: entries-per-page must match server options (data-per-page-options).
 */
(function (w) {
    'use strict';

    function allowedPerPageValues() {
        var el = document.getElementById('sappcRecordsPanel');
        if (!el) {
            return [10, 25, 50, 100];
        }
        var raw = el.getAttribute('data-per-page-options');
        if (!raw) {
            return [10, 25, 50, 100];
        }
        try {
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) && parsed.length ? parsed : [10, 25, 50, 100];
        } catch (e) {
            return [10, 25, 50, 100];
        }
    }

    w.sappcNormalizePerPage = function (value) {
        var allowed = allowedPerPageValues();
        var n = parseInt(value, 10);
        return allowed.indexOf(n) !== -1 ? n : allowed[0];
    };
})(window);
