<?php

/**
 * processes the import submission
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    2.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_import;

defined( 'ABSPATH' ) || exit;

class process extends \WP_Background_Process {

  use store;
  
  /**
   * @var string name for the action
   */
  protected $action = 'pdb_import';
  
  /**
   * @var string name for the settings transient
   */
  const setting = 'pdb_import_settings';

	/**
	 * Get post args
   * 
   * this serializes the data array to avoid hitting php max input vars limits 
   * with large imports
	 *
	 * @return array
	 */
	protected function get_post_args()
  {
    $args = parent::get_post_args();
    
    $args['body'] = serialize( $args['body'] );
    
    return $args;
  }
  
  /**
   * sets up the store class
   * 
   * @param array $column_names
   * @param string $match_mode
   * @param string $match_field
   */
  public function setup( $column_names, $match_mode, $match_field )
  {
    set_transient( self::setting, array(
        'column_names' => $column_names,
        'match_mode' => $match_mode,
        'match_field' => $match_field
    ));
  }
  
  /**
   * performs the import on a single line
   * 
   * @param array $line a single line from the CSV file
   */
  protected function task( $line )
  {
    $this->import( $line, $this->settings() );
    
    return false;
  }
  
  /**
   * adds the data to the queue
   * 
   * @param array $line CSV data line
   */
  public function push_to_queue( $line )
  {
    if ( \Participants_Db::plugin_setting_is_true( 'background_import', true ) ) {
      parent::push_to_queue($line);
    } else {
      $this->task( $line );
    }
  }
  
  /**
   * gets the current batch
   * 
   * @return stdClass
   */
  protected function get_batch()
  {
    $batch = parent::get_batch();
    
    if ( $this->batch_check() === false )
    {
      $this->set_check_transient( $this->batch_id( $batch ) );
    }
    
    return $batch;
  }
  
  /**
   * provides the settings array
   * 
   * @return array
   */
  private function settings()
  {
    return get_transient( self::setting );
  }
  
  /**
   * this is called when the queue is cleared and the process is complete
   */
  protected function complete()
  {
    parent::complete();
    
    tally::get_instance()->complete( true );
    
    /**
     * @see xnau_CSV_Import::__construct for another import complete action
     */
    do_action( 'pdb-import_process_complete', $this );
  }

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck()
  {
    if ( $this->is_process_running() ) {
			// Background process already running.
			exit;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			exit;
		}
    
    $batch_serial = $this->batch_id( $this->get_batch() );
    $batch_check = $this->batch_check();
    
    if ( $batch_check == $batch_serial )
    {
      $this->abort_queue();
			exit;
    } 
    else
    {
      $this->set_check_transient( $batch_serial );
    }

		$this->handle();

		exit;
	}
  
  /**
   * bails out of the queue due to an error in the import
   * 
   */
  private function abort_queue()
  {
    $this->tally = tally::get_instance();
    $this->tally->add('error');
    delete_transient( $this->check_transient_name() );
    delete_option( $this->tally->get_tally()->key );
    
    add_filter( 'pdb-csv_import_report', function($report)
    {
      $report = $report . '<br/><strong>Import terminated due to error</strong>'; 
      $tally = $this->tally->get_tally();
      if ( isset($tally['error']) && $tally['error'] > 0 )
      {
        $report = $report . ' on line ' . ( $tally['error'] + 1 );
      }
      return $report;
    });
    
    $this->complete();
  }
  
  /**
   * produces an id string for a batch
   * 
   * @param object $batch
   * @return string
   */
  private function batch_id( $batch )
  {
    return crc32( serialize( $batch->data ) );
  }
  
  /**
   * provides the batch check value
   * 
   * @return string|bool false if the value is not set
   */
  private function batch_check()
  {
    return get_transient( $this->check_transient_name() );
  }
  
  /**
   * provides the name of the batch check transient
   * 
   * @return string
   */
  private function check_transient_name()
  {
    return $this->action . '-check';
  }
  
  /**
   * sets the check transient
   * 
   * @param string $batch_id
   */
  private function set_check_transient( $batch_id )
  {
    set_transient( $this->check_transient_name(), $batch_id, MINUTE_IN_SECONDS * apply_filters( 'wp_pdb_import_cron_interval', 5 ) * 1.1 );
  }
}
