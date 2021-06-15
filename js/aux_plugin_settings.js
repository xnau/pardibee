/*
 * Participants Database Aux Plugin settings page support
 * 
 * sets up the tab functionality on the plugin settings page
 * 
 * @version 1.1
 * 
 */
PDbAuxSettings = (function ($) {
  var tabsetup;
  var wrapped;
  var wrapclass;
  var effect = {
    effect : 'fadeToggle',
    duration : 200
  };
  const lastTab = 'pdb-settings-page-tab';
  const tabvar = 'settingstab';
  var getCurrentTab = function () {
    var currentTab = isNaN(Cookies.get(lastTab)) ? 0 : Cookies.get(lastTab);
    if ( typeof URLSearchParams !== 'function' ) {
      return currentTab; // if the browser doesn't support this function, return now
    }
    var urlTab = new URLSearchParams(window.location.search).get(tabvar);
    if (urlTab) {
      currentTab = urlTab;
    }
    return parseInt(currentTab);
  }
  var setupTabConfig = function () {
    if ($.versioncompare("1.9", $.ui.version) == 1) {
      tabsetup = {
        fx : {
          opacity : "show",
          duration : "fast"
        },
        cookie : {
          expires : 1
        }
      }
    } else {
      tabsetup = {
        hide : effect,
        show : effect,
        active : getCurrentTab(),
        activate : function (event, ui) {
          Cookies.set(lastTab, ui.newTab.index(), {
            expires : 365, path : ''
          });
        }
      }
    }
  }
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
    init : function () {
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