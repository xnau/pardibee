<?php

/**
 * provides a list of record data
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

class list_raw extends \PDb_submission\rest_api\request {

  /**
   * @var string 
   */
  protected $endpoint = 'list/raw';

  /**
   * provides the parameter validation regex
   * 
   * @return string
   */
  protected function params_setup()
  {
    return '';
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
        'orderby' => array(
            'description' => esc_html('the name of the field or fields used to sort the list'),
            'requred' => false,
            'type' => 'string',
            'sanitize_callback' => [ $this, 'sanitize_filter' ],
            'validate_callback' => [ $this, 'validate_filter' ],
        ),
        'order' => array(
            'description' => esc_html('the sort direction: "asc" or "desc"'),
            'requred' => false,
            'type' => 'string',
            'enum' => array( 'asc', 'desc' ),
        ),
        'fields' => array(
            'description' => esc_html('comma-separated list of fields to include in the result'),
            'requred' => false,
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
    
    return $list;
  }

  /**
   * provides the set list filter array
   * 
   * @return array
   */
  protected function list_filter_params()
  {
    $filter_params = [];
    foreach( ['filter','orderby','order','fields'] as $param )
    {
      $value = $this->get_param( $param );
      if ( strlen($value) )
      {
        $filter_params[$param] = $value;
      }
    }
    
    return $filter_params;
  }

}
