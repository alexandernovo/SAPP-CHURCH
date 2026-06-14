@extends('layouts.adminDashboard')

@section('title', 'Confirmation — ' . config('app.name', 'SAPP Church'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/christening/applicationOfChristening.css') }}">
    <link rel="stylesheet" href="{{ asset('css/confirmation/confirmationKompirmaModals.css') }}">
@endpush

@section('content')
    <div class="sappc-registry-page">
    <input type="hidden" id="cnSelectedConfirmationId" value="">

    <h1 class="sappc-page-title">
        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
        CONFIRMATION
    </h1>
    <p class="sappc-page-breadcrumb mb-0">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="sappc-page-breadcrumb_sep" aria-hidden="true">|</span>
        <span>Confirmation</span>
    </p>

    <div class="sappc-registry-toolbar" role="toolbar" aria-label="Confirmation record actions">
        <span class="sappc-registry-toolbar_record">RECORD</span>
        <div class="sappc-registry-toolbar_actions">
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--reload" id="confirmationReloadBtn" title="Reload" aria-label="Reload table">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reload
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--cta"
                id="confirmationScheduleRequestBtn"
                data-schedule-save-url="{{ route('admin.confirmation.schedule-request') }}" title="Schedule request"
                aria-label="Open schedule request" aria-expanded="false" aria-controls="confirmationScheduleRequestModal"
                data-bs-toggle="modal" data-bs-target="#confirmationScheduleRequestModal">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Schedule Request
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="confirmationPaymentFeeBtn" title="Payment fee" aria-label="Open payment fee" aria-expanded="false"
                aria-controls="confirmationPaymentFeeModal">
                <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                Payment Fee
            </button>
            <button type="button" class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--outline"
                id="confirmationApplicationFormBtn" title="Aplikasyon sa Kompirma"
                aria-label="Open confirmation application" aria-expanded="false"
                aria-controls="confirmationApplicationModal"
                data-confirmation-application-details-url="{{ route('admin.confirmation.application-details') }}"
                data-confirmation-application-save-url="{{ route('admin.confirmation.application-save') }}"
                data-confirmation-arancel-details-url="{{ route('admin.confirmation.arancel-details') }}"
                data-confirmation-arancel-save-url="{{ route('admin.confirmation.arancel-save') }}">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                Application Form
            </button>
        </div>
    </div>

    <div class="sappcPaymentFeeModal">
        <div class="modal fade" id="confirmationPaymentFeeModal" tabindex="-1"
            aria-labelledby="confirmationPaymentFeeModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered sappcPaymentFeeModalDialog">
                <div class="modal-content sappcPaymentFeeModalSurface">
                    <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                        <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden"
                            id="confirmationPaymentFeeModalTitle">Payment fee record</h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body pt-0">
                        <form class="sappcPaymentFeeModalForm" id="confirmationPaymentFeeForm" action="#"
                            method="post" autocomplete="off"
                            data-save-url="{{ route('admin.confirmation.payment-save') }}">
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
                                        <label class="sappcPaymentFeeModalLabel" for="cnPaymentRefCode">Reference
                                            Code</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="cnPaymentRefCode"
                                            name="reference_code" value="" readonly
                                            title="System-generated; use when creating a new record">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="cnPaymentClient">Client</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="cnPaymentClient"
                                            name="client" value="">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="cnPaymentContact">Contact
                                            Number</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="cnPaymentContact"
                                            name="contact_number" value="" inputmode="tel">
                                    </div>
                                    <div class="sappcPaymentFeeModalField">
                                        <label class="sappcPaymentFeeModalLabel" for="cnPaymentAddress">Address</label>
                                        <input type="text" class="sappcPaymentFeeModalInput" id="cnPaymentAddress"
                                            name="address" value="">
                                    </div>
                                </div>

                                <h3 class="sappcPaymentFeeModalFeeHeading">Arancel kang kumpirma</h3>

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
                                        <tbody id="confirmationPaymentFeeItemsBody">
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
                                        id="confirmationPaymentFeeAddItemBtn">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        Add item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer sappcPaymentFeeModalFooter sappcChristeningAppModalFooter">
                        <button type="submit" form="confirmationPaymentFeeForm"
                            class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                            id="confirmationPaymentFeeSaveBtn">
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
    <div class="modal fade" id="confirmationApplicationModal" tabindex="-1" aria-labelledby="confirmationApplicationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered sappcCnAppModal sappcCnKompirmaDialog">
            <div class="modal-content sappcChristeningAppModal">
                <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0">
                    <h2 class="modal-title h6 mb-0 visually-hidden" id="confirmationApplicationModalTitle">Aplikasyon sa Kompirma</h2>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0 sappcCnKompirmaModalBody">
                    <form class="sappcCnApp" id="confirmationApplicationForm" method="post" action="#" autocomplete="off">
                        @csrf
                        <input type="hidden" name="confirmation_id" id="cnApplicationConfirmationId" value="">
    
                        <div class="sappcCnAppHeader">
                            <div class="sappcCnAppLogo">
                                <img src="{{ asset('assets/logos/DSA.jpg') }}" width="80" height="80" alt="">
                            </div>
                            <div class="sappcCnAppMasthead">
                                <p>The Roman Catholic Parish of St. Anthony of Padua</p>
                                <p>Diocese of San Jose de Antique</p>
                                <p>Barbaza, 5706, Antique, Philippines</p>
                            </div>
                            <div class="sappcCnAppLogo">
                                <img src="{{ asset('assets/logos/SAPPC.png') }}" width="80" height="80" alt="" class="sappcCnAppLogoParish">
                            </div>
                        </div>
    
                        <h1 class="sappcCnAppTitle">APLIKASYON SA KOMPIRMA</h1>
    
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppFirstName">First name</label>
                            <input type="text" class="sappcCnAppIn" name="first_name" id="cnAppFirstName" autocomplete="given-name">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppMiddleName">Middle name</label>
                            <input type="text" class="sappcCnAppIn" name="middle_name" id="cnAppMiddleName" autocomplete="additional-name">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppFamilyName">Family name</label>
                            <input type="text" class="sappcCnAppIn" name="family_name" id="cnAppFamilyName" autocomplete="family-name">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppDob">Date of birth</label>
                            <input type="date" class="sappcCnAppIn sappcCnAppIn--dob" name="date_of_birth" id="cnAppDob">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppPob">Place of birth</label>
                            <input type="text" class="sappcCnAppIn" name="place_of_birth" id="cnAppPob">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppFather">Father's name</label>
                            <input type="text" class="sappcCnAppIn" name="father_name" id="cnAppFather" autocomplete="off">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppMother">Mother's maiden name</label>
                            <input type="text" class="sappcCnAppIn" name="mother_maiden" id="cnAppMother" autocomplete="off">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppAddress">Address</label>
                            <input type="text" class="sappcCnAppIn" name="address" id="cnAppAddress" autocomplete="street-address">
                        </div>
    
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppBapDate">Petsa kang pagbunyag <span class="sappcCnAppHint">(Date of Baptism)</span></label>
                            <input type="date" class="sappcCnAppIn" name="baptism_date" id="cnAppBapDate"
                                title="If a christening record matches this client (same first and last name) and has a reserved schedule, this date is filled from that schedule.">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppBapPlace">Lugar kang pagbunyag <span class="sappcCnAppHint">(Place of Baptism)</span></label>
                            <input type="text" class="sappcCnAppIn" name="baptism_place" id="cnAppBapPlace">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppMinisterBap">Pari nga nagbunyag <span class="sappcCnAppHint">(Minister of the Sacraments)</span></label>
                            <input type="text" class="sappcCnAppIn" name="minister_baptism" id="cnAppMinisterBap">
                        </div>
    
                        <div class="sappcCnRegRow">
                            <div class="sappcCnRegItem">
                                <label for="cnAppBookNo">Book no.</label>
                                <input type="text" class="sappcCnAppIn sappcCnRegIn" name="book_no" id="cnAppBookNo" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="sappcCnRegItem">
                                <label for="cnAppPageNo">Page no.</label>
                                <input type="text" class="sappcCnAppIn sappcCnRegIn" name="page_no" id="cnAppPageNo" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="sappcCnRegItem">
                                <label for="cnAppRegistryNo">Registry no.</label>
                                <input type="text" class="sappcCnAppIn sappcCnRegIn" name="registry_no" id="cnAppRegistryNo" inputmode="numeric" autocomplete="off">
                            </div>
                        </div>
    
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppConfDate">Petsa kang pagkompirma <span class="sappcCnAppHint">(Date of Confirmation)</span></label>
                            <input type="date" class="sappcCnAppIn" name="confirmation_date" id="cnAppConfDate">
                        </div>
                        <div class="sappcCnAppRow">
                            <label class="sappcCnAppLabel" for="cnAppConfMinister">Ministro nga nagkompirma</label>
                            <input type="text" class="sappcCnAppIn" name="confirmation_minister" id="cnAppConfMinister">
                        </div>
    
                        <div class="sappcCnGpBlock">
                            <p class="sappcCnGpHead">NGALAN KANG MANINOY KAG MANINAY SA PAGKOMPIRMA <span class="sappcCnAppHint">(Name of Godparents)</span></p>
                            <div class="sappcCnGpGrid">
                                <input type="text" class="sappcCnAppIn" name="godparent_1" id="cnAppGp1" aria-label="Godparent 1" placeholder=" ">
                                <input type="text" class="sappcCnAppIn" name="godparent_2" id="cnAppGp2" aria-label="Godparent 2" placeholder=" ">
                                <input type="text" class="sappcCnAppIn" name="godparent_3" id="cnAppGp3" aria-label="Godparent 3" placeholder=" ">
                                <input type="text" class="sappcCnAppIn" name="godparent_4" id="cnAppGp4" aria-label="Godparent 4" placeholder=" ">
                            </div>
                        </div>
    
                        <div class="sappcCnPah" role="region" aria-label="Reminders">
                            <h3 class="sappcCnPahTitle">Pahanumdom:</h3>
                            <ul>
                                <li>Palihog ilakip ang xerox copy kang Baptismal Certificate</li>
                                <li>Magtambong sa natalana nga seminar schedule kang parokya</li>
                            </ul>
                        </div>
    
                        <div class="sappcCnArEmbed" id="cnArancelSection" aria-label="Arancel kang Kompirma">
                            <h2 class="sappcCnArTitle sappcCnArTitle--embed">ARANCEL KANG KOMPIRMA</h2>
    
                            <div class="table-responsive">
                                <table class="sappcCnArTable">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="50%"> </th>
                                            <th scope="col" class="sappcCnArNum">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Arancel (By Appointment)</td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="amt_arancel" id="cnArAm1" inputmode="decimal" placeholder="0.00" aria-label="Arancel by appointment amount">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Candle</td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="amt_candle" id="cnArAm2" inputmode="decimal" placeholder="0.00" aria-label="Candle amount">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Maninoy kag Maninay</td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="amt_godparents" id="cnArAm3" inputmode="decimal" placeholder="0.00" aria-label="Godparents amount">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><input type="text" class="sappcCnArOtherLine" name="other_label_1" id="cnArOther1" placeholder=" " aria-label="Other fee label line 1"></td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="amt_other_1" id="cnArOa1" inputmode="decimal" placeholder="0.00" aria-label="Other amount 1">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><input type="text" class="sappcCnArOtherLine" name="other_label_2" id="cnArOther2" placeholder=" " aria-label="Other fee label line 2"></td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="amt_other_2" id="cnArOa2" inputmode="decimal" placeholder="0.00" aria-label="Other amount 2">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><input type="text" class="sappcCnArOtherLine" name="other_label_3" id="cnArOther3" placeholder=" " aria-label="Other fee label line 3"></td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="amt_other_3" id="cnArOa3" inputmode="decimal" placeholder="0.00" aria-label="Other amount 3">
                                            </td>
                                        </tr>
                                        <tr class="sappcCnArTotal">
                                            <td>TOTAL PAYMENT:</td>
                                            <td class="sappcCnArNum">
                                                <input type="text" class="sappcCnArAmt" name="total_payment" id="cnArTotal" inputmode="decimal" placeholder="0.00" aria-label="Total payment" readonly tabindex="-1" title="Sum of amounts above (automatic)">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
    
                            <div class="sappcCnArSignBox" aria-label="Approvals and signatures">
                                <div class="sappcCnArSignGrid">
                                    <div class="sappcCnArSignCell">
                                        <input type="text" class="sappcCnArSignIn" name="sig_bpc_chairman" id="cnArSigBpc" aria-label="BPC Chairman">
                                        <div class="sappcCnArSignCap">BPC CHAIRMAN</div>
                                    </div>
                                    <div class="sappcCnArSignCell">
                                        <input type="text" class="sappcCnArSignIn" name="sig_parish_secretary" id="cnArSigSec" aria-label="Parish Secretary">
                                        <div class="sappcCnArSignCap">PARISH SECRETARY</div>
                                    </div>
                                    <div class="sappcCnArSignCell">
                                        <input type="text" class="sappcCnArSignIn" name="sig_presacramental_instructor" id="cnArSigInstr" aria-label="Pre-Sacramental Ministry Instructor">
                                        <div class="sappcCnArSignCap">PRE-SACRAMENTAL MINISTRY INSTRUCTOR</div>
                                    </div>
                                    <div class="sappcCnArSignCell">
                                        <input type="text" class="sappcCnArSignIn" name="sig_parish_priest" id="cnArSigPriest" aria-label="Parish Priest">
                                        <div class="sappcCnArSignCap">PARISH PRIEST</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer sappcChristeningAppModalFooter">
                    <button type="submit" form="confirmationApplicationForm"
                        class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                        id="confirmationApplicationSaveBtn">Save</button>
                    <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnCancel" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <section
        class="sappc-table-panel"
        id="confirmationRecordsPanel"
        data-table-colspan="8"
        data-records-url="{{ route('admin.dashboard.records') }}"
        data-registry-type="confirmation"
        data-payment-details-url="{{ route('admin.confirmation.payment-details') }}"
        data-payment-save-url="{{ route('admin.confirmation.payment-save') }}"
        data-confirmation-application-details-url="{{ route('admin.confirmation.application-details') }}"
        data-confirmation-application-save-url="{{ route('admin.confirmation.application-save') }}"
        data-confirmation-arancel-details-url="{{ route('admin.confirmation.arancel-details') }}"
        data-confirmation-arancel-save-url="{{ route('admin.confirmation.arancel-save') }}"
        data-confirmation-delete-url="{{ route('admin.confirmation.record-delete') }}"
        data-schedule-details-url="{{ route('admin.confirmation.schedule-details') }}"
        aria-label="Confirmation records"
    >
        <div class="sappc-table-toolbar">
            <div class="sappc-table-toolbar_row sappc-table-toolbar_row--primary">
                <div class="sappc-table-toolbar_entries">
                    <label class="visually-hidden" for="confirmationEntries">Entries per page</label>
                    <select id="confirmationEntries" class="form-select form-select-sm sappc-table-toolbar_select" aria-label="Entries per page">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="sappc-toolbar-date-strip" role="group" aria-label="Filter by date range">
                    <span class="sappc-toolbar-date-strip_label">From:</span>
                    <input type="date" id="confirmationDateFrom" class="sappc-toolbar-date-strip_input" name="date_from" aria-label="From date">
                    <span class="sappc-toolbar-date-strip_label">To:</span>
                    <input type="date" id="confirmationDateTo" class="sappc-toolbar-date-strip_input" name="date_to" aria-label="To date">
                    <button type="button" class="sappc-toolbar-date-strip_btn">Filter</button>
                </div>
                <div class="sappc-table-toolbar_letters" role="group" aria-label="Filter by first letter of client last name">
                    <span class="visually-hidden">Filter by first letter of last name A through Z; scroll horizontally to see all letters.</span>
                    <div class="sappc-letter-filter_letters">
                        @foreach (range('A', 'Z') as $letter)
                            <button type="button" class="sappc-letter-filter_btn" data-letter="{{ $letter }}">{{ $letter }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="sappc-table-toolbar_search" role="search">
                    <label class="sappc-table-toolbar_search-heading" for="confirmationSearch">Search:</label>
                    <div class="sappc-table-toolbar_search-wrap">
                        <input type="search" id="confirmationSearch" class="form-control form-control-sm sappc-table-toolbar_search-input" placeholder="" autocomplete="off" aria-label="Search confirmation records" aria-controls="confirmationTableBody">
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
                        <th scope="col">DATE CREATED</th>
                        <th scope="col" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody id="confirmationTableBody" aria-live="polite" aria-relevant="additions text">
                    <tr class="sappc-table-loading">
                        <td colspan="8" class="text-center text-muted py-4">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="sappc-table-footer">
            <p class="sappc-table-footer_info mb-0" id="confirmationTableFooterInfo">Showing 0 entries</p>
            <nav class="sappc-pagination" id="confirmationPagination" aria-label="Table pagination"></nav>
        </div>
    </section>

    <div class="modal fade" id="confirmationScheduleRequestModal" tabindex="-1"
        aria-labelledby="confirmationScheduleRequestModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content sappcScheduleRequestModal">
                <div class="modal-body">
                    <button type="button" class="btn-close sappcScheduleRequestModalClose" data-bs-dismiss="modal"
                        aria-label="Close"></button>

                    <div class="sappcScheduleRequestLayout">
                        <section class="sappcScheduleCalendarCard" aria-label="Calendar">
                            <div class="sappcScheduleCalendarHead sappcScheduleCalendarHead--interactive">
                                <button type="button" class="sappcScheduleCalNav" id="cnCalPrev"
                                    aria-label="Previous month">‹</button>
                                <span class="sappcScheduleCalendarMonthNo" id="cnCalMonthNum">3</span>
                                <select class="sappcScheduleCalendarMonth" id="cnCalMonth" aria-label="Month"></select>
                                <select class="sappcScheduleCalendarYear" id="cnCalYear" aria-label="Year"></select>
                                <button type="button" class="sappcScheduleCalNav" id="cnCalNext"
                                    aria-label="Next month">›</button>
                            </div>

                            <div class="sappcScheduleCalendarGrid" role="grid" aria-label="Select confirmation date"
                                id="cnCalGrid">
                                <div class="sappcScheduleCalendarWeekday sunday">SUN</div>
                                <div class="sappcScheduleCalendarWeekday">MON</div>
                                <div class="sappcScheduleCalendarWeekday">TUE</div>
                                <div class="sappcScheduleCalendarWeekday">WED</div>
                                <div class="sappcScheduleCalendarWeekday">THU</div>
                                <div class="sappcScheduleCalendarWeekday">FRI</div>
                                <div class="sappcScheduleCalendarWeekday saturday">SAT</div>
                                <div id="cnCalDayCells"></div>
                            </div>
                        </section>

                        <section class="sappcScheduleFormCard">
                            <header class="sappcScheduleFormHeader">
                                <img src="{{ asset('assets/logos/SAPPC.png') }}" alt="Parish logo"
                                    class="sappcScheduleFormLogo">
                                <h2 id="confirmationScheduleRequestModalTitle">Confirmation Schedule Request Form</h2>
                            </header>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <form id="confirmationScheduleRequestForm" method="post" autocomplete="off"
                                data-schedule-save-url="{{ route('admin.confirmation.schedule-request') }}"
                                data-schedule-reserved-url="{{ route('admin.confirmation.schedule-reserved-dates') }}"
                                data-default-reference-code="{{ $generatedReferenceCode ?? '' }}">
                                @csrf
                                <div class="sappcScheduleFormField">
                                    <input type="hidden" name="confirmation_id" id="cnScheduleConfirmationId" value="">
                                    <label for="cnScheduleRefCode">Reference Code:</label>
                                    <input type="text" name="reference_code" id="cnScheduleRefCode"
                                        value="{{ $generatedReferenceCode ?? '' }}"
                                        placeholder="System default; edit or pick a table row"
                                        title="Generated on page load. Click a row to use that record's code.">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="cnScheduleContact">Contact Number:</label>
                                    <input type="text" name="contact_number" id="cnScheduleContact" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="cnScheduleClient">Client:</label>
                                    <input type="text" name="client" id="cnScheduleClient" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="cnScheduleAddress">Address:</label>
                                    <input type="text" name="address" id="cnScheduleAddress" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="cnScheduleSex">Sex:</label>
                                    <select name="sex" id="cnScheduleSex" class="form-select">
                                        <option value="">Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="cnScheduleDate">Date:</label>
                                    <input type="date" name="schedule_date" id="cnScheduleDate" required
                                        class="sappcScheduleNativeInput">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="cnScheduleTime24">Time:</label>
                                    <input type="time" name="schedule_time" id="cnScheduleTime24" required
                                        step="60" class="sappcScheduleNativeInput">
                                </div>
                            </form>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <div class="sappcScheduleFormActions">
                                <button type="button" class="sappcScheduleActionBtn is-cancel"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="confirmationScheduleRequestForm"
                                    class="sappcScheduleActionBtn is-reserve">Reserved Schedule</button>
                            </div>

                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('confirmation.js.confirmationScript', [
        'initialTablePayload' => $initialTablePayload ?? null,
        'activeSection' => $activeSection ?? 'application',
    ])
@endpush
