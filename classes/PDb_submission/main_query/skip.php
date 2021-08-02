<?php

/**
 * provides the query for a skipped write
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

namespace PDb_submission\main_query;

class skip {
  
  /**
   * @param array $post
   * @param int $record_id
   */
  public function __construct( $post, $record_id )
  {
  }

  /**
   * provides the column object array
   * 
   * @param array $function_columns
   * @return array
   */
  public function column_array( $function_columns )
  {
    return array();
  }
}
