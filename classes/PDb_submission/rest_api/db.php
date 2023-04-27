<?php

/**
 * provides database interactions for rest requests
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

namespace PDb_submission\rest_api;

class db {
  

  /**
   * gets an array of record values
   *
   * @global \wpdb $wpdb
   * @param  int $id the record ID
   * @param string $role the plugin role
   * 
   * @return array|bool associative array of name=>value pairs; false if no record 
   *                    matching the ID was found 
   */
  public static function get_record( $id, $role )
  {
    global $wpdb;

    $sql = 'SELECT p.' . implode( ',p.', self::user_field_list( $role ) ) . ' FROM ' . \Participants_Db::$participants_table . ' p WHERE p.id = %d';

    $result = $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );

    if ( is_array( $result ) ) {
      return array_merge( $result, array('id' => $id) );
    } else {
      return false;
    }
  }
  
  /**
   * gets a list of records based on the filter, sort and user privs
   * 
   * @param string $role the user role
   * @param array $filter array of filter, fields, sort and sortby values
   * @return array array of record value arrays
   */
  public static function get_list( $role, $filter )
  {
    $filter['fields'] = self::list_field_list( $role, ( isset($filter['fields']) ? $filter['fields'] : '' ) );
    
    return \Participants_Db::get_participant_list( $filter );
  }
  
  /**
   * checks for a record with the provided id
   * 
   * @global \wpdb $wpdb
   * @param int $record_id
   * @return bool true if the record exists
   */
  public static function record_exists( $record_id )
  {
    global $wpdb;
    
    $sql = 'SELECT COUNT(*) FROM ' . \Participants_Db::$participants_table . ' p WHERE p.id = %d'; 
    
    $result = $wpdb->get_var( $wpdb->prepare( $sql, $record_id ) );
    
    return boolval( $result );
  }
  
  /**
   * checks for a record with the provided id
   * 
   * @global \wpdb $wpdb
   * @param int $record_id
   * @param bool $delete_files
   * @return bool true if the record existed and was deleted
   */
  public static function delete_record( $record_id, $delete_files = false )
  {
    return \PDb_admin_list\delete::delete_records([$record_id], $delete_files);
  }
  
  /**
   * provides the list of fields for a list request
   * 
   * if the $fields parameter is supplied, it is filtered to remove fields the 
   * user isn't authorized to access
   * 
   * @param string $role
   * @param string $fields the fields parameter
   * @return string comma-separated list of field names
   */
  private static function list_field_list( $role, $fields )
  {
    $allowed_list = self::user_field_list( $role );
    
    if ( empty( $fields ) )
    {
      return implode( ',', $allowed_list );
    }
    
    $fields_param = explode( ',', $fields );
    
    return implode( ',', array_intersect( $fields_param, $allowed_list ) );
  }
  
  /**
   * provides a list of fieldnames filtered by the user's access
   * 
   * @param string $role the plugin role of the current user
   * @param bool $writable if true, only fields the role can write to will be included 
   * @global \wpdb $wpdb
   * @return array
   */
  public static function user_field_list( $role, $writable = false )
  {
    global $wpdb;
    
    $sql = 'SELECT f.name FROM ' . \Participants_Db::$fields_table . ' f JOIN ' . \Participants_Db::$groups_table . ' g ON f.group = g.name WHERE g.mode IN ("' . implode('","', self::group_modes($role,$writable) ) . '")';
    
    return array_intersect( \Participants_Db::db_field_list(), $wpdb->get_col( $sql ) );
  }
  
  /**
   * provides the list of allowed group modes
   * 
   * @param string $role
   * @param bool $writable if true, will exclude groups the role cannot write to
   * @return array
   */
  public static function group_modes( $role, $writable = false )
  {
    $modes = array( 'public' );
    
    switch ($role)
    {
      case 'editor':
        $modes[] = 'private';
        if ( !$writable )
        {
          $modes[] = 'admin';
        }
        break;
      case 'admin':
        $modes[] = 'private';
        $modes[] = 'admin';
    }
    
    return $modes;
  }
}
