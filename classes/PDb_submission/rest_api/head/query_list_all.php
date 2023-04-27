<?php

/**
 * handles providing a count of records
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2023  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission\rest_api\head;
use PDb_submission\rest_api\db;

class query_list_all extends query_list {

  /**
   * provides the parameter validation regex
   * 
   * @return string
   */
  protected function params_setup()
  {
    return '';
  }
  
}
