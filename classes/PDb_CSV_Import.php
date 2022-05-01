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
 * @version    1.0
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
   * @var int the current duplicate record preference
   */
  private $match_preference;
  
  /**
   * @var \PDb_import\process instance
   */
  private $process;

  /**
   * 
   * @param string $file_field_name
   */
  public function __construct( $file_field_name )
  {
    $this->set_column_array();

    $this->match_field = filter_input( INPUT_POST, 'match_field', FILTER_SANITIZE_SPECIAL_CHARS );
    $this->match_preference = filter_input( INPUT_POST, 'match_preference', FILTER_SANITIZE_SPECIAL_CHARS);

    Participants_Db::$session->set( 'form_status', 'normal' ); // CSV import is a normal status
    
    // get the process instance from \PDb_import\controller
    $this->process = Participants_Db::apply_filters( 'get_import_process', false );

    parent::__construct( $file_field_name );
    
    $this->check_for_report();
  }
  
  /**
   * checks for a background process report and adds it to the error heading
   */
  public function check_for_report()
  {
    $tally = \PDb_import\tally::get_instance();
    
    if ( $tally->has_report() ) {
      $this->set_error( $tally->report(), false, 'progress_report' );
    }
  }
  
  /**
   * supplies the import error messages
   * 
   * @return array
   */
  public function get_errors()
  {
    return $this->errors;
  }
  
  /**
   * supplies the import error messages
   * 
   * @return bool
   */
  public function has_errors()
  {
    return is_array( $this->errors ) && count( $this->errors ) > 0;
  }
  
  /**
   * provides the columns names array
   * 
   * @return array
   * 
   */
  public function column_names()
  {
    return $this->column_names;
  }
  
  /**
   * provides the number of columns
   * 
   * @return int
   * 
   */
  public function column_count()
  {
    return $this->column_count;
  }
  
  /**
   * provides the configured export columns
   * 
   * @return array of field names
   */
  public function export_columns()
  {
    $columns = Participants_Db::get_column_atts( 'all' );
    $export_columns = array();

    foreach ( $columns as $column ) {

      if ( $column->CSV )
        $export_columns[] = $column->name;
    }
    
    return $export_columns;
  }

  /**
   * sets up the column name array
   * 
   * the defaults to the configured export columns
   *
   */
  protected function set_column_array()
  {
    $this->column_names = $this->export_columns();

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
    if ( is_array( $this->CSV->titles ) && $this->column_names != $this->CSV->titles ) {

      $this->column_names = $this->get_column_names();

      // remove enclosure characters
      array_walk( $this->column_names, array( $this, '_enclosure_trim' ), $this->CSV->enclosure );

      $this->column_count = count( $this->column_names );
    }
    
    $this->process->setup( $this->column_names, $this->match_preference, $this->match_field );
  }
  
  /**
   * provides the set of validated import columns
   * 
   * @return array of column names
   */
  protected function get_column_names()
  {
    $columns = array();
    $bad_columns = array();
    
    foreach ( $this->CSV->titles as $fieldname ) {
      
      if ( PDb_Form_Field_Def::is_field( $fieldname ) ) {
        $columns[] = $fieldname;
      } else {
        $bad_columns[] = $fieldname;
      }
    }
    
    if ( count( $bad_columns ) > 0 ) {
      
      $this->set_error( _n( 'Incorrect column name found in CSV:', 'Incorrect column names found in CSV:', count($bad_columns), 'participants-database' ) . ' ' . implode( ', ', $bad_columns ), true, 'incorrect_column' );
    }
    
    return $columns;
  }

  /**
   * verifies the uploads directory and creates it if needed
   *
   * @return bool true if the directory can be used
   */
  protected function verify_upload_dir()
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
  protected function process_value( $value, $column = '' )
  {
    return Participants_Db::apply_filters( 'csv_import_value', $this->_enclosure_trim( $value, '', $this->CSV->enclosure ), $column );
  }
  
  /**
   * inserts the record data from the CSV file
   *
   * @param string $src_file the file to parse
   *
   * @return bool success/failure
   * 
   */
  protected function insert_from_csv($src_file)
  {
    $success = parent::insert_from_csv($src_file);
    
    if ( $success && $this->is_background_import() ) {
      
      \PDb_import\tally::get_instance()->reset();
      
      $this->process->save()->dispatch();
    }
    
    return $success;
  }

  /**
   * stores the record in the database
   * 
   * @param array $post array of imported data
   */
  protected function store_record( $post )
  {
    /**
     * @version 1.6.3
     * @filter pdb-before_csv_store_record
     */
    $post = Participants_Db::apply_filters( 'before_csv_store_record', $post );

    $this->process->push_to_queue( array_values( $post ) );
  }

  /**
   * detect the enclosure character
   *
   * @param string $csv_file path to a csv file to read and analyze
   * @return string the best guess enclosure character
   */
  protected function _detect_enclosure( $csv_file )
  {
    $post_enclosure = filter_input( INPUT_POST, 'enclosure_character', FILTER_SANITIZE_SPECIAL_CHARS );

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
    $post_delimiter = filter_input( INPUT_POST, 'delimiter_character', FILTER_SANITIZE_SPECIAL_CHARS );
    if ( !empty( $post_delimiter ) && $post_delimiter !== 'auto' ) {
      return $post_delimiter;
    } else {
      return parent::_detect_delimiter( $csv_file );
    }
  }
}
