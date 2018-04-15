/**
 * Admin code for dismissing Participants Database notifications.
 * 
 * @version 1.1
 *
 */
jQuery(document).on( 'click', '.pdb_admin_notices-notice .notice-dismiss', function(e) {
    var msgid = jQuery(e.target).parent('.pdb_admin_notices-notice').data('dismiss');
    jQuery.ajax({
        url: ajaxurl,
        data: {
            action : PDb_Notices.action,
            msgid : msgid,
            nonce : PDb_Notices.nonce
        }
    })
})
