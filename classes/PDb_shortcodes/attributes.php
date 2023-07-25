<?php

/**
 * provides interactions with the plugin shortcode attributes
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.6
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_shortcodes;

defined( 'ABSPATH' ) || exit;

class attributes {
  
  /**
   * @var array the collected shortcode attributes
   */
  private $shortcode_attributes = [];
  
  /**
   * @var \PDb_shortcodes\attributes holds the class instance
   */
  private static $instance;
  
  /**
   * initializes the class filters
   */
  public function __construct()
  {
    $this->get_page_content_atts();
    
    if ( $this->list_sort_headers_enabled() )
    {
      new sort_headers();
    }
    
    self::$instance = $this;
  }
  
  /**
   * gets the attributes from the loading page
   * 
   * @global \WP_Post $post
   */
  private function get_page_content_atts()
  {
    global $post;
    
    preg_match_all( '/\[(.+?)\]/', $post->post_content, $matches );
    
    foreach ( $matches[1] as $shortcode )
    {
      $atts = shortcode_parse_atts( $shortcode );
      $tag = $atts[0];
      unset( $atts[0] );
      $this->shortcode_attributes[$tag][] = $atts;
      $this->shortcode_attributes['last'] = array_merge( ['tag' => $tag ], $atts );
    }
  }
  
  /**
   * checks the list attributes for the sort headers
   * 
   * @return bool
   */
  private function list_sort_headers_enabled()
  {
    $list_atts = $this->list_attributes();
    
    return isset( $list_atts['header_sort'] ) && self::attribute_true( $list_atts['header_sort'] );
  }
  
  /**
   * tells if a boolean attribute is enabled
   * 
   * @paeam string $attrivute_value
   * @return bool true if the attribute is set
   */
  public static function attribute_true( $attribute_value )
  {
    return $attribute_value == 1 || $attribute_value === 'true';
  }
  
  /**
   * provides the attributes from the last called shortcode
   * 
   * @return array
   */
  public static function last_attributes()
  {
    $attributes = self::$instance;
    
    if ( is_object( $attributes ) )
    {
      return $attributes->attribute_set('last');
    }
  }
  
  /**
   * provides the sets of attributes for the given shortcode tag
   * 
   * @param string $tag
   * @return array of attribute arrays, one for each instance of the named shortcode on the page
   */
  public static function page_shortcode_attributes( $tag )
  {
    $attributes = self::$instance;
    
    return is_object( $attributes ) ? $attributes->attribute_set($tag) : [];
  }
  
  /**
   * provides the shortcode attribute values
   * 
   * @param string $set the tag or name of the attribute set to get
   * @return array
   */
  private function attribute_set( $set )
  {
    return isset( $this->shortcode_attributes[$set] ) ? $this->shortcode_attributes[$set] : [];
  }
  
  /**
   * gets the attributes for a list tag
   * 
   * @return array
   */
  private function list_attributes()
  {
    $list_atts = $this->attribute_set('pdb_list');
    return end($list_atts);
  }
  
}
