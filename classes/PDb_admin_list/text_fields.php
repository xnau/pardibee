<?php

/**
 * models a list of text fields for use in a multi-field search filter
 * 
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;
use \Participants_Db;

class text_fields extends search_field_group {
  
  /**
   * tests the field for inclusion
   * 
   * @param \PDb_Form_Field_Def $field
   * @return bool true to include the field
   */
  protected function include_field( $field )
  {
    return in_array( $field->form_element(), $this->valid_types() );
  }
  
  /**
   * provides a list of valid form elements to include
   * 
   * @return array
   */
  private function valid_types()
  {
    return Participants_Db::apply_filters('valid_types_for_admin_list_search_text_fields', array(
        'text-line', 'text-area', 'rich-text'
    ) );
  }
}
