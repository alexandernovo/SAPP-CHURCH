    <div class="modal fade" id="weddingScheduleRequestModal" tabindex="-1"
        aria-labelledby="weddingScheduleRequestModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content sappcScheduleRequestModal">
                <div class="modal-body">
                    <button type="button" class="btn-close sappcScheduleRequestModalClose" data-bs-dismiss="modal"
                        aria-label="Close"></button>

                    <div class="sappcScheduleRequestLayout">
                        <section class="sappcScheduleCalendarCard" aria-label="Calendar">
                            <div class="sappcScheduleCalendarHead sappcScheduleCalendarHead--interactive">
                                <button type="button" class="sappcScheduleCalNav" id="wdCalPrev"
                                    aria-label="Previous month">‹</button>
                                <span class="sappcScheduleCalendarMonthNo" id="wdCalMonthNum">3</span>
                                <select class="sappcScheduleCalendarMonth" id="wdCalMonth" aria-label="Month"></select>
                                <select class="sappcScheduleCalendarYear" id="wdCalYear" aria-label="Year"></select>
                                <button type="button" class="sappcScheduleCalNav" id="wdCalNext"
                                    aria-label="Next month">›</button>
                            </div>

                            <div class="sappcScheduleCalendarGrid" role="grid" aria-label="Select wedding date"
                                id="wdCalGrid">
                                <div class="sappcScheduleCalendarWeekday sunday">SUN</div>
                                <div class="sappcScheduleCalendarWeekday">MON</div>
                                <div class="sappcScheduleCalendarWeekday">TUE</div>
                                <div class="sappcScheduleCalendarWeekday">WED</div>
                                <div class="sappcScheduleCalendarWeekday">THU</div>
                                <div class="sappcScheduleCalendarWeekday">FRI</div>
                                <div class="sappcScheduleCalendarWeekday saturday">SAT</div>
                                <div id="wdCalDayCells"></div>
                            </div>
                        </section>

                        <section class="sappcScheduleFormCard">
                            <header class="sappcScheduleFormHeader">
                                <img src="{{ asset('assets/logos/SAPPC.png') }}" alt="Parish logo"
                                    class="sappcScheduleFormLogo">
                                <h2 id="weddingScheduleRequestModalTitle">Wedding Schedule Request Form</h2>
                            </header>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <form id="weddingScheduleRequestForm" method="post" autocomplete="off"
                                data-schedule-save-url="{{ route('admin.wedding.schedule-request') }}"
                                data-schedule-reserved-url="{{ route('admin.wedding.schedule-reserved-dates') }}"
                                data-default-reference-code="{{ $generatedReferenceCode ?? '' }}">
                                @csrf
                                <div class="sappcScheduleFormField">
                                    <input type="hidden" name="wedding_id" id="wdScheduleWeddingId" value="">
                                    <label for="wdScheduleRefCode">Reference Code:</label>
                                    <input type="text" name="reference_code" id="wdScheduleRefCode"
                                        value="{{ $generatedReferenceCode ?? '' }}"
                                        placeholder="System default; edit or pick a table row"
                                        title="Generated on page load. Click a row to use that record's code.">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="wdScheduleContact">Contact Number:</label>
                                    <input type="text" name="contact_number" id="wdScheduleContact" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="wdScheduleClient">Client:</label>
                                    <input type="text" name="client" id="wdScheduleClient" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="wdScheduleAddress">Address:</label>
                                    <input type="text" name="address" id="wdScheduleAddress" value="">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="wdScheduleSex">Sex:</label>
                                    <select name="sex" id="wdScheduleSex" class="form-select">
                                        <option value="">Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="wdScheduleDate">Date:</label>
                                    <input type="date" name="schedule_date" id="wdScheduleDate" required
                                        class="sappcScheduleNativeInput">
                                </div>
                                <div class="sappcScheduleFormField">
                                    <label for="wdScheduleTime24">Time:</label>
                                    <input type="time" name="schedule_time" id="wdScheduleTime24" required
                                        step="60" class="sappcScheduleNativeInput">
                                </div>
                            </form>

                            <hr class="sappcScheduleFormDivider" aria-hidden="true">

                            <div class="sappcScheduleFormActions">
                                <button type="button" class="sappcScheduleActionBtn is-cancel"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="weddingScheduleRequestForm"
                                    class="sappcScheduleActionBtn is-reserve">Reserved Schedule</button>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
