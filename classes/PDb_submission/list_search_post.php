<?php

/**
 * models a list search submission from the POST input
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

class list_search_post extends list_search_submission {
  
  /**
   * 
   */
  public function __construct()
  {
    if ( isset( $_POST['search_field'] ) && is_array( $_POST['search_field'] ) ) {
      
      $this->input = filter_input_array( INPUT_POST, \PDb_List_Query::multi_search_input_filter() );
      
      $this->is_multi = true;
    } else {
      
      $this->input = filter_input_array( INPUT_POST, \PDb_List_Query::single_search_input_filter() );
      
      if ( ! isset( $this->input['search_field'] ) || $this->input['search_field'] === 'none' ) {
        $this->input['search_field'] = '';
      }
      
      if ( self::split_search_preference() ) {
        $this->prepare_split_search();
      }
    }
    
    $this->_prepare_submit_value();
  }
  
}
