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
 * @version    1.7
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

    $this->set_up_error_messages();

    $this->error_style = Participants_Db::plugin_setting( 'field_error_style' );

    // set the default error wrap HTML for the validation error feedback display
    $this->error_html_wrap = is_admin() ?
            Participants_Db::apply_filters( 'admin_validation_errors_template', array('<div class="error below-h2 %s">%s</div>', '<p %2$s >%1$s</p>') ) :
            Participants_Db::apply_filters( 'validation_errors_template', array('<div class="%s">%s</div>', '<p %2$s >%1$s</p>') );
  }

  /**
   * provides an array of validation methods
   * 
   * the "regex/match" method is added as the "other" option
   * 
   * @return  array in the form $key => $title
   */
  public static function validation_methods()
  {
    return Participants_Db::apply_filters( 'validation_methods', array(
                'no' => __( 'Not Required', 'participants-database' ),
                'yes' => __( 'Required', 'participants-database' ),
                'email-regex' => __( 'Email', 'participants-database' ),
                'other' => __( 'regex/match', 'participants-database' ),
                'captcha' => 'CAPTCHA',
            ) );
  }

  /**
   * returns the error messages HTML
   *
   */
  public function get_error_html()
  {

    if ( empty( $this->errors ) )
      return '';

    $this->build_validation_errors();

    $output = $this->get_error_CSS();

    $messages = '';

    foreach ( $this->errors as $error ) {
      /* @var $error PDb_Validation_Error_Message */

      $messages .= sprintf(
              $this->error_html_wrap[1], 
              $error->error_message(), 
              $error->element_attributes()
      );
    }

    $output .= sprintf( $this->error_html_wrap[0], $this->error_class, $messages );

    return $output;
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
   * @param int    $record_id    the current record ID
   * 
   * @return mixed field value
   */
  protected function _validate_field( $value, $name, $validation = NULL, $form_element = false, $record_id = false )
  {
    $validating_field = new PDb_Validating_Field( $value, $name, $validation, $form_element, false, $record_id );

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
     * @param PDb_Validating_Field $field
     * 
     */
    Participants_Db::do_action( 'before_validate_field', $validating_field );

    /*
     * if there is no validation method defined, exit here
     */
    if ( !$validating_field->is_validated() ) {
      return;
    }

    /*
     * if the validation method is set and the field has not already been
     * validated (error_type == false) we test the submitted field for empty using
     * a defined method that allows 0, but no whitespace characters.
     * 
     * a field that has any validation method and is empty will validate as empty first
     */
    if ( $validating_field->has_not_been_validated() ) {
      // we can validate each form element differently here if needed
      switch ( $validating_field->form_element ) {
        
        case 'file-upload':
        case 'image-upload':

          /*
           * only "required" validation is allowed on this. Restricting file types 
           * is done in the "values" field or in the settings
           */
          $validating_field->validation = 'yes';
          if ( $this->is_empty( $validating_field->value ) ) {
            $validating_field->validation_state_is( 'empty' );
          } else {
            $validating_field->validation_state_is( 'valid' );
          }
          break;

        case 'link':
          
          // a "link" field only needs the first element to be filled in
          if ( $this->is_empty( $validating_field->value[0] ) ) {
            $validating_field->validation_state_is( 'empty' );
          } elseif ( !filter_var( $validating_field->value[0], FILTER_VALIDATE_URL ) ) {
            $validating_field->validation_state_is( 'invalid' );
          } else {
            $validating_field->validation_state_is( 'valid' );
          }
          break;
          
        case 'checkbox':
          
          $values = Participants_Db::$fields[$validating_field->name]->option_values();
          
          $checked_value = current( $values );
          if ( $validating_field->validation === 'yes' && $validating_field->value !== $checked_value ) {
            $validating_field->validation_state_is( 'empty' );
          } elseif ( $validating_field->validation === 'yes' ) {
            $validating_field->validation_state_is( 'valid' );
          }
          break;
          
        case 'password':
          
          // if the password exists and is not being changed, it will come in as the dummy string
          if ( $validating_field->value === PDb_FormElement::dummy ) {
            $validating_field->validation_state_is( 'valid' );
          }
          
          
          if ( $validating_field->has_not_been_validated() ) {
            if ( $this->is_empty( $validating_field->value ) && ! $validating_field->is_regex() ) {
              $validating_field->validation_state_is( 'empty' );
            } elseif ( $validating_field->validation === 'yes' ) {
              $validating_field->validation_state_is( 'valid' );
            }
          }
          
          break;
          
        default:
          /*
           * check all the simple validated fields for empty
           */
          if ( $validating_field->validation === 'yes' ) {
            if ( $this->is_empty( $validating_field->value ) ) {
              $validating_field->validation_state_is( 'empty' );
            } else {
              $validating_field->validation_state_is( 'valid' );
            }
          }
      }
    }

    /*
     * if the field has still not been validated, we process it with the remaining validation methods
     */
    if ( $validating_field->has_not_been_validated() ) {

      $regex = '';
      $test_value = false;

      switch ( true ) {

        case ( $validating_field->is_email() ) :

          $regex = Participants_Db::apply_filters( 'email_regex', '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$#i' ); // version 1.7.1 long tld's allowed
          break;

        case ( 'captcha' == strtolower( $validating_field->validation ) ) :

          $validating_field->value = isset( $validating_field->value[1] ) ? $validating_field->value[1] : '';

          // grab the value and the validation key
          list($info, $v) = (isset( $this->post_array[$validating_field->name][1] ) ? $this->post_array[$validating_field->name] : array($this->post_array[$validating_field->name][0], $validating_field->value));
          $info = json_decode( urldecode( $info ) );

          /**
           * @since 1.6.3
           * @filter pdb-captcha_validation
           */
          $regex = Participants_Db::apply_filters( 'captcha_validation', $this->xcrypt( $info->nonce, PDb_CAPTCHA::get_key() ), $this->post_array );
          
          if ( !self::is_regex( $regex ) ) {
            $validating_field->validation_state_is( 'invalid' );
          }

          //error_log(__METHOD__.' validate CAPTCHA $info:'.print_r($info,1).' $field->value:'.$field->value.' regex:'.$regex);

          break;

        case ( self::is_regex( $validating_field->validation ) ) :

          $regex = $validating_field->validation;
          break;

        /*
         * if it's not a regex, test to see if it's a valid field name for a match test
         */
        case ( isset( $this->post_array[strtolower( $validating_field->validation )] ) ) :

          $test_value = $this->post_array[strtolower( $validating_field->validation )];
          break;

        default:
      }

      if ( $test_value !== false ) {
        if ( ! $this->verify_field_is_valid( $test_value, $validating_field ) ) {
          $validating_field->validation_state_is( 'nonmatching' );
        } else {
          // set the state to valid because it matches
          $validating_field->validation_state_is( 'valid' );
        }
      } elseif ( $regex !== false && self::is_regex( $regex ) ) {

        $test_result = preg_match( $regex, $validating_field->value );

        if ( $test_result === 0 ) {
          // failed regex
          if ( $this->is_empty( $validating_field->value ) ) {
            $validating_field->error_type = 'empty';
          } elseif( $validating_field->validation === 'captcha' ) {
            $validating_field->error_type = 'captcha';
          } else {
            $validating_field->error_type = 'invalid';
          }
          
        } elseif ( $test_result === false ) {
          error_log( __METHOD__ . ' captcha or regex error with regex: "' . $regex . '"' );
          
        } elseif ( $test_result === 1 ) {
          $validating_field->validation_state_is( 'valid' );
          
        }
      }
    }

    if ( $validating_field->is_not_valid() ) {
      $this->_add_error( $validating_field->name, $validating_field->error_type, false );
    }
    /*
     * the result of a captcha validation are stored in a session variable
     */
    if ( $validating_field->is_captcha() || $validating_field->form_element === 'captcha' ) {
      Participants_Db::$session->set( 'captcha_result', $validating_field->error_type );
    }

    if ( false ) {
      error_log( __METHOD__ . '
  field: ' . $name . '
  element: ' . $validating_field->form_element . '
  value: ' . (is_array( $validating_field->value ) ? print_r( $validating_field->value, 1 ) : $validating_field->value) . '
  validation: ' . (is_bool( $validating_field->validation ) ? ($validating_field->validation ? 'true' : 'false') : $validating_field->validation) . '
  submitted? ' . ($this->not_submitted( $name ) ? 'no' : 'yes') . '
  empty? ' . ($this->is_empty( $validating_field->value ) ? 'yes' : 'no') . '
  error type: ' . $validating_field->error_type );
    }

    return $validating_field->value;
  }
  
  /**
   * validates a verify field
   * 
   * a verify field is a field that is supposed to match the value of another field. 
   * It should only be checked if the other field is getting changed. If the other 
   * field is not getting changed, this field should validate as true
   * 
   * @param mixed $test_value
   * @param PDb_Validating_Field $field
   * @return bool true if the field is valid
   */
  protected function verify_field_is_valid( $test_value, $field )
  {
    if ( $test_value != $this->saved_test_value( $field ) ) {
      return $test_value == $field->value;
    }
    
    return true;
  }
  
  /**
   * provides the field's value from the db
   * 
   * this can be used to tell if the field's value is getting changed
   * 
   * @param PDb_Validating_Field $field
   * @return mixed the saved value
   */
  protected function saved_test_value( $field )
  {
    $record = Participants_Db::get_participant($field->record_id());
    
    return isset( $record[$field->validation] ) ? $record[$field->validation] : '';
  }

  /**
   * provides the validation errors array
   * 
   * @return  array of PDb_Validation_Error_Message instances
   */
  public function get_validation_errors()
  {
    $this->build_validation_errors();
    return $this->errors;
  }

  /**
   * prepares the error messages and CSS for a main database submission
   *
   * @return array indexed array of error messages
   */
  public function build_validation_errors()
  {

    // check for errors
    if ( !$this->errors_exist() )
      return array();

    foreach ( $this->errors as $fieldname => $error ) {
      /* @var $error PDb_Validation_Error_Message */

      if ( $fieldname !== '' ) {

        $field = Participants_Db::$fields[$fieldname];
        /* @var $field PDb_Form_Field_Def */

        switch ( $field->form_element() ) {
          case 'captcha':
          case 'link':
            $field_selector = '[name="' . $field->name() . '[]"]';
            break;
          case 'multi-checkbox':
          case 'radio':
          case 'checkbox':
          case 'multi-select-other':
            $field_selector = '[for="pdb-' . $field->name() . '"]';
            break;
          default:
            $field_selector = '[name="' . $field->name() . '"]';
        }

        //$this->error_CSS[] = '[class*="' . Participants_Db::$prefix . '"] ' . $field_selector;
        $error->set_css_selector( '[class*="' . Participants_Db::$prefix . '"] ' . $field_selector );
        
        $error_message = $error->slug; // fallback message
        
        switch ( $error->slug ) {
          
          case 'invalid':
            if ( $field->has_validation_message() && $field->validation() !== 'yes' ) {
              $error_message = sprintf( str_replace( '%s', '%1$s', $field->validation_message() ), $field->title() );
            } elseif ( isset( $this->error_messages[$error->slug] ) ) {
              $error_message = sprintf( str_replace( '%s', '%1$s', $this->error_messages[$error->slug] ), $field->title() );
            }
            break;
            
          case 'nonmatching':
            if ( $field->has_validation_message() && $field->validation() === 'other' ) {
              $error_message = sprintf( str_replace( '%s', '%1$s', $field->validation_message() ), $field->title() );
            } elseif ( isset( $this->error_messages[$error->slug] ) ) {
              $error_message = sprintf( $this->error_messages[$error->slug], $field->title(), Participants_Db::column_title( $field->validation ) );
            }
            break;
            
          case 'empty':
            if ( $field->has_validation_message() && $field->validation() === 'yes' ) {
              $error_message = sprintf( str_replace( '%s', '%1$s', $field->validation_message() ), $field->title() );
            } elseif ( isset( $this->error_messages[$error->slug] ) ) {
              $error_message = sprintf( str_replace( '%s', '%1$s', $this->error_messages[$error->slug] ), $field->title() );
            }
        
            break;
            
          case 'duplicate':
          case 'identifier':
          default:
            if ( isset( $this->error_messages[$error->slug] ) ) {
              $error_message = sprintf( str_replace( '%s', '%1$s', $this->error_messages[$error->slug] ), $field->title() );
            }
            break;
            
        }
        $this->error_class = Participants_Db::$prefix . 'error';
        
      } else {
        $error_message = $error->slug;
        $this->error_class = Participants_Db::$prefix . 'message';
      }
      $error->set_error_message( $error_message );
      $error->add_message_class( $this->error_class . '-' . $fieldname );
    }
  }

  /**
   * sets the error status for a field
   *
   * @param string $field the name of the field
   * @param string $error the error status of the field
   * @param bool $overwrite if true, overwrites an existing error on the same field
   */
  protected function _add_error( $field, $error, $overwrite = false )
  {
    if ( $overwrite === true || !isset( $this->errors[$field] ) || empty( $this->errors[$field] ) ) {
      $this->errors[$field] = new PDb_Validation_Error_Message( $field, array('slug' => $error) );
    }
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
    $error_css = Participants_Db::apply_filters( 'error_css', implode( ",\r", $this->error_selectors() ) . '{ ' . $this->error_style . ' }', $this->errors );
    if ( !empty( $error_css ) ) {
      return sprintf( '<style type="text/css">%s</style>', $error_css );
    } else {
      return '';
    }
  }

  /**
   * supplies an array of error css selectors
   * 
   * @return array
   */
  private function error_selectors()
  {
    $selectors = array();
    foreach ( $this->errors as $error ) {
      $selectors[] = $error->css_selector;
    }
    return $selectors;
  }

  /**
   * sets up the error messages
   * 
   */
  private function set_up_error_messages()
  {
    /*
     * get our error messages from the plugin options
     * 
     */
    foreach ( array('invalid', 'empty', 'nonmatching', 'duplicate', 'captcha', 'identifier') as $error_type ) {
      $this->error_messages[$error_type] = Participants_Db::plugin_setting( $error_type . '_field_message' );
    }
    /*
     * this filter provides an opportunity to add or modify validation error messages
     * 
     * for example, if there is a custom validation that generates an error type of 
     * "custom" an error message with a key of "custom" will be shown if it fails.
     */
    $this->error_messages = Participants_Db::apply_filters( 'validation_error_messages', $this->error_messages );
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

    for ( $i = 0; $i < strlen( $string ); $i++ ) {
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
    return !( empty( $this->validation ) || $this->validation === NULL || $this->validation === 'no' || $this->validation === FALSE );
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

class PDb_Validation_Error_Message {

  /**
   * @var string field name
   */
  private $fieldname;

  /**
   * @var string the error message slug
   */
  private $slug;

  /**
   * @var string the error message
   */
  private $error_message = '';

  /**
   * @var string the field CSS selector
   */
  private $css_selector;

  /**
   * @var object the field definition
   */
  private $field_def;

  /**
   * @var string the message class
   */
  private $class = '';

  /**
   * 
   * @param string $fieldname
   * @param array $config
   */
  public function __construct( $fieldname, $config )
  {
    $this->fieldname = $fieldname;
    $this->setup_config( $config );
    $this->setup_field_def();
  }

  /**
   * supplies the error message content
   * 
   * @return string
   */
  public function error_message()
  {
    return $this->error_message;
  }

  /**
   * supplies the error message HMTL data
   * 
   * @return string HTML element attribute string
   */
  public function element_attributes()
  {
    return sprintf( ' data-field-group="%s" data-field-name="%s" class="%s" ', ( $this->field_def ? $this->field_def->group : '' ), $this->fieldname, $this->class );
  }

  /**
   * sets up the field definition
   */
  private function setup_field_def()
  {
    if ( !empty( $this->fieldname ) ) {
      $this->field_def = Participants_Db::$fields[$this->fieldname];
    }
  }

  /**
   * supplies object values
   * 
   * @param string  $name of the parameter to get
   * @return mixed
   */
  public function __get( $name )
  {
    switch ( $name ) {
      default:
        if ( isset( $this->{$name} ) ) {
          return $this->{$name};
        }
        if ( isset( $this->field_def->{$name} ) ) {
          return $this->field_def->{$name};
        }
    }
    return '';
  }

  /**
   * sets the CSS selector property
   * 
   * @param string $selector the field selector
   */
  public function set_css_selector( $selector )
  {
    $this->css_selector = $selector;
  }

  /**
   * sets the error message css class
   * 
   * @param string $class
   */
  public function set_message_class( $class )
  {
    $this->class = $class;
  }

  /**
   * adds a class to the error message css class
   * 
   * @param string $class
   */
  public function add_message_class( $class )
  {
    $this->class .= ' ' . $class;
  }

  /**
   * sets the error message content
   * 
   * @param string $message
   */
  public function set_error_message( $message )
  {
    $this->error_message = $message;
  }
  
  /**
   * provides the title of the field
   * 
   * @return string
   */
  public function field_title()
  {
    return $this->field_def->title();
  }

  /**
   * sets up the config values
   * 
   * @param array $config
   */
  public function setup_config( $config )
  {
    foreach ( get_object_vars( $this ) as $name => $value ) {

      switch ( $name ) {
        case 'slug':
          if ( isset( $config['slug'] ) ) {
            $this->slug = $config['slug'];
          } else {
            $this->slug = $this->fieldname;
          }
          break;
        default:
          if ( isset( $config[$name] ) ) {
            $this->{$name} = $config[$name];
          }
      }
    }
  }

}
