@php
    $kasalSpouseCols = [
        ['prefix' => 'groom', 'title' => 'NOBYO', 'pfirst' => 'Groom'],
        ['prefix' => 'bride', 'title' => 'NOBYA', 'pfirst' => 'Bride'],
    ];
    $precanaTopics = [
        'Love in Marriage',
        'Communication in Marriage',
        'Responsible Parenthood',
        'Human Sexuality/Art of Loving',
        'Sacrament of Marriage',
        'Natural Family Planning',
        'Orientation for Confession',
    ];
@endphp
@once
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endonce
<div class="modal fade" id="weddingMarriageApplicationModal" tabindex="-1"
    aria-labelledby="weddingMarriageApplicationModalTitle" aria-hidden="true">
    <div
        class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered sappcKasalModal">
        <div class="modal-content sappcChristeningAppModal sappcKasalAppModal">
            <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden"
                    id="weddingMarriageApplicationModalTitle">Aplikasyon sa Kasal</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body pt-0">
                <form class="sappcKasalApp" id="weddingMarriageApplicationForm" action="#" method="post"
                    autocomplete="off" novalidate>
                    @csrf
                    <input type="hidden" name="wedding_id" id="wdMarriageAppWeddingId" value="">

                    <header class="sappcKasalAppHeader">
                        <div class="sappcKasalAppLogo">
                            <img src="{{ asset('assets/logos/DSA.jpg') }}" width="88" height="88" alt=""
                                class="sappcKasalAppLogoImg">
                        </div>
                        <div class="sappcKasalAppMasthead">
                            <p class="sappcKasalAppMastheadLine sappcKasalAppMastheadLine--strong">The Roman Catholic Parish
                                of St. Anthony of Padua</p>
                            <p class="sappcKasalAppMastheadLine">Diocese of San Jose de Antique</p>
                            <p class="sappcKasalAppMastheadLine">Barbaza, 5706, Antique, Philippines</p>
                            <h1 class="sappcKasalAppTitle">APLIKASYON SA KASAL</h1>
                        </div>
                        <div class="sappcKasalAppLogo">
                            <img src="{{ asset('assets/logos/SAPPC.png') }}" width="88" height="88" alt=""
                                class="sappcKasalAppLogoImg sappcKasalAppLogoImgParish">
                        </div>
                    </header>

                    <div class="row g-3 sappcKasalAppCoupleRow">
                        @foreach ($kasalSpouseCols as $sp)
                            <div class="col-md-6 sappcKasalCol">
                                <h2 class="sappcKasalBoxTitle sappcKasalBoxTitle--above">{{ $sp['title'] }}</h2>
                                <div class="sappcKasalBox" aria-label="{{ $sp['title'] }}">
                                    <div class="sappcKasalField sappcKasalField--name">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Name">Name</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Name"
                                            name="{{ $sp['prefix'] }}_full_name" autocomplete="name">
                                        <p class="sappcKasalFieldSub sappcKasalFieldSub--center">Last Name, First Name, Middle
                                            Name</p>
                                    </div>
                                    <div class="sappcKasalFieldRow sappcKasalFieldRow--ageDob">
                                        <div class="sappcKasalField sappcKasalField--short">
                                            <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Age">Age</label>
                                            <input type="text" class="sappcKasalLineIn sappcKasalLineIn--short"
                                                id="wdApp{{ $sp['pfirst'] }}Age" name="{{ $sp['prefix'] }}_age" inputmode="numeric"
                                                maxlength="3" aria-label="Age">
                                        </div>
                                        <div class="sappcKasalField sappcKasalField--short">
                                            <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Dob">Date of
                                                Birth</label>
                                            <input type="date" class="sappcKasalLineIn sappcKasalLineIn--short" id="wdApp{{ $sp['pfirst'] }}Dob"
                                                name="{{ $sp['prefix'] }}_date_of_birth">
                                        </div>
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Pob">Place of
                                            Birth</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Pob"
                                            name="{{ $sp['prefix'] }}_place_of_birth">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Address">Present
                                            Address</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Address"
                                            name="{{ $sp['prefix'] }}_present_address">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Father">Father</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Father"
                                            name="{{ $sp['prefix'] }}_father">
                                    </div>
                                    <div class="sappcKasalField sappcKasalField--mother">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Mother">Mother</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Mother"
                                            name="{{ $sp['prefix'] }}_mother_maiden" aria-label="Mother, maiden name">
                                        <p class="sappcKasalFieldSub sappcKasalFieldSub--center">(ngalan sang Dalaga pa)</p>
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Religion">Religion
                                            / Sect</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Religion"
                                            name="{{ $sp['prefix'] }}_religion">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}BapDate">Date of
                                            Baptism</label>
                                        <input type="date" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}BapDate"
                                            name="{{ $sp['prefix'] }}_baptism_date">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}BapPlace">Place of
                                            Baptism</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}BapPlace"
                                            name="{{ $sp['prefix'] }}_baptism_place">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}ConfDate">Confirmation
                                            Date</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}ConfDate"
                                            name="{{ $sp['prefix'] }}_confirmation_date" placeholder="mm/dd/yyyy or text">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Contact">Contact
                                            No</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Contact"
                                            name="{{ $sp['prefix'] }}_contact" inputmode="tel">
                                    </div>
                                    <div class="sappcKasalField">
                                        <label class="sappcKasalFieldLabel" for="wdApp{{ $sp['pfirst'] }}Sign">Signature</label>
                                        <input type="text" class="sappcKasalLineIn" id="wdApp{{ $sp['pfirst'] }}Sign"
                                            name="{{ $sp['prefix'] }}_signature" placeholder="Name or signature">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <section class="sappcKasalGeneral" aria-label="Civil and church wedding details">
                        <div class="sappcKasalGenRow">
                            <label class="sappcKasalGenLabel" for="wdAppCivilMarriageDate">Civil Marriage Date:</label>
                            <input type="date" class="sappcKasalLineIn" id="wdAppCivilMarriageDate"
                                name="civil_marriage_date" aria-label="Civil marriage date">
                        </div>
                        <div class="sappcKasalGenRow">
                            <label class="sappcKasalGenLabel" for="wdAppCivilMarriagePlace">Civil Marriage Place:</label>
                            <input type="text" class="sappcKasalLineIn" id="wdAppCivilMarriagePlace"
                                name="civil_marriage_place">
                        </div>
                        <div class="sappcKasalGenRow">
                            <label class="sappcKasalGenLabel" for="wdAppPrenuptialInv">Date of Prenuptial
                                Investigation:</label>
                            <input type="date" class="sappcKasalLineIn" id="wdAppPrenuptialInv"
                                name="prenuptial_investigation_date">
                        </div>
                        <div class="sappcKasalGenRow">
                            <label class="sappcKasalGenLabel" for="wdAppChurchWeddingDate">Date of Church Wedding:</label>
                            <input type="date" class="sappcKasalLineIn" id="wdAppChurchWeddingDate"
                                name="church_wedding_date">
                        </div>
                        <div class="sappcKasalGenRow">
                            <label class="sappcKasalGenLabel" for="wdAppChurchWeddingPlace">Place of Church Wedding:</label>
                            <input type="text" class="sappcKasalLineIn" id="wdAppChurchWeddingPlace"
                                name="church_wedding_place">
                        </div>
                        <div class="sappcKasalGenRow">
                            <label class="sappcKasalGenLabel" for="wdAppOfficiatingPriest">Officiating Priest:</label>
                            <input type="text" class="sappcKasalLineIn" id="wdAppOfficiatingPriest"
                                name="officiating_priest">
                        </div>
                        <div class="sappcKasalField" style="margin-top:0.3rem">
                            <span class="sappcKasalFieldLabel">Sponsors</span>
                            <input type="text" class="sappcKasalLineIn" id="wdAppSponsorLine1" name="sponsors_line1" aria-label="Sponsor line 1">
                            <input type="text" class="sappcKasalLineIn mt-1" id="wdAppSponsorLine2" name="sponsors_line2" aria-label="Sponsor line 2">
                            <input type="text" class="sappcKasalLineIn mt-1" id="wdAppSponsorLine3" name="sponsors_line3" aria-label="Sponsor line 3">
                        </div>
                    </section>

                    <div class="sappcKasalDocGrid" role="group" aria-label="Documents and fees">
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_baptismal_certificate" value="1"> Baptismal
                            Certificate</label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_confirmation_certificate" value="1"> Confirmation
                            Certificate</label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_civil_marriage" value="1"> Civil
                            Marriage</label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_prenuptial_interrogation" value="1"> Prenuptial
                            Interrogation</label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_pre_cana" value="1"> Pre-Cana: <input
                                type="text" class="sappcKasalLineIn sappcKasalLineIn--sm" name="doc_pre_cana_remarks"
                                aria-label="Pre-Cana details" placeholder=" "></label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_wedding_fees" value="1"> Wedding
                            Fees: <input type="text" class="sappcKasalLineIn sappcKasalLineIn--sm" name="doc_wedding_fees_remarks"
                                aria-label="Wedding fees details"></label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_marriage_certificate" value="1"> Marriage
                            Certificate: <input type="text" class="sappcKasalLineIn sappcKasalLineIn--sm" name="doc_marriage_certificate_remarks"
                                aria-label="Marriage certificate details"></label>
                        <label class="sappcKasalDocItem"><input type="checkbox" name="doc_presider" value="1"> Presider: <input
                                type="text" class="sappcKasalLineIn sappcKasalLineIn--sm" name="doc_presider_remarks" aria-label="Presider name"></label>
                    </div>

                    <div class="sappcKasalAdminRow">
                        <div class="sappcKasalAdminBlock">
                            <input type="text" class="sappcKasalLineIn sappcKasalAdminInput" name="parish_secretary_name"
                                id="wdAppParishSecretaryName" aria-label="Parish secretary signature or name">
                            <p class="sappcKasalAdminCap">Signature of Parish Secretary</p>
                        </div>
                        <div class="sappcKasalAdminBlock">
                            <input type="date" class="sappcKasalLineIn sappcKasalAdminInput" name="date_of_application"
                                id="wdAppDateApplication" aria-label="Date of application">
                            <p class="sappcKasalAdminCap">Date of Application</p>
                        </div>
                        <div class="sappcKasalAdminBlock">
                            <input type="text" class="sappcKasalLineIn sappcKasalAdminInput" name="ar_number" id="wdAppArNo" aria-label="A R number">
                            <p class="sappcKasalAdminCap">A.R. No.</p>
                        </div>
                    </div>

                    <div class="sappcKasalPrecanaWrap" aria-label="Pre-Cana seminar">
                        <div class="sappcKasalPrecanaBrand">
                            <p class="sappcKasalPrecanaTitle">PRE-CANA SEMINAR</p>
                            <p class="sappcKasalPrecanaFala">With Family and Life Apostolate (FALA)</p>
                        </div>
                        <p class="sappcKasalPrecanaSub">Schedule of Instructions</p>
                        <div class="table-responsive">
                            <table class="sappcKasalPrecanaTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Date</th>
                                        <th scope="col">Topic</th>
                                        <th scope="col">Signature (Facilitator)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($precanaTopics as $i => $topic)
                                        <tr>
                                            <td>
                                                <input type="date" class="sappcKasalPrecanaIn" name="precana[{{ $i }}][date]"
                                                    id="wdAppPrecanaDate{{ $i }}">
                                            </td>
                                            <td>
                                                <input type="text" class="sappcKasalPrecanaIn sappcKasalPrecanaIn--topic" readonly
                                                    name="precana[{{ $i }}][topic]" value="{{ $topic }}"
                                                    aria-label="Topic {{ $i + 1 }}">
                                            </td>
                                            <td>
                                                <input type="text" class="sappcKasalPrecanaIn" name="precana[{{ $i }}][signature]"
                                                    id="wdAppPrecanaSig{{ $i }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h3 class="sappcKasalSponsorHead">LISTAHAN SANG MGA MANINOY KAG MANINAY</h3>
                    <div class="sappcKasalSponsorGrid" aria-label="Sponsor names">
                        @for ($g = 1; $g <= 40; $g++)
                            <div class="sappcKasalSponsorCell">
                                <span class="sappcKasalSponsorBullet" aria-hidden="true">&#8226;</span>
                                <input type="text" class="sappcKasalLineIn sappcKasalLineIn--sm" name="marriage_sponsors[{{ $g }}]"
                                    id="wdAppSponsor{{ $g }}" aria-label="Sponsor {{ $g }}">
                            </div>
                        @endfor
                    </div>

                    <div class="sappcKasalApproval" aria-label="Approvals">
                        <p class="sappcKasalApprovalIntro">Approved by:</p>
                        <div class="sappcKasalApprovalTop">
                            <div class="sappcKasalApprovalBlock">
                                <input type="text" class="sappcKasalLineIn" name="approval_bpc_chairman" id="wdAppApprBpc"
                                    aria-label="BPC Chairman signature or name">
                                <p class="sappcKasalApprovalCap">BPC Chairman</p>
                            </div>
                            <div class="sappcKasalApprovalBlock">
                                <input type="text" class="sappcKasalLineIn" name="approval_parish_fiscal_secretary" id="wdAppApprFiscal"
                                    aria-label="Parish fiscal or secretary">
                                <p class="sappcKasalApprovalCap">Parish Fiscal / Secretary</p>
                            </div>
                        </div>
                        <div class="sappcKasalApprovalBottom">
                            <input type="text" class="sappcKasalLineIn" name="approval_minister" id="wdAppApprMinister"
                                aria-label="Minister (Parish priest)">
                            <p class="sappcKasalApprovalCap">Minister (Parish Priest)</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer sappcChristeningAppModalFooter">
                <button type="submit" form="weddingMarriageApplicationForm" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave" id="weddingMarriageAppSaveBtn">Save</button>
                <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnCancel" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
