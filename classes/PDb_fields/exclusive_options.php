<?php

/**
 * handles implementing the exclusive options attribute in selection fields
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2024  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class exclusive_options {
  
  /**
   * @var string field name
   */
  private $fieldname;
  
  /**
   * @var int the record ID
   */
  private $record_id;
  
  /**
   * @var string name of the cache group
   */
  const cachegroup = 'pdb-exclusive_options';
  
  /**
   * @var string the keyword for applying exclusive options
   */
  const keyword = 'exclusive';
  
  /**
   * provides the filter handler
   * 
   * @param array $attributes
   * @param string $fieldname
   * @param string $value
   * @return array of attributes
   */
  public static function filter_handler( $attributes, $fieldname, $value, $record_id )
  {
    $exclusive = new self( $fieldname, $record_id );
    
    if ( $exclusive->is_selected( $value ) )
    {
      $attributes['disabled'] = 'disabled';
    }
    
    return $attributes;
  }
  
  /**
   * sets up the class
   * 
   * @param string $fieldname
   * @param int $record_id
   */
  private function __construct( $fieldname, $record_id )
  {
    $this->fieldname = $fieldname;
    $this->record_id = $record_id;
  }
  
  /**
   * tells if the option value has been selected already
   * 
   * @param string $value
   * @return bool
   */
  private function is_selected( $value )
  {
    /**
     * @filter pdb-{$fieldname}_option_is_selected
     * @param bool true if the option value if found in the participant database table
     * @param string the option value
     * @return bool true if the option is considered selected
     */
    return \Participants_Db::apply_filters( $this->fieldname . '_option_is_selected', in_array( $value, $this->selected_options() ), $value );
  }
  
  /**
   * provides the list of selected options from the db
   * 
   * @global \wpdb $wpdb
   * @return array of option values
   */
  private function selected_options()
  {
    $selected_options = wp_cache_get( $this->cachekey(), self::cachegroup );
    
    if ( $selected_options !== false )
    {
      return $selected_options;
    }
      
    global $wpdb;
    
    $sql = 'SELECT DISTINCT p.' . $this->fieldname . ' FROM ' . \Participants_Db::participants_table() . ' p WHERE  p.id <> %s AND p.' . $this->fieldname . ' <> "" AND p.' . $this->fieldname . ' IS NOT NULL';
    
    $db_selections = $wpdb->get_col( $wpdb->prepare( $sql, $this->record_id )  );
    
    $selection_list = [];
    
    foreach( $db_selections as $selection )
    {
      $selection = maybe_unserialize( $selection );
      
      switch (true)
      {
        case is_array( $selection ):
          $selection_list = array_merge( $selection_list, $selection );
          break;
        
        default:
          $selection_list[] = $selection;
      }
    }
    
    wp_cache_add( $this->cachekey(), $selection_list, self::cachegroup, \Participants_Db::cache_expire() );
    
    return $selection_list;
  }
  
  /**
   * provides the cache key string
   * 
   * @return string
   */
  private function cachekey()
  {
    return $this->fieldname . $this->record_id;
  }
}
