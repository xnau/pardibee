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
   * sets up the instance
   * 
   * @param string $name name of the content to access
   */
  private function __construct( $name )
  {
    $this->name = $name;
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
   * gets the response body from a transient unless it has expired
   * 
   * the transient expires after a week, so new content will only be gotten once 
   * a week by any one user
   * 
   * @return string the JSON response
   */
  private function get_response_body()
  {
    $response = get_transient( $this->transient_name() );
    if ( !$response ) {
      $response = $this->get_reponse();
      $this->store_response($response);
    }
    return $response;
  }
  
  /**
   * gets the response
   * 
   * @return string string content; empty string if the endpoint doesn't exist
   */
  private function get_reponse()
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
    if ( ! empty( $response ) ) {
      set_transient( $this->transient_name(), $response, WEEK_IN_SECONDS );
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
    $post_index = array(
        'greeting' => 2047,
        'latest' => 2050
    );
    return isset( $post_index[$this->name] ) ? $post_index[$this->name] : false;
  }
}
