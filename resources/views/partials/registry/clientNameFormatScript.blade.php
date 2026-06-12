<script>
    (function($) {
        'use strict';

        function capitalizeNamePart(str) {
            var s = String(str == null ? '' : str).trim();
            if (!s.length) {
                return '';
            }
            return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();
        }

        function titleCaseNamePart(str) {
            return String(str == null ? '' : str).trim().split(/\s+/).filter(function(w) {
                return w.length;
            }).map(function(w) {
                return capitalizeNamePart(w);
            }).join(' ');
        }

        window.sappcFormatClientDisplayName = function(value) {
            var s = String(value == null ? '' : value).trim();
            if (!s.length || s === '\u2014' || s === '—') {
                return s;
            }
            return s.split(/\s+/).map(function(part) {
                if (/^[A-Za-z]\.$/.test(part)) {
                    return part.charAt(0).toUpperCase() + '.';
                }
                return capitalizeNamePart(part);
            }).join(' ');
        };

        window.sappcFormatAddress = function(value) {
            var s = String(value == null ? '' : value).trim();
            if (!s.length || s === '\u2014' || s === '—') {
                return s;
            }
            return titleCaseNamePart(s);
        };

        window.sappcFormatPriestName = function(value) {
            return window.sappcFormatAddress(value);
        };

        window.sappcFormatNamePart = function(value) {
            return capitalizeNamePart(value);
        };

        window.sappcFormatFamilyName = function(value) {
            return titleCaseNamePart(value);
        };

        window.sappcCapitalizeNamePart = capitalizeNamePart;
        window.sappcTitleCaseNamePart = titleCaseNamePart;

        function fieldKey($el) {
            return (String($el.attr('id') || '') + ' ' + String($el.attr('name') || '')).toLowerCase();
        }

        function shouldFormatAddressField($el) {
            var key = fieldKey($el);
            return /address|barangay|municipality|province|birthplace|place_of_birth|pob|parent_address|top_address|present_address|deceased_address/.test(key);
        }

        function shouldFormatPriestField($el) {
            var key = fieldKey($el);
            return /priest|minister|officiating|solemnizer|parish_priest|sig_parish_priest|approval_parish_priest|approved_parish_priest|approval_minister/.test(key);
        }

        function shouldFormatNamePartField($el) {
            var key = fieldKey($el);
            if (shouldFormatPriestField($el) || shouldFormatAddressField($el)) {
                return false;
            }
            return /(^|[^a-z])(first_name|middle_name)([^a-z]|$)|firstname|middlename|maninoy|maninay|childfirst|childmiddle|fatherfirst|fathermiddle|motherfirst|mothermiddle/.test(key);
        }

        function shouldFormatFamilyNameField($el) {
            var key = fieldKey($el);
            if (shouldFormatPriestField($el) || shouldFormatAddressField($el) || shouldFormatNamePartField($el)) {
                return false;
            }
            return /(^|[^a-z])(family_name|last_name)([^a-z]|$)|familyname|lastname|childlast|fatherlast|motherlast/.test(key);
        }

        function shouldFormatFullPersonNameField($el) {
            var key = fieldKey($el);
            if (shouldFormatPriestField($el) || shouldFormatAddressField($el) || shouldFormatNamePartField($el) || shouldFormatFamilyNameField($el)) {
                return false;
            }
            if (/place|address|religion|contact|remark|relation|topic|amount|permit|status|age|date|time|number|bec|selda|kinamatyan|obligation|stewardship|sacrament|ceremony|interment|niche|occupation|label|remarks|ar_number|doc_/.test(key)) {
                return false;
            }
            return /father|mother|maiden|godparent|deceased|spouse|claimant|sponsor|full_name|signature|chairman|secretary|fiscal|approval_|sig_|noted_|marriage_sponsors/.test(key);
        }

        $(function() {
            $(document).on('blur', 'input[type="text"]:not([readonly]), textarea:not([readonly])', function() {
                var $el = $(this);
                var raw = $el.val();
                var formatted = raw;
                if (shouldFormatPriestField($el)) {
                    formatted = window.sappcFormatPriestName(raw);
                } else if (shouldFormatAddressField($el)) {
                    formatted = window.sappcFormatAddress(raw);
                } else if (shouldFormatNamePartField($el)) {
                    formatted = window.sappcFormatNamePart(raw);
                } else if (shouldFormatFamilyNameField($el)) {
                    formatted = window.sappcFormatFamilyName(raw);
                } else if (shouldFormatFullPersonNameField($el)) {
                    formatted = window.sappcFormatFamilyName(raw);
                } else {
                    return;
                }
                if (formatted !== raw) {
                    $el.val(formatted);
                }
            });
        });
    })(jQuery);
</script>
