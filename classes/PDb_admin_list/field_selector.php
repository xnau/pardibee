<?php

/**
 * provides the list filter field selector options
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2025  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;
use \Participants_Db;

class field_selector {

  /**
   * @var array of selector options
   */
  private $options = [];

  /**
   * @var \PDb_admin_list\filter the current filter object
   */
  private $list_filter;
  
  /**
   * builds the options
   */
  public function __construct()
  {
    $this->list_filter = new filter();
    $this->options = $this->field_selector_options();
  }
  
  /**
   * provides the selector options
   * 
   * @return array
   */
  public function options()
  {
    return array_merge( $this->list_filter->recent_field_option_list(), $this->options, search_field_group::group_selector() );
  }
  
  /**
   * provides the options for the sort field selector
   * 
   * @return array
   */
  public function sort_options()
  {
    return $this->options;
  }
  
  /**
   * provides the values for the field selector in the list filter
   * 
   * @return array as $title => $fieldname
   */
  private function field_selector_options()
  {
    $option_list = [];
    $title_list = $this->field_titles();

    foreach ( $this->filter_columns() as $groupname => $field_group ) 
    {
      $option_list[reset($field_group)->grouptitle] = 'optgroup';

      /*
       * since it is possible for there to be more than one field with the same 
       * title, we handle the case of a duplicate title by also showing the name 
       * of the field to differentiate them
       */
      foreach( $field_group as $column ) 
      {
        $title = $title_list[$column->name];
        $dupes = array_keys( $title_list, $title );
        
        switch (count($dupes))
        {
          case 1:
            // we add a space at the end of the field title to avoid possibly matching the group title
            $display_title = $title === '' ? $column->name . ' ' : $title . ' ';
            break;
          
          default:
            $display_title = $title === '' ? $column->name . ' ' : $title . ' (' . $column->name . ')';
        }

        $option_list[ $display_title ] = $column->name;
      }
    }
    
    return $option_list;
  }
  
  /**
   * provides a list of all field titles
   * 
   * @return array as $fieldname => $title
   */
  private function field_titles()
  {
    $titles = [];
    
    foreach( $this->filter_columns() as $field_group )
    {
      foreach( $field_group as $column )
      {
        $titles[$column->name] = Participants_Db::apply_filters( 'translate_string', $column->title );
      }
    }
    
    return $titles;
  }

  /**
   * provides a list columns to use in the list filter and sort
   * 
   * @return array of column definitions
   */
  private function filter_columns()
  {
    add_filter( 'pdb-access_capability', '\PDb_List_Admin::column_filter_user', 10, 2 );
    $columns = $this->search_field_list();
    remove_filter( 'pdb-access_capability', '\PDb_List_Admin::column_filter_user' );
    return $columns;
  }
  
  /**
   * provides the list of searchable fields
   * 
   * @global \wpdb $wpdb
   * @return array of data objects
   */
  private function search_field_list()
  {
    $cachekey = 'pdb-search_field_list';
    
    $field_select = wp_cache_get( $cachekey );
    
    if ( $field_select !== false && !PDB_DEBUG )
    {
      return $field_select;
    }
    
    global $wpdb;
    
    $main_group_list = $wpdb->get_col( 'SELECT `name` FROM ' . Participants_Db::$groups_table . ' WHERE `mode` IN ("public", "private", "admin") ORDER BY CASE `mode` WHEN "public" THEN 1 WHEN "private" THEN 2 WHEN "admin" THEN 3 ELSE `id` END, `order`');
    
    $group_list = array_merge( $main_group_list, $this->additional_groups() );
    
    $where = 'WHERE f.group IN ("' . implode( '","', $group_list ) . '")';
    
    $omit_element_types = Participants_Db::apply_filters('omit_backend_edit_form_element_type', ['captcha','placeholder','heading'] );
    
    $where .= ' AND f.form_element NOT IN ("' . implode('","', $omit_element_types) . '")';

    if ( !\PDb_submission\main_query\columns::editor_can_edit_admin_fields() ) 
    {
      // don't show non-displaying groups to non-admin users
      // the approval field is an exception; it should always be visible to editor users
      $where .= 'AND g.mode <> "admin" OR f.name = "' . \Participants_Db::apply_filters( 'approval_field', 'approved' ) . '"';
    }
    
    $group_db = $wpdb->get_results('SELECT f.name,f.title,f.group,g.title AS grouptitle FROM ' . Participants_Db::$fields_table . ' f INNER JOIN ' . Participants_Db::$groups_table . ' g ON f.group = g.name ' . $where . ' ORDER BY FIELD (f.group,"' . implode( '","',$group_list ) . '")' );
    
    $field_select = [];
    $group = '';
    foreach( $group_db as $field ) 
    {
      if ( $field->group !== $group ) 
      {
        $group = $field->group;
        $grouptitle = empty($field->grouptitle) ? $field->group : $field->grouptitle;
        $field_select[$group] = [];
      }
      $field->grouptitle = $grouptitle;
      $field_select[$group][] = $field;
    }
    
    wp_cache_set( $cachekey, $field_select, '', HOUR_IN_SECONDS );
    
    return $field_select;
  }
  
  /**
   * provides the list of additional groups
   * 
   * these are fields that are enabled and used by add-on plugins
   * 
   * @return array
   */
  private function additional_groups()
  {
    return array_merge( Participants_Db::apply_filters('addon_field_groups', [] ), $this->log_list() );
  }
  
  /**
   * provides the list of participant logs
   * 
   * this will be phased out when the participant log add-on is setting this
   * 
   * @global \wpdb $wpdb
   * @return array
   */
  private function log_list()
  {
    $log_list = [];
    
    if ( ! is_plugin_active( 'pdb-participant_log/participant_log.php' ) )
    {
      return $log_list;
    }
    
    global $wpdb;
    
    $log_list = $wpdb->get_col('SELECT `name` FROM ' . Participants_Db::$fields_table . ' WHERE `form_element` = "participant-log" ORDER BY `order`' );
    
    return $log_list;
  }
}
