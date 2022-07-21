<?php

/**
 * handles the admin list filter
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2020  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
namespace PDb_admin_list;

use \Participants_Db;

defined( 'ABSPATH' ) || exit;

class filter {

  /**
   * 
   * @var string name of the admin user filter option
   */
  public static $filter_option = 'pdb-admin_list_filter';
  
  /**
   * @var string key for the filter cache
   */
  const cachekey = 'pdbadminlistfilter';

  /**
   * sets up the filter object
   */
  public function __construct()
  {
    $current_user = wp_get_current_user();
    self::$filter_option = self::$filter_option . '-' . $current_user->ID;
    $this->_update_filter();
  }

  /**
   * gets a filter array
   * 
   * this is used for pagination to set the query and the search form values
   * 
   * returns an array of default values if no filter array has been saved
   * 
   * @return array the filter values
   */
  public function get_filter()
  {
    $filter = wp_cache_get(self::cachekey);
    
    if ( ! $filter ) {
      $filter = get_option( self::$filter_option, $this->default_filter() );
      wp_cache_add( self::cachekey, $filter, \Participants_Db::cache_expire() );
    }
    
    return $filter;
  }

  /**
   * returns the named filter value
   * 
   * @param string $name of the value to get
   * @return string|int
   */
  public function value( $name )
  {
    return $this->get_filter()[$name];
  }

  /**
   * provides the number of filters
   * 
   * @return int
   */
  public function list_fiter_count()
  {
    return intval( $this->get_filter()['list_filter_count'] );
  }

  /**
   * gets a search array from the filter
   * 
   * provides a blank array if there is no defined filter at the index given
   * 
   * @param int $index filter array index to get
   * 
   * @return array
   */
  public function get_set( $index )
  {
    $filter = $this->get_filter();
    if ( isset( $filter['search'][$index] ) && is_array( $filter['search'][$index] ) ) {
      return $filter['search'][$index];
    } else {
      return $this->default_filter()['search'][0];
    }
  }

  /**
   * saves the filter array
   * 
   * @param array $filter
   */
  public function save_filter( $filter )
  {
    update_option( self::$filter_option, $this->validate_filter( $filter ) );
    wp_cache_delete( self::cachekey );
  }
  
  /**
   * resets the stored filter to the default
   */
  public function reset()
  {
    $default_filter = $this->default_filter();
    
    // retain the recent fields set
    $stored_filter = get_option( self::$filter_option, array() );
    if ( isset( $stored_filter['recent_fields'] ) ) {
      $default_filter['recent_fields'] = $stored_filter['recent_fields'];
    }
    
    $this->save_filter( $default_filter );
  }
  
  /**
   * provides the number of search filters
   * 
   * @return int
   */
  public function count()
  {
    return is_array( $this->get_filter() ) ? count( $this->get_filter() ) : 0;
  }
  
  /**
   * tells if there is a search defined
   * 
   * @return bool
   */
  public function has_search()
  {
    return $this->count() > 0;
  }
  
  /**
   * provides global filter values
   * 
   * @param string $name of the value to get
   * @return mixed
   */
  public function __get( $name )
  {
    $filter = $this->get_filter();
    if ( isset( $filter[$name] ) ) {
      return $filter[$name];
    }
    
    Participants_Db::debug_log(__METHOD__.' invalid property "' . $name . '"');
    
    return false;
  }
  
  /**
   * provides the list of recently used fields
   * 
   * @return array
   * 
   */
  public function recents()
  {
    $filter = $this->get_filter();
    return isset( $filter['recent_fields'] ) ? $filter['recent_fields'] : array();
  }
  
  /**
   * tells if a filter set is a valid search
   * 
   * @param int $index the index of the set to check
   * @return bool true if the search set is valid 
   */
  public function is_valid_set( $index )
  {
    $filter = $this->get_set($index);
    
    return $filter['search_field'] !== '' && $filter['search_field'] !== 'none' && ( \Participants_Db::is_column( $filter['search_field'] ) || in_array( $filter['search_field'], search_field_group::group_list() ) );
  }

  /**
   * updates the filter property
   * 
   * gets the incoming filter values from the POST array and updates the filter 
   * property, filling in default values as needed
   * 
   * @return null
   */
  private function _update_filter()
  {
    $filter = $this->get_filter();

    if ( filter_input( INPUT_POST, 'action', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::make_name') ) === 'admin_list_filter' ) {

      $post = filter_input_array( INPUT_POST, $this->list_filter_sanitize() );

      $filter['search'] = array();

      for ( $i = $post['list_filter_count']; $i > 0; $i-- ) {
        $filter['search'][] = current( $this->default_filter()['search'] );
      }

      foreach ( $post as $key => $postval ) {
        
        if ( is_array( $postval ) ) {
          
          foreach ( $postval as $index => $value ) {
            if ( isset( $filter['search'][$index] ) && $value !== '' ) {
              $filter['search'][$index][$key] = $value;
              if ( $key === 'search_field' ) {
                $filter['recent_fields'] = self::add_to_recents($value, $filter['recent_fields']);
              }
            }
          }
        } elseif ( in_array( $key, array('list_filter_count', 'sortBy', 'ascdesc') ) ) {
          $filter[$key] = $post[$key];
        }
      }
    } elseif ( $column_sort = filter_input( INPUT_GET, 'column_sort', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::make_name') ) ) {
      if ( $filter['sortBy'] !== $column_sort ) {
        // if we're changing the sort column, set the sort to ASC
        $filter['ascdesc'] = 'ASC';
      } else {
        $filter['ascdesc'] = $filter['ascdesc'] === 'ASC' ? 'DESC' : 'ASC';
      }
      $filter['sortBy'] = $column_sort;
    }
    
    $this->save_filter( $filter );
  }

  /**
   * makes sure the filter array is valid
   * 
   * @param array $filter
   * @return array
   */
  private function validate_filter( $filter )
  {
    // set invalid fields to default values
    if ( !isset( $filter['sortBy'] ) || ( isset( $filter['sortBy'] ) && !Participants_Db::is_column( $filter['sortBy'] ) ) ) {
      $filter['sortBy'] = 'date_recorded';
    }

    if ( isset( $filter['search'] ) && is_array( $filter['search'] ) ) {
      foreach ( $filter['search'] as $search ) {
        if ( !Participants_Db::is_column( $search['search_field'] ) ) {
          $search['search_field'] = 'none';
        }
      }
    }

    if ( !isset( $filter['list_filter_count'] ) || empty( $filter['list_filter_count'] ) ) {
      $filter['list_filter_count'] = 1;
    }
    
    if ( ! isset( $filter['recent_fields'] ) ) {
      $filter['recent_fields'] = array();
    }

    return $filter;
  }

  /**
   * provides the sanitize filter array for the list filter submission
   * 
   * @return array of filter settings
   */
  private function list_filter_sanitize()
  {
    return array(
        'list_filter_count' => FILTER_SANITIZE_NUMBER_INT,
        'ascdesc' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^(asc|desc)$/i')),
        'sortBy' => array('filter' => FILTER_CALLBACK, 'options' => 'PDb_Manage_Fields_Updates::make_name'),
        'search_field' => array('filter' => FILTER_CALLBACK, 'options' => 'PDb_Manage_Fields_Updates::make_name'),
        'operator' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^(gt|lt|=|!=|NOT LIKE|LIKE)$/i'), 'flags' => FILTER_REQUIRE_ARRAY),
        'value' => array('filter' => FILTER_DEFAULT, 'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK | FILTER_REQUIRE_ARRAY),
        'logic' => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^(OR|AND)$/'), 'flags' => FILTER_REQUIRE_ARRAY),
    );
  }

  /**
   * provides the default filter
   * 
   * @return array
   */
  public function default_filter()
  {
    $cachekey = __CLASS__ . 'default';
    $filter = wp_cache_get( $cachekey );

    if ( !$filter ) {
      
      $filter = array(
          'search' => array(
              0 => array(
                  'search_field' => 'none',
                  'value' => '',
                  'operator' => 'LIKE',
                  'logic' => 'AND'
              )
          ),
          'sortBy' => Participants_Db::plugin_setting( 'admin_default_sort' ),
          'ascdesc' => Participants_Db::plugin_setting( 'admin_default_sort_order' ),
          'list_filter_count' => 1,
          'recent_fields' => array(),
      );
      
      wp_cache_set( $cachekey, $filter, \Participants_Db::cache_expire() );
    }

    return $filter;
  }
  
  /**
   * adds a field to the recent fields array
   * 
   * @param string $fieldname
   * @param array $recents the array of recent fields
   * @return array
   */
  public static function add_to_recents( $fieldname, $recents )
  {
    // sets the number of recent fields to keep
    $max_recents = \Participants_Db::apply_filters('pdb-admin_list_max_recent_fields', 6 );
    
    if ( in_array( $fieldname, $recents ) ) {
      unset( $recents[ array_search( $fieldname, $recents ) ] );
    }
    
    $recents[] = $fieldname;
    
    while ( count( $recents ) > $max_recents ) {
      array_shift( $recents );
    }
    
    return $recents;
  }
        
  /**
   * provides the recent fields option
   * 
   * @param array $field_list list of fieldnames
   * @return array
   */
  public static function recent_field_option( $field_list )
  {
    $recents = array();

    if ( count( $field_list ) > 0 ) {
      $recents[ __('Recent Fields', 'participants-database' ) ] = 'optgroup';
      
      $group_fields = search_field_group::group_display_list();
      
      foreach ( $field_list as $fieldname ) {

        switch (true) {
          
          case \PDb_Form_Field_Def::is_field($fieldname):
            
            $recents[ Participants_Db::$fields[$fieldname]->title() . ' ' ] = $fieldname;
            break;
          
          case isset( $group_fields[$fieldname] ):
            
            $recents[ $group_fields[$fieldname] . ' ' ] = $fieldname;
        }
      }
    }

    return $recents;
  }

}
