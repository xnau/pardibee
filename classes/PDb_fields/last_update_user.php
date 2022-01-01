<?php

/**
 * provides a field to monitor who is making changes to records
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

namespace PDb_fields;

class last_update_user {
  
  const fieldname = 'last_update_user';
  
  /**
   * @var int the ID of the current user
   */
  private $user_id = 0;
  
  /**
   * sets up the field
   */
  public function __construct()
  {
    if ( ! $this->field_exists() ) {
      $this->create_field();
    }
    
    add_filter( "pdb-field_editor_switches", array( $this, 'editor_config' ), 10, 2 );
    
    add_filter( 'pdb-before_submit_update', array( $this, 'update_last_user_id' ) );
  }
  
  /**
   * updates the field
   * 
   * @param array $post the posted data
   * @return array
   */
  public function update_last_user_id( $post )
  {
    if ( $this->yes_log_user_id() ) {
      if ( array_key_exists( self::fieldname, $post ) ) {

        $post[self::fieldname] = $this->user_id;
      } else {
        
        add_action( 'pdb-after_submit_update', array( $this, 'update_record' ) );
      }
    }
    
    return $post;
  }
  
  /**
   * updates the record with the user id
   * 
   * this is only fired if we can't do it in the initial submission
   * 
   * @param array $post the new record data
   */
  public function update_record( $post )
  {
    $this->_update_record( $post['id'] );
    
    remove_action( 'pdb-after_submit_update', array( $this, 'update_record' ) );
  }
  
  /**
   * updates the user ID to the record in the db
   * 
   * @global \wpdb $wpdb
   * @param int $record_id
   */
  private function _update_record( $record_id )
  {
    global $wpdb;
    
    $wpdb->update( \Participants_Db::participants_table(), array( self::fieldname => $this->user_id ), array( 'id' => $record_id ) );
  }
  
  /**
   * tells if the current user should be logged
   * 
   * @return bool
   */
  private function yes_log_user_id()
  {
    $this->user_id = get_current_user_id();
    
    return $this->user_id ? $this->user_is_logged() : false;
  }
  
  /**
   * tells if the user id is excepted
   * 
   * @return true if the current user is not excepted
   */
  private function user_is_logged()
  {
    $field = \Participants_Db::$fields[self::fieldname];
    /** @var \PDb_Form_Field_Def $field */
    
    $exceptions = explode( '|', $field->get_attribute('dont_log') );
    
    if ( ! empty( $exceptions ) && in_array( $this->user_id, $exceptions ) ) {
      return false;
    }
    
    return true;
  }
  
  /**
   * provides the title of the field
   * 
   * @return string
   */
  public static function title()
  {
    return __( 'Last Updater ID', 'participants-database' );
  }
  
  /**
   * tells if the field exists
   * 
   * @return bool
   */
  private function field_exists()
  {
    return \PDb_Form_Field_Def::is_field(self::fieldname);
  }
  
  /**
   * provides the editor config for the field
   * 
   * @param array $switches
   * @param \PDb_Form_Field_Def $field
   * @return array
   */
  public function editor_config( $switches, $field )
  {
    if ( $field->name() === self::fieldname ) {
      $switches['attributes'] = true;
    }
    
    return $switches;
  }
  
  /**
   * creates the field if it doesn't exist
   */
  private function create_field()
  {
    $params = array(
        'name' => self::fieldname,
        'title' => self::title(),
        'group' => 'internal',
        'form_element' => 'text-line',
        'read-only' => 1,
        'searchable' => 1,
    );
    
    \Participants_Db::add_blank_field($params);
  }
}
