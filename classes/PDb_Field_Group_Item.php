<?php
/**
 * class for handling the display of field groups in a template
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    1.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Template_Item class
 */
if ( ! defined( 'ABSPATH' ) ) die;

class PDb_Field_Group_Item extends PDb_Template_Item {
  
  /**
   *
   * @var string the group description 
   */
  public $description;
  
  /**
   * @var int number of fields in the group
   */
  public $field_count;
  
  /**
   * @var array of field definitions, either PDb_Form_Field_Def or PDb_Field_Item objects
   */
  public $fields = array();
  
  /**
   * @var bool true if the fields in the group have values
   */
  private $has_values;
  
  /**
   * provides a list of the group's fields
   * 
   * @param string $group_name
   * @param bool $object_list if true, return a list of PDb_Form_Field_Def objects
   * @return array of field names or field def objects
   */
  public static function get_group_fields( $group_name, $object_list = false )
  {
    $group = new self( $group_name );
    
    if ( $object_list ) {
      return $group->fields;
    } else {
      return array_keys( $group->fields );
    }
  }
  
  /**
   * instantiates a field group object
   *
   * @param object|array|string $group_config a object or array with all the field group's properties or the string name of the group
   * @param string $module optional name of the current module
   */
  public function __construct( $group_config, $module = '' )
  {
    // load the object properties from the supplied configuration
    $this->assign_props( $this->recast_config($group_config) );
    
    // set up some classes
    $this->add_class( $this->has_fields() ? '' : 'pdb-group-empty' );
    $this->add_class( $this->has_values ? '' : 'pdb-group-novalues' );
    
    $this->module = $module;
  }
  
  /**
   * prints the title of the group
   *
   * @param string $start_tag the opening tag for the title wrapper
   * @param string $end_tag   the closing tag for the title wrapper
   */
  public function print_title( $start_tag = '<h3 class="pdb-group-title">', $end_tag = '</h3>', $echo = true ) {
    
    if ( $this->printing_title() ) {
      $output = $start_tag.stripslashes($this->title()).$end_tag;
      if ( $echo ) echo $output;
      else return $output;
    }
    
  }
  
  /**
   * prints an HTML class value
   */
  public function print_class() {
    echo $this->get_class() . ' ' . $this->name . '-group';
  }
  
  /**
   * provides the group description
   * 
   * @return string
   */
  public function description()
  {
    return $this->prepare_display_value( stripslashes($this->description) );
  }
  
  /**
   * prints a group description
   *
   * @param array  $wrap  tags to wrap the description in; first element is
   *                      opening tag, second is closing tag (optional)
   * @param bool   $echo  if true, echo the description (defaults to true)
   *
   */
  public function print_description(  $start_tag = '<p class="pdb-group-description">', $end_tag = '</p>', $echo = true ) {
    
    if ( $this->printing_groups() and ! empty( $this->description ) ) {
      
      $output = $start_tag.$this->description().$end_tag;
      
      if ( $echo ) echo $output;
      else return $output;
      
    }
    
  }
  
  /**
   * indicates whether group titles should be shown
   * 
   * @return bool 
   */
  public function printing_title() {
    
    return (bool) $this->printing_groups() and ! empty( $this->title );
  }
  
  /**
   * indicates whether groups are to be printed in the form
   *
   * signup and record forms print group titles/descriptions only if the setting for that form is true
   * all other shortcodes always print groups, but they're really only seen in single record displays
   * 
   * @return bool true if groups are to be printed
   */
  public function printing_groups() {
    
    switch ($this->module) {
      case 'signup':
        $optionname = 'signup_show_group_descriptions';
        break;
      case 'record':
        $optionname = 'show_group_descriptions';
        break;
      default:
        return true;
    }
    
    return Participants_Db::plugin_setting_is_true($optionname);
  }
  
  /**
   * tells if the group gas fields in it
   * 
   * @return bool
   */
  public function has_fields()
  {
    return $this->field_count > 0;
  }
  
  /**
   * provides the group's title
   * 
   * if the title is blank, provides the name
   * 
   * @return string
   */
  public function title()
  {
    if ( ! empty($this->title) ) {
      return Participants_Db::apply_filters( 'translate_string', $this->title );
    }
    
    return $this->name;
  }
  
  /**
   * tells if all the group's fields are empty
   * 
   * @return bool
   */
  public function has_all_empty_fields()
  {
    return ! $this->has_values;
  }
  
  /**
   * recasts the supplied configuration into an object
   * 
   * @param string|array|object $group_config
   * @return object
   */
  private function recast_config( $group_config )
  {
    switch (true) {
      
      case is_string( $group_config ):
        
        $config_obj = new stdClass();
        $config_obj->name = $group_config;
        break;
      
      case is_array( $group_config ) :
        $config_obj = (object) $group_config;
        break;
      
      default:
        
        $config_obj = $group_config;
    }
    
    return $config_obj;
  }
  
  /**
   * assigns the object properties that match properties in the supplied object
   * 
   * @param object $group_config the supplied object or config array
   */
  protected function assign_props( $group_config )
  {$class_properties = array_keys( get_class_vars( get_class( $this ) ) );
      
    $group_def = $this->group_def( $group_config->name );
    
    $field_config = isset( $group_config->fields ) ? $group_config->fields : false;
    unset( $group_config->fields );
    
    // grab and assign the class properties from the provided object
    foreach( $class_properties as $property ) {
      
      if ( isset( $group_config->$property ) ) {
        
        $this->$property = $group_config->$property;
      
      } elseif ( isset( $group_def->$property ) ) {
        
        $this->$property = $group_def->$property;
      }
    }
    
    $this->setup_fields($field_config);
    
    // set the field count for the group
    $this->field_count = count( $this->fields );
    
    $this->group_fields_have_values();
  }
  
  /**
   * supplies the groups definition object
   * 
   * @param string $groupname
   * @return object
   */
  private function group_def( $groupname )
  {
    $groups = Participants_Db::get_groups();
    
    return (object) $groups[$groupname];
  }
  
  /**
   * sets up the fields property
   * 
   * @param object|array $fields_config the fields from the configuraiton array
   * @global \wpdb $wpdb
   */
  private function setup_fields( $fields_config = false )
  {
    if ( $fields_config === false ) {
      
      global $wpdb;

      $results = $wpdb->get_col( $wpdb->prepare( 'SELECT f.name FROM '  . Participants_Db::$fields_table . ' f WHERE f.group = %s', $this->name ) );

      foreach ( $results as $fieldname ) {
        $this->fields[$fieldname] = Participants_Db::$fields[$fieldname];
      }
    
      return;
    }
    
    if ( is_object( $fields_config ) ) {
      $fields_config = (array) $fields_config;
    }
    
    foreach( $fields_config as $fieldname => $field_object ) {
      $this->fields[ $fieldname ] = $field_object;
    }
  }
  
  /**
   * determine if the group is composed of empty fields
   * 
   * @return bool true if one or more field have values
   */
  private function group_fields_have_values()
  {
    $found = false;
    $has_field_values = wp_cache_get( $this->name, 'pdb-group_fields_have_value', false, $found );
    
    if ( ! $found ) {
      
      foreach( $this->fields as $field ) {
        /* @var $field PDb_Form_Field_Def */
        
        if ( is_a( $field, '\PDb_Field_Item' ) && $field->has_value() ) {
          
          $has_field_values = true;
          break;
        }
      }
      
      reset( $this->fields );
      wp_cache_set( $this->name, $has_field_values, 'pdb-group_fields_have_value', Participants_Db::cache_expire() );
    }
    
    $this->has_values = $has_field_values;
  }
  
}