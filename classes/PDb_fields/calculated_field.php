<?php

/**
 * defines a field that calculates a value following a template in the field definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.6
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

defined( 'ABSPATH' ) || exit;

abstract class calculated_field extends dynamic_db_field {
  
  use calculations;
  
  /**
   * @var bool tells if the calculation has the info it needs
   */
  protected $complete;
  
  /**
   * @var string name of the date key transient
   */
  const keycache = 'pdb-key_cache';
  
  /**
   * @var \PDb_fields\calc_template instance
   */
  protected $template;
  
  /**
   * @var int|float holds the calculated value
   */
  protected $result;

  /**
   * 
   */
  public function __construct()
  {
    $class = get_class($this);
    parent::__construct( $class::element_name, $this->field_title() );
    
    $this->customize_default_attribute( __( 'Template', 'participants-database' ), 'text-area' );
    
    $this->is_linkable();
    
    $this->set_signup_filter();
    add_filter( 'pdb-before_submit_signup', array( $this, 'set_submission_value' ) );
    
    add_filter( 'pdb-new_field_params', array( $this, 'new_field_defaults' ) );
    
    add_filter( sprintf( 'pdb-%s_is_numeric', $class::element_name ), function(){
      return $this->is_numeric_field();
    });
  }
  
  /**
   * provides the field's title
   * 
   * @return string
   */
  abstract protected function field_title();
  


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
   * tells if the current field stores a numeric value
   * 
   * @return bool
   */
  abstract protected function is_numeric_field();

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    $this->set_field( $this->field );
    
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
   * places the filter for the signup form
   */
  private function set_signup_filter()
  {
    foreach( \Participants_Db::signup_shortcode_tags() as $tag ) {
      add_filter( 'pdb-shortcode_call_' . $tag, array( $this, 'setup_field_for_signup' ) );
    }
  }
  
  /**
   * sets up the field as a hidden field for a signup form
   * 
   * @param array $params the shortcode params
   * @return array
   */
  public function setup_field_for_signup( $params )
  {
    foreach( $this->field_list() as $field ) {
      /** @var \PDb_Field_Item $field */
      if ( $field->is_signup() ) {
        
        // add to the hidden fields
        add_filter( 'pdb-signup_form_hidden_fields', function ( $hidden_fields ) use ( $field ) {
          $hidden_fields[ $field->name() ] = '';
          return $hidden_fields;
        } );

        // prevent the field from getting added to the main iterator now that is it hidden
        add_filter( 'pdb-add_field_to_iterator', function ( $add, $iterator_field ) use ( $field )  {
          if ( $iterator_field->name() === $field->name() && $iterator_field->form_element() === $field->form_element() ) {
            $add = false;
          }
          return $add;
        }, 10, 2 );
      }
    }
    
    return $params;
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
        $this->set_field( $field );
        $post[$field->name()] = $this->dynamic_value($post);
      }
    }
    
    return $post;
  }
  
  /**
   * supplies the formatted display
   */
  protected function formatted_display()
  {
    $formatted_value = $this->format( $this->field->value, $this->display_format(), 0, true );
    
    return $formatted_value === '' ? '' : $this->combined_display( $formatted_value );
  }
  
  /**
   * concatenates the display components
   * 
   * @param string $formatted_value
   * @return string
   */
  protected function combined_display( $formatted_value )
  {
    $pretext = '';
    $postext = '';
    
    if ( $this->field->has_attribute( 'data-before' ) ) {
      $pretext = '<span class="pdb-precontent">' . esc_html( $this->field->get_attribute('data-before') ) . '</span>';
    }
    if ( $this->field->has_attribute( 'data-after' ) ) {
      $postext = '<span class="pdb-postcontent">' . esc_html( $this->field->get_attribute('data-after') ) . '</span>';
    }
    
    
    return $pretext . $this->template->front_text() . $formatted_value . $this->template->back_text() . $postext;
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
    
    $dynamic_value = wp_cache_get( $this->field->cache_id(), $cachegroup, false, $found );
    
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
      
      wp_cache_add( $this->field->cache_id(), $dynamic_value, $cachegroup, HOUR_IN_SECONDS );
    }
    
    return $this->is_numeric_field() ? filter_var( $dynamic_value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) : $dynamic_value;
  }
  
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  protected function replaced_string( $data )
  {
    $replacement_data = $this->replacement_data( $data );
    
    if ( ! $this->check_data( $replacement_data ) ) {
      return false;
    }
    
    $class = get_class( $this );
    
    return $replacement_data[ $class::calc_tag ];
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
    
    return $template;
  }
  
 
  /**
   * provides the display format tag
   * 
   * @return string
   */
  protected function display_format()
  {
    $format_tag = $this->template->format_tag();
    
    return $this->is_display_only_format($format_tag) ? $format_tag : $this->unformatted_tag();
  }

  /**
   * replaces the calculation part of the template with the calculation tag
   * 
   * @return string
   */
  protected function prepped_template()
  {
    return $this->template->prepped_template( self::calc_tag );
  }

  /**
   * displays the log in a write context
   * 
   * @param object $field
   * @return string
   */
  protected function form_element_html()
  {
    return sprintf( $this->wrap_template(), $this->formatted_display(), esc_attr( $this->field->value() ) ); // $this->dynamic_value()
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
   * provides the value for a field, drawn from the database
   * 
   * @global \wpdb $wpdb
   * @param string $fieldname
   * @return string
   */
  protected function field_db_value( $fieldname )
  {
    global $wpdb;
    
    /**
     * @filter pdb-calculated_field_template_field_value_query
     * @param string query
     * @param string field name
     * @return string query
     */
    $sql = \Participants_Db::apply_filters('calculated_field_template_field_value_query', 'SELECT `' . $fieldname . '` FROM ' . $this->data_table() . ' WHERE `id` = %s', $fieldname );
    
    $db_value = $wpdb->get_var( $wpdb->prepare( $sql, $this->field->record_id ) );
    
    return is_null( $db_value ) ? '': $db_value;
  }
  
  /**
   * provides a list of the fields that are included in the template
   * 
   * @return array of field names
   */
  protected function template_field_list()
  {
    $list = array();
    
    foreach( $this->template->field_list() as $fieldname )
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
   * sets the field property
   * 
   * @param string|\PDb_FormElement $field the incoming field
   * @param int $record_id optionally supply the record id
   */
  protected function set_field( $field, $record_id = 0 )
  {
    parent::set_field( $field, $record_id );
    
    $this->template = new calc_template( $this->field, $this->default_format_tag() );
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
   * sets the "signup" checkbox for new calculated fields
   * 
   * @param array $new_field_params
   * @return array
   */
  public function new_field_defaults( $new_field_params )
  {
    $class = get_class($this);
    
    if ( $new_field_params['form_element'] === $class::element_name ) {
      $new_field_params['signup'] = 1;
    }
    
    return $new_field_params;
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
        'persistent' => false,
        'csv' => true,
        'sortable' => true,
        'help_text' => false,
        'validation' => false,
        'validation_message' => false,
        'signup' => true,
        'default' => true,
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
