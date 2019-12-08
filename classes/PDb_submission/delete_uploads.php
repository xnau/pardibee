<?php

/**
 * handles deleting the uploaded files for a set of records
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2019  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission;

class delete_uploads {
  
  /**
   * @var array of fields that need checking for an uploaded file
   */
  private $upload_field_list = array();
  
  /**
   * sets up the deletion
   */
  private function __construct()
  {
    $this->setup_upload_field_list();
  }
  
  /**
   * deletes al the uploaded files for the listed records
   * 
   * @param array $record_id_list
   */
  public static function delete_record_uploaded_files ( $record_id_list ) {
    $deleter = new self();
    if ( $deleter->need_to_check_for_uploads() ) {
      $deleter->delete_all_record_uploads( $record_id_list );
    }
  }
  
  /**
   * processes the list of deleting records
   * 
   * @param array $record_id_list of record ids to be deleted
   */
  private function delete_all_record_uploads( Array $record_id_list )
  {
    foreach ( $record_id_list as $record_id ) {
      $this->delete_record_uploads($record_id);
    }
  }
  
  /**
   * checks a record for uploaded files
   * 
   * @param int $record_id
   */
  private function delete_record_uploads ( $record_id )
  {
    foreach ( $this->upload_field_list as $upload_field ) {
      $field_item = new \PDb_Field_Item( $upload_field, $record_id );
      if ( $field_item->has_uploaded_file() ) {
        $deleted = \Participants_Db::delete_file( $field_item->value );
      }
    }
  }
  
  /**
   * tells if we should check for uploaded files
   * 
   * @return bool true if there are fields that upload files
   */
  private function need_to_check_for_uploads()
  {
    return count( $this->upload_field_list ) > 0;
  }
  
  /**
   * compiles a list of upload fields
   */
  private function setup_upload_field_list()
  {
    foreach ( \Participants_Db::field_defs() as $field ) {
      if ( $field->is_upload_field() ) {
        $this->upload_field_list[] = $field;
      }
    }
  }
}
