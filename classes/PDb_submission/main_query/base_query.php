<?php

/**
 * maintains the query for the main record insert or update
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

namespace PDb_submission\main_query;

defined( 'ABSPATH' ) || exit;

use \Participants_Db as PDB;

if ( !defined( 'ABSPATH' ) )
  exit;

abstract class base_query {

  /**
   * @var array of column values
   */
  protected $values;

  /**
   * @var array of column clause strings
   */
  protected $column_clauses = array();

  /**
   * @var array the posted data
   */
  protected $post;

  /**
   * @var int holds the record id
   */
  protected $record_id;

  /**
   * @var bool tells if the current request is a CSV import
   */
  protected $is_import;

  /**
   * @var bool tells if the current request is a CSV import
   */
  protected $is_func_call;

  /**
   * @var PDb_submission\main_query\base_query
   */
  private static $instance;

  /**
   * provides a class instance
   * 
   * @param string $type update, insert or skip
   * @param array $post
   * @param int $record_id
   * @param bool $func_call true if the submission is a function call
   * @return PDb_submission\main_query\base_query
   */
  public static function get_instance( $type, $post, $record_id, $func_call )
  {
    $class = "\PDb_submission\main_query\\{$type}_query";
    self::$instance = new $class( $post, $record_id, $func_call );

    return self::$instance;
  }

  /**
   * provides the class instance without specifying parameters
   * 
   * @return  PDb_submission\main_query\base_query
   */
  public static function instance()
  {
    return self::$instance;
  }

  /**
   * @param array $post
   * @param int $record_id
   * @param bool $func_call true if getting called by a function instead of a form submission
   */
  public function __construct( $post, $record_id, $func_call )
  {
    $this->post = $post;
    $this->record_id = $record_id;
    $this->is_import = self::is_csv_import() || isset( $post['csv_file_upload'] );
    $this->is_func_call = $func_call;
  }

  /**
   * provides the query top clause
   * 
   * @return string
   */
  abstract protected function top_clause();

  /**
   * provides the query where clause
   * 
   * @return string
   */
  abstract protected function where_clause();

  /**
   * provides the name of the query mode: update or insert
   * 
   * @return string
   */
  abstract protected function query_mode();
  
  /**
   * tells if the current request is for a CSV import
   * 
   * @return bool
   */
  public static function is_csv_import()
  {
    return filter_input( INPUT_POST, 'csv_file_upload', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
  }

  /**
   * provides the current query mode
   * 
   * @return string update or insert
   */
  public function write_mode()
  {
    return $this->query_mode();
  }

  /**
   * provides the column object array
   * 
   * @param array $function_columns
   * @return array
   */
  public function column_array( $function_columns )
  {
    return columns::column_array( $this->query_mode(), $function_columns );
  }

  /**
   * provides the completed query
   * 
   * @return string
   */
  public function query()
  {
    return $this->top_clause() . $this->data_clause() . $this->where_clause();
  }

  /**
   * provides the query header
   * 
   * @return string
   */
  public function query_head()
  {
    return $this->top_clause();
  }
  
  /**
   * tells the number of columns that are getting added/modified by the query
   * 
   * @return int
   */
  public function column_count()
  {
    return count( $this->column_clauses );
  }

  /**
   * provides the data body of the query
   * 
   * @return string
   */
  protected function data_clause()
  {
    return implode( ', ', $this->column_clauses );
  }

  /**
   * provides the query header
   * 
   * @return string
   */
  public function query_where()
  {
    return $this->where_clause();
  }

  /**
   * executes the query
   * 
   * @global \wpdb $wpdb
   * @return int record id
   */
  public function execute_query()
  {
    if ( has_filter( PDB::$prefix . 'process_form_query_column_data' ) ) {
      /**
       * provides a way to alter the data structure before going into the query
       * 
       * @filter pdb-process_form_query_column_data
       * @param array as $insert_template => $value
       * @return array
       */
      $data = PDB::apply_filters( 'process_form_query_column_data', array_combine( $this->column_clauses, $this->values ) );
      $this->column_clauses = array_keys( $data );
      $this->values = array_values( $data );
    }

    global $wpdb;

    // run the query
    $result = $wpdb->query( $this->sanitized_query() );

    if ( $result === false ) {
      PDB::debug_log( __METHOD__ . ' record store error: ' . $wpdb->last_error . ' for query: '.$wpdb->last_query );
    } elseif ( $result > 0 ) {
      
      PDB::debug_log( __METHOD__ . ' storing record: ' . $wpdb->last_query );
    }

    $db_error_message = '';
    if ( $result === 0 ) {
      
      if ( !$this->is_import && !$this->is_func_call ) {
        $db_error_message = sprintf( PDB::$i18n[ 'zero_rows_error' ], $wpdb->last_query );
      }
      PDB::$insert_status = 'skip';
    } elseif ( $result === false ) {
      $db_error_message = sprintf( PDB::$i18n[ 'database_error' ], $wpdb->last_query, $wpdb->last_error );
      PDB::$insert_status = 'error';
    } else {
      // is it a new record?
      if ( $this->query_mode() === 'insert' ) {

        // get the new record id for the return
        $this->record_id = $wpdb->insert_id;

        /*
         * is this record a new one created in the admin? This also applies to CSV 
         * imported new records
         */
        if ( PDB::is_admin() ) {
          // if in the admin hang on to the id of the last record for an hour
          set_transient( PDB::$last_record, $this->record_id, HOUR_IN_SECONDS );
        }
      }
    }

    /*
     * set up user feedback
     */
    if ( PDB::is_admin() ) {
      if ( !$this->is_import && !$this->is_func_call && $result ) {
        PDB::set_admin_message( ($this->query_mode() === 'insert' ? PDB::$i18n[ 'added' ] : PDB::$i18n[ 'updated' ] ), 'updated' );
      }
      if ( !empty( $db_error_message ) ) {
        PDB::set_admin_message( PDB::db_error_message( $db_error_message ), 'error' );
      }
    }

    return $this->record_id;
  }

  /**
   * adds a column to the data arrays
   * 
   * @param string $value the column value
   * @param string $clause the column query clause
   */
  public function add_column( $value, $clause )
  {
    $this->values[] = $value;
    $this->column_clauses[] = $clause;
  }

  /**
   * validates a column submission
   * 
   * @param base_column $field the submitted field object
   * @param object $column the column object
   */
  public function validate_column( $field, $column )
  {
    // validation is only performed on form submissions
    if ( is_object( PDB::$validation_errors ) && !$this->is_import() && !$this->is_func_call() ) {
      PDB::$validation_errors->validate( PDB::deep_stripslashes( $field->validation_value() ), $column, $this->post, $this->record_id );
    }
  }

  /**
   * tells if there are validation errors
   * 
   * @return bool true if there are validation errors
   */
  public function has_validation_errors()
  {
    $has_errors = false;

    if ( is_object( PDB::$validation_errors ) && PDB::$validation_errors->errors_exist() ) {

//      error_log( __METHOD__.' errors exist; returning: '.print_r(self::$validation_errors->get_validation_errors(),1));

      $has_errors = true;
    } elseif ( PDB::has_admin_message() && 'error' === PDB::admin_message_type() ) {
      PDB::debug_log( __METHOD__ . ' admin error message set; returning: ' . PDB::admin_message_content(), 3 );
      $has_errors = true;
    }

    return $has_errors;
  }

  /**
   * provides the record ID
   * 
   * @return int
   */
  public function record_id()
  {
    return $this->record_id;
  }

  /**
   * tells if the record is a new record
   * 
   * @return bool
   */
  public function is_new()
  {
    return $this->record_id == 0;
  }

  /**
   * tells if the request is an import
   * 
   * @return bool
   */
  public function is_import()
  {
    return $this->is_import;
  }

  /**
   * tells if the current request is a function call
   * 
   * @return bool
   */
  public function is_func_call()
  {
    return $this->is_func_call;
  }

  /**
   * provides the column value
   * 
   * @return string
   */
  public function column_value( $column_name )
  {
    $value = isset( $this->post[ $column_name ] ) ? $this->post[ $column_name ] : '';

    if ( $this->query_mode() === 'insert' && !isset( $this->post[ $column_name ] ) ) {
      $field = new \PDb_Form_Field_Def( $column_name );
      $value = $field->default_display();
    }

    return $value;
  }

  /**
   * provides the default record values
   * 
   * this also includes persistent values
   * 
   * @return array as $fieldname => $default_value
   */
  protected function default_record()
  {
    return PDB::get_default_record( $this->is_import === false );
  }

  /**
   * provides the default value for the named field
   * 
   * @param string $fieldname
   * @return string
   */
  public function default_value( $fieldname )
  {
    return isset( $this->default_record()[ $fieldname ] ) ? $this->default_record()[ $fieldname ] : '';
  }

  /**
   * provides the sanitized query
   * 
   * @global \wpdb $wpdb
   * @return string
   */
  protected function sanitized_query()
  {
    $query = $this->query();

    // check if the query has placeholders
    if ( strpos( $query, '%s' ) !== false ) {

      global $wpdb;

      // remove null values from the values array
      $values = array_filter( $this->values, function ($v) {
        return !is_null( $v );
      } );

      $query = $wpdb->prepare( $query, $values );
    }

    return $query;
  }

}
