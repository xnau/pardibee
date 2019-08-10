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
if ( ! defined( 'ABSPATH' ) ) die;
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
   * 
   * @param string $file_field_name
   */
  function __construct( $file_field_name ) {
    
    $this->i10n_context = Participants_Db::PLUGIN_NAME;
    
    $this->_set_column_array();
    
    $this->match_field = filter_input(INPUT_POST, 'match_field', FILTER_SANITIZE_STRING);
    $this->match_preference = filter_input(INPUT_POST, 'match_preference', FILTER_SANITIZE_NUMBER_INT);
    
    Participants_Db::$session->set( 'form_status', 'normal' ); // CSV import is a normal status
    
    parent::__construct( $file_field_name );
    
  }
  
  function _set_column_array() {
    
    $columns = Participants_Db::get_column_atts('all');

    foreach ( $columns as $column ) {
    
      if ( $column->CSV )
        $this->column_names[] = $column->name;
    
    }
    
    $this->column_count = count( $this->column_names );
        
    
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

      $this->errors[] = __('New columns imported from the CSV file.', 'participants-database');

      // remove enclosure characters
      array_walk($this->column_names, array($this, '_enclosure_trim'), $this->CSV->enclosure);

      $this->column_count = count($this->column_names);
    }
  }
  
  function _set_upload_dir() {

    $this->upload_directory = Participants_Db::files_location();
  
    // check for the target directory; attept to create if it doesn't exist
    return is_dir( Participants_Db::base_files_path() . $this->upload_directory ) ? true : Participants_Db::_make_uploads_dir( $this->upload_directory ) ;
    
  }
  
  /**
   * applies conditioning and escaping to the incoming value, also allows for a filter callback
   * 
   * @param string|int $value
   * @param string $column name of the column
   * @return string
   */
  function process_value( $value, $column = '' ) {
    return Participants_Db::apply_filters( 'csv_import_value', $this->_enclosure_trim($value, '', $this->CSV->enclosure), $column );
  }
  
  /**
   * stores the record in the database
   * 
   * @param array $post associative array of imported data
   */
  function store_record( $post ) {
    
    // reset the validation object
    Participants_Db::$validation_errors = new PDb_FormValidation();
    
    /**
     * @version 1.6.3
     * @filter pdb-before_csv_store_record
     */
    $post = Participants_Db::apply_filters('before_csv_store_record', $post);
    
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
   * detect the enclosure character
   *
   * @param string $csv_file path to a csv file to read and analyze
   * @return string the best guess enclosure character
   */
  protected function _detect_enclosure($csv_file) {
    $post_enclosure = filter_input(INPUT_POST, 'enclosure_character', FILTER_SANITIZE_STRING);
    
    if (!empty($post_enclosure) && $post_enclosure !== 'auto' ) {
      switch ( $post_enclosure ) {
        case '&#39;':
        case "'":
          return "'";
        default:
          return '"';
      }
    } else {
      return parent::_detect_enclosure($csv_file);
    }
  }

  /**
   * determines the delimiter character in the CSV file
   * 
   * @param string $csv_file the CSV file to scan for a delimiter
   * @return string the delimiter
   */
  protected function _detect_delimiter($csv_file) {
    $post_delimiter = filter_input(INPUT_POST, 'delimiter_character', FILTER_SANITIZE_STRING);
    if (!empty($post_delimiter) && $post_delimiter !== 'auto' ) {
      return $post_delimiter;
    } else {
      return parent::_detect_delimiter($csv_file);
    }
  }
  
  /*
   * provides the current matching record policy
   * 
   * @return string
   */
  
  
}