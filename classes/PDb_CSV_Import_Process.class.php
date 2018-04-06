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
   * sets a status value
   * 
   * @param string  $status_field of the value to set
   * @param mixed $value the value to save
   */
  public static function set_status_value( $status_field, $value ) {
    $status_info = get_transient(self::status);
    $status_info[$status_field] = $value;
    set_transient(self::status, $status_field);
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
    $status_info = get_transient(self::status);
    return $status_info[$status_field];
  }
  
  /**
   * initializes the status value
   * 
   * @param array $status_info the initialzing data
   */
  public function initialize_status_info( $status_info = array() )
  {
    set_transient(self::status, $status_info);
  }
  

		/**
		 * Dispatch the async request
		 *
		 * @return array|WP_Error
		 */
		public function dispatch() {
			$url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
			$args = $this->get_post_args();

			return wp_remote_post( esc_url_raw( $url ), $args );
		}
  
  /**
   * handles the ajax callback
   */
  public function maybe_handle()
  {
    ini_set('error_log', '/home/xnaucom/public_html/dev/error_log');
    error_log(__METHOD__);
    parent::maybe_handle();
  }
  
  /**
   * handles the record data importation
   * 
   * @param array $data the data from the CSV
   */
  protected function task( $data )
  {
    error_log(__METHOD__.' 
      
props: '.print_r($this,1).' 
  
data: '.print_r($data,1));
    
    
    /**
     * @version 1.6.3
     * @filter pdb-before_csv_store_record
     */
    $post = Participants_Db::apply_filters('before_csv_store_record', $data);
    
    $post['csv_file_upload'] = 'true';
    $post['subsource'] = Participants_Db::PLUGIN_NAME;
    $post['match_field'] = $this->match_field;
    $post['match_preference'] = $this->match_preference;
    
    // add the record data to the database
		$id = Participants_Db::process_form( $post, 'insert', false, array_keys($post) );
    
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
    
    self::set_status_value( 'process_complete', time() );
      
    do_action( 'pdb-csv_import_complete' );
  }
  
  
  /**
   * adds arguments to the post payload
   * 
   * @param array $data in the form $name => $value
   */
  public function add_arg( Array $data )
  {
    $args = $this->get_query_args();
    $this->query_args = array_merge( $args, $data );
  }

		/**
		 * Get post args
		 *
		 * @return array
		 */
		protected function get_post_args() {
			if ( property_exists( $this, 'post_args' ) ) {
				return $this->post_args;
			}

			return array(
				'action' => $this->identifier,
				'nonce'  => wp_create_nonce( $this->identifier ),
				'timeout'   => 1,
				'blocking'  => true,
				'body'      => $this->data,
				'cookies'   => $_COOKIE,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			);
		}
  
  /**
   * increments a status value
   * 
   * @param string  $status_field name of the status count to increment
   * @return null
   */
  private function increment_status( $status_field )
  {
    $status_info = get_transient(self::status);
    
    $count = isset( $status_info[$status_field] ) ? $status_info[$status_field] : 0;
    $count++;
    $status_info[$status_field] = $count;
    
    set_transient(self::status, $status_info);
  }
}
