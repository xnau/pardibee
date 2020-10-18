<?php

/**
 * defines a field that displays a video embed.
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class video extends custom_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'pdb_video';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'Video Embed', 'name of a field type that shows an embedded video', 'participants-database' ) );
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    return implode( PHP_EOL, $this->video_embed_html() );
  }
  
  /**
   * provides the HTML lines for the video embed
   * 
   * @return array
   */
  private function video_embed_html()
  {
    $html = array();
    if ( $this->field->form_element === $this->name ) {
      $oembed = new \WP_oEmbed();
      $html[] = '<div class="pdb-video-container ' . $this->field->name . '-video">';
      $html[] = $oembed->get_html( $this->extract_url( $this->field->value ) );
      $html[] = '</div>';
    }
   
    return $html;
  }

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param \PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {  
    $field->type = 'text-line';
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
    $html = $this->video_embed_html();
    
    $parameters = array(
        'name' => $this->field->name(),
        'type' => 'text-line',
        'value' => $this->field->value(),
    );
    $html[] = \PDb_FormElement::get_element($parameters);
    
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
        'readonly' => false,
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

}
