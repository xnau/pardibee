/*
 * Participants Database Plugin
 * 
 * @version 1.0
 * 
 * xnau webdesign xnau.com
 * 
 * 
 *  functionality added here:
 *    disable submit after submit click to prevent multiple submissions
 *    perform email obfuscation if enabled
 *    before and after content
 *    scroll to validation error message
 */
PDbShortcodes = (function ($) {
  var submitOnce = function (e) {
    if ($(this).hasClass('pdb-disabled')) {
      e.preventDefault();
      return false;
    }
    $(this).addClass('pdb-disabled');
    return true;
  }
  var precontent = function (el) {
    el.wrap('<span class="pdb-added-content"></span>');
    el.before($('<span />', {
      html : el.data('before'),
      class : 'pdb-precontent'
    }));
  }
  var postcontent = function (el) {
    el.wrap('<span class="pdb-added-content"></span>');
    el.after($('<span />', {
      html : el.data('after'),
      class : 'pdb-postcontent'
    }));
  }
  $.fn.PDb_email_obfuscate = function () {
    var address, link,
            el = this;
    try {
      address = jQuery.parseJSON(el.attr('data-email-values'));
    } catch (e) {
      return;
    }
    link = ''.concat(address.name, '@', address.domain);
    el.attr('href', 'mailto:' + link).html(link).addClass('obfuscated');
  }
  return {
    init : function () {
      // prevent double submissions
      var pdbform = $('input.pdb-submit').closest("form");
      pdbform.submit(submitOnce);
      // test for cookies, then set a page class if not available
      if (!navigator.cookieEnabled) {
        $('html').addClass('cookies-disabled');
      }
      // place email obfuscation
      $('a.obfuscate[data-email-values]').each(function () {
        $(this).PDb_email_obfuscate();
      });
      $('[data-before]').each(function () {
        precontent($(this));
      });
      $('[data-after]').each(function () {
        postcontent($(this));
      });
      var errormsg = $('.pdb-scroll-to-error .pdb-error').filter(':visible');
      if (errormsg.length) {
        $("body,html").animate(
                {
                  scrollTop : errormsg.offset().top - 50
                },
                300 //speed
                );
      }
    }
  }
}(jQuery));
jQuery(function () {
  PDbShortcodes.init();
});