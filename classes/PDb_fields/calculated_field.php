<?php

/**
 * defines a field that calculates a value following a template in the field definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

abstract class calculated_field extends dynamic_db_field {
  
  /**
   * @var bool tells if the calculation has the info it needs
   */
  protected $complete = true;
  
  /**
   * @var string name of the date key transient
   */
  const keycache = 'pdb-key_cache';
  
  /**
   * @var int|float holds the calculated value
   */
  protected $result;
  
  /**
   * @var string the field's display format
   */
  protected $display_format;

  /**
   * 
   */
  public function __construct()
  {
    $class = get_class($this);
    parent::__construct( $class::element_name, $this->field_title() );
    
    $this->customize_default_attribute( __( 'Template', 'participants-database' ), 'text-area' );
    
    $this->is_linkable();
    
    add_filter( 'pdb-shortcode_call_pdb_signup', array( $this, 'setup_field_for_signup' ) );
    add_filter( 'pdb-before_submit_signup', array( $this, 'set_submission_value' ) );
  }
  
  /**
   * provides the field's title
   * 
   * @return string
   */
  abstract protected function field_title();
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  abstract protected function replaced_string( $data );

  /**
   * provides the data set for the value tag replacement
   * 
   * defaults to the current record
   * if the $post array is provided, that will be used as the field value source
   * 
   * @param array $post optional fresh data; uses db value if empty array
   * @return array as $name => $value
   */
  abstract protected function replacement_data( $post );



  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    if ( $this->field->is_valid_single_record_link_field() ) {
      
      $this->field->set_value( $this->formatted_display() );
      
      return $this->field->output_single_record_link();
    }
    
    return $this->formatted_display();
  }

  /**
   * provides the HTML for the form element in a write context
   * 
   * @param \PDb_FormElement $field the field definition
   * @return null
   */
  public function form_element_build( $field )
  {  
    $this->element_id = uniqid();
    
    $field->form_element = 'text-line';
    
    $this->set_field( $field );
    
    $field->output = $this->form_element_html();
  }
  
  /**
   * supplies the formatted display
   */
  protected function formatted_display()
  {
    return $this->dynamic_value();
  }
  
  /**
   * sets up the field as a hidden field for a signup form
   * 
   * @param array $params
   * @return array
   */
  public function setup_field_for_signup( $params )
  {
    add_filter( 'pdb-raw_field_definition', array( $this, 'redefine_for_signup' ) );
    add_action( 'pdb-after_include_shortcode_template', function(){
      remove_filter( 'pdb-raw_field_definition', array( $this, 'redefine_for_signup' ) );
    });
    
    return $params;
  }
  
  /**
   * redefines the field for the signup form
   * 
   * this reconfigures the field when included in the signup form as a hidden 
   * field so the calculated value can be added before the submission is saved
   * 
   * @param stdClass $definition
   * @return stdClass
   */
  public function redefine_for_signup( $definition )
  {
    $class = get_class($this);
    if ( $definition->form_element === $class::element_name && $definition->signup ) {
    
      $definition->form_element = 'hidden';
      $definition->default = '';
      
      // prevent the field from getting added to the main iterator now that is it hidden
      add_filter( 'pdb-add_field_to_iterator', function ( $add, $field ) use ( $definition, $class ) {
        if ( $field->name() === $definition->name && $field->form_element() === $class::element_name ) {
          $add = false;
        }
        return $add;
      }, 10, 2 );
      
    } 
    
    return $definition;
  }
  
  /**
   * places the dynamic value in the signup submission
   * 
   * @param array $post
   * @return array
   */
  public function set_submission_value( $post )
  {
    foreach( $this->field_list() as $field )
    {
      if ( $field->is_signup() && isset( $post[$field->name()] ) ) {
        $this->set_field( $field->name() );
        $post[$field->name()] = $this->dynamic_value($post);
      }
    }
    
    return $post;
  }
  
  /**
   * provides the strings with tags replaced
   * 
   * this removes any unreplaced tags
   * 
   * @param array $data optional data set to override the data in the db
   * @return string
   */
  protected function dynamic_value( $data = false )
  {
    $cachegroup = 'pdb-calculated_field-' . $this->field->name();
    
    $dynamic_value = wp_cache_get( $this->field->record_id(), $cachegroup, false, $found );
    
    if ( $found === false ) {
      
      $replaced_string = $this->replaced_string( $data );

      if ( $replaced_string === false ) {
        
        $dynamic_value = false;
      } elseif ( $replaced_string === $this->template() ) {

        // if there is no replacement data
        $dynamic_value = $this->field->module() === 'admin-edit' ? '' : $this->field->get_attribute( 'default' );
      } else {

        $cleanup_expression = apply_filters('pdb-' . $this->name . '_cleanup_expression_array', array(
            '/\[[a-z0-9_:]+\]/',
            '/^([ ,]*)/',
            '/([ ,]*)$/',
            '/( )(?= )/',
            '/(, |,)(?=,)/',
            ) );

        // remove unreplaced tags, trim spaces and dangling or duplicate commas
        $dynamic_value = preg_replace( $cleanup_expression, '', $replaced_string );
      }
      
      wp_cache_add( $this->field->record_id(), $dynamic_value, $cachegroup, HOUR_IN_SECONDS );
    }
    
    return $dynamic_value;
  }
  
  /**
   * provides the template string
   * 
   * preps the template for the use of raw values in element attributes
   * 
   * @return string
   */
  protected function template()
  {
    return $this->completed_template();
  }
  

  
  /**
   * adds the format tag to the calculation template if it is missing
   * 
   * @return string calculation format
   */
  protected function completed_template()
  {
    $template = $this->field->default_value();
    
    if ( preg_match( '/=$/', $template ) === 1 ) {
      $template .= $this->default_format_tag();
    }
    
    return $this->extract_display_format( $template );
  }
  
  /**
   * extracts the format from the template
   * 
   * this saves the template's format tag for use in the display of the value and 
   * replaces it with the default template. This is so the saved value of the field 
   * will be a timestamp, but the display will be controlled by the format tag in 
   * the template.
   * 
   * @param string $template
   * @return string template with the format tag converted
   */
  private function extract_display_format( $template )
  {
    $has_format_tag = preg_match( '/=\[(.+)\]$/', $template, $matches );
    
    $this->display_format = $matches[1];
    
    return str_replace( '[' . $this->display_format . ']', '[?unformatted]', $template );
  }

  /**
   * extracts the calculation part of the template
   * 
   * @return string
   */
  protected function calc_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$2', $this->template() );
  }

  /**
   * replaces the calculation part of the template with the calculation tag
   * 
   * @return string
   */
  protected function prepped_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$1[' . self::calc_tag . ']$3', $this->template() );
  }
  
  /**
   * provides the default format tag
   * 
   * @return string
   */
  protected function default_format_tag()
  {
    return '[?unformatted]';
  }

  /**
   * displays the log in a write context
   * 
   * @param object $field
   * @return string
   */
  protected function form_element_html()
  {
    return sprintf( $this->wrap_template(), $this->formatted_display(), esc_attr( $this->dynamic_value() ) );
  }
  
  /**
   * provides the html wrap template
   * 
   * @return string
   */
  protected function wrap_template()
  {
    $template[] = '<input type="hidden" name="' . $this->field->name() . '" value="%2$s" />';
    $template[] = '<span class="pdb-' . $this->name . '_field">%1$s</span>';
    
    return \Participants_Db::apply_filters( $this->name . '_wrap_template', implode( PHP_EOL, $template ) );
  }

  
  /**
   * provides the form element name
   */
  
  /**
   * provides the value for a field, drawn from the database
   * 
   * @global \wpdb $wpdb
   * @param string $fieldname
   * @return string
   */
  protected function field_db_value( $fieldname )
  {
    global $wpdb;
    
    $db_value = $wpdb->get_var( $wpdb->prepare( 'SELECT `' . $fieldname . '` FROM ' . \Participants_Db::participants_table() . ' WHERE `id` = %s', $this->field->record_id ) );
    
    return is_null( $db_value ) ? '': $db_value;
  }
  
  /**
   * provides a list of the fields that are included in the template
   * 
   * @return array of field names
   */
  protected function template_field_list()
  {
    $template = $this->field->default_value();
    
    preg_match_all('/\[([^\]]+)\]/', $template, $matches );
    
    $list = array();
    
    foreach( $matches[1] as $fieldname )
    {
      if( \PDb_Form_Field_Def::is_field( $fieldname ) ) {
        $list[] = $fieldname;
      }
    }
    
    return $list;
  }
  
  /**
   * provides a global filter for the replacement data array
   * 
   * @param array $replacement_data
   * @return array
   */
  protected function filter_data( $replacement_data )
  {
    /**
     * @filter pdb-calculated_field_data
     * 
     * provides a way to alter or add to the replacement data array
     * 
     * @param array $replacement_data the replacement data set
     * @param \PDb_Field_Item $field the current field
     * @return array the filtered replacement data
     */
    return \Participants_Db::apply_filters( 'calculated_field_data', $replacement_data, $this->field );
  }
  
  /**
   * removes empty and null string values from an array
   * 
   * @param array $input
   * @reteun array
   */
  protected function clear_empty_values( $input )
  {
    return array_filter( $input, function($v) {
      return $v !== '' && !is_null( $v );
    } );
  }

  /**
   *  provides the field editor configuration switches array
   * 
   * @param array $switches
   * @return array
   */
  public function editor_config( $switches )
  {
    return array(
        'readonly' => false,
        'default' => true,
        'persistent' => false,
        'csv' => true,
        'sortable' => true,
        'help_text' => false,
        'validation' => false,
        'validation_message' => false,
        'signup' => true,
    );
  }

  /**
   * provides the form element's mysql datatype
   * 
   * @return string
   */
  protected function element_datatype()
  {
    return 'TEXT';
  }

}
