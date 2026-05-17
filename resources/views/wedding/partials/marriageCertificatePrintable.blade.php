<template id="marriageCertificatePrintableTemplate">
    <style>
        /* Certificate of Marriage — single A4 page (Form 97 style), 6mm @page margins */
        :root {
            --mc-red: #b00808;
            --mc-ink: #111;
            --mc-border: 0.35pt solid var(--mc-red);
            --mc-pad-x: 0.85mm;
            --mc-pad-y: 0.52mm;
            --mc-fs: 7.35pt;
            --mc-lh: 1.12;
        }

        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        .mc-wrap {
            box-sizing: border-box;
            margin: 0;
            padding: 6px;
            background: #b0b0b0;
        }

        .mc-wrap *,
        .mc-wrap *::before,
        .mc-wrap *::after {
            box-sizing: border-box;
        }

        .mc-screen-frame {
            width: 198mm;
            max-width: 100%;
            margin: 0 auto;
        }

        .page.mc-a4 {
            width: 198mm;
            height: 285mm;
            max-height: 285mm;
            margin: 0 auto;
            padding: 1.6mm 2mm 1.4mm;
            background: #fff;
            color: var(--mc-ink);
            border: 0.85pt double var(--mc-red);
            font-family: Arial, Helvetica, sans-serif;
            font-size: var(--mc-fs);
            line-height: var(--mc-lh);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page.mc-a4 > * {
            flex-shrink: 0;
        }

        .page.mc-a4 > .mc-main-grid-wrap {
            flex: 1 1 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            flex-shrink: 1 !important;
        }

        .mc-main-grid-wrap .mc-cell-hw {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
        }

        .mc-main-grid-wrap .mc-cell-hw tbody tr {
            height: 6.25%;
        }

        .page.mc-a4 table.mc-tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .page.mc-a4 table.mc-tbl td,
        .page.mc-a4 table.mc-tbl th {
            border: var(--mc-border);
            padding: var(--mc-pad-y) var(--mc-pad-x);
            vertical-align: middle;
            font-size: var(--mc-fs);
            line-height: var(--mc-lh);
            word-wrap: break-word;
        }

        .page.mc-a4 .mc-b-0 td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .mc-meta {
            font-size: 7pt;
            line-height: 1.06;
        }

        .mc-meta strong {
            font-weight: 700;
        }

        .mc-header-row td {
            vertical-align: top;
            padding-bottom: 0.85mm;
        }

        .mc-hd-left {
            width: 44%;
            font-size: 7.15pt;
            line-height: 1.12;
            color: var(--mc-ink);
        }

        .mc-hd-center {
            width: 32%;
            text-align: center;
            padding-left: 0.6mm;
            padding-right: 0.6mm;
        }

        .mc-hd-right {
            width: 24%;
            text-align: center;
            vertical-align: top;
        }

        .mc-line-sm {
            margin: 0;
            font-size: 7.45pt;
            line-height: 1.12;
        }

        .mc-line-md {
            margin: 0;
            font-size: 7.05pt;
            line-height: 1.12;
        }

        .mc-title {
            margin: 0.65mm 0 0;
            font-size: 12.6pt;
            font-weight: 700;
            line-height: 1.08;
            letter-spacing: 0.015em;
            text-transform: uppercase;
        }

        .mc-registry {
            border: var(--mc-border);
            padding: 0.85mm 1.1mm;
            font-size: 6.6pt;
            line-height: 1.1;
        }

        .mc-registry .mc-lbl {
            color: var(--mc-red);
            font-size: 6.1pt;
        }

        .mc-registry .mc-reg-val {
            min-height: 4.25mm;
            margin-top: 0.35mm;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.08;
            color: var(--mc-ink);
        }

        .mc-prov td {
            font-size: 7.35pt;
            vertical-align: bottom;
            padding-top: 0.55mm !important;
            padding-bottom: 0.55mm !important;
        }

        .mc-uline {
            border-bottom: 0.35pt dotted var(--mc-red);
            display: inline-block;
            min-height: 3.75mm;
            min-width: 52%;
            vertical-align: baseline;
            color: var(--mc-ink);
            font-weight: 700;
        }

        .mc-party-band td {
            font-weight: 700;
            text-align: center;
            font-size: 7.55pt;
            text-transform: uppercase;
            padding: 0.72mm var(--mc-pad-x);
            color: var(--mc-red);
            border-bottom: 0.55pt double var(--mc-red);
        }

        .mc-corner {
            width: 7mm;
        }

        .mc-spacer-lbl {
            width: 26%;
        }

        .mc-col-h,
        .mc-col-w {
            width: calc((100% - 7mm - 26%) / 2);
        }

        .mc-num {
            width: 7mm;
            text-align: center;
            font-weight: 700;
            font-size: 7.05pt;
            vertical-align: middle;
            color: var(--mc-red);
        }

        .mc-lbl {
            width: 26%;
            font-size: 7.1pt;
            vertical-align: top;
            color: var(--mc-red);
            line-height: 1.1;
        }

        .mc-lbl strong {
            font-weight: 700;
            font-size: 7.35pt;
            color: var(--mc-red);
        }

        .mc-hint {
            color: var(--mc-red);
            font-size: 5.75pt;
            font-weight: 400;
            display: block;
            margin-top: 0.35mm;
            line-height: 1.08;
        }

        .mc-hint-i {
            color: var(--mc-red);
            font-size: 5.75pt;
        }

        .page.mc-a4 table.mc-tbl.mc-cell-hw td {
            padding: 0.58mm var(--mc-pad-x);
        }

        .mc-val {
            min-height: 4.75mm;
            font-size: 8.45pt;
            font-weight: 700;
            line-height: 1.12;
            border-bottom: 0.35pt dotted var(--mc-red);
            padding: 0.42mm 0 0.18mm;
            color: var(--mc-ink);
        }

        .mc-dt-inner .mc-val {
            margin-top: 0.28mm;
            min-height: 4.35mm;
        }

        .page.mc-a4 table.mc-tbl.mc-cell-hw td:not(.mc-num):not(.mc-lbl) {
            vertical-align: bottom;
        }

        .mc-dt16-17 td.mc-lbl {
            vertical-align: middle;
        }

        .mc-dt-inner {
            width: 100%;
        }

        .mc-dt-inner td {
            border: 0;
            padding: 0 0.75mm 0 0;
            vertical-align: bottom;
            width: 50%;
        }

        .mc-section {
            border-top: var(--mc-border);
            padding: 0.32mm 1.2mm 0.32mm;
            font-size: 7.1pt;
            line-height: 1.1;
            text-align: justify;
        }

        .mc-section p {
            margin: 0.12mm 0;
        }

        .mc-sec-title {
            margin: 0 0 0.28mm;
            font-size: 7.35pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.08;
            color: var(--mc-red);
        }

        .mc-k {
            font-weight: 700;
        }

        .mc-ch {
            display: inline-block;
            width: 2.2mm;
            height: 2.2mm;
            border: 0.3pt solid var(--mc-red);
            margin-right: 0.55mm;
            vertical-align: middle;
            flex-shrink: 0;
        }

        .mc-checkline {
            margin: 0.12mm 0;
            display: flex;
            align-items: flex-start;
            gap: 0.35mm;
            font-size: 7pt;
            line-height: 1.08;
        }

        .mc-checkline > span:last-child {
            flex: 1 1 auto;
            min-width: 0;
        }

        .mc-bl {
            display: inline-block;
            border-bottom: 0.35pt dotted var(--mc-red);
            min-height: 2.85mm;
            vertical-align: baseline;
            color: var(--mc-ink);
            font-weight: 700;
            font-size: 7.35pt;
            line-height: 1;
        }

        .mc-sign2 {
            width: 100%;
            margin-top: 0.35mm;
            border-collapse: collapse;
        }

        .mc-sign2 td {
            border: 0;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 1mm;
        }

        .mc-sign-cap {
            border-top: 0.35pt dotted #000;
            margin-top: 3.1mm;
            padding-top: 0.25mm;
            font-size: 6.25pt;
            line-height: 1.05;
        }

        .mc-off3 {
            width: 100%;
            margin-top: 0.28mm;
            border-collapse: collapse;
        }

        .mc-off3 td {
            border: 0;
            width: 33.33%;
            vertical-align: bottom;
            padding: 0 0.45mm;
        }

        .mc-off3 td:first-child {
            padding-left: 0;
        }

        .mc-off3 td:last-child {
            padding-right: 0;
        }

        .mc-off-cap {
            border-top: 0.35pt dotted #000;
            margin-top: 2.75mm;
            padding-top: 0.22mm;
            font-size: 5.45pt;
            line-height: 1.04;
        }

        .mc-wit {
            width: 100%;
            margin-top: 0.18mm;
            border-collapse: collapse;
        }

        .mc-wit td {
            border: 0;
            width: 50%;
            padding: 0.22mm 0.75mm 0 0;
            vertical-align: bottom;
        }

        .mc-wit-line {
            min-height: 3.1mm;
            border-bottom: 0.35pt dotted var(--mc-red);
            font-size: 7.25pt;
            font-weight: 700;
            color: var(--mc-ink);
        }

        .mc-admin-outer {
            padding: 0 1.2mm;
        }

        .mc-admin-outer table.mc-tbl td {
            vertical-align: top;
            padding: 0.35mm;
        }

        .mc-admin-h {
            font-size: 7.05pt;
            font-weight: 700;
            margin-bottom: 0.18mm;
            line-height: 1.05;
            color: var(--mc-red);
        }

        .mc-admin-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 0.15mm;
        }

        .mc-admin-grid td {
            border: 0;
            padding: 0.12mm 0;
            vertical-align: bottom;
        }

        .mc-al {
            width: 38%;
            font-size: 6.45pt;
            line-height: 1.05;
            color: var(--mc-ink);
            padding-right: 0.65mm !important;
            white-space: nowrap;
        }

        .mc-af {
            border-bottom: 0.35pt dotted var(--mc-red);
            min-height: 3.55mm;
            font-size: 7pt;
            font-weight: 700;
            color: var(--mc-ink);
        }

        .mc-parish-row {
            font-size: 7pt;
            padding: 0.35mm 1mm !important;
            line-height: 1.08;
        }

        .mc-remarks {
            margin: 0.38mm 1.2mm;
            padding: 0.35mm 0.75mm;
            border: var(--mc-border);
            min-height: 4.5mm;
            font-size: 7pt;
            line-height: 1.06;
        }

        .mc-remarks-sub {
            display: block;
            font-size: 5.85pt;
            font-weight: 400;
            color: var(--mc-red);
            margin: 0.12mm 0 0;
            line-height: 1.06;
        }

        .mc-lcro-bar {
            border-top: var(--mc-border);
            padding: 0.35mm 1mm;
            text-align: center;
            font-size: 7.2pt;
            font-weight: 700;
            letter-spacing: 0.015em;
            line-height: 1.06;
            color: var(--mc-red);
        }

        .mc-codes {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-content: flex-start;
            gap: 0.22mm;
            padding: 0.35mm 0.85mm;
            margin: 0.15mm 1.2mm 0.18mm;
            border: var(--mc-border);
        }

        .mc-code-cell {
            width: 5.1mm;
            height: 3.65mm;
            border: var(--mc-border);
            font-size: 5.1pt;
            font-weight: 600;
            color: var(--mc-red);
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .mc-parish-ref {
            margin: 0;
            padding: 0.28mm 1mm 0.1mm;
            text-align: center;
            font-size: 7.45pt;
            font-weight: 600;
            line-height: 1.08;
        }

        .mc-print-actions {
            text-align: center;
            margin-top: 8px;
        }

        .mc-print-actions button {
            border: 0;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-weight: 600;
            font-family: Arial, Helvetica, sans-serif;
        }

        .mc-btn-print {
            background: #1c426e;
            color: #fff;
        }

        .mc-btn-reload {
            background: #6c757d;
            color: #fff;
            margin-left: 8px;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }

            .mc-wrap {
                background: #fff;
                padding: 0;
                width: 198mm;
                height: 285mm;
                margin: 0 auto;
                overflow: hidden;
            }

            .mc-screen-frame {
                width: 198mm;
                height: 285mm;
                max-width: none;
                margin: 0;
                overflow: hidden;
            }

            .page.mc-a4 {
                width: 198mm;
                height: 285mm;
                max-height: 285mm;
                padding: 1.3mm 1.6mm 1.2mm;
                border: 0.45pt solid var(--mc-red);
                display: flex;
                flex-direction: column;
                page-break-after: avoid;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .mc-print-actions {
                display: none !important;
            }
        }
    </style>
    <div class="mc-wrap">
        <div class="mc-screen-frame">
            <article class="page mc-a4" id="mcPrintArea">
                <table class="mc-tbl mc-b-0" role="presentation">
                    <tbody>
                        <tr class="mc-header-row">
                            <td class="mc-hd-left">
                                <p class="mc-meta"><strong>Municipal Form No. 97</strong> <span>(revised August
                                        2016)</span></p>
                            </td>
                            <td class="mc-hd-center">
                                <p class="mc-line-sm">Republic of the Philippines</p>
                                <p class="mc-line-md">OFFICE OF THE CIVIL REGISTRAR GENERAL</p>
                                <p class="mc-title">CERTIFICATE OF MARRIAGE</p>
                            </td>
                            <td class="mc-hd-right">
                                <div class="mc-registry">
                                    <span class="mc-lbl">Registry No.</span>
                                    <div class="mc-reg-val" id="mcHdrRegistry"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table class="mc-tbl mc-prov" role="presentation">
                    <tbody>
                        <tr>
                            <td style="width:50%;">Province: <span class="mc-uline" id="mcHdrProvince"></span></td>
                            <td style="width:50%;">City/Municipality: <span class="mc-uline" id="mcHdrCity"></span></td>
                        </tr>
                    </tbody>
                </table>

                <table class="mc-tbl mc-party-band" role="presentation">
                    <tbody>
                        <tr>
                            <td class="mc-corner"></td>
                            <td class="mc-spacer-lbl"></td>
                            <td class="mc-col-h">HUSBAND</td>
                            <td class="mc-col-w">WIFE</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mc-main-grid-wrap">
                <table class="mc-tbl mc-cell-hw" aria-label="Marriage certificate particulars">
                    <tbody>
                        <tr>
                            <td class="mc-num">1</td>
                            <td class="mc-lbl"><strong>Name of Contracting Parties</strong><span class="mc-hint">(First)
                                    (Middle) (Last)</span></td>
                            <td>
                                <div class="mc-val" id="mcHName"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWName"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">2</td>
                            <td class="mc-lbl"><strong>2a. Date of Birth</strong> / <strong>2b. Age</strong><span
                                    class="mc-hint">(Day) — (Month) — (Year) / (Age)</span></td>
                            <td>
                                <div class="mc-val"><span id="mcHDob"></span> <span class="mc-hint-i" id="mcHAge"></span>
                                </div>
                            </td>
                            <td>
                                <div class="mc-val"><span id="mcWDob"></span> <span class="mc-hint-i" id="mcWAge"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">3</td>
                            <td class="mc-lbl"><strong>Place of Birth</strong><span class="mc-hint">(City/Municipality) —
                                    (Province) — (Country)</span></td>
                            <td>
                                <div class="mc-val" id="mcHPob"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWPob"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">4</td>
                            <td class="mc-lbl"><strong>4a. Sex</strong> / <strong>4b. Citizenship</strong></td>
                            <td>
                                <div class="mc-val"><span id="mcHSex"></span> <span class="mc-hint-i">/</span> <span
                                        id="mcHCitz"></span></div>
                            </td>
                            <td>
                                <div class="mc-val"><span id="mcWSex"></span> <span class="mc-hint-i">/</span> <span
                                        id="mcWCitz"></span></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">5</td>
                            <td class="mc-lbl"><strong>Residence</strong><span class="mc-hint">(House No., St.,
                                    Barangay, City/Municipality, Province, Country)</span></td>
                            <td>
                                <div class="mc-val" id="mcHRes"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWRes"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">6</td>
                            <td class="mc-lbl"><strong>Religion / Religious Sect</strong></td>
                            <td>
                                <div class="mc-val" id="mcHRel"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWRel"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">7</td>
                            <td class="mc-lbl"><strong>Civil Status</strong></td>
                            <td>
                                <div class="mc-val" id="mcHCivil"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWCivil"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">8</td>
                            <td class="mc-lbl"><strong>Name of Father</strong><span class="mc-hint">(First) (Middle)
                                    (Last)</span></td>
                            <td>
                                <div class="mc-val" id="mcHFather"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWFather"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">9</td>
                            <td class="mc-lbl"><strong>Citizenship</strong> <span class="mc-hint-i">(of Father)</span>
                            </td>
                            <td>
                                <div class="mc-val" id="mcHFatherCitz"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWFatherCitz"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">10</td>
                            <td class="mc-lbl"><strong>Maiden Name of Mother</strong><span class="mc-hint">(First)
                                    (Middle) (Last)</span></td>
                            <td>
                                <div class="mc-val" id="mcHMother"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWMother"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">11</td>
                            <td class="mc-lbl"><strong>Citizenship</strong> <span class="mc-hint-i">(of Mother)</span>
                            </td>
                            <td>
                                <div class="mc-val" id="mcHMotherCitz"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWMotherCitz"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">12</td>
                            <td class="mc-lbl"><strong>Name of Person Who Gave Consent or Advice</strong><span
                                    class="mc-hint">(First) (Middle) (Last)</span></td>
                            <td>
                                <div class="mc-val" id="mcHConsentName"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWConsentName"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">13</td>
                            <td class="mc-lbl"><strong>Relationship</strong></td>
                            <td>
                                <div class="mc-val" id="mcHConsentRel"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWConsentRel"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">14</td>
                            <td class="mc-lbl"><strong>Residence</strong> <span class="mc-hint-i">(of person giving
                                    consent)</span></td>
                            <td>
                                <div class="mc-val" id="mcHConsentRes"></div>
                            </td>
                            <td>
                                <div class="mc-val" id="mcWConsentRes"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="mc-num">15</td>
                            <td class="mc-lbl"><strong>Place of Marriage</strong></td>
                            <td colspan="2">
                                <div class="mc-val" id="mcPlaceMarriage"></div>
                            </td>
                        </tr>
                        <tr class="mc-dt16-17">
                            <td class="mc-num" style="line-height:1.05;">16<br>17</td>
                            <td class="mc-lbl"><strong>16. Date of Marriage</strong><br><strong>17. Time of
                                    Marriage</strong></td>
                            <td colspan="2">
                                <table class="mc-dt-inner" role="presentation">
                                    <tbody>
                                        <tr>
                                            <td><span class="mc-hint-i">Date</span>
                                                <div class="mc-val" id="mcDateMarriage"></div>
                                            </td>
                                            <td><span class="mc-hint-i">Time</span>
                                                <div class="mc-val" id="mcTimeMarriage"></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>

                <div class="mc-section">
                    <p class="mc-sec-title">18. Certification of the contracting parties</p>
                    <p><span class="mc-k">THIS IS TO CERTIFY:</span> That I, <strong id="mc18HName"></strong>, and I,
                        <strong id="mc18WName"></strong>, of legal age, contracted marriage; the foregoing particulars
                        are true; marriage settlement copy is attached / not applicable.</p>
                    <p class="mc-checkline"><span class="mc-ch" aria-hidden="true"></span><span>We have entered into a
                            marriage settlement, a copy of which is hereto attached.</span></p>
                    <p class="mc-checkline"><span class="mc-ch" aria-hidden="true"></span><span>We have not entered
                            into a marriage settlement.</span></p>
                    <p><span class="mc-k">IN WITNESS WHEREOF,</span> we signed this certificate in quadruplicate this
                        <span class="mc-bl" style="width:7mm">&nbsp;</span> day of <span class="mc-bl"
                            style="width:22mm">&nbsp;</span>.</p>
                    <table class="mc-sign2" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="mc-sign-cap">(Signature of Husband)</div>
                                </td>
                                <td>
                                    <div class="mc-sign-cap">(Signature of Wife)</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mc-section">
                    <p class="mc-sec-title">19. Certification of the solemnizing officer</p>
                    <p><span class="mc-k">THIS IS TO CERTIFY:</span> THAT BEFORE ME, on the date and place
                        above-written, the parties were joined in marriage.</p>
                    <p class="mc-checkline"><span class="mc-ch" aria-hidden="true"></span><span><strong>a.</strong>
                            Marriage License No. <span class="mc-bl" style="width:14mm">&nbsp;</span> issued on <span
                                class="mc-bl" style="width:12mm">&nbsp;</span> at <span class="mc-bl"
                                style="width:18mm">&nbsp;</span>.</span></p>
                    <p class="mc-checkline"><span class="mc-ch" aria-hidden="true"></span><span><strong>b.</strong> No
                            marriage license was necessary, under Art. <span class="mc-bl"
                                style="width:6mm">&nbsp;</span> of Executive Order No. 209.</span></p>
                    <p class="mc-checkline"><span class="mc-ch" aria-hidden="true"></span><span><strong>c.</strong> The
                            marriage was solemnized in accordance with Presidential Decree No. 1083.</span></p>
                    <p class="mc-meta" style="margin-top:0.35mm;"><strong>Printed name of solemnizing officer:</strong>
                        <span id="mcSolemnizer"></span></p>
                    <table class="mc-off3" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="mc-off-cap">(Signature Over Printed Name of Solemnizing Officer)</div>
                                </td>
                                <td>
                                    <div class="mc-off-cap">(Position/Designation)</div>
                                </td>
                                <td>
                                    <div class="mc-off-cap">(Religion/Religious Sect, Registry No. and Expiration Date,
                                        if applicable)</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mc-section">
                    <p class="mc-sec-title">20a. Witness (Print Name and Sign)</p>
                    <table class="mc-wit" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="mc-wit-line" id="mcWitness1"></div>
                                </td>
                                <td>
                                    <div class="mc-wit-line" id="mcWitness2"></div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="mc-wit-line" id="mcWitness3"></div>
                                </td>
                                <td>
                                    <div class="mc-wit-line" id="mcWitness4"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mc-hint" style="margin:0.35mm 0 0;">Additional at the back</p>
                </div>

                <div class="mc-admin-outer">
                    <table class="mc-tbl" role="presentation">
                        <tbody>
                            <tr>
                                <td style="width:50%;">
                                    <div class="mc-admin-h">21. RECEIVED BY</div>
                                    <table class="mc-admin-grid" role="presentation">
                                        <tbody>
                                            <tr>
                                                <td class="mc-al">Signature</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                            <tr>
                                                <td class="mc-al">Name in Print</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                            <tr>
                                                <td class="mc-al">Title or Position</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                            <tr>
                                                <td class="mc-al">Date</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td style="width:50%;">
                                    <div class="mc-admin-h">22. REGISTERED AT THE OFFICE OF THE CIVIL REGISTRAR</div>
                                    <table class="mc-admin-grid" role="presentation">
                                        <tbody>
                                            <tr>
                                                <td class="mc-al">Signature</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                            <tr>
                                                <td class="mc-al">Name in Print</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                            <tr>
                                                <td class="mc-al">Title or Position</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                            <tr>
                                                <td class="mc-al">Date</td>
                                                <td class="mc-af"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="mc-parish-row" colspan="2">Parish register reference — Book <strong
                                        id="mcBookNo"></strong>, Page <strong id="mcPageNo"></strong>, Register No.
                                    <strong id="mcRegisterNo"></strong>, Date issued: <strong id="mcDateIssued"></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mc-remarks">
                    <strong>REMARKS / ANNOTATIONS</strong>
                    <span class="mc-remarks-sub">(for LCRO/OCRG/Shari'a Circuit Registrar Use Only)</span>
                    <div id="mcPurpose"></div>
                </div>

                <div class="mc-lcro-bar">TO BE FILLED-UP AT THE OFFICE OF THE CIVIL REGISTRAR</div>
                <div class="mc-codes" aria-hidden="true">
                    @foreach ([
        '4bH', '4bW', '5H', '5W', '6H', '6W', '7H', '7W', '8H', '8W', '9H', '9W',
        '10H', '10W', '11H', '11W', '12H', '12W', '13H', '13W', '14H', '14W',
        '15H', '15W', '16H', '16W', '17H', '17W', '18H', '18W',
    ] as $code)
                        <div class="mc-code-cell">{{ $code }}</div>
                    @endforeach
                </div>
                <p class="mc-parish-ref">Parish extract — Ref. <span id="mcRefCode"></span></p>
            </article>
        </div>
        <div class="mc-print-actions">
            <button type="button" class="mc-btn-print" onclick="window.print()">Print certificate</button>
            <button type="button" class="mc-btn-reload"
                onclick="window.opener && window.opener.sappcReloadMarriagePrintWindow ? window.opener.sappcReloadMarriagePrintWindow(window) : window.location.reload()">Reload</button>
        </div>
    </div>
</template>
