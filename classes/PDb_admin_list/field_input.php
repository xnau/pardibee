<?php

/**
 * provides the html for a field edit or search input
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

namespace PDb_admin_list;

class field_input {
  
  /**
   * @var \PDb_Field_Item
   */
  private $field;
  
  /**
   * @var bool whether to show the label or not
   */
  private $show_label = true;
  
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
    
    $field->show_label = false;
    
    return $field->field_input();
  }
  
  /**
   * @param string $fieldname name of the field
   * @param string|array $value the field's initial value
   */
  public function __construct( $fieldname, $value )
  {
    $this->field = new \PDb_Field_Item( $fieldname );
    
    if ( $value === false ) {
      $value = $this->field->default_value();
    }
    
    $this->field->set_value($value);
    
    $this->modify_field();
  }
  
  /**
   * provides the input control for a field
   * 
   * @return string HTML the input control for the field
   */
  private function field_input()
  {
    if ( !\PDb_Form_Field_Def::is_field( $this->field->name() ) ) {
      return '';
    }
    
    $template = $this->show_label ? '<span class="field-input-label">%1$s:</span>%2$s' : '%2$s';
    
    return sprintf( $template, $this->field->title(), $this->field->get_element() );
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
    }
    
    if ( $new_form_element ) {
      $this->field->set_form_element($new_form_element);
    }
    
    $this->field->set_readonly(false);
    
    $this->field->attributes['id'] = 'mass_edit_' . $this->field->name();
    $this->field->add_class('field-input');
  }
}
