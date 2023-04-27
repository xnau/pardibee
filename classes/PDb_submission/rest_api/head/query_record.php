<?php

/**
 * handles a head request for a record id
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

class query_record extends \PDb_submission\rest_api\request {
  /**
   * @var string 
   */
  protected $endpoint = 'query/record';

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
    return array(
        'id' => array(
            'requred' => true,
            'sanitize_callback' => function ( $param ) {
              return intval( $param );
            },
            'validate_callback' => function ( $param ) {
              return is_int( intval( $param ) );
            },
        ),
    );
  }

  /**
   * provides the response data
   * 
   * @return array
   */
  protected function response()
  {
    return db::record_exists( $this->params[ 'id' ] );
  }
  
}
