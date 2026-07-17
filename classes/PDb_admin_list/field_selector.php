<?php

/**
 * provides the list filter field selector options
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2025  xnau webdesign
 * @license    GPL3
 * @version    1.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
namespace PDb_admin_list;

defined( 'ABSPATH' ) || exit;

use \Participants_Db;

class field_selector {

  /**
   * @var array of selector options
   */
  protected $options = [];

  /**
   * @var \PDb_admin_list\filter the current filter object
   */
  protected $list_filter;
  
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
    return array_merge( $this->list_filter->recent_field_option_list(), search_field_group::group_selector(), $this->options );
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

    foreach ( $this->filter_columns() as $groupname => $group_field_list ) 
    {
      $option_list[reset($group_field_list)->grouptitle] = 'optgroup';

      /*
       * since it is possible for there to be more than one field with the same 
       * title, we handle the case of a duplicate title by also showing the name 
       * of the field to differentiate them
       */
      foreach( $group_field_list as $column ) 
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
    
    foreach( $this->filter_columns() as $group_field_list )
    {
      foreach( $group_field_list as $column )
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
    
    $field_select = [];
    $group = '';
    foreach( $this->searchable_field_list() as $field ) 
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
   * provides a list of searchable fields
   * 
   * @global $wpdb \wpdb
   * @return array
   */
  private function searchable_field_list()
  {
    $cachekey = 'pdb-searchable_field_list';
    
    $group_db = wp_cache_get($cachekey);
    
    if ( $group_db !== false )
    {
      return $group_db;
    }
    
    global $wpdb;
    
    $main_group_list = $wpdb->get_col( $wpdb->prepare( 'SELECT `name` FROM %i WHERE `mode` IN ("public", "private", "admin") ORDER BY CASE `mode` WHEN "public" THEN 1 WHEN "private" THEN 2 WHEN "admin" THEN 3 ELSE `id` END, `order`', Participants_Db::$groups_table ) );
    
    $group_list = array_merge( $main_group_list, $this->additional_groups() );
    
    $group_list_placeholder = array_fill( 0, count( $group_list ), '%s' );
    
    $where = 'WHERE f.group IN (' . implode( ',', $group_list_placeholder ) . ')';
    
    $omit_element_types = $this->omit_element_types();
    $element_types_placeholder = array_fill( 0, count( $omit_element_types ), '%s' );
    
    $where .= ' AND f.form_element NOT IN (' . implode(',', $element_types_placeholder ) . ')';
    
    $args = array_merge( [Participants_Db::$fields_table,Participants_Db::$groups_table], $group_list, $omit_element_types );

    if ( !\PDb_submission\main_query\columns::editor_can_edit_admin_fields() ) 
    {
      // don't show non-displaying groups to non-admin users
      // the approval field is an exception; it should always be visible to editor users
      $where .= 'AND g.mode <> "admin" OR f.name = %s';
      $args = array_merge( $args, [\Participants_Db::apply_filters( 'approval_field', 'approved' )] );
    }
    
    $args = array_merge( $args, $group_list );
  
    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber 
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared 
    $group_db = $wpdb->get_results( $wpdb->prepare( 'SELECT f.name,f.title,f.group,g.title AS grouptitle FROM %i f INNER JOIN %i g ON f.group = g.name ' . $where . ' ORDER BY FIELD (f.group,' . implode( ',',$group_list_placeholder ) . '), f.order ASC', $args ) ); 
    
    wp_cache_set( $cachekey, $group_db, '', \Participants_Db::cache_expire() );
    
    return $group_db;
  }
  
  /**
   * provides a list of form element types to omit from the selector
   * 
   * @return array
   */
  protected function omit_element_types()
  {
    return Participants_Db::apply_filters('omit_backend_edit_form_element_type', ['captcha','placeholder','heading'] );
  }
  
  /**
   * provides the list of additional groups
   * 
   * these are fields that are enabled and used by add-on plugins
   * 
   * @return array
   */
  protected function additional_groups()
  {
    /**
     * adds field groups created by add-on plugins
     * 
     * @filter pdb-addon_field_groups
     * @param array
     * @return array
     */
    return Participants_Db::apply_filters('addon_field_groups', [] );
  }
}
