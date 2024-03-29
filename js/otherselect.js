/**
 * js for handling dropdown/other and multiselect/other form elements
 * 
 * @author Roland Barker, xnau webdesign
 * @version 0.7
 */
var PDbOtherSelect = (function ($) {
  "use strict";
  var groupClass = '[class*="-other-control-group"]';
  var nameData = 'other-name';
  var tempName = 'temp';
  var initializing;
  var dropdown_update = function (e) {
    initializing = e.type === 'pdbinit';
    var dropdown = $(this);
    var thisGroup = dropdown.closest(groupClass);
    var thisName = thisGroup.data(nameData);
    var otherfield = thisGroup.find('.otherfield');
    cache_other_value(otherfield);
    if (dropdown.val() === 'other') {
      thisGroup.find('.otherselect').attr('name', tempName);
      otherfield.attr('name', thisName).select();
      set_saved_value(otherfield);
    } else {
      thisGroup.find('.otherselect').attr('name', thisName);
      otherfield
              .attr('name', tempName)
              .val("");
    }
  };
  var dropdown_otherfield_select = function () {
    var otherfield = $(this);
    var thisGroup = otherfield.closest(groupClass);
    var thisName = thisGroup.data(nameData);
    thisGroup.find('.otherfield').attr('name', thisName);
    thisGroup
            .find('.otherselect option:selected').removeAttr('selected')
            .end()
            .find('.otherselect option[value=other]').prop('selected', true)
            .end()
            .find('select.otherselect').attr('name', tempName);
    return true;
  };
  var checkbox_update = function (e) {
    initializing = e.type === 'pdbinit';
    otherfield_update($(this));
  };
  var checkbox_otherfield_select = function () {
    return otherfield_select($(this));
  };
  var radio_update = function (e) {
    initializing = e.type === 'pdbinit';
    otherfield_update($(this));
  };
  var radio_otherfield_select = function () {
    return otherfield_select($(this));
  };
  var set_saved_value = function (field) {
    if (field.data('fieldvalue')) {
      field.attr('value', field.data('fieldvalue')).val(field.attr('value'));
    }
  };
  var cache_other_value = function (field) {
    var othervalue = field.val();
    if (othervalue !== '') {
      field.attr( 'data-fieldvalue', othervalue );
      field.data('fieldvalue', othervalue);
    }
  };
  var otherfield_update = function (field) {
    var thisGroup = field.closest(groupClass);
    var thisName = thisGroup.data(nameData).replace("[]", '') + (field.PDb_is_checkbox() ? '[other]' : '');
    var otherfield = thisGroup.find('.otherfield');
    cache_other_value(otherfield);
    if (field.is(':checked') && field.hasClass('otherselect')) {
      set_saved_value(otherfield);
      otherfield.attr('name', thisName);
      if ( ! initializing ) {
        otherfield.focus();
      }
    } else {
      otherfield.attr('name', tempName).val("");
    }
  };
  var otherfield_select = function (field) {
    var thisGroup = field.closest(groupClass);
    var thisName = thisGroup.data(nameData);
    if (field.is(':focus')) {
      field.prop('name', thisName.replace("[]", '') + (field.PDb_is_checkbox() ? '[other]' : ''));
      thisGroup.find('.otherselect').prop('checked', true);
      field.focus();
    }
    return true;
  };
  var other_element_setup = function () {
    var el = $(this);
    var control = el.find('[name]').first();
    var name = control.attr('name'); //.replace(/\[[^\]]*\]/, '');
    var otherinput = el.find('input.otherfield');
    if ( otherinput.length ) {
      el.data(nameData, name);
      otherinput.attr('name', otherinput.attr('name').replace(name, tempName));
    }
  };
  $.fn.PDb_is_checkbox = function () {
    return this.closest('.selectother[class*="checkbox"]').length > 0;
  };

  return {
    init : function () {
      var ddOtherGroup = $('div.dropdown-other-control-group');
      var cbOtherGroup = $('div.checkbox-other-control-group');
      var rbOtherGroup = $('div.radio-other-control-group');
      $('[class*=other-control-group]').each(other_element_setup);
      
      /*
       * dropdown-other controls
       */
      ddOtherGroup.on('change pdbinit', 'select.otherselect', dropdown_update);
      ddOtherGroup.on('click', 'input.otherfield', dropdown_otherfield_select);
      ddOtherGroup.find('.otherselect').trigger('pdbinit');
      /*
       * multi-select-other controls
       */
      cbOtherGroup.on('change pdbinit', 'input.otherselect', checkbox_update);
      cbOtherGroup.on('click', 'input.otherfield', checkbox_otherfield_select);
      cbOtherGroup.find('.otherselect').trigger('pdbinit');
      /*
       * radio-other controls
       */
      rbOtherGroup.on('change pdbinit', 'input[type="radio"]', radio_update);
      rbOtherGroup.on('click', 'input.otherfield', radio_otherfield_select);
      rbOtherGroup.find('.otherselect').trigger('pdbinit');
    }
  };
}(jQuery));
jQuery(function () {
  "use strict";
  PDbOtherSelect.init();
});
