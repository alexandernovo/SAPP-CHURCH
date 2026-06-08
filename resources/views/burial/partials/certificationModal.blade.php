    <div class="sappcBurialCertificationModal">
        <div class="modal fade" id="burialCertificationModal" tabindex="-1"
            aria-labelledby="burialCertificationModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcCertModalDialog">
                <div class="modal-content sappcCertModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 fw-normal visually-hidden"
                            id="burialCertificationModalTitle">Burial certification form</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcCertModalForm" id="burialCertificationForm" action="#" method="post"
                            autocomplete="off">
                            <div class="sappcCertModalMasthead">
                                <div class="sappcCertModalLogoWrap">
                                    <img src="{{ asset('assets/logos/SAPPC.png') }}" width="72" height="72"
                                        alt="Parish of St. Anthony of Padua, Barbaza"
                                        class="sappcCertModalLogoImg">
                                </div>
                                <h3 class="sappcCertModalTitle">Burial Certification Form</h3>
                            </div>

                            <div class="sappcCertModalMetaGrid">
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="brCertRefCode">Reference Code</label>
                                    <input type="text" class="sappcCertModalInput" id="brCertRefCode"
                                        name="reference_code" value="" readonly
                                        title="Populated from selected record">
                                    <label class="sappcCertModalLabel" for="brCertClient">Client</label>
                                    <input type="text" class="sappcCertModalInput" id="brCertClient" name="client"
                                        value="" readonly>
                                </div>
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="brCertContact">Contact Number</label>
                                    <input type="text" class="sappcCertModalInput" id="brCertContact"
                                        name="contact_number" value="" readonly inputmode="tel">
                                    <label class="sappcCertModalLabel" for="brCertTopAddress">Address</label>
                                    <input type="text" class="sappcCertModalInput" id="brCertTopAddress" name="top_address"
                                        value="" readonly>
                                </div>
                            </div>

                            <h4 class="sappcCertModalSectionTitle">Burial Information</h4>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Complete Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertChildFirst" name="child_first_name" placeholder="Juan"
                                        aria-label="First name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertChildMiddle" name="child_middle_name" placeholder="D."
                                        aria-label="Middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertChildLast" name="child_last_name" placeholder="Cruz"
                                        aria-label="Last name">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="brCertBirthday">Birthday</label>
                                    <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertBirthday" name="birthday">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="brCertBirthplace">Birthplace</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertBirthplace" name="birthplace" placeholder="Barbaza, Antique">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Father's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertFatherFirst" name="father_first_name" placeholder="Juan"
                                        aria-label="Father first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertFatherMiddle" name="father_middle_name" placeholder="D."
                                        aria-label="Father middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertFatherLast" name="father_last_name" placeholder="Cruz"
                                        aria-label="Father last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Mother's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertMotherFirst" name="mother_first_name" placeholder="First Name"
                                        aria-label="Mother first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertMotherMiddle" name="mother_middle_name" placeholder="Middle Name"
                                        aria-label="Mother middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertMotherLast" name="mother_last_name" placeholder="Last Name"
                                        aria-label="Mother last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Address</span>
                                <div class="sappcCertModalAddress3">
                                    <select class="sappcCertModalInput sappcCertModalInput--center sappcCertModalSelect"
                                        id="brCertBarangay" name="barangay" aria-label="Barangay">
                                        <option value="">Barangay</option>
                                    </select>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertMunicipality" name="municipality" placeholder="Municipality">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertProvince" name="province" value="Antique" placeholder="Province">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="brCertDateReceived">Date Received</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="brCertDateReceived" name="date_received">
                                    </div>
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="brCertPriest">Rev. / Priest</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertPriest" name="priest" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="brCertSponsors">Sponsors</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="brCertSponsors" name="sponsors" placeholder="">
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="brCertPurpose">Purpose</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="brCertPurpose" name="purpose" placeholder="">
                            </div>

                            <div class="sappcCertModalTrackingGrid">
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="brCertBookNo">Book No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertBookNo" name="book_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="brCertRegisterNo">Register No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertRegisterNo" name="register_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="brCertPageNo">Page No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="brCertPageNo" name="page_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel" for="brCertDateIssued">Date Issued</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="brCertDateIssued" name="date_issued">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcChristeningAppModalFooter">
                        <button type="button"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave" id="brCertAddRecordBtn">
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
