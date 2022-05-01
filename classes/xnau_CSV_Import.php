<?php

/*
 * class providing CSV file import functionality
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    1.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    parseCSV class
 *
 * this class is given a $_POST field name for an uploaded file and an optional
 * list of target columns. it parses the file contents as a CSV-format text file
 * and imports the records, matching the fields with the provided columns. Each
 * line of the CSV can be read from the object for storage or other use.
 *
 */
if ( ! defined( 'ABSPATH' ) ) die;

abstract class xnau_CSV_Import {

  /**
   * @var array all the valid column names in the receiving database
   */
  protected $column_names;
  
  /**
   *
   * @var int number of valid columns
   */
  protected $column_count;
  
  /**
   * @var string holds the path to the target location for the uploaded file 
   */
  protected $upload_directory;
  
  /**
   * @var array holds any errors or confirmation messages
   */
  protected $errors;
  
  /**
   *  @var string status of the error message
   */
  public $error_status = 'updated';
  
  /**
   * @var int the number of lines imported
   */
  protected $lines;
  
  /**
   * @var object the parseCSV instance
   */
  protected $CSV;
  
  /**
   * @var string name of the nonce
   */
  const nonce = 'pdb_csv_import';
  
  /**
   * @var string name of the CSV upload field
   */
  const csv_field = 'pdb_csv_uploadfile';

  /**
   * instantiates the object
   * 
   * @param type $file_field the name of the file upload POST array element
   */
  function __construct($file_field) {

    if ( isset($_POST[$file_field]) && $this->check_submission() ) {

      if ( $this->verify_upload_dir() ) {

        $target_path = Participants_Db::base_files_path() . $this->upload_directory . basename($_FILES[PDb_CSV_Import::csv_field]['name']);

        if (false !== move_uploaded_file($_FILES[PDb_CSV_Import::csv_field]['tmp_name'], $target_path)) {

          $this->set_error(sprintf(__('The file %s has been uploaded.', 'participants-database'), '<strong>' . $_FILES[PDb_CSV_Import::csv_field]['name'] . '</strong>'), false);

          $success = $this->insert_from_csv($target_path);
          
          if ( $success ) {
            
            if ( ! $this->is_background_import() ) {
              
              $tally = \PDb_import\tally::get_instance();
              $tally->complete( false );
              $this->set_error_heading( $tally->report(), '', false);
              $tally->reset();
              
            } else {
              
              $this->set_error_heading( $this->upload_success_message(), '', false);
            }
            
    
          }
        } // file move successful
        else { // file move failed
          $this->set_error_heading(
                  __('There was an error uploading the file. This is often because the file is larger than the server configuration will allow.', 'participants-database'), __('Destination', 'participants-database') . ': ' . dirname( $target_path )
          );
        }
      } else {

        $this->set_error_heading(
                __('Target directory does not exist and could not be created. Try creating it manually.', 'participants-database'), __('Destination', 'participants-database') . ': ' . dirname( $target_path )
        );
      }
      // we are done with the file, delete it
      Participants_Db::delete_file($target_path);
      
      PDb_Participant_Cache::make_all_stale();
      
      do_action( 'pdb-after_import_csv', $this );
    }
  }
  
  /**
   * supplies tue upload success message
   * 
   * @return string
   */
  protected function upload_success_message()
  {
    $message = array( __( 'File upload complete', 'participants-database') . ': ' );
    
    $message[] = sprintf( _n( '%d line received.', '%d lines received.', $this->lines, 'participants-database' ), $this->lines  );
    
    $message[] = '<br/>' . __( 'The data is importing in the background, refresh the page to get the current status of the import.', 'participants-database');
    
    return implode( PHP_EOL, $message );
  }
  
  /**
   * checks for a valid submission
   * 
   * @return bool true if the submission checks out
   */
  protected function check_submission()
  {
    $nonce = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
    if ( ! wp_verify_nonce( $nonce, self::nonce ) ) {
      return false;
    }
    
    $filename = sanitize_file_name( filter_var( $_FILES[PDb_CSV_Import::csv_field]['name'], FILTER_SANITIZE_STRING ) );
    
    if ( pathinfo( $filename, PATHINFO_EXTENSION ) === 'csv' ) {
      $_FILES[PDb_CSV_Import::csv_field]['name'] = $filename;
      return true;
    }
    $this->set_error_heading( __('Invalid file for import.', 'participants-database') );
    return false;
  }

  /**
   * sets up the column name array
   *
   */
  abstract protected function set_column_array();

  /**
   * sets and verifies the uploads directory
   *
   * @return bool true if the directory can be used
   */
  abstract protected function verify_upload_dir();

  /**
   * stores the record in the database
   *
   * @param array $post associative array of imported data
   */
  abstract protected function store_record( $post );

