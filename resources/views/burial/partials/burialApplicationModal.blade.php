{{-- Burial application — layout aligned to parish printed form --}}
<div class="modal fade" id="burialApplicationFormModal" tabindex="-1" aria-labelledby="burialApplicationFormModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content sappcChristeningAppModal">
            <div class="modal-header flex-wrap gap-2 border-bottom-0 pb-0 align-items-center">
                <h2 class="modal-title h6 mb-0 text-muted fw-normal visually-hidden" id="burialApplicationFormModalTitle">
                    Burial application</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body pt-0">
                <form class="sappcChristeningAppForm sappcChOfficial sappcBurialAppForm" id="burialApplicationForm"
                    action="#" method="post" autocomplete="off">
                    @csrf
                    <input type="hidden" name="burial_id" id="brAppBurialId" value="">

                    <header class="sappcChOfficialHeader">
                        <div class="sappcChOfficialLogo sappcChOfficialLogoLeft">
                            <img src="{{ asset('assets/logos/DSA.jpg') }}" width="80" height="80"
                                alt="Diocese of San Jose de Antique" class="sappcChOfficialLogoImg">
                        </div>
                        <div class="sappcChOfficialMasthead">
                            <p class="sappcChOfficialMastheadLine sappcChOfficialMastheadLineStrong">THE ROMAN CATHOLIC PARISH
                                OF ST. ANTHONY OF PADUA</p>
                            <p class="sappcChOfficialMastheadLine">DIOCESE OF SAN JOSE de ANTIQUE</p>
                            <p class="sappcChOfficialMastheadLine">BARBAZA, 5706, ANTIQUE, PHILIPPINES</p>
                            <p class="sappcBurialAppDocTitle" role="heading" aria-level="1">APLIKASYON SA PAGPALUBONG</p>
                        </div>
                        <div class="sappcChOfficialLogo sappcChOfficialLogoRight sappcChOfficialLogoParishSeal">
                            <img src="{{ asset('assets/logos/SAPPC.png') }}" width="80" height="80"
                                alt="Parish of St. Anthony of Padua, Barbaza"
                                class="sappcChOfficialLogoImg sappcChOfficialLogoImgParishSeal">
                        </div>
                    </header>

                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppDeceasedName">† Ngaran kang Minatay:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppDeceasedName" name="deceased_name"
                            autocomplete="off">
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppDeceasedAge">† Edad kang Minatay:</label>
                        <div class="sappcBurialAppInline">
                            <input type="text" class="sappcBurialAppIn sappcBurialAppIn--narrow" id="brAppDeceasedAge"
                                name="deceased_age" inputmode="numeric" maxlength="4" aria-label="Edad kang minatay">
                            <span class="sappcBurialAppRadioRow" role="group" aria-label="Civil status">
                                <label><input type="radio" name="marital_status" value="married"> Kasado</label>
                                <label><input type="radio" name="marital_status" value="single"> Single</label>
                            </span>
                        </div>
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppSpouseName">(Kun kasado) Ngaran kang Asawa/Bana:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppSpouseName" name="spouse_name"
                            autocomplete="off">
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppDeceasedAddress">† Address kang Minatay:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppDeceasedAddress" name="deceased_address"
                            autocomplete="street-address">
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppKinamatyan">† Kinamatyan:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppKinamatyan" name="kinamatyan"
                            title="Cause of death or circumstances">
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppOccupation">† Obra kang nagakabuhi:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppOccupation" name="occupation"
                            autocomplete="off">
                    </div>

                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppClaimantName">Tag-iya kang Minatay:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppClaimantName" name="claimant_name"
                            autocomplete="name">
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppClaimantRelation">Relasyon sa Minatay:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppClaimantRelation" name="claimant_relation">
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppClaimantPlace">Lugar kang Tag-iya:</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppClaimantPlace" name="claimant_place">
                    </div>

                    <div class="sappcBurialAppRow sappcBurialAppRow--full">
                        <label class="sappcBurialAppLabel" for="brAppChurchObligation">† Ano ang nangin obligasyon sa
                            Simbahan?</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppChurchObligation" name="church_obligation">
                    </div>
                    <div class="sappcBurialAppRow">
                        <div class="sappcBurialAppLabel">† Nangin katapo bala kang Parish BEC Program?</div>
                        <div class="sappcBurialAppInline sappcBurialAppInline--stretch">
                            <span class="sappcBurialAppRadioRow">
                                <label><input type="radio" name="parish_bec" value="huo"> Huo</label>
                                <label><input type="radio" name="parish_bec" value="indi"> Indi</label>
                            </span>
                            <span class="sappcBurialAppInline sappcBurialAppGrow">
                                <span class="text-nowrap me-1">Selda:</span>
                                <input type="text" class="sappcBurialAppIn" id="brAppBecSelda" name="bec_selda">
                            </span>
                        </div>
                    </div>
                    <div class="sappcBurialAppRow">
                        <div class="sappcBurialAppLabel">† Nangin kabahin bala kang Christian Stewardship Program?</div>
                        <div class="sappcBurialAppRadioRow">
                            <label><input type="radio" name="stewardship" value="huo"> Huo</label>
                            <label><input type="radio" name="stewardship" value="indi"> Indi</label>
                        </div>
                    </div>
                    <div class="sappcBurialAppRow">
                        <div class="sappcBurialAppLabel">† Nakabaton bala kang Sakramento kang Pagbadlis?</div>
                        <div class="sappcBurialAppInline sappcBurialAppInline--stretch">
                            <span class="sappcBurialAppRadioRow">
                                <label><input type="radio" name="baptized_sacrament" value="huo"> Huo</label>
                                <label><input type="radio" name="baptized_sacrament" value="wala"> Wala</label>
                            </span>
                            <span class="sappcBurialAppInline sappcBurialAppGrow">
                                <span class="text-nowrap me-1">Petsa kang Pagbadlis:</span>
                                <input type="date" class="sappcBurialAppIn sappcBurialAppIn--date" id="brAppBaptismDate"
                                    name="baptism_date">
                            </span>
                        </div>
                    </div>

                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppDeathDate">† Petsa kang Pagkamatay:</label>
                        <input type="date" class="sappcBurialAppIn sappcBurialAppIn--date" id="brAppDeathDate"
                            name="death_date">
                    </div>
                    <div class="sappcBurialAppRow">
                        <div class="sappcBurialAppLabel">Petsa kag Oras kang Lubong</div>
                        <div class="sappcBurialAppInline">
                            <span class="d-inline-flex align-items-end gap-1">
                                <span class="text-nowrap">Petsa kang Lubong:</span>
                                <input type="date" class="sappcBurialAppIn sappcBurialAppIn--date" id="brAppBurialDate"
                                    name="burial_date">
                            </span>
                            <span class="d-inline-flex align-items-end gap-1">
                                <span class="text-nowrap">Oras kang Lubong:</span>
                                <input type="time" class="sappcBurialAppIn sappcBurialAppIn--time" id="brAppBurialTime"
                                    name="burial_time">
                            </span>
                        </div>
                    </div>
                    <div class="sappcBurialAppRow">
                        <label class="sappcBurialAppLabel" for="brAppBurialPermitNo">Burial Permit No.</label>
                        <input type="text" class="sappcBurialAppIn" id="brAppBurialPermitNo" name="burial_permit_no">
                    </div>

                    <div class="sappcBurialAppChildBox" aria-labelledby="brAppChildBoxTitle">
                        <h2 class="sappcBurialAppChildBoxTitle" id="brAppChildBoxTitle">Kung Bata ang Minatay</h2>
                        <div class="sappcBurialAppRow">
                            <label class="sappcBurialAppLabel" for="brAppMinorFather">Ngaran kang Tatay:</label>
                            <input type="text" class="sappcBurialAppIn" id="brAppMinorFather" name="minor_father_name">
                        </div>
                        <div class="sappcBurialAppRow">
                            <label class="sappcBurialAppLabel" for="brAppMinorMother">Ngaran kang Nanay:</label>
                            <input type="text" class="sappcBurialAppIn" id="brAppMinorMother" name="minor_mother_name">
                        </div>
                    </div>

                    <div class="sappcBurialAppRow">
                        <div class="sappcBurialAppLabel">Seremonya kang Paglubong:</div>
                        <div class="sappcBurialAppRadioRow">
                            <label><input type="radio" name="ceremony_type" value="blessing"> Blessing</label>
                            <label><input type="radio" name="ceremony_type" value="funeral_mass"> Funeral Mass</label>
                        </div>
                    </div>
                    <div class="sappcBurialAppRow">
                        <div class="sappcBurialAppLabel">Paglubong:</div>
                        <div class="sappcBurialAppInline flex-column align-items-stretch gap-2">
                            <div class="sappcBurialAppRadioRow">
                                <label><input type="radio" name="interment_type" value="lupa"> Lupa</label>
                                <label><input type="radio" name="interment_type" value="land_rental"> Land Rental</label>
                                <label><input type="radio" name="interment_type" value="panteon_rental"> Panteon Rental</label>
                            </div>
                            <div class="sappcBurialAppInline align-items-end">
                                <span class="text-nowrap">(Niche No.</span>
                                <input type="text" class="sappcBurialAppIn" id="brAppNicheNo" name="niche_no"
                                    style="max-width:10rem" aria-label="Niche number" maxlength="32">
                                <span class="align-self-end pb-1">)</span>
                            </div>
                        </div>
                    </div>

                    <h2 class="sappcBurialAppArancelTitle">ARANCEL</h2>
                    <div class="table-responsive">
                        <table class="sappcBurialAppArancelTable">
                            <thead>
                                <tr>
                                    <th scope="col">ITEM</th>
                                    <th scope="col">AMOUNT</th>
                                    <th scope="col">REMARKS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="sappcBurialAppArItem">Panteon Rental</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_panteon_amount"
                                            inputmode="decimal" aria-label="Panteon rental amount"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_panteon_remarks"
                                            aria-label="Panteon rental remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem">Land Rental <span class="sappcBurialAppArSub">(Rights
                                            for museleo site)</span></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_land_amount"
                                            inputmode="decimal"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_land_remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem">“Kalkal/Sarado”</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_kalkal_amount"
                                            inputmode="decimal"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_kalkal_remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem">Cemetery Maintenance</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_cemetery_amount"
                                            inputmode="decimal"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_cemetery_remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem">Mass Arancel</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_mass_amount"
                                            inputmode="decimal"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_mass_remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem">Proroga</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_proroga_amount"
                                            inputmode="decimal"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_proroga_remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem">Others:</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_others_amount"
                                            inputmode="decimal"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_others_remarks"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem" aria-hidden="true">&nbsp;</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_extra_1_amount"
                                            inputmode="decimal" aria-label="Others amount line 2"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_extra_1_remarks"
                                            aria-label="Others remarks line 2"></td>
                                </tr>
                                <tr>
                                    <td class="sappcBurialAppArItem" aria-hidden="true">&nbsp;</td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_extra_2_amount"
                                            inputmode="decimal" aria-label="Others amount line 3"></td>
                                    <td><input type="text" class="sappcBurialAppIn" name="ar_extra_2_remarks"
                                            aria-label="Others remarks line 3"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="sappcBurialAppSignBlock">
                        <p class="sappcBurialAppSignIntro">Noted by:</p>
                        <div class="sappcBurialAppSignTwoCol">
                            <div class="sappcBurialAppSignCol">
                                <input type="text" class="sappcBurialAppIn" id="brAppNotedBpc" name="noted_bpc_chairman"
                                    aria-label="BPC Chairman signature or name">
                                <span class="sappcBurialAppSignCap">BPC Chairman</span>
                            </div>
                            <div class="sappcBurialAppSignCol">
                                <input type="text" class="sappcBurialAppIn" id="brAppNotedFiscal" name="noted_parish_fiscal"
                                    aria-label="Parish fiscal secretary signature or name">
                                <span class="sappcBurialAppSignCap">Parish Fiscal Secretary</span>
                            </div>
                        </div>
                        <p class="sappcBurialAppSignIntro">Approved by:</p>
                        <div class="sappcBurialAppSignApproved">
                            <input type="text" class="sappcBurialAppIn" id="brAppApprovedPriest" name="approved_parish_priest"
                                aria-label="Parish priest signature or name">
                            <span class="sappcBurialAppSignCap">Parish Priest</span>
                        </div>
                    </div>

                    <div class="sappcBurialAppPahanumdom" role="region" aria-label="Pahanumdom">
                        <p class="sappcBurialAppPahanumdomTitle">Pahanumdom:</p>
                        <ol>
                            <li>Sa mga natungdan nga namatyan: Sa pagkamatay kang myembro kang panimalay, magpamaan
                                lagi-lagi sa inyo Barangay Pastoral Council Chairman kag sa iba pa nga meyembro kng council
                                agud mabuligan kag mapatigayun ang mga kinahanglanun sa paghaya kag paglubong. Dayun
                                magpamaan sa Opisina kang Parokya sa pagproseso kang mga kinahanglanun sa paglubong kag
                                agud matalana ang adlaw kag oras kang paglubong.</li>
                            <li>Para sa manami nga liturhiya sa paglubong, ginapangabay ang natungdan nga pamilya sa
                                pagpakig-angot sa Brgy. Pastoral Council Chairman sa pagpreparar kang mga balasahon,
                                ambahanon kag Pangamuyo sang mga tumuluo.</li>
                        </ol>
                    </div>
                </form>
            </div>
            <div class="modal-footer sappcChristeningAppModalFooter">
                <button type="button" class="sappcChristeningAppModalBtn sappcChristeningAppModalBtnSave"
                    id="burialApplicationFormSaveBtn">
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
