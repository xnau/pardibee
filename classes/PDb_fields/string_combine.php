<?php

/**
 * defines a field that displays a formatted string from record values
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    1.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

defined( 'ABSPATH' ) || exit;

class string_combine extends calculated_field {

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
   * supplies the formatted display
   */
  protected function formatted_display()
  {
    return $this->field->value();
  }
  
  /**
   * adds the format tag to the calculation template if it is missing
   * 
   * @return string calculation format
   */
  protected function completed_template()
  {
    return $this->field->default_value();
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
    
    $replaced_string = \PDb_Tag_Template::replace_text( $this->template(), $replacement_data );
    
    if ( $this->field->get_attribute( 'complete_only' ) && ! $this->complete ) {
      $replaced_string = '';
    }
    
    return $replaced_string;
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

      $template_field = $this->field_object( $fieldname, $this->field->record_id(), $post );
      $template_field->set_module('list');
      
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

        $data[$fieldname] = $this->field_value( $template_field );
        $data['value:'.$fieldname] = $template_field->has_content() ? $template_field->raw_value() : '';
      }
      
    }
    
    $clean_data = $this->clear_empty_values( $data );
    
    $this->complete = count( $data ) === count( $clean_data );
    
    /**
     * provides a way to bring in other values for use by the field
     * 
     * this data is also passed through the global pdb-calculated_field_data filter 
     * 
     * @filter pdb-{$name}_replacement_data
     * @param array as $name => $value
     * @param \PDb_Field_Item
     * @return array
     */
    return \Participants_Db::apply_filters( $this->name . '_replacement_data', $this->filter_data( $clean_data ), $this->field );
  }
  
  /**
   * provides the field value to use in the string combine data array
   * 
   * @param \PDb_Field_Item $field
   * @return string value to use in the concatenation
   */
  private function field_value( $template_field )
  {
    $value = '';
    
    if ( $template_field->has_content() )
    {
      $value = $template_field->get_value_display();
      if ( $this->field->get_attribute( 'strip_tags' ) ) {
        $value = strip_tags( $value );
      }
    }
    
    return $value;
  }
  
  
  /**
   * provides the template string
   * 
   * preps the template for the use of raw values in element attributes
   * 
   * @return string
   */
  protected function template()
  {
    $template = $this->strip_format_tag( $this->field->default_value() );
    
    // if there are no tags, use the template as-is
    if ( preg_match( '/\[.+\]/', $template ) !== 1 ) {
      return $template;
    }
    
    $pattern = <<<PATT
/(\S+)=["']?((?:.(?!["']?\s+(?:\S+)=|\s*\/?[>"']))+.)["']?/m
PATT;
    
    $template = preg_replace_callback( $pattern, function ($tag) {
      return str_replace('[', '[value:', $tag[0] );
    }, $template );
    
    return $template;
  }
  
  /**
   * strips the format tag out of the template if present
   * 
   * @param string $template
   * @return string
   */
  private function strip_format_tag( $template )
  {
    // substring that will be present if there is a format tag in the template
    $key = '=[?'; 
    
    if ( strpos( $template, $key ) === false ) {
      return $template;
    }
    
    $split = explode( $key, $template );
    
    return $split[0];
  }
  
  /**
   * tells if the current field stores a numeric value
   * 
   * @return bool
   */
  protected function is_numeric_field()
  {
    return false;
  }

}
