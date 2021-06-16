<?php

/**
 * models a class of fields that save a dynamically-generated value to the db
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

        $post[ $dynamic_db_field->name() ] = $this->dynamic_value( $post );
      }
    }
    return $post;
  }

  /**
   * gets the string combine field list
   * 
   * caches a list of all the string combine fields
   * 
   * @return array of PDb_Form_Field_Def objects
   */
  private function field_list()
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
    $this->field = $field;
    
    return $this->dynamic_value();
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
      
      $field_def = \Participants_Db::get_field_atts( $info[ 'name' ] );

      if ( $field_def->default_value() !== $field_data[ 'default' ] ) {
      
        $this->set_field($field_def);
        $this->field->default = $field_data[ 'default' ];
    
        // do the update
        $this->update_all_records();
      }
    }

    return $field_data;
  }

  /**
   * updates all records with the dynamic value
   * 
   * @global \wpdb $wpdb
   */
  protected function update_all_records()
  {
    global $wpdb;

    $record_list = $wpdb->get_results( 'SELECT p.id, p.' . $this->field->name() . ' FROM ' . \Participants_Db::$participants_table . ' p ORDER BY p.id ASC' );
        
    status_header( 200 );

    foreach ( $record_list as $record ) {
    
      $packet = (object) array( 'record_id' => $record->id, 'field' => $this->field->name(), 'default' => $this->field->default_value() );
      $this->process->push_to_queue( $packet );
    }

    $this->process->save()->dispatch();
  }

  /**
   * updates a record with their dynamic values
   * 
   * @global \wpdb $wpdb
   * @param object $packet
   */
  public function update_record( $packet )
  {
    global $wpdb;
    $this->set_field( $packet->field );
    $this->field->set_record_id( $packet->record_id );
    $this->field->default = $packet->default;
    $value = $this->dynamic_value();
    
    $wpdb->update( \Participants_Db::participants_table(), array( $this->field->name() => $value ), array( 'id' => $packet->record_id ) );
  }

}

class dynamic_value_update extends \WP_Background_Process {

  /**
   * @var string
   */
  protected $action = 'pdb_dynamic_value_update';
  
  /**
   * @var dynamic_db_field the current field type object
   */
  public $dynamic_db_field;

  /**
   * 
   * @param dynamic_db_field $dynamic_db_field
   */
  public function __construct( $dynamic_db_field )
  {
    $this->dynamic_db_field = $dynamic_db_field;
    parent::__construct();
  }

  /**
   * updates the dynamic value for a field
   * 
   * @param object $packet
   */
  protected function task( $packet )
  {
    $this->dynamic_db_field->update_record( $packet );

    return false;
  }

  /**
   * called when all operations are complete
   */
  protected function complete()
  {
    parent::complete();

    do_action( $this->action . '_complete' );
  }

}
