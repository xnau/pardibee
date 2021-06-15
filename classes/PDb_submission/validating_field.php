<?php

/**
 * models a field that is getting validated
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission;

class validating_field {

  /**
   * @var mixed the submitted values
   */
  private $value;

  /**
   *
   * @var string name of the field 
   */
  private $name;

  /**
   *
   * @var string name of the validation type to apply
   */
  private $validation;

  /**
   * @var string name of the form element
   */
  private $form_element;

  /**
   * @var string|bool current validated state
   */
  private $error_type;

  /**
   * @var int|bool  the record id or bool false if not known
   */
  private $record_id;

  /**
   * set it up
   */
  public function __construct( $value, $name, $validation = NULL, $form_element = false, $error_type = false, $record_id = false )
  {
    foreach ( get_object_vars( $this ) as $prop => $val ) {
      $this->{$prop} = $$prop;
    }
  }
  
  /**
   * supplies the record id
   * 
   * @return int
   */
  public function record_id()
  {
    return $this->record_id ? $this->record_id : 0;
  }

  /**
   * determines of the field needs to be validated
   * 
   * @return bool
   */
  public function is_validated()
  {
    return !( !\PDb_Form_Field_Def::is_field($this->name) || empty( $this->validation ) || $this->validation === NULL || $this->validation === 'no' || $this->validation === FALSE );
  }

  /**
   * determines of the field has been validated
   * 
   * @return bool
   */
  public function has_not_been_validated()
  {
    return $this->error_type === false;
  }

  /**
   * determines if the field is email validated
   * 
   * @return bool
   */
  public function is_email()
  {
    /*
     * the validation method key for an email address was formerly 'email' This 
     * has been changed to 'email-regex' but will still come in as 'email' from 
     * legacy databases. We test for that by looking for a field named 'email' 
     * in the incoming values.
     */
    return $this->validation == 'email-regex' || ($this->validation == 'email' && $this->name == 'email');
  }

  /**
   * determines if the field is a captcha
   * 
   * @return bool
   */
  public function is_captcha()
  {
    return strtolower( $this->validation ) == 'captcha';
  }

  /**
   * sets the error state
   * 
   * @param string $state the error state string
   */
  public function set_validation_state( $state )
  {
    $this->error_type = $state;
  }
  

  /**
   * sets the error state
   * 
   * alias of set_validation_state()
   * 
   * @param string $state the error state string
   */
  public function validation_state_is( $state )
  {
    $this->set_validation_state($state);
  }

  /**
   * tells of the field has not passed validation
   * 
   * @return bool true if the field has failed validation
   */
  public function is_not_valid()
  {
    return $this->error_type !== 'valid';
  }
  
  /**
   * tells if the field validation is a regex type
   * 
   * this tests to see if it is NOT a regex since testing positively for a regex 
   * is problematic
   * 
   * @return bool
   */
  public function is_regex_validation()
  {
    // check if it is one of the predefined methods
    if ( in_array( $this->validation, array_keys( \PDb_FormValidation::validation_methods() ) ) ) {
      return false;
    }
    
    $field_list = \Participants_Db::$fields;
    
    // check if the validation string matches a field name
    if ( isset( $field_list[ $this->validation ] ) ) {
      return false;
    }
    
    // it's either a regex or invalid field match
    return true;
  }

  /**
   * determines if the field is regex-validated
   * 
   * alias of is_regex_validation()
   * 
   * @return bool
   */
  public function is_regex()
  {
    return $this->is_regex_validation();
  }

  /**
   * sets the property value
   * 
   * @param string $name property name
   * @param mixed $value
   */
  public function __set( $name, $value )
  {
    $this->{$name} = $value;
  }

  /**
   * provides a property value
   * 
   * @param string $name
   * @return mixed
   */
  public function __get( $name )
  {
    return $this->{$name};
  }

}
