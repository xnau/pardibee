<?php
/*
 * class for handling the display of field groups in a template
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.6
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
   * @var array of group fields
   */
  public $fields = array();
  
  /**
   * instantiates a field group object
   *
   * @param object $group a object with all the field group's properties
   * @param string $module name of the current module
   */
  public function __construct( $group, $module )
  {
    
    // load the object properties
    $this->assign_props( $group );
    
    // set the field count for the group
    $this->field_count = isset( $group->fields ) ? count( (array) $group->fields ) : 0;
    
    // set up some classes
    $this->add_class( $this->has_fields() ? '' : 'pdb-group-empty' );
    $this->add_class( $this->group_fields_have_values() ? '' : 'pdb-group-novalues' );
    
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
    return ! $this->group_fields_have_values();
  }
  
  /**
   * assigns the object properties that match properties in the supplied object
   * 
   * @param object $group the supplied object or config array
   */
  protected function assign_props( $group ) {
    
    if ( is_string( $group ) ) {
      $group = array( 'name' => $group );
    }
    
    $group = (object) $group;
    
    $class_properties = array_keys( get_class_vars( get_class( $this ) ) );
      
    $item_def = new stdClass;
    
    $groups = Participants_Db::get_groups();
    if ( in_array( $group->name, $groups ) ) {
      $item_def = (object) $groups[$group->name];
    }
    
    // grab and assign the class properties from the provided object
    foreach( $class_properties as $property ) {
      
      if ( isset( $group->$property ) ) {
        
        $this->$property = $group->$property;
      
      } elseif ( isset( $item_def->$property ) ) {
        
        $this->$property = $item_def->$property;
        
      }
      
    }
    
  }
  
  /**
   * determine if the group is composed of empty fields
   * 
   * @return bool true if one or more field have values
   */
  private function group_fields_have_values()
  {
    $has_field_values = wp_cache_get( $this->name, 'pdb-group_fields_have_value', false, $found );
    
    if ( ! $found ) {
      foreach( $this->fields as $field ) {
        /* @var $field PDb_Form_Field_Def */
        if ( $field->has_value() ) {
          $has_field_values = true;
          break;
        }
      }
      reset( $this->fields );
      wp_cache_set( $this->name, $has_field_values, 'pdb-group_fields_have_value', Participants_Db::cache_expire() );
    }
    
    return $has_field_values;
  }
  
}