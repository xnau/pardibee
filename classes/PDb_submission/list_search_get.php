<?php

/**
 * models a list search submission in the GET input
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission;

class list_search_get extends list_search_submission {
  
  /**
   * 
   */
  public function __construct()
  {
    if ( isset( $_GET['search_field'] ) && is_array( $_GET['search_field'] ) ) {
      
      $this->input = filter_input_array( INPUT_GET, \PDb_List_Query::multi_search_input_filter() );
      
      $this->is_multi = true;
    } else {
      
      $this->input = filter_input_array( INPUT_GET, \PDb_List_Query::single_search_input_filter() );
      
      if ( ! isset( $this->input['search_field'] ) || $this->input['search_field'] === 'none' ) {
        $this->input['search_field'] = '';
      }
    }
    
    $this->_prepare_submit_value();
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
    $this->input[ 'submit' ] = $this->has_search() ? 'search' : '';
    
    if ( $this->is_multi() ) {
      for (  $i = 0; $i < count( $this->input['search_field'] ); $i++ ) {
        if ( ! isset( $this->input['operator'][$i] ) ) {
          $this->input['operator'][$i] = \Participants_Db::plugin_setting_is_true('strict_search') ? '=' : 'LIKE';
        }
        if ( ! isset( $this->input['logic'][$i] ) ) {
          $this->input['logic'][$i] = \Participants_Db::plugin_setting_is_true('strict_search') ? 'AND' : 'OR';
        }
      }
    }
    
    if ( empty( $this->input['target_instance'] ) ) {
      $this->input['target_instance'] = '1';
    }
  }
}
