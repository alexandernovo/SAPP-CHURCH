<template id="baptismCertificatePrintableTemplate">
    <style>
        .bap-wrap { min-height: 100vh; display: grid; place-items: center; background: #ececec; padding: 1rem; }
        .bap-sheet { width: min(96vw, 48.5rem); aspect-ratio: 1164 / 1800; position: relative; background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,.2); overflow: hidden; }
        .bap-bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; }
        .bap-field { position: absolute; z-index: 1; box-sizing: border-box; border: 0; background: transparent; color: #0e285c; font: 600 1.03rem Arial, sans-serif; letter-spacing: .025em; line-height: 1.05; padding: .05rem .15rem; overflow: hidden; text-overflow: clip; white-space: nowrap; }
        .bap-field:focus { outline: none; }
        .bap-field--center { text-align: center; }
        .bap-name { left: 21%; top: 37.8%; width: 67%; font-size: 1.16rem; letter-spacing: .035em; text-align: center; }
        .bap-birth-day { left: 24%; top: 44.6%; width: 11%; text-align: center; }
        .bap-birth-month-year { left: 45%; top: 44.6%; width: 28%; text-align: center; }
        .bap-birth-year { left: 78%; top: 44.6%; width: 10%; text-align: center; }
        .bap-birthplace { left: 14%; top: 47.1%; width: 74%; }
        .bap-father { left: 24%; top: 49.5%; width: 64%; }
        .bap-mother { left: 24%; top: 51.9%; width: 64%; }
        .bap-address { left: 21%; top: 54.3%; width: 67%; }
        .bap-baptism-day { left: 18%; top: 62.7%; width: 12%; text-align: center; }
        .bap-baptism-month-year { left: 39%; top: 62.7%; width: 34%; text-align: center; }
        .bap-baptism-year { left: 78%; top: 62.7%; width: 10%; text-align: center; }
        .bap-priest { left: 25%; top: 65.2%; width: 63%; }
        .bap-sponsors { left: 34%; top: 67.4%; width: 52%; }
        .bap-sponsors-extra { left: 11%; top: 69.8%; width: 75%; }
        .bap-purpose { left: 32%; top: 77.4%; width: 43%; text-align: center; }
        .bap-book-no { left: 72%; top: 83.8%; width: 17%; }
        .bap-page-no { left: 72%; top: 85.6%; width: 17%; }
        .bap-register-no { left: 73%; top: 87.4%; width: 16%; }
        .bap-date-issued { left: 73%; top: 89.2%; width: 16%; }
        .bap-print-action { text-align: center; margin-top: .75rem; }
        .bap-print-btn { border: 0; background: #0e285c; color: #fff; padding: .5rem .95rem; border-radius: .25rem; cursor: pointer; }
        .bap-reload-btn { border: 0; background: #6c757d; color: #fff; padding: .5rem .95rem; border-radius: .25rem; cursor: pointer; margin-left: .5rem; }
        @page { size: A4 portrait; margin: 0; }
        @media print {
            html, body { margin: 0 !important; padding: 0 !important; width: 210mm; height: 297mm; }
            .bap-wrap { background: #fff; padding: 0; min-height: 0; width: 210mm; height: 297mm; }
            .bap-sheet {
                width: 210mm;
                height: 297mm;
                box-shadow: none;
                page-break-inside: avoid;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .bap-field { border: 0 !important; }
            .bap-print-action { display: none; }
        }
    </style>
    <div class="bap-wrap">
        <div class="bap-sheet" id="bapPrintArea">
            <img class="bap-bg" src="{{ asset('assets/certificates/baptismCert.jpg') }}" alt="">
            <input class="bap-field bap-name" id="bapFullName">
            <input class="bap-field bap-birth-day" id="bapBirthDay">
            <input class="bap-field bap-birth-month-year" id="bapBirthMonthYear">
            <input class="bap-field bap-birth-year" id="bapBirthYear">
            <input class="bap-field bap-birthplace" id="bapBirthplace">
            <input class="bap-field bap-father" id="bapFatherName">
            <input class="bap-field bap-mother" id="bapMotherName">
            <input class="bap-field bap-address" id="bapAddress">
            <input class="bap-field bap-baptism-day" id="bapBaptismDay">
            <input class="bap-field bap-baptism-month-year" id="bapBaptismMonthYear">
            <input class="bap-field bap-baptism-year" id="bapBaptismYear">
            <input class="bap-field bap-priest" id="bapPriestName">
            <input class="bap-field bap-sponsors" id="bapSponsors">
            <input class="bap-field bap-sponsors-extra" id="bapSponsorsExtra">
            <input class="bap-field bap-purpose" id="bapPurpose">
            <input class="bap-field bap-book-no" id="bapBookNo">
            <input class="bap-field bap-page-no" id="bapPageNo">
            <input class="bap-field bap-register-no" id="bapRegisterNo">
            <input class="bap-field bap-date-issued" id="bapDateIssued">
        </div>
        <div class="bap-print-action">
            <button type="button" class="bap-print-btn" onclick="window.print()">Print Certificate</button>
            <button type="button" class="bap-reload-btn" onclick="window.opener && window.opener.sappcReloadBaptismPrintWindow ? window.opener.sappcReloadBaptismPrintWindow(window) : window.location.reload()">Reload Certificate</button>
        </div>
    </div>
</template>
