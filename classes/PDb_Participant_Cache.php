<?php

/*
 * manages the cache for participant data
 *
 * this works by dividing the database into blocks of records. When a request for 
 * a record comes in, the block it is in is loaded from the cache. This allows potentially 
 * hundreds of database queries to be reduced to a few queries: one for each block
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    1.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Participant_Cache {

  /**
   * @var string prefix for the transient name
   */
  const prefix = 'participant_cache_';

  /**
   * @var name of the site transient to use for the cache staleness status
   * 
   * this transient holds an array of flags, one for each cache group, that are  
   * set to true when the data is stale
   */
  const stale_flags = 'participant_cache_stale_flags';

  /**
   * @var int identifies the cache group
   */
  private $cache_group;

  /**
   * @var int the number of records in the cache group
   */
  private $group_size;

  /**
   * @var int the id of the record of interest
   */
  private $id;

  /**
   * @var array|bool the result of the cache get
   */
  private $data;

  /**
   * @var bool current cache staleness state: true if cache is stale
   */
  private $staleness;

  /**
   * supplies the cached data for the record
   * 
   * @param int $id
   * 
   * @return array|bool data array or bool false if no cached record found
   */
  public static function get_participant( $id )
  {
    $cache = new self( $id );
    return $cache->get();
  }

  /**
   * static function to clear the cache in which the record is found
   * 
   * @param int $id the id of the record
   */
  public static function clear_cache( $id )
  {
    $cache = new self( $id );
    $cache->clear();
  }

  /**
   * sets a cache to stale
   * 
   * this would be done whenever a record is created or altered
   */
  public static function is_now_stale( $id )
  {
    $cache = new self( $id );
    $cache->set_stale();
  }
  
  /**
   * clears all stale flags
   */
  public static function make_all_stale()
  {
    delete_transient(self::stale_flags);
  }

  /**
   * sets up the cache handler
   * 
   * @param int $id of the record to get or update
   */
  private function __construct( $id )
  {
    $this->id = (int) $id;
    
    /**
     * sets the number or records to cache in each group
     * 
     * @filter pdb-get_participant_cache_size
     * @param int number of records in a group
     * @return int
     */
    $this->group_size = Participants_Db::apply_filters( 'get_participant_cache_size', 100 );
    $this->cache_group = (int) ( $this->id / $this->group_size );

    $this->setup_staleness();
    $this->set_data();
  }

  /**
   * clears the cache where the target id is found
   */
  private function clear()
  {
    $this->_clear_cache();
  }

  /**
   * supplies the participant data
   * 
   * @return array
   */
  private function get()
  {
    $participant_data = isset( $this->data[ $this->id ] ) ? (array) $this->data[ $this->id ] : false;
    
    if ( ! $participant_data ) {
      Participants_Db::debug_log(__METHOD__ . ' cache missed for participant id ' . $this->id );
    }
    
    return $participant_data;
  }
  /**
   * provides the cache persistence time
   * 
   * defaults to 24 hours
   * 
   * @return int seconds
   */
  private function expiration()
  {
    return Participants_Db::apply_filters( 'participant_cache_expiration',  DAY_IN_SECONDS );
  }

  /**
   * sets the cache to stale
   */
  private function set_stale()
  {
    $this->set_staleness( true );
  }

  /**
   * sets the cache to fresh
   */
  private function set_fresh()
  {
    $this->set_staleness( false );
  }

  /**
   * tells if the cache is stale
   * 
   * @return bool true if cache is stale
   */
  private function cache_is_stale()
  {
    return $this->staleness === true;
  }

  /**
   * gets the staleness transient
   */
  private function setup_staleness()
  {
    $staleness = $this->get_staleness();

    $this->staleness = (bool) ( $staleness === false || ( isset( $staleness[ $this->cache_group ] ) ? $staleness[ $this->cache_group ] : true ) );
  }

  /**
   * sets the staleness
   * 
   * @param bool $state
   */
  private function set_staleness( $state = true )
  {
    $this->staleness = (bool) $state;

    $staleness = $this->get_staleness();
    $staleness[ $this->cache_group ] = $this->staleness;

    set_transient( self::stale_flags, $staleness, $this->expiration() );
  }

  /**
   * provides the stored staleness record
   * 
   * @return array as $id => $staleness
   */
  private function get_staleness()
  {
    $staleness = get_transient( self::stale_flags );
    
    return $staleness ? : array();
  }

  /**
   * provides the cache key used to get/set the cache
   * 
   * @return string
   */
  private function _cache_key()
  {
    return self::prefix . $this->cache_group;
  }

  /**
   * sets the data property
   */
  private function set_data()
  {
    $this->data = empty( $this->data ) ? $this->get_cache() : $this->data;

    if ( $this->data === false || $this->cache_is_stale() ) {
      $this->refresh_cache();
    }
  }

  /**
   * refreshes the cache
   * 
   * @global wpdb $wpdb
   */
  private function refresh_cache()
  {
    global $wpdb;

    $series_start = $this->cache_group * $this->group_size;
    $series_end = $series_start + $this->group_size;

    $sql = 'SELECT * FROM ' . Participants_Db::$participants_table . ' p WHERE p.id >= ' . $series_start . ' AND p.id < ' . $series_end . ' ORDER BY p.id ASC';

    $this->data = $wpdb->get_results( $sql, OBJECT_K );

    $this->set_cache();

    $this->set_fresh();
    
    Participants_Db::debug_log( __METHOD__ . ': Refreshing Participants Database cache for cache group ' . $this->cache_group, 2 );
  }

  /**
   * supplies the cached value
   * 
   * @return array|bool false if not cached or cache expired
   */
  private function get_cache()
  {
    return get_transient( $this->_cache_key() );
  }

  /**
   * sets the cache value
   * 
   */
  private function set_cache()
  {
    set_transient( $this->_cache_key(), $this->data, $this->expiration() );
  }

  /**
   * clears the cache
   */
  private function _clear_cache()
  {
    delete_transient( $this->_cache_key() );
  }

}
