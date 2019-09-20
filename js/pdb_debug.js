jQuery(document).ready(function ($) {
  var frame = $(".pdb-log-display-frame");
  var pdb_scroll_down = function () {
    frame.animate({
      scrollTop : frame.prop("scrollHeight")
    }, 1000);
  }
  $(document).on('click', '#pdb-debug-refresh button', function (e) {
    var el = $(e.target);
    var spinner = $(PDb_Debug.spinner);
    $.ajax({
      url : ajaxurl,
      type : 'post',
      data : {
        action : PDb_Debug.action,
        command : el.data('action'),
        _wpnonce : $('#_wpnonce').val()
      },
      beforeSend : function () {
        $('.pdb-log-display').html(spinner);
      },
      success : function (response) {
        if ( response.indexOf('nonce failed') > -1 ) {
          $('.pdb-log-display').html('reloading…');
          window.location.href = $('#pdb-debug-refresh').attr('action');
        } else {
          $('.pdb-log-display').html(response);
          pdb_scroll_down();
        }
      }
    });
    return false;
  });
  pdb_scroll_down();
});