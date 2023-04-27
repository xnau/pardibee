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

class query_list extends \PDb_submission\rest_api\request {
  /**
   * @var string 
   */
  protected $endpoint = 'query/list';

  /**
   * provides the parameter validation regex
   * 
   * @return string
   */
  protected function params_setup()
  {
    return '/(?P<filter>.+)';
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
        'filter' => array(
            'requred' => false,
            'description' => esc_html('The list shortcode filter string to filter the result set.'),
            'type' => 'string',
            'sanitize_callback' => [ $this, 'sanitize_filter' ],
            'validate_callback' => [ $this, 'validate_filter' ],
        ),
    );
  }

  /**
   * validates a filter argument
   * 
   * @param string $filter
   * @return true if valid
   */
  public function validate_filter( $filter )
  {
    return strlen( $filter ) === strlen( $this->sanitize_filter( $filter ) );
  }

  /**
   * sanitizes the filter string
   * 
   * @param string $filter
   * @return string
   */
  public function sanitize_filter( $filter )
  {
    return filter_var( $filter, FILTER_DEFAULT, \Participants_Db::string_sanitize() );
  }

  /**
   * provides the response data
   * 
   * @return array
   */
  protected function response()
  {
    $list = db::get_list( $this->user_role, $this->list_filter_params() );
    
    return count( $list );
  }

  /**
   * provides the set list filter array
   * 
   * @return array
   */
  protected function list_filter_params()
  {
    $filter = $this->get_param('filter');
    $filter_params['filter'] = empty( $filter ) ? 'id=*' : $filter;
    $filter_params['fields'] = 'id'; 
    
    return $filter_params;
  }

  
}
