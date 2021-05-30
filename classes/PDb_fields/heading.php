<?php

/**
 * defines a field that can serve as a divider between groups of fields.
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

class heading extends utility {

  /**
   * @var string name of the form element
   */
  const element_name = 'heading';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'Heading', 'name of a field type that shows a text heading', 'participants-database' ) );

    add_filter( 'pdb-add_field_to_iterator', array( $this, 'yes_show_field' ), 10, 2 );
    
    $this->customize_default_attribute( __( 'Heading', 'participants-database' ), 'rich-text' );
    
    $this->suppressed_shortcodes(array('list'));
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    return $this->form_element_html();
  }

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param \PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {  
    parent::form_element_build($field);
  }

  /**
   * displays the field in a write context
   * 
   * @return string
   */
  public function form_element_html()
  {
    return \Participants_Db::apply_filters('translate_string', $this->field->default );
  }

  /**
   * tells whether the field should be shown or not
   * 
   * called on the pdb-add_field_to_iterator filter
   * 
   * @param bool $shown
   * @param PDb_Field_Item the current field
   * @return bool
   */
  public function yes_show_field( $shown, $field )
  {
    $this->setup_field($field);
    switch ( $field->module() ) {
      case 'email-template':
      case 'tag-template':
      case 'single';
        return $this->show_in_display_context();
        break;
      case 'record':
        return $this->show_in_write_context();
        break;
      default:
        return true;
    }
  }

  /**
   * tells if the field should be shown in a write (form) context
   * 
   * @return bool
   */
  private function show_in_write_context()
  {
    return ! isset( $this->field->attributes()[ 'display_only' ] );
  }

  /**
   * tells if the field should be shown in a display (read only) context
   * 
   * @return bool
   */
  private function show_in_display_context()
  {
    return ! isset( $this->field->attributes()[ 'form_only' ] );
  }


  /**
   *  provides the field editor configuration switches array
   * 
   * @param array $switches
   * @return array
   */
  public function editor_config( $switches )
  {
    return array_merge( parent::editor_config($switches), array( 'attributes' => false ) );
  }
}
