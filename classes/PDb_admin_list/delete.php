<?php

/**
 * handles deleting records
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

defined( 'ABSPATH' ) || exit;

class delete {
  
  /**
   * @param string name of the file delete preference
   */
  const file_delete_preference = 'delete_files';
  
  /**
   * @param string name of the GET var to initiate a orphan file delete
   */
  const orphan_delete = 'pdb-delete_orphan_files';
  
  /**
   * 
   */
  public function __construct()
  {
    add_filter( 'pdb-admin_list_with_selected_control_html', array( $this, 'add_preference_control' ) );
    
    global $pagenow;
    
    if ( $pagenow === 'admin.php' && strpos( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ), 'participants-database' ) !== false ) {
      $delete_orphan_file_types = filter_input( INPUT_GET, self::orphan_delete, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE );
      if ( $delete_orphan_file_types ) {
        
        $count = uploaded_files::delete_orphaned_files( $delete_orphan_file_types );
        
        \Participants_Db::set_admin_message( sprintf( __( 'Orphan uploaded files cleared. %s files deleted', 'participants-database' ), $count ), 'updated' );
      }
    }
  }
  
  /**
   * deletes a list of records
   * 
   * @param array $id_list
   * @param bool $delete_files if true, delete all associated files
   * @return string the db query that was used
   */
  public static function delete_records( $id_list, $delete_files = false )
  {
    /**
     * @version 1.6.3
     * @filter  pdb-before_admin_delete_record
     * @param array $selected_ids list of ids to delete
     */
    $id_list = \Participants_Db::apply_filters( 'before_admin_delete_record', $id_list );

    do_action( 'pdb-list_admin_with_selected_delete', $id_list );
    
    if ( $delete_files ) {
      uploaded_files::delete_record_files($id_list);
    }
    
    return self::_delete($id_list);
  }

  /**
   * handles record deletion
   * 
   * @global \wpdb $wpdb
   * @param array $id_list
   * 
   * @return string the db query that was used
   */
  private static function _delete( $id_list )
  {
    global $wpdb;

    $pattern = count( $id_list ) > 1 ? 'IN ( ' . trim( str_repeat( '%s,', count( $id_list ) ), ',' ) . ' )' : '= %s';
    $sql = "DELETE FROM " . \Participants_Db::$participants_table . " WHERE id " . $pattern;
    $result = $wpdb->query( $wpdb->prepare( $sql, $id_list ) );
    $last_query = $wpdb->last_query;

    if ( $result > 0 ) {
      \Participants_Db::set_admin_message( __( 'Record delete successful.', 'participants-database' ), 'updated' );
    }
    
    return $last_query;
  }

  /**
   * adds the edit controllers to the with selected control
   * 
   * @param array $html
   * @return array
   */
  public function add_preference_control( $html )
  {
    foreach ( $html as $index => $row ) {
      if ( strpos( $row, 'type="submit"' ) !== false ) {
        break;
      }
    }

    array_splice( $html, $index, 0, $this->preference_control() );

    return $html;
  }
  
  /**
   * provides the preference control
   * 
   * @return string HTML
   */
  private function preference_control()
  {
    $config = array(
        'type' => 'checkbox',
        'name' => self::file_delete_preference,
        'value' => $this->file_delete_preference(),
        'options' => array( __( 'delete uploaded files with record?', 'participants-database' ) => '1', '0' => '0' ),
    );
    
    return '<span class="file-delete-preference-selector" style="display:none">' . \PDb_FormElement::get_element($config) . '</span>';
  }
  
  /**
   * provides the file delete preference
   * 
   * @return string
   */
  private function file_delete_preference()
  {
    /*
     * the 'delete_uploaded_files' setting is no longer used, but we get our initial state from it
     */
    return \PDb_List_Admin::get_admin_user_setting( self::file_delete_preference, \Participants_Db::plugin_setting_is_true( 'delete_uploaded_files', false ) ? '1':'0' );
  }
}
