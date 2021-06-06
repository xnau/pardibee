<?php

/**
 * defines a utility field type
 * 
 * field that does not store a value to the database
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

abstract class utility extends core {

  /**
   * sets up the field type
   * 
   * @param string $name of the field type
   * @param string title of the field type
   * 
   */
  public function __construct( $name, $title )
  {
    parent::__construct( $name, $title );
    
    $this->customize_default_attribute( __( 'Display', 'participants-database' ), 'text-area' );
    
    $this->is_linkable();
    
    $this->set_colorclass('utility');
    
    $this->is_dynamic_field();
    
    /*
     * remove the field type from the selector in the field editor so that fields 
     * of this type may only be created by adding a new field, and existing fields 
     * cannot be converted to this type #2556
     */
    add_filter( 'pdb-field_editor_form_element_options', function ($list) use ($name) {
      
      if ( array_key_exists( $name, $list ) ) {
        unset( $list[$name] );
      }
      
      return $list;
    });
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
        'form_element' => false,
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
   * utility fields do not have a stored value, return false
   * 
   * @param \PDb_Field_Item $field the current field
   * @return mixed the value to test
   */
  protected function has_content_test( $field ) {
    return false;
  }
  
}
