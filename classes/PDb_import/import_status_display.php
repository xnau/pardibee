<?php

/**
 * provides status updates to the CSV import page
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2025  xnau webdesign
 * @license    GPL3
 * @version    2.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_import;

class import_status_display {
  
  /**
   * @var string name of the AJAX action
   */
  const action = 'csv-import-status';
  
  /**
   * sets up the object
   * 
   */
  public function __construct()
  {
    add_action( 'wp_ajax_' . self::action, [$this,'ajax_response'] );
    
    add_action( 'pdb-csv_import_file_load', [$this,'reset'] );
  }
  
  /**
   * sends the status display values
   * 
   * @return null
   */
  public function ajax_response()
  {
    check_ajax_referer( self::action );
    
    wp_send_json( self::status_report() );
  }
  
  /**
   * provides the status values with the HTML report
   * 
   * @return array
   */
  private static function status_report()
  {
    $tally = tally::get_instance();
    
    $status = $tally->get_tally();
    
    $status_report = array_merge( $status, ['html' => $tally->realtime_report() ] );
    
    return $status_report;
  }
  
  /**
   * resets the status values
   */
  public function reset()
  {
    $tally = tally::get_instance();
    $tally->reset();
  }
  
  /**
   * tells if there is an import in progress
   * 
   * @return bool
   */
  public static function is_importing()
  {
    $post_import = (bool) filter_input( INPUT_POST, 'csv_file_upload', FILTER_DEFAULT, \Participants_Db::string_sanitize(FILTER_NULL_ON_FAILURE) );
    
    $import_status = self::status_report();
    
    $in_progress = isset( $import_status['length'] ) && isset( $import_status['progress'] );
    
    return $post_import || $in_progress;
  }
 
}
