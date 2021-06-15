<?php

/**
 * defines a placeholder field type
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class placeholder extends core {

  /**
   * @var string name of the form element
   */
  const element_name = 'placeholder';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'Placeholder', 'name of a field type that shows placeholder text', 'participants-database' ) );
    
    $this->customize_default_attribute( __( 'Display', 'participants-database' ), 'text-area' );
    
    $this->is_linkable();
    
    $this->set_colorclass('utility');
    
    $this->is_dynamic_field();
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    if ( $this->field->has_link() ) {
      $template = '<a class="%3$s-link" href="%2$s">%1$s</a>';
    } else {
      $template = '%1$s';
    }
    return sprintf( $template, $this->field->default_value, $this->field->link(), $this->field->name() );
  }

  /**
   * displays the field in a write context
   * 
   * @return string
   */
  public function form_element_html()
  {
    return '';
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
        'help_text' => false,
        'persistent' => false,
        'signup' => false,
        'validation' => false,
        'validation_message' => false,
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
    return ''; // this element does not store data
  }
  
  /**
   * supplies the value for testing if the element has content
   * 
   * @param \PDb_Field_Item $field the current field
   * @return mixed the value to test
   */
  protected function has_content_test( $field ) {
    return $field->default_value;
  }
  
}
