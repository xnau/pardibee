<?php
/*
 * class for handling the display of a participant record
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Template_Item class
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Record_Item extends PDb_Template_Item {
  
  /**
   * instantiates a field group object
   *
   * @param object a object with all the field group's properties
   */
  public function __construct( $record, $id, $module ) {
    
    $this->module = $module;
    
    // get rid of unneeded properties
    unset( $this->name, $this->title );
    
    $this->record_id = $id;
    
    $this->assign_props( $record );
    
  }
  
  /**
   * sets up the values property
   */
  protected function assign_props( $record ) {
    
    // add the record field objects
    // this needs to by typed as array for the iterators to work
    $this->fields = (array) $record;
    
    foreach($this->fields as $name => $field) {
    
      // get the field attributes
      $field = (object) array_merge((array)$field, (array)Participants_Db::$fields[$name]);
      
      if (isset($field->value)) $this->values[$name] = $field->value;
    }
    reset($this->fields);
  }
  
}