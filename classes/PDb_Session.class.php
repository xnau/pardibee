<?php

/*
 * manages user sessions for the plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    2.0
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
   * Whether to use PHP $_SESSION or WP_Session
   *
   * @var bool
   */
  private $use_php_sessions = false;

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
    $this->use_php_sessions = (bool) $plugin_setting['use_php_sessions'] || PDb_Base::wp_session_plugin_is_active();  $this->session_name = Participants_Db::$prefix . 'session';

    Participants_Db::initialize_session(); // this is only to set up the cache limiter now
    
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
