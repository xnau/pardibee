<?php

/**
 * maintains a cache of the list of records from the admin list display
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2025  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;

class record_list_cache {

  /**
   * @var string name of the transient
   */
  const transient = 'pdb-admin_edit_record_list';

  /**
   * @var int expiration time in seconds
   */
  private static $expiration = 3600; // one hour

  /**
   * sets the transient
   * 
   * @param array $record_list
   */
  public static function set( $record_list )
  {
    set_transient( self::transient, $record_list, self::$expiration );
  }

  /**
   * provides the record list value
   * 
   * @return array|bool false if it is not available
   */
  public static function get()
  {
    return get_transient( self::transient );
  }

  /**
   * clears the transient
   */
  public static function clear()
  {
    delete_transient( self::transient );
  }
  
  /**
   * refreshes the expiration time of the cache
   */
  public static function refresh()
  {
    $value = self::get();
    
    if ( $value )
    {
      self::set( $value );
    }
  }
}