  /**
   * inserts a series of records from a csv file
   *
   * @param string $src_file the file to parse
   *
   * @return bool success/failure
   * 
   */
  protected function insert_from_csv($src_file)
  {
    global $wpdb;
    $wpdb->hide_errors();

    if (empty($src_file) || !is_file($src_file)) {

      /* translators: the %s will be the name of the file */
      $this->set_error_heading(
              __('Error occured while trying to add the data to the database', 'participants-database'), sprintf(__('Input file does not exist or path is incorrect.<br />Attempted to load: %s', 'participants-database'), basename($src_file))
      );

      return false;
    }

    $this->CSV = new \ParseCsv\Csv();

    /* this method determines the delimiter automatically then parses the file; 
     * we don't use it because it seems easily confused
     */
    //$this->CSV->auto( $src_file, true, 1, ',' );

    /*
     * we use our own detection algorithms and parse the file based on what we 
     * found
     */
    $this->CSV->delimiter = $this->_detect_delimiter($src_file);
    $this->CSV->enclosure = $this->_detect_enclosure($src_file);
    $this->CSV->parseFile($src_file);

    if ( $this->CSV->error ) {
      Participants_Db::debug_log(__METHOD__ . ' CSV parse error:' . print_r($this->CSV->error_info, 1));
    }

    /*
     * build the column names from the CSV if we have one and it's different from 
     * the CSV columns defined by the database
     */
    $this->setup_import_columns();
    
    if ( isset($this->errors['incorrect_column']) ) {
      
      $this->set_error( __( 'Cannot import data. Make sure all field names are correct in the CSV header.', 'participants-database' ) );
      return false;
    }

    $this->lines = 0;

    foreach ($this->CSV->data as $csv_line) {

      $this->lines++;

      Participants_Db::debug_log( __METHOD__.'
        
columns:'.implode(', ',$this->column_names).'
  
csv line= '.print_r( $csv_line, true ), 2 );

      $column_values = array();

      foreach ($csv_line as $value) {

        $column_values[] = $this->process_value($value);
      }

      if (count($column_values) != $this->column_count) {

        $this->set_error( sprintf( __('The number of items in line %s is incorrect.<br />There are %s and there should be %s.', 'participants-database'), $this->lines + 1, count($column_values), $this->column_count ), true, 'column_value_mismatch' );

        return false;
      }

      // put the keys and the values together into the $post array
      if ( !$post = array_combine( $this->column_names, $column_values ) ) {
        $this->set_error( __('Number of values does not match number of columns', 'participants-database'), true, 'column_value_mismatch' );
      } else {

        // store the record
        $this->store_record( $post );
      }
    }

    return true;
  }

  /**
   * applies conditioning and escaping to the incoming value
   * 
   * @param type $value
   * @return string
   */
  protected function process_value($value) {
    
    return esc_sql($this->_enclosure_trim($value, '', $this->CSV->enclosure));
  }

  /**
   * trims enclosure characters from the csv field
   *
   * @param string $value raw value from CSV file
   * @param string $key column key
   * @param string $enclosure the enclosure character
   * 
   * @access public because PHP callback uses it
   * @return string the trimmed value
   */
  public function _enclosure_trim( $value, $key, $enclosure) {
    return trim( $value, $enclosure );
  }

  /**
   * detect an enclosure character
   *
   * this uses a regex backreference to find pairs of enclosure characters
   *
   * @param string $csv_file path to a csv file to read and analyze
   * @return string the best guess enclosure character
   */
  protected function _detect_enclosure($csv_file) {

    $csv_text = file_get_contents($csv_file);

    $assay = preg_match( '#(["\'])([^\1]+?)\1' . $this->CSV->delimiter . '#', $csv_text, $matches);

    return isset($matches[1]) ? $matches[1] : '"';
  }

  /**
   * determines the delimiter character in the CSV file
   * 
   * crude method used here, but should be right most of the time
   * 
   * @param string $csv_file the CSV file to scan for a delimiter
   * @return string the delimiter
   */
  protected function _detect_delimiter($csv_file) {

    // grab the file as a string, limiting it to a large, but not too large sample
    $csv_text = substr(file_get_contents($csv_file), 0, 2000);
    // count each of the likely suspects in the string
    $test_chars = array(",", ";", "\t", ".", ":", "|");
    $result_array = array();
    foreach ($test_chars as $test_char) {
      $result_array[$test_char] = substr_count($csv_text, $test_char);
    }
    // sort the array by the number of hits
    arsort($result_array);
    // the most abundant character is chosen as most likely
    // falls back to comma if no clear winner emerges
    return current($result_array) > 1 ? key($result_array) : ',';
  }

  /**
   * takes a raw title row from the CSV and sets the column names array with it
   * if the imported row is different from the plugin's defined CSV columns
   *
   */
  protected function setup_import_columns() {

    // build the column names from the CSV if it's there
    if (is_array($this->CSV->titles) and $this->column_names != $this->CSV->titles) {

      $this->column_names = $this->CSV->titles;

      // remove enclosure characters
      array_walk($this->column_names, array($this, '_enclosure_trim'), $this->CSV->enclosure);

      $this->column_count = count($this->column_names);
    }
  }

  /**
   * adds an error to the errors array
   *
   * @param string $message      the message to add
   * @param bool   $error_status true for error, false for non-error message
   * @param string $label string label for the error message
   * 
   */
  protected function set_error($message, $error_status = true, $label = false ) {

    if ( $label ) {
      $this->errors[$label] = $message;
    } else {
      $this->errors[] = $message;
    }
    
    if ($error_status) {
      $this->error_status = 'error';
    }
  }

  /**
   * adds an error message with a header
   *
   * @param string $heading      the heading message to show
   * @param string $message      the message body (if any)
   * @param bool   $error_status true for error, false for non-error message
   */
  protected function set_error_heading($heading, $message = '', $error_status = true) {

    $this->errors[] = '<strong>' . $heading . '</strong>';
    $this->set_error($message, $error_status);
  }
  
  /**
   * tells if the import is done in the background
   * 
   * @return bool
   */
  public function is_background_import()
  {
    $options = get_option( Participants_Db::$participants_db_options );
    return isset( $options['background_import'] ) ? (bool) $options['background_import'] : true;
  }

}