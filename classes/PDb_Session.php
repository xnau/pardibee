<?php

/**
 * manages user sessions for the plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    2.3
 * @link       https://github.com/ericmann/wp-session-manager
 * @depends    wp-session-manager
 * 
 * 
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Session {
  
  /**
   * @var string name of the session id variable
   */
  const id_var = 'sess';

  /**
   * construct the class
   * 
   */
  public function __construct()
  {
    $plugin_setting = get_option(Participants_Db::$participants_db_options);
    
    if ( isset( $plugin_setting['use_session_alternate_method'] ) && $plugin_setting['use_session_alternate_method'] ) {
      $this->obtain_session_id();
    }
  }

 

  /**
   * get a session variable
   * 
   * @param string $key Session key
   * @param string|array|bool $default the value to return if none is found in the session
   * @return string Session variable or $default value
   */
  public function get( $key, $default = false )
  {
    $key = sanitize_key( $key );
    return isset( $_SESSION[$key] ) ? maybe_unserialize( $_SESSION[$key] ) : $default;
  }

  /**
   * supplies the current record ID if available
   * 
   * @param bool $pid_only if true, don't get the id from the 'pdb' URL var 
   * 
   * @return  int|bool the ID or bool false
   */
  public function record_id( $pid_only = false )
  {
    if ( apply_filters( 'pdb-record_id_in_get_var', false ) ) {
      $id = 0;
      if ( ! $pid_only && array_key_exists( Participants_Db::$single_query, $_GET )  ) {
        $id = filter_input( INPUT_GET, Participants_Db::$single_query, FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
      } elseif ( array_key_exists( Participants_Db::$record_query, $_GET )  ) {
        $id = Participants_Db::get_participant_id( filter_input( INPUT_GET, Participants_Db::$record_query, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE ) );
      }
      if ( $id )
        return $id;
    }
    return $this->get( 'pdbid' );
  }

  /**
   * get a session variable array
   * 
   * @param string $key Session key
   * @param string|array|bool $default the value to return if none is found in the session
   * @return array Session variable or $default value
   */
  public function getArray( $key, $default = false )
  {
    $array_object = $this->get( $key );

    if (is_array( $array_object ) ) 
      return $array_object;
    
    if ( is_object( $array_object ) ) 
      return $array_object->toArray();
    
    return $default;
  }

  /**
   * Set a session variable
   *
   * @param $key Session key
   * @param $value Session variable
   * @return mixed Session variable
   */
  public function set( $key, $value )
  {
    $key = sanitize_key( $key );

    $_SESSION[$key] = $value;

    return $this->get( $key );
  }

  /**
   * update a session variable
   * 
   * if the incoming value is an array, it is merged with the stored value if it 
   * is also an array; if not, it stores the value, overwriting the stored value
   *
   * @param $key Session key
   * @param $value Session variable
   * @return mixed Session variable
   */
  public function update( $key, $value )
  {
    $key = sanitize_key( $key );
    $stored = $this->getArray( $key );

    if ( is_array( $value ) && is_array( $stored ) )
      $_SESSION[$key] = self::deep_merge( $stored, $value );
    else
      $_SESSION[$key] = $value;

    return $this->get( $key );
  }

  /**
   * clears a session variable
   * 
   * @param string $name the name of the variable to delete
   * @return null
   */
  public function clear( $name )
  {
    unset( $_SESSION[sanitize_key( $name )] );
  }
  
  /**
   * sets the session ID from the post or get
   * 
   * @return session id or bool false if not found
   */
  private function obtain_session_id()
  {
    $sessid = false;
    $validator = array('options' => array(
        'regexp' => '/^[0-9a-zA-Z,-]{22,40}$/',
        ) );
    if ( array_key_exists( self::id_var, $_POST ) ) {
      $sessid = filter_input( INPUT_POST, self::id_var, FILTER_VALIDATE_REGEXP, $validator );
    } elseif ( array_key_exists( self::id_var, $_GET ) ) {
      $sessid = filter_input( INPUT_GET, self::id_var, FILTER_VALIDATE_REGEXP, $validator );
    }
    
    if ( !$sessid ) {
      $value = false;
      /**
       * @filter pdb-session_get_var_keys
       * @param array of get var keys to check
       * @return string
       */
      $get_var_keys = Participants_Db::apply_filters('session_get_var_keys', array('cm') );
      foreach ( $get_var_keys as $key ) {
        $value = filter_input( INPUT_GET, 'cm', FILTER_VALIDATE_REGEXP, $validator );
        if ( $value ) {
          break;
        }
      }
      $sessid = $value;
    }
    
    if ( $sessid ) {
      $this->set_session_from_id( $sessid );
    
      if ( PDB_DEBUG > 1 ) {
        Participants_Db::debug_log(__METHOD__.' obtaining session id by alternate method: '.$sessid );
      }
    }
    
    return $sessid;
  }
  
  /**
   * sets up the php session with the found ID
   * 
   * @param sring $sessid
   */
  private function set_session_from_id( $sessid )
  {
    session_id($sessid);
  }
  

  /**
   * displays all session object values
   * 
   */
  public function value_dump()
  {
    return print_r( $_SESSION, 1 );
  }

  /**
   * merges two arrays recursively
   * 
   * returned array will include unmatched elements from both input arrays. If 
   * there is an element key match, the element from $b will be present in the 
   * return value
   * 
   * @param array $array1
   * @param array $array2
   * @return array
   */
  public static function deep_merge( Array $array1, Array $array2 )
  {
    $merged = $array1;

    foreach ( $array2 as $key => $value ) {
      if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
        $merged[$key] = self::deep_merge( $merged[$key], $value );
      } else {
        $merged[$key] = $value;
      }
    }
    return $merged;
  }
  
  /**
   * deletes the session table on uninstall
   */
  public static function uninstall()
  {
    global $wpdb;

    // delete session table
    $wpdb->query( "DROP TABLE {$wpdb->prefix}sm_sessions;" );
    
    delete_option('sm_session_db_version');
  }
}
