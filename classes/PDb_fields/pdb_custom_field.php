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

abstract class pdb_custom_field {

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
    
    add_filter( 'pdb-form_element_build_' . $this->name, array($this, 'form_element_build') );
    add_filter( 'pdb-before_display_form_element', array($this, 'display_form_element'), 10, 2 );
    add_filter( 'pdb-form_element_datatype', array($this, 'set_datatype'), 10, 2 );
    add_filter( 'pdb-set_form_element_types', array($this, 'add_element_to_selector') );
    
    add_filter( "pdb-{$this->name}_form_element_def_att_switches", array( $this, 'editor_config' ) );
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
    $this->form_element_html();
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
    $types[$this->name] = $this->title;
    return $types;
  }

  /**
   * provides the form element HTML
   * 
   * @param \PDb_FormElement $field the form element object
   * @return null
   */
  abstract protected function form_element_html();

  /**
   * display the field value in a read context
   * 
   * @return string value
   */
  protected function display_value()
  {
    return $this->field->value();
  }

  /**
   * supplies the field definition values
   * 
   * @param string $name name of the field
   * @return \PDb_Form_Field_Def|bool all the field definition values; bool false if the field is not found
   */
  protected function field_definition( $name )
  {
    return isset( \Participants_Db::$fields[$name] ) ? \Participants_Db::$fields[$name] : false;
  }

  /**
   * provides the form element's mysql datatype
   * 
   * @return string
   */
  protected function element_datatype()
  {
    return 'TINYTEXT';
  }

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
    
    if ( ! $this->field->record_id ) {
      $this->field->set_record_id( $this->find_record_id() );
    }
  }

  /**
   * finds the record ID
   * 
   * @global WP_Post $post
   * @return int record id
   */
  private function find_record_id()
  {
    global $post;
    $record_id = false;
    
    if ( is_a( $post, 'WP_Post') ) {
      $record_id = get_post_meta( $post->ID, \pdblog\Plugin::id_meta_name, true );
    }

    if ( !$record_id ) {
      $record_id = \Participants_Db::get_record_id();
    }

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
  
}
