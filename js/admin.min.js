PDbAdmin=function(a){a.fn.PDb_email_obfuscate=function(){try{var a=jQuery.parseJSON(this.attr("data-email-values"))}catch(b){return}a="".concat(a.name,"@",a.domain);this.attr("href","mailto:"+a).html(a).addClass("obfuscated")};return{init:function(){a(".participants_db .ui-tabs-nav li").append(a('<span class="mask"/>'));a(".manage-fields-wrap").on("focus","[name*=title], [name*=default] ",function(){a(this).addClass("focused").closest("td").addClass("focused")}).on("blur",'.manage-fields input[type="text"]',
function(){a(this).removeClass("focused").closest("td").removeClass("focused")});a("a.obfuscate[data-email-values]").each(function(){a(this).PDb_email_obfuscate()});a(".pdb-horiz-scroll-scroller").doubleScroll();a("[data-before]").each(function(){var c=a(this);c.wrap('<span class="pdb-added-content"></span>');c.before(a("<span />",{html:c.data("before"),class:"pdb-precontent"}))});a("[data-after]").each(function(){var c=a(this);c.wrap('<span class="pdb-added-content"></span>');c.after(a("<span />",
{html:c.data("after"),class:"pdb-postcontent"}))});a("div.settings-subsection").closest("tr").addClass("settings-subsection-row").before('<tr class="settings-subsection-row-spacer"></tr>')}}}(jQuery);jQuery(function(){PDbAdmin.init()});
/*
 jQuery version compare plugin

  Usage:
    $.versioncompare(version1[, version2 = jQuery.fn.jquery])

  Example:
    console.log($.versioncompare("1.4", "1.6.4"));

  Return:
    0 if two params are equal
    1 if the second is lower
   -1 if the second is higher

  Licensed under the MIT:
  http://www.opensource.org/licenses/mit-license.php

  Copyright (c) 2011, Nobu Funaki @zuzara
*/
(function(a){function c(b){return a.map(b.split("."),function(a){return parseInt(a,10)})}a.versioncompare=function(b,d){if("undefined"===typeof b)throw Error("$.versioncompare needs at least one parameter.");d=d||a.fn.jquery;if(b===d)return 0;b=c(b);d=c(d);for(var f=Math.max(b.length,d.length),e=0;e<f;e++)if(b[e]=b[e]||0,d[e]=d[e]||0,b[e]!=d[e])return b[e]>d[e]?1:-1;return 0}})(jQuery);