<?php

/**
 * sets up the request routing
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

namespace PDb_submission\rest_api;

class routing {
  
  /**
   * @var string namespace
   */
  const route_top = 'participants-database/v1';
  
  /**
   * establishes the routes
   * 
   * the plan here is to instantiate all the routes individually
   */
  public function __construct()
  {
    new get\record_raw();
    new get\record_export();
    new get\record_html();
    new get\list_raw();
    new get\list_export();
    new get\list_html();
    
    new head\query_record();
    new head\query_list();
    new head\query_list_all();
    
    new post\update_record();
    new post\delete_record();
    new post\add_record();
    
    // other plugins that add to these routes can use this action to initialize
    do_action( 'pdb-rest_routing_init' );
  }
}
