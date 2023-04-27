<?php

/**
 * handles adding a single record
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

class add_record extends \PDb_submission\rest_api\post_request {
  
  /**
   * @var string 
   */
  protected $endpoint = 'record/add';
  
  /**
   * provides the response
   * 
   * @return int record id
   */
  protected function response()
  {
    $result = \Participants_Db::write_participant( $this->filtered_data() );
    
    $validation = \Participants_Db::$validation_errors;
    /** @var \PDb_FormValidation $validation */
    
    if ( is_a( $validation, '\PDb_FormValidation' ) && $validation->errors_exist() )
    {
      $errors = current( $validation->get_validation_errors() );
      /** @var \PDb_submission\validation_error_message $errors */
      return new \WP_Error( $errors->slug, $errors->error_message(), ['status' => 404] );
    }
    
    if ( $result )
    {
      do_action( 'pdb-after_submit_add', \Participants_Db::get_participant( $result ) );
    }
    
    return new \WP_REST_Response( [ 'record_id' => $result ] );
  }
  
  /**
   * pass the data through the pdb-before_submit_update filter
   * 
   * this also triggers updates to dynamic fields
   * 
   * @return array the filtered data array
   */
  private function filtered_data()
  {
    // we merge in the default data set for the record so that dynamic fields will be updated
    return \Participants_Db::apply_filters('before_submit_add', array_merge( \Participants_Db::get_participant(false), $this->data ) );
  }

  /**
   * provides the parameter validation regex
   * 
   * this must include the leading slash
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
    return \WP_REST_Server::EDITABLE;
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
   * sanitizes the data array
   * 
   * @param array $data
   * @return array
   */
  public function sanitize_data( $data )
  {
    $user_allowed_fields = \PDb_submission\rest_api\db::user_field_list( $this->user_role, true );
    
    $sanitized_data = [];
    
    foreach( $data as $fieldname => $value )
    {
      if ( in_array( $fieldname, $user_allowed_fields ) )
      {
        $sanitized_data[$fieldname] = filter_var( $value, FILTER_DEFAULT, \Participants_Db::string_sanitize() );
      }
    }
    
    return $sanitized_data;
  }
  
  /**
   * validates the submitted data array
   * 
   * @param array $data
   * @return bool
   */
  public function validate_data( $data )
  {
    return is_array( $data );
  }
  
}
