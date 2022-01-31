<?php

/**
 * handles updating the records via mass update
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

defined( 'ABSPATH' ) || exit;

class mass_edit_update {
  
  
  /**
   * @var int holds the number of records that were updated
   */
  private $updated_count = 0;
  
  /**
   * @var \PDb_Field_Item the field object
   */
  private $field;
  
  /**
   * @var string value of the mass edit input
   */
  private $value;
  
  /**
   * 
   */
  public function __construct()
  {
    add_action( 'pdb_admin_list_with_selected/' . mass_edit::edit_action, array( $this, 'update_records' ) );
  }
  
  /**
   * handles the update
   * 
   * @param array $id_list
   */
  public function update_records( $id_list )
  {
    $this->setup_field();
    $this->new_value();
    $this->updated_count = $this->perform_update($id_list);
    $this->update_admin_prefs();
    $this->set_feedback();
  }
  
  /**
   * sets the feedback message
   */
  private function set_feedback()
  {
    $type = $this->updated_count > 0 ? 'success' : 'info';
    
    \Participants_Db::set_admin_message( $this->operation_feedback(), $type );
  }

  /**
   * provides an operation feedback message
   * 
   * @return string
   */
  private function operation_feedback()
  {
    if ( $this->is_plugin_action() ) {
      $message[] = '<strong>' . __( 'Admin List Mass Edit', 'participant-database' ) . ':</strong> ';
      $message[] = sprintf( _n( '%s Record was updated', '%s Records were updated', $this->updated_count, 'participants-database' ), $this->updated_count );

      return implode( PHP_EOL, $message );
    }
  }
  
  /**
   * update the admin prefernces
   */
  private function update_admin_prefs()
  {
    \PDb_List_Admin::set_admin_user_setting( mass_edit::field_selector, $this->field->name() );
    \PDb_List_Admin::set_admin_user_setting( $this->field->name(), $this->value );
    $this->add_to_recents();
  }
  
  
  /**
   * adds the last used field to the list of recently used fields
   * 
   */
  private function add_to_recents()
  {
    $setting = mass_edit::field_selector . '_recents';
    $recents = \PDb_List_Admin::get_admin_user_setting( $setting, array() );
    
    $recent_fields = filter::add_to_recents( $this->field->name(), $recents );
    
    \PDb_List_Admin::set_admin_user_setting( $setting, $recent_fields );
  }
  
  /**
   * performs the mass update
   * 
   * @global \wpdb $wpdb
   * @param array $id_list list of record ids
   * @return int number of records updated
   */
  private function perform_update( $id_list )
  {
    $result = 0;
    
    if ( $this->value !== false ) {
      global $wpdb;

      $result = $wpdb->query( $this->update_query( $id_list ) );

      \Participants_Db::debug_log( 'Mass edit query: '.$wpdb->last_query );
    }
    
    return $result > 0 ? intval( $result/2 ) : 0;
  }
  
  /**
   * provides the list write query
   * 
   * @param array $id_list
   * @return string
   */
  private function update_query( $id_list )
  {
    $sql[] = 'INSERT INTO ' . \Participants_Db::$participants_table . '(id,' . $this->field->name() . ')';
    $sql[] = 'VALUES';

    $values = array();

    foreach ( $id_list as $record_id ) {
      $values[] = sprintf( "('%s','%s')", $record_id, $this->value );
    }

    $sql[] = implode( ',', $values );

    $sql[] = 'ON DUPLICATE KEY UPDATE';

    $sql[] = $this->field->name() . ' = VALUES(' . $this->field->name() . ')';

    return implode( ' ', $sql );
  }
  
  /**
   * provides the new value for the update
   * 
   * @return string
   */
  private function new_value()
  {
    $data = filter_input_array( INPUT_POST, $this->sanitizer() );
    
    $this->value = $this->prep_value( $data[ $this->field->name() ] );
  }
  
  /**
   * prepares the value for the update
   * 
   * @param string|array $raw_value
   * @return string|bool false if the value should not be written
   */
  private function prep_value( $raw_value )
  {
    if ( is_array( $raw_value ) ) {
      $raw_value = serialize( $raw_value );
    }
    
    switch ( $this->field->form_element() ) {
      
      case 'date':
        
        $value = \PDb_Date_Parse::timestamp( $raw_value );
        break;
      
      case 'timestamp':
        
        $value = \PDb_Date_Display::get_mysql_timestamp( \PDb_Date_Parse::timestamp( $raw_value ) );
        break;
      
      default:
        $value = $raw_value;
      
    }
    
    if ( $value === false ) {
      \Participants_Db::debug_log( 'Mass edit invalid value "' . $raw_value . '" for field: '. $this->field->name() );
    }
    
    return esc_sql( $value );
  }
  
  /**
   * provides the sanitize array
   * 
   * @return int the filter code
   */
  private function sanitizer()
  {
    $sanitizer = array( 'filter' => FILTER_SANITIZE_STRING );
    
    if ( $this->field->is_multi() ) {
      $sanitizer['flags'] = FILTER_REQUIRE_ARRAY;
    }
    
    return array( $this->field->name() => $sanitizer );
  }
  
  /**
   * sets up the field property
   */
  private function setup_field()
  {
    $this->field = new \PDb_Field_Item( filter_input( INPUT_POST, mass_edit::field_selector, FILTER_SANITIZE_STRING ) );
  }


  /**
   * tells if the last performed action was a plugin action
   * 
   * @return bool 
   */
  private function is_plugin_action()
  {
    $action = filter_input( INPUT_POST, 'with_selected', FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE );
    
    return $action === mass_edit::edit_action;
  }
  
  
}
