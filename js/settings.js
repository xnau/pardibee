/*
 * Participants Database settings page support
 * 
 * sets up the tab functionality on the plugin settings page
 * 
 * @version 0.6
 * 
 */
PDbSettings = (function ($) {
  var lastTab = 'pdb-settings-page-tab';
  var optIndex = 'participants-database_options';
  var effect = {
    effect: 'fadeToggle',
    duration: 200
  };
  var getCurrentTab = function () {
    var currentTab = get_tab_cookie()[optIndex]||0;
    var tabvar = 'settingstab';
    if (typeof URLSearchParams !== 'function') {
      return currentTab; // if the browser doesn't support this function, return now
    }
    var urlTab = new URLSearchParams(window.location.search).get(tabvar);
    if (urlTab) {
      var selectedTab = $('[href="#pdb-'+urlTab+'"]');
      if (selectedTab.length) {
        currentTab = selectedTab.data('index');
      } else {
        currentTab = urlTab;
      }
    }
    return parseInt(currentTab);
  }
  var get_tab_cookie = function() {
    var cookie = tryParseJSONObject(Cookies.get(lastTab));
    return typeof cookie === "object" ? cookie : {};
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
  return {
    init: function () {
      var wrapped = $(".participants_db.wrap .ui-tabs>h2, .participants_db.wrap .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">");
      var wrapclass = $('.participants_db.wrap').attr('class');
      var tabsetup;
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
            var cookie = get_tab_cookie();
            cookie[optIndex] = ui.newTab.index();
            Cookies.set(lastTab, cookie, {
              expires: 365, path: ''
            });
          }
        }
      }
      wrapped.each(function () {
        $(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
      });
      $(".ui-tabs-panel").each(function (index) {
        var str = $(this).children("a.pdb-anchor").attr('name').replace(/\s/g, "_");
        $(this).attr("id", str.toLowerCase());
      });
      $(".participants_db.wrap").removeClass().addClass(wrapclass + " main");
      $('.participants_db .ui-tabs').tabs(tabsetup).bind('tabsselect', function (event, ui) {
        var activeclass = $(ui.tab).attr('href').replace(/^#/, '');
        $(".participants_db.wrap").removeClass().addClass(wrapclass + " " + activeclass);
      });
      $("form").attr("autocomplete", "off");
    }
  }
}(jQuery));
jQuery(function () {
  PDbSettings.init();
});