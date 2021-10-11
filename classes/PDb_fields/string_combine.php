<?php

/**
 * defines a field that displays a formatted string from record values
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class string_combine extends templated_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'string-combine';
  
  /**
   * provides the field's title
   * 
   * @return string
   */
  protected function field_title()
  {
    return _x( 'String Combine', 'name of a field type that shows a combined string', 'participants-database' );
  }
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  protected function replaced_string( $data )
  {
    // supply an empty array if the data is not provided in the argument
    $data = is_array( $data ) ? $data : array();
    
    $replacement_data = $this->replacement_data( $data );
    
    return \PDb_Tag_Template::replace_text( $this->template(), $replacement_data );
  }

  /**
   * provides the data set for the value tag replacement
   * 
   * defaults to the current record
   * if the $post array is provided, that will be used as the field value source
   * 
   * @param array $post optional fresh data; uses db value if not provided
   * @return array as $name => $value
   */
  protected function replacement_data( $post )
  {
    $data = array();
    
    // iterate through the fields named in the template
    foreach( $this->template_field_list() as $fieldname ) {
      
      $template_field = new \PDb_Field_Item( array('name' => $fieldname, 'module' => 'list' ), $this->field->record_id );
      
      if ( $template_field->form_element() === $this->name ) {
        
        /* if we are using the value from another string combine field, get the 
         * value directly from the db to avoid recursion
         */
        $data[$fieldname] = $this->field_db_value( $template_field->name() );
        $data['value:'.$fieldname] = $data[$fieldname];
        
      } else {
      
        if ( isset( $post[$template_field->name()] ) && ! \PDb_FormElement::is_empty( $post[$template_field->name()] ) ) {
          // get the value from the provided data
          $template_field->set_value( $post[$template_field->name()] );
        }

        $data[$fieldname] = $template_field->has_content() ? $template_field->get_value_display() : '';
        $data['value:'.$fieldname] = $template_field->has_content() ? $template_field->raw_value() : '';
      }
    }
    
    /**
     * provides a way to bring in other values for use by the field
     * 
     * @filter pdb-{$name}_replacement_data
     * @param array as $name => $value
     * @param \PDb_Field_Item
     * @return array
     */
    return \Participants_Db::apply_filters( $this->name . '_replacement_data', $this->clear_empty_values( $data ), $this->field );
  }

}
