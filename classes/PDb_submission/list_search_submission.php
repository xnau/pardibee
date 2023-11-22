<?php

/**
 * models a list search submission
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission;
use Participants_Db;

defined( 'ABSPATH' ) || exit;

class list_search_submission {
   /**
   * @array holds the post input
   */
  protected $input;
  
  /**
   * @bool tells if the search is a multi-term search
   */
  protected $is_multi = false;
  
  /**
   * 
   */
  public function __construct()
  {
  }
  
  /**
   * tells if the search is a multi-term search
   * 
   * @return bool
   */
  public function is_multi()
  {
    return $this->is_multi;
  }
  
  /**
   * provides the search field name or array
   * 
   * @retrun string|array
   */
  public function search_field()
  {
    return $this->input['search_field'];
  }
  
  /**
   * provides the search term
   * 
   * @return string
   */
  public function value()
  {
    return $this->input['value'];
  }
  
  /**
   * provides the current list page
   * 
   * @return int
   */
  public function current_page()
  {
    return $this->input[ Participants_Db::$list_page ];
  }
  
  /**
   * tells if the current submission is a search
   * 
   * @return bool
   */
  public function is_search()
  {
    return $this->input['submit'] === 'search';
  }
  
  /**
   * provides the name of the submission type
   * 
   * @return string
   */
  public function submit_type()
  {
    return $this->input['submit'];
  }
  
  /**
   * provides the submission values
   * 
   * @return array
   */
  public function submission()
  {
    return $this->input;
  }
  
  /**
   * tells if the search parameters are present in the input
   * 
   * @return bool
   */
  public function has_search()
  {
    return ( isset( $this->input['search_field'] ) && ! empty( $this->input['search_field'] ) ) || ( isset( $this->input['sortBy'] ) && ! empty( $this->input['sortBy'] ) );
  }
  
  /**
   * prepares the post input for a split search
   */
  protected function prepare_split_search()
  {
    $search_terms = $this->split_search_terms( $this->input['value'] );
    
    if ( count( $search_terms ) > 1 ) {
    
      $search_field = $this->input['search_field'];
      $operator = $this->input['operator'];
      $this->input['search_field'] = array();
      $this->input['operator'] = array();
      $this->input['value'] = array();
      $this->input['logic'] = array();

      $i = 1;
      foreach ( $search_terms as $term ) {
        $this->input['value'][$i] = filter_var( $term, FILTER_DEFAULT, \Participants_Db::string_sanitize( FILTER_NULL_ON_FAILURE ) );
        $this->input['search_field'][$i] = $search_field;
        $this->input['operator'][$i] = $operator;
        $this->input['logic'][$i] = 'OR';
        $i++;
      }

      $this->is_multi = true;
    }
  }
  
   /**
   * splits the posted search string into an array
   * 
   * @param string $term the search input
   * @return array of search terms
   */
  protected function split_search_terms( $term )
  {
    $term_delimiter = Participants_Db::apply_filters( 'split_search_delimiter', ' ' );
    
    return array_map( 'trim', explode( $term_delimiter, urldecode( $term ) ) );
  }
  
  /**
   * prepares a submission input array for use as a filter configuration
   * 
   * allows for the use of several different submit button names
   * converts translated submit button value to key string
   * 
   * @retun array
   */
  protected function _prepare_submit_value()
  {
    $submit = $this->input[ 'submit' ];
    if ( !empty( $this->input[ 'submit_button' ] ) ) {
      $submit = $this->input[ 'submit_button' ];
    } elseif ( !empty( $this->input[ 'submit-button' ] ) ) {
      $submit = $this->input[ 'submit-button' ];
    }
    unset( $this->input[ 'submit-button' ], $this->input[ 'submit_button' ] );
    $this->input[ 'submit' ] = $this->untranslate_value( $submit );
  }

  /**
   * untranslates the submit value
   * 
   * @param string $value the submit value
   * 
   * @return string the key or untranslated value
   */
  protected function untranslate_value( $value )
  {
    if ( $key = array_search( $value, \PDb_List::i18n() ) ) {
      $value = $key;
    }
    return $value;
  }
  
  /**
   * trims spaces from the search terms
   */
  protected function trim_search_terms()
  {
    if ( is_array( $this->input['value'] ) )
    {
      array_walk( $this->input['value'], function(&$value){
        $value = self::deep_trim($value);
      } );
    }
    elseif ( ! empty( $this->input['value'] ) )
    {
      $this->input['value'] = self::deep_trim( $this->input['value'] );
    }
  }
  
  /**
   * performs a trim on a url-encoded string
   * 
   * @param string $value
   * @return string
   */
  public static function deep_trim($value)
  {
    return trim( urldecode( $value ) );
  }
  
  /**
   * tells if the split search preference is on
   * 
   * @return bool
   */
  public static function split_search_preference()
  {
    return Participants_Db::plugin_setting_is_true('split_search');
  }
}
