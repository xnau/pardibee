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
  const cache = 'live_notification_';
  /**
   * @var array holds the latest response
   */
  private $response;
  /**
   * provides the named content
   * 
   * @param string $name name of the content source to get
   * @return string HTML
   */
  /**
   * defines the endpoints for named source
   * 
   * these correspond to the post ID of the named source
   * 
   * @param string $name name of the source to get
   * @return string the endpoint
   */
  private function get_endpoint( $name )
  {
    $post_index = array(
        'greeting' => 2047,
        'latest' => 2050
    );
  }
  /**
   * loads the named response
   * 
   * gets the response from a transient unless it has expired
   * 
   * @param string  $name name of the content to load
   */
}
