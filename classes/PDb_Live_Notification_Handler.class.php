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
   * any new named content pieces can be added here, also if the post ID of a piece 
   * changes, it must be changed here
   */
  static $post_index = array(
      'greeting' => 2047,
      'latest' => 2050
  );

  /**
   * @var array base analytics values
   */
  static $analytics_vars = array(
      'utm_campaign' => 'pdb-addons-inplugin-promo',
      'utm_medium' => '',
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
    $notification = new PDb_Live_Notification( 'latest' );
    return $notification->content();
  }

  /**
   * sets up the manager
   */
  public function __construct()
  {
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

?>
