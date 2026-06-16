    <div class="sappcWeddingCertificationModal">
        <div class="modal fade" id="weddingCertificationModal" tabindex="-1"
            aria-labelledby="weddingCertificationModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcCertModalDialog">
                <div class="modal-content sappcCertModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 fw-normal visually-hidden"
                            id="weddingCertificationModalTitle">Wedding certification form</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcCertModalForm" id="weddingCertificationForm" action="#" method="post"
                            autocomplete="off" data-default-reference-code="{{ $generatedReferenceCode ?? '' }}">
                            <div class="sappcCertModalMasthead">
                                <div class="sappcCertModalLogoWrap">
                                    <img src="{{ asset('assets/logos/SAPPC.png') }}" width="72" height="72"
                                        alt="Parish of St. Anthony of Padua, Barbaza"
                                        class="sappcCertModalLogoImg">
                                </div>
                                <h3 class="sappcCertModalTitle">Wedding Certification Form</h3>
                            </div>

                            <div class="sappcCertModalMetaGrid">
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="wdCertRefCode">Reference Code</label>
                                    <input type="text" class="sappcCertModalInput" id="wdCertRefCode"
                                        name="reference_code" value="{{ $generatedReferenceCode ?? '' }}" readonly
                                        tabindex="-1" aria-readonly="true" placeholder="Auto-generated"
                                        title="Auto-generated reference code">
                                    <label class="sappcCertModalLabel" for="wdCertClient">Client</label>
                                    <input type="text" class="sappcCertModalInput" id="wdCertClient" name="client"
                                        value="" readonly>
                                </div>
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="wdCertContact">Contact Number</label>
                                    <input type="text" class="sappcCertModalInput" id="wdCertContact"
                                        name="contact_number" value="" inputmode="tel">
                                    <label class="sappcCertModalLabel" for="wdCertTopAddress">Address</label>
                                    <input type="text" class="sappcCertModalInput" id="wdCertTopAddress" name="top_address"
                                        value="">
                                </div>
                            </div>

                            <h4 class="sappcCertModalSectionTitle">Wedding Information</h4>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Complete Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertChildFirst" name="child_first_name" placeholder="First Name"
                                        aria-label="First name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertChildMiddle" name="child_middle_name" placeholder="Middle Name"
                                        aria-label="Middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertChildLast" name="child_last_name" placeholder="Last Name"
                                        aria-label="Last name">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="wdCertBirthday">Birthday</label>
                                    <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertBirthday" name="birthday">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="wdCertBirthplace">Birthplace</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertBirthplace" name="birthplace" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Father's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertFatherFirst" name="father_first_name" placeholder="First Name"
                                        aria-label="Father first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertFatherMiddle" name="father_middle_name" placeholder="Middle Name"
                                        aria-label="Father middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertFatherLast" name="father_last_name" placeholder="Last Name"
                                        aria-label="Father last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Mother's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertMotherFirst" name="mother_first_name" placeholder="First Name"
                                        aria-label="Mother first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertMotherMiddle" name="mother_middle_name" placeholder="Middle Name"
                                        aria-label="Mother middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertMotherLast" name="mother_last_name" placeholder="Last Name"
                                        aria-label="Mother last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Address</span>
                                <div class="sappcCertModalAddress3">
                                    <select class="sappcCertModalInput sappcCertModalInput--center sappcCertModalSelect"
                                        id="wdCertBarangay" name="barangay" aria-label="Barangay">
                                        <option value="">Barangay</option>
                                    </select>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertMunicipality" name="municipality" placeholder="Municipality">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertProvince" name="province" value="Antique" placeholder="Province">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="wdCertDateReceived">Date Received</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="wdCertDateReceived" name="date_received">
                                    </div>
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="wdCertPriest">Rev. / Priest</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertPriest" name="priest" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="wdCertSponsors">Sponsors</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="wdCertSponsors" name="sponsors" placeholder="">
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="wdCertPurpose">Purpose</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="wdCertPurpose" name="purpose" value="For all legal purposes">
                            </div>

                            <div class="sappcCertModalTrackingGrid">
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="wdCertBookNo">Book No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertBookNo" name="book_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="wdCertRegisterNo">Register No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertRegisterNo" name="register_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="wdCertPageNo">Page No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="wdCertPageNo" name="page_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel" for="wdCertDateIssued">Date Issued</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="wdCertDateIssued" name="date_issued">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcChristeningAppModalFooter">
                        <button type="button"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave" id="wdCertAddRecordBtn">
                            Add Record
                        </button>
                        <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnCancel"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
