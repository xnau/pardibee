<?php

/**
 * models a form field definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.6
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
  public $CSV;

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
   * @var string name of the current module
   */
  private $module;

  /**
   * @var array element attributes
   */
  public $attributes;

  /**
   * @var array element options
   */
  public $options;

  /**
   * instantiates the object
   * 
   * @param stdClass|string $field the field definition or field name
   */
  public function __construct( $field )
  {
    $def = is_string( $field ) ? self::get_field_def( $field ) : $field;

    $this->assign_def_props( $def );
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
    global $wpdb;
    $sql = 'SELECT COUNT(*) 
            FROM ' . Participants_Db::$fields_table . ' v 
            WHERE v.name = %s';
    return (bool) $wpdb->get_var( $wpdb->prepare( $sql, $fieldname ) );
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
    global $wpdb;
    $cachekey = 'participants-database-field-definitions';
    
    $all_defs = wp_cache_get( $cachekey );
    if ( ! $all_defs ) {
      $raw_defs = $wpdb->get_results( 'SELECT * FROM ' . Participants_Db::$fields_table );
      
      $all_defs = array();
      foreach( $raw_defs as $def ) {
        $all_defs[$def->name] = $def;
      }
      wp_cache_set( $cachekey, $all_defs );
    }
    
    return $all_defs[$fieldname];
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
    switch ( $prop ) {
      default:
        return $this->{$prop};
    }
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
    return is_null( $this->default ) ? '' : $this->default;
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
   * tells the field group
   * 
   * @return string
   */
  public function group()
  {
    return $this->group;
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
        } elseif ( $option_value === $value ) { // strip out spaces in the option because we did that to the value
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
   * tells if the field has a default value
   * 
   * @return bool true if the default is non-empty
   */
  public function has_default()
  {
    return $this->default_value() !== '';
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
    return (array) maybe_unserialize( $this->values );
  }

  /**
   * provides the field options
   * 
   * this is really only valid with form elements that have options
   * 
   * @retrun array of options as $title => $option
   */
  public function options()
  {
    return $this->options;
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
    return Participants_Db::apply_filters( 'form_field_attributes', is_array( $this->attributes ) ? $this->attributes : array(), $this );
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
        case 'CSV':
        case 'persistent':
        case 'signup':
        case 'readonly':
          $this->{$prop} = (bool) $value;
          break;

        case 'values':

          $this->values = $def->values;

          // if the values parameter has a value, then the field def is of the old format
          if ( $def->values !== '' && !is_null( $def->values ) ) {
            $values = $this->values_array();
            /*
             * for "value set" fields, the values parameter defines the options; for 
             * other fields, it defines the attributes
             */
            if ( $this->is_value_set() ) {
              $this->options = $values;
            } else {
              $this->attributes = $values;
            }
          }
          break;

        case 'attributes':
        case 'options':

          if ( !empty($value) ) {
            $this->{$prop} = (array) maybe_unserialize( $value );
          }
          break;

        default:
          $this->{$prop} = $value;
      }
    }
  }

}
