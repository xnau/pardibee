<?php

/*
 * class providing CSV file import functionality for the Participants Database
 * plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    0.5
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Participants_Db class, CSV_Import
 *
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_CSV_Import extends xnau_CSV_Import {

  /**
   * @var string name of the duplicate record match field
   * 
   */
  private $match_field;

  /**
   * @var int the current dulicate record preference
   */
  private $match_preference;

  /**
   * @var PDb_CSV_Import_Process instance 
   */
  private $process;

  /**
   * 
   * @param string $file_field_name
   */
  function __construct( $file_field_name )
  {
    $this->i10n_context = Participants_Db::PLUGIN_NAME;

    $this->_set_column_array();

    $this->match_field = filter_input( INPUT_POST, 'match_field', FILTER_SANITIZE_STRING );
    $this->match_preference = filter_input( INPUT_POST, 'match_preference', FILTER_SANITIZE_NUMBER_INT );

    Participants_Db::$session->set( 'form_status', 'normal' ); // CSV import is a normal status
    //
    // get the process controller
    global $PDb_CSV_Import_Process;
    $this->process = $PDb_CSV_Import_Process;

    parent::__construct( $file_field_name );
  }

  /**
   * inserts a series of records from a csv file
   *
   * @param string $src_file the file to parse
   *
   * @return bool success/failure
   * 
   */
  protected function insert_from_csv( $src_file )
  {

    global $wpdb;
    $wpdb->hide_errors();

    if ( empty( $src_file ) || !is_file( $src_file ) ) {

      /* translators: the %s will be the name of the file */
      $this->set_error_heading(
              __( 'Error occured while trying to add the data to the database', 'participants-database' ), sprintf( __( 'Input file does not exist or path is incorrect.<br />Attempted to load: %s', 'participants-database' ), basename( $src_file ) )
      );

      return false;
    }

    $this->CSV = new parseCSV();

    /* this method determines the delimiter automatically then parses the file; 
     * we don't use it because it seems easily confused
     */
    //$this->CSV->auto( $src_file, true, 1, ',' );

    /*
     * we use our own detection algorithms and parse the file based on what we 
     * found
     */
    $this->CSV->delimiter = $this->_detect_delimiter( $src_file );
    $this->CSV->enclosure = $this->_detect_enclosure( $src_file );
    $this->CSV->parse( $src_file );

    if ( PDB_DEBUG and $this->CSV->error )
      error_log( __METHOD__ . ' CSV parse error:' . print_r( $this->CSV->error_info, 1 ) );

    /*
     * build the column names from the CSV if we have one and it's different from 
     * the CSV columns defined by the database
     */
    $this->setup_import_columns();

    $line_num = 1;
    
    $timecheck = microtime(true);

    foreach ( $this->CSV->data as $csv_line ) {

      if ( PDB_DEBUG )
        error_log( __METHOD__ . '
        
columns:' . implode( ', ', $this->column_names ) . '
  
csv line= ' . print_r( $csv_line, true ) );

      $values = array();

      foreach ( $csv_line as $value ) {

        $values[] = $this->process_value( $value );
      }

      if ( count( $values ) != $this->column_count ) {

        $this->set_error( sprintf(
                        __( 'The number of items in line %s is incorrect.<br />There are %s and there should be %s.', 'participants-database' ), $line_num, count( $values ), $this->column_count
                )
        );

        return false;
      }

      // put the keys and the values together into the $post array
      if ( !$post = array_combine( $this->column_names, $values ) ) {
        $this->set_error( __( 'Number of values does not match number of columns', 'participants-database' ) );
      }
      
      $post['match_field'] = $this->match_field;
      $post['match_preference'] = $this->match_preference;

      // put the record data into the queue
      $this->process->push_to_queue( $post );

      $line_num++;
    }
    
//    error_log(__METHOD__.' time to push to queue: '. floatval( microtime(true) ) - floatval( $timecheck ) );

    $this->process->initialize_status_info( array(
        'process_start' => microtime(true),
        'queue_count' => $line_num - 1,
    ) );

    $this->process->save();

//    error_log( __METHOD__ . ' queue saved: ' . print_r( $this->process, 1 ) );

    $result = $this->process->dispatch();

//    error_log( __METHOD__ . ' dispatched result: ' . print_r( $result, 1 ) . '
//      
//status: ' . print_r( get_transient( PDb_CSV_Import_Process::status ), 1 ) );

    return true;
  }

  function _set_column_array()
  {

    $columns = Participants_Db::get_column_atts( 'all' );

    foreach ( $columns as $column ) {

      if ( $column->CSV != '0' )
        $this->column_names[] = $column->name;
    }

    $this->column_count = count( $this->column_names );
  }

  /**
   * takes a raw title row from the CSV and sets the column names array with it
   * if the imported row is different from the plugin's defined CSV columns
   *
   */
  protected function setup_import_columns()
  {

    // build the column names from the CSV if it's there
    if ( is_array( $this->CSV->titles ) and $this->column_names != $this->CSV->titles ) {

      $this->column_names = $this->CSV->titles;

      $this->errors[] = __( 'New columns imported from the CSV file.', 'participants-database' );

      // remove enclosure characters
      array_walk( $this->column_names, array($this, '_enclosure_trim'), $this->CSV->enclosure );

      $this->column_count = count( $this->column_names );
    }
  }

  function _set_upload_dir()
  {

    $this->upload_directory = Participants_Db::files_location();

    // check for the target directory; attept to create if it doesn't exist
    return is_dir( Participants_Db::base_files_path() . $this->upload_directory ) ? true : Participants_Db::_make_uploads_dir( $this->upload_directory );
  }

  /**
   * applies conditioning and escaping to the incoming value, also allows for a filter callback
   * 
   * @param string|int $value
   * @param string $column name of the column
   * @return string
   */
  function process_value( $value, $column = '' )
  {
    $value = maybe_unserialize( $value );
//    $parpared_value = esc_sql( $this->_enclosure_trim( $value, '', $this->CSV->enclosure ) );
    return Participants_Db::apply_filters( 'csv_import_value', $value, $column );
  }

  /**
   * stores the record in the database
   * 
   * @param array $post asscitative srray of imported data
   */
  function store_record( $post )
  {

    /**
     * @version 1.6.3
     * @filter pdb-before_csv_store_record
     */
    $post = Participants_Db::apply_filters( 'before_csv_store_record', $post );

    $post['csv_file_upload'] = 'true';
    $post['subsource'] = Participants_Db::PLUGIN_NAME;
    $post['match_field'] = $this->match_field;
    $post['match_preference'] = $this->match_preference;

    // add the record data to the database
    $id = Participants_Db::process_form( $post, 'insert', false, $this->column_names );

    /**
     * @action pdb-after_import_record
     * @param array $post the saved data
     * @param int $id the record id
     * @param string $insert_status the insert status for the record
     */
    do_action( 'pdb-after_import_record', $post, $id, Participants_Db::$insert_status );

    // count the insert type for the record
    switch ( Participants_Db::$insert_status ) {

      case 'insert' :
        $this->insert_count++;
        break;

      case 'update' :
        $this->update_count++;
        break;

      case 'skip' :
        $this->skip_count++;
        break;

      case 'error' :
        $this->error_count++;
        break;
    }
  }

  /**
   * puts up the user feedback after the import is complete
   */
  public function post_complete_feedback()
  {
    
  }

  /**
   * detect the enclosure character
   *
   * @param string $csv_file path to a csv file to read and analyze
   * @return string the best guess enclosure character
   */
  protected function _detect_enclosure( $csv_file )
  {
    $post_enclosure = filter_input( INPUT_POST, 'enclosure_character', FILTER_SANITIZE_STRING );

    if ( !empty( $post_enclosure ) && $post_enclosure !== 'auto' ) {
      switch ( $post_enclosure ) {
        case '&#39;':
        case "'":
          return "'";
        default:
          return '"';
      }
    } else {
      return parent::_detect_enclosure( $csv_file );
    }
  }

  /**
   * determines the delimiter character in the CSV file
   * 
   * @param string $csv_file the CSV file to scan for a delimiter
   * @return string the delimiter
   */
  protected function _detect_delimiter( $csv_file )
  {
    $post_delimiter = filter_input( INPUT_POST, 'delimiter_character', FILTER_SANITIZE_STRING );
    if ( !empty( $post_delimiter ) && $post_delimiter !== 'auto' ) {
      return $post_delimiter;
    } else {
      return parent::_detect_delimiter( $csv_file );
    }
  }

}
