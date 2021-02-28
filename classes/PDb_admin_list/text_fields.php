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
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;
use \Participants_Db;

class text_fields {
  
  /**
   * @param array of field names
   */
  private $field_list;
  
  /**
   */
  public function __construct()
  {
    $this->build_list();
  }
  
  /**
   * provides the name list string
   * 
   * @param string $separator list separator
   * @return string
   */
  public function list_string( $separator = ', ' )
  {
    return implode( $separator, $this->field_list );
  }
  
  /**
   * provides the field name string for a where clause
   * 
   * @return string
   */
  public function name()
  {
    return 'CONCAT_WS( " ",' . $this->list_string() . ')';
  }
  
  /**
   * provides the form element string
   * 
   * @return string
   */
  public function form_element()
  {
    return 'text-line';
  }
  
  /**
   * tells if the field is a value set
   * 
   * @return bool
   */
  public function is_value_set()
  {
    return false;
  }
  
  /**
   * build the list of fields to use in the filter
   * 
   */
  private function build_list()
  {
    foreach( Participants_Db::field_defs() as $fieldname => $def ) {
      
      if ( in_array( $def->form_element(), $this->valid_types() ) ) {
        $this->field_list[] = $fieldname;
      }
    }
  }
  
  /**
   * provides a list of valid form elements to include
   * 
   * @return array
   */
  private function valid_types()
  {
    return Participants_Db::apply_filters('valid_types_for_admin_list_multi_field_filter', array(
        'text-line', 'text-area', 'rich-text'
    ) );
  }
}
