<?php

/**
 * class description
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

class validation_error_message {

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
   * @var \PDb_Form_Field_Def the field definition
   */
  public $field;

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
    return sprintf( ' data-field-group="%s" data-field-name="%s" class="%s" ', ( $this->field ? $this->field->group : '' ), $this->fieldname, $this->class );
  }

  /**
   * sets up the field definition
   */
  private function setup_field_def()
  {
    if ( !empty( $this->fieldname ) ) {
      $this->field = \Participants_Db::$fields[ $this->fieldname ];
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
    if ( isset( $this->{$name} ) ) {
      return $this->{$name};
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
    return $this->field->title();
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
          if ( isset( $config[ 'slug' ] ) ) {
            $this->slug = $config[ 'slug' ];
          } else {
            $this->slug = $this->fieldname;
          }
          break;
        default:
          if ( isset( $config[ $name ] ) ) {
            $this->{$name} = $config[ $name ];
          }
      }
    }
  }

}
