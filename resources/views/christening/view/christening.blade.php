@extends('layouts.adminDashboard')

@section('title', 'Christening — ' . config('app.name', 'SAPP Church'))
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
    <h1 class="sappc-page-title">
        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
        CHRISTENING
    </h1>
    <p class="sappc-page-breadcrumb mb-0">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
        <span>Christening</span>
    </p>

    <div class="sappc-registry-toolbar" role="toolbar" aria-label="Christening record actions">
        <span class="sappc-registry-toolbar_record">RECORD</span>
        <div class="sappc-registry-toolbar_actions">
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--reload"
                id="christeningReloadBtn" title="Reload" aria-label="Reload table">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reload
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                id="christeningScheduleRequestBtn"
                data-schedule-save-url="{{ route('admin.christening.schedule-request') }}" title="Schedule request"
                aria-label="Open schedule request" aria-expanded="false" aria-controls="christeningScheduleRequestModal"
                data-bs-toggle="modal" data-bs-target="#christeningScheduleRequestModal">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Schedule Request
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="christeningCertificationBtn" title="Baptism certification" aria-label="Open baptism certification form"
                aria-expanded="false" aria-controls="christeningCertificationModal">
                <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                Certification
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="christeningPaymentFeeBtn" title="Payment fee" aria-label="Open payment fee" aria-expanded="false"
                aria-controls="christeningPaymentFeeModal">
                <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                Payment Fee
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="christeningApplicationFormBtn" aria-expanded="false" aria-controls="christeningApplicationFormModal">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                Application Form
            </button>
        </div>
    </div>

    <div class="sappcPaymentFeeModal">
        <div class="modal fade" id="christeningPaymentFeeModal" tabindex="-1"
            aria-labelledby="christeningPaymentFeeModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcPaymentFeeModalDialog">
                <div class="modal-content sappcPaymentFeeModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden"
                            id="christeningPaymentFeeModalTitle">Payment fee record</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcPaymentFeeModalForm" id="christeningPaymentFeeForm" action="#"
                            method="post" autocomplete="off"
                            data-save-url="{{ route('admin.christening.payment-save') }}">
                            <div class="sappcChOfficial sappcPaymentFeeModalOfficial">
                                <header class="sappcChOfficialHeader">
                                    <div class="sappcChOfficialLogo sappcChOfficialLogoLeft">
                                        <img src="{{ asset('assets/logos/DSA.jpg') }}" width="72" height="72"
                                            alt="Diocese of San Jose de Antique" class="sappcChOfficialLogoImg">
                                    </div>
                                    <div class="sappcChOfficialMasthead">
                                        <p class="sappcChOfficialMastheadLine sappcChOfficialMastheadLineStrong">
                                            The Roman Catholic Parish of St. Anthony of Padua</p>
                                        <p class="sappcChOfficialMastheadLine">Diocese of San Jose de Antique</p>
                                        <p class="sappcChOfficialMastheadLine">Barbaza, 5706, Antique, Philippines</p>
                                    </div>
                                    <div class="sappcChOfficialLogo sappcChOfficialLogoRight sappcChOfficialLogoParishSeal">
                                        <img src="{{ asset('assets/logos/SAPPC.png') }}" width="72" height="72"
                                            alt="Parish of St. Anthony of Padua, Barbaza"
                                            class="sappcChOfficialLogoImg sappcChOfficialLogoImgParishSeal">
                                    </div>
                                </header>

                                <div class="sappcPaymentFeeModalFields">
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="chPaymentRefCode">Reference
                                            Code</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="chPaymentRefCode"
                                            name="reference_code" value="" readonly
                                            title="System-generated; use when creating a new record">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="chPaymentClient">Client</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="chPaymentClient"
                                            name="client" value="">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="chPaymentContact">Contact
                                            Number</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="chPaymentContact"
                                            name="contact_number" value="" inputmode="tel">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="chPaymentAddress">Address</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="chPaymentAddress"
                                            name="address" value="">
                                    </div>
                                </div>

                                <h3 class="sappcPaymentFeeModalFeeHeading">Arancel kang bunyag</h3>

                                <div class="table-responsive sappcPaymentFeeModalTableWrap">
                                    <table class="table table-bordered mb-0 sappcPaymentFeeModalTable">
                                        <thead>
                                            <tr>
                                                <th scope="col"
                                                    class="sappcPaymentFeeModalTh sappcPaymentFeeModalThNo">No.</th>
                                                <th scope="col" class="sappcPaymentFeeModalTh">Item</th>
                                                <th scope="col" class="sappcPaymentFeeModalTh">Status Fee</th>
                                                <th scope="col" class="sappcPaymentFeeModalTh">Date of Paid</th>
                                                <th scope="col"
                                                    class="sappcPaymentFeeModalTh sappcPaymentFeeModalThAction text-center">
                                                    Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="christeningPaymentFeeItemsBody">
                                            <tr class="sappcPaymentFeeModalRow" data-fee-row>
                                                <td class="sappcPaymentFeeModalCellNo">1</td>
                                                <td>
                                                    <input type="text" class="sappcPaymentFeeModalItemInput"
                                                        name="fee_items[]" value="" aria-label="Fee item 1">
                                                </td>
                                                <td>
                                                    <span
                                                        class="sappcPaymentFeeModalStatus sappcPaymentFeeModalStatusUnpaid">Unpaid</span>
                                                </td>
                                                <td>
                                                    <span class="sappcPaymentFeeModalDatePaid" data-date-paid="">&#8212;</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="sappcPaymentFeeModalActions">
                                                        <button type="button"
                                                            class="sappcPaymentFeeModalToggleUnpaid">Paid</button>
                                                        <button type="button" class="sappcPaymentFeeModalBtnRemove"
                                                            aria-label="Remove row">
                                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="sappcPaymentFeeModalBelowTable">
                                    <button type="button" class="sappcPaymentFeeModalBtnAddItem"
                                        id="christeningPaymentFeeAddItemBtn">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        Add item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcPaymentFeeModalFooter sappcChristeningAppModalFooter">
                        <button type="submit" form="christeningPaymentFeeForm"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                            id="christeningPaymentFeeSaveBtn">
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
    </div>

    <div class="modal fade" id="christeningScheduleRequestModal" tabindex="-1"
        aria-labelledby="christeningScheduleRequestModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content sappcScheduleRequestModal">
                <div class="modal-body">
                    <button type="button" class="btn-close sappcScheduleRequestModalClose" data-bs-dismiss="modal"
                        aria-label="Close"></button>

                    <div class="sappcScheduleRequestLayout">
                        <section class="sappcScheduleCalendarCard" aria-label="Calendar">
                            <div class="sappcScheduleCalendarHead sappcScheduleCalendarHead--interactive">
                                <button type="button" class="sappcScheduleCalNav" id="chCalPrev"
                                    aria-label="Previous month">‹</button>
                                <span class="sappcScheduleCalendarMonthNo" id="chCalMonthNum">3</span>
                                <select class="sappcScheduleCalendarMonth" id="chCalMonth" aria-label="Month"></select>
                                <select class="sappcScheduleCalendarYear" id="chCalYear" aria-label="Year"></select>
                                <button type="button" class="sappcScheduleCalNav" id="chCalNext"
                                    aria-label="Next month">›</button>
                            </div>

                            <div class="sappcScheduleCalendarGrid" role="grid" aria-label="Select baptism date"
                                id="chCalGrid">
                                <div class="sappcScheduleCalendarWeekday sunday">SUN</div>
                                <div class="sappcScheduleCalendarWeekday">MON</div>
                                <div class="sappcScheduleCalendarWeekday">TUE</div>
                                <div class="sappcScheduleCalendarWeekday">WED</div>
                                <div class="sappcScheduleCalendarWeekday">THU</div>
                                <div class="sappcScheduleCalendarWeekday">FRI</div>
                                <div class="sappcScheduleCalendarWeekday saturday">SAT</div>
                                <div id="chCalDayCells"></div>
                            </div>
                        </section>

                        <section class="sappcScheduleFormCard">
                            <header class="sappcScheduleFormHeader">
                                <img src="{{ asset('assets/logos/SAPPC.png') }}" alt="Parish logo"
                                    class="sappcScheduleFormLogo">
                                <h2 id="christeningScheduleRequestModalTitle">Baptism Schedule Request Form</h2>
                            </header>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <form id="christeningScheduleRequestForm" method="post" autocomplete="off"
                                data-schedule-save-url="{{ route('admin.christening.schedule-request') }}"
                                data-schedule-reserved-url="{{ route('admin.christening.schedule-reserved-dates') }}"
                                data-default-reference-code="{{ $generatedReferenceCode ?? '' }}">
                                @csrf
                                <div class="sappcScheduleFormField">
                                    <input type="hidden" name="christening_id" id="chScheduleChristeningId" value="">
                                    <label for="chScheduleRefCode">Reference Code:</label>
                                    <input type="text" name="reference_code" id="chScheduleRefCode"
                                        value="{{ $generatedReferenceCode ?? '' }}"
                                        placeholder="System default; edit or pick a table row"
                                        title="Generated on page load. Click a row to use that record’s code.">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="chScheduleContact">Contact Number:</label>
                                    <input type="text" name="contact_number" id="chScheduleContact" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="chScheduleClient">Client:</label>
                                    <input type="text" name="client" id="chScheduleClient" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="chScheduleAddress">Address:</label>
                                    <input type="text" name="address" id="chScheduleAddress" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="chScheduleSex">Sex:</label>
                                    <select name="sex" id="chScheduleSex" class="form-select">
                                        <option value="">Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="chScheduleDate">Date:</label>
                                    <input type="date" name="schedule_date" id="chScheduleDate" required
                                        class="sappcScheduleNativeInput">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="chScheduleTime24">Time:</label>
                                    <input type="time" name="schedule_time" id="chScheduleTime24" required
                                        step="60" class="sappcScheduleNativeInput">
                                </div>
                            </form>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <div class="sappcScheduleFormActions">
                                <button type="button" class="sappcScheduleActionBtn is-cancel"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="christeningScheduleRequestForm"
                                    class="sappcScheduleActionBtn is-reserve">Reserved Schedule</button>
                                <button type="button" class="sappcScheduleActionBtn is-calendar">View Calendar</button>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

                        <div class="sappcChOfficialColumns">
                            <div class="sappcChOfficialColumn sappcChOfficialColumnLeft">
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
                                        <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                            id="chAppDob" name="date_of_birth" placeholder="">
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
                                    <input type="text" class="sappcChOfficialDottedInput" name="marriage_date_1"
                                        aria-label="Marriage date (first)">
                                    <span>Date</span>
                                    <input type="text" class="sappcChOfficialDottedInput" name="marriage_date_2"
                                        aria-label="Marriage date (second)">
                                    <span>Date</span>
                                    <input type="text" class="sappcChOfficialDottedInput" name="marriage_date_3"
                                        aria-label="Marriage date (third)">
                                </div>
                                <div class="sappcChOfficialDottedRow">
                                    <span>Place</span>
                                    <input type="text" class="sappcChOfficialDottedInput" name="marriage_place_1"
                                        aria-label="Marriage place (first)">
                                    <span>Place</span>
                                    <input type="text" class="sappcChOfficialDottedInput" name="marriage_place_2"
                                        aria-label="Marriage place (second)">
                                    <span>Place</span>
                                    <input type="text" class="sappcChOfficialDottedInput" name="marriage_place_3"
                                        aria-label="Marriage place (third)">
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
                                    <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                        id="chAppBaptismDate" name="baptism_date">
                                </div>
                                <div class="sappcChOfficialField">
                                    <label class="sappcChOfficialLabel" for="chAppBaptismPlace"><span
                                            class="sappcChOfficialHiligaynon">Lugar kang pagbunyag</span> <span
                                            class="sappcChOfficialLabelNote">(Place of Baptism)</span></label>
                                    <input type="text" class="sappcChOfficialInput sappcChOfficialInputRounded"
                                        id="chAppBaptismPlace" name="baptism_place">
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
                            </div>

                            <div class="sappcChOfficialColumn sappcChOfficialColumnRight">
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
                                                        name="fee_total" inputmode="decimal" aria-label="Total fees">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h2 class="sappcChOfficialGpHeading">Listahan kang mga maninoy kag maninay sa pagbunyag
                                    </h2>
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
                            </div>
                        </div>
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

    <div class="sappcChristeningCertificationModal">
        <div class="modal fade" id="christeningCertificationModal" tabindex="-1"
            aria-labelledby="christeningCertificationModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcCertModalDialog">
                <div class="modal-content sappcCertModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 fw-normal visually-hidden"
                            id="christeningCertificationModalTitle">Baptism certification form</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcCertModalForm" id="christeningCertificationForm" action="#" method="post"
                            autocomplete="off"
                            data-save-url="{{ route('admin.christening.certification-form') }}">
                            <div class="sappcCertModalMasthead">
                                <div class="sappcCertModalLogoWrap">
                                    <img src="{{ asset('assets/logos/SAPPC.png') }}" width="72" height="72"
                                        alt="Parish of St. Anthony of Padua, Barbaza"
                                        class="sappcCertModalLogoImg">
                                </div>
                                <h3 class="sappcCertModalTitle">Baptism Certification Form</h3>
                            </div>

                            <div class="sappcCertModalMetaGrid">
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="chCertRefCode">Reference Code</label>
                                    <input type="text" class="sappcCertModalInput" id="chCertRefCode"
                                        name="reference_code" value="" readonly
                                        title="Populated from selected record">
                                    <label class="sappcCertModalLabel" for="chCertClient">Client</label>
                                    <input type="text" class="sappcCertModalInput" id="chCertClient" name="client"
                                        value="" readonly>
                                </div>
                                <div class="sappcCertModalMetaRow">
                                    <label class="sappcCertModalLabel" for="chCertContact">Contact Number</label>
                                    <input type="text" class="sappcCertModalInput" id="chCertContact"
                                        name="contact_number" value="" readonly inputmode="tel">
                                    <label class="sappcCertModalLabel" for="chCertTopAddress">Address</label>
                                    <input type="text" class="sappcCertModalInput" id="chCertTopAddress" name="top_address"
                                        value="" readonly>
                                </div>
                            </div>

                            <h4 class="sappcCertModalSectionTitle">Baptism Information</h4>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Complete Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertChildFirst" name="child_first_name" placeholder="First Name"
                                        aria-label="Child first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertChildMiddle" name="child_middle_name" placeholder="Middle Name"
                                        aria-label="Child middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertChildLast" name="child_last_name" placeholder="Last Name"
                                        aria-label="Child last name">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="chCertBirthday">Birthday</label>
                                    <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertBirthday" name="birthday">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="chCertBirthplace">Birthplace</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertBirthplace" name="birthplace" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Father's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertFatherFirst" name="father_first_name" placeholder="First Name"
                                        aria-label="Father first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertFatherMiddle" name="father_middle_name" placeholder="Middle Name"
                                        aria-label="Father middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertFatherLast" name="father_last_name" placeholder="Last Name"
                                        aria-label="Father last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Mother's Name</span>
                                <div class="sappcCertModalTriple">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertMotherFirst" name="mother_first_name" placeholder="First Name"
                                        aria-label="Mother first name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertMotherMiddle" name="mother_middle_name" placeholder="Middle Name"
                                        aria-label="Mother middle name">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertMotherLast" name="mother_last_name" placeholder="Last Name"
                                        aria-label="Mother last name">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--triple">
                                <span class="sappcCertModalLabel sappcCertModalLabel--block">Address</span>
                                <div class="sappcCertModalAddress3">
                                    <select class="sappcCertModalInput sappcCertModalInput--center sappcCertModalSelect"
                                        id="chCertBarangay" name="barangay" aria-label="Barangay">
                                        <option value="">Barangay</option>
                                    </select>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertMunicipality" name="municipality" placeholder="Municipality">
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertProvince" name="province" value="Antique" placeholder="Province">
                                </div>
                            </div>

                            <div class="sappcCertModalRow2">
                                <div class="sappcCertModalField sappcCertModalField--stack sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="chCertDateReceived">Date Received</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="chCertDateReceived" name="date_received">
                                        <i class="fa-regular fa-calendar sappcCertModalDateIcon" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--stack">
                                    <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                        for="chCertPriest">Rev. / Priest</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertPriest" name="priest" placeholder="">
                                </div>
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="chCertSponsors">Sponsors</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="chCertSponsors" name="sponsors" placeholder="">
                            </div>

                            <div class="sappcCertModalField sappcCertModalField--stack">
                                <label class="sappcCertModalLabel sappcCertModalLabel--block"
                                    for="chCertPurpose">Purpose</label>
                                <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                    id="chCertPurpose" name="purpose" placeholder="">
                            </div>

                            <div class="sappcCertModalTrackingGrid">
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="chCertBookNo">Book No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertBookNo" name="book_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="chCertRegisterNo">Register No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertRegisterNo" name="register_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline">
                                    <label class="sappcCertModalLabel" for="chCertPageNo">Page No.</label>
                                    <input type="text" class="sappcCertModalInput sappcCertModalInput--center"
                                        id="chCertPageNo" name="page_no">
                                </div>
                                <div class="sappcCertModalField sappcCertModalField--inline sappcCertModalField--dateIcon">
                                    <label class="sappcCertModalLabel" for="chCertDateIssued">Date Issued</label>
                                    <div class="sappcCertModalDateWrap">
                                        <input type="date" class="sappcCertModalInput sappcCertModalInput--center"
                                            id="chCertDateIssued" name="date_issued">
                                        <i class="fa-regular fa-calendar sappcCertModalDateIcon" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcChristeningAppModalFooter">
                        <button type="submit" form="christeningCertificationForm"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave" id="chCertAddRecordBtn">
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

    <section class="sappc-table-panel" id="christeningRecordsPanel"
        data-records-url="{{ route('admin.dashboard.records') }}" data-registry-type="christening"
        data-application-details-url="{{ route('admin.christening.application-details') }}"
        data-payment-details-url="{{ route('admin.christening.payment-details') }}"
        data-payment-save-url="{{ route('admin.christening.payment-save') }}"
        data-certification-save-url="{{ route('admin.christening.certification-form') }}"
        data-certification-details-url="{{ route('admin.christening.certification-details') }}"
        data-christening-delete-url="{{ route('admin.christening.record-delete') }}"
        data-per-page-options="{{ json_encode($perPageOptions) }}" aria-label="Christening records">
        <div class="sappc-table-toolbar">
            <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
                <div class="sappc-table-toolbar_entries">
                    <label class="visually-hidden" for="christeningEntries">Entries per page</label>
                    <select id="christeningEntries" class="form-select form-select-sm sappc-table-toolbar_select"
                        aria-label="Entries per page">
                        @foreach ($perPageOptions as $n)
                            <option value="{{ $n }}" @selected($records->perPage() === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                    <span class="sappc-toolbar-date-strip_label">From:</span>
                    <input type="date" id="christeningDateFrom" class="sappc-toolbar-date-strip_input"
                        name="date_from" value="{{ request('date_from') }}" aria-label="From date">
                    <span class="sappc-toolbar-date-strip_label">To:</span>
                    <input type="date" id="christeningDateTo" class="sappc-toolbar-date-strip_input" name="date_to"
                        value="{{ request('date_to') }}" aria-label="To date">
                    <button type="button" class="sappc-toolbar-date-strip_btn">Filter</button>
                </div>
                <div class="sappc-table-toolbar_letters" role="group"
                    aria-label="Filter by first letter of client last name">
                    <span class="visually-hidden">Filter by first letter of last name A through Z; scroll horizontally to
                        see all letters.</span>
                    <div class="sappc-letter-filter_letters">
                        @foreach ($letterOptions as $letter)
                            <button type="button"
                                class="sappc-letter-filter_btn {{ request('letter') === $letter ? 'is-active' : '' }}"
                                data-letter="{{ $letter }}">{{ $letter }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="sappc-table-toolbar_search" role="search">
                    <label class="sappc-table-toolbar_search-heading" for="christeningSearch">Search:</label>
                    <div class="sappc-table-toolbar_search-wrap">
                        <input type="search" id="christeningSearch"
                            class="form-control form-control-sm sappc-table-toolbar_search-input"
                            value="{{ request('search') }}" placeholder="" autocomplete="off"
                            aria-label="Search christening records" aria-controls="christeningTableBody">
                        <i class="fa-solid fa-magnifying-glass sappc-table-toolbar_search-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive sappc-table-panel_scroll">
            <table class="table table-bordered mb-0 sappc-data-table">
                <thead>
                    <tr>
                        <th scope="col">NO.</th>
                        <th scope="col">REFERENCE CODE</th>
                        <th scope="col">CLIENT</th>
                        <th scope="col">ADDRESS</th>
                        <th scope="col">SEX</th>
                        <th scope="col">CONTACT NUMBER</th>
                        <th scope="col">PAYMENT STATUS</th>
                        <th scope="col">DATE CREATED</th>
                        <th scope="col" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody id="christeningTableBody" aria-live="polite" aria-relevant="additions text"></tbody>
            </table>
        </div>

        <div class="sappc-table-footer">
            <p class="sappc-table-footer_info mb-0" id="christeningTableFooterInfo"></p>
            <nav class="sappc-pagination" id="christeningPagination" aria-label="Table pagination"></nav>
        </div>
    </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('christening.js.christeningScript', ['initialTablePayload' => $initialTablePayload])
@endpush
