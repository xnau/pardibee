<?php

/*
 * manages the cache for participant data
 *
 * this works by dividing the database into blocks of records. When a request for 
 * a record comes in, the block it is in is loaded from the cache. This allows potentially 
 * hundreds of database queries to be reduced to a few querites: one for each block
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Participant_Cache {

  /**
   * @var string name of the cache group
   */
  const group = 'get_participant';

  /**
   * @var name of the site transient to use for the cache staleness status
   * 
   * this transient holds an array of flags, one for each cache, that are set to 
   * true when the data is stale
   */
  const stale_flags = 'participant_cache_stale_flags';

  /**
   * @var string identifies the cache within the group
   */
  private $cache_key;

  /**
   * @var int the size of the cache (number of records it holds)
   */
  private $cache_size;

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
   * sets up the cache handler
   * 
   * @param int $id of the record to get or update
   */
  public function __construct( $id )
  {
    $this->id = (int) $id;
    /**
     * @version 1.6.2.6
     * 
     * filter 'pdb-get_participant_cache_size' sets the number or records to cache in each group
     */
    $this->cache_size = Participants_Db::apply_filters( 'get_participant_cache_size', 100 );
    $this->cache_key = (int) ( $this->id / $this->cache_size );
    
    $this->setup_staleness();
    $this->set_data();
  }

  /**
   * clears the cache where the target id is found
   */
  public function clear()
  {
    wp_cache_delete( $this->cache_key, self::group );
  }

  /**
   * supplies the participant data
   */
  public function get()
  {
    return isset( $this->data[$this->id] ) ? (array) $this->data[$this->id] : false;
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
  public static function is_now_stale($id) {
    $cache = new self($id);
    $cache->set_stale();
  }

  /**
   * supplies the cached data for the record
   * 
   * @param int $id
   * 
   * @return array|bool data array or bool false if no record matches the id
   */
  public static function get_participant( $id )
  {
    $cache = new self( $id );
    return $cache->get();
  }

  /**
   * sets the cache to stale
   */
  private function set_stale()
  {
    if ( !$this->cache_is_stale() ) {
      $this->set_staleness( true );
    }
  }

  /**
   * sets the cache to fresh
   */
  private function set_fresh()
  {
    if ( $this->cache_is_stale() ) {
      $this->set_staleness( false );
    }
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
    $staleness = wp_cache_get( self::stale_flags );
    
    $this->staleness = $staleness === false || ( ! isset($staleness[$this->cache_key]) ? true : $staleness[$this->cache_key] );
  }

  /**
   * sets the staleness
   * 
   * @param bool $state
   */
  private function set_staleness( $state = true )
  {
    $this->staleness = (bool) $state;
    $staleness = (array) wp_cache_get( self::stale_flags );
    $staleness[$this->cache_key] = $this->staleness;
    wp_cache_set( self::stale_flags, $staleness );
  }

  /**
   * sets the data property
   */
  private function set_data()
  {
    $this->data = wp_cache_get( $this->cache_key, self::group );
    if ( $this->data === false || $this->cache_is_stale() ) {
      $this->refresh_cache();
    } else {
      //error_log(__METHOD__.' getting from cache...key: '.$this->cache_key);
    }
  }

  /**
   * refreshes the cache
   */
  private function refresh_cache()
  {
    global $wpdb;

    $series_start = $this->cache_key * $this->cache_size;
    $series_end = $series_start + $this->cache_size;

    $sql = 'SELECT * FROM ' . Participants_Db::$participants_table . ' p WHERE p.id >= ' . $series_start . ' AND p.id < ' . $series_end . ' ORDER BY p.id ASC';

    $result = $wpdb->get_results( $sql, OBJECT_K );
    
//    error_log(__METHOD__.' query: '.$wpdb->last_query);

    $this->data = (array) $result;
    
//    error_log(__METHOD__.' data: '.print_r($this->data,1));

    wp_cache_set( $this->cache_key, $this->data, self::group, Participants_Db::apply_filters( 'participant_cache_time', 0) );
    
    $this->set_fresh();
  }

}
