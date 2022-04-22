<?php

/**
 * provides the html for a field edit or search input
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;

defined( 'ABSPATH' ) || exit;

class field_input {
  
  /**
   * @var \PDb_Field_Item the current field
   */
  private $field;
  
  /**
   * provides the field input HTML including the label
   * 
   * @param string $fieldname name of the field
   * @param string|array $value the field's initial value
   * @return string HTML
   */
  public static function html( $fieldname, $value )
  {
    $field = new self( $fieldname, $value );
    
    return $field->field_input();
  }
  
  /**
   * provides the field input HTML without a label
   * 
   * @param string $fieldname name of the field
   * @param string|array $value the field's initial value
   * @return string HTML
   */
  public static function bare_html( $fieldname, $value )
  {
    $field = new self( $fieldname, $value );
    
    return $field->field_input(false);
  }
  
  /**
   * @param string $fieldname name of the field
   * @param string|array|bool $value the field's initial value
   */
  private function __construct( $fieldname, $value )
  {
    $this->field = new \PDb_Field_Item( $fieldname );
    
    if ( $value === false && $this->field_displays_default_value() ) {
      $value = $this->field->default_value();
    }
    
    $this->field->set_value($value);
    
    $this->field->set_module('admin_list_action');
    
    $this->modify_field();
  }
  
  /**
   * provides the input control for a field
   * 
   * @param bool $show_label
   * @return string HTML the input control for the field
   */
  private function field_input( $show_label = true )
  {
    if ( !\PDb_Form_Field_Def::is_field( $this->field->name() ) ) {
      return '';
    }
    
    $template = $show_label ? '<span class="field-input-label">%1$s:</span>%2$s%3$s' : '%2$s';
    
    return sprintf( $template, $this->field->title(), $this->field->get_element(), $this->field_help_text() );
  }
  
  /**
   * modifies the field object for display in the mass edit control
   * 
   */
  private function modify_field()
  {
    $new_form_element = false;
    
    switch( $this->field->form_element() ) {
      
      case 'hidden':
        $new_form_element = 'text-line';
        break;
      
      case 'rich-text':
        $new_form_element = 'text-area';
        break;
    }
    
    if ( $new_form_element ) {
      $this->field->set_form_element($new_form_element);
    }
    
    $this->field->set_readonly(false);
    
    $this->field->attributes['id'] = 'mass_edit_' . $this->field->name();
    $this->field->add_class('field-input');
  }
  
  /**
   * provides the field help text
   * 
   * @return string
   */
  private function field_help_text()
  {
    switch ( $this->field->form_element() ) {
      
      case 'timestamp':
        
        $datetime = date( get_option('date_format') . ' ' . get_option( 'time_format' ) );
        $helptext = sprintf( __('Timestamp values must be entered using this format: %s', 'participants-database' ), $datetime );
        break;
      
      default:
        $helptext = '';
    }
    
    if ( $helptext !== '' ) {
      $helptext = sprintf( '<p class="field-help">%s</p>', $helptext );
    }
    
    return $helptext;
  }
  
  /**
   * tells of the field input shows the default value if empty
   * 
   * @return bool
   */
  private function field_displays_default_value()
  {
    return ! $this->field->is_dynamic_field();
  }
}
