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
 * @depends    PDB_Live_Notification_Handler
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
   * sets up the instance
   * 
   * @param string $name name of the content to access
   */
  public function __construct( $name )
  {
    $this->name = $name;
  }
  
  /**
   * supplies the notification content
   * 
   * @return string
   */
  public function content()
  {
    return $this->get_response_body()->content->rendered;
  }
  
  /**
   * supplies the notification content
   * 
   * @return string
   */
  public function title()
  {
    return $this->get_response_body()->title->rendered;
  }

  /**
   * loads the named response body
   * 
   * gets the response body from a transient unless it has expired in which case 
   * it gets it from xnau.com
   * 
   * @return string the response body
   */
  public function get_response_body()
  {
    $response = get_transient( $this->transient_name() );
    if ( ! $response ) {
      $this->refresh_cached_response();
      $response = get_transient( $this->transient_name() );
    }
    return json_decode( $response );
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
      set_transient( $this->transient_name(), $response, PDb_Live_Notification_Handler::cache_lifetime() );
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
    return PDb_Live_Notification_Handler::content_id( $this->name );
  }

}
