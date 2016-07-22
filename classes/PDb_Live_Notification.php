<?php

/*
 * provides a way to show xnau.com notifications on plugin admin pages
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

class PDb_Live_Notification {

  /**
   * @var string base URL for notifications
   */
  const base_url = 'https://xnau.com/wp-json/wp/v2/pages/';

  /**
   * @var string  base name of the transient
   */
  const cache_name = 'live_notification_';

  /**
   * @var string name of the content
   */
  private $name;

  /**
   * @var array defines the post IDs to use for the named content
   */
  static $post_index = array(
      'greeting' => 2047,
      'latest' => 2050
  );
  
  /**
   * @var int the base cache lifetime
   */
  private $cache_life = DAY_IN_SECONDS;

  /**
   * sets up the instance
   * 
   * @param string $name name of the content to access
   */
  private function __construct( $name )
  {
    $this->name = $name;
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
  public static function update_content_cache()
  {
    foreach ( array_keys( self::$post_index ) as $name ) {
      $notification = new self( $name );
      if ( $notification->refresh_cached_response() ) {
        /*
         * we are refreshing the cache by calling in to xnau.com for fresh content
         */
        break; // we only do this once, so break out of the foreach loop
      }
    }
  }

  /**
   * supplies the greeting content
   * 
   * @return string content HTML
   */
  public static function greeting()
  {
    $notification = new self( 'greeting' );
    return $notification->get_response_body();
  }

  /**
   * supplies the latest news content
   * 
   * @return string content HTML
   */
  public static function latest_news()
  {
    $notification = new self( 'latest' );
    return $notification->get_response_body();
  }

  /**
   * loads the named response body
   * 
   * gets the response body from a transient unless it has expired in which case 
   * it gets it from xnau.com
   * 
   * @return string the response body
   */
  private function get_response_body()
  {
    $response = get_transient( $this->transient_name() );
    if ( ! $response ) {
      $this->refresh_cached_response();
      $response = get_transient( $this->transient_name() );
    }
    return $response;
  }

  /**
   * refreshes the response cache
   * 
   * @return bool true if the cache was stale and needed to be refreshed
   */
  private function refresh_cached_response()
  {
    $cache_is_stale = false;
    $response = get_transient( $this->transient_name() );
    if ( !$response ) {
      $this->store_response( $this->get_response() );
      $cache_is_stale = true;
    }
    return $cache_is_stale;
  }

  /**
   * gets the response
   * 
   * @return string string content; empty string if the endpoint doesn't exist
   */
  private function get_response()
  {
    if ( $this->named_endpoint() ) {
      return wp_remote_retrieve_body( wp_remote_get( $this->endpoint() ) );
    }
    return '';
  }

  /**
   * sets the transient
   * 
   * @param string $response the data to store
   */
  private function store_response( $response )
  {
    if ( !empty( $response ) ) {
      set_transient( $this->transient_name(), $response, count( self::$post_index ) * $this->cache_life );
    }
  }

  /**
   * supplies the transient name
   * 
   * @return string
   */
  private function transient_name()
  {
    return self::cache_name . $this->name;
  }

  /**
   * supplies the endpoint url
   * 
   * @return string
   */
  private function endpoint()
  {
    return self::base_url . $this->named_endpoint();
  }

  /**
   * defines the endpoints for named source
   * 
   * these correspond to the post ID of the named source
   * 
   * @return string the endpoint
   */
  private function named_endpoint()
  {
    return isset( $this->post_index[$this->name] ) ? $this->post_index[$this->name] : '';
  }

}
