<div class="sappcChristeningCertPreviewModal">
    <div class="modal fade" id="sappcCertificatePreviewModal" tabindex="-1"
        aria-labelledby="sappcCertificatePreviewModalTitle" aria-hidden="true">
        {{-- Same shell as christeningApplicationFormModal: modal-xl + scrollable --}}
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered sappcCertPreviewModalDialog">
            <div class="modal-content sappcChristeningAppModal">
                <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                    <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden"
                        id="sappcCertificatePreviewModalTitle">Certificate preview</h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body pt-0 sappcCertPreviewModalBody">
                    <div id="sappcCertificatePreviewMount" class="sappcChristeningCertPreviewSheet"></div>
                </div>
                <div class="modal-footer sappcChristeningAppModalFooter">
                    <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                        id="sappcCertificatePreviewPrintBtn">
                        Print
                    </button>
                    <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnCancel"
                        data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/sappcCertificatePreview.js') }}"></script>
    @endpush
@endonce
