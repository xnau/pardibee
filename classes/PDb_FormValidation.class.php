<?php

/**
 * Form Validation Class
 *
 * tracks form submission validation and provides user feedback
 * 
 * Requires PHP Version 5.2 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    1.6
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_FormValidation extends xnau_FormValidation {
  /*
   * instantiates the form validation object
   * this is meant to be instantiated once per form submission
   *
   */

  public function __construct()
  {

    parent::__construct();
    /*
     * get our error messages from the plugin options
     * 
     */
    foreach (array( 'invalid', 'empty', 'nonmatching', 'duplicate', 'captcha', 'identifier' ) as $error_type) {
      $this->error_messages[$error_type] = Participants_Db::plugin_setting( $error_type . '_field_message' );
    }
    /*
     * this filter provides an opportunity to add or modify validation error messages
     * 
     * for example, if there is a custom validation that generates an error type of 
     * "custom" an error message with a key of "custom" will be shown if it fails.
     */
    $this->error_messages = Participants_Db::apply_filters( 'validation_error_messages', $this->error_messages );

    $this->error_style = Participants_Db::plugin_setting( 'field_error_style' );
  }

  /**
   * validates a single field submitted to the main database
   *
   * receives a validation pair and processes it, adding any error to the
   * validation status array
   *
   * @param string $value        the submitted value of the field
   * @param string $name         the name of the field
   * @param string $validation   validation method to use: can be NULL (or absent),
   *                             'no', 'yes', 'email-regex', 'other' (for regex or match
   *                             another field value)
   * @param string $form_element the form element type of the field
   * @return mixed field value
   */
  protected function _validate_field( $value, $name, $validation = NULL, $form_element = false )
  {
    //$field = (object) compact( 'value', 'name', 'validation', 'form_element', 'error_type' );
    $field = new PDb_Validating_Field( $value, $name, $validation, $form_element );

    /**
     * this filter sends the $field object through a filter to allow a custom 
     * validation to be inserted
     * 
     * if a custom validation is implemented, the $field->error_type must be set 
     * to a validation method key string. If the key string is a defined validation 
     * method, that method will be applied. If $field->validation is set to false 
     * by the filter callback, no further processing will be applied.
     * 
     * @action pdb-before_validate_field
     * @param PDb_Validating_field object $field
     * 
     */
    Participants_Db::do_action( 'before_validate_field', $field );

    /*
     * if there is no validation method defined, exit here
     */
    if ( ! $field->is_validated() ) {
      return;
    }


    /*
     * if the validation method is set and the field has not already been
     * validated (error_type == false) we test the submitted field for empty using
     * a defined method that allows 0, but no whitespace characters.
     * 
     * a field that has any validation method and is empty will validate as empty first
     */
    if ( $field->has_not_been_validated() ) {
      // we can validate each form element differently here if needed
      switch ( $field->form_element ) {
        case 'file-upload':
        case 'image-upload':

          /*
           * only "required" validation is allowed on this. Restricting file types 
           * is done in the "values" field or in the settings
           */
          $field->validation = 'yes';
          if ( $this->is_empty( $field->value ) ) {
            $field->validation_state_is( 'empty' );
          } else {
            $field->validation_state_is( 'valid' );
          }
          break;

        case 'link':
          // a "link" field only needs the first element to be filled in
          if ( $this->is_empty( $field->value[0] ) ) {
            $field->validation_state_is( 'empty' );
          } elseif ( !filter_var( $field->value[0], FILTER_VALIDATE_URL ) ) {
            $field->validation_state_is( 'invalid' );
          } else {
            $field->validation_state_is( 'valid' );
          }
          break;
        default:
          /*
           * check all the validated fields for empty first
           */
          if ( $this->is_empty( $field->value ) ) {
            $field->validation_state_is( 'empty' );
          } elseif ( $field->validation === 'yes' ) {
            $field->validation_state_is( 'valid' );
          }
      }
    }

    /*
     * if the field has still not been validated, we process it with the remaining validation methods
     */
    if ( $field->has_not_been_validated() ) {

      $regex = '';
      $test_value = false;

      switch ( true ) {

        case ( $field->is_email() ) :

          $regex = Participants_Db::apply_filters( 'email_regex', '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i' );
          break;

        case ( 'captcha' == strtolower( $field->validation ) ) :

          $field->value = isset( $field->value[1] ) ? $field->value[1] : '';

          // grab the value and the validation key
          list($info, $v) = (isset( $this->post_array[$field->name][1] ) ? $this->post_array[$field->name] : array( $this->post_array[$field->name][0], $field->value ));
          $info = json_decode( urldecode( $info ) );

          /**
           * @since 1.6.3
           * @filter pdb-captcha_validation
           */
          $regex = Participants_Db::apply_filters( 'captcha_validation', $this->xcrypt( $info->nonce, PDb_CAPTCHA::get_key() ), $this->post_array );

          if ( !self::is_regex( $regex ) ) {
            $field->validation_state_is( 'invalid' );
          }

          //error_log(__METHOD__.' validate CAPTCHA $info:'.print_r($info,1).' $field->value:'.$field->value.' regex:'.$regex);

          break;

        case ( self::is_regex( $field->validation ) ) :

          $regex = $field->validation;
          break;

        /*
         * if it's not a regex, test to see if it's a valid field name for a match test
         */
        case ( isset( $this->post_array[strtolower( $field->validation )] ) ) :

          $test_value = $this->post_array[strtolower( $field->validation )];
          break;

        default:
      }

      if ( $test_value !== false ) {
        if ( $field->value !== $test_value ) {
          $field->validation_state_is( 'nonmatching' );
        } else {
          // set the state to valid because it matches
          $field->validation_state_is( 'valid' );
        }
      } elseif ( $regex !== false && self::is_regex( $regex ) ) {

        $test_result = preg_match( $regex, $field->value );

        if ( $test_result === 0 ) {
          $field->error_type = $field->validation == 'captcha' ? 'captcha' : 'invalid';
        } elseif ( $test_result === false ) {
          error_log( __METHOD__ . ' captcha regex error with regex: "' . $regex . '"' );
        } elseif ( $test_result === 1 ) {
          $field->validation_state_is( 'valid' );
        }
      }
    }

    if ( $field->is_not_valid() ) {
      $this->_add_error( $name, $field->error_type, false );
    }
    /*
     * the result of a captcha validation are stored in a session variable
     */
    if ( $field->is_captcha() || $field->form_element === 'captcha' ) {
      Participants_Db::$session->set( 'captcha_result', $field->error_type );
    }

    if ( false ) {
      error_log( __METHOD__ . '
  field: ' . $name . '
  element: ' . $field->form_element . '
  value: ' . (is_array( $field->value ) ? print_r( $field->value, 1 ) : $field->value) . '
  validation: ' . (is_bool( $field->validation ) ? ($field->validation ? 'true' : 'false') : $field->validation) . '
  submitted? ' . ($this->not_submitted( $name ) ? 'no' : 'yes') . '
  empty? ' . ($this->is_empty( $field->value ) ? 'yes' : 'no') . '
  error type: ' . $field->error_type );
    }

    return $field->value;
  }

  /**
   * prepares the error messages and CSS for a main database submission
   *
   * @return array indexed array of error messages
   */
  public function get_validation_errors()
  {

    // check for errors
    if ( !$this->errors_exist() )
      return array();

    $output = '';
    $error_messages = array();
    $this->error_CSS = array();
    
    foreach ($this->errors as $field => $error) {

      if ( $field !== '' ) {

        $field_atts = clone Participants_Db::$fields[$field];

        switch ( $field_atts->form_element ) {
          case 'captcha':
          case 'link':
            $field_selector = '[name="' . $field_atts->name . '[]"]';
            break;
          case 'multi-checkbox':
          case 'radio':
          case 'checkbox':
          case 'multi-select-other':
            $field_selector = '[for="pdb-' . $field_atts->name . '"]';
            break;
          default:
            $field_selector = '[name="' . $field_atts->name . '"]';
        }

        $this->error_CSS[] = '[class*="' . Participants_Db::$prefix . '"] ' . $field_selector;

        if ( isset( $this->error_messages[$error] ) ) {
          $error_messages[] = $error == 'nonmatching' ? sprintf( $this->error_messages[$error], $field_atts->title, Participants_Db::column_title( $field_atts->validation ) ) : sprintf( str_replace( '%s', '%1$s', $this->error_messages[$error] ), $field_atts->title );
          $this->error_class = Participants_Db::$prefix . 'error';
        } else {
          $error_messages[] = $error;
          $this->error_class = empty( $field ) ? Participants_Db::$prefix . 'message' : Participants_Db::$prefix . 'error';
        }
      } else {
        $error_messages[] = $error;
        $this->error_class = Participants_Db::$prefix . 'message';
      }
    } // $this->errors 

    return $error_messages;
  }

  public function get_error_CSS()
  {
    /**
     * @version 1.6.3
     * 
     * @filter 'pdb-error_css'
     * @param string  $CSS    the error CSS
     * @param array   $errors the current errors array
     */
    $error_css = Participants_Db::apply_filters('error_css', empty( $this->error_CSS ) ? '' : implode( ",\r", $this->error_CSS ) . '{ ' . $this->error_style . ' }', $this->errors );
    if ( !empty( $error_css ) ) {
      return sprintf('<style type="text/css">%s</style>', $error_css );
    } else {
      return '';
    }
  }

  /**
   * encodes or decodes a string using a simple XOR algorithm
   * 
   * @param string $string the tring to be encoded/decoded
   * @param string $key the key to use
   * @return string
   */
  public static function xcrypt( $string, $key )
  {

    for ($i = 0; $i < strlen( $string ); $i++) {
      $pos = $i % strlen( $key );
      $replace = ord( $string[$i] ) ^ ord( $key[$pos] );
      $string[$i] = chr( $replace );
    }

    return $string;
  }

}

/**
 * assists in the validation of a single field
 * 
 * $field = (object) compact('value', 'name', 'validation', 'form_element', 'error_type');
 */
class PDb_Validating_Field {

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
   * set it up
   */
  public function __construct( $value, $name, $validation = NULL, $form_element = false, $error_type = false )
  {
    foreach (get_object_vars( $this ) as $prop => $val) {
      $this->{$prop} = $$prop;
    }
  }

  /**
   * determines of the field needs to be validated
   * 
   * @return bool
   */
  public function is_validated()
  {
    return ! ( empty( $this->validation ) || $this->validation === NULL || $this->validation === 'no' || $this->validation === FALSE );
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
   * determines if the field is regex-validated
   * 
   * @return bool
   */
  public function is_regex()
  {
    return PDb_FormValidation::is_regex( $this->validation );
  }

  /**
   * sets the error state
   * 
   * @param string $state the error state string
   */
  public function validation_state_is( $state )
  {
    $this->error_type = $state;
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
