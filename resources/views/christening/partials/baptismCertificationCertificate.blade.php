<template id="baptismCertificatePrintableTemplate">
    <style>
        .bap-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            background: #ececec;
            padding: 1rem;
            gap: 0.75rem;
        }

        .bap-scale {
            width: 210mm;
            height: 297mm;
            flex-shrink: 0;
        }

        @media screen {
            .bap-scale {
                transform: scale(min(1, calc((100vw - 2rem) / 210mm), calc((100vh - 8rem) / 297mm)));
                transform-origin: top center;
            }
        }

        .bap-sheet {
            width: 210mm;
            height: 297mm;
            position: relative;
            background: #fff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .2);
            overflow: hidden;
        }

        .bap-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            z-index: 0;
            pointer-events: none;
        }

        .bap-field {
            position: absolute;
            z-index: 1;
            box-sizing: border-box;
            margin: 0;
            padding: 0 0.4mm;
            border: 0;
            background: transparent;
            color: #0e285c;
            font-family: Arial;
            font-weight: 400;
            font-size: 3.45mm;
            letter-spacing: .02em;
            line-height: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }

        .bap-field.is-hidden { display: none !important; }

        .bap-field-line {
            line-height: 1.05;
        }

        .bap-name {
            left: 10%;
            top: 38.45%;
            width: 80%;
            font-size: 4.85mm;
            font-weight: 400;
            letter-spacing: .03em;
            text-align: center;
        }

        .bap-birth-day { left: 26%; top: 43.05%; width: 9%; text-align: center; }
        .bap-birth-month-year { left: 44%; top: 43.05%; width: 24%; text-align: center; }
        .bap-birth-year { left: 73%; top: 43.05%; width: 11%; text-align: center; }
        .bap-birthplace { left: 15%; top: 45.4%; width: 70%; }
        .bap-father { left: 29%; top: 47.7%; width: 63%; }
        .bap-mother { left: 29%; top: 49.75%; width: 63%; }
        .bap-address { left: 28%; top: 51.75%; width: 64%; }
        .bap-baptism-day { left: 21.5%; top: 61.2%; width: 9%; text-align: center; }
        .bap-baptism-month-year { left: 42%; top: 61.2%; width: 26%; text-align: center; }
        .bap-baptism-year { left: 73%; top: 61.2%; width: 11%; text-align: center; }
        .bap-priest { left: 28%; top: 64%; width: 60%; }
        .bap-sponsors { left: 38%; top: 65.55%; width: 54%; }
        .bap-sponsors-extra { left: 10%; top: 67.95%; width: 76%; }
        .bap-purpose { left: 38%; top: 74.25%; width: 44%; font-size: 3.3mm; }
        .bap-book-no { right: 14.5%; left: auto; top: 78.25%; width: 18%; text-align: right; padding-right: 0; }
        .bap-page-no { right: 14.5%; left: auto; top: 80%; width: 18%; text-align: right; padding-right: 0; }
        .bap-register-no { right: 14.5%; left: auto; top: 81.75%; width: 18%; text-align: right; padding-right: 0; }
        .bap-date-issued { right: 14.5%; left: auto; top: 83.5%; width: 18%; font-size: 3.2mm; text-align: right; padding-right: 0; }

        .bap-print-action { text-align: center; }

        .bap-print-btn {
            border: 0;
            background: #0e285c;
            color: #fff;
            padding: .5rem .95rem;
            border-radius: .25rem;
            cursor: pointer;
        }

        .bap-reload-btn {
            border: 0;
            background: #6c757d;
            color: #fff;
            padding: .5rem .95rem;
            border-radius: .25rem;
            cursor: pointer;
            margin-left: .5rem;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .bap-wrap {
                display: block !important;
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                gap: 0 !important;
            }

            .bap-scale {
                transform: none !important;
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
            }

            .bap-sheet {
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                break-inside: avoid;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .bap-bg {
                object-fit: fill !important;
                width: 100% !important;
                height: 100% !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .bap-field,
            .bap-field-line {
                display: block !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .bap-print-action { display: none !important; }
        }
    </style>
    <div class="bap-wrap">
        <div class="bap-scale">
            <div class="bap-sheet" id="bapPrintArea">
                <img class="bap-bg" src="{{ asset('assets/certificates/baptismCert.jpg') }}" alt="">
                <span class="bap-field bap-field-line bap-name" id="bapFullName"></span>
                <span class="bap-field bap-field-line bap-birth-day" id="bapBirthDay"></span>
                <span class="bap-field bap-field-line bap-birth-month-year" id="bapBirthMonthYear"></span>
                <span class="bap-field bap-field-line bap-birth-year" id="bapBirthYear"></span>
                <span class="bap-field bap-field-line bap-birthplace" id="bapBirthplace"></span>
                <span class="bap-field bap-field-line bap-father" id="bapFatherName"></span>
                <span class="bap-field bap-field-line bap-mother" id="bapMotherName"></span>
                <span class="bap-field bap-field-line bap-address" id="bapAddress"></span>
                <span class="bap-field bap-field-line bap-baptism-day" id="bapBaptismDay"></span>
                <span class="bap-field bap-field-line bap-baptism-month-year" id="bapBaptismMonthYear"></span>
                <span class="bap-field bap-field-line bap-baptism-year" id="bapBaptismYear"></span>
                <span class="bap-field bap-field-line bap-priest" id="bapPriestName"></span>
                <span class="bap-field bap-field-line bap-sponsors" id="bapSponsors"></span>
                <span class="bap-field bap-field-line bap-sponsors-extra" id="bapSponsorsExtra"></span>
                <span class="bap-field bap-field-line bap-purpose is-hidden" id="bapPurpose"></span>
                <span class="bap-field bap-field-line bap-book-no" id="bapBookNo"></span>
                <span class="bap-field bap-field-line bap-page-no" id="bapPageNo"></span>
                <span class="bap-field bap-field-line bap-register-no" id="bapRegisterNo"></span>
                <span class="bap-field bap-field-line bap-date-issued" id="bapDateIssued"></span>
            </div>
        </div>
        <div class="bap-print-action">
            <button type="button" class="bap-print-btn" onclick="window.print()">Print Certificate</button>
            <button type="button" class="bap-reload-btn"
                onclick="window.opener && window.opener.sappcReloadBaptismPrintWindow ? window.opener.sappcReloadBaptismPrintWindow(window) : window.location.reload()">Reload Certificate</button>
        </div>
    </div>
</template>

<template id="baptismCertificatePreviewTemplate">
    <style id="baptismCertificatePreviewStyles">
        :root {
            --bap-cert-w: 116.4mm;
            --bap-cert-h: 180mm;
            --bap-cert-aspect: 1164 / 1800;
        }

        .bap-sheet {
            position: relative;
            width: 100%;
            height: auto;
            aspect-ratio: var(--bap-cert-aspect);
            overflow: hidden;
            background: #fff;
            box-sizing: border-box;
        }

        .bap-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            z-index: 0;
            pointer-events: none;
        }

        .bap-field {
            position: absolute;
            z-index: 1;
            box-sizing: border-box;
            margin: 0;
            padding: 0 0.4mm;
            border: 0;
            background: transparent;
            color: #0e285c;
            font-family: Arial;
            font-weight: 400;
            font-size: 3.45mm;
            letter-spacing: .02em;
            line-height: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }

        .bap-field.is-hidden { display: none !important; }

        .bap-field-line { line-height: 1.05; }

        .bap-name {
            left: 10%;
            top: 38.45%;
            width: 80%;
            font-size: 4.85mm;
            font-weight: 400;
            letter-spacing: .03em;
            text-align: center;
        }

        .bap-birth-day { left: 26%; top: 43.05%; width: 9%; text-align: center; }
        .bap-birth-month-year { left: 44%; top: 43.05%; width: 24%; text-align: center; }
        .bap-birth-year { left: 73%; top: 43.05%; width: 11%; text-align: center; }
        .bap-birthplace { left: 15%; top: 45.4%; width: 70%; }
        .bap-father { left: 29%; top: 47.7%; width: 63%; }
        .bap-mother { left: 29%; top: 49.75%; width: 63%; }
        .bap-address { left: 28%; top: 51.75%; width: 64%; }
        .bap-baptism-day { left: 21.5%; top: 61.2%; width: 9%; text-align: center; }
        .bap-baptism-month-year { left: 42%; top: 61.2%; width: 26%; text-align: center; }
        .bap-baptism-year { left: 73%; top: 61.2%; width: 11%; text-align: center; }
        .bap-priest { left: 28%; top: 64%; width: 60%; }
        .bap-sponsors { left: 38%; top: 65.55%; width: 54%; }
        .bap-sponsors-extra { left: 10%; top: 67.95%; width: 76%; }
        .bap-purpose { left: 38%; top: 74.25%; width: 44%; font-size: 3.3mm; }
        .bap-book-no { right: 14.5%; left: auto; top: 78.25%; width: 18%; text-align: right; padding-right: 0; }
        .bap-page-no { right: 14.5%; left: auto; top: 80%; width: 18%; text-align: right; padding-right: 0; }
        .bap-register-no { right: 14.5%; left: auto; top: 81.75%; width: 18%; text-align: right; padding-right: 0; }
        .bap-date-issued { right: 14.5%; left: auto; top: 83.5%; width: 18%; font-size: 3.2mm; text-align: right; padding-right: 0; }
    </style>
    <div class="bap-sheet" id="bapPreviewArea">
        <img class="bap-bg" src="{{ asset('assets/certificates/baptismCert.jpg') }}" alt="">
        <span class="bap-field bap-field-line bap-name" id="bapFullName"></span>
        <span class="bap-field bap-field-line bap-birth-day" id="bapBirthDay"></span>
        <span class="bap-field bap-field-line bap-birth-month-year" id="bapBirthMonthYear"></span>
        <span class="bap-field bap-field-line bap-birth-year" id="bapBirthYear"></span>
        <span class="bap-field bap-field-line bap-birthplace" id="bapBirthplace"></span>
        <span class="bap-field bap-field-line bap-father" id="bapFatherName"></span>
        <span class="bap-field bap-field-line bap-mother" id="bapMotherName"></span>
        <span class="bap-field bap-field-line bap-address" id="bapAddress"></span>
        <span class="bap-field bap-field-line bap-baptism-day" id="bapBaptismDay"></span>
        <span class="bap-field bap-field-line bap-baptism-month-year" id="bapBaptismMonthYear"></span>
        <span class="bap-field bap-field-line bap-baptism-year" id="bapBaptismYear"></span>
        <span class="bap-field bap-field-line bap-priest" id="bapPriestName"></span>
        <span class="bap-field bap-field-line bap-sponsors" id="bapSponsors"></span>
        <span class="bap-field bap-field-line bap-sponsors-extra" id="bapSponsorsExtra"></span>
        <span class="bap-field bap-field-line bap-purpose is-hidden" id="bapPurpose"></span>
        <span class="bap-field bap-field-line bap-book-no" id="bapBookNo"></span>
        <span class="bap-field bap-field-line bap-page-no" id="bapPageNo"></span>
        <span class="bap-field bap-field-line bap-register-no" id="bapRegisterNo"></span>
        <span class="bap-field bap-field-line bap-date-issued" id="bapDateIssued"></span>
    </div>
</template>
