<?php

/**
 * provides the fields for an "all fields" search
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

class all_fields extends search_field_group {
  
  /**
   * tests the field for inclusion
   * 
   * @param \PDb_Form_Field_Def $field
   * @return bool true to include the field
   */
  protected function include_field( $field )
  {
    return $this->_include_field( $field );
  }
  
  /**
   * tells if the field type should be included
   * 
   * @param \PDb_Form_Field_Def $field
   * @return bool
   */
  private function _include_field( $field )
  {
    return \Participants_Db::apply_filters( 'valid_type_for_admin_list_search_all_fields', $field->stores_data(), $field );
  }
  
}
