/*
 * Participants Database Aux Plugin settings page support
 * 
 * sets up the tab functionality on the plugin settings page
 * 
 * @version 1.0
 * 
 */
PDbAuxSettings = (function ($) {
  var
          tabsetup,
          lastTab = 'pdb-settings-page-tab',
          effect = {
            effect : 'fadeToggle',
            duration : 200
          };
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
      active : Cookies.get(lastTab),
      activate : function (event, ui) {
        Cookies.set(lastTab, ui.newTab.index(), {
          expires : 365, path : ''
        });
      }
    }
  }
  return {
    init : function () {
      var wrapped = $(".pdb-aux-settings-tabs .ui-tabs>h2, .pdb-aux-settings-tabs .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">");
      var wrapclass = $('.pdb-aux-settings-tabs').attr('class');
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
//        if ($.browser.mozilla) {
          $("form").attr("autocomplete", "off");
//        }
      }
    }
  }
}(jQuery));
jQuery(function () {
  PDbAuxSettings.init();
});