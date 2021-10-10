/**
 * js for handling general list management functions
 * 
 * @author Roland Barker, xnau webdesign
 * @version 0.8
 */
var PDbListAdmin = (function ($) {
  "use strict";
  var checkState = false;
  var listform = $('#list_form');
  var count_element = $('#select_count');
  var apply_button = $('#apply_button').prop('disabled', true).addClass('unarmed');
  var checkall = $('#checkall');
  var submitElement = $('<input type="hidden" name="submit-button" />');
  var task_selector = $('#pdb-with_selected-1');
  var speed = 300;
  var spinner;
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
  // handle changing the with selected task
  var taskSelect = function (e) {
    var el = e.target ? $(e.target) : $(e);
    var edit_control = el.closest('.list-controls').find('.mass-edit-control');
    if (el.val() === mass_editL10n.edit_action) {
      edit_control.show(speed);
    } else {
      edit_control.hide(speed);
    }
  }
  // provides the field-specific input for the mass edit control
  var set_input = function () {
    var el = $(this).closest('.list-controls');
    var input = el.find('.mass-edit-input');
    input.find('.field-input-label, .field-input').fadeTo(speed,0,function(){input.addClass('changeout');});
    input.append(spinner);
    var field = el.find('[name="' + mass_editL10n.selector + '"]').val();
    var data = {action: mass_editL10n.action};
    data[mass_editL10n.selector] = field;
    $.post(ajaxurl,
            data,
            function (response) {
              input.html(response.input);
              input.find('.field-input-label, .field-input').fadeTo(speed,1);
              input.removeClass('changeout');
            }
    );
  }
  var confirmDialog = $('<div/>').dialog({
    dialogClass: 'confirmation-dialog participants-database-confirm',
    modal: true,
    zIndex: 10000,
    autoOpen: false,
    width: 'auto',
    resizable: false,
    buttons: [{
        icon: "dashicons dashicons-yes",
        click: function () {
          listform.prepend(submitElement.clone().val(list_adminL10n.apply));
          armDeleteButton(true);
          checkState = false;
          $(this).dialog("destroy");
          listform.submit();
        }
      }, {
        icon: 'dashicons dashicons-no-alt',
        click: function () {
          checkState = true;
          $(this).dialog("close").html('');
        }
      }]
  });
  return {
    init: function () {
      apply_button.on('click', function (e) {
        e.preventDefault();
        var send_count = parseInt(count_element.val(), 10);
        var sense = (send_count > 1) ? 'plural' : 'singular';
        var action = $('[name=with_selected]').val();
        var overlimit = !list_adminL10n.unlimited_actions.includes(action) && send_count > parseInt(list_adminL10n.send_limit);
        var limit_message = overlimit ? $('<h4 class="dashicons-before dashicons-warning"/>').append(list_adminL10n.apply_confirm.recipient_count_exceeds_limit.replace('{limit}', list_adminL10n.send_limit)) : '';
        confirmDialog.append($('<h3/>').append(list_adminL10n.apply_confirm[action][sense])).append(limit_message).dialog('open').find('a').blur();
      });

      task_selector.on('change', taskSelect);
      taskSelect(task_selector);
      $('[name="' + mass_editL10n.selector + '"]').on('change',set_input);
      spinner = mass_editL10n.spinner;

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
