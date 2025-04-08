<?php

/**
 * processes the import submission
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    2.4
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
   * @param array $preferences
   */
  public function setup( $column_names, $preferences )
  {
    set_transient( self::setting, ['column_names' => $column_names] + $preferences );
  }

	/**
	 * Save queue
	 *
	 * @return $this
	 */
	public function save() {
		$key = $this->generate_key();
    
    $this->clear_old_batch_entries($key);

		if ( ! empty( $this->data ) ) {
			update_site_option( $key, $this->data );
		}
    
    // store the queue length
    tally::set_import_length( $this->queue_count() );
    
    /**
     * @action pdb-import_queue_saved
     * 
     * fired when the queue is complete and processing begins
     */
    do_action( 'pdb-import_queue_saved', $this );

		return $this;
	}
  
  /**
   * performs the import on a single line
   * 
   * @param array $line a single line from the CSV file
   */
  protected function task( $line )
  {
    $ts = microtime(true);
    
    $preferences = $this->settings();
    
    if ( $this->is_background_import() && isset($preferences['blank_overwrite']) && boolval( $preferences['blank_overwrite'] ) )
    {
      add_filter( 'pdb-allow_imported_empty_value_overwrite', '__return_true', 5 );
    }
    
    $this->import( $line, $preferences );
    
    $ms = round( ( microtime(true) - $ts ) * 1000 );
    
//    error_log(__METHOD__.' elapsed: ' . $ms . 'μs' );
    
    return false;
  }
  
  /**
   * adds the data to the queue
   * 
   * if background import is disabled, imports the data line immediately
   * 
   * @param array $line CSV data line
   */
  public function push_to_queue( $line )
  {
    if ( $this->is_background_import() ) 
    {
      parent::push_to_queue($line);
    } 
    else 
    {
      $this->task( $line );
    }
  }
  
  /**
   * tells the number of items in the queue
   * 
   * @return int
   */
  public function queue_count()
  {
    return count( $this->data );
  }
  
  /**
   * tells if beckground imports is enabled
   * 
   * @return bool
   */
  private function is_background_import()
  {
    return \Participants_Db::plugin_setting_is_true( 'background_import', true );
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
    
    /**
     * @see xnau_CSV_Import::__construct for another import complete action
     */
    do_action( 'pdb-import_process_complete', $this );
  }

	/**
	 * Delete queue
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key )
  {
    do_action('pdb-import_process_delete_queue', $this );
    
		delete_site_option( $key );

		return $this;
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
    
    // looks like we don't need this #3108
    //delete_option( $this->tally->get_tally()->key );
    
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
  
  /**
   * clears any orphan batch entries
   * 
   * @global \wpdb $wpdb
   * @param string $key the current batch key
   */
  private function clear_old_batch_entries( $key )
  {
    global $wpdb;
    
    $sql = 'DELETE FROM ' . $wpdb->options . ' WHERE `option_name` LIKE "wp_' . $this->action . '_batch_%" AND `option_name` <> %s';
    
    $wpdb->query( $wpdb->prepare( $sql, $key ) );
  }
}
