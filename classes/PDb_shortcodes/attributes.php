<?php

/**
 * provides interactions with the plugin shortcode attributes
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_shortcodes;

defined( 'ABSPATH' ) || exit;

class attributes {
  
  /**
   * @var string name of the shortcode attributes transient
   */
  const attribute_store = 'pdb-shortcode_attributes';
  
  /**
   * initializes the class filters
   */
  public function __construct()
  {
    $this->setup_filters();
  }
  
  /**
   * sets up the filters
   */
  private function setup_filters()
  {
    foreach( \Participants_Db::plugin_shortcode_list() as $tag ) {
      add_filter( 'pdb-shortcode_call_' . $tag, function ( $atts ) use ( $tag ) {
        $this->stash_attributes( $atts, $tag );
        return $atts;
      } );
    }
  }
  
  /**
   * registers the attributes for the shortcode
   * 
   * @param array $shortcode_atts
   * @param string $tag the shortcode tag
   * @return array
   */
  private function stash_attributes( $shortcode_atts, $tag )
  {
    $attributes = self::get_attributes();
    
    if ( ! $attributes ) {
      $attributes = array();
    }
    
    $attributes[ $tag ] = $shortcode_atts;
    
    $attributes['last'] = array_merge( $shortcode_atts, array( 'tag' => $tag ) );
    
    set_transient( self::attribute_store, $attributes );
    
    return $shortcode_atts;
  }
  
  /**
   * provides the attributes from the last called shortcode
   * 
   * @return array
   */
  public static function last_attributes()
  {
    return self::attribute_set('last');
  }
  
  /**
   * provides the shortcode attribute values
   * 
   * @param string $set the tag or name of the attribute set to get
   * @return array
   */
  private static function attribute_set( $set )
  {
    $attributes = self::get_attributes();
    
    if ( $attributes && isset( $attributes[$set] ) ) {
      return $attributes[$set];
    }
    
    return array();
  }
  
  /**
   * provides the stored attribute array
   * 
   * @return array
   */
  private static function get_attributes()
  {
    return get_transient( self::attribute_store );
  }
  
}
