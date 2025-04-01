/**
 * 
 * @author Roland Barker <webdesign@xnau.com>
 * @version 1.2
 */
PDb_CSV_Status = (function ($) {
  var importing = true;
  var messageFrame;
  var poll_updates = function () {
    $.post(ajaxurl, {
      action: csvStatus.action,
      _wpnonce: csvStatus._wpnonce
    }, update_screen, 'json')
            .done(function () {
              if (csvStatus.importing && importing) {
                setTimeout(poll_updates, 0);
              }
            });
  };
  var update_screen = function (data, status, jqXHR)
  {
    if (jqXHR.status == 200) {
      if (importing){
        $('#message .import-tally-report').remove();
        if (messageFrame.find('#realtime').length === 0)
        {
          messageFrame.append($('<p id="realtime"></p><p class="progressbar"><progress id="realtime-progress" max="'+data.length+'" value="0" >0%</progress></p>'));
        }
        if (data.progress){
          $('#realtime-progress').val(data.progress).text(data.progress+"%");
          messageFrame.find('#realtime').html(data.html);
          if(data.progress===data.length){
            display_complete();
          }
        }
        importing = data.length > data.progress;
        if(!importing){
          // set one last time here
          setTimeout(poll_updates, 0);
        }
      }
    }
  };
  var display_complete = function(){
    $('#realtime-progress').addClass('complete');
    $('#realtime .import-tally-report').prepend($('<span class="dashicons dashicons-flag"></span>'));
  };
  return {
    init: function () {
      messageFrame = $('#message');
      poll_updates();
    },
  }
}(jQuery));
jQuery(function () {
  PDb_CSV_Status.init();
});