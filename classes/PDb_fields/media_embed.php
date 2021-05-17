<?php

/**
 * defines a field that displays a media embed.
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.4
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class media_embed extends core {

  /**
   * @var string name of the form element
   */
  const element_name = 'media-embed';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'Media Embed', 'name of a field type that shows embedded media', 'participants-database' ) );
    
    $this->is_dynamic_field();
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    if ( strpos( $this->field->module(), 'list' ) !== false ) {
      return $this->field->value();
    }
    
    return implode( PHP_EOL, $this->media_embed_html() );
  }
  
  /**
   * provides the HTML lines for the media embed
   * 
   * @return array
   */
  private function media_embed_html()
  {
    $html = array();
    
    $media_url = $this->extract_url( $this->field->value );
    
    $display = '';
    
    if ( ! empty( $media_url ) ) {
      
      $oembed = new \WP_oEmbed();
      $display = $oembed->get_html( $media_url );
    }
    
    if ( $display === false ) {
      
      $display = $this->fallback_content( $media_url );
    }

    $html[] = '<div class="pdb-media-container ' . $this->field->name . '-media ">';
    $html[] = $display;
    $html[] = '</div>';
   
    return $html;
  }
  
  /**
   * supplies a substitute display if the embed fails
   * 
   * @param string $url
   * @return string HTML
   */
  private function fallback_content( $url )
  {
    if ( preg_match( '/\.(' . implode( '|', self::valid_img_src_types() ) . ')$/', $url ) ) {
      return sprintf( '<img src="%s" />', $url );
    }
    
    return sprintf( '<a href="%1$s" rel="external nofollow" target="_blank" >%1$s</a>', esc_attr( $url ) );
  }

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param \PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {  
    $field->form_element = 'text-line';
    
    parent::form_element_build($field);
  }

  /**
   * displays the log in a write context
   * 
   * @param object $field
   * @return string
   */
  public function form_element_html()
  {
    $html = $this->media_embed_html();
    
    if (! $this->field->is_read_only() ) {
      $parameters = array(
          'name' => $this->field->name(),
          'type' => 'text-line',
          'value' => $this->field->value(),
      );
      $html[] = \PDb_FormElement::get_element($parameters);
    }
    
    return implode( PHP_EOL, $html );
  }

  /**
   *  provides the field editor configuration switches array
   * 
   * @param array $switches
   * @return array
   */
  public function editor_config( $switches )
  {
    return array(
        'readonly' => true,
        'default' => false,
        'persistent' => false,
        'csv' => false,
        'sortable' => false,
    );
  }

  /**
   * provides the form element's mysql datatype
   * 
   * @return string
   */
  protected function element_datatype()
  {
    return 'text';
  }
  
  /**
   * supplies the value for testing if the element has content
   * 
   * @param \PDb_Field_Item $field the current field
   * @return mixed the value to test
   */
  protected function has_content_test( $field ) {
    return $this->extract_url( $field->value );
  }
  
  /**
   * extracts the URL from the content
   * 
   * this is to clean up a messy content string so that all that remains is the URL
   * 
   * @param string $content
   * @return string the cleaned-up URL
   */
  private function extract_url( $content )
  {
    $output = '';
    
    foreach( explode( ' ', $content ) as $part ) {
      if ( empty( $part ) ) {
        continue;
      }
      $check = filter_var( $part, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED );
      if ( $check !== false ) {
        $output = $check;
        break;
      }
    }
    
    return $output;
  }
  
  /**
   * supplies a list of valid img tag file extensions
   * 
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img
   * 
   * @return array
   */
  public static function valid_img_src_types()
  {
    return array('apng', 'avif', 'gif', 'jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp', 'png', 'svg', 'webp');
  }

}
