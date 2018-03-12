<?php

/*
 * manages user sessions for the plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    0.11
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    FormElement class, Shortcode class
 * 
 * based on EDD_Session class by Pippin Williamson
 * https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/class-edd-session.php
 * 
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Session {

  /**
   * Holds the session data
   *
   * @var array|WP_Session
   */
  private $session;

  /**
   * Whether to use PHP $_SESSION or WP_Session
   *
   * @var bool
   */
  private $use_php_sessions = false;

  /**
   * a name for our session array
   * 
   * @var string
   */
  public $session_name;

  /**
   * true if the current user does not allow cookies
   * 
   * @version 1.6 removed; not reliable
   * 
   * @var bool
   */
  public $no_user_cookie = false;

  /**
   * construct the class
   * 
   * we check the setting for using PHP session, if false, we use a WP Transient-based session
   * 
   * we are just using this alternate form of session mnagement instead of PHP 
   * sessions for now
   */
  public function __construct()
  {
    $plugin_setting = get_option(Participants_Db::$participants_db_options);
    $this->use_php_sessions = (bool) $plugin_setting['use_php_sessions'];

    $this->session_name = Participants_Db::$prefix . 'session';

    Participants_Db::initialize_session();

    if ( $this->use_php_sessions ) {

      if ( !session_id() && !headers_sent() ) {
        /*
         * as of 1.7.6 we only start the session in PDb_Base::control_caching()
         */
        //session_start();
      }
    } else {


      $wp_session_path = Participants_Db::$plugin_path . '/vendor/wp-session-manager/';

      if ( !defined( 'WP_SESSION_COOKIE' ) ) {
        /**
         * @version 1.6.2.6
         * 
         * for compatibility with servers using varnish cache
         */
        define( 'WP_SESSION_COOKIE', apply_filters( 'pdb-cookie_name', Participants_Db::plugin_setting( 'cookie_name', 'pdb_wp_session' ) ) );
      }

      if ( !class_exists( 'Recursive_ArrayAccess' ) ) {
        include $wp_session_path . 'class-recursive-arrayaccess.php';
      }

      // Include utilities class
      if ( !class_exists( 'WP_Session_Utils' ) ) {
        include $wp_session_path . 'class-wp-session-utils.php';
      }

      // Include WP_CLI routines early
      if ( defined( 'WP_CLI' ) && WP_CLI ) {
        include $wp_session_path . 'wp-cli.php';
      }

      // Only include the functionality if it's not pre-defined.
      if ( !class_exists( 'WP_Session' ) ) {
        include $wp_session_path . 'class-wp-session.php';
        include $wp_session_path . 'wp-session.php';
      }

      // sets up the new sessions table
      // Create the required table.
      add_action( 'admin_init', array( $this, 'create_sm_sessions_table' ) );
      add_action( 'wp_session_init', array( $this, 'create_sm_sessions_table' ) );
      
    }

    //add_action( 'plugins_loaded', array( $this, 'init' ), -1 );
    $this->init();
  }

  /**
   * Create the new table for housing session data if we're not still using
   * the legacy options mechanism. This code should be invoked before
   * instantiating the singleton session manager to ensure the table exists
   * before trying to use it.
   *
   * @see https://github.com/ericmann/wp-session-manager/issues/55
   */
  function create_sm_sessions_table()
  {
    if ( defined( 'WP_SESSION_USE_OPTIONS' ) && WP_SESSION_USE_OPTIONS ) {
      return;
    }

    $current_db_version = '0.1';
    $created_db_version = get_option( 'sm_session_db_version', '0.0' );

    if ( version_compare( $created_db_version, $current_db_version, '<' ) ) {
      global $wpdb;

      $collate = '';
      if ( $wpdb->has_cap( 'collation' ) ) {
        $collate = $wpdb->get_charset_collate();
      }

      $table = "CREATE TABLE {$wpdb->prefix}sm_sessions (
		  session_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		  session_key char(32) NOT NULL,
		  session_value LONGTEXT NOT NULL,
		  session_expiry BIGINT(20) UNSIGNED NOT NULL,
		  PRIMARY KEY  (session_key),
		  UNIQUE KEY session_id (session_id)
		) $collate;";

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $table );

      add_option( 'sm_session_db_version', '0.1', '', 'no' );

      WP_Session_Utils::delete_all_sessions_from_options();
    }
  }

  /**
   * Setup the WP_Session instance
   * 
   * @return void
   */
  public function init()
  {

    if ( $this->use_php_sessions ) {
      $this->session = isset( $_SESSION[$this->session_name] ) && is_array( $_SESSION[$this->session_name] ) ? $_SESSION[$this->session_name] : array();
    } else {

      $this->session = WP_Session::get_instance();
    }

    return $this->session;
  }

  /**
   * get the session ID
   * 
   * @return string Session ID
   */
  public function get_id()
  {
    $sessid = isset( $this->session->session_id ) ? $this->session->session_id : session_id();
    return $sessid;
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
    return isset( $this->session[$key] ) ? maybe_unserialize( $this->session[$key] ) : $default;
  }

  /**
   * supplies the current record ID if available
   * 
   * @param bool  $get_var set to true to also check the get var for the ID
   * 
   * @return  int|bool the ID or bool false
   */
  public function record_id( $get_var = false )
  {
    if ( apply_filters( 'pdb-record_id_in_get_var', $get_var ) && $id = filter_input( INPUT_GET, Participants_Db::$single_query, FILTER_SANITIZE_NUMBER_INT ) ) {
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

    $key = sanitize_key( $key );
    $array_object = isset( $this->session[$key] ) ? maybe_unserialize( $this->session[$key] ) : false;

    switch ( $this->use_php_sessions ) {
      case true:
        return is_array( $array_object ) ? $array_object : $default;
      case false:
        return is_object( $array_object ) ? $array_object->toArray() : $default;
    }
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
//    error_log(__METHOD__.' setting: '.$key.'
//      
//value: ' . print_r( $value,1 ) . '
//      
//trace: '.print_r(  wp_debug_backtrace_summary(),1));

    $key = sanitize_key( $key );

    $this->session[$key] = $value;

    if ( $this->use_php_sessions )
      $_SESSION[$this->session_name] = $this->session;

    return $this->session[$key];
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
      $this->session[$key] = self::deep_merge( $stored, $value );
    else
      $this->session[$key] = $value;

    if ( $this->use_php_sessions )
      $_SESSION[$this->session_name] = $this->session;

    return $this->session[$key];
  }

  /**
   * clears a session variable
   * 
   * @param string $name the name of the variable to delete
   * @return null
   */
  public function clear( $name )
  {
//    error_log(__METHOD__.' clearing: '.$name.' 
//      
//trace: '.print_r(  wp_debug_backtrace_summary(),1));

    $key = sanitize_key( $name );

    unset( $this->session[$key] );

    if ( $this->use_php_sessions )
      $_SESSION[$this->session_name] = $this->session;
  }

  /**
   * displays all session object values
   * 
   */
  public function value_dump()
  {
    return print_r( $this, 1 );
  }

  /**
   * merges two arrays recursively
   * 
   * returned array will include unmatched elements from both input arrays. If 
   * there is an element key match, the element from $b will be present in the 
   * return value
   * 
   * @param array $a
   * @param array $b
   * @return array
   */
  public static function deep_merge( array $array1, array $array2 )
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

//  public static function deep_merge( $a, $b )
//  {
//    $a = (array) $a;
//    $b = (array) $b;
//    $c = $b;
//    foreach ( $a as $k => $v ) {
//      if ( isset( $b[$k] ) ) {
//        if ( is_array( $v ) && is_array( $b[$k] ) ) {
//          $c[$k] = self::deep_merge( $v, $b[$k] );
//        }
//      } else {
//        $c[$k] = $v;
//      }
//    }
//    return $c;
//  }
}
