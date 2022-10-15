<?php

/*
 * manages the list page greeting functionality
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
defined( 'ABSPATH' ) || exit;

class PDb_xnau_Greeting {

  /**
   * supplies the greeting content
   * 
   * @return string content HTML
   */
  public static function greeting()
  {
    if ( Participants_Db::apply_filters( 'disable_live_notifications', true ) )
      return '';
    
    return self::greeting_content();
  }
  
  /**
   * provides the xnau greeting panel content
   * 
   * @return string HTML
   */
  private static function greeting_content()
  {
    ob_start();
    ?>
      <div class="top_notification_banner live_notification-cpt static-content"><img class="size-medium wp-image-1623 aligncenter" style="width: 100%; height: auto;" src="<?php echo plugins_url( 'ui/plain-banner-600x122.jpg', Participants_Db::$plugin_file ) ?>" title="By Tukulti65 - Own work, CC BY-SA 4.0, https://commons.wikimedia.org/w/index.php?curid=47436569" alt="photo of a vintage computer with lots of data tape machines, data reels, and a human operator"></div>
      <h3><a href="https://xnau.com/shop?utm_campaign=pdb-addons-inplugin-promo&amp;utm_medium=list_page_banner-static&amp;utm_source=pdb_plugin_user"><span style="color: #993300;">Now Available...</span></a> add-ons and UI enhancements for Participants Database at the <a href="https://xnau.com/shop?utm_campaign=pdb-addons-inplugin-promo&amp;utm_medium=list_page_banner-static&amp;utm_source=pdb_plugin_user">xnau.com store</a>!</h3>
    <?php
    return ob_get_clean();
  }

}
