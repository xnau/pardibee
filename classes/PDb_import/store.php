<?php

/**
 * handles storing the data
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

namespace PDb_import;

trait store {
  
  /**
   * @var array of column names
   */
  private $column_names;
  
  /**
   * @var \PDb_import\tally instance
   */
  private $tally;
  
  /**
   * performs the import
   * 
   * @param array $column_values array of values from the imported line
   * @param array $settings
   */
  public function import( $column_values, $settings )
  {
    $this->column_names = $settings['column_names'];
    $this->tally = tally::get_instance();
    
    $post = array_combine( $this->column_names, $column_values );
    
    $post['match_preference'] = $settings['match_mode'];
    $post['match_field'] = $settings['match_field'];
    
    $result = $this->import_record( $post );
    
    if ( $result ) {
      /**
       * @action pdb-after_import_record
       * @param array $post the saved data
       * @param int $result the record id
       * @param string $status the insert status for the record
       */
      do_action( 'pdb-after_import_record', $post, $result, $this->tally->last_status() );
    }
  }
  
  /**
   * save the imported data to the database
   * 
   * this method is a modified form of Participants_Db::process_form()
   * 
   * @param array       $post           the array of values to import
   *
   * @return int|bool   int ID of the record created or updated, bool false if submission 
   *                    does not validate
   */
  private function import_record( $post )
  {
    $record_id = false;
    $action = 'insert';
    
    $record_match = new \PDb_submission\match\import( $post, $record_id );

    // modify the action according the the match mode
    $action = $record_match->get_action( $action );
    
    // get the record id to use in the query
    $record_id = $record_match->record_id();
    
    // tally the status
    $this->tally->add( $action );
    
    $main_query = \PDb_submission\main_query\base_query::get_instance( $action, $post, $record_id, false );
    /** @var \PDb_submission\main_query\base_query $main_query */
    
    /*
     * process the submitted data
     */
    foreach ( $main_query->column_array( $this->column_names ) as $column ) {
      
      /** @var object $column */
      
      /**
       * @action pdb-process_form_submission_column_{$fieldname}
       * 
       * @param object  $column the current column
       * @param array   $post   the current post array
       * 
       */
      do_action( 'pdb-process_form_submission_column_' . $column->name, $column, $post );
      
      $column_object = \PDb_submission\main_query\columns::get_column_object( $column, $main_query->column_value( $column->name ) );

      $main_query->validate_column( $column_object->value(), $column );
      
      if ( $column_object->add_to_query( $action ) ) {
        
        // add the column to the query
        $main_query->add_column( $column_object->value(), $column_object->query_clause() );
      }
    } // columns

    /*
     * now that we're done adding the submitted data to the query, check for 
     * validation and abort the process if there are validation errors
     */
    if ( $main_query->has_validation_errors() ) {
      return false;
    }
    
    $updated_record_id = $main_query->execute_query();
    
    \PDb_Participant_Cache::is_now_stale( $updated_record_id );

    return $updated_record_id;
  }
   
}