<?php

/**
 * provides the query for a record update
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

class update_query extends base_query {

  /**
   * provides the query top clause
   * 
   * @return string
   */
  protected function top_clause()
  {
    $sql = 'UPDATE ' . \Participants_Db::participants_table() . ' SET ';

    if ( $this->needs_date_updated_timestamp() ) {
      $sql .= ' `date_updated` = "' . \Participants_Db::timestamp_now() . '", ';
    }
    
    return $sql;
  }

  /**
   * tells if the query needs the date updated clause
   * 
   * @return bool
   */
  private function needs_date_updated_timestamp()
  {
    if ( !$this->is_import ) {
      return true;
    }

    return !isset( $this->post[ 'date_updated' ] ) || !\PDb_Date_Parse::is_mysql_timestamp( $this->post[ 'date_updated' ] );
  }

  /**
   * provides the query where clause
   * 
   * @return string
   */
  protected function where_clause()
  {
    return " WHERE id = " . $this->record_id;
  }
  
  /**
   * provides the name of the query mode
   * 
   * @return string
   */
  protected function query_mode()
  {
    return 'update';
  }

}
