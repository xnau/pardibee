<?php

/**
 * defines a field that displays a formatted string from record values
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class string_combine extends dynamic_db_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'string-combine';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'String Combine', 'name of a field type that shows a combined string', 'participants-database' ) );
    
    $this->customize_default_attribute( __( 'Template', 'participants-database' ), 'text-area' );
    
    $this->is_linkable();
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    if ( $this->field->is_valid_single_record_link_field() ) {
      
      $this->field->set_value( $this->dynamic_value() );
      
      return $this->field->output_single_record_link();
    }
    return $this->dynamic_value();
  }

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param \PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {  
    $this->element_id = uniqid();
    
    $field->form_element = 'text-line';
    
    $this->set_field( $field );
    
    $field->output = $this->form_element_html();
  }
  
  /**
   * provides the strings with tags replaced
   * 
   * this removes any unreplaced tags
   * 
   * @param array $data the data
   * @return string
   */
  protected function dynamic_value( $data = false )
  {
    $replaced_string = $this->replaced_string($data);
    
    if ( $replaced_string === $this->field->default_value ) {
      
      // if there is no replacement data
      return $this->field->module() === 'admin-edit' ? '' : $this->field->get_attribute( 'default' );
    }
    
    // remove unreplaced tags, trim spaces and dangling commas
    return preg_replace( array('/\[[a-z_]+\]/','/^([ ,]*)/','/([ ,]*)$/'), '', $replaced_string );
  }
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  private function replaced_string( $data )
  {
    return \PDb_Tag_Template::replace_text( $this->field->default_value, $data ? : $this->replacement_data() );
  }

  /**
   * displays the log in a write context
   * 
   * @param object $field
   * @return string
   */
  protected function form_element_html()
  {
    return sprintf( $this->wrap_template(), $this->dynamic_value() );
  }
  
  /**
   * provides the html wrap template
   * 
   * @return string
   */
  private function wrap_template()
  {
    $template[] = '<input type="hidden" name="' . $this->field->name() . '" value="%1$s" />';
    $template[] = '<span class="pdb-string_combine_field">%1$s</span>';
    
    return \Participants_Db::apply_filters( 'string_combine_wrap_template', implode( PHP_EOL, $template ) );
  }


  /**
   * provides the data set for the value tag replacement
   * 
   * defaults to the current record
   * 
   * @return array as $name => $value
   */
  private function replacement_data()
  {
    $data = array();
    
    foreach( $this->template_field_list() as $fieldname ) {
      
      $template_field = new \PDb_Field_Item( array('name' => $fieldname ), $this->field->record_id );
      
      $data[$fieldname] = $template_field->display_array_value();
    }
    /**
     * provides a way to bring in other values for use by the field
     * 
     * @filter pdb-string_combine_replacement_data
     * @param array as $name => $value
     * @param \PDb_Field_Item
     * @return array
     */
    return \Participants_Db::apply_filters( 'string_combine_replacement_data', $this->clear_empty_values( $data ), $this->field );
  }
  
  /**
   * provides a list of the fields that are included in the template
   * 
   * @return array of field names
   */
  private function template_field_list()
  {
    $template = $this->field->default_value();
    
    preg_match_all('/\[([^\]]+)\]/', $template, $matches );
    
    $list = array();
    
    foreach( $matches[1] as $fieldname )
    {
      if( \PDb_Form_Field_Def::is_field( $fieldname ) ) {
        $list[] = $fieldname;
      }
    }
    
    return $list;
  }
  
  /**
   * removes empty and null string values from an array
   * 
   * @param array $input
   * @reteun array
   */
  private function clear_empty_values( $input )
  {
    return array_filter( $input, function($v) {
      return $v !== '' && !is_null( $v );
    } );
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
        'default' => true,
        'persistent' => false,
        'csv' => true,
        'sortable' => true,
        'help_text' => false,
        'validation' => false,
        'validation_message' => false,
        'signup' => false,
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

}
