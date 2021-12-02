<?php

/**
 * models a class of fields that save a dynamically-generated value to the db
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

abstract class dynamic_db_field extends core {

  /**
   * @var dynamic_value_update the background processor
   */
  private $process;

  /**
   * sets up the field type
   * 
   * @param string $name of the field type
   * @param string title of the field type
   */
  public function __construct( $name, $title )
  {
    parent::__construct( $name, $title );

    $this->is_dynamic_field();

    $this->field_list(); // cache the field list

    $this->process = new dynamic_value_update( $this );

    add_filter( 'pdb-before_submit_update', array( $this, 'update_db_value' ) );
    add_filter( 'pdb-before_submit_add', array( $this, 'update_db_value' ) );

    add_filter( 'pdb-update_field_def', array( $this, 'maybe_update_database' ), 10, 2 );

    add_action( 'admin_enqueue_scripts', array( $this, 'admin_update_all' ) );

    add_filter( 'pdb-before_list_admin_with_selected_action', array( $this, 'mass_edit_update' ), 10, 2 );

    if ( !has_action( 'pdb-after_import_record', array( $this, 'process_imported_record' ) ) ) {
      add_action( 'pdb-after_import_record', array( $this, 'process_imported_record' ), 10, 3 );
    }
  }

  /**
   * provides the dynamically-generated value
   * 
   * @param array $data the data
   * @return string
   */
  abstract protected function dynamic_value( $data = false );

  /**
   * updates the database value for the field
   * 
   * @param array $post
   * @retrn array
   */
  public function update_db_value( $post )
  {
    foreach ( $this->field_list() as $dynamic_db_field ) {

      /** @var \PDb_Form_Field_Def $dynamic_db_field */
      if ( isset( $post[ $dynamic_db_field->name() ] ) ) {

        $field = new \PDb_Field_Item( $dynamic_db_field );
        $field->set_record_id( $post[ 'id' ] );
        $this->setup_field( $field );

        $this->template = new calc_template( $field, $this->default_format_tag() );

        $post[ $dynamic_db_field->name() ] = $this->dynamic_value( $post );
      }
    }
    return $post;
  }

  /**
   * gets the dynamic db field list
   * 
   * caches a list of all the field types that extend this class
   * 
   * @return array of PDb_Form_Field_Def objects
   */
  protected function field_list()
  {
    $cachekey = $this->name . '_field_list';
    $list = wp_cache_get( $cachekey );

    if ( !is_array( $list ) ) {

      $list = array();

      foreach ( \Participants_Db::$fields as $field ) {
        if ( $field->form_element() === $this->name ) {

          $list[] = $field;
        }
      }

      wp_cache_add( $cachekey, $list );
    }

    return $list;
  }

  /**
   * sets the field property
   * 
   * @param string|\PDb_FormElement $field the incoming field
   */
  protected function set_field( $field )
  {
    $field_item = new \PDb_Field_Item( $field );
    
    $field_item->default = $this->get_field_default( $field_item->name() );

    $this->setup_field( $field_item );

    $this->field->set_readonly();
  }

  /**
   * provides the test for content value
   * 
   * this value is used to determine if the field has content
   * 
   * @param \PDb_Field_Item $field
   * @return string the value to test
   */
  public function has_content_test( $field )
  {
    return $field->value();
//    $this->field = $field;
//    
//    $this->template = new calc_template( $field, $this->default_format_tag() );
//    
//    return $this->dynamic_value();
  }

  /**
   * update the main db if needed
   * 
   * this happens when the "default" parameter of the field is changed, updating 
   * the values in the database using the new template
   * 
   * @param array $field_data the data going into the field def update
   * @param array $info several pieces of information about the field
   * @return array of the field data
   * 
   */
  public function maybe_update_database( $field_data, $info )
  {
    if ( $field_data[ 'form_element' ] === $this->name ) {

      $stored_field_default = $this->get_field_default( $info[ 'name' ] );
      
      if ( $stored_field_default !== $field_data[ 'default' ] ) {
        
        \PDb_Manage_Fields_Updates::clear_field_def_cache();

        $this->set_field( $info[ 'name' ] );
        $this->field->default = $field_data[ 'default' ];

        // do the update
        $this->update_all_records();
      }
    }

    return $field_data;
  }
  
  /**
   * provides the field's default setting
   * 
   * this gets the value directly from the db
   * 
   * @global \wpdb $wpdb
   * @param string $fieldname
   * @return string field default
   */
  private function get_field_default( $fieldname )
  {
    global $wpdb;
    
    return $wpdb->get_var( $wpdb->prepare( 'SELECT f.default FROM ' . \Participants_Db::$fields_table . ' f WHERE f.name = %s', $fieldname ) );
  }

  /**
   * updates all records with the dynamic value
   * 
   * @global \wpdb $wpdb
   */
  protected function update_all_records()
  {
    $this->update_record_list();
  }

  /**
   * updates all records with the dynamic value
   * 
   * @global \wpdb $wpdb
   * @param array $record_id_list list of record ids to update, updates all records if omitted
   */
  protected function update_record_list( $record_id_list = false )
  {
    global $wpdb;

    $where = '';

    if ( is_array( $record_id_list ) ) {
      $where = ' WHERE p.id IN ("' . implode( '","', $record_id_list ) . '")';
    }

    $record_list = $wpdb->get_results( 'SELECT p.id, p.' . $this->field->name() . ' FROM ' . \Participants_Db::$participants_table . ' p ' . $where . ' ORDER BY p.id ASC' );

    status_header( 200 );

    foreach ( $record_list as $record ) {

      $packet = (object) array( 'record_id' => $record->id, 'field' => $this->field->name(), 'default' => $this->field->default_value() );
   
      $this->process->push_to_queue( $packet );
    }

    $this->process->save()->dispatch();
  }

  /**
   * updates a record with the dynamic value
   * 
   * @global \wpdb $wpdb
   * @param object $packet
   */
  public function update_record( $packet )
  {
    $this->set_field( $packet->field );
    $this->field->default = $packet->default;
    $this->field->set_record_id( $packet->record_id );
    
    $value = $this->dynamic_value();

    if ( $value === '' ) {
      $value = null;
    }

    if ( $value !== false ) {
      
      global $wpdb;
      $wpdb->update( \Participants_Db::participants_table(), array( $this->field->name() => $value ), array( 'id' => $packet->record_id ) );

      \Participants_Db::debug_log( __METHOD__ . ' query: ' . $wpdb->last_query, 3 );
    }
  }

  /**
   * forces an administrative regeneration of dynamic field values
   * 
   * called on the admin_enqueue_scripts hook
   * 
   * @param string $hook
   */
  public function admin_update_all( $hook )
  {
    if ( strpos( $hook, 'participants-database' ) !== false && array_key_exists( 'pdb-regenerate-dynamic-fields', $_GET ) ) {

      $rebuild_field = filter_input( INPUT_GET, 'pdb-regenerate-dynamic-fields', FILTER_SANITIZE_STRING . FILTER_NULL_ON_FAILURE );
      $field_list = \PDb_Form_Field_Def::is_field( $rebuild_field ) ? array( new \PDb_Form_Field_Def( $rebuild_field ) ) : $this->field_list();

      foreach ( $field_list as $field ) {
        $this->set_field( $field->name() );
        $this->update_all_records();
      }
    }
  }

  /**
   * checks for the need to update when performing a mass edit on the admin list
   * 
   * @param array $selected_ids the list of selected record ids
   * @param string $action name of the with selected action
   * @return array the list of selected ids
   */
  public function mass_edit_update( $selected_ids, $action )
  {
    $updatable_actions = \Participants_Db::apply_filters( 'dynamic_field_updates_enabled_with_selected_actions', array( 'mass_edit' ) );

    if ( in_array( $action, $updatable_actions ) && count( $selected_ids[ 'pid' ] ) > 0 ) {

      $id_list = $selected_ids[ 'pid' ];

      add_action( 'pdb-admin_list_with_selected_complete', function () use ( $id_list ) {

        foreach ( $this->field_list() as $field ) {
          $this->set_field( $field->name() );
          $this->update_record_list( $id_list );
        }
      } );
    }

    return $selected_ids;
  }

  /**
   * processes an imported record
   * 
   * called on the pdb-after_import_record action
   * 
   * @param array $post the saved data
   * @param int $record_id the record id
   * @param string $status the insert status for the record
   */
  public function process_imported_record( $post, $record_id, $status )
  {
    $data = array();

    foreach ( $this->field_list() as $dynamic_db_field ) {

      /** @var \PDb_Form_Field_Def $dynamic_db_field */
      $field = new \PDb_Field_Item( $dynamic_db_field );
      $field->set_record_id( $record_id );
      $this->setup_field( $field );

      $this->template = new calc_template( $field, $this->default_format_tag() );

      $data[ $dynamic_db_field->name() ] = $this->dynamic_value( $post );
    }
    
    if ( count( $data ) ) {
      \Participants_Db::write_participant( $data, $record_id );
    }
  }

  /**
   * provides the datatype for the column
   * 
   * @global \wpdb $wpdb
   * @param string name of the field
   * @return string string,numeric,float
   */
  protected function field_datatype( $fieldname )
  {
    $types = $this->column_datatypes();

    switch ( true ) {

      case stripos( $types[ $fieldname ], 'decimal' ) !== false:
      case stripos( $types[ $fieldname ], 'int' ) !== false:
        return 'numeric';

      case stripos( $types[ $fieldname ], 'double' ) !== false:
      case stripos( $types[ $fieldname ], 'float' ) !== false:
        return 'float';

      default:
        return 'string';
    }
  }

  /**
   * provides the main db table column datatypes
   * 
   * @return array as $column_name => $datatype
   */
  private function column_datatypes()
  {
    $cachekey = 'pdb-column-datatypes';
    $table = \Participants_Db::participants_table();
    $types = wp_cache_get( $table, $cachekey );

    if ( $types === false ) {

      global $wpdb;

      $sql = 'SELECT COLUMN_NAME , DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = Database()
AND TABLE_NAME = %s';

      $columns = $wpdb->get_results( $wpdb->prepare( $sql, $table ), ARRAY_A );

      $types = array();
      foreach ( $columns as $info ) {
        $types[ $info[ 'COLUMN_NAME' ] ] = $info[ 'DATA_TYPE' ];
      }

      wp_cache_add( $table, $types, $cachekey, DAY_IN_SECONDS );
    }

    return $types;
  }

}
