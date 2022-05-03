/**
 * js for handling general list management functions
 * 
 * @author Roland Barker, xnau webdesign
 * @version 1.2
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
    var delete_control = el.closest('.list-controls').find('.file-delete-preference-selector');
    var hide_all = function (){
        edit_control.hide(speed);
        delete_control.hide(speed);
      }
    switch(el.val()){
      case mass_editL10n.edit_action:
        delete_control.hide(speed);
        set_mass_edit_input(el);
        edit_control.show(speed);
        break;
      case 'delete':
        delete_control.show(speed);
        edit_control.hide(speed);
        break;
      default:
        hide_all();
    }
  }
  // provides the field-specific input for the mass edit control
  var set_mass_edit_input = function (e) {
    var el = e.target ? $(e.target) : $(e);
    var control = el.closest('.list-controls');
    var input = control.find('.mass-edit-input');
    input.find('.field-input-label, .field-input').fadeTo(speed,0,function(){
      input.addClass('changeout');
    });
    input.append(spinner);
    var data = {action: mass_editL10n.action};
    var editField = control.find('[name="' + mass_editL10n.selector + '"]').val();
    data[mass_editL10n.selector] = editField;
    data[editField] = $('.mass-edit-input [name='+editField+']').val(); 
    $.post(ajaxurl,
            data,
            function (response) {
              input.html(response.input);
              input.find('.field-input-label, .field-input').fadeTo(speed,1);
              input.removeClass('changeout');
              PDbOtherSelect.init();
              if (PDbDatepicker) PDbDatepicker.init();
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
          performTask();
        }
      }, {
        icon: 'dashicons dashicons-no-alt',
        click: function () {
          checkState = true;
          $(this).dialog("close").html('');
        }
      }]
  });
  var performTask = function () {
    if ( task_selector.val() === 'export' ) {
      var exportform = $('form.csv-export');
      var listformdata = new FormData(listform[0]);
      if ( exportform.find('[name=export_selection]').length ) {
        exportform.find('[name=export_selection]').val(listformdata.getAll('pid[]').toString());
      } else {
        exportform.prepend($('<input>',{
          type:'hidden',
          name:'export_selection',
          value:listformdata.getAll('pid[]').toString()
        }));
      }
      exportform.submit();
      confirmDialog.dialog("close").html('');
    } else {
      listform.submit();
      confirmDialog.dialog("destroy");
    }
  }
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

      spinner = mass_editL10n.spinner;
      task_selector.on('change', taskSelect);
      taskSelect(task_selector);
      $('[name="' + mass_editL10n.selector + '"]').on('change',set_mass_edit_input);

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
