/*
 * Participants Database Aux Plugin settings page support
 * 
 * sets up the tab functionality on the plugin settings page
 * 
 * @version 1.2
 * 
 */
PDbAuxSettings = (function ($) {
  var tabsetup;
  var wrapped;
  var wrapclass;
  var effect = {
    effect: 'fadeToggle',
    duration: 200
  };
  var curpage;
  var pagetabs = {}
  const tabcookie = 'pdb-settings-page-tab';
  const tabvar = 'settingstab';
  var getCurrentTab = function () {
    return parseInt(pagetabs[curpage]);
  }
  var setupTabConfig = function () {
    curpage = $('input[name=option_page]').val();
    var cookieval = tryParseJSONObject(Cookies.get(tabcookie));
    if (typeof cookieval === 'undefined') {
      pagetabs = {};
      pagetabs[curpage] = 0;
      setCookie();
    } else {
      if (typeof cookieval !== 'object') {
        pagetabs = {};
        pagetabs[curpage] = cookieval;
        setCookie();
      }
      pagetabs = cookieval;
    }
    if ($.versioncompare("1.9", $.ui.version) == 1) {
      tabsetup = {
        fx: {
          opacity: "show",
          duration: "fast"
        },
        cookie: {
          expires: 1
        }
      }
    } else {
      tabsetup = {
        hide: effect,
        show: effect,
        active: getCurrentTab(),
        activate: function (event, ui) {
          pagetabs[curpage] = ui.newTab.index();
          setCookie();
        }
      }
    }
  }
  var setCookie = function () {
    Cookies.set(tabcookie, pagetabs, {
      expires: 365, path: ''
    });
  }
  var tryParseJSONObject = function (jsonString) {
    try {
      var o = JSON.parse(jsonString);
      if (o && typeof o === "object") {
        return o;
      }
    } catch (e) {
    }

    return jsonString;
  };
  var wrapTabs = function () {
    if (wrapped.length) {
      wrapped.each(function () {
        $(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
      });
      $(".ui-tabs-panel").each(function (index) {
        var str = $(this).find('a').attr('name').replace(/\s/g, "_");
        $(this).attr("id", str.toLowerCase());
      });
      $(".pdb-aux-settings-tabs").removeClass().addClass(wrapclass + " main");
      $('.pdb-aux-settings-tabs .ui-tabs').tabs(tabsetup).bind('tabsselect', function (event, ui) {
        var activeclass = $(ui.tab).attr('href').replace(/^#/, '');
        $(".pdb-aux-settings-tabs").removeClass().addClass(wrapclass + " " + activeclass);
      });
      $("form").attr("autocomplete", "off");
    }
  }
  return {
    init: function () {
      wrapped = $(".pdb-aux-settings-tabs .ui-tabs>h2, .pdb-aux-settings-tabs .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">");
      wrapclass = $('.pdb-aux-settings-tabs').attr('class');
      setupTabConfig();
      wrapTabs();
    }
  }
}(jQuery));
jQuery(function () {
  PDbAuxSettings.init();
});