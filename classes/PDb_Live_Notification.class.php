<?php

/*
 * models a remote notification channel
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    PDB_Live_Notification_Handler
 */

class PDb_Live_Notification {

  /**
   * @var string base URL for notifications
   */
  const base_url = 'https://xnau.com/wp-json/wp/v2/live_notification/';

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
    /**
     * @version 1.7.0.6
     * fix assumed availability of $this in the closure #1321
     */
    $self =& $this;
    add_filter( 'pdb-live_notification_' . $this->name, function ( $content ) use ( &$self ) {
      return $self->content_filter( $content, $self->analytics_vars() );
    } );
  }

  /**
   * filters the live notification html, adding the analytics vars
   * 
   * used as a filter callback on the output
   * 
   * @param string $content the html content
   * @param array $vars the analytics query var override values
   */
  public function content_filter( $content, $vars )
  {
    return preg_replace_callback(
            '/"(https?:\/\/xnau.com\/[^"]+)"/', function ( $matches ) use ( $vars ) {
      if ( stripos( $matches[1], 'utm_campaign' ) === false ) {
        if ( stripos( $matches[1], '.jpg') !== false ) {
          // if it's the image, mark it as a load instead of a click
          $vars['utm_medium'] = $vars['utm_medium'] . '_load';
        }
        return add_query_arg( $vars, $matches[1] );
      } else {
        return $matches[1];
      }
    }, $content );
  }

  /**
   * supplies the notification content
   * 
   * @return string
   */
  public function content()
  {
    return Participants_Db::apply_filters( 'live_notification_' . $this->name, $this->get_response_property( 'content' ) );
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
   * provides a response body property
   * 
   * @param string $name property name to get
   * @return string
   */
  private function get_response_property( $name )
  {
    $response = $this->get_response_body();
    return is_object( $response ) && isset( $response->{$name} ) ? $response->{$name}->rendered : '';
  }

  /**
   * loads the named response body
   * 
   * gets the response body from a transient unless it has expired in which case 
   * it gets it from xnau.com
   * 
   * @return object the response body
   */
  public function get_response_body()
  {
    return json_decode( $this->get_response() );
  }

  /**
   * gets the response
   * 
   * @return string json-encoded response
   */
  private function get_response()
  {
    $cached = Participants_Db::apply_filters( 'live_notification_cache_enable', true ) ? get_transient( $this->transient_name() ) : false;
    if ( $cached === false && $this->remote_content_is_available() ) {
      //error_log(__METHOD__.' getting remote content');
      $response = wp_remote_retrieve_body( wp_remote_get( $this->endpoint() ) );
      $this->cache_response( $response );
    } elseif ( $cached !== false ) {
      //error_log(__METHOD__.' getting cached content');
      $response = $cached;
    } else {
      //error_log(__METHOD__.' providing blank content');
      $response = json_encode( $this->blank_response() );
    }
    return $response;
  }

  /**
   * checks the remote content for availabilty
   * 
   * also checks that the endpoint is a valid request for a page
   * 
   * @return bool true if the content is available
   */
  private function remote_content_is_available()
  {
    $response = wp_remote_head( $this->endpoint() );
    $final_endpoint = $this->named_endpoint();
    if ( is_wp_error( $response ) || empty( $final_endpoint ) ) {
      return false;
    }
    /*
     * code should be 2xx or 3xx for a valid content response
     */
    //error_log(__METHOD__.' response code: '.$response['response']['code']);
    return preg_match( '/^[23]\d{2}$/', $response['response']['code'] ) === 1;
  }

  /**
   * caches the supplied response string
   * 
   * @param string $response
   */
  private function cache_response( $response )
  {
    if ( Participants_Db::apply_filters( 'live_notification_cache_enable', true ) ) {
      $this->store_response( $response );
    }
  }

  /**
   * sets the transient
   * 
   * @param string $response the data to store
   */
  private function store_response( $response )
  {
    set_transient( $this->transient_name(), $response, PDb_Live_Notification_Handler::cache_lifetime() );
    //error_log(__METHOD__.' storing: '.$this->transient_name().' expiration: '.PDb_Live_Notification_Handler::cache_lifetime());
  }

  /**
   * supplies the transient name
   * 
   * @return string
   */
  private function transient_name()
  {
    return Participants_Db::$prefix . self::cache_name . $this->name;
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

  /**
   * provides a blank response
   * 
   * @return object
   */
  private function blank_response()
  {
    return (object) array('content' => array('rendered' => ''), 'title' => array('rendered' => ''));
  }

  /**
   * supplies the analytics vars array
   * 
   * sets the "medium" string to the message location
   * 
   * @return atring
   */
  private function analytics_vars()
  {
    $analytics_vars = PDb_Live_Notification_Handler::$analytics_vars;
    $analytics_vars['utm_medium'] = PDb_Live_Notification_Handler::$analytics_vars['utm_medium'][$this->name];
    return $analytics_vars;
  }

}
