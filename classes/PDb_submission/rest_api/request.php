<?php

/**
 * abstract class for handling a request
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

abstract class request {

  /**
   * @var string 
   */
  protected $endpoint;

  /**
   * @var array the provided parameter values
   */
  protected $params = [];

  /**
   * @var string plugin access level of the current user public, editor, admin
   */
  protected $user_role;

  /**
   * sets up the route
   */
  public function __construct()
  {
    add_action( 'rest_api_init', [ $this, 'register_route' ] );
  }

  /**
   * registers the route
   */
  public function register_route()
  {
    register_rest_route( routing::route_top, $this->route(), array(
        'methods' => $this->methods(),
        'callback' => [ $this, 'rest_response' ],
        'args' => $this->args(),
        'permission_callback' => [ $this, 'check_permissions' ],
    ) );
  }

  /**
   * provides the parameter validation regex
   * 
   * @return string
   */
  abstract protected function params_setup();

  /**
   * provides the method or methods
   * 
   * @return string
   */
  abstract public function methods();

  /**
   * provides the raw response data
   * 
   * @return int|string|array
   */
  abstract protected function response();

  /**
   * provides an array of argument declarations
   * 
   * @return array
   */
  abstract protected function args();

  /**
   * provides the request response
   * 
   * @param \WP_REST_Request $request
   * @return string JSON
   */
  public function rest_response( $request )
  {
    $this->params = $request->get_params();

    return rest_ensure_response( $this->response() );
  }

  /**
   * handles the request permissions
   * 
   * @param \WP_REST_Request $request
   * @return bool
   */
  public function check_permissions( $request )
  {
    $this->user_role = $this->user_plugin_role();
    
    if ( \WP_REST_Server::READABLE === $request->get_method() && $this->public_access_allowed() && $this->user_role === 'public' )
    {
      return true;
    }
    
    return $this->user_role === 'admin'|| $this->user_role === 'editor';
  }

  /**
   * provides the route string
   * 
   * @return string
   */
  protected function route()
  {
    return '/' . $this->endpoint . $this->params_setup();
  }
  
  /**
   * tells if public access is allowed
   * 
   * @return bool
   */
  protected function public_access_allowed()
  {
    return \Participants_Db::plugin_setting_is_true( 'api_public', false );
  }

  /**
   * provides the plugin role for the current user
   * 
   * @return string public, editor, admin
   */
  protected function user_plugin_role()
  {
    $role = 'public';

    foreach ( array( 'admin', 'editor' ) as $test_role )
    {
      if ( \Participants_Db::current_user_has_plugin_role( $test_role, 'api_access' ) ) {
        $role = $test_role;
        break;
      }
    }

    return $role;
  }
  
  /**
   * provides a parameter value
   * 
   * @param string $name
   * @return string|int|float
   */
  protected function get_param( $name )
  {
    return isset( $this->params[$name] ) ? $this->params[$name] : '';
  }

}
