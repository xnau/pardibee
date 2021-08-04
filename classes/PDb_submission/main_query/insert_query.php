<?php

/**
 * provides the query for a record insert
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

class insert_query extends base_query {

  /**
   * provides the query top clause
   * 
   * @return string
   */
  protected function top_clause()
  {
    $sql = 'INSERT INTO ' . \Participants_Db::participants_table() . ' SET ';

    if ( $this->needs_timestamp( 'date_recorded' ) ) {
      $sql .= ' `date_recorded` = NOW(), ';
    }
    if ( $this->needs_timestamp( 'date_updated' ) ) {
      $sql .= ' `date_updated` = NOW(), ';
    }
    
    return $sql;
  }

  /**
   * tells if the query needs the timestamp clause
   * 
   * @param string $type name of the timestamp to check
   * @return bool
   */
  private function needs_timestamp( $type )
  {
    return !isset( $this->post[ $type ] ) || !\PDb_Date_Parse::is_mysql_timestamp( $this->post[ $type ] );
  }

  /**
   * provides the query where clause
   * 
   * @return string
   */
  protected function where_clause()
  {
    return '';
  }

  /**
   * provides the name of the query mode
   * 
   * @return string
   */
  protected function query_mode()
  {
    return 'insert';
  }

}
