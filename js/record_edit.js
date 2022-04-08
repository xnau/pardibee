/*
 * Participants Database Add/Edit Participant
 * 
 * sets up the unsaved changes warning functionality on the record edit page
 * @version 0.3
 * 
 */
PDbRecordEdit = (function ($) {
  var editContainer;
  var initState = 'initState';
  var setChangedFlag = function () {
    var el = $(this);
    var initValue = el.data(initState);
    var check = el.is('[type=checkbox]') ? el.is(':checked') : el.val();
    if (check !== initValue) {
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
  var setInitState = function(){
        var el = $(this);
        el.data(initState, el.val());
      };
  return {
    init : function () {
      editContainer = $('.pdb-admin-edit-participant');
      // flag the row as changed for text inputs
      editContainer.find('input, textarea').on('input', setChangedFlag).each(setInitState);
      // flag the row as changed for dropdowns, checkboxes
      editContainer.find('select, input[type=checkbox]').on('change', setChangedFlag).each(setInitState);
      // flag the row as changed for rich text editors
      editContainer.find('.rich-text').on('pdb-tinymce-change', '.mce-container', setChangedFlag).each(setInitState);
      // clear the unsaved changes pop-up
      editContainer.find('input[type=submit]').on('click', clearUnsavedChangesWarning);
    }
  }
}(jQuery));
jQuery(function () {
  PDbRecordEdit.init();
});
