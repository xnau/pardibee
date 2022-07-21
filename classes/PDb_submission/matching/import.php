<?php

/**
 * provides matching services for a CSV import
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission\matching;

defined( 'ABSPATH' ) || exit;

class import extends record {

  /**
   * tells if the current operation is a csv import
   * 
   * @return bool
   */
  public function is_csv_import()
  {
    return true;
  }

  /**
   * sets the default match mode (duplicate preference)
   */
  protected function setup_match_mode()
  {
    /*
     * this is determined by the setting on the CSV inport page
     * * add
     * * update
     * * skip
     */
    $this->set_match_mode( $this->post['match_preference'] );
  }

  /**
   * sets the current match field
   * 
   * this is the field that is checked for a match between two records
   * 
   */
  protected function set_match_field()
  {
    $this->match_field = $this->post['match_field'];
  }

}
