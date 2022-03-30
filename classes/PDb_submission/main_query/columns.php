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

defined( 'ABSPATH' ) || exit;

class columns {
  
  /**
   * @var array of column objects
   */
  private $column_array;
  
  /**
   * @param string $action update or insert
   * @param array $columns optional list of column names
   */
  private function __construct( $action, $columns )
  {
    if ( is_array( $columns ) ) {
      // shortcode with "fields" attribute or function call
      
      $default_cols = array();
      
      if ( $action === 'insert' ) {
        
        $main_query = base_query::instance();
        
        /**
         * switch to enable filling in a new record with default values
         * @filter pdb-process_form_fill_default_values
         * @param bool
         * @return bool
         */
        // intent here is to add the default values with a signup submission or an import add only
        if ( ( $main_query->is_import() || !$main_query->is_func_call() ) && Participants_Db::apply_filters( 'process_form_fill_default_values', true ) ) {
          $default_cols = array_merge( self::column_default_list(), array('private_id') );
        } else {
          $default_cols = array('private_id');
        }
      }
      
      $column_set = array_merge( $columns, $default_cols );
      
    } else {
      
      $column_set = 'all';
      
      /**
       * provides a way force a signup
       * 
       * used in pdb-member_payments/pdbmps/Ajax.php:54:pdb-post_action_override
       * 
       * @filter pdb-post_action_override
       * @param the current $_POST action value
       * @return the action value to use: either "signup" or "update"
       */
      if ( Participants_Db::apply_filters( 'post_action_override', filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING ) ) === 'signup' ) {

        $column_set = 'signup';
        
      } elseif ( $action === 'update' ) {

        $column_set = Participants_Db::is_admin() ? 'backend' : 'frontend' ;

      } elseif ( $action === 'insert' ) {
        
        $column_set = 'new';
      }
    }
    
    $this->column_array = self::column_atts( $column_set );
      
//    $array = [];
//    foreach( $this->column_array as $column ) {
//      $array[] = $column->name;
//    }
    
  }
  
  /**
   * provides the column array for use in the iterator
   * 
   * @param string $action
   * @param array $columns optional list of column names
   * @return array
   */
  public static function column_array( $action, $columns = false )
  {
    $columns = new self( $action, $columns );
    
    return $columns->column_array;
  }
  
  /**
   * provides a column instance
   * 
   * @param object $column_def raw database object
   * @param string $value
   * @return \PDb_submission/main_query/internal_column|\PDb_submission/main_query/user_column
   */
  public static function get_column_object( $column, $value )
  {
    $internal_fields = array('id','date_recorded','date_updated','last_accessed','private_id');
    
    if ( in_array( $column->name, $internal_fields ) ) {
      return new internal_column( $column, $value );
    } else {
      return new user_column( $column, $value );
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

          $where .= 'AND v.signup = 1';
          $where .= ' OR v.name IN ("' . implode( '","', self::column_default_list() ) . '") ';
          $where .= ' OR v.name = "private_id" ';
          
          break;

        case 'sortable':

          $where .= 'AND v.sortable = 1 AND v.form_element <> "placeholder"';
          break;

        case 'CSV':
          
          $where .= 'AND v.CSV = 1 AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
          
          break;

        case 'all':

          $where .= '';
          break;

        case 'frontend_list':

          $where .= 'AND g.mode = "public" ';
          break;

        case 'frontend': // record module

          $where .= 'AND g.mode IN ("public","private") AND v.form_element NOT IN ("placeholder","captcha")';
          // omit non-writing fields
          $where .= ' AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
          break;

        case 'readonly':

          $where .= 'AND v.group = "internal" OR v.readonly = 1';
          break;

        case 'backend': // used to lay out the admin participant edit/add form, also the admin list participants page
          
          $omit_element_types = Participants_Db::apply_filters('omit_backend_edit_form_element_type', array('captcha','placeholder','heading') );
          $where .= 'AND v.form_element NOT IN ("' . implode('","', $omit_element_types) . '")';
          
          if ( ! self::editor_can_edit_admin_fields() ) {
            // don't show non-displaying groups to non-admin users
            // the approval field is an exception; it should always be visible to editor users
            $where .= 'AND g.mode <> "admin" OR v.name = "' . \Participants_Db::apply_filters( 'approval_field', 'approved' ) . '"';
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
    
//    error_log(__METHOD__.' filter: "'.$filter.'" sql: '.$wpdb->last_query);
//    error_log(__METHOD__.' result: '.print_r($result,1));

    return $result;
  }
  
  /**
   * provides a list of columns that have defined default values
   * 
   * @global \wpdb $wpdb
   * @return array of column names
   */
  private static function column_default_list()
  {
    global $wpdb;
    $sql = 'SELECT v.name FROM ' . Participants_Db::$fields_table . ' v INNER JOIN ' . Participants_Db::$groups_table . ' g ON v.group = g.name WHERE v.default <> "" AND g.mode IN ("' . implode( '","', array_keys( \PDb_Manage_Fields::group_display_modes() ) ) . '") AND v.name IN ("' . implode( '","', Participants_Db::table_columns() ) . '") ';
    
    $list = $wpdb->get_col( $sql );
    
    return $list;
  }
  
  /**
   * tells if a plugin editor can edit administrative fields
   * 
   * @return bool
   */
  private static function editor_can_edit_admin_fields()
  {
    return current_user_can( Participants_Db::plugin_capability( 'plugin_admin_capability', 'access admin field groups' ) ) || \Participants_Db::plugin_setting_is_true( 'editor_allowed_edit_admin_fields', false );
  }
}
