<?php

/**
 * keeps track of the import status values
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
    $this->clear();
  }
  
  /**
   * completes the report
   * 
   * this posts the final report and resets the running tally
   * 
   * @param bool $background true if the import is done in the background
   */
  public function complete( $background )
  {
    $this->complete = true;
    
    if ( $background ) {
      $params = array(
          'type' => 'success',
          'persistent' => false,
      );
      \PDb_Admin_Notices::post_admin_notice( '<p>' . $this->report() . '</p>', $params );

     $this->clear();
    }
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
    $report = array( '<span class="import-tally-report">' );
    
    $report[] = '<strong>' . $this->context_string() . ':</strong><br>';
    
    $template = '<span class="import-tally-report-item">%s</span>';
    
    foreach( $this->tally as $status => $count ) {
      $report[] = sprintf( $template, $this->status_phrase( $status ) );
    }
    
    $report[] = sprintf( $template, $this->count_report() );
    
    $report[] = '</span>';
    
    return implode( PHP_EOL, $report );
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
    $this->tally = array();
    delete_transient( self::store_name );
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
