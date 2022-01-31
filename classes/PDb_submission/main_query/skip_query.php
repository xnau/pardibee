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

defined( 'ABSPATH' ) || exit;

class skip_query {

  /**
   * @var int the record id
   */
  private $record_id;

  /**
   * @param array $post
   * @param int $record_id
   */
  public function __construct( $post, $record_id )
  {
    $this->record_id = $record_id;
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

  /**
   * tells if there are validation errors
   * 
   * @return bool true if there are validation errors
   */
  public function has_validation_errors()
  {
    return false;
  }

  /**
   * executes the query
   * 
   * does nothing in this case
   * 
   * @return int record id
   */
  public function execute_query()
  {
    return $this->record_id;
  }

}
