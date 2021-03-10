<?php

/**
 * defines a field that displays a formatted string from record values
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class string_combine extends core {

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
    
    add_filter( 'pdb-before_submit_update', array( $this, 'update_db_value' ) );
    
    $this->field_list(); // cache the field list
    
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
      
      $this->field->set_value( $this->combined_string() );
      
      return $this->field->output_single_record_link();
    }
    return $this->combined_string();
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
    
    $field->type = 'text-line';
    
    $this->set_field( $field );
    
    $field->output = $this->form_element_html();
  }
  
  /**
   * updates the database value for the field
   * 
   * @param array $post
   * @retrn array
   */
  public function update_db_value( $post )
  {
    foreach( $this->field_list() as $string_combine_field ) {
      
      /** @var \PDb_Form_Field_Def $string_combine_field */
      
      if ( isset( $post[ $string_combine_field->name() ] ) ) {
      
        $field = new \PDb_Field_Item( $string_combine_field );
        $field->set_record_id( $post['id'] );
        $this->setup_field( $field );

        $post[ $string_combine_field->name() ] = $this->combined_string( $post );
      }
      
    }
    return $post;
  }
  
  /**
   * provides the combined string
   * 
   * this removes any unreplaced tags
   * 
   * @param array $data the data
   * @return string
   */
  private function combined_string( $data = false )
  {
    $replaced_string = $this->replaced_string($data);
    
    if ( $replaced_string === $this->field->default_value() ) {
      // if there is no replacement data
      return $this->field->get_attribute( 'default' );
    }
    
    return preg_replace( '/\[[a-z_]+\]/', '', $replaced_string );
  }
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  private function replaced_string( $data )
  {
    return \PDb_Tag_Template::replace_text( $this->field->default_value(), $data ? : $this->record_data() );
  }

  /**
   * displays the log in a write context
   * 
   * @param object $field
   * @return string
   */
  protected function form_element_html()
  {
    return sprintf( $this->wrap_template(), $this->combined_string() );
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
   * gets the string combine field list
   * 
   * caches a list of all the string combine fields
   * 
   * @return array of PDb_Form_Field_Def objects
   */
  private function field_list()
  {
    $cachekey = self::element_name . '_field_list';
    $list = wp_cache_get( $cachekey );
    
    if ( ! is_array( $list ) ) {
      
      $list = array();
      
      foreach( \Participants_Db::$fields as $field ) {
        if ( $field->form_element() === self::element_name ) {
          
          $list[] = $field;
        }
      }
      
      wp_cache_add( $cachekey, $list );
    }
    
    return $list;
  }


  /**
   * provides the record data
   * 
   * @return array as $name => $value
   */
  private function record_data()
  {
    return \Participants_Db::get_participant( $this->field->record_id );
  }

  /**
   * sets the field property
   * 
   * @param string|\PDb_FormElement $field the incoming field
   */
  private function set_field( $field )
  {
    $field_item = new \PDb_Field_Item( $field );

    $this->setup_field( $field_item );
    
    $this->field->set_readonly();
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
