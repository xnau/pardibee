<?php

/**
 * defines a field that shows the result of a shortcode
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class shortcode extends custom_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'shortcode';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'Shortcode', 'name of a field type that shows a shortcode', 'participants-database' ) );
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    $output[] = $this->shortcode_content();
    
    if ( $this->field->get_attribute( 'show_value' )  ) {
      $output[] = '<span class="field-value">' . $this->field->value() . '</span>';
    }
    
    return implode( PHP_EOL, $output );
  }
  

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param \PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {
    parent::form_element_build( $field );
  }

  /**
   * displays the field in a write context
   * 
   * @return string
   */
  public function form_element_html()
  {
    $output = array( '<div class="shortcode-field-wrap" >' );

    $parameters = array(
        'type' => 'text-line',
        'value' => $this->field_value(),
        'name' => $this->field->name(),
    );

    $output[] = \PDb_FormElement::get_element( $parameters );

    $output[] = '</div>';
    
    
    if ( $this->field->get_attribute( 'preview' )  ) {
      $output[] = '<div class="shortcode-field-preview">' . $this->shortcode_content() . '</div>';
    }

    return implode( PHP_EOL, $output );
  }

  /**
   * provides the shortcode content
   * 
   * @return string
   */
  private function shortcode_content()
  {
    $raw_shortcode = $this->field->default_value();
    $has_placeholder = stripos( $raw_shortcode, '%s' ) !== false;

    $shortcode = $has_placeholder ? sprintf( $raw_shortcode, $this->field_value() ) : $raw_shortcode;

    return do_shortcode( $shortcode );
  }
  
  /**
   * provides the field's value
   * 
   * @return string
   */
  private function field_value()
  {
    $record = \Participants_Db::get_participant( $this->field->record_id );
    
    return $record[ $this->field->name() ];
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
   *  provides the field editor configuration switches array
   * 
   * @param array $switches
   * @return array
   */
  public function editor_config( $switches )
  {
    return array(
        'readonly' => true,
        'help_text' => true,
        'persistent' => false,
        'signup' => true,
        'validation' => true,
        'validation_message' => true,
        'csv' => true,
        'sortable' => false,
    );
  }
}
