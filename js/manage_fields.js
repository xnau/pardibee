/**
 * general scripts for manage database fields page
 * 
 * Participants Database plugin
 * 
 * @version 2.3
 * @author Roland Barker <webdesign@xnau.com>
 */
PDbManageFields = (function ($) {
  "use strict";
  var dialogOptions = {
    dialogClass : 'participants-database-confirm',
    autoOpen : false,
    height : 'auto',
    minHeight : '20'
  };
  var tabEffect = {
    effect : 'fadeToggle',
    duration : 100
  };
  var lastTab = 'pdb-manage-fields-tab';
  var effect_speed = 300;
  var confirmationBox = $('#confirmation-dialog');
  var deleteField = function (event) {
    event.preventDefault();
    var el = $(this);
    var row_id = el.data('thing-name').replace(/^delete_/, '');
    var parent = el.closest('.def-line');
    var name = parent.find('.title-attribute input').val();
    var thing = el.data('thing');
    var group = parent.find('td.group select').val(); // set the group ID and get the field count for the group
    var group_id = group ? group : row_id;
    var countDisplay = $('#field_count_' + group_id);
    var count = countDisplay.html();
    var not_empty_group = (/[0-9]+/.test(count) && count > 0) ? true : false; // test to see if the group we're deleting has fields in it

    if (not_empty_group && thing === 'group') {

      confirmationBox.html(PDb_L10n.must_remove.replace('{name}', name));

      // initialize the dialog action
      confirmationBox.dialog(dialogOptions, {
        buttons : {
          "Ok" : function () {
            $(this).dialog('close');
          }
        }
      });

    } else {

      confirmationBox.html(PDb_L10n.delete_confirm.replace('{name}', name).replace('{thing}', thing));

      confirmationBox.dialog(dialogOptions, {
        buttons : {
          "Ok" : function () {
            parent.css('opacity', '0.3');
            $(this).dialog('close');
            $.ajax({
              type : 'post',
              url : ajaxurl,
              data : {
                list : [row_id],
                action : PDb_L10n.action,
                task : 'delete_' + thing,
                _wpnonce : PDb_L10n._wpnonce
              },
              beforeSend : function () {
              },
              success : function (response) {
                if ( response.status === 'success' ) {
                  parent.slideUp(600, function () {
                    parent.remove();
                  });
                  countDisplay.html(count - 1);
                  $('#tab_' + row_id).fadeOut();
                } else {
                  parent.css('opacity', 'inherit');
                }      
                if (response.feedback) {
                  set_feedback(response.feedback);
                }
              }
            });// ajax
          }, // ok
          "Cancel" : function () {
            $(this).dialog('close');
          } // cancel
        } // buttons
      });// dialog
    }
    confirmationBox.dialog('open');
    return false;
  };
  var form_element_change_confirm = function (e) {
    var target = $(this);
    var warning_name = target.prop('name').replace(/\[(.+)\]/, '[datatype_warning]');
    var warning = $('[name="' + warning_name + '"]').length ? $('[name="' + warning_name + '"]') : $('<input>', {
      name : warning_name,
      type : 'hidden',
      value : 'pending'
    }).insertAfter(target);
    var confirmationBox = $('#confirmation-dialog');
    confirmationBox.html(PDb_L10n.datatype_confirm);
    // initialize the dialog action
    confirmationBox.dialog(dialogOptions, {
      buttons : [
        {
          text : PDb_L10n.datatype_confirm_button,
          class : 'confirm-button dashicons-before dashicons-yes',
          click : function () {
            warning.val('accepted');
            confirmationBox.dialog('close');
          }
        },
        {
          text : PDb_L10n.datatype_cancel_button,
          class : 'cancel-button dashicons-before dashicons-no',
          click : function () {
            warning.val('rejected');
            target.val(target.data('initState'));
            confirmationBox.dialog('close'); // Close the Confirmation Box
          }
        }
      ] // buttons
    });// dialog 
    confirmationBox.dialog('open');
    return false;
  };
  var captchaPreset = function () {
    var el = $(this);
    var row = el.closest('tr');
    if (el.val() === 'captcha') {
      row.find('td.validation select').val('captcha');
      row.find('td.readonly input[type=checkbox], td.signup input[type=checkbox]').prop('checked', true);
      row.find('td.sortable input[type=checkbox], td.CSV input[type=checkbox], td.persistent input[type=checkbox]').prop('checked', false);
    }
  };
  var saveState = function () {
    var el = $(this);
    if (el.is('[type=checkbox]')) {
      el.data('initState', el.is(':checked'));
    } else {
      el.data('initState', el.val());
    }
  };
  var setChangedFlag = function () {
    var el = $(this);
    var flag;
    var initState = el.data('initState');
    if (el.closest('.manage-field-groups').length) {
      flag = $('#status_' + el.closest('.def-fieldset').data('id'));
    } else {
      var matches = el.closest('.def-fieldset').attr('id').match(/(\d+)/);
      flag = $('#status_' + matches[1]);
    }
    var check = el.is('[type=checkbox]') ? el.is(':checked') : el.val();
    if (check !== initState) {
      flag.attr('value', 'changed');
      setUnsavedChangesFlag(1);
    } else {
      setUnsavedChangesFlag(-1);
    }
  };
  var setUnsavedChangesFlag = function (op) {
    var body = $('body');
    var unsavedChangesCount = body.data('unsavedChanges') || 0;
    if (op === 1) {
      unsavedChangesCount++;
    } else if (op === -1) {
      unsavedChangesCount--;
    }
    body.data('unsavedChanges', unsavedChangesCount);
    if (unsavedChangesCount <= 0) {
      clearUnsavedChangesWarning();
    } else {
      window.onbeforeunload = confirmOnPageExit; // set up the unsaved changes warning
    }
  };
  var clearUnsavedChangesWarning = function () {
    window.onbeforeunload = null;
  };
  var fixHelper = function (e, ui) {
    ui.children().each(function () {
      jQuery(this).width(jQuery(this).width());
    });
    return ui;
  };
  var enableNew = function () {
    $(this).closest('.add-field-inputs, .add-group-inputs').find('[type=submit]').removeClass('disabled').addClass('enabled').prop('disabled', false);
  };
  var disableNew = function (el) {
    el.find('[type=submit]').removeClass('enabled').addClass('disabled').prop('disabled', true);
  };
  var serializeList = function (container) {
    var n = 0;
    var query = '';
    container.find('.def-line').each( function () {
      var el = $(this);
      var index = n + (el.data('groupid')*1000);
      if (query !== '') {
        query = query + '&';
      }
      query = query + el.data('numid') + '=' + index;
      n++;
    });
    return query;
  };
  var cancelReturn = function (event) {
    // disable autocomplete
//    if ($.browser.mozilla) {
    $(this).attr("autocomplete", "off");
//    }
    if (event.keyCode === 13)
      return false;
  };
  var getTabSettings = function () {
    if ($.versioncompare("1.9", $.ui.version) === 1) {
      return {
        fx : {
          opacity : "show",
          duration : "fast"
        },
        cookie : {
          expires : 1
        }
      };
    } else {
      return {
        hide : tabEffect,
        show : tabEffect,
        active : $.cookie(lastTab),
        activate : function (event, ui) {
          $.cookie(lastTab, ui.newTab.index(), {
            expires : 365
          });
        }
      };
    }
  };
  var confirmOnPageExit = function (e) {
    e = e || window.event;
    var message = PDb_L10n.unsaved_changes;
    // For IE6-8 and Firefox prior to version 4
    if (e) {
      e.returnValue = message;
    }
    // For Chrome, Safari, IE8+ and Opera 12+
    return message;
  };
  var open_field_editor = function () {
    switch_field_editor($(this), 'open');
  }
  var close_field_editor = function () {
    switch_field_editor($(this), 'close');
  }
  var switch_field_editor = function (el, action) {
    switch (action) {
      case 'close':
        el.closest('.def-fieldset').removeClass('editor-open').addClass('editor-closed');
        break;
      case 'open':
        el.closest('.def-fieldset').removeClass('editor-closed').addClass('editor-open');
        break;
    }
    $.post(ajaxurl, {
      action : PDb_L10n.action,
      task : 'open_close_editor',
      id : el.closest('.field-header').find('[data-id]').data('id'),
      state : action,
      _wpnonce : PDb_L10n._wpnonce,
    });
  }
  var open_close_all_field_editors = function (container, action) {
    switch (action) {
      case 'close':
        container.find('.def-fieldset').removeClass('editor-open').addClass('editor-closed');
        break;
      case 'open':
        container.find('.def-fieldset').removeClass('editor-closed').addClass('editor-open');
        break;
    }
    var list = [];
    container.find('[name*=selectable]').each(function () {
      list.push($(this).data('id'));
    });
    $.post(ajaxurl, {
      action : PDb_L10n.action,
      task : 'open_close_all',
      list : list,
      state : action,
      _wpnonce : PDb_L10n._wpnonce,
    });
  }
  var showhide_validation_message = function () {
    var message_control = $(this).closest('.attribute-control').next('.validation_message-attribute');
    switch ($(this).val()) {
      case 'no':
        message_control.hide();
        break;
      default:
        message_control.show();
    }
  }
  var is_field_selected = function (container) {
    var checked = false;
    container.find('[name*=selectable][type=checkbox]').each(function () {
      if ($(this).prop('checked')) {
        checked = true;
        return;
      }
    });
    return checked;
  }
  var handle_with_selected_action = function () {
    var fieldgroup = $(this).closest('.general_fields_control_header').next('form');
    var ws_action = $(this).closest('.general_fields_control_header').find('select.with-selected-action-select').val();
    var list = get_selected_ids(fieldgroup);
    if (list.length) {
      switch (ws_action) {
        case 'delete':
          delete_selected_fields(list, $(this).closest('.manage-fields-wrap').attr('id'));
          break;
        case 'group':
          assign_group(list, $(this).prev('select.with-selected-group-select').val());
          fieldgroup.find('.manage-fields-update').trigger('click');
          break;
        case 'add_csv':
          set_flag(list, 'csv', true);
          break;
        case 'remove_csv':
          set_flag(list, 'csv', false);
          break;
        case 'add_signup':
          set_flag(list, 'signup', true);
          break;
        case 'remove_signup':
          set_flag(list, 'signup', false);
          break;
      }
    }
    return false;
  }
  var set_flag = function (list, flag, set) {
    $.each(list, function (index, value) {
      $('#row_' + value + '_' + flag).prop('checked', set);
    });
    $.post(ajaxurl, {
      action : PDb_L10n.action,
      task : 'update_param',
      param : flag,
      setting : set,
      _wpnonce : PDb_L10n._wpnonce,
      list : list
    }, function (response) {
      if (response.feedback) {
        set_feedback(response.feedback);
      }
    }, 'json');
  }
  var assign_group = function (list, group) {
    $.each(list, function (index, id) {
      $('#row_' + id + '_group').val(group).trigger('change');
    });
  }
  var get_selected_ids = function (container) {
    var list = [];
    container.find('[name*=selectable]:checked').each(function () {
      list.push($(this).data('id'));
    });
    return list;
  }
  var delete_selected_fields = function (list, group) {

    confirmationBox.html(list.length > 1 ? PDb_L10n.delete_confirm_fields : PDb_L10n.delete_confirm_field);

    // initialize the dialog action
    confirmationBox.dialog(dialogOptions, {
      buttons : {
        "Ok" : function () { //If the user choose to click on "OK" Button

          $.each(list, function (index, value) {
            $('#db_row_' + value).css('opacity', '0.3');
          });
          $(this).dialog('close');
          $.ajax({
            type : 'post',
            url : ajaxurl,
            data : {
              list : list,
              action : PDb_L10n.action,
              task : 'delete_field',
              _wpnonce : PDb_L10n._wpnonce
            },
            success : function (response) {
              $.each(list, function (index, value) {
                $('#db_row_' + value).slideUp(600, function () {
                  $(this).remove();
                });
              });
              var countDisplay = $('#field_count_' + group);
              countDisplay.html(parseInt(countDisplay.html()) - list.length);
              $('.with-selected-control').slideUp(effect_speed);
              set_feedback(response.feedback);
            }
          });
        }, // ok
        "Cancel" : function () { //if the User Clicks the button "cancel"
          $(this).dialog('close');
        } // cancel
      } // buttons
    });// dialog
    confirmationBox.dialog('open');
  };
  var set_feedback = function (html) {
    $('#fields-tabs .ui-tabs-nav').after(html);
  };
  var sortFields = {
    helper : fixHelper,
    handle : '.dragger',
    update : function (event, ui) {
      $.post(ajaxurl, {
        action : PDb_L10n.action,
        task : 'reorder_fields',
        _wpnonce : PDb_L10n._wpnonce,
        list : serializeList($(this))
      });
    }
  };
  var sortGroups = {
    helper : fixHelper,
    handle : '.dragger',
    update : function (event, ui) {
      $.post(ajaxurl, {
        action : PDb_L10n.action,
        task : 'reorder_groups',
        _wpnonce : PDb_L10n._wpnonce,
        list : serializeList($(this))
      });
    }
  };
  return {
    init : function () {
      var tabcontrols = $("#fields-tabs");

      clearUnsavedChangesWarning();
      // set up tabs
      tabcontrols.tabs(getTabSettings());
      // save the initial state of all inputs
      tabcontrols.find('.manage-fields input[type=text], .manage-fields textarea, .manage-fields select, .manage-fields input[type=checkbox]').each(saveState);
      // flag the row as changed for text inputs
      tabcontrols.find('.attribute-control input, .attribute-control textarea').on('input', setChangedFlag);
      // flag the row as changed for dropdowns, checkboxes
      tabcontrols.find('.attribute-control select, .attribute-control input[type=checkbox]').on('change', setChangedFlag);
      // defeat return key submit behavior
      tabcontrols.on("keypress", 'form', cancelReturn);
      // set the form element change warning
      tabcontrols.on('change.confirm', 'select.column-has-values', form_element_change_confirm);
      // pre-set CAPTCHA settings
      $('.manage-fields-wrap').on('change', '.manage-fields tbody .form_element-attribute select', captchaPreset);
      // set up the delete functionality
      tabcontrols.find('.manage-fields a.delete').click(deleteField);
      // prevent empty submission
      tabcontrols.find('input.add_field').on('input', enableNew);
      // set up the field sorting
      $(".manage-field-groups").sortable(sortGroups);
      $('section[id$="_fields"]').sortable(sortFields);
      // clear the unsaved changes opo-up
      $('.manage-fields-update, .manage-groups-update').on('click', clearUnsavedChangesWarning);
      // set up the open/close field editor button
      $('.def-fieldset').on('click', '.editor-opener.field-open-icon', open_field_editor);
      $('.def-fieldset').on('click', '.editor-opener.field-close-icon', close_field_editor);
      // show/hide the validation message setting
      $('.validation-attribute select').on('change', showhide_validation_message).each(showhide_validation_message);
      // set up the manage fields global action panels
      $('.button-showhide').slideUp();
      $('button.showhide').click(function () {
        $('.button-showhide').not('#' + $(this).attr('for')).slideUp();
        $('#' + $(this).attr('for')).slideToggle('slow');
        return false;
      });
      // cancel add field
      $('button[name=add-field-cancel]').click(function (e) {
        e.preventDefault();
        $(this).closest('.button-showhide').slideUp();
        disableNew($(this).closest('.button-showhide'));
      });
      // "with selected" functionality
      $('.with-selected-control').slideUp(effect_speed);
      $('[name*=selectable][type=checkbox]').change(function () {
        var control = $(this).closest('form').prev('.general_fields_control_header').find('.with-selected-control');
        if (is_field_selected($(this).closest('form'))) {
          control.slideDown(effect_speed);
        } else {
          control.slideUp(effect_speed);
        }
      });
      $('.with-selected-group-select').hide();
      $('.with-selected-action-select').change(function () {
        $(this).next('.with-selected-group-select').hide(effect_speed);
        if ($(this).val() === 'group') {
          $(this).next('.with-selected-group-select').show(effect_speed);
        }
      });
      // field selection logic
      $('.general_fields_control_header .check-all input[type=checkbox]').click(function () {
        $(this).closest('.general_fields_control_header').next('form').find('[name*=selectable]').prop('checked', $(this).prop("checked")).trigger('change');
      });
      // open/close all
      $('.general_fields_control_header .openclose-all').click(function () {
        var icon = $(this).find('.dashicons');
        var action = 'close';
        if (icon.hasClass('field-open-icon')) {
          action = 'open';
        }
        open_close_all_field_editors($(this).closest('.manage-fields-wrap'), action);
        icon.toggleClass('field-close-icon field-open-icon');
      });
      // with selected action handler
      $('.apply-with-selected').click(handle_with_selected_action);
      // dismiss notice
      $('#fields-tabs').on('click', '.notice-dismiss', function () {
        $(this).closest('div.notice').remove();
      });

      // handle empty field options
      $('textarea.option-list')
              .on('input', function () {
                if (!this.validity.valueMissing) {
                  this.setCustomValidity('');
                }
              })
              .on('invalid', function () {
                this.setCustomValidity($(this).data('message'));
                $(this).closest('.editor-closed').find('.field-open-icon').trigger('click');
              });
    }
  };
}(jQuery));
jQuery(function () {
  "use strict";
  PDbManageFields.init();
});
