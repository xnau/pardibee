<?php

/**
 * manages user sessions using WP options
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission;

class db_session {
  
  /**
   * @var string base name of the transient
   */
  const option_base = 'pdb_db_sessions';
  
  /**
   * @var string holds the current session id
   */
  private $session_id = '';
  
  /**
   * @var array the stored session data
   */
  private $session_data;
  
  /**
   * initializes the user session
   * 
   * @param string $session_id
   */
  public function __construct( $session_id )
  {
    $this->session_id = filter_var( $session_id, FILTER_VALIDATE_REGEXP, array('options' => array(
        'regexp' => '/^[0-9a-zA-Z,-]{22,40}$/',
        ) ) );
    
    $this->setup_session_data();
  }
  
  /**
   * provides the session value
   * 
   * @param string $key
   * @return string|array|bool flase if no value found for the given key
   */
  public function get( $key )
  {
    return array_key_exists($key, $this->session_data ) ? $this->session_data[$key] : false;
  }
  
  /**
   * removes an element from the session data
   * 
   * @param string $key
   */
  public function clear( $key )
  {
    if ( array_key_exists($key, $this->session_data ) ) {
      unset( $this->session_data[$key] );
      $this->_update_session();
    }
  }
  
  /**
   * saves session data
   * 
   * @param string $key
   * @param string|array $data
   */
  public function save( $key, $data )
  {
    $this->update($key, $data);
  }
  
  /**
   * provides the current session ID
   * 
   * @return string
   */
  public function session_id()
  {
    return $this->session_id;
  }
  
  /**
   * closes the current session, deletes the db entry if there is one
   */
  public function close()
  {
    delete_transient( $this->transient_name() );
  }
  
  /**
   * provides the full session data array
   * 
   * @return array
   */
  public function to_array()
  {
    return $this->session_data;
  }
  
  /**
   * updates an item in a data array
   * 
   * @param string $key
   * @param string|array
   */
  private function update( $key, $data )
  {
    if ( is_array( $data ) ) {
      $this->session_data[$key] = \PDb_Session::deep_merge( (array) $this->get($key), $data );
    } else {
      $this->session_data[$key] = $data;
    }
    
    $this->_update_session();
  }
  
  /**
   * updates the session transient
   */
  private function _update_session()
  {
    set_transient( $this->transient_name(), $this->session_data, $this->expiration() );
  }
  
  /**
   * sets up the transient data
   * 
   */
  private function setup_session_data()
  {
    $data = get_transient( $this->transient_name() );
    
    $this->session_data = $data ? : array();
  }
  
  /**
   * provides the transient name
   * 
   * @return string
   */
  private function transient_name()
  {
    return self::option_base . '-' . $this->session_id;
  }
  
  /**
   * clears all class transients
   * 
   * @global \wpdb $wpdb
   */
  public static function close_all()
  {
    global $wpdb;
    
    $transient_list = $wpdb->get_col('SELECT `option_name` FROM ' . $wpdb->options . ' WHERE `option_name` LIKE "_transient_' . self::option_base . '-%"');
    
    foreach ( $transient_list as $transient ) {
      delete_transient( str_replace( '_transient_', '', $transient ) );
    }
  }
  
  /**
   * provides the transient lifetime value
   * 
   * @return int transient life in seconds
   */
  private function expiration()
  {
    return \Participants_Db::apply_filters('session_transient_lifetime', DAY_IN_SECONDS );
  }
}
