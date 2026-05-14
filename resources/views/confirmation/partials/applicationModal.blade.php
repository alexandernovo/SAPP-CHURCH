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
