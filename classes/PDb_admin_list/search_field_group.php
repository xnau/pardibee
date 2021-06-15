<?php

/**
 * models a set of fields to use in the admin list search
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

abstract class search_field_group {
  
  /**
   * @var array of field names
   */
  private $field_list;
  
  /**
   * provides the list of field groups
   * 
   * @return array as $slug => $title
   */
  public static function group_display_list()
  {
    return array(
        'pdb_text_fields' => __( 'Any Text Field', 'participants-database' ),
        'pdb_all_fields' => __( 'Any Field', 'participants-database' ),
    );
  }
  
  /**
   * provides an array of group slugs
   * 
   * @return array
   */
  public static function group_list()
  {
    return array_keys( self::group_display_list() );
  }
  
  /**
   * provides the field groups selector
   * 
   * @return array
   */
  public static function group_selector()
  {
    $selector[ __( 'Multi-Field', 'participants-database' ) ] = 'optgroup';
    
    foreach ( self::group_display_list() as $slug => $title ) {
      
      $selector[$title] = $slug;
    }
    
    return $selector;
  }
  
  /**
   * 
   * @param string $slug name of the field group to get
   * @return object the field group instance
   */
  public static function get_search_group_object( $slug )
  {
    $class = '\PDb_admin_list\\' . str_replace('pdb_', '', $slug);
    return new $class();
  }


  /**
   * tests the field for inclusion
   * 
   * @param \PDb_Form_Field_Def $field
   * @return bool true to include the field
   */
  abstract protected function include_field( $field );
  
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
   * tells if the field type is a value set
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
      
      if ( $this->include_field( $def ) ) {
        $this->field_list[] = $fieldname;
      }
    }
  }
}
