<?php

/**
 * processes matching an incoming record to the database
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2019  xnau webdesign
 * @license    GPL3
 * @version    0.4
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission\matching;

defined( 'ABSPATH' ) || exit;

abstract class record {

  /**
   * @var string name of the configured field to match
   * 
   */
  protected $match_field;

  /**
   * @var string the duplicate record preference
   * 
   * 0 - create new record 'add'
   * 1 - update matching record 'update'
   * 2 - skip the record 'skip' (also as: 'error')
   */
  private $match_mode;

  /**
   * @var int the current record id, int 0 if new record
   */
  private $record_id;

  /**
   * @var array of posted data
   * 
   * don't assume sanitized
   */
  protected $post;

  /**
   * @var bool match status
   * 
   * null: not checked; true: matched; false:no match found
   */
  private $match_status;

  /**
   * @var string the action key
   */
  protected $action;
  
  /**
   * provides the match object instance
   * 
   * @param array $post data
   * @param int $record_id
   * @return PDb_submission\matching\record
   */
  public static function get_object( $post, $record_id )
  {
    return \PDb_submission\main_query\base_query::is_csv_import() ? new import( $post, $record_id ) : new form( $post, $record_id );
  }

  /**
   * tells if the current operation is a csv import
   * 
   * @return bool
   */
  abstract public function is_csv_import();

  /**
   * sets the default match mode (duplicate preference)
   */
  abstract protected function setup_match_mode();

  /**
   * sets the current match field
   * 
   * this is the field that is checked for a match between two records
   * 
   */
  abstract protected function set_match_field();

  /**
   * @param array $post the posted data
   * @param int $record_id
   */
  public function __construct( $post, $record_id = 0 )
  {
    $this->post = $post;
    $this->set_record_id( $record_id );
    $this->set_match_field();
    $this->setup_match_mode();
  }

  /**
   * provides the record ID based on the action
   * 
   * this changes the action according to the match mode
   * 
   * @param string @action
   * @return string the updated action value
   */
  public function get_action( $action )
  {
    $this->action = $action;
    
    $this->adjust_action();
    
    return $this->action;
  }
  
  /**
   * modifies the action if needed
   */
  protected function adjust_action()
  {
    switch ( $this->action ) {
      case 'update':
        $this->update_action();
        break;

      case 'insert':
        $this->insert_action();
        break;

      default:
      case 'skip':
        $this->skip_action();
    } 
  }

  /**
   * sets the match mode for the update action
   * 
   */
  protected function update_action()
  {
    if ( $this->match_mode === 'skip' && $this->is_matched()  ) {
      
      $this->register_duplicate_record_error();
      $this->action = 'skip';
    }
  }

  /**
   * sets the match mode for the insert action
   */
  protected function insert_action()
  {
    if ( $this->is_matched() ) {
      if ( $this->match_mode === 'update' ) {

        /**
         * @filter pdb-process_form_matched_record
         * 
         * @param int id of the matched record
         * @param array the submitted post data
         * 
         * @return int the id of the matched record
         */
        $this->record_id = \Participants_Db::apply_filters( 'process_form_matched_record', $this->matched_record_id(), $this->post );

        // set the update mode
        $this->action = 'update';
      } elseif ( $this->match_mode === 'skip' ) {

        $this->register_duplicate_record_error();

        // we won't be saving this record
        $this->action = 'skip';
      }
    } elseif ( $this->is_id_update_mode() ) {
        
        /*
         * if we are updating by record id, but there is no matching record to update, 
         * try to get the record id from the incoming record data then add a new record
         * 
         */
        $this->record_id = $this->matched_record_id();
        
        if ( $this->record_id !== 0 ) {
          $this->action = 'insert';
        } else {
          $this->record_id = false;
        }
    }
  }

  /**
   * sets the match mode for the skip action
   */
  protected function skip_action()
  {
    
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
   * provides object property values
   * 
   * @param string $name of the property
   * @return mixed
   */
  public function __get( $name )
  {
    switch ( $name ) {
      case 'record_id':
        return $this->record_id;
      case 'post':
        return $this->post;
      case 'match_field':
        return $this->match_field;
      case 'match_status':
        return $this->match_status;
    }
  }

  /**
   * provides the current match status
   * 
   * @return bool true if a matching record was found
   */
  public function is_matched()
  {
    if ( has_action( 'pdb-incoming_record_match_object' ) ) {
      /**
       * @action pdb-incoming_record_match_object
       * @param incoming_record_match this object
       * 
       * action for modifying the match object before a match check is performed
       * 
       * the action handler is expected to set the match_status property true/false if a match is found
       * if it leaves the match_status property alone, the default match method will be used
       * 
       */
      \Participants_Db::do_action( 'incoming_record_match_object', $this );
    }

    // for backward compatibility
    if ( is_null( $this->match_status ) && has_filter( 'pdb-incoming_record_match' ) ) {

      /**
       * 
       * @filter pdb-incoming_record_match
       * @param bool  $record_match true if a matching record has been found
       * @param array $post         the submitted post data
       * @param int   $duplicate_record_preference 1 = update matched record, 2 = prevent duplicate
       * 
       * @return bool true if a matching record is found
       */
      $this->match_status = \Participants_Db::apply_filters( 'incoming_record_match', $this->match_status, $this->post, $this->match_mode_numeric() );
    }

    if ( is_null( $this->match_status ) ) {

      // use the configured match
      $this->_match_check();
    }

    if ( $this->match_mode() === 'skip' && $this->match_status ) {
      /* 
       * we've got a matching record and we're supposed to skip updating it
       */
      $this->register_duplicate_record_error();
    }

    return (bool) $this->match_status;
  }

  /**
   * checks for a match
   * 
   * @return bool true if matched
   */
  protected function match_check()
  {
    $this->_match_check();
    return $this->match_status;
  }

  /**
   * sets the matching mode
   * 
   * also works with numeric mode values
   * 
   * @param string $mode one of 'skip','add','update'
   */
  public function set_match_mode( $mode )
  {
    switch ( $mode ) {
      case '2':
      case 'skip':
      case 'error':
        $this->match_mode = 'skip';
        break;
      case '1':
      case 'update':
        $this->match_mode = 'update';
        break;
      case '0';
      case 'add':
        $this->match_mode = 'add';
        break;
    }
  }

  /**
   * sets the record id value
   * 
   * @param int $value the record id
   */
  public function set_record_id( $value )
  {
    $this->record_id = intval( $value );
  }

  /**
   * sets the match status
   * 
   * @param bool $status
   */
  public function set_match_status( $status )
  {
    $this->match_status = (bool) $status;
  }

  /**
   * clears the match status
   * 
   */
  public function clear_match_status( $status )
  {
    unset( $this->match_status );
  }

  /**
   * provides the current match mode
   * 
   * @return string
   */
  public function match_mode()
  {
    return $this->match_mode;
  }

  /**
   * tells if the preference is to skip or error the record
   * 
   * @return bool true if mode is skip
   */
  public function skip_mode()
  {
    return $this->match_mode === 'skip';
  }

  /**
   * tells if the current configuration is to overwrite a matched record id
   * 
   * @return bool
   */
  public function is_id_update_mode()
  {
    return $this->match_mode === 'update' && $this->match_field === 'id' && is_numeric( $this->match_field_value() );
  }

  /**
   * provides the match mode as a number
   * 
   * this is for compatibility with old code
   * 
   * @return string the match mode number
   */
  public function match_mode_numeric()
  {
    switch ( $this->match_mode ) {
      case 'skip':
      case 'error':
        $mode = '2';
        break;
      case 'update':
        $mode = '1';
        break;
      case 'add':
        $mode = '0';
    }
    return $mode;
  }

  /**
   * provides the value of the match field
   * 
   * @return string
   */
  public function match_field_value()
  {
    $value = isset( $this->post[ $this->match_field ] ) ? filter_var( $this->post[ $this->match_field ], FILTER_SANITIZE_SPECIAL_CHARS ) : '';
    
    $field = new \PDb_Form_Field_Def( $this->match_field );
    
    // convert the value to something that can match the stored value
    switch ( $field->form_element() ) {
      
      case 'date':
        $value = \PDb_Date_Parse::timestamp( $value );
        break;
      
    }
    
    return $value;
  }

  /**
   * provides the ID of the matched record
   * 
   * @return int
   */
  public function matched_record_id()
  {
    if ( $this->record_id != 0 ) {
      $id = $this->record_id;
    } elseif ( $this->match_field === 'id' ) {
      $id = intval( $this->match_field_value() );
    } else {
      $id = \Participants_Db::get_record_id_by_term( $this->match_field, $this->match_field_value() );
    }

    return is_array( $id ) ? current( $id ) : intval( $id );
  }

  /**
   * sets up the matched field error message
   */
  protected function register_duplicate_record_error()
  {
    if ( !is_object( \Participants_Db::$validation_errors ) ) {
      \Participants_Db::$validation_errors = new \PDb_FormValidation();
    }

    \Participants_Db::$validation_errors->add_error( $this->match_field, 'duplicate' );
  }

  /**
   * calculates a match based on the match field value and sets the match status
   * 
   */
  private function _match_check()
  {
    if ( $this->match_mode() === 'add' ) {
      $this->match_status = false;
    }
    /*
     *  prevent updating record from matching itself if we are avoiding duplicates
     */
    $mask_id = $this->skip_mode() ? $this->record_id : 0;

    $this->match_status = $this->match_field_value() !== '' && self::field_value_exists( $this->match_field_value(), $this->match_field, $mask_id );
  }

  /**
   * check the db for a record that matches the given field
   *
   * @param string $value the value of the field to test
   * @param string $field the field to test
   * @param int $mask_id optional record id to exclude
   * @global \wpdb $wpdb
   * @return bool true if there is a matching value for the field
   */
  public static function field_value_exists( $value, $field, $mask_id = 0 )
  {
    global $wpdb;

    $match_count = $wpdb->get_var( $wpdb->prepare( "SELECT EXISTS( SELECT 1 FROM " . \Participants_Db::participants_table() . " p WHERE p." . $field . " = '%s' AND p.id <> %s )", $value, $mask_id ) );

    if ( defined( 'PDB_DEBUG' ) && PDB_DEBUG > 2 ) {
      \Participants_Db::debug_log( __METHOD__ . ' query: ' . $wpdb->last_query );
    }

    return is_null( $match_count ) ? false : (bool) $match_count;
  }

}
