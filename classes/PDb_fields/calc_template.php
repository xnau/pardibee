<?php

/**
 * models the calculation template used by calculation fields
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class calc_template {
  
  /**
   * @var string the defined calc template
   */
  private $field_template;
  
  /**
   * @var array of template components
   */
  private $components;
  
  /**
   * @param \PDb_Field_Item $field
   * @param string $default_format the default format tag
   */
  public function __construct( $field, $default_format )
  {
    $this->field_template = $field->default_value();
    $this->complete_template( $default_format );
    $this->extract_components();
  }
  
  /**
   * provides the calc template
   * 
   * @return string
   */
  public function calc_template()
  {
    return $this->field_template;
  }
  
  /**
   * completes the calc template, adding the default format tag if needed
   * 
   * @param string $default_format the default format tag
   */
  private function complete_template( $default_format )
  {
    $this->field_template = $this->completed_template( $default_format );
  }
  
  /**
   * provides a list of fields named in the template
   * 
   * this is not validated so that custom value tags may be included
   * 
   * @return array of fieldnames
   */
  public function field_list()
  {
    preg_match_all('/\[([^#\?][^\]]+)\]/', $this->field_template, $matches );
    
    return $matches[1];
  }
  
  /**
   * replaces the calculation part of the template with the calculation tag
   * 
   * @param string $calc_tag the key string for the replacement tag
   * @return string
   */
  public function prepped_template( $calc_tag )
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$1['. $calc_tag . ']$3', $this->field_template );
  }
  
  /**
   * provides the display format tag
   * 
   * @param string $default_format the default format tag
   */
  public function format_tag()
  {
    return $this->components['format'];
  }

  /**
   * extracts the calculation part of the template
   * 
   * @return string
   */
  public function calc_body()
  {
    return $this->components['body'];
  }
  
  /**
   * provides the front peritext
   * 
   * @return string
   */
  public function front_text()
  {
    return $this->components['front'];
  }
  
  /**
   * provides the back peritext
   * 
   * @return string
   */
  public function back_text()
  {
    return $this->components['back'];
  }
  
  /**
   * extracts the template components
   * 
   */
  private function extract_components()
  {
    $this->components = array( 'front' => '', 'body' => '', 'format' => '', 'back' => '' );
    
    if ( preg_match( '/^(?<front>.*?)(?<body>\[.+=(?<format>\[.+\]))(?<back>.*)$/', $this->field_template, $matches ) === 1 ) {
    
      $this->components = $matches;
    } else {
      
      \Participants_Db::debug_log(' calculation template format not recognized for template: '.$this->field_template );
    }
  }
  
  /**
   * adds the format tag to the calculation template if it is missing
   * 
   * @param string $default_format the default format tag
   * @return string calculation format
   */
  private function completed_template( $default_format )
  {
    $template = $this->field_template;
    
    if ( ! $this->has_format_tag() ) {
      $template .= $default_format;
    }
    
    return $template;
  }
  
  /**
   * tells if the template has a format tag
   * 
   * @return bool true if a format tag is found in the calc template
   */
  private function has_format_tag()
  {
    return preg_match( '/=$/', $this->field_template ) === 0;
  }
}
