<?php

/**
 * defines a field that shows the result of a simple calculation
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class numeric_calc extends calculated_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'numeric-calc';
  
  /**
   * @var string the calculation tag
   */
  const calc_tag = 'pdb_numeric_calc_result';
  
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
   * replaces the calculation part of the template with the calculation tag
   * 
   * @return string
   */
  protected function prepped_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$1['. self::calc_tag . ']$3', $this->template() );
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
    $this->complete = true;
    
    // iterate through the fields named in the template
    foreach( $this->template_field_list() as $fieldname ) {
      
      $template_field = $this->field_object( $fieldname, $this->field->record_id(), $post );
      $template_field->set_module('list');
      
      if ( $template_field->form_element() === $this->name ) {
        
        $replacement_data[$fieldname] = isset( $source_data[$fieldname] ) ? $source_data[$fieldname] : $this->field_db_value( $template_field->name() );
        
      } else {
      
        if ( isset( $source_data[$template_field->name()] ) && ! \PDb_FormElement::is_empty( $source_data[$template_field->name()] ) ) {
          
          $value = $source_data[$template_field->name()];
          
          if ( in_array( $template_field->form_element(), array( 'date','timestamp' ) ) ) {
            $value = \PDb_Date_Parse::timestamp($value);
          }
          
          // get the value from the provided data
          $template_field->set_value( $value );
        }
        
        $field_value = $template_field->has_content() ? $template_field->value: $this->template_field_default( $template_field );
        
        if ( (string) $field_value === '' ) {
          $this->complete = false;
        }

        $replacement_data[$fieldname] = $field_value;
      }
    }
    
    // calculate the value only if the value set is complete
    if ( $this->complete ) {
      $this->calculate_value( $this->filter_data( $replacement_data ) );
    } else {
      $this->result = '';
    }
    
    $replacement_data[ self::calc_tag ] = $this->result;
    
    return \Participants_Db::apply_filters( $this->name . '_replacement_data', $replacement_data, $this->field );
  }
  
  /**
   * provides the default value for a template field
   * 
   * @param \PDb_Field_Item $template_field
   * @return string default value
   */
  private function template_field_default( $template_field )
  {
    // all numeric fields except date field should default to 0
    $numeric_field = $template_field->is_numeric() && $template_field->form_element() !== 'date';
    
    $default_value = $template_field->default_value() === '' ? ( $numeric_field ? 0 : '' ) : $template_field->default_value();
    
    return $default_value;
  }

  /**
   * provides the form element's mysql datatype
   * 
   * @return string
   */
  protected function element_datatype()
  {
    return 'DOUBLE';
  }
  
  /**
   * tells if the current field stores a numeric value
   * 
   * @return bool
   */
  protected function is_numeric_field()
  {
    return true;
  }
  
}
