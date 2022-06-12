<?php

/**
 * models a database column from the internal group
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission\main_query;

use \Participants_Db,
    \PDb_Date_Parse;

defined( 'ABSPATH' ) || exit;

class internal_column extends base_column {

  /**
   * sets the value property
   */
  protected function setup_value()
  {
    $initialvalue = $this->raw_value;

    switch ( $this->field->name() ) {

      case 'id':
        $this->value = $this->main_query()->record_id();
        $this->skip = true;
        break;

      case 'date_recorded':
      case 'date_updated':
      case 'last_accessed':

        $this->skip = true;
        
        /*
         *  don't process the imported value if it is already set in the sql
         */
        if ( strpos( $this->main_query()->query_head(), $this->field->name() ) !== false ) {
          break;
        }
        
        /*
         * always include the value if importing a CSV
         * 
         * also parses the value if "pdb-edit_record_timestamps" filter is true
         */
        if ( $this->main_query()->is_import() || Participants_Db::apply_filters( 'edit_record_timestamps', false ) ) {
          
          if ( !empty( $initialvalue ) ) {

            if ( PDb_Date_Parse::is_mysql_timestamp( $initialvalue ) ) {
              // record it if it is a valid mysql timestamp
              $this->value = $initialvalue;
            } else {
              // convert the date to a mysql timestamp
              $display_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

              $timestamp = PDb_Date_Parse::timestamp( $initialvalue, array( 'input_format' => $display_format ), __METHOD__ . ' saving internal timestamp fields, standard format' );
              
              if ( !$timestamp ) {
                $timestamp = PDb_Date_Parse::timestamp( $initialvalue, array(), __METHOD__ . ' saving internal timestamp fields' );
              }

              if ( $timestamp ) {
            
                $adjusted = $timestamp - $this->tz_offset();
                $this->value = \PDb_Date_Display::get_mysql_timestamp( $timestamp - $this->tz_offset() );
                
                $this->skip = false;
              }
            }
          }
        }
        
        break;

      case 'private_id':
        
        $this->value = false;
        
        /**
         * supplied private ID is tested for validity and rejected if not valid
         * 
         * @filter pdb-private_id_validity
         * @param bool  test result using the regex
         * @param string the tested value
         * @return bool true if valid
         */
        if ( Participants_Db::apply_filters( 'private_id_validity', preg_match( '#^[A-Z0-9]{5,}$#', $initialvalue ) === 1, $initialvalue ) && Participants_Db::get_participant_id( $initialvalue ) === false ) {
          $this->value = $initialvalue;
        }
        
        if ( $this->value === false ) {
          $this->value = $this->main_query()->write_mode() === 'insert' ? Participants_Db::generate_pid() : false;
        }
        
        if ( $this->value === false ) {
          $this->skip = true;
        }

        break;
    }
  }
  
  /**
   * provides the timezone offset for db timestamps
   * 
   * @return int
   */
  private function tz_offset()
  {
    $use_utc = PDb_Date_Parse::db_timestamp_timezone() === 'UTC';
    return $use_utc ? get_option( 'gmt_offset' ) * HOUR_IN_SECONDS : 0;
  }
}
