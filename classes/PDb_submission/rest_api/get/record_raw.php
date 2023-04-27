<?php

/**
 * handles the single record GET request
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

namespace PDb_submission\rest_api\get;
use PDb_submission\rest_api\db;

class record_raw extends \PDb_submission\rest_api\request {

  /**
   * @var string 
   */
  protected $endpoint = 'record/raw';

  /**
   * provides the parameter validation regex
   * 
   * this must include the leading slash
   * 
   * @return string
   */
  protected function params_setup()
  {
    return '/(?P<id>\d+)';
  }

  /**
   * provides the method or methods
   * 
   * @return string
   */
  public function methods()
  {
    return \WP_REST_Server::READABLE;
  }

  /**
   * provides an array of argument declarations
   * 
   * @return array
   */
  protected function args()
  {
    return array();
  }

  /**
   * provides the response data
   * 
   * @return array
   */
  protected function response()
  {
    return db::get_record( $this->params[ 'id' ], $this->user_role );
  }

}
