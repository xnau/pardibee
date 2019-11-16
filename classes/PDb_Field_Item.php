<?php

/*
 * class for handling the display of fields in a template
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2018 xnau webdesign
 * @license    GPL2
 * @version    2.2
 * @link       http://xnau.com/wordpress-plugins/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Field_Item extends PDb_Form_Field_Def {

  /**
   * @var string the field's value
   */
  private $value;

  /**
   *
   * @var int the id of the current record
   */
  private $record_id = 0;

  /**
   *
   * @var string the instantiating module
   */
  private $module = 'none';

  /**
   *
   * @var string the element class name
   */
  private $field_class;

  /**
   *
   * @var string the href value
   */
  public $link = '';

  /**
   * @var bool determines if the field value is output as HTML or a formatted value 
   */
  public $html_output = true;

  /**
   * 
   * @param array|object|string $config the field attributes or field name
   * @param int|string $id the id of the source record if available
   */
  public function __construct( $config, $id = false )
  {
    if ( is_string( $config ) ) {
      $config = array('name' => $config);
    }

    if ( is_array( $config ) ) {
      $config = (object) $config;
    }

    parent::__construct( $config->name );

    if ( $id ) {
      $this->set_record_id( $id );
    }

    // load the object properties
    $this->assign_props( $config );
  }

  /**
   * provides direct access to property values
   * 
   * this is provided for compatibility, eventually, we will only allow certain 
   * properties to be gotten this way
   * 
   * @param string $name of the property to get
   * @return string|int|array
   */
  public function __get( $name )
  {
    if ( property_exists( $this, $name ) ) {
      return $this->{$name};
    }
    return parent::__get( $name );
  }

  /**
   * allows direct setting of the value property
   * 
   * this is for backward compatibility
   * 
   * @param string $prop name of the property
   * @oaram mixed $value to set the property to
   */
  public function __set( $prop, $value )
  {
    switch ( $prop ) {
      case 'value':
        $this->_set_value( $value );
        break;
    }
  }

  /**
   * handles isset call on class properties
   * 
   * @param string $name of the property
   */
  public function __isset( $name )
  {
    switch ( $name ) {
      case 'record_id':
        return $this->record_id > 0;
      default:
        return $this->{$name} !== '';
    }
  }

  // template methods

  /**
   * prints a field label
   */
  public function print_label()
  {
    echo $this->_label();
  }

  /**
   * prints a field value, wrapping with a link as needed
   * 
   */
  public function print_value( $print = true )
  {
    if ( $print ) {
      echo $this->get_value_display();
    } else {
      return $this->get_value_display();
    }
  }

  /**
   * returns a field value, wrapping with a link as needed
   * 
   * @return string the field's value, prepped for display
   * 
   */
  public function get_value_display()
  {
    return PDb_FormElement::get_field_value_display( $this, $this->html_output );
  }

  /**
   * supplies the raw value of the field
   * 
   * @return string|int value
   */
  public function value()
  {
    return $this->value;
  }

  /**
   * provides the current value of the field as an associative array or string
   * 
   * alias for the value method
   * 
   * @return array|string the current value of the field
   */
  public function get_value()
  {
    return $this->value();
  }

  /**
   * provides the dynamic value for a dynamic hidden field
   *
   * @return string
   */
  public function dynamic_value()
  {
    $value = '';
    if ( $this->is_dynamic_hidden_field() ) {
      $value = Participants_Db::get_dynamic_value( $this->default );
    }
    return $value;
  }

  /**
   * supplies the value in a displayable format
   * 
   * @return string
   */
  public function display_array_value()
  {
    if ( $this->is_value_set() ) {
      $titles = array();
      foreach ( self::field_value_array( $this->value ) as $value ) {
        $titles[] = $this->sanitize_option_title( $this->value_title( $value ) );
      }
      return implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $titles );
    }
    return $this->value();
  }
  
  /**
   * sanitizes an option title
   * 
   * option titles are allowed a limited set of HTML tags
   * 
   * @param string $title
   * @return string sanitized
   */
  private function sanitize_option_title( $title )
  {
    return PDb_Manage_Fields_Updates::sanitize_text($title);
  }

  /**
   * provides the field value as an exportable string
   * 
   * @return string
   */
  public function export_value()
  {
    /**
     * filters the raw value of the field
     * 
     * subsequent processing can be skipped by setting the $column->form_element property to 'skip'
     * 
     * @version 1.7.1
     * @filter pdb-csv_export_value_raw
     * @param mixed the raw value
     * @param object the field object
     * @return mixed
     */
    $value = Participants_Db::apply_filters( 'csv_export_value_raw', $this->value, $this );

    switch ( $this->form_element ) {

      case 'date':
        $value = PDb_Date_Display::get_date( $this->value, 'export value' );
        break;

      case 'link':

        $link = maybe_unserialize( $this->value );
        if ( is_array( $link ) ) {

          if ( empty( $link[0] ) )
            $value = isset( $link[1] ) ? $link[1] : '';
          else {
            $pattern = empty( $link[1] ) ? '<%1$s>' : '[%2$s](%1$s)';
            $value = vsprintf( $pattern, $link );
          }
        }
        break;

      case 'rich-text':

        /*
         * what we need to do here is add the missing markup (wpautop does 
         * this) and then remove all line breaks and such so the whole thing 
         * looks like one field
         */
        $value = preg_replace( '/^\s+|\n|\r|\s+$/m', '', wpautop( $this->value, true ) );
        break;

      case 'skip':
        // do nothing; we can use this to skip the normal value in the filter above
        break;

      default:

        $value = maybe_unserialize( $this->value );

        /*
         * as of version 1.7.9 multi-type fields export their values as a 
         * comma-separated list of values; values that contain a comma will use 
         * the &#44; entity to represent them
         */
        if ( $this->is_multi() ) {
          $value = implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), (array) $value );
        } elseif ( is_array( $value ) ) {
          // if it is an array, serialize it
          $value = html_entity_decode( serialize( $value ), ENT_QUOTES, "UTF-8" );
        } else {
          $value = html_entity_decode( $value, ENT_QUOTES, "UTF-8" );
        }
    }

    return $value;
  }

  /**
   * supplies the name of the current module
   * 
   * @return string value
   */
  public function module()
  {
    return $this->module;
  }

  /**
   * supplies the href value
   * 
   * @return string value
   */
  public function link()
  {
    return $this->link;
  }

  /**
   * sets the field's value
   * 
   * @param string|int|array $value
   */
  public function set_value( $value )
  {
    $this->_set_value( $value );
  }
  
  /**
   * sets the record id prop and value if available
   * 
   * @param int $record_id
   */
  public function set_record_id( $record_id )
  {
    if ( $id = intval( $record_id ) ) {
      $this->record_id = $id;
      $this->set_value_from_db();
    }
  }
  
  /**
   * sets the value from the db if it has not been set
   */
  private function set_value_from_db()
  {
    if ( is_null( $this->value ) && $this->record_id > 0 ) {
      $data = Participants_Db::get_participant($this->record_id);
      if ( $data && isset( $data[$this->name] ) ) {
        $this->_set_value($data[$this->name]);
      }
    }
  }

  /**
   * sets the field's module
   * 
   * @param string $module
   */
  public function set_module( $module )
  {
    $this->module = $module;
  }

  /**
   * sets the link value of the object
   * 
   * @param string $url the link url
   */
  public function set_link( $url )
  {
    $this->link = $url;
  }

  /**
   * tells if the title (field label) is empty
   * 
   * @return bool true if there is a title string defined
   */
  public function has_title()
  {
    return strlen( $this->title ) > 0;
  }

  /**
   * tells if the field has a non-empty value
   * 
   * alias of has_content()
   * 
   * @return bool
   */
  public function has_value()
  {
    return $this->has_content();
  }

  /**
   * tells if the link property is set
   * 
   * @return bool
   */
  public function has_link()
  {
    return $this->link !== '';
  }
  
  /**
   * supplies the "raw" field value
   * 
   * this is simply the field's value without any html that might normally be 
   * included with a display value
   * 
   * @return string
   */
  public function raw_value()
  {
    $temp = $this->html_output;
    $this->html_mode(false);
    $value = $this->get_value_display();
    $this->html_mode($temp);
    return $value;
  }

  /**
   * tests a value for emptiness, includinf arrays with empty elements
   * 
   * @param mixed $value the value to test
   * @return bool
   */
  public function is_empty( $value = false )
  {
    if ( $value === false ) {
      $value = $this->value;
    }

    if ( is_object( $value ) ) {
      // backward compatibility: we used to call this with an object
      $value = $value->value;
    }

    if ( is_array( $value ) )
      $value = implode( '', $value );

    return strlen( $value ) === 0;
  }

  /**
   * tests a field for printable content
   * 
   * @return bool
   */
  public function has_content()
  {
    switch ( $this->form_element ) {
      case 'placeholder':
        $value = $this->default_value();
        break;
      case 'link':
        $value = $this->value . $this->link;
        break;
      default:
        /**
         * @filter pdb-field_has_content_test_value
         * 
         * @param string the value to test
         * @param PDb_Field_Item the current field
         * @return string the value to test for content
         */
        $value = Participants_Db::apply_filters( 'field_has_content_test_value', $this->value, $this );
    }
    return !$this->is_empty( $value );
  }

  /**
   * tells if the field's value is diferent from the field's default value
   * 
   * @return bool true if the value is different from the default
   */
  public function is_not_default()
  {
    return $this->value !== $this->default_value();
  }

  /**
   * sets the html mode
   * 
   * @param bool $mode true to use html, false to use only formatted values
   */
  public function html_mode( $mode )
  {
    $this->html_output = (bool) $mode;
  }

  /**
   * processes a field value into an array 
   * 
   * this is used for values as they come in from a database or imported from CSV
   * 
   * @param string|array $value
   * @return array
   */
  public static function field_value_array( $value )
  {
    $multivalues = maybe_unserialize( $value );

    if ( !is_array( $multivalues ) ) {
      // make it into an array
      $multivalues = explode( ',', $value );
    }

    // remove empty elements
    return array_filter( $multivalues, function ($v) {
      return $v !== '';
    } );
  }

  /**
   * adds a string to the element class
   * 
   * @param string $classname
   */
  public function add_class( $classname )
  {
    $classes = explode( ' ', $this->get_attribute( 'class' ) );
    $classes[] = esc_attr( $classname );
    $this->attributes['class'] = implode( ' ', $classes );
  }
  
  /**
   * provides the file URI for an upload type field
   * 
   * @return string empty if not an upload field or no file has been uploaded
   */
  public function file_uri()
  {
    $uri = '';
    if ( $this->is_upload_field() ) {
      if ( function_exists( 'get_attachment_url_by_slug') ) {
        $uri = get_attachment_url_by_slug( $this->value );
      }
      if ( $uri === '' ) {
        $uri = trailingslashit( Participants_Db::files_uri() ) . $this->value;
      }
    }
    return $uri;
  }
  
  /**
   * tells if the field has an uploaded file
   * 
   * this only applies to files uploaded via Participants Database, it does not 
   * count files uploaded to the Media Library using the Image Expansion Kit add-on
   * 
   * @return bool
   */
  public function has_uploaded_file()
  {
    if ( ! $this->is_upload_field() ) {
      return false;
    }
    if ( $this->has_content() ) {
      $filepath = trailingslashit( Participants_Db::files_path() ) . $this->value;
      return is_file( $filepath );
    }
    return false;
  }

  /**
   * assigns the object properties from matching properties in the supplied object
   * 
   * @param object $config the field data object
   */
  protected function assign_props( $config )
  {
    foreach ( $config as $prop => $value ) {

      switch ( true ) {
        case ( $prop === 'name' ):
        case ( $prop === 'record_id' && !empty( $this->record_id ) ):
          break;
        case ( method_exists( $this, 'set_' . $prop ) ):
          $this->{'set_' . $prop}( $value );
          break;
        case ($prop === 'attributes'):
        case ($prop === 'options'):
          break;
        case ($prop === 'value'):
          $this->_set_value( $value );
          break;
        case ( property_exists( $this, $prop ) ):
          $this->{$prop} = $value;
          break;
      }
    }

    if ( $this->is_valid_single_record_link_field() ) {
      $this->set_link( Participants_Db::single_record_url( $this->record_id ) );
    }
  }

  /**
   * tells if the field is valid to get the single record link
   * 
   * @return bool
   */
  private function is_valid_single_record_link_field()
  {
    return (
            !in_array( $this->module, array('single', 'signup') ) &&
            $this->is_single_record_link_field() &&
            $this->record_id
            );
  }

  /**
   * outputs a single record link
   *
   * @param string $template an optional template for showing the link
   *
   * @return string the HTML for the single record link
   *
   */
  public function output_single_record_link( $template = false )
  {
    $pattern = $template ? $template : '<a class="single-record-link" href="%1$s" title="%2$s" >%2$s</a>';
    $url = Participants_Db::single_record_url( $this->record_id );

    return sprintf( $pattern, $url, (empty( $this->value ) ? $this->default : $this->value ) );
  }

  /**
   * tells if the field should have the required field marker added to the label
   * 
   * 
   * @return bool true if the marker should be added
   */
  public function place_required_mark()
  {
    /**
     * @filter pdb-add_required_mark
     * @param bool
     * @param PDb_Field_Item
     * @return bool
     */
    return Participants_Db::apply_filters( 'add_required_mark', Participants_Db::plugin_setting_is_true('mark_required_fields') && $this->validation != 'no' && !in_array( $this->module, array('list', 'search', 'total', 'single') ), $this );
  }

  /**
   * prints a CSS class name based on the form_element
   */
  public function print_element_class()
  {
    // for compatibility we are not prefixing the form element class name
    echo PDb_Template_Item::prep_css_class_string( $this->form_element );

    if ( $this->is_readonly() )
      echo ' readonly-element';
  }

  /**
   * prints a CSS classname based on the field name
   */
  public function print_element_id()
  {
    echo PDb_Template_Item::prep_css_class_string( Participants_Db::$prefix . $this->name );
  }

  /**
   * prints the field element with an id
   * 
   */
  public function print_element_with_id()
  {
    $this->attributes['id'] = PDb_Template_Item::prep_css_class_string( Participants_Db::$prefix . $this->name );
    $this->print_element();
  }

  /**
   * prints the form element HTML
   */
  public function print_element()
  {
    echo $this->get_element();
  }

  /**
   * gets the field form element HTML
   *
   * @return string
   */
  public function get_element()
  {
    $this->field_class = ( $this->validation != 'no' ? "required-field" : '' ) . ( in_array( $this->form_element(), array('text-line', 'date', 'timestamp') ) ? ' regular-text' : '' );

    /**
     * @filter pdb-before_display_form_input
     * 
     * allows a callback to alter the field object before displaying a form input element
     * 
     * @param PDb_Form_Element this instance
     */
    Participants_Db::do_action( 'before_display_form_input', $this );

    if ( $this->is_readonly() && !in_array( $this->form_element(), array('captcha') ) ) {

      if ( !in_array( $this->form_element(), array('rich-text') ) ) {

        $this->attributes['readonly'] = 'readonly';
        return $this->_get_element();
      } else {
        return '<span class="pdb-readonly ' . $this->field_class . '" >' . $this->get_value_display() . '</span>';
      }
    } else {

      return $this->_get_element();
    }
  }

  /**
   * prints the element
   */
  public function _print()
  {
    PDb_FormElement::print_element( array(
        'type' => $this->form_element(),
        'value' => $this->value(),
        'name' => $this->name(),
        'options' => $this->options(),
        'class' => $this->field_class,
        'attributes' => $this->attributes(),
        'module' => $this->module(),
        'link' => $this->link(),
            )
    );
  }

  /**
   * supplies the form element HTML
   * 
   * @return string
   */
  private function _get_element()
  {
    return PDb_FormElement::get_element( array(
                'type' => $this->form_element(),
                'value' => $this->value(),
                'name' => $this->name(),
                'options' => $this->options(),
                'class' => $this->field_class,
                'attributes' => $this->attributes(),
                'module' => $this->module(),
                'link' => $this->link(),
                    )
    );
  }

  /**
   * tells if the help_text is defined
   * 
   * @return bool
   */
  public function has_help_text()
  {
    return !empty( $this->help_text );
  }

  /**
   * prints the field's help text
   */
  public function print_help_text()
  {
    echo $this->prepare_display_value( PDb_Template_Item::html_allowed( $this->help_text ) );
  }

  /**
   * returns a field's error status
   *
   * @return mixed bool false if no error, string error type if validation error is set
   *
   */
  public function has_error()
  {
    $error_array = array('no error');

    if ( is_object( Participants_Db::$validation_errors ) )
      $error_array = Participants_Db::$validation_errors->get_error_fields();

    if ( $error_array and isset( $error_array[$this->name] ) )
      return $error_array[$this->name];
    else
      return false;
  }

  /**
   * is this the single record link?
   * 
   * @return bool
   */
  public function is_single_record_link()
  {
    return (
            Participants_Db::is_single_record_link( $this->name ) &&
            !in_array( $this->form_element, array('rich-text', 'link') ) &&
            $this->record_id
            );
  }

  /**
   * adds the required marker to a field label as needed
   *
   * @return string
   */
  private function _label()
  {

    $label = $this->prepare_display_value( PDb_Template_Item::html_allowed( $this->title ) );

    if ( $this->place_required_mark() ) {

      $label = sprintf( Participants_Db::plugin_setting_value('required_field_marker'), $label );
    }

    return $label;
  }

  /**
   * prepare a field for display
   *
   * makes the value available to translations
   * 
   * @param string $string the value to be prepared
   */
  private function prepare_display_value( $string )
  {
    return Participants_Db::apply_filters( 'translate_string', $string );
  }

  /**
   * provides an associative array of values
   * 
   * this will add an "other" value if no matching field option is found
   * 
   * @param array $value_list the selected values from the record
   * @return array as $title => $value
   */
  private function make_assoc_value_array( $value_list )
  {
    $title_array = array();
    $other_list = array();

    foreach ( $value_list as $value ) {
      if ( $this->option_match_found( $value, $this->options ) ) {
        $title_array[$this->value_title( $value )] = $value;
      } else {
        $other_list[] = $value;
      }
    }
    if ( !empty( $other_list ) ) {
      return array_merge( $title_array, array('other' => implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $other_list )) );
    } else {
      return $title_array;
    }
  }

  /**
   * finds a matching value in an array of options
   * 
   * this gives us a chance to prepare the option value before comparing
   * 
   * @param string  $value
   * @param array $option_list the array to find the matching value in
   * @return bool true if a match is found
   */
  private function option_match_found( $value, $option_list )
  {
    foreach ( $option_list as $option ) {
      // both terms are decoded for comparison #2063
      if ( trim( html_entity_decode( $value ) ) === html_entity_decode( $option ) ) {
        return true;
      }
    }
    return false;
  }

  /**
   * sets the value property from an incoming value
   * 
   * this is to clean up an array of values and replace any commas with entities 
   * so that when stored as a comma string, can be recompsed into the same array
   * 
   * @param string|array $raw_value
   */
  private function _set_value( $raw_value )
  {
    switch ( true ) {
      
      case ( $this->form_element === 'link' ):
        
        $this->value = $this->prepare_value( $raw_value );
        $this->set_link_field_value();
        break;
      
      case ( $this->is_multi() ):

        $value_list = array();
        foreach ( self::field_value_array( $raw_value ) as $value ) {
          // this is to allow commas in values when the entity is needed in the field def #2063
          $value_list[] = str_replace( '&#44;', ',', $this->prepare_value( $value ) );
        }

        $this->value = $this->is_value_set() ? $this->make_assoc_value_array( $value_list ) : $value_list;
        break;
        
      default:
        
        $this->value = $this->prepare_value( $raw_value );
    }
  }

  /**
   * prepares a single value for use as the value property
   * 
   * @param string
   * @return string
   */
  private function prepare_value( $value )
  {
    return $value; // filter_var( $value, FILTER_SANITIZE_STRING );
  }

  /**
   * assigns the values for the special case of the "link" field 
   */
  private function set_link_field_value()
  {
    $parts = maybe_unserialize( $this->value );
    
    if ( is_array( $parts ) ) {

      if ( isset( $parts[0] ) && filter_var( $parts[0], FILTER_VALIDATE_URL ) && !$this->has_link() ) {
        $this->link = $parts[0];
      }

      // remove the URL component from the value
      $this->value = isset( $parts[1] ) ? $parts[1] : $this->default;
      
    } elseif ( filter_var( $this->value, FILTER_VALIDATE_URL ) ) {
      $this->link = $this->value;
    }
    
  }
  
    }
