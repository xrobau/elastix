﻿/*
* Kendo UI v2011.3.1129 (http://kendoui.com)
* Copyright 2011 Telerik AD. All rights reserved.
*
* Kendo UI commercial licenses may be obtained at http://kendoui.com/license.
* If you do not own a commercial license, this file shall be governed by the
* GNU General Public License (GPL) version 3. For GPL requirements, please
* review: http://www.gnu.org/copyleft/gpl.html
*/

﻿(function( window, undefined ) {
    kendo.cultures["hr-HR"] = {
        name: "hr-HR",
        numberFormat: {
            pattern: ["- n"],
            decimals: 2,
            ",": ".",
            ".": ",",
            groupSize: [3],
            percent: {
                pattern: ["-n%","n%"],
                decimals: 2,
                ",": ".",
                ".": ",",
                groupSize: [3],
                symbol: "%"
            },
            currency: {
                pattern: ["-n $","n $"],
                decimals: 2,
                ",": ".",
                ".": ",",
                groupSize: [3],
                symbol: "kn"
            }
        },
        calendars: {
            standard: {
                days: {
                    names: ["nedjelja","ponedjeljak","utorak","srijeda","četvrtak","petak","subota"],
                    namesAbbr: ["ned","pon","uto","sri","čet","pet","sub"],
                    namesShort: ["ne","po","ut","sr","če","pe","su"]
                },
                months: {
                    names: ["siječanj","veljača","ožujak","travanj","svibanj","lipanj","srpanj","kolovoz","rujan","listopad","studeni","prosinac",""],
                    namesAbbr: ["sij","vlj","ožu","tra","svi","lip","srp","kol","ruj","lis","stu","pro",""]
                },
                AM: [""],
                PM: [""],
                patterns: {
                    d: "d.M.yyyy.",
                    D: "d. MMMM yyyy.",
                    F: "d. MMMM yyyy. H:mm:ss",
                    g: "d.M.yyyy. H:mm",
                    G: "d.M.yyyy. H:mm:ss",
                    m: "d. MMMM",
                    M: "d. MMMM",
                    s: "yyyy'-'MM'-'dd'T'HH':'mm':'ss",
                    t: "H:mm",
                    T: "H:mm:ss",
                    u: "yyyy'-'MM'-'dd HH':'mm':'ss'Z'",
                    y: "MMMM, yyyy",
                    Y: "MMMM, yyyy"
                },
                "/": ".",
                ":": ":",
                firstDay: 1
            }
        }
    }
})(this);