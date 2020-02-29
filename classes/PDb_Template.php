<?php

/*
 * utility class for arbitrarily accessing record fields in a template
 * 
 * this class facilitates building a template that is not based on a loop, but needs 
 * to access and print any of the fields of a record by name
 * 
 * use the class by instantiating it with the "$this" variable, then use the resuting 
 * object methods in your template: $record = new PDb_Template($this);
 * 
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    2.1
 * @link       http://xnau.com/wordpress-plugins/
 */

if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Template {

  /**
   * @var object holds the instantiating object
   */
  private $shortcode_object;

  /**
   * holds the record object
   * @var object $record
   */
  var $record;

  /**
   * holds all the fields data
   * 
   * not organized by groups
   * 
   * @var object $fields
   */
  var $fields;

  /**
   * holds the currently displayed groups
   * 
   * @var obect $groups
   */
  var $groups;

  /**
   * holds the current module name
   * 
   * @var string module name
   */
  var $module;

  /**
   * the type of object used to instantiate the class
   * 
   * @var string $base_type
   */
  var $base_type;

  /**
   * this is an indexed array of raw (as stored) field values
   * 
   * @var array $values
   */
  var $values;

  /**
   * permalink to the record edit page
   * 
   * @var string 
   */
  var $edit_page;

  /**
   * permalink to the single record page
   *
   * @var string
   */
  var $detail_page;

  /**
   * @var int the record ID
   */
  var $id;

  /**
   * @var string the link to the detail page
   */
  var $detail_link;

  /**
   * @var string link to the record edit page
   */
  var $edit_link;

  /**
   * this class is instantiated with the module class
   * 
   * @var type $object
   */
  function __construct( $object )
  {
    $this->shortcode_object = $object;
    $this->_setup_fields();
    unset( $this->shortcode_object );
  }

  /**
   * 
   * prints a formatted field value
   * 
   * public alias for _print()
   * 
   * @param string $name name of the field to print
   */
  public function print_field( $name )
  {
    $this->_print( $name );
  }

  /**
   * 
   * prints a formatted field value
   * 
   * alias for print_field()
   * 
   * @param string $name name of the field to print
   */
  public function print_value( $name )
  {
    $this->_print( $name );
  }

  /**
   * gets an individual value from the raw values array
   * 
   * @param string $name the name of the value to get
   * @return mixed the value
   */
  public function get_value( $name )
  {
    return maybe_unserialize( $this->_value( $name ) );
  }

  /**
   * sets a field value
   * 
   * this does no field format checking, you must use a compatible value, for instance 
   * if the field stores it's value as an array, you must store an array
   *
   * @param string $name of the field
   * @param int|string $value the value to set the field to
   */
  public function set_value( $name, $value )
  {
    if ( Participants_Db::is_column( $name ) ) {
      $this->_setvalue( $name, $value );
    }
  }

  /**
   * prints a field title
   * 
   * @param string $name
   */
  public function print_title( $name )
  {

    echo $this->get_field_prop( $name, 'title' );
  }

  /**
   * prints a field help text
   * 
   * @param string $name
   */
  public function print_help_text( $name )
  {

    echo $this->get_field_prop( $name, 'help_text' );
  }

  /**
   * prints a field property
   * 
   * @param string $name the fields name
   * @param string $prop the field property to get
   * @return string
   */
  public function print_field_prop( $name, $prop )
  {
    echo $this->get_field_prop( $name, $prop );
  }

  /**
   * prints a group title given it's name
   * 
   * @param string $name
   * @return string
   */
  public function print_group_title( $name )
  {
    echo $this->get_group_prop( $name, 'title' );
  }

  /**
   * determines if the named group has a defined description
   * 
   * @param string $name the groups name
   * 
   * @return bool true if the group description is non-empty
   */
  public function has_group_description( $name )
  {
    $description = $this->get_group_prop( $name, 'description' );
    return !empty( $description );
  }

  /**
   * prints a group title given it's name
   * 
   * @param string $name
   * @return string
   */
  public function print_group_description( $name )
  {
    echo $this->get_group_prop( $name, 'description' );
  }

  /**
   * prints a value wrapped in an anchor tag with an href value
   * 
   * @param string $name of the field
   * @param string $href the href value
   * @return null
   */
  public function print_with_link( $name, $href )
  {
    if ( $this->is_defined_field( $name ) && !empty( $href ) ) {
      $this->_set_link( $name, $href );
      $this->_print( $name );
    }
  }

  /**
   * gets a field property
   * 
   * @param string $name the fields name
   * @param string $prop the field property to get
   * @return string|array
   */
  public function get_field_prop( $name, $prop )
  {
    $field = $this->get_field( $name );
    /** @var PDb_Field_Item $field */
    
    $value = '';
    
    if ( is_a( $field, 'PDb_Field_Item' ) ) {
      
      switch ( $prop ) {
        case 'title':
          $value = $field->title();
          break;
        case 'help_text':
          $value = $field->help_text();
          break;
        default:
          $value = maybe_unserialize( $field->{$prop} );
      }
      
    }
    
    return $value;
  }

  /**
   * gets a group property
   * 
   * @param string $name of the group
   * @param string $prop property name
   * @return string
   */
  public function get_group_prop( $name, $prop )
  {
    return $this->groups[$name]->{$prop};
  }

  /**
   * provides a URL for a record edit link
   * 
   * @return string the URL
   */
  public function get_edit_link( $page = '' )
  {
    $edit_page = empty( $page ) ? $this->edit_page : Participants_Db::find_permalink( $page );
    return Participants_Db::get_record_link( $this->_value( 'private_id' ), $edit_page );
  }

  /**
   * provides the URL for a record detail page
   * 
   * @return string the URL
   */
  public function get_detail_link( $page = '' )
  {
    $detail_page = empty( $page ) ? $this->detail_page : Participants_Db::find_permalink( $page );
    return Participants_Db::apply_filters( 'single_record_url', $this->cat_url_var( $detail_page, Participants_Db::$single_query, $this->_value( 'id' ) ), $this->_value( 'id' ) );
  }

  /**
   * sets the edit page property
   * 
   * it is assumed the [pdb_record] shortcode is on that page
   * 
   * @param string|int $page the page slug, path or ID
   */
  public function set_edit_page( $page = '' )
  {
    if ( empty( $page ) ) {
      $page = Participants_Db::plugin_setting_value('registration_page');
    }
    $this->edit_page = Participants_Db::find_permalink( $page );
  }

  /**
   * sets the detail page property
   * 
   * it is assumed the [pdb_single] shortcode is on that page
   * 
   * @param string|int $page the page slug, path or ID
   * 
   */
  public function set_detail_page( $page = '' )
  {
    if ( empty( $page ) ) {
      $page = !empty( $this->shortcode_object->shortcode_atts['single_record_link'] ) ? $this->shortcode_object->shortcode_atts['single_record_link'] : Participants_Db::single_record_page();
    }
    
    $this->detail_page = Participants_Db::find_permalink( $page );
  }

  /**
   * checks a field for a value to show
   * 
   * @param string $name names of the field to check

   * @return bool true if field value is non-empty
   */
  public function has_content( $name )
  {
    return $this->is_defined_field($name) ? $this->get_field( $name )->has_content() : false;
  }

  /**
   * determines if a group has any fields with non-empty content
   * 
   * typically, this is used to determine if a group should be shown or not
   * 
   * @param string $group name of the group
   * @return bool true if at least one of the group's fields have content
   */
  public function group_has_content( $group )
  {
    if ( $this->base_type === 'PDb_List' ) {
      return true;
    } else {
      if ( is_array( $this->groups[$group]->fields ) ) {
        foreach ( $this->groups[$group]->fields as $field_name ) {
          if ( $this->has_content( $field_name ) ) {
            return true;
          }
        }
      }
      return false;
    }
  }
  
  /**
   * tells if the name is a defined field
   * 
   * @param string $name
   * @return bool true if the field is defined
   */
  public function is_defined_field( $name )
  {
    return isset( $this->fields->{$name} );
  }
  
  /**
   * provides the field object
   * 
   * @param string $name name of the field
   * @return PDb_Field_Item|bool false if no field matches the name given
   */
  public function get_field( $name )
  {
    if ( $this->is_defined_field( $name ) ) {
      return $this->fields->{$name};
    } else {
      if ( PDB_DEBUG > 2 ) {
        Participants_Db::debug_log(__METHOD__.' accessed undefined field: ' . $name );
      }
      return false;
    }
  }

  /**
   * provides the field's form element
   * 
   * @var string $name the field name
   * @return string HTML
   */
  public function get_form_element( $name )
  {
    if ( property_exists( $this->fields, $name ) ) {
      $field = $this->get_field( $name );
      if ( !property_exists( $field, 'attributes' ) ) {
        $field->attributes = array();
      }
      $element = array(
          'type' => $field->form_element,
          'name' => $field->name,
          'value' => $field->value,
          'options' => $field->options,
          'class' => Participants_Db::$prefix . $field->form_element,
          'attributes' => $field->attributes,
      );
      return PDb_FormElement::get_element( $element );
    }
  }

  /**
   * prints a field form element
   * 
   * @var string $name
   * @return null
   */
  public function print_form_element( $name )
  {
    echo $this->get_form_element( $name );
  }
  
  /**
   * provides the URI for an uploaded file
   * 
   * @param string $name of the field
   * @return string empty if no file is uploaded
   */
  public function file_uri( $name )
  {
    return $this->is_defined_field($name) ? $this->get_field( $name )->file_uri() : '';
  }

  /**
   * adds a value to an url
   * 
   * @param string $url
   * @param string $name of the variable
   * @param string $value
   * 
   * @return string the concatenated url
   */
  public function cat_url_var( $url, $name, $value )
  {
    $op = strpos( $url, '?' ) === false ? '?' : '&';
    return $url . $op . $name . '=' . urlencode( $value );
  }

  /**
   * returns the named value
   * 
   * @param string $name of the property
   * 
   * @return mixed
   */
  protected function _value( $name )
  {
    switch ( $this->base_type ) {
      case 'Record_Item':
        return isset( $this->record->values[$name] ) ? $this->record->values[$name] : '';
      case 'PDb_Single':
      default:
        /**
         * @version 1.7 modified to get the currently submitted value if available
         */
        return $this->is_defined_field($name) && $this->get_field( $name )->has_content() ? $this->get_field( $name )->get_value() : ( isset($this->values[$name]) ? $this->values[$name] : '' );
    }
  }

  /**
   * sets the field value
   * 
   * @param string $name of the property
   * @param string|array $value the value to set
   * @return mixed
   */
  protected function _setvalue( $name, $value )
  {
    switch ( $this->base_type ) {
      case 'Record_Item':
        $this->record->values[$name] = $value;
      case 'PDb_Single':
      default:
        $this->values[$name] = is_array( $value ) ? serialize( $value ) : $value;
        $field = $this->get_field( $name );
        if ( is_a( $field, 'PDb_Field_Item' ) ) {
          $field->set_value($value);
        }
    }
  }

  /**
   * 
   * prints a formatted field value
   * 
   * @param string $name name of the field to print
   */
  protected function _print( $name )
  {
    if ( $this->is_defined_field( $name ) ) {
      echo $this->get_field( $name )->get_value_display();
    }
  }

  /**
   * adds a link value to a field object
   * 
   * @param string $name
   * @param string $href
   */
  private function _set_link( $name, $href )
  {
    if ( PDb_FormElement::field_is_linkable( $this->fields->{$name} ) ) {
      switch ( $this->base_type ) {
        case 'PDb_List':
          $this->fields->{$name}->link = $href;
          break;
        case 'PDb_Signup':
        case 'PDb_Single':
        case 'PDb_Record':
        default:
          $group = $this->fields->{$name}->group;
          $field = $this->record->{$group}->fields->{$name}->link = $href;
      }
    }
  }

  /**
   * sets up the fields object
   * 
   * this will use a different method for each type of object used to instantiate the class
   */
  private function _setup_fields()
  {
    $this->base_type = get_class( $this->shortcode_object );
    
    $this->values = $this->base_type !== 'PDb_List' ? $this->shortcode_object->participant_values : Participants_Db::get_participant( $this->shortcode_object->record->record_id );
    $this->set_edit_page();
    $this->set_detail_page();
    $this->module = $this->shortcode_object->module;
    $this->id = isset( $this->values['id'] ) ? $this->values['id'] : '';
    $this->edit_link = Participants_Db::apply_filters( 'record_edit_url', $this->cat_url_var( $this->edit_page, Participants_Db::$record_query, $this->values['private_id'] ), $this->values['private_id'] );
    $this->detail_link = Participants_Db::apply_filters( 'single_record_url', $this->cat_url_var( $this->detail_page, Participants_Db::$single_query, $this->id ), $this->id );
    $this->fields = new stdClass();
    $this->groups = array();
    switch ( $this->base_type ) {
      case 'PDb_List':
        foreach ( $this->shortcode_object->record->fields as $field_object ) {
          /* @var $field_object PDb_Field_Item */
        
          $name = $field_object->name();
          $this->fields->{$name} = clone $field_object;
          $this->fields->{$name}->set_record_id( $this->id );
        }
        
        foreach( $this->shortcode_object->display_groups as $groupname ) {
          
          $this->groups[$groupname] = new PDb_Template_Field_Group( Participants_Db::get_group($groupname) );
          
        }
        
        reset( $this->shortcode_object->record->fields );
        $this->_setup_list_record_object();
        
        break;
      case 'PDb_Signup':
      case 'PDb_Single':
      case 'PDb_Record':
      default:
        if ( !isset( $this->shortcode_object->record ) ) {
          error_log( __METHOD__ . ' cannot instantiate ' . __CLASS__ . ' object. Class must be instantiated with full module object.' );
          break;
        }
        $this->record = clone $this->shortcode_object->record;
        foreach ( Participants_Db::field_defs() as $name => $field ) {
          /* @var $field PDb_Form_Field_Def */
          $this->fields->{$name} = new PDb_Field_Item( $field );
          $this->fields->{$name}->set_record_id($this->shortcode_object->participant_id);
          $this->fields->{$name}->set_module( $this->shortcode_object->module );
          if ( array_key_exists( $name, $this->values ) ) {
            $this->fields->{$name}->set_value( $this->values[$name] );
          }
        }
        foreach ( $this->record as $name => $group ) {
          
          if ( count( (array) $group->fields ) === 0 ) continue 1; // skip empty groups
          $this->groups[$name] = new PDb_Template_Field_Group( $group );
          foreach ( $group->fields as $group_field ) {
            $this->groups[$name]->add_field( $group_field->name );
            $this->fields->{$group_field->name}->set_value( $group_field->value );
          }
          
          reset( $group->fields );
        }
        reset( $this->record );
        break;
    }
    //unset($this->record->options);
  }

  /**
   * builds a record object for the list module
   * 
   * @return null
   */
  private function _setup_list_record_object()
  {
    $this->record = new stdClass();
    $this->_setup_record_groups();
    foreach ( $this->fields as $name => $field ) {
      /* @var $field PDb_Field_Item */
      if ( isset( $this->record->{$field->group} ) ) {
        $this->record->{$field->group}->add_field( $field );
      }
    }
  }

  private function _setup_record_groups()
  {
    foreach ( $this->shortcode_object->display_groups as $group_name ) {
      $this->record->{$group_name} = new PDb_Template_Field_Group( Participants_Db::get_group( $group_name ) );
    }
  }

}

/**
 * class that forms a container for field groups
 */
class PDb_Template_Field_Group {
  
  /**
   * @var object the group properties from the db
   */
  private $group_props;

  /**
   *
   * @var array of field names or objects
   */
  private $fields = array();

  /**
   * instantiates the object
   * 
   * @param object $group the group object from the db
   */
  public function __construct( $group )
  {
    $this->group_props = $group;
  }
  
  /**
   * adds a field to the fields array
   * 
   * @param string|object $field name or object
   */
  public function add_field( $field )
  {
    $this->fields[] = $field;
  }
  
  /**
   * provides the property values
   * 
   * @param string $prop of the property
   * @retrun string
   */
  public function __get( $prop )
  {
    switch ( $prop ) {
      case 'title':
      case 'description':
        return Participants_Db::apply_filters('translate_string', $this->group_props->{$prop} );
      case 'fields':
        return $this->fields;
      default:
        return $this->group_props->{$prop};
    }
  }

}
