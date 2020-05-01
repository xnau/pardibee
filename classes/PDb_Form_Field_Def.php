<?php

/**
 * models a form field definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.9
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
class PDb_Form_Field_Def {

  /**
   * @var int id of the field definition
   */
  protected $id;

  /**
   * @var int order value of the field definition
   */
  protected $order;

  /**
   * @var string name of the field
   */
  protected $name;

  /**
   * @var string display title of the field
   */
  protected $title;

  /**
   * @var string default value of the field
   */
  public $default;

  /**
   * @var string name of the field's group
   */
  protected $group;

  /**
   * @var string title of the field's group
   */
  protected $grouptitle;

  /**
   * @var string field help text
   */
  public $help_text;

  /**
   * @var string form element of the field
   */
  protected $form_element;

  /**
   * @var string raw string from the "values" parameter of the field def
   */
  private $values;

  /**
   * @var string validation type
   */
  public $validation;

  /**
   * @var int display column value
   */
  protected $display_column;

  /**
   * @var int admin column value
   */
  protected $admin_column;

  /**
   * @var bool field is sortable
   */
  public $sortable;

  /**
   * @var bool field is included in CSV export
   */
  public $csv;

  /**
   * @var bool field is persistent
   */
  public $persistent;

  /**
   * @var bool field is included in the signup form
   */
  public $signup;

  /**
   * @var bool field is read only
   */
  protected $readonly;
  
  /**
   * @var string holds the validation message
   */
  public $validation_message;

  /**
   * @var string name of the current module
   */
  private $module;

  /**
   * @var array element attributes
   */
  public $attributes = array();

  /**
   * @var array element options
   */
  public $options = array();

  /**
   * instantiates the object
   * 
   * @param stdClass|string $field the field definition or field name
   */
  public function __construct( $field )
  {
    $def = is_string( $field ) ? self::get_field_def( $field ) : $field;

    if ( $def ) {
      $this->assign_def_props( $def );
    }
  }

  /**
   * provides an instance of the object, given a field name
   * 
   * @param string  $fieldname
   * @return PDb_Form_Field_Def|bool false if no field matches the name given
   */
  public static function instance( $fieldname )
  {
    return $result = self::get_field_def( $fieldname ) ? new self( $result ) : false;
  }

  /**
   * tells if the field def is in the db
   * 
   * @global wpdb $wpdb
   * @param string  $fieldname
   * @return bool
   */
  public static function is_field( $fieldname )
  {
    if ( ! is_string($fieldname) ) {
      return false;
    }
    
    $cachekey = 'pdb-field_list';
    $field_list = wp_cache_get( $cachekey );
    
    if ( ! $field_list ) {
      global $wpdb;
      $sql = 'SELECT v.name 
              FROM ' . Participants_Db::$fields_table . ' v';
      $field_list = $wpdb->get_col( $sql );
      
      wp_cache_set( $cachekey, $field_list, '', Participants_Db::cache_expire() );
    }
    
    return in_array( $fieldname, $field_list );
  }

  /**
   * supplies the field definition data from the db
   * 
   * @global wpdb $wpdb
   * @param string  $fieldname
   * @return object
   */
  private static function get_field_def( $fieldname )
  {
    $cachekey = 'pdb-field_def';
    $def = wp_cache_get( $fieldname, $cachekey );
    
    if ( ! $def ) {
      global $wpdb;
      $sql = 'SELECT v.*, g.title AS grouptitle 
              FROM ' . Participants_Db::$fields_table . ' v 
                JOIN ' . Participants_Db::$groups_table . ' g
                  ON v.group = g.name 
              WHERE v.name = %s';
      $def = current( $wpdb->get_results( $wpdb->prepare( $sql, $fieldname ) ) );
      
      wp_cache_set( $fieldname, $def, $cachekey, Participants_Db::cache_expire() );
      
    }
    
    return $def;
  }
  
  /**
   * provides the value of the named property
   * 
   * this is an an explicit version of the __get magic method
   * 
   * @param string $prop property name
   * @return mixed null if property method does not exist
   */
  public function get_prop( $prop )
  {
    switch ( $prop ) {
      case 'form_element':
      case 'default_value':
      case 'name':
      case 'group':
      case 'options':
      case 'attributes':
      case 'help_text':
      case 'validation':
      case 'validation_message':
        return $this->{$prop}();
      case 'sortable':
      case 'csv':
      case 'persistent':
      case 'signup':
      case 'readonly':
        return $this->{'is_' . $prop}();
      case 'default':
        return $this->default_value();
      case 'id':
      case 'order':
      case 'title':
        return $this->{$prop};
      default:
        return null;
    }
  }

  /**
   * provides a property value
   * 
   * @param string  $prop of the property
   * @return mixed the property value
   */
  public function __get( $prop )
  {
//    error_log(__METHOD__.' getting property: '.$prop.' 
//      
//trace: '.print_r(wp_debug_backtrace_summary(),1));
//    switch ( $prop ) {
//      case 'form_element':
//      case 'title':
//      case 'default':
//      case 'name':
//      case 'group':
//      case 'options':
//      case 'attributes':
//      case 'help_text':
//      case 'validation_message':
//      case 'sortable':
//      case 'csv':
//      case 'persistent':
//      case 'signup':
//      case 'readonly':
//        return $this->{$prop}();
//      default:
//        return property_exists(get_class($this), $prop) ? $this->{$prop} : null;
//    }
    return $this->get_prop( $prop );
  }

  /**
   * sets a property value
   * 
   * @param string $prop name of the property to set
   * @paam mixed $value
   * 
   */
  public function __set( $prop, $value )
  {
    switch ( $prop ) {
      case 'value':
        $this->{$prop} = $value;
        break;
      default:
        $this->{$prop} = $value;
    }
  }

  /**
   * makes the field a read-only field
   * 
   * @param bool $readonly if true, makes the field a readonly field, if false, makes the field a public writable field
   */
  public function make_readonly( $readonly = true )
  {
    if ( $readonly ) {
      $this->readonly = true;
      $this->form_element = 'text-line';
    } else {
      $this->readonly = false;
    }
  }

  /**
   * sets the readonly state
   * 
   * @param bool $state defaults to true, sets the readonly state
   */
  public function set_readonly( $state = true )
  {
    $this->readonly = (bool) $state;
  }
  
  /**
   * sets the form_element property
   * 
   * @param string $form_element
   */
  public function set_form_element( $form_element )
  {
    if ( PDb_FormElement::is_form_element( $form_element ) ) {
      $this->form_element = esc_sql($form_element);
    }
  }

  /**
   * provides the name of the field
   * 
   * @return string
   */
  public function name()
  {
    return $this->name;
  }

  /**
   * provides the field title
   * 
   * @return string
   */
  public function title()
  {
    return Participants_Db::apply_filters( 'translate_string', $this->title );
  }

  /**
   * provides the default value for the field
   * 
   * @return string
   */
  public function default_value()
  {
    return Participants_Db::apply_filters( 'translate_string', $this->default );
  }

  /**
   * provides the validation type field
   * 
   * @return string
   */
  public function validation()
  {
    return $this->validation;
  }

  /**
   * provides the validation message for the field
   * 
   * @return string
   */
  public function validation_message()
  {
    return Participants_Db::apply_filters( 'translate_string', $this->validation_message );
  }

  /**
   * tells the field's form element
   * 
   * @return string name of the form element
   */
  public function form_element()
  {
    return $this->form_element;
  }

  /**
   * provides the display title of the field's form element
   * 
   * @return string title for the form element
   */
  public function form_element_title()
  {
    $types = PDb_FormElement::get_types();
    return  isset( $types[$this->form_element] ) ? $types[$this->form_element] : $this->form_element;
  }

  /**
   * tells the field group
   * 
   * @return string
   */
  public function group()
  {
    return $this->group;
  }

  /**
   * tells the field group
   * 
   * @return string
   */
  public function group_title()
  {
    if ( ! empty($this->grouptitle) ) {
      return Participants_Db::apply_filters( 'translate_string', $this->grouptitle );
    }
    
    return $this->group;
  }
  
  /**
   * provides the field options
   * 
   * this is really only valid with form elements that have options
   * 
   * @retrun array of options as $title => $value
   */
  public function options()
  {
    return $this->options;
  }

  /**
   * provides the field options
   * 
   * alias of options method for backward compatibility
   * 
   * @retrun array of options as $title => $value
   */
  public function values()
  {
    return $this->options();
  }

  /**
   * provides an array of option values
   * 
   * this provides an indexed array of just the values, titles are ignored
   * 
   * @return array of option values
   */
  public function option_values()
  {
    return array_values( $this->options );
  }

  /**
   * provides the attributes array
   * 
   * these are typically printed in the HTML as attributes to the main tag
   * 
   * @return array as $name => $value
   */
  public function attributes()
  {
    /**
     * @filter pdb-form_field_attributes
     * @param array the field attributes
     * @param PDb_Form_Field_Def current instance
     * @return array as $name => $value
     */
    return Participants_Db::apply_filters( 'form_field_attributes', $this->attributes, $this );
  }
  
  /**
   * provides the named attribute value
   * 
   * @param string $name name of the attribute to get
   * @return string empty string if the attribute is not set
   */
  public function get_attribute( $name )
  {
    $attributes = $this->attributes();
    return isset( $attributes[$name] ) ? $attributes[$name] : '';
  }
  
  /**
   * provides the allowed extensions attribute for a file upload field
   * 
   * @return array
   */
  public function allowed_extensions()
  {
    if ( $this->get_attribute('allowed') === '' ) {
      
      /* if the depricated style of setting is present
       * it will show up as an array where the key and value are identical
       */
      $extensions_array = array_filter($this->attributes(), function ($k,$v) {
        return $k === $v;
      }, ARRAY_FILTER_USE_BOTH );
      
    } else {
      
      $extensions_array = explode( '|', strtolower( $this->get_attribute('allowed') ) );
    }
    
    return $extensions_array;
  }
  
  /**
   * provides the help text
   * 
   * @return string
   */
  public function help_text()
  {
    return Participants_Db::apply_filters( 'translate_string', $this->help_text );
  }

  /**
   * provides a "value title" if defined
   * 
   * @param string  $value
   * @return string the value or it's defined title
   */
  public function value_title( $value )
  {
    if ( $this->is_value_set() ) {
      foreach ( $this->options as $option_title => $option_value ) {
        if ( !is_string( $option_title ) || $option_title === 'other' ) {
          // do nothing: we use the stored value
        } elseif ( $option_value === $value ) {
          // grab the option title
          return Participants_Db::apply_filters( 'translate_string', stripslashes( $option_title ) );
        }
      }
    }
    return $value;
  }

  /**
   * tells if the field is readonly
   * 
   * @return bool
   */
  public function is_readonly()
  {
    return (bool) $this->readonly;
  }
  
  /**
   * tells if the field is some kind of file upload field
   * 
   * @return bool
   */
  public function is_upload_field()
  {
    /**
     * @filter pdb-upload_field_types
     * @param array of upload field form element names
     * @return array
     */
    $upload_fields = Participants_Db::apply_filters( 'upload_field_types', array( 'file-upload', 'image-upload' ) );
    
    return in_array( $this->form_element(), $upload_fields );
  }

  /**
   * tells if the field is an internal field
   * 
   * @return bool true if it is an internal field
   */
  public function is_internal_field()
  {
    return $this->group === 'internal';
  }

  /**
   * tells if the field is a hidden field
   * 
   * @return bool true if it is an internal field
   */
  public function is_hidden_field()
  {
    return $this->form_element === 'hidden';
  }

  /**
   * tells if the form element is a "value set" type element
   * 
   * @return bool
   */
  public function is_value_set()
  {
    return PDb_FormElement::is_value_set( $this->form_element );
  }

  /**
   * tells if the field is a dynamic hidden field
   * 
   * @return bool true if the field is a dynamic hidden field
   */
  public function is_dynamic_hidden_field()
  {
    return $this->is_hidden_field() && Participants_Db::is_dynamic_value( $this->default );
  }

  /**
   * tells if the field is the single record link field
   * 
   * @return bool
   */
  public function is_single_record_link_field()
  {
    return !in_array( $this->form_element, array('rich-text', 'link') ) && Participants_Db::is_single_record_link( $this );
  }

  /**
   * tells if the field has the persistent flag set
   * 
   * @return bool
   */
  public function is_persistent()
  {
    return (bool) $this->persistent;
  }
  
  /**
   * tells if the field is sortable
   * 
   * @return bool
   */
  public function is_sortable()
  {
    return (bool) $this->sortable;
  }
  
  /**
   * tells if the field is read only
   * 
   * alias for is_readonly
   * 
   * @return bool
   */
  public function is_read_only()
  {
    return $this->is_readonly();
  }
  
  /**
   * tells if the field is to be included in the signup form by default
   * 
   * @return bool
   */
  public function is_signup()
  {
    return (bool) $this->signup;
  }
  
  /**
   * tells if the field is to be included in the csv export
   * 
   * alias
   * 
   * @return bool
   */
  public function is_csv()
  {
    return $this->is_csv_exported();
  }
  
  /**
   * tells if the field is to be included in the csv export
   * 
   * @return bool
   */
  public function is_csv_exported()
  {
    return (bool) $this->csv;
  }

  /**
   * tells if the field has a default value
   * 
   * @return bool true if the default is non-empty
   */
  public function has_default()
  {
    return strlen( $this->default ) > 0;
  }

  /**
   * tells if the field has a default value
   * 
   * @return bool true if the default is non-empty
   */
  public function has_validation_message()
  {
    return strlen( $this->validation_message ) > 0;
  }

  /**
   * tells if the field is a multi-type field
   * 
   * this is a field that has multiple values and stores it's value in the db as 
   * a serialized array
   * 
   * @return bool
   */
  public function is_multi()
  {
    return PDb_FormElement::is_multi( $this->form_element );
  }
  
  /**
   * tells if the field validation is a match to another field
   * 
   * @return bool
   */
  public function is_match_validation()
  {
    return self::is_field( $this->validation );
  }
  
  /**
   * tells if the field's value is to be treated as a number
   * 
   * @return bool
   */
  public function is_numeric()
  {
    /**
     * makes it possible to register a custom form element as numeric
     * 
     * @filter pdb-numeric_fields
     * @param array of numeric form element names
     * @return array
     */
    return in_array( $this->form_element, Participants_Db::apply_filters('numeric_fields', array(
        'numeric',
        'decimal',
        'currency',
        'date',
    )));
  }

  /**
   * provides the values array
   * 
   * this is the contents of the "values" parameter of the definition
   * 
   * this is not the preferred way to get options or attributes, but it is here 
   * for edge cases
   * 
   * @return array
   */
  public function values_array()
  {
//    $calling_class = $this->get_calling_class();
//    if ( $calling_class !== get_class() ) {
//      error_log(__METHOD__.' calling deprecated method: '. print_r( wp_debug_backtrace_summary(),1 ) );
//    }
    return (array) maybe_unserialize( $this->values );
  }

  /**
   * assigns the object properties
   * 
   * @param object $def the field definition values
   */
  private function assign_def_props( $def )
  {
    foreach ( $def as $prop => $value ) {
      switch ( $prop ) {

        case 'sortable':
        case 'persistent':
        case 'signup':
        case 'readonly':
          $this->{$prop} = (bool) $value;
          break;
        
        case 'CSV':
        case 'csv':
          $this->csv = (bool) $value;
          break;
        
        case 'values':

          $this->values = $def->values; // this is for backward compatibility
          
          // if the values parameter has a value, then the field def is of the old format
          if ( $def->values !== '' && !is_null($def->values) && $def->values !== 'a:0:{}' ) {
            $values = $this->values_array();
            /*
             * for "value set" fields, the values parameter defines the options; for 
             * other fields, it defines the attributes
             */
            if ( $this->is_value_set() ) {
              if ( empty( $def->options ) || $def->options === 'a:0:{}' ) {
                $this->options = $values;
              }
            } elseif ( empty( $def->attributes ) || $def->attributes === 'a:0:{}' ) {
              $this->attributes = $values;
            }
          }
          break;

        case 'attributes':
        case 'options':
          
          if ( empty( $this->{$prop} ) ) {
            $this->{$prop} = (array) maybe_unserialize($value);
          }
          break;

        default:
          $this->{$prop} = $value;
      }
    }
  }

}
