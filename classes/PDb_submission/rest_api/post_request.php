<?php

/**
 * base class for a post request
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

abstract class post_request extends request {
  
  
  /**
   * @var array of record data
   */
  protected $data;
  

  /**
   * provides the request response
   * 
   * @param \WP_REST_Request $request
   * @return string JSON
   */
  public function rest_response( $request )
  {
    $this->params = $request->get_url_params();
    
    $this->data = $this->sanitize_data( $request->get_body_params() );

    return rest_ensure_response( $this->response() );
  }
}
