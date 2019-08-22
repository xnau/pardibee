<?php

/*
 * manages the list notification functionality
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Live_Notification_Handler {

  /**
   * @var array defines the post IDs to use for the named content
   * 
   * @version 1.7.0.5 started using live_notification post types
   * 
   * any new named content pieces can be added here, also if the post ID of a piece 
   * changes, it must be changed here
   */
  static $post_index = array(
      'greeting' => 2199, //  formerly page id 2047
      'latest' => 2200, // 2050
  );

  /**
   * @var array base analytics values
   */
  static $analytics_vars = array(
      'utm_campaign' => 'pdb-addons-inplugin-promo',
      'utm_medium' => array(
          'latest' => 'settings_page_banner',
          'greeting' => 'list_page_banner',
      ),
      'utm_source' => 'pdb_plugin_user',
  );

  /**
   * @var int the base cache lifetime
   */
  static $cache_life = DAY_IN_SECONDS;

  /**
   * supplies the greeting content
   * 
   * @return string content HTML
   */
  public static function greeting()
  {
    if ( Participants_Db::apply_filters( 'disable_live_notifications', true ) )
      return;
    
    $notification = new PDb_Live_Notification( 'greeting' );
    return $notification->content();
  }

  /**
   * supplies the latest news content
   * 
   * @return string content HTML
   */
  public static function latest_news()
  {
    if ( Participants_Db::apply_filters( 'disable_live_notifications', true ) )
      return;
    
    $notification = new PDb_Live_Notification( 'latest' );
    return $notification->content();
  }

  /**
   * provides the cache lifetime value
   * 
   * @return int
   */
  public static function cache_lifetime()
  {
    return count( self::$post_index ) * self::$cache_life;
  }

  /**
   * defines the endpoints for named source
   * 
   * these correspond to the post ID of the named source
   * 
   * @param string $$name name of the notification content
   * @return string the endpoint
   */
  public static function content_id( $name )
  {
    return isset( self::$post_index[$name] ) ? self::$post_index[$name] : '';
  }

}
