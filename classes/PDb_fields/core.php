<?php

/**
 * provides the basic structure for defining a custom field type in Participants Database
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPLv3
 * @version    0.5
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

abstract class core {

  /**
   * @var string name of the field type
   */
  protected $name;

  /**
   * @var string title of the field type
   */
  protected $title;

  /**
   * @var \PDb_Field_Item the current field object
   */
  protected $field;

  /**
   * @var class label string
   */
  const label = 'pdb-custom_fields';

  /**
   * constructs the instance
   * 
   * @param string $name slug name of the custom field
   * @param string $title display name
   */
  protected function __construct( $name, $title )
  {
    $this->name = $name;
    $this->title = $title;

    add_filter( 'pdb-form_element_build_' . $this->name, array( $this, 'form_element_build' ) );
    add_filter( 'pdb-before_display_form_element', array( $this, 'display_form_element' ), 10, 2 );
    add_filter( 'pdb-form_element_datatype', array( $this, 'set_datatype' ), 10, 2 );
    add_filter( 'pdb-set_form_element_types', array( $this, 'add_element_to_selector' ) );

    add_filter( "pdb-{$this->name}_form_element_def_att_switches", array( $this, 'editor_config' ) );
    
    add_filter( 'pdb-field_has_content_test_value', array( $this, 'test_content' ), 10, 2 );
  }

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {
    $field_item = new \PDb_Field_Item( $field );

    $this->setup_field( $field_item );

    $field->output = $this->form_element_html();
  }

  /**
   * display the field value in a read context
   * 
   * @param string  $display the field value
   * @param \PDb_Field_Item $field
   * @return string HTML
   */
  public function display_form_element( $display, $field )
  {
    if ( $field->form_element() === $this->name ) {
      $this->setup_field( $field );
      $display = $this->display_value();
    }
    return $display;
  }

  /**
   * sets the database datatype for the custom field
   * 
   * @param string $datatype the default datatype for this element
   * @param string  $form_element the name of the form element
   * @param string $datatype definition string of the mysql datatype to use for this element
   */
  public function set_datatype( $datatype, $form_element )
  {
    return $form_element === $this->name ? $this->element_datatype() : $datatype;
  }

  /**
   * adds the custom element to the selector dropdown
   * 
   * @param array $types all current form_element definitions
   * 
   * @return array the amended list
   */
  public function add_element_to_selector( $types )
  {
    $types[ $this->name ] = $this->title;
    return $types;
  }

  /**
   * provides the form element HTML
   * 
   * @return string
   */
  abstract protected function form_element_html();

  /**
   * display the field value in a read context
   * 
   * @return string value
   */
  abstract protected function display_value();

  /**
   * supplies the field definition values
   * 
   * @param string $name name of the field
   * @return \PDb_Form_Field_Def|bool all the field definition values; bool false if the field is not found
   */
  protected function field_definition( $name )
  {
    return isset( \Participants_Db::$fields[ $name ] ) ? \Participants_Db::$fields[ $name ] : false;
  }

  /**
   * provides the form element's mysql datatype
   * 
   * @return string
   */
  abstract protected function element_datatype();

  /**
   * sets up the field object
   * 
   * this gives us a way to trigger other setup methods when the field object comes in
   * 
   * @param \PDb_Field_Item $field the incoming object
   */
  protected function setup_field( $field )
  {
    $this->field = $field;

    if ( !$this->field->record_id ) {
      $this->field->set_record_id( $this->find_record_id() );
    }
  }

  /**
   * finds the record ID
   * 
   * @global WP_Post $post
   * @return int record id
   */
  protected function find_record_id()
  {
    $record_id = \Participants_Db::get_record_id();

    return empty( $record_id ) ? 0 : $record_id;
  }

  /**
   * sets up the options property of the field object
   * 
   * this is needed because in some contexts, the field object doesn't have the 
   * objects property set up. I need to clean this up upstream, but for now, we 
   * can fix it here
   * 
   * @param object  $field
   * 
   * @return object
   */
  protected function set_options_prop( $field )
  {
    if ( !isset( $field->options ) && isset( $field->values ) ) {
      $options = maybe_unserialize( $field->values );
      if ( is_array( $options ) ) {
        $field->options = $options;
      }
    }
    return $field;
  }

  /**
   * provides the list of editor switches for the field editor
   * 
   * @param array $switches
   * @return array
   */
  public function editor_config( $switches )
  {
    return $switches;
  }

  /**
   * sets up the linkable property of the field
   */
  protected function is_linkable()
  {
    add_filter( 'pdb-field_is_linkable', function ( $linkable, $form_element ) {
      if ( $form_element === $this->name ) {
        $linkable = true;
      }
      return $linkable;
    }, 10, 2 );
  }
  
  /**
   * registers the field type as dynamic
   * 
   * this is a field that generates its value dynamically based on the field's defined default value
   * 
   */
  protected function is_dynamic_field()
  {
    add_filter( 'pdb-dynamic_field_list', function ( $list ) {
      if ( ! in_array( $this->name, $list ) ) {
        $list[] = $this->name;
      }
      return $list;
    });
  }

  /**
   * customizes the default attribute in the field editor
   * 
   * @param string $title the display title
   * @param string $type the input type to use
   */
  protected function customize_default_attribute( $title = false, $type = false )
  {
    add_filter( 'pdb-field_default_attribute_edit_config', function ( $config, $field ) use ( $title, $type ) {
      if ( $field->form_element() === $this->name ) {
        if ( $title ) {
          $config[ 'label' ] = $title;
        }
        if ( $type ) {
          $config[ 'type' ] = $type;
        }
      }
      return $config;
    }, 10, 2 );
  }
  
  /**
   * provides the attributes string for an anchor tag
   * 
   * @return string html attribute
   */
  public function anchor_tag_attributes()
  {
    return \PDb_FormElement::html_attributes( $this->field->attributes, array('rel','download','target','type') );
  }
  
  /**
   * sets the color class for the element
   * 
   * this is the key color used in the field editors
   * 
   * must be a defined colorclass in PDb-manage-fields.css
   * 
   * @param string $colorclass name of the colorclass to use
   */
  protected function set_colorclass( $colorclass )
  {
    add_filter( 'pdb-' . $this->name . '_form_element_colorclass', function ($color) use ($colorclass) {
      return $colorclass;
    });
  }
  
  /**
   * sets a list of shortcodes where the form element is not displayed
   * 
   * @param array $suppressed list of shortcode modules where the field type should not be displayed
   */
  protected function suppressed_shortcodes( $suppressed ) {
    add_filter( 'pdb-display_column_suppressed_form_elements', function ( $list, $shortcode ) use ( $suppressed ) {
      if ( in_array( $shortcode->module, $suppressed ) && ! in_array( $this->name, $list ) ) {
        $list[] = $this->name;
      }
      return $list;
    }, 10, 2 );
  }
  
  /**
   * supplies the mechanism for supplying the "has content" value
   * 
   * @param mixed $value the value to test
   * @param \PDb_Field_Item $field the current field
   * @return mixed the value to test
   */
  public function test_content( $value, \PDb_Field_Item $field ) {
    if ( $field->form_element() === $this->name ) {
      $value = $this->has_content_test( $field );
    }
    return $value;
  }
  
  /**
   * supplies the value for testing if the element has content
   * 
   * this func should be overridden in the child class if it needs a different test
   * 
   * @param \PDb_Field_Item $field the current field
   * @return mixed the value to test
   */
  protected function has_content_test( $field ) {
    return $field->value();
  }

}
