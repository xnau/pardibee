<?php

/**
 * handles the asynchronous importing of a record
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
class PDb_CSV_Import_Process extends WP_Background_Process {

  /**
   * @var string
   */
  protected $action = 'pdb_import_csv_async';

  /**
   * @var string name of the import status transient
   */
  const status = 'pdb-csv_import_status';

  /**
   * @var string name of the preferences option
   */
  const prefs = 'pdb-csv_import_prefs';

  /**
   * Dispatch the async request
   *
   * @return array|WP_Error
   */
  public function dispatch()
  {
    $url = add_query_arg( $this->get_query_args(), $this->get_query_url() );
    $args = $this->get_post_args();
    
    $this->post_initial_feedback();

    return wp_remote_post( esc_url_raw( $url ), $args );
  }

  /**
   * handles the record data importation
   * 
   * @param array $data the data from the CSV
   */
  protected function task( $data )
  {
    /**
     * @version 1.6.3
     * @filter pdb-before_csv_store_record
     */
    $post = Participants_Db::apply_filters( 'before_csv_store_record', $data );

    $post['csv_file_upload'] = 'true';
    $post['subsource'] = Participants_Db::PLUGIN_NAME;

    $post = array_merge( $post, $this->import_prefs() );

//    error_log(__METHOD__.' 
//      
//props: '.print_r($this,1).' 
//  
//post: '.print_r($post,1));
    // add the record data to the database
    $id = Participants_Db::process_form( $post, 'insert', false, array_keys( $post ) );

    /**
     * @action pdb-after_import_record
     * @param array $post the saved data
     * @param int $id the record id
     * @param string $insert_status the insert status for the record
     */
    do_action( 'pdb-after_import_record', $post, $id, Participants_Db::$insert_status );

    $this->increment_status( Participants_Db::$insert_status );

    return false;
  }

  /**
   * Complete
   *
   * Override if applicable, but ensure that the below actions are
   * performed, or, call parent::complete().
   */
  protected function complete()
  {
    parent::complete();

    self::set_status_value( 'process_complete', microtime( true ) );

    $this->post_complete_feedback();

    do_action( 'pdb-csv_import_complete' );
  }

  /**
   * puts up the user feedback when the import begins
   */
  public function post_initial_feedback()
  {
    $status = get_transient( self::status );

    $message = array();
    $message[] = sprintf( _n( 'Importing %s Record', 'Importing %s Records', $status['queue_count'], 'participants-database' ), $status['queue_count'] );
    $this->post_feedback_message($message, 'info');
  }

  /**
   * puts up the user feedback after the import is complete
   */
  public function post_complete_feedback()
  {
    $status = get_transient( self::status );

    $message = array();
    $elapsed = $status['process_complete'] - $status['process_start'];
    $minutes = intval( $elapsed / 60 ) > 0 ? intval( $elapsed / 60 ) . ' ' . __('minutes', 'participants-database') . ', ' : '';
    $seconds = number_format( $elapsed - intval( $elapsed / 60 ) * 60, 2 ) . ' ' . __('seconds', 'participants-database');
    $message[] = sprintf( __( 'Import completed in %s.', 'participants-database' ), $minutes . $seconds );
    $insert = array();
    if ( isset( $status['insert'] ) ) {
      $insert[] = sprintf( __( '%s records added', 'participants-database' ), $status['insert'] );
    }
    if ( isset( $status['update'] ) ) {
      $insert[] = sprintf( __( '%s records updated', 'participants-database' ), $status['update'] );
    }
    if ( isset( $status['skip'] ) ) {
      $insert[] = sprintf( __( '%s records skipped', 'participants-database' ), $status['skip'] );
    }
    if ( !empty($insert) ) {
      $message[] = implode( ', ', $insert ) . '.';
    }
    $this->post_feedback_message($message);
  }
  
  /**
   * posts the feedback message
   * 
   * @param array $message lines
   * @param string $type message type
   * @return string message ID
   */
  public function post_feedback_message( $message, $type = 'success' )
  {
    if ( $id = self::get_status_value( 'last_feedback_id' ) ) {
      PDb_Admin_Notices::delete_notice($id);
    }
    $feedback_id = PDb_Admin_Notices::post_admin_notice( implode( '</p><p>', $message ), array(
        'type' => $type,
        'context' => __( 'Import CSV', 'participants-database' ),
        'persistent' => true,
        'global' => true,
            )
    );
    self::set_status_value( 'last_feedback_id', $feedback_id );
  }

  /**
   * sets a status value
   * 
   * @param string  $status_field of the value to set
   * @param mixed $value the value to save
   */
  public static function set_status_value( $status_field, $value )
  {
    $status_info = get_transient( self::status );
    $status_info[$status_field] = $value;
    set_transient( self::status, $status_info );
  }

  /**
   * provides a status value
   * 
   * @param string $status_field
   * 
   * @return mixed the value
   */
  public static function get_status_value( $status_field )
  {
    $status_info = get_transient( self::status );
    return isset($status_info[$status_field]) ? $status_info[$status_field] : false;
  }

  /**
   * initializes the status value
   * 
   * @param array $status_info the initialzing data
   */
  public function initialize_status_info( $status_info = array() )
  {
    $status = get_transient( self::status );
    set_transient( self::status, array_merge( $status_info, (array) $status ) );
  }

  /**
   * sets the import preferences
   * 
   * @param array $prefs in the form $name => $value
   */
  public function set_import_prefs( Array $prefs )
  {
    update_option( self::prefs, $prefs );
  }

  /**
   * provides the import preferences array
   * 
   * @return array
   */
  private function import_prefs()
  {
    return shortcode_atts( array(
        'match_field' => 'test',
        'match_preference' => 0,
            ), get_option( self::prefs ) );
  }

  /**
   * Get post args
   *
   * @return array
   */
//		protected function get_post_args() {
//			if ( property_exists( $this, 'post_args' ) ) {
//				return $this->post_args;
//			}
//
//			return array(
//				'action' => $this->identifier,
//				'nonce'  => wp_create_nonce( $this->identifier ),
//				'timeout'   => 1,
//				'blocking'  => true,
//				'body'      => $this->data,
//				'cookies'   => $_COOKIE,
//				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
//			);
//		}

  /**
   * increments a status value
   * 
   * @param string  $status_field name of the status count to increment
   * @return null
   */
  private function increment_status( $status_field )
  {
    $status_info = get_transient( self::status );

    $count = isset( $status_info[$status_field] ) ? $status_info[$status_field] : 0;
    $count++;
    $status_info[$status_field] = $count;

    set_transient( self::status, $status_info );
  }

}
