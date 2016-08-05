<?php

/*
 * models a remote notification channel
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
    return $this->get_response_property( 'content' );
  }
  
  /**
   * supplies the notification content
   * 
   * @return string
   */
  public function title()
  {
    return $this->get_response_property( 'title' );
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
    $response = Participants_Db::apply_filters( 'live_notification_cache_enable', true ) ? get_transient( $this->transient_name() ) : $this->get_response();
    if ( ! $response ) {
      $this->refresh_cached_response();
      $response = get_transient( $this->transient_name() );
    }
    return json_decode( $response );
  }
  
  /**
   * provides a response body property
   * 
   * @param string $name property name to get
   * @return string
   */
  private function get_response_property( $name )
  {
    $response = $this->get_response_body();
    return is_object($response) && isset( $response->{$name} ) ? $response->{$name}->rendered : '';
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
    //error_log(__METHOD__.' getting response from ' . ($cache_is_stale ? 'xnau.com' : 'cache: ' . $this->transient_name() ) );
    return $cache_is_stale;
  }

  /**
   * gets the response
   * 
   * @return string json-encoded response; empty string if the endpoint doesn't exist
   */
  private function get_response()
  {
    $response = '';
    if ( $this->named_endpoint() ) {
      $response = wp_remote_retrieve_body( wp_remote_get( $this->endpoint() ) );
      //error_log(__METHOD__.' response: '. $response );
    }
    return $response;
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
