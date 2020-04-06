/*
 * Participants Database Plugin
 * 
 * version: 1.8
 * 
 * xnau webdesign xnau.com
 * 
 * handles AJAX list filtering, paging and sorting
 */
PDbListFilter = (function ($) {
  "use strict";
  var isError = false;
  var errormsg = $('.pdb-searchform .pdb-error');
  var filterform = $('.sort_filter_form[data-ref="update"]');
  var remoteform = $('.sort_filter_form[data-ref="remote"]');
  var submission = {
      filterNonce : PDb_ajax.filterNonce,
      postID : PDb_ajax.postID
  },
  submit_search = function (event, remote) {
    remote = remote || false;
    if (event.preventDefault) {
      event.preventDefault();
    } else {
      event.returnValue = false;
    }
    var list_instance = $(event.target).closest('div[id^=participants-list-], .pdb-search');
    errormsg = list_instance.find('.pdb-searchform .pdb-error');
    filterform = list_instance.find('.sort_filter_form[data-ref="update"]');
    remoteform = list_instance.find('.sort_filter_form[data-ref="remote"]');
    clear_error_messages();
    // validate and process form here
    var $pageButton = get_page_button(event.target);
    var $submitButton = $(event.target);
    var search_field_error = $submitButton.closest('.' + PDb_ajax.prefix + 'searchform').find('.search_field_error');
    var value_error = $submitButton.closest('.' + PDb_ajax.prefix + 'searchform').find('.value_error');
    submission.target_instance = submission.instance_index || ''; // clear this for lists with no search form
    submission.submit = $submitButton.data('submit');

    switch (submission.submit) {

      case 'search':
        submission.listpage = '1';
        if ($('[name^="search_field"]').PDb_checkInputs('none')) {
          search_field_error.show();
          isError = true;
        }
        if (!PDb_ajax.allow_empty_term && $('[name^="value"]').PDb_checkInputs('')) {
          value_error.show();
          isError = true;
        }
        if (isError) {
          errormsg.show();
          return;
        }
        break;

      case 'clear':
        clear_search($submitButton);
        submission.listpage = '1';
        break;

      case 'page':
        submission.action = $pageButton.closest('div').data('action');
        submission.listpage = $pageButton.data('page');
        break;

      case 'sort':
        break;

      default:
        return;
    }
    if (remote) {
      if (submission.submit !== 'clear' ) {
        if ($submitButton.closest('form').find('[name=submit_button]').length===0) {
          $submitButton.closest('form').append($('<input>',{type:'hidden',name:'submit_button',value:$submitButton.val()}));
        }
        $submitButton.closest('form').submit();
      }
      return;
    }
    $submitButton.PDb_processSubmission();
    // trigger a general-purpose event
    // this does not wait for the ajax to complete
    $('html').trigger('pdbListFilterComplete');
  };
  var get_page_button = function (target) {
    var $button = $(target);
    if ($button.is('a'))
      return $button;
    return $button.closest('a');
  };
  var submit_remote_search = function (event) {
    submit_search(event, true);
  };
  var get_page = function (event) {
    $(event.target).data('submit', 'page');
    find_instance_index($(event.target));
    submit_search(event);
  };
  var find_instance_index = function (el) {
    var classes = el.closest('.wrap.pdb-list').prop('class');
    var match = classes.match(/pdb-instance-(\d+)/);
    submission.instance_index = match[1];
  };
  var clear_error_messages = function () {
    errormsg.hide().children().hide();
    isError = false;
  };
  var clear_search = function () {
    $('select[name^="search_field"]').PDb_clearInputs('none');
    $('input[name^="value"]').PDb_clearInputs('');
    clear_error_messages();
  };
  var compatibility_fix = function () {
    // for backward compatibility
    if (typeof PDb_ajax.prefix === "undefined") {
      PDb_ajax.prefix = 'pdb-';
    }
    $('.wrap.pdb-list').PDb_idFix();
  };
  var scroll_to_top = function () {
    
    var listinstance = $("#participants-list-" + submission.instance_index); 
    
    // if the list is taller than the window, scroll after paginating
    if ( listinstance.length && listinstance.height() > $(window).innerHeight() ) {
      $('html, body').animate({
        scrollTop : listinstance.offset().top
      }, 500);
    }
  }
  var add_value_to_submission = function (el, submission) {
    var value = encodeURI(el.val());
    var fieldname = el.attr('name');
    var multiple = fieldname.match(/\[\]$/);
    fieldname = fieldname.replace('[]', ''); // now we can remove the brackets
    if (multiple && typeof submission[fieldname] === 'string') {
      submission[fieldname] = [submission[fieldname]];
    }
    if (typeof submission[fieldname] === 'object') {
      submission[fieldname][submission[fieldname].length] = value;
    } else {
      submission[fieldname] = value;
    }
  };
  var post_submission = function (button) {
    var target_instance = $('.pdb-list.pdb-instance-' + submission.instance_index);
    var container = target_instance.length ? target_instance : $('.pdb-list').first();
    var pagination = container.find('.pdb-pagination');
    var buttonParent = button.closest('fieldset, ul, div');
    var spinner = $(PDb_ajax.loading_indicator).clone();
    $.ajax({
      type : "POST",
      url : PDb_ajax.ajaxurl,
      data : submission,
      beforeSend : function () {
        pagination.find('a').prop('disabled',true);
        buttonParent.after(spinner);
      },
      success : function (html, status) {
        if (/^failed/.test(html)) {
          // if the call fails, submit synchronously to reset form
          switch (submission.submit) {
            case 'page':
              var parser = document.createElement('a');
              parser.href = window.location.href;
              window.location.href = parser.protocol + '//' + parser.hostname + button.attr('href');
              break;
            default:
              filterform.append('<input type="hidden" name="submit_button" value="' + submission.submit + '" /> ').submit();
          }
        }
        var newContent = $(html);
        var replacePagination = newContent.find('.pdb-pagination');
        var replaceContent = newContent.find('.list-container').length ? newContent.find('.list-container') : newContent;
        newContent.PDb_idFix();
        replaceContent.find('a.obfuscate[data-email-values]').each(function () {
          $(this).PDb_email_obfuscate();
        });
        container.find('.list-container').replaceWith(replaceContent);
        pagination.remove();
        if (replacePagination.length) {
          replacePagination.each(function () {
            var builtContent = container.find('.list-container + .pdb-pagination').length ? container.find('.list-container + .pdb-pagination').last() : container.find('.list-container');
            builtContent.after(this);
          });
        }
        spinner.remove();
        pagination.find('a').prop('disabled',false);
        // trigger a general-purpose event
        $('html').trigger('pdbListAjaxComplete');
      },
      error : function (jqXHR, status, errorThrown) {
        console.log('Participants Database JS error status:' + status + ' error:' + errorThrown);
      }
    });
  };
  $.fn.PDb_idFix = function () {
    var el = this;
    el.find('#pdb-list').addClass('list-container').removeAttr('id');
    el.find('#sort_filter_form').addClass('sort_filter_form').removeAttr('id');
  };
  $.fn.PDb_checkInputs = function (check) {
    var el = this;
    var number = el.length;
    var count = 0;
    el.each(function () {
      if ($(this).val() === check) {
        count++;
      }
    });
    return count === number;
  };
  $.fn.PDb_clearInputs = function (value) {
    this.each(function () {
      $(this).val(value);
    });
  };
  $.fn.PDb_processSubmission = function () {
    // collect the form values and add them to the submission
    var $thisform = this.closest('form');
    if (!$thisform.length) {
      $thisform = this.closest('.pdb-list').find('.sort_filter_form');
    }
    $thisform.find('input:not(input[type="submit"],input[type="radio"]), select').each(function () {
      add_value_to_submission($(this), submission);
    });
    $thisform.find('input[type="radio"]:checked').each(function () {
      add_value_to_submission($(this), submission);
    });
    post_submission(this);
  };
  return {
    run : function () {

      compatibility_fix();

      clear_error_messages();

      filterform.on('click', '[type="submit"]', submit_search);
      remoteform.on('click', '[type="submit"]', submit_remote_search);
      $('.pdb-list').on('click', '.pdb-pagination a', get_page);
      $('html').on('pdbListAjaxComplete', scroll_to_top);
    }
  };
}(jQuery));
jQuery(function () {
  "use strict";
  PDbListFilter.run();
});
