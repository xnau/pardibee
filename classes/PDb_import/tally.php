<?php

/**
 * keeps track of the import status values
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_import;

defined( 'ABSPATH' ) || exit;

class tally {
  
  /**
   * @var string name of the transient used to store the results
   */
  const store_name = 'wp_pdb_import_tally';
  
  /**
   * @var \PDb_import\tally the current instance
   */
  private static $instance;
  
  /**
   * @var array keeps the tally values
   */
  private $tally = array();
  
  /**
   * @var string holds the last status added
   */
  private $last_status;
  
  /**
   * @var bool flags the tally as complete
   */
  private $complete = false;
  
  /**
   * provides the current instance
   * 
   * @return \PDb_import\tally
   */
  public static function get_instance()
  {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }
    
    return self::$instance;
  }
  
  /**
   * sets the import length value
   * 
   * this represents the total number of lines in the imported CSV file
   * 
   * @param int $length
   */
  public static function set_import_length( $length )
  {
    $tally = self::get_instance();
    
    $tally->tally['length'] = intval( $length );
    
    $tally->save();
  }
  
  /**
   * sets up the tally
   */
  private function __construct()
  {
    $this->setup_tally();
  }
  
  /**
   * stashes the tally
   */
  public function __destruct()
  {
    $this->save();
  }
  
  /**
   * provides the tally value
   * 
   * @return array as $status => $count
   */
  public function get_tally()
  {
    $this->save();
    
    return $this->tally;
  }
  
  /**
   * adds to the tally
   * 
   * @param string $status the status to add to
   * @param int $number the number to add to the tally, defaults to 1
   */
  public function add( $status, $number = 1 )
  {
    if ( !array_key_exists( $status, $this->tally ) ) {
      $this->tally[$status] = 0;
    }
    
    $this->tally[$status] += $number;
    $this->last_status = $status;
    
    $this->tally['progress'] = $this->import_count();
    
    $this->save();
  }
  
  /**
   * provides the last status value
   * 
   * @return string status
   */
  public function last_status()
  {
    return $this->last_status;
  }
  
  /**
   * resets the tally
   * 
   */
  public function reset()
  {
    $this->complete = false;
    $this->clear();
  }
  
  /**
   * tells if the tally has a report
   * 
   * @return bool
   */
  public function has_report()
  {
    return count( $this->tally ) > 0;
  }
  
  /**
   * provides a tally report
   * 
   * @return string
   */
  public function report()
  {
    if ( $this->is_complete() )
    {
      $this->complete = true;
    }
    
    $report = array( '<span class="import-tally-report">' );
    
    $report[] = '<strong>' . $this->context_string() . ':</strong><br>';
    
    $template = '<span class="import-tally-report-item">%s</span>';
    
    foreach( $this->tally as $status => $count ) {
      $report[] = sprintf( $template, $this->status_phrase( $status ) );
    }
    
    $report[] = sprintf( $template, \Participants_Db::apply_filters( 'csv_import_report', $this->count_report() ) );
    
    $report[] = '</span>';
    
    $report_html = implode( PHP_EOL, $report );;
    
    if ( $this->complete )
    {
      set_transient( self::store_name . 'final-report', $report_html, 0 );
      $this->clear();
    }
    
    return $report_html;
  }
  
  /**
   * tells if the import is complete
   * 
   * @return bool
   */
  private function is_complete()
  {
    error_log(__METHOD__.' tally: '. print_r($this->tally,1));
    return isset( $this->tally['progress'] ) && isset( $this->tally['length'] ) && $this->tally['progress'] == $this->tally['length'];
  }
  
  /**
   * provides the report to asynchronous code
   * 
   * @return string HTML
   */
  public function realtime_report()
  {
    $final_report = get_transient( self::store_name . 'final-report' );
    
    return $final_report ? : $this->report();
  }
  
  /**
   * provides the total import count
   * 
   * @return  int
   */
  private function import_count()
  {
    $count = 0;
    
    $count += $this->status_count( 'insert' );
    $count += $this->status_count( 'update' );
    
    return $count;
  }
  
  /**
   * provides the count value for a status
   * 
   * @param string $status
   * @return int
   */
  private function status_count( $status )
  {
    return isset( $this->tally[$status] ) ? $this->tally[$status] : 0;
  }
  
  /**
   * provides the import count report
   * 
   * @return string
   */
  private function count_report()
  {
    $count = $this->import_count();
    
    return sprintf( _n( '%s record imported.', '%s records imported', $count, 'participants-database' ), $count );
  }
  
  /**
   * provides the context string
   * 
   * @return string
   */
  private function context_string()
  {
    if ( $this->complete ) {
      return __('Import Complete', 'participants-database' );
    } else {
      return __('Import in Progress', 'participants-database' );
    }
  }


  /**
   * sets up the tally property
   */
  private function setup_tally()
  {
    $tally = get_transient( self::store_name );
    
    if ( ! $tally ) {
      $tally = array();
    }
    
    $this->tally = $tally;
  }
  
  /**
   * saves the tally count to the transient
   */
  private function save()
  {
    set_transient( self::store_name, $this->tally );
  }
  
  /**
   * saves the tally count to the transient
   */
  private function clear()
  {
    error_log(__METHOD__);
    $this->tally = array();
    delete_transient( self::store_name );
    delete_transient( self::store_name . 'final-report' );
  }
  
  /**
   * provides the report phrases
   * 
   * @param string $status
   * @eturn string count phrase
   */
  private function status_phrase( $status )
  {
    $phrase = '';
    $count = $this->status_count($status);
    
    switch( $status ){
      case 'insert':
        $phrase = sprintf(_n('%s record added', '%s records added', $count, 'participants-database'), $count);
        break;
      
      case 'update':
        $phrase = sprintf(_n('%s matching record updated', '%s matching records updated', $count, 'participants-database'), $count);
        break;
      
      case 'skip':
        $phrase = sprintf(_n('%s duplicate record skipped', '%s duplicate records skipped', $count, 'participants-database'), $count);
        break;
      
      case 'error':
        $phrase = sprintf(_n('%s record skipped due to errors', '%s records skipped due to errors', $count, 'participants-database'), $count);
        break;
    }
    
    return $phrase;
  }
  
}
