<?php

/**
 * models a form field definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Form_Field {
  /**
   * @var int id of the field definition
   */
  private $id;
  /*
   [id] => 348
   [order] => 202
   [name] => consent_ip
   [title] => Consent IP
   [default] => SERVER:REMOTE_ADDR
   [group] => admin
   [help_text] =>
   [form_element] => hidden
   [values] => a:0:{}
   [validation] => no
   [display_column] => 0
   [admin_column] => 0
   [sortable] => 0
   [CSV] => 0
   [persistent] => 0
   [signup] => 0
   [readonly] => 0
   *    */
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
  private $default;
  
  /**
   * @var string name of the field's group
   */
  private $group;
  
  /**
   * @var string field help text
   */
  private $help_text;
  
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
   * @var mixed current value
   */
  private $value = '';
  
  /**
   * @var string url for the field link
   */
  private $link = '';
  
  /**
   * instantiates the object
   * 
   * @param stdClass $def the field definition
   */
  public function __construct( $def )
  {
    if ( empty( $def ) ) {
      ob_start();
      var_dump($def);
      error_log(__METHOD__.' def: '.ob_get_clean());
    }
    
    
    $this->assign_props( $def );
    
//    error_log(__METHOD__.' 
//      
//def: '.print_r($def,1).' 
//  
//object: '.print_r($this,1));
  }
  
  /**
   * provides an instance of the object, given a field name
   * 
   * @global wpdb $wpdb
   * @param string  $fieldname
   * @return PDb_Form_Field|bool false if no field matches the name given
   */
  public static function instance( $fieldname )
  {
    global $wpdb;
    $sql = 'SELECT v.* 
            FROM ' . Participants_Db::$fields_table . ' v 
            WHERE v.name = %s';
    $result = $wpdb->get_results( $wpdb->prepare( $sql, $fieldname ) );
    return empty($result) ? false : new self( current( $result ) );
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
    error_log(__METHOD__.' setting property: '.$prop.' 
      
trace: '.print_r(wp_debug_backtrace_summary(),1));
    $this->{$prop} = $value;
  }
  
  /**
   * sets the module property
   * 
   * @param string  $module name of the module
   */
  public function set_module( $module )
  {
    $this->module = $module;
  }
  
  /**
   * sets the value of the field
   * 
   * @param mixed $value
   */
  public function set_value( $value )
  {
    $this->value = $value;
  }
  
  /**
   * sets the link property of the field
   * 
   * @param string  $url
   */
  public function set_link( $url )
  {
    $this->link = $url;
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
   * provides the current value
   * 
   * @return string
   */
  public function value()
  {
    return $this->value;
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
   * tells if the field has a non-empty value
   * 
   * @return bool
   */
  public function has_value()
  {
    return $this->value !== '';
  }
  
  /**
   * provides the values array
   * 
   * this is the contents of the "vlaues" paramter of the definition
   * 
   * @return array
   */
  public function values_array()
  {
    return maybe_unserialize( $this->values );
  }
    
  
  /**
   * assigns the object properties
   * 
   * @param object $def the field definition values
   */
  private function assign_props( $def )
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

        default:
          $this->{$prop} = $value;
      }
    }
  }
}
