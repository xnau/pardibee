<?php

/**
 * provides the list of column objects for the main record query
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

namespace PDb_submission\main_query;
use Participants_Db;

class columns {
  
  /**
   * @var array of column objects
   */
  private $column_array;
  
  /**
   * @param string $action update or insert
   * @param bool $new true if the columns are for a new record
   * @param array $columns optional list of column names
   */
  private function __construct( $action, $new, $columns )
  {
    if ( is_array( $columns ) ) {
      
      // signup form or function call
      
      $default_cols = array();
      
      if ( $action === 'insert' ) {
        /**
         * switch to enable filling in a new record with default values
         * @filter pdb-process_form_fill_default_values
         * @param bool
         * @return bool
         */
        if ( Participants_Db::apply_filters( 'process_form_fill_default_values', true ) ) {
          $default_cols = array_merge( $this->column_default_list(), array('private_id') );
        } else {
          $default_cols = array('private_id');
        }
      }
      
      $column_set = array_merge( $columns, $default_cols );
      
    } else {
      /**
       * @filter pdb-post_action_override
       * @param the current $_POST action value
       * @return the action value to use: either "signup" or "update"
       */
      if ( Participants_Db::apply_filters( 'post_action_override', filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING ) ) === 'signup' ) {

        $column_set = 'signup';
      } else {

        $column_set = $action == 'update' ? ( is_admin() ? 'backend' : 'frontend' ) : ( $new ? 'new' : 'all' );

      }
    }
    
    $this->column_array = self::column_atts( $column_set );
      
    $array = [];
    foreach( $this->column_array as $column ) {
      $array[] = $column->name;
    }
//    error_log(__METHOD__.' set: '.$column_set.' columns array: '.print_r($array,1));
    
  }
  
  /**
   * provides the column array for use in the iterator
   * 
   * @param string $action
   * @param bool $new true if the columns are for a new record
   * @param array $columns optional list of column names
   * @return array
   */
  public static function column_array( $action, $new, $columns = false )
  {
    $columns = new self( $action, $new, $columns );
    
    return $columns->column_array;
  }
  
  /**
   * provides a column instance
   * 
   * @param object $column_def raw database object
   * @param string $value
   * @param \PDb_submission\main_query\record $main_query
   * @return object \PDb_submission/main_query/base_column
   */
  public static function get_column_object( $column, $value, $main_query )
  {
    $internal_fields = array('id','date_recorded','date_updated','last_accessed','private_id');
    
    if ( in_array( $column->name, $internal_fields ) ) {
      return new internal_column( $column, $value, $main_query );
    } else {
      return new user_column( $column, $value, $main_query );
    }
  }
  
  /**
   * provides an array of field definitions
   * 
   * @param string|array $set the set of columns to get
   */
  public static function field_definition_list( $set )
  {
    return self::column_atts( $set );
  }


  /**
   * gets a set of field attributes as filtered by context
   *
   * @global wpdb $wpdb
   * @param string|array $filter sets the context of the display and determines the 
   *                             set of columns to return, also accepts an array of 
   *                             column names
   * @return object the object is ordered first by the order of the group, then 
   *                by the field order
   */
  private static function column_atts( $filter = 'new' )
  {
    global $wpdb;
    
    if ( is_array( $filter ) ) {
      
      $where = 'WHERE v.name IN ("' . implode( '","', $filter ) . '")';
      // omit non-writing fields
      $where .= ' AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
      
    } else {
      
      $where = 'WHERE g.mode IN ("' . implode( '","', array_keys( \PDb_Manage_Fields::group_display_modes() ) ) . '") ';
      switch ( $filter ) {

        case 'signup':

          $where .= 'AND v.signup = 1 AND v.form_element <> "placeholder"';
          break;

        case 'sortable':

          $where .= 'AND v.sortable = 1 AND v.form_element <> "placeholder"';
          break;

        case 'CSV':

          $where .= 'AND v.CSV = 1 ';
          break;

        case 'all':

          $where .= '';
          break;

        case 'frontend_list':

          $where .= 'AND g.mode = "public" ';
          break;

        case 'frontend': // record and single modules

          $where .= 'AND g.mode = "public" AND v.form_element <> "placeholder"';
          break;

        case 'readonly':

          $where .= 'AND v.group = "internal" OR v.readonly = 1';
          break;

        case 'backend': // used to lay out the admin participant edit/add form
          
          $omit_element_types = Participants_Db::apply_filters('omit_backend_edit_form_element_type', array('captcha','placeholder') );
          $where .= 'AND v.form_element NOT IN ("' . implode('","', $omit_element_types) . '")';
          
          // omit non-writing fields
          $where .= ' AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
          
          if ( !current_user_can( Participants_Db::plugin_capability( 'plugin_admin_capability', 'access admin field groups' ) ) ) {
            // don't show non-displaying groups to non-admin users
            // the "approved" field is an exception; it should be visible to editor users
            $where .= 'AND g.mode <> "admin" OR v.name = "approved"';
          }
          break;

        case 'new':
        default:
          // omit non-writing fields
          $where .= ' AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
          $where .= ' AND v.name <> "id" AND v.form_element <> "captcha"';
      }
    }

    $sql = 'SELECT v.*, g.order, g.title AS grouptitle FROM ' . Participants_Db::$fields_table . ' v INNER JOIN ' . Participants_Db::$groups_table . ' g ON v.group = g.name ' . $where . ' ORDER BY g.order, v.order';
    
    $result = $wpdb->get_results( $sql, OBJECT_K );
    
//    error_log(__METHOD__.' sql: '.$wpdb->last_query);
//    error_log(__METHOD__.' result: '.print_r($result,1));

    return $result;
  }
  
  /**
   * provides a list of columns that have defined default values
   * 
   * @global \wpdb $wpdb
   * @return array of column names
   */
  private function column_default_list()
  {
    global $wpdb;
    $sql = 'SELECT v.name FROM ' . Participants_Db::$fields_table . ' v INNER JOIN ' . Participants_Db::$groups_table . ' g ON v.group = g.name WHERE v.default <> "" AND g.mode IN ("' . implode( '","', array_keys( \PDb_Manage_Fields::group_display_modes() ) ) . '") AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
    
    $list = $wpdb->get_col( $sql );
    
    return $list;
  }
}
