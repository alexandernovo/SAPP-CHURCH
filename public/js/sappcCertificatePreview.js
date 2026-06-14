(function ($) {
    'use strict';

    var bsModal = null;

    function getModal() {
        return $('#sappcCertificatePreviewModal');
    }

    function ensureModal() {
        var $modal = getModal();
        if (!$modal.length || typeof bootstrap === 'undefined') {
            return null;
        }
        if (!bsModal) {
            bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
            $modal.on('hidden.bs.modal.sappcCertPreview', function () {
                $('#sappcCertificatePreviewMount').empty();
            });
        }
        return bsModal;
    }

    window.sappcShowCertificatePreview = function (opts) {
        opts = opts || {};
        var modal = ensureModal();
        if (!modal) {
            window.alert('Certificate preview is not available on this page.');
            return;
        }

        var title = opts.title || 'Certificate preview';
        $('#sappcCertificatePreviewModalTitle').text(title);
        $('#sappcCertificatePreviewMount').empty();

        if (typeof opts.render === 'function') {
            opts.render(document.getElementById('sappcCertificatePreviewMount'));
        }

        $('#sappcCertificatePreviewPrintBtn')
            .off('click.sappcCertPreview')
            .on('click.sappcCertPreview', function () {
                if (typeof opts.onPrint === 'function') {
                    opts.onPrint();
                }
            })
            .toggle(typeof opts.onPrint === 'function');

        modal.show();
    };

    window.sappcHideCertificatePreview = function () {
        if (bsModal) {
            bsModal.hide();
        }
    };
})(jQuery);
