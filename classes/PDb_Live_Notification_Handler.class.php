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
   * @var string name of the cron hook
   */
  const hook = 'pdb-live_notification_hook';

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
    $this->schedule_cron();
    add_action( self::hook, array($this, 'update_content_cache') );
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

  /**
   * updates the transient
   * 
   * this is called by the WP cron on a daily basis. The cache lasts for one day 
   * for each content type and we will only refresh the cache for one type at a time. 
   * This is so that we don't try to do too much on the cron, so each day one of 
   * the notifications will be refreshed.
   *
   */
  public function update_content_cache()
  {
    foreach ( array_keys( self::$post_index ) as $name ) {
      $notification = new PDb_Live_Notification( $name );
      if ( $notification->refresh_cached_response() ) {
        /*
         * we are refreshing the cache by calling in to xnau.com for fresh content
         */
        break; // we only do this once, so break out of the foreach loop
      }
    }
  }

  /**
   * schedules the cron if it hasn't already been scheduled
   */
  private function schedule_cron()
  {
    if ( !wp_next_scheduled( self::hook ) ) {
      wp_schedule_event( time(), 'daily', self::hook );
    }
  }

  /**
   * deschedules the cron
   * 
   * fired on deactivation
   */
  public static function deschedule_cron()
  {
    $timestamp = wp_next_scheduled( self::hook );
    wp_unschedule_event( $timestamp, self::hook );
  }

}

?>
