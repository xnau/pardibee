<?php

/**
 * models a database column from the internal group
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.1
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
    $initialvalue = $this->value;

    switch ( $this->field->name() ) {

      case 'id':
        $this->value = $this->main_query()->record_id();
        $this->skip = true;
        break;

      case 'date_recorded':
      case 'date_updated':
      case 'last_accessed':

        /*
         *  remove the value from the post data if it is already set in the sql
         */
        if ( strpos( $this->main_query()->query_head(), $this->field->name() ) !== false ) {
          $this->skip = true;
          break;
        }
        /*
         * always include the value if importing a CSV
         * 
         * also parses the value if "pdb-edit_record_timestamps" filter is true
         */
        if ( $this->main_query()->is_import() || Participants_Db::apply_filters( 'edit_record_timestamps', false ) ) {

          $this->skip = true;

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
                $this->value = \PDb_Date_Display::get_mysql_timestamp( $timestamp );
              }
            }
          }
          break;
        }
        /*
         * skip the "last_accessed" field: it is set by the PDb_Record class
         * skip the "date_recorded" field: it is either set in the query or left alone
         */
        if ( in_array( $this->field->name(), array( 'last_accessed', 'date_recorded' ) ) ) {
          $this->skip = true;
          break;
        }

      case 'private_id':
        /*
         * supplied private ID is tested for validity and rejected if not valid
         * 
         * @filter pdb-private_id_validity
         * @param bool  test result using the regex
         * @return bool true if valid
         */
        
        if ( Participants_Db::apply_filters( 'private_id_validity', preg_match( '#^[A-Z0-9]{5,}$#', $initialvalue ) === 1 ) && Participants_Db::get_participant_id( $initialvalue ) === false ) {
          $this->value = $initialvalue;
        } else {
          $this->value = $this->main_query()->write_mode() === 'insert' ? Participants_Db::generate_pid() : false;
        }
        
        if ( $this->value === false ) {
          $this->skip = true;
        }

        break;
    }
  }
}
