<?php

/**
 * defines a field that shows the result of a simple calculation
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

class numeric_calc extends calculated_field {
  
  use calculations;

  /**
   * @var string name of the form element
   */
  const element_name = 'numeric-calc';
  
  /**
   * @var string the calculation tag
   */
  const calc_tag = 'pdb_numeric_calc_result';
  
  /**
   * @var array of calculation steps
   */
  private $calc_list;
  
  /**
   * provides the field's title
   * 
   * @return string
   */
  protected function field_title()
  {
    return _x( 'Numeric Calculation', 'name of a field type that shows the result of a calculation', 'participants-database' );
  }
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  protected function replaced_string( $data )
  {
    $replacement_data = $this->replacement_data( $data );
    
    if ( ! $this->check_data( $replacement_data ) ) {
      return false;
    }
    
    return \PDb_Tag_Template::replace_text( $this->prepped_template(), $replacement_data );
  }
  
  /**
   * extracts the calculation part of the template
   * 
   * @return string
   */
  private function calc_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$2', $this->field->default_value() );
  }
  
  /**
   * replaces the calculation part of the template with the calculation tag
   * 
   * @return string
   */
  private function prepped_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$1['. self::calc_tag . ']$3', $this->field->default_value() );
  }
  
  /**
   * provides the replacement data
   * 
   * @param array|bool $post the provided data
   * @return array as $tagname => $value
   */
  protected function replacement_data( $post )
  {
    $source_data = $post ? $post : array();
    $replacement_data = array();
    
    // iterate through the fields named in the template
    foreach( $this->template_field_list() as $fieldname ) {
      
      $template_field = new \PDb_Field_Item( array('name' => $fieldname, 'module' => 'list' ), $this->field->record_id );
      
      if ( $template_field->form_element() === $this->name ) {
        
        /* if we are using the value from another string combine field, get the 
         * value directly from the db to avoid recursion
         */
        $replacement_data[$fieldname] = $this->field_db_value( $template_field->name() );
        $replacement_data['value:'.$fieldname] = $source_data[$fieldname];
        
      } else {
      
        if ( isset( $post[$template_field->name()] ) && ! \PDb_FormElement::is_empty( $post[$template_field->name()] ) ) {
          // get the value from the provided data
          $template_field->set_value( $post[$template_field->name()] );
        }

        $replacement_data[$fieldname] = $template_field->has_content() ? $template_field->value : '';
      }
    }
    
    $this->calculate_value( $replacement_data );
    
    $replacement_data[ self::calc_tag ] = $this->result;
    
    return $replacement_data;
  }
  
}
