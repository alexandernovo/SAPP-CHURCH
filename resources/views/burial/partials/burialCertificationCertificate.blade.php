<template id="burialCertificatePrintableTemplate">
    <style>
        .bc-wrap { min-height: 100vh; display: grid; place-items: center; background: #ececec; padding: 1rem; }
        .bc-sheet { width: min(96vw, 53rem); aspect-ratio: 667 / 1024; position: relative; background: #fff url('{{ asset('assets/certificates/burialCert.jpg') }}') center / cover no-repeat; box-shadow: 0 4px 18px rgba(0,0,0,.2); }
        .bc-field { position: absolute; border: 0; background: transparent; color: #0e285c; font: 600 1.02rem "Times New Roman", serif; padding: .05rem .15rem; }
        .bc-field:focus { outline: none; }
        .bc-name { left: 18%; top: 36.9%; width: 64%; }
        .bc-birth-day { left: 25%; top: 43.5%; width: 14%; text-align: center; }
        .bc-birth-month-year { left: 43%; top: 43.5%; width: 34%; }
        .bc-birthplace { left: 10%; top: 46.8%; width: 67%; }
        .bc-father { left: 23%; top: 50.1%; width: 54%; }
        .bc-mother { left: 22%; top: 53.5%; width: 55%; }
        .bc-address { left: 14%; top: 56.9%; width: 63%; }
        .bc-baptism-date { left: 23%; top: 65.6%; width: 54%; }
        .bc-priest { left: 21%; top: 68.9%; width: 56%; }
        .bc-sponsors { left: 25%; top: 72.3%; width: 52%; }
        .bc-purpose { left: 14%; top: 76.3%; width: 63%; }
        .bc-book-no { left: 68%; top: 82.4%; width: 18%; }
        .bc-page-no { left: 68%; top: 84.8%; width: 18%; }
        .bc-register-no { left: 68%; top: 87.3%; width: 18%; }
        .bc-date-issued { left: 68%; top: 89.8%; width: 18%; }
        .bc-print-action { text-align: center; margin-top: .75rem; }
        .bc-print-btn { border: 0; background: #0e285c; color: #fff; padding: .5rem .95rem; border-radius: .25rem; cursor: pointer; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            html, body { margin: 0 !important; padding: 0 !important; width: 210mm; height: 297mm; }
            .bc-wrap { background: #fff; padding: 0; min-height: 0; width: 210mm; height: 297mm; }
            .bc-sheet {
                width: 210mm;
                height: 297mm;
                box-shadow: none;
                page-break-inside: avoid;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .bc-field { border: 0 !important; }
            .bc-print-action { display: none; }
        }
    </style>
    <div class="bc-wrap">
        <div class="bc-sheet" id="bcPrintArea">
            <input class="bc-field bc-name" id="bcFullName">
            <input class="bc-field bc-birth-day" id="bcBirthDay">
            <input class="bc-field bc-birth-month-year" id="bcBirthMonthYear">
            <input class="bc-field bc-birthplace" id="bcBirthplace">
            <input class="bc-field bc-father" id="bcFatherName">
            <input class="bc-field bc-mother" id="bcMotherName">
            <input class="bc-field bc-address" id="bcAddress">
            <input class="bc-field bc-baptism-date" id="bcBaptismDate">
            <input class="bc-field bc-priest" id="bcPriestName">
            <input class="bc-field bc-sponsors" id="bcSponsors">
            <input class="bc-field bc-purpose" id="bcPurpose">
            <input class="bc-field bc-book-no" id="bcBookNo">
            <input class="bc-field bc-page-no" id="bcPageNo">
            <input class="bc-field bc-register-no" id="bcRegisterNo">
            <input class="bc-field bc-date-issued" id="bcDateIssued">
        </div>
        <div class="bc-print-action">
            <button type="button" class="bc-print-btn" onclick="window.print()">Print Certificate</button>
        </div>
    </div>
</template>
