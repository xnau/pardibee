<?php

/**
 * models a class of fields that save a dynamically-generated value to the db
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

defined( 'ABSPATH' ) || exit;

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
    
    // general filter for updating a single record
    add_filter( 'pdb-dynamic_db_field_update', array( $this, 'update_db_value' ) );

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
   * provides the name of the data table
   * 
   * @return string
   */
  protected function data_table()
  {
    return \Participants_Db::apply_filters( 'dynamic_db_field_database_table', \Participants_Db::participants_table(), $this->field );
  }

  /**
   * updates the database value for the field
   * 
   * called on pdb-before_submit_add or pdb-before_submit_update
   * 
   * @param array $post the submission
   * @return array
   */
  public function update_db_value( $post )
  {
    foreach ( $this->field_list() as $dynamic_db_field ) {
      
      /** @var \PDb_Form_Field_Def $dynamic_db_field */
      $fieldname = $dynamic_db_field->name();

      $field = $this->field_object( $dynamic_db_field, $post['id'], $post );
      
      $this->setup_field( $field );

      $this->template = new calc_template( $field, $this->default_format_tag() );

      $dynamic_value = $this->dynamic_value( $post );

      if ( isset( $post[ $fieldname ] ) ) {
        $post[ $fieldname ] = $dynamic_value;
      }

      if ( isset( $_POST[ $fieldname ] ) ) {
        $_POST[ $fieldname ] = $dynamic_value;
      }
    }
    
    return $post;
  }
  
  /**
   * updates a list of records
   * 
   * @param array $record_id_list
   */
  public function update_dynamic_fields( $record_id_list ) 
  {
    $this->update_record_list($record_id_list);
  }
  
  /**
   * provides the field item object
   * 
   * @param \PDb_Form_Field_Def|string|\PDb_FormElement $field object
   * @param int $record_id
   * @param array $data (optional) additional data 
   * @return \PDb_Field_Item object
   */
  protected function field_object( $field, $record_id, $data = array() )
  {
    $field_obj = new \PDb_Field_Item( $field, $record_id );
    
    /**
     * @filter pdb-dynamic_db_internal_field_object
     * @param \PDb_Field_Item the newly instantiated field object
     * @param array additional data
     * @return object
     */
    return \Participants_Db::apply_filters('dynamic_db_internal_field_object',  $field_obj, $data  );
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
   * @param int $record_id optionally supply the record id
   */
  protected function set_field( $field, $record_id = 0 )
  {
    if ( is_object( $field ) && isset( $field->record_id ) ) {
      $record_id = $field->record_id;
    }
    
    $field_item = $this->field_object( $field, $record_id );
    
    $field_item->default = $this->get_template_def( $field_item->name() );

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
    if ( isset( $field_data[ 'form_element' ] ) && $field_data[ 'form_element' ] === $this->name ) {

      $template_def = $this->get_template_def( $info[ 'name' ] );
      
      if ( $template_def !== $field_data[ 'default' ] || $this->get_attributes_def( $info[ 'name' ] ) !== maybe_unserialize( $field_data['attributes'] ) ) {
        
        $field = new \PDb_Field_Item( $info );
        
        $field->default = $field_data[ 'default' ];
        
        $calc_template = new calc_template(  $field, $this->default_format_tag() );
        
        if ( ! $calc_template->is_valid_template() ) {
          
          \Participants_Db::set_admin_message( sprintf( __( 'The "Template" setting for the %s field could not be validated. Details here: %s', 'participants-database' ), $field->title(), '<a href="https://xnau.com/work/wordpress-plugins/participants-database/participants-database-documentation/field-types/participant-database-calculation-fields/" target="_blank" >Calculation Fields</a>' ), 'error' );
          
          $field_data['default'] = $template_def;
          
          return $field_data;
        }
        
        \PDb_Manage_Fields_Updates::clear_field_def_cache();

        $this->set_field( $info[ 'name' ] );
        $this->field->default = $calc_template->calc_template();
        $field_data['default'] = $calc_template->raw_template_string();
        
        // do the update
        $this->update_all_records();
      }
    }

    return $field_data;
  }
  
  /**
   * provides the default format tag
   * 
   * @return string
   */
  protected function default_format_tag()
  {
    return $this->unformatted_tag();
  }
  
  /**
   * provides the default format tag
   * 
   * @return string
   */
  protected function unformatted_tag()
  {
    return '[?unformatted]';
  }
  
  /**
   * provides the field's stored default setting
   * 
   * @param string $fieldname
   * @return string field default
   */
  private function get_template_def( $fieldname )
  {
    return $this->get_field_db_value( $fieldname, 'default' );
  }
  
  /**
   * provides the field's stored default setting
   * 
   * @param string $fieldname
   * @return array field default
   */
  private function get_attributes_def( $fieldname )
  {
    return maybe_unserialize( $this->get_field_db_value( $fieldname, 'attributes' ) );
  }
  
  /**
   * provides the stored field def value
   * 
   * this gets the value directly from the db
   * 
   * @global \wpdb $wpdb
   * @param string $fieldname name of the field
   * @param string $column name of the field def column value to get
   * @return string field def value
   */
  private function get_field_db_value( $fieldname, $column )
  {
    $cachekey = 'pdb-field_' . $column;
    
    $def_value = wp_cache_get( $fieldname, $cachekey );
    
    if ( $def_value === false ) {
      global $wpdb;

      $def_value = $wpdb->get_var( $wpdb->prepare( 'SELECT f.' . $column . ' FROM ' . \Participants_Db::$fields_table . ' f WHERE f.name = %s', $fieldname ) );
      
      wp_cache_set( $fieldname, $def_value, $cachekey );
    }
    
    return $def_value;
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
   * @param bool $dispatch_now if true, dispatches the background queue immediately
   */
  protected function update_record_list( $record_id_list = false, $dispatch_now = true )
  {
    global $wpdb;

    $where = '';

    if ( is_array( $record_id_list ) ) {
      $where = ' WHERE p.id IN ("' . implode( '","', $record_id_list ) . '")';
    }

    $record_list = $wpdb->get_results( 'SELECT p.id, p.' . $this->field->name() . ' FROM ' . $this->data_table() . ' p ' . $where . ' ORDER BY p.id ASC' );

    status_header( 200 );

    foreach ( $record_list as $record ) {
      
      $packet_array = array( 
          'record_id' => $record->id, 
          'field' => $this->field->name(), 
          'default' => $this->field->default_value() 
              );
      /**
       * @filter pdb-dynamic_db_field_background_update_packet
       * @param array the packet data
       * @param object the db data for the record
       * @param \PDb_Field_Item the current field object
       * @return array the packet data
       */
      $packet = (object) \Participants_Db::apply_filters( 'dynamic_db_field_background_update_packet', $packet_array, $record, $this->field );
   
      $this->process->push_to_queue( $packet );
    }
    
    if ( $dispatch_now ) {
      $this->save_and_dispatch();
    }
  }
  
  /**
   * dispatches the process
   * 
   */
  public function save_and_dispatch()
  {
    $this->process->save();

//    global $wpdb;
//    $result = $wpdb->get_results('SELECT option_value,option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE "%' . $this->process->action . '%"' );
//    $queue = current($result);
//    error_log(__METHOD__.'
//      
//queue name: '.$queue->option_name.'
//  
//queue: '. print_r( maybe_unserialize( $queue->option_value ),1));
    
    $this->process->dispatch();
  }

  /**
   * updates a record with the dynamic value
   * 
   * @global \wpdb $wpdb
   * @param object $packet
   */
  public function update_record( $packet )
  {
    do_action('pdb-dynamic_db_field_background_record_update', $packet );
    
    $this->set_field( $packet->field, $packet->record_id );
    $this->field->default = $packet->default;
    
    $value = $this->dynamic_value();

    if ( $value === '' ) {
      $value = null;
    }

    if ( $value !== false ) {
      
      global $wpdb;
      $wpdb->update( $this->data_table(), array( $this->field->name() => $value ), array( 'id' => $packet->record_id ) );

      $debug_level = is_null( $value ) ? 3 : 1;
      \Participants_Db::debug_log( __METHOD__ . ' query: ' . $wpdb->last_query, $debug_level );
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
    $getkey = 'pdb-regenerate-dynamic-fields';
    if ( strpos( $hook, 'participants-database' ) !== false && array_key_exists( $getkey, $_GET ) ) {

      $rebuild_field = filter_input( INPUT_GET, $getkey, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE );
      
      if ( empty( $rebuild_field ) ) {
        
        $field_list = $this->field_list();
        
      } elseif ( \PDb_Form_Field_Def::is_field( $rebuild_field ) ) {
        
        $field = new \PDb_Form_Field_Def( $rebuild_field );
        
        if ( $field->form_element() !== $this->name ) {
          return;
        }
        $field_list = array( $field );
        
      } else {
        
        return;
      }

      foreach ( $field_list as $field ) {
        $this->set_field( $field->name() );
        $this->update_record_list( false, false );
      }
      
      $this->save_and_dispatch();
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
          $this->update_record_list( $id_list, false );
        }
        
      }, 1 );
      
      if ( ! has_action( 'pdb-admin_list_with_selected_complete', array( $this, 'save_and_dispatch' ) ) ) {
        add_action( 'pdb-admin_list_with_selected_complete', array( $this, 'save_and_dispatch' ), 100 );
      }
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
    $match_prefs = array_intersect_key( $post, array( 'match_preference' => '', 'match_field' => '' ) );
    $data = array();

    foreach ( $this->field_list() as $dynamic_db_field ) {

      /** @var \PDb_Form_Field_Def $dynamic_db_field */
      $field = $this->field_object( $dynamic_db_field, $record_id, $post );
      
      $this->setup_field( $field );

      $this->template = new calc_template( $field, $this->default_format_tag() );

      $data[ $dynamic_db_field->name() ] = $this->dynamic_value( $post );
    }
    
    if ( count( $data ) ) {
      \Participants_Db::write_participant( array_merge( $data, $match_prefs ), $record_id );
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
    $table = $this->data_table();
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
