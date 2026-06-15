<div class="modal fade" id="christeningApplicationFormModal" tabindex="-1"
    aria-labelledby="christeningApplicationFormModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content sappcChristeningAppModal">
            <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden"
                    id="christeningApplicationFormModalTitle">
                    Baptism application</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body pt-0">
                <form class="sappcChristeningAppForm sappcChOfficial" id="christeningApplicationForm"
                    action="{{ route('admin.christening.application-form') }}" method="post" autocomplete="off"
                    data-save-url="{{ route('admin.christening.application-form') }}">
                    <section aria-label="Baptism application page 1">
                            <header class="sappcChOfficialHeader">
                                <div class="sappcChOfficialLogo sappcChOfficialLogoLeft">
                                    <img src="{{ asset('assets/logos/DSA.jpg') }}" width="88" height="88"
                                        alt="Diocese of San Jose de Antique" class="sappcChOfficialLogoImg">
                                </div>
                                <div class="sappcChOfficialMasthead">
                                    <p class="sappcChOfficialMastheadLine sappcChOfficialMastheadLineStrong">The Roman Catholic
                                        Parish of St. Anthony of Padua</p>
                                    <p class="sappcChOfficialMastheadLine">Diocese of San Jose de Antique</p>
                                    <p class="sappcChOfficialMastheadLine">Barbaza, 5706, Antique, Philippines</p>
                                    <p class="sappcChOfficialDocTitle">APLIKASYON SA BUNYAG</p>
                                </div>
                                <div class="sappcChOfficialLogo sappcChOfficialLogoRight sappcChOfficialLogoParishSeal">
                                    <img src="{{ asset('assets/logos/SAPPC.png') }}" width="88" height="88"
                                        alt="Parish of St. Anthony of Padua, Barbaza"
                                        class="sappcChOfficialLogoImg sappcChOfficialLogoImgParishSeal">
                                </div>
                            </header>

                            <div class="sappcChOfficialNameBlock">
                                <div class="sappcChOfficialNameRow">
                                    <div class="sappcChOfficialNameStrip">
                                        <span class="sappcChOfficialNameLabel">First name</span>
                                        <div class="sappcChOfficialCellsWrap sappcChOfficialCellsWrapName"
                                            id="chAppCellsFirst">
                                            <input type="text" class="sappcChOfficialCellInput"
                                                id="chAppFirstName" name="first_name" autocomplete="off"
                                                aria-label="First name">
                                        </div>
                                    </div>
                                </div>
                                <div class="sappcChOfficialNameRow">
                                    <div class="sappcChOfficialNameStrip">
                                        <span class="sappcChOfficialNameLabel">Middle name</span>
                                        <div class="sappcChOfficialCellsWrap sappcChOfficialCellsWrapName"
                                            id="chAppCellsMiddle">
                                            <input type="text" class="sappcChOfficialCellInput"
                                                id="chAppMiddleName" name="middle_name" autocomplete="off"
                                                aria-label="Middle name">
                                        </div>
                                    </div>
                                </div>
                                <div class="sappcChOfficialNameRow">
                                    <div class="sappcChOfficialNameStrip">
                                        <span class="sappcChOfficialNameLabel">Family name</span>
                                        <div class="sappcChOfficialCellsWrap sappcChOfficialCellsWrapName"
                                            id="chAppCellsFamily">
                                            <input type="text" class="sappcChOfficialCellInput"
                                                id="chAppFamilyName" name="family_name" autocomplete="off"
                                                aria-label="Family name">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sappcChOfficialRow sappcChOfficialRowDob">
                                <div class="sappcChOfficialField sappcChOfficialFieldGrow">
                                    <label class="sappcChOfficialLabel" for="chAppDob">Date of birth <span
                                            class="sappcChOfficialLabelSub">(if registered)</span></label>
                                    <input type="date" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                        id="chAppDob" name="date_of_birth"
                                        title="Date of birth (if registered)">
                                </div>
                                <div class="sappcChOfficialField sappcChOfficialFieldNarrow">
                                    <label class="sappcChOfficialLabel" for="chAppRegistryNo">Registry number</label>
                                    <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                        id="chAppRegistryNo" name="registry_number">
                                </div>
                            </div>

                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppPob">Place of birth</label>
                                    <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppPob" name="place_of_birth">
                            </div>
                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppFather">Father&rsquo;s name</label>
                                <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppFather" name="father_name">
                            </div>
                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppMother">Mother&rsquo;s maiden
                                    name</label>
                                <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppMother" name="mother_maiden_name">
                            </div>
                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppParentAddress">Parent&rsquo;s
                                    address</label>
                                <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppParentAddress" name="parent_address">
                            </div>

                            <p class="sappcChOfficialSectionTitle">Parent&rsquo;s status</p>
                            <div class="sappcChOfficialStatusRow" role="group" aria-label="Parent status">
                                <label class="sappcChOfficialCheck"><input type="checkbox" name="parent_status[]"
                                        value="civilly_married"> Civilly married</label>
                                <label class="sappcChOfficialCheck"><input type="checkbox" name="parent_status[]"
                                        value="married_other"> Married in another denomination/sect</label>
                                <label class="sappcChOfficialCheck"><input type="checkbox" name="parent_status[]"
                                        value="church_marriage"> Church marriage</label>
                                <label class="sappcChOfficialCheck"><input type="checkbox" name="parent_status[]"
                                        value="not_married"> not yet Married</label>
                                <label class="sappcChOfficialCheck"><input type="checkbox" name="parent_status[]"
                                        value="single_parent"> Single Parent</label>
                            </div>

                            <div class="sappcChOfficialDottedRow">
                                <span>Date</span>
                                <input type="date" class="sappcChOfficialDottedInput" name="marriage_date"
                                    aria-label="Marriage date">
                                <span>Place</span>
                                <input type="text" class="sappcChOfficialDottedInput" name="marriage_place"
                                    aria-label="Marriage place">
                            </div>

                            <p class="sappcChOfficialNote">* If parent&rsquo;s are married, indicate Marriage Contract
                                no.</p>
                            <input type="text" class="sappcChOfficialDottedInput sappcChOfficialDottedInputFull"
                                name="marriage_contract_no" aria-label="Marriage contract number">

                            <div class="sappcChOfficialNameRow sappcChOfficialNameRowContact">
                                <div class="sappcChOfficialNameStrip">
                                    <span class="sappcChOfficialNameLabel">Parent/guardian&rsquo;s contact no.</span>
                                    <div class="sappcChOfficialCellsWrap sappcChOfficialCellsWrapContact"
                                        id="chAppCellsContact">
                                        <input type="text" class="sappcChOfficialCellInput"
                                            id="chAppGuardianContact" name="guardian_contact" inputmode="numeric"
                                            autocomplete="off" aria-label="Parent or guardian contact number">
                                    </div>
                                </div>
                            </div>

                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppBaptismDate"><span
                                        class="sappcChOfficialHiligaynon">Petsa kang pagbunyag</span> <span
                                        class="sappcChOfficialLabelNote">(Date of Baptism)</span></label>
                                <input type="date" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppBaptismDate" name="baptism_date"
                                    title="Date of baptism">
                            </div>
                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppBaptismPlace"><span
                                        class="sappcChOfficialHiligaynon">Lugar kang pagbunyag</span> <span
                                        class="sappcChOfficialLabelNote">(Place of Baptism)</span></label>
                                <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppBaptismPlace" name="baptism_place"
                                    value="Saint Anthony of Padua Parish Church" readonly tabindex="-1"
                                    title="Place of baptism (parish fixed)">
                            </div>
                            <div class="sappcChOfficialField">
                                <label class="sappcChOfficialLabel" for="chAppMinister"><span
                                        class="sappcChOfficialHiligaynon">Pari nga nagbunyag</span> <span
                                        class="sappcChOfficialLabelNote">(Minister of the Sacrament)</span></label>
                                <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                    id="chAppMinister" name="minister">
                            </div>

                            <p class="sappcChOfficialSectionTitle sappcChOfficialSectionTitlePahanumdom">Pahanumdom:
                            </p>
                            <ol class="sappcChOfficialList">
                                <li>Ipalista ang burunyagan sa opisina kang parokya sangka semana antes ang bunyag.</li>
                                <li>Ipresentar ang xerox copy nga marriage contract kang ginikanan kag xerox copy kang
                                    birth certificate kang burunyagan.</li>
                                <li>Magtambong ang ginikanan kag mga maninoy/maninay sa natalana nga mga pre-Jordan
                                    seminar skedyul kang parokya.</li>
                                <li>Ang regular nga schedule kang bunyag ginahiwat kada una, ikarwa, kag ikatlo nga
                                    Domingo, pagkatapos kang <span class="sappcChOfficialTextRed">SECOND Mass</span>.
                                    Wara ti bunyag nga pagahiwaton sa ikap-at nga Domingo kang bulan bangud dya
                                    gintalana para sa meeting kang Parish Pastoral Council (PPC).</li>
                            </ol>
                    </section>

                    <section aria-label="Baptism application page 2">
                            <div class="sappcChristeningAppFormPage2 sappcChOfficialPage2">
                                <h2 class="sappcChOfficialPage2Title">Arancel kang bunyag</h2>

                                <table class="sappcChOfficialFeeTable">
                                    <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col" class="sappcChOfficialFeeThAmount">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Arancel <em>(For Parents if by Appointment)</em></td>
                                            <td><input class="sappcChOfficialFeeInput" type="text"
                                                    name="fee_arancel" inputmode="decimal"></td>
                                        </tr>
                                        <tr>
                                            <td>Baptismal Symbols <em>(white Garment, Candle, etc)</em></td>
                                            <td><input class="sappcChOfficialFeeInput" type="text"
                                                    name="fee_symbols" inputmode="decimal"></td>
                                        </tr>
                                        <tr>
                                            <td>Godparents</td>
                                            <td><input class="sappcChOfficialFeeInput" type="text"
                                                    name="fee_godparents" inputmode="decimal"></td>
                                        </tr>
                                        <tr>
                                            <td>Parents&rsquo; Seminar <em>(if by Appointment)</em></td>
                                            <td><input class="sappcChOfficialFeeInput" type="text"
                                                    name="fee_seminar" inputmode="decimal"></td>
                                        </tr>
                                        <tr>
                                            <td>Others:</td>
                                            <td><input class="sappcChOfficialFeeInput" type="text"
                                                    name="fee_others" inputmode="decimal"></td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td></td>
                                        </tr>
                                        <tr class="sappcChOfficialFeeTotalRow">
                                            <th scope="row">TOTAL</th>
                                            <td><input class="sappcChOfficialFeeInput" type="text"
                                                    name="fee_total" inputmode="decimal" aria-label="Total fees"
                                                    readonly tabindex="-1" title="Sum of the amounts above (automatic)">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <h2 class="sappcChOfficialGpHeading">Listahan kang mga maninoy kag maninay sa pagbunyag
                                </h2>
                                <div class="sappcChOfficialGpToolbar">
                                    <div class="sappcChOfficialGpColHeads" aria-hidden="true">
                                        <span>Maninoy</span>
                                        <span>Maninay</span>
                                    </div>
                                    <button type="button" class="sappcChOfficialGpAddBtn" id="chAppGpAddBtn">Add</button>
                                </div>
                                <div class="sappcChOfficialGpCols">
                                    <div class="sappcChOfficialGpCol" id="chAppGpColA"></div>
                                    <div class="sappcChOfficialGpCol" id="chAppGpColB"></div>
                                </div>

                                <div class="sappcChOfficialApprovalBox">
                                    <p class="sappcChOfficialApprovalIntro"><em>Approved by:</em></p>
                                    <div class="sappcChOfficialSignatures">
                                        <div class="sappcChOfficialSignBlock">
                                            <input type="text" class="sappcChOfficialSignLine"
                                                name="approval_bpc_chairman" id="chAppApprovalBpcChairman"
                                                autocomplete="off" aria-label="BPC Chairman (name or signature)">
                                            <strong>BPC CHAIRMAN</strong>
                                        </div>
                                        <div class="sappcChOfficialSignBlock">
                                            <input type="text" class="sappcChOfficialSignLine"
                                                name="approval_prejordan_instructor" id="chAppApprovalPrejordan"
                                                autocomplete="off"
                                                aria-label="Pre-Jordan instructor (name or signature)">
                                            <strong>PRE-JORDAN INSTRUCTOR</strong>
                                        </div>
                                        <div class="sappcChOfficialSignBlock">
                                            <input type="text" class="sappcChOfficialSignLine"
                                                name="approval_parish_secretary" id="chAppApprovalSecretary"
                                                autocomplete="off" aria-label="Parish secretary (name or signature)">
                                            <strong>PARISH SECRETARY</strong>
                                        </div>
                                        <div class="sappcChOfficialSignBlock">
                                            <input type="text" class="sappcChOfficialSignLine"
                                                name="approval_parish_priest" id="chAppApprovalPriest"
                                                autocomplete="off" aria-label="Parish priest (name or signature)">
                                            <strong>PARISH PRIEST</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </section>
                </form>
            </div>
            <div class="modal-footer sappcChristeningAppModalFooter">
                <button type="submit" form="christeningApplicationForm"
                    class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                    id="christeningApplicationFormSaveBtn">
                    Save
                </button>
                <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnCancel"
                    data-bs-dismiss="modal">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
