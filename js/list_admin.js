/**
 * js for handling general list management functions
 * 
 * @author Roland Barker, xnau webdesign
 * @version 0.7
 */
var PDbListAdmin = (function ($) {
  "use strict";
  var checkState = false;
  var listform = $('#list_form');
  var count_element = $('#select_count');
  var apply_button = $('#apply_button').prop('disabled', true).addClass('unarmed');
  var checkall = $('#checkall');
  var submitElement = $('<input type="hidden" name="submit-button" />');
  var armDeleteButton = function (state) {
    apply_button
            .attr('class', state ? apply_button.attr('class').replace('unarmed', 'armed') : apply_button.attr('class').replace('armed', 'unarmed'))
            .prop('disabled', state ? false : true);
  };
  var addSelects = function (selected) {
    var count = count_element.val();
    if (selected === true) {
      checkall.prop("checked", listform.find('.delete-check:not(:checked)').length ? false : true);
      count++;
    } else {
      checkall.prop("checked", false);
      count--;
    }
    if (count < 0) {
      count = 0;
    }
    armDeleteButton(count > 0);
    count_element.val(count);
  };
  var checkAll = function () {
    if (checkState === false) {
      checkState = true;
    } else {
      checkState = false;
      armDeleteButton(false);
    }
    listform.find('.delete-check').each(function () {
      $(this).prop('checked', checkState);
      addSelects(checkState);
    });
  };
  var confirmDialog = $('<div/>').dialog({
    dialogClass : 'confirmation-dialog participants-database-confirm',
    modal : true,
    zIndex : 10000,
    autoOpen : false,
    width : 'auto',
    resizable : false,
    buttons : [{
        icons : {
          primary : "dashicons dashicons-yes ui-icon-check"
        },
        click : function () {
          listform.prepend(submitElement.clone().val(list_adminL10n.apply));
          armDeleteButton(true);
          checkState = false;
          $(this).dialog("destroy");
          listform.submit();
        }
      }, {
        icons : {
          primary : "dashicons dashicons-no-alt ui-icon-close"
        },
        click : function () {
          checkState = true;
          $(this).dialog("close").html('');
        }
      }]
  });
  return {
    init : function () {
      apply_button.on('click', function (e) {
        e.preventDefault();
        var send_count = parseInt(count_element.val(), 10);
        var sense = (send_count > 1) ? 'plural' : 'singular';
        var action = $('[name=with_selected]').val();
        var overlimit = !list_adminL10n.unlimited_actions.includes(action) && send_count > parseInt( list_adminL10n.send_limit );
        var limit_message = overlimit ? $('<h4 class="dashicons-before dashicons-warning"/>').append(list_adminL10n.apply_confirm.recipient_count_exceeds_limit.replace('{limit}',list_adminL10n.send_limit)) : '';
        confirmDialog.append($('<h3/>').append(list_adminL10n.apply_confirm[action][sense])).append(limit_message).dialog('open').find('a').blur();
      });
      checkall.click(checkAll);
      $('.delete-check').on('click', function () {
        addSelects($(this).prop('checked'));
      });
      $('#list_filter_count').change($.debounce(500, function () {
        $(this).closest('form').submit();
      }));
    }
  };
}(jQuery));
jQuery(function () {
  "use strict";
  PDbListAdmin.init();
});
