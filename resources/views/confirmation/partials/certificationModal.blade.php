    <div class="sappcConfirmationCertificationModal">
        <div class="modal fade" id="confirmationCertificationModal" tabindex="-1"
            aria-labelledby="confirmationCertificationModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcCertModalDialog">
                <div class="modal-content sappcCertModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 fw-normal visually-hidden"
                            id="confirmationCertificationModalTitle">Confirmation certification form</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcCertModalForm" id="confirmationCertificationForm" action="#" method="post"
                            autocomplete="off" data-default-reference-code="{{ $generatedReferenceCode ?? '' }}">
                            <div class="sappcCertModalMasthead">
                                <div class="sappcCertModalLogoWrap">
                                    <img src="{{ asset('assets/logos/SAPPC.png') }}" width="72" height="72"
                                        alt="Parish of St. Anthony of Padua, Barbaza"
                                        class="sappcCertModalLogoImg">
                                </div>
                                <h3 class="sappcCertModalTitle">Confirmation Certification Form</h3>
                            </div>

                            <div class="sappcCertModalMetaGrid">
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="cnCertRefCode">Reference Code</label>
                                    <input type="text" class="sappcCertModalInput" id="cnCertRefCode"
                                        name="reference_code" value="{{ $generatedReferenceCode ?? '' }}" readonly
                                        tabindex="-1" aria-readonly="true" placeholder="Auto-generated"
                                        title="Auto-generated reference code">
                                    <label class="sappcCertModalLabel" for="cnCertClient">Client</label>
                                    <input type="text" class="sappcCertModalInput" id="cnCertClient" name="client"
                                        value="" readonly>
                                </div>
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="cnCertContact">Contact Number</label>
                                    <input type="text" class="sappcCertModalInput" id="cnCertContact"
                                        name="contact_number" value="" readonly inputmode="tel">
                                    <label class="sappcCertModalLabel" for="cnCertTopAddress">Address</label>
                                    <input type="text" class="sappcCertModalInput" id="cnCertTopAddress" name="top_address"
                                        value="" readonly>
                                </div>
                            </div>

                            <h4 class="sappcCertModalSectionTitle">Confirmation Information</h4>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Complete Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertChildFirst" name="child_first_name" placeholder="First Name"
                                        aria-label="Confirmand first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertChildMiddle" name="child_middle_name" placeholder="Middle Name"
                                        aria-label="Confirmand middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertChildLast" name="child_last_name" placeholder="Last Name"
                                        aria-label="Confirmand last name">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="cnCertBirthday">Birthday</label>
                                    <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertBirthday" name="birthday">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="cnCertBirthplace">Birthplace</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertBirthplace" name="birthplace" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Father's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertFatherFirst" name="father_first_name" placeholder="First Name"
                                        aria-label="Father first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertFatherMiddle" name="father_middle_name" placeholder="Middle Name"
                                        aria-label="Father middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertFatherLast" name="father_last_name" placeholder="Last Name"
                                        aria-label="Father last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Mother's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertMotherFirst" name="mother_first_name" placeholder="First Name"
                                        aria-label="Mother first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertMotherMiddle" name="mother_middle_name" placeholder="Middle Name"
                                        aria-label="Mother middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertMotherLast" name="mother_last_name" placeholder="Last Name"
                                        aria-label="Mother last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Address</span>
                                <div class="sappcCertModalAddress3">
                                    <select class="sappcCertModalInput sappcCertModalInput--center sappcCertModalSelect"
                                        id="cnCertBarangay" name="barangay" aria-label="Barangay">
                                        <option value="">Barangay</option>
                                    </select>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertMunicipality" name="municipality" placeholder="Municipality">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertProvince" name="province" value="Antique" placeholder="Province">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="cnCertDateReceived">Date Received</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="cnCertDateReceived" name="date_received">
                                    </div>
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="cnCertPriest">Rev. / Priest</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertPriest" name="priest" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="cnCertSponsors">Sponsors</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="cnCertSponsors" name="sponsors" placeholder="">
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="cnCertPurpose">Purpose</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="cnCertPurpose" name="purpose" placeholder="">
                            </div>

                            <div class="sappcCertModalTrackingGrid">
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="cnCertBookNo">Book No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertBookNo" name="book_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="cnCertRegisterNo">Register No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertRegisterNo" name="register_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="cnCertPageNo">Page No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="cnCertPageNo" name="page_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel" for="cnCertDateIssued">Date Issued</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="cnCertDateIssued" name="date_issued">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcChristeningAppModalFooter">
                        <button type="submit" form="confirmationCertificationForm"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave" id="cnCertAddRecordBtn">
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
