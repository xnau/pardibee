<?php

/**
 * models a form field definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Form_Field_Def {
  /**
   * @var int id of the field definition
   */
  private $id;
  /**
   * @var int order value of the field definition
   */
  private $order;
  
  /**
   * @var string name of the field
   */
  private $name;
  
  /**
   * @var string display title of the field
   */
  private $title;
  
  /**
   * @var string default value of the field
   */
  public $default;
  
  /**
   * @var string name of the field's group
   */
  private $group;
  
  /**
   * @var string field help text
   */
  public $help_text;
  
  /**
   * @var string form element of the field
   */
  private $form_element;
  
  /**
   * @var string raw string form the "values" parameter of the field def
   */
  private $values;
  
  /**
   * @var string validation type
   */
  private $validation;
  
  /**
   * @var int display column value
   */
  private $display_column;
  
  /**
   * @var int admin column value
   */
  private $admin_column;
  
  /**
   * @var bool field is sortable
   */
  private $sortable;
  
  /**
   * @var bool field is included in CSV export
   */
  private $CSV;
  
  /**
   * @var bool field is persistent
   */
  private $persistent;
  
  /**
   * @var bool field is included in the signup form
   */
  private $signup;
  
  /**
   * @var bool field is read only
   */
  private $readonly;
  
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
    assert( is_string( $field ) || property_exists( $field, 'name' ), ' improperly instantiated with: ' . print_r($field,1) );
    $def = is_string( $field ) ? self::get_field_def($field) : $field;
    
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
    $sql = 'SELECT v.* 
            FROM ' . Participants_Db::$fields_table . ' v 
            WHERE v.name = %s';
    return current( $wpdb->get_results( $wpdb->prepare( $sql, $fieldname ) ) );
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
    assert( property_exists($this, $prop ),' undefined property: ' . $prop );
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
    switch ($prop) {
      case 'value':
        $this->{$prop} = $value;
        break;
      default:
        error_log(__METHOD__.' setting property: '.$prop.' 

    trace: '.print_r(wp_debug_backtrace_summary(),1));
        $this->{$prop} = $value;
    }
  }
  
  /**
   * makes the field a read-only field
   */
  public function make_readonly()
  {
    $this->readonly = true;
    $this->form_element = 'text-line';
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
   * provides the default value for the field
   * 
   * @return string
   */
  public function default_value()
  {
    return $this->default;
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
          } elseif ( str_replace(' ','',$option_value) === $value ) { // strip out spaces in the option because we did that to the value
            // grab the option title
            return Participants_Db::apply_filters( 'translate_string', stripslashes( $option_title ) );
          }
        }
    }
    return $value;
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
    return $this->is_hidden_field() && Participants_Db::is_dynamic_value( $this->value );
  }
  
  /**
   * tells if the field is the single record link field
   * 
   * @return bool
   */
  public function is_single_record_link_field()
  {
    return !in_array( $this->form_element, array('rich-text', 'link' ) ) && Participants_Db::is_single_record_link($this);
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
   * provides the values array
   * 
   * this is the contents of the "values" paramter of the definition
   * 
   * @return array
   */
  public function values_array()
  {
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
        case 'CSV':
        case 'persistent':
        case 'signup':
        case 'readonly':
          $this->{$prop} = (bool) $value;
          break;
        
        case 'values':
          
          $this->values = $def->values;
          $values = $this->values_array();
          /*
           * for "value set" fields, the values parameter defines the options; for 
           * other fields, it defines the attributes
           */
          if ( $this->is_value_set() ) {
//            $this->values = $values;
            $this->options = $values;
          } else {
            $this->attributes = $values;
          }
          break;

        default:
          $this->{$prop} = $value;
      }
    }
  }
}
