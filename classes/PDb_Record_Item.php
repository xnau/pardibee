<?php
/*
 * class for handling the display of a participant record
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Template_Item class
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Record_Item extends PDb_Template_Item {
  
  /**
   * instantiates the record item
   * 
   * @param array $fields collection of PDb_Field_Item objects
   * @param int $id the record id
   * @param string $modules name of the current module
   */
  public function __construct( $fields, $id, $module ) {
    
    $this->module = $module;
    $this->record_id = $id;
    $this->fields = $fields;
    
    // get rid of unneeded properties
    unset( $this->name, $this->title );
    
    $this->assign_values();
    
  }
  
  /**
   * sets up the values property
   */
  protected function assign_values() {
    
    foreach($this->fields as $name => $field) {
      
      $this->values[$name] = isset($field->value) ? $field->value : '';
      
    }
    reset($this->fields);
  }
  
}