<?php

/**
 * handles a delete record request
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

namespace PDb_submission\rest_api\post;
use PDb_submission\rest_api\db;

class delete_record extends \PDb_submission\rest_api\request {

  /**
   * @var string 
   */
  protected $endpoint = 'record/delete';

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
    return \WP_REST_Server::EDITABLE;
  }

  /**
   * provides an array of argument declarations
   * 
   * @return array
   */
  protected function args()
  {
    return array(
        'delete_files' => array(
            'requred' => false,
            'sanitize_callback' => function ( $param ) {
              return boolval( $param );
            },
            'validate_callback' => function ( $param ) {
              return is_bool( boolval( $param ) );
            },
            'default' => false,
        )
    );
  }

  /**
   * provides the response data
   * 
   * @return array
   */
  protected function response()
  {
    if ( db::record_exists( $this->params[ 'id' ] ) ) {
      db::delete_record( $this->params['id'], $this->params['delete_files'] );
      return true;
    } else {
      return false;
    }
  }

}
