<?php

/**
 * triggers an action the first time a user accesses their record using a private link
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2023  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_shortcodes;

class user_access_action {
  
  /**
   * @var string name of the last_accessed column
   */
  const last_access_column = 'last_accessed';
  
  /**
   * sets up the hook
   * 
   * @param int $record_id
   */
  public function __construct( $record_id )
  {
    if ( ! $this->current_user_is_plugin_editor() )
    {
      $record = \Participants_Db::get_participant( $record_id );
      
      if ( $this->has_no_previous_access( $record_id ) )
      {
        $this->trigger_first_access_event( $record );

        \Participants_Db::debug_log( 'First time user access for record ' . $record_id  );
      }
      
      $this->trigger_access_event( $record );
    }
  }
  
  /**
   * triggers the first access event
   * 
   * @param array $record the record data
   */
  private function trigger_access_event( $record )
  {
    /**
     * @action pdb-record_accessed_using_private_link
     * @param array record data
     */
    do_action( 'pdb-record_accessed_using_private_link', $record );
  }
  
  /**
   * triggers the first access event
   * 
   * @param array $record the record data
   */
  private function trigger_first_access_event( $record )
  {
    /**
     * @action pdb-first_time_record_access_with_private_link
     * @param array record data
     */
    do_action( 'pdb-first_time_record_access_with_private_link', $record );
  }
  
  /**
   * checks for a last access
   * 
   * @global \wpdb $wpdb
   * @param int $record_id
   * @return bool
   */
  private function has_no_previous_access( $record_id )
  {
    global $wpdb; 
    
    $result = $wpdb->get_var( $wpdb->prepare( 'SELECT p.' . self::last_access_column . ' FROM ' . \Participants_Db::$participants_table . ' p WHERE p.id = %s', $record_id ) );
    
    return empty( $result );
  }
  
  /**
   * tells if the current user is a plugin admin or editor
   * 
   * @return bool
   */
  private function current_user_is_plugin_editor()
  {
    if ( !is_user_logged_in() )
    {
      return false;
    }
    
    if ( \Participants_Db::current_user_has_plugin_role( 'editor', __METHOD__ ) )
    {
      return true;
    }
    
    return false;
  }
}
