<?php

/*
 * class for modeling a field instance
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2018 xnau webdesign
 * @license    GPL2
 * @version    2.7
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
    return $this->_field_value_display();
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
   * this is specifically for fields that store their value as an array, but works for all fields
   * 
   * @return string
   */
  public function display_array_value()
  {
    if ( $this->is_multi() ) {
      $titles = array();
      foreach ( self::field_value_array( $this->value ) as $value ) {
        $titles[] = $this->sanitize_option_title( $this->value_title($value) );
      }
      return implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $titles );
    }
    return $this->value_title($this->value);
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
    return PDb_Manage_Fields_Updates::sanitize_text( $title );
  }

  /**
   * provides the field value as an exportable string
   * 
   * @return string
   */
  public function export_value()
  {
    /**
     * filters the raw value of the field for export
     * 
     * @version 1.7.1
     * @filter pdb-field_export_value_raw
     * @param mixed the raw value
     * @param PDb_Field_Item the field object
     * @return mixed
     */
    $value = Participants_Db::apply_filters( 'field_export_value_raw', $this->value, $this );

    $export_value = '';

    switch ( $this->form_element ) {

      case 'date':
        
        if ( PDb_Date_Display::is_valid_timestamp( $value ) ) {
          $export_value = PDb_Date_Display::get_date( $value, 'export value' );
        }
        break;

      case 'link':

        $link_pair = maybe_unserialize( $value );
        
        // is $link a linktext/URL array?
        if ( is_array( $link_pair ) ) {

          if ( empty( $link_pair[0] ) )
            $export_value = isset( $link_pair[1] ) ? $link_pair[1] : '';
          else {
            $pattern = empty( $link_pair[1] ) ? '<%1$s>' : '[%2$s](%1$s)';
            $export_value = vsprintf( $pattern, $link_pair );
          }
        } else {
          
          // instance has the linktext and link property set
          if ( $this->has_link() ) {
            $pattern = strlen( $link_pair ) > 0 ? '[%2$s](%1$s)' : '<%1$s>' ;
            $export_value = sprintf( $pattern, $this->link, $link_pair );
          } else {
            $export_value = $link_pair;
          }
        }
        
        break;

      case 'rich-text':

        /*
         * what we need to do here is add the missing markup (wpautop does 
         * this) and then remove all line breaks and such so the whole thing 
         * looks like one field
         */
        $export_value = preg_replace( '/^\s+|\n|\r|\s+$/m', '', wpautop( $value, true ) );
        break;

      default:

        $value = maybe_unserialize( $value );

        /*
         * as of version 1.7.9 multi-type fields export their values as a 
         * comma-separated list of values; values that contain a comma will use 
         * the &#44; entity to represent them
         */
        if ( $this->is_multi() ) {
          $export_value = implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $this->apply_multilingual_filter_to_array( (array) $value ) );
        } elseif ( is_array( $value ) ) {
          
          /*
           * this is a fallback, fields that store their values as arrays won't 
           * normally go through here, but if the value is an array, it requires 
           * special handling. This might happen if the field type was changed 
           * from a multi-type field to a single value field.
           */
          switch ( count($value) ) {
            case 0:
              $export_value = '';
              break;
            case 1:
              $export_value = $this->apply_multilingual_filter( html_entity_decode( current($value), ENT_QUOTES, "UTF-8" ) );
              break;
            default:
              // export it as a serialized array
              $export_value = html_entity_decode( serialize( $this->apply_multilingual_filter_to_array( $value ) ), ENT_QUOTES, "UTF-8" );
          }
          
          
          if ( PDB_DEBUG > 2 ) {
            Participants_Db::debug_log(__METHOD__.' array value for field: '.$this->name() );
          }
          
        } else {
          $export_value = $this->apply_multilingual_filter( html_entity_decode( $value, ENT_QUOTES, "UTF-8" ) );
        }
    }

    return $export_value;
  }
  
  /**
   * optionally applies the multilingual filter to the export value
   * 
   * @param string $value
   * @return string value
   */
  public function apply_multilingual_filter( $value )
  {
    /**
     * @filter pdb-multilingual_string_export_enable
     * @param bool the enable mode
     * @return bool
     */
    if ( Participants_Db::apply_filters( 'multilingual_string_export_enable', false ) === false ) {
      $value = Participants_Db::apply_filters( 'translate_string', $value );
    }
    return $value;
  }
  
  /**
   * applies the multilingual filter to an array
   * 
   * @param array $array
   * @return array
   */
  protected function apply_multilingual_filter_to_array( $array )
  {
    array_walk($array, array( $this, 'apply_multilingual_filter' ) );
    return $array;
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
      $data = Participants_Db::get_participant( $this->record_id );
      if ( $data && isset( $data[$this->name] ) ) {
        $this->_set_value( $data[$this->name] );
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
    return $this->_field_value_display(false);
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
      case 'date':
      case 'date5':
        $value = $this->value == '0' ? '' : $this->value;
        break;
      case 'image-upload':
        $image = $this->image_instance();
        $value = $image->image_defined ? $this->value : '';
        break;
      default:
        $value = $this->value;
    }
    /**
     * @filter pdb-field_has_content_test_value
     * 
     * @param string the value to test
     * @param PDb_Field_Item the current field
     * @return string the value to test for content
     */
    return !$this->is_empty( Participants_Db::apply_filters( 'field_has_content_test_value', $value, $this ) );
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
   * this is used for values as they come in from a database or imported from CSV, 
   * for fields that store their value as an array
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
      if ( function_exists( 'get_attachment_url_by_slug' ) ) {
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
    if ( !$this->is_upload_field() ) {
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
    return Participants_Db::apply_filters( 'add_required_mark', Participants_Db::plugin_setting_is_true( 'mark_required_fields' ) && $this->validation != 'no' && !in_array( $this->module, array('list', 'search', 'total', 'single') ), $this );
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
    echo PDb_Template_Item::prep_css_class_string( $this->base_id() );
  }

  /**
   * prints the field element with an id
   * 
   */
  public function print_element_with_id()
  {
    $this->attributes['id'] = PDb_Template_Item::prep_css_class_string( $this->base_id());
    $this->print_element();
  }
  
  /**
   * provides the base element id
   * 
   * @return string
   */
  protected function base_id()
  {
    return Participants_Db::$prefix . $this->name . '-' . Participants_Db::$instance_index;
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

      $label = sprintf( Participants_Db::plugin_setting_value( 'required_field_marker' ), $label );
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

  /**
   * returns an element value formatted for display
   * 
   * @param bool $html if true will provide a html-formatted value, if false, provides a raw value
   * @return string the object's current value, formatted
   */
  protected function _field_value_display( $html = true )
  {
    $return = false;

    $this->html_output = $html;
    /**
     * @filter pdb-before_display_form_element
     * 
     * @param bool false
     * @param PDb_Field_Item $field the field object
     * @return string the field value display or false if not altering the value
     * 
     * formerly, this was set as "pdb-before_display_field" and included a more limited set of arguments
     */
    if ( has_filter( Participants_Db::$prefix . 'before_display_form_element' ) ) {
      $return = Participants_Db::apply_filters( 'before_display_form_element', $return, $this );
    } elseif ( has_filter( Participants_Db::$prefix . 'before_display_field' ) ) {
      // provided for backward-compatibility
      $return = Participants_Db::apply_filters( 'before_display_field', $return, $this->value(), $this->form_element() );
    }

    if ( $return === false ) :

      switch ( $this->form_element() ) :

        case 'image-upload' :

          switch ( $this->module() ) {
            case 'single':
            case 'list':
            case 'tag-template':
            case 'email-template':
              $display_mode = 'image';
              break;
            case 'signup':
            case 'admin-edit':
            case 'record':
              $display_mode = 'both';
              break;
            default :
              $display_mode = 'none';
          }

          $image = $this->image_instance();

          $image->display_mode = $display_mode;

          if ( $this->html_output ) {

            $image->set_image_wrap();

            if ( !$image->image_defined ) {
              $this->add_class( 'image-blank-field' );
            }

            $return = $image->get_image_html();
          } elseif ( $image->file_exists ) {
            $return = $image->get_image_file();
          } else {
            $return = $this->value();
          }

          break;

        case 'file-upload' :

          if ( $this->html_output && $this->is_not_default() ) {
            $return = '';
            if ( $this->module === 'signup' ) {
              
              $this->set_link( false );
              $return = $this->value();
              
            } elseif ( $this->has_content() && Participants_Db::is_allowed_file_extension( $this->value(), $this->allowed_extensions() ) ) {
              
              $this->set_link( filter_var( Participants_Db::files_uri() . $this->value, FILTER_VALIDATE_URL ) );
              
              if ( !Participants_Db::is_admin() && $this->link() && strlen( $this->default ) > 0 ) {
                $this->value = $this->default;
              } elseif ( strpos( $this->module, 'list' ) !== false && $this->get_attribute( 'max_link_length' ) !== '' ) {
                // contract the value length
                $this->value = $this->contracted_value();
              }
              
              $return = $this->make_link();
              
            }
          } else {
            // no valid filename in the value, show a blank
            $return = '';
          }
          
          break;

        case 'date' :
          if ( $this->has_value() ) {
            if ( isset( $this->attributes['format'] ) ) {
              $return = PDb_Date_Display::get_date_with_format( $this->value, $this->attributes['format'], __METHOD__ . ' date field' );
            } else {
              $return = PDb_Date_Display::get_date( $this->value, __METHOD__ . ' date field' );
            }
          }
          $return = $return ? $return : '';
          break;

        case 'timestamp' :

          if ( $this->has_value() ) {
            $return = Participants_Db::plugin_setting_is_true( 'show_time' ) ? PDb_Date_Display::get_date_time( $this->value(), __METHOD__ . ' timestamp field with time' ) : PDb_Date_Display::get_date( $this->value(), __METHOD__ . ' timestamp field' );
          }
          $return = $return ? $return : '';
          break;

        case 'multi-checkbox' :
        case 'multi-select-other' :
        case 'multi-dropdown':

          /*
           * these elements are stored as serialized arrays of values, the data is displayed 
           * a comma-separated string of the values, using the value titles if defined
           */
          $return = $this->make_link( $this->display_array_value() );
          
          break;

        case 'link' :

          $linkdata = maybe_unserialize( $this->value() );

          if ( !empty( $linkdata ) && is_array( $linkdata ) ) {
            list( $url, $value ) = $linkdata + array('', '');
          } else {
            $url = $this->link();
            $value = $this->value();
          }

          if ( strlen( $value ) < 1 ) {
            if ( strlen( $url ) > 0 ) {
              $value = $this->has_default() ? $this->default : preg_replace( '#https?://#', '', $url );
            } else {
              $value = '';
            }
          }
          
          $return = $this->make_link( $value, $url );
          break;

        case 'text-line' :

          if ( $this->html_output ) {

            $return = $this->make_link();

            break;
          } else {

            $return = esc_html( $this->value() );

            break;
          }

        case 'text-area':
        case 'textarea':

          $pattern = $this->html_output ? '<span ' . PDb_FormElement::class_attribute( 'textarea' ) . '>%s</span>' : '%s';
          $return = sprintf( $pattern, esc_textarea( $this->value() ) );
          break;

        case 'rich-text':

          if ( $this->html_output ) {
            $return = sprintf( '<span ' . PDb_FormElement::class_attribute( 'textarea richtext' ) . '>%s</span>', Participants_Db::process_rich_text( $this->value(), 'rich-text field' ) );
          } else {
            $return = strip_tags( esc_textarea( $this->value() ) );
          }

          break;

        case 'dropdown':
        case 'radio':
        case 'checkbox':
        case 'dropdown-other':
        case 'select-other':

          if ( $this->html_output ) {
            
            $temp = $this->value();
            $this->set_value( $this->display_array_value() );
            $return = sprintf( '<span %s>%s</span>', PDb_FormElement::class_attribute( $this->form_element() ), $this->make_link() );
            $this->set_value( $temp );
          } else {
            $return = $this->display_array_value();
          }

          break;

        case 'placeholder':

          $this->set_value( $this->default_value() );

          $return = $this->html_output ? $this->make_link() : $this->value();

          break;

        case 'password':
          // password hashes are never shown
          $return = '';
          break;

        case 'decimal':
        case 'currency':
        case 'numeric':

          $field_display = $this->get_value();

          // localize the display value
          switch ( $this->form_element() ) {
            case 'decimal':
              // this is to remove any trailing zeroes
              $field_display = PDb_Localization::display_number( floatval( $this->value() ), $this );
              break;
            case 'currency':
              $field_display = PDb_Localization::display_currency( $this->value(), $this );
              break;
          }

          if ( isset( $this->attributes['data-before'] ) && $this->has_content() ) {
            $field_display = '<span class="pdb-added-content"><span class="pdb-precontent">' . esc_html( $this->attributes['data-before'] ) . '</span>' . esc_html( $field_display ) . '</span>';
          } elseif ( isset( $this->attributes['data-after'] ) && $this->has_content() ) {
            $field_display = '<span class="pdb-added-content">' . esc_html( $field_display ) . '<span class="pdb-postcontent">' . esc_html( $this->attributes['data-after'] ) . '</span></span>';
          }

          $return = $field_display;
          break;

        case 'hidden':

          if ( $this->is_dynamic_hidden_field() && !$this->is_not_default() ) {
            // this is to prevent the dynamic value key from getting printed
            $this->set_value( '' );
          } elseif ( !$this->is_dynamic_hidden_field() && !$this->has_content() ) {
            // show the default value if it's not a dynamic field value and there is no set value
            $this->set_value( $this->default );
          }

          $return = $this->value();
          break;

        default :

          $return = $this->html_output ? $this->make_link() : $this->value();

      endswitch; // form element
    endif; // return === false
    
    return $this->html_output ? $return : strip_tags( $return );
  }

  /**
   * provides a field value wrapped in an anchor tag
   * 
   * if the $display argument is provided, it just wraps the display in an anchor 
   * tag and uses either the link property or the provided href value for the anchor href
   * 
   * @param string $display the display string that will be clickable
   * @param string $href the href value
   * @return string
   */
  protected function make_link( $display = false, $href = false )
  {
    if ( $display !== false ) {
      
      $href = $href ? : $this->link();
      
      if ( $this->html_output ) {
        $return = sprintf( ( empty( $href ) ? '%2$s' : '<a href="%1$s" %3$s >%2$s</a>' ), esc_url($href), $display, PDb_FormElement::html_attributes( $this->attributes ) );
      } else {
        $return = empty( $href ) ? $display : $href;
      }
      return $return;
    }
    
    return PDb_FormElement::make_link( $this );
  }

  /**
   * provides an image object
   * 
   * @return PDb_Image instance
   */
  protected function image_instance()
  {
    return new PDb_Image( array(
        'filename' => $this->value(),
        'link' => $this->link(),
        'module' => $this->module(),
        'attributes' => $this->attributes(),
            ) );
  }
  
  /**
   * provides a contracted value if configured
   * 
   * @param string
   * @return string
   */
  public function contracted_value( $string = false )
  {
    $value = $string ? $string : $this->value();
    if ( $this->get_attribute('max_link_length') !== '' ) { 
      return self::contract_string($value, $this->get_attribute('max_link_length'));
    }
    return $value;
  }
  
  /**
   * provides a contracted version of a string
   * 
   * removes characters from the middle of the string
   * 
   * @param string $string
   * @param int $max the maximum length of the string
   * @return string
   */
  public static function contract_string( $string, $max )
  {
    $max = intval( $max );
    
    if ( strlen($string) < $max ) {
      return $string;
    }
    
    $ellipsis = Participants_Db::apply_filters( 'contract_string_ellipsis', '&hellip;' );
    
    $s1 = substr( $string, 0, $max / 2 );
    $s2 = substr( $string, $max / -2 );
    
    return $s1 . $ellipsis . $s2;
  }

}
