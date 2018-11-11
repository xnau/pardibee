<?php

/**
 * manages the abstract definitions for PDB field form element types
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
class PDb_Form_Element_Def {

  /**
   * @var array of attribute statuses
   */
  private $definition_attributes;

  /**
   * @var PDb_Form_Field_Def the current field definition
   */
  private $field_def;

  /**
   * @var the row color class
   */
  private $colorclass;

  /**
   * creates the object
   * 
   * @param PDb_Form_Field_Def $field_def
   */
  public function __construct( PDb_Form_Field_Def $field_def )
  {
    $this->field_def = $field_def;

    $this->setup_switches();
  }

  /**
   * provides the HTML for the field's next definition attribute control
   * 
   * this will be used in a loop like:
   *  while ( $html = get_next_control() ) { print $html; }
   * 
   * @return string|bool HTML or bool false if end has been reached
   */
  public function get_next_control()
  {
    $html = false;
    if ( current( $this->definition_attributes ) ) {
      $html = $this->get_att_control( key( $this->definition_attributes ) );
      next( $this->definition_attributes );
    }
    return $html;
  }
  
  /**
   * provides a classname for the row container
   * 
   * @return string
   */
  public function rowclass ()
  {
    return $this->field_def->form_element() . '-form-element color-' . $this->colorclass . ' ' . $this->open_close_class();
  }
  
  
  /**
   * provides the open/close class for the field
   * 
   * @return string classname
   */
  public function open_close_class()
  {
    return isset( $_SESSION[PDb_Manage_Fields_Updates::action_key]['editorclosed'][$this->field_def->get_prop( 'id' )] ) ? 'editor-closed' : 'editor-open';
  }

  /**
   * sets up the definition attribute switches
   */
  private function setup_switches()
  {
    $form_element_switches = $this->form_element_atts();
    $this->definition_attributes = Participants_Db::array_merge2( $this->default_def_att_switches(), $form_element_switches );
    if ( $form_element_switches['validation'] === false ) {
      // disable the validation if disabled in the form element
      $this->definition_attributes['validation'] = false;
    }
    $this->definition_attributes = array_filter( $this->definition_attributes ); // remove the disabled elements
    reset( $this->definition_attributes );
  }

  /**
   * provides the control HTML for the named attribute
   * 
   * @param string $attribute
   * 
   * @return string
   */
  private function get_att_control( $attribute )
  {
//    error_log(__METHOD__.' attribute: '.$attribute);
    switch ( $attribute ) {
      case 'id':
        $config = array(
            'type' => 'hidden',
            'value' => $this->field_def->id,
        );
        break;
      case 'status':
        $config = array(
            'type' => 'hidden',
            'attributes' => array('id' => 'status_' . $this->field_def->id),
            'value' => null,
        );
        break;
      case 'selectable':
        $config = array(
            'type' => 'checkbox',
            'options' => array(1, 0),
            'attributes' => array('data-id' => $this->field_def->id ),
        );
        break;
      case 'orderable':
        $config = array(
            'type' => 'drag-sort',
        );
        break;
      case 'deletable':
        $config = array(
            'type' => 'delete-button',
            'id' => $this->field_def->id,
        );
        break;
      case 'sortable':
      case 'csv':
      case 'persistent':
      case 'signup':
      case 'readonly':
        $config = array(
            'type' => 'checkbox',
            'options' => array(1, 0),
        );
        break;
      case 'name':
        $config = array(
            'type' => 'text-line',
            'attributes' => array('readonly' => 'readonly'),
        );
        break;
      case 'title':
      case 'default':
        $config = array(
            'type' => 'text-line',
        );
        break;
      case 'help_text':
      case 'validation_message':
        $config = array(
            'type' => 'text-area',
        );
        break;
      case 'attributes':
      case 'options':
        $config = array(
            'type' => 'text-area',
            'attributes' => array(
                'class' => 'parameter-list'
            ),
        );
        break;
      case 'groupable':
        $config = array(
            'name' => 'row_' . $this->field_def->id . '[group]',
            'type' => 'dropdown',
            'options' => self::group_options(),
            'attributes' => array('id' => 'row_' . $this->field_def->id . '_group'),
        );
        break;
      case 'form_element':
        $config = array(
            'type' => 'dropdown',
            'options' => array_flip( PDb_FormElement::get_types() ) + array('null_select' => false),
            'attributes' => array('class' => $this->column_has_data( $this->field_def->name() ) ? 'column-has-values' : 'column-empty'),
        );
        break;
      case 'validation':
        $config = array(
            'type' => 'dropdown-other',
            'options' => $this->validation_methods(),
        );
        break;
      default:
        error_log( __METHOD__ . ' undefined attribute: ' . $attribute );
        return '';
    }

    $field_def_att = new PDb_Field_Def_Att_Item( $attribute, Participants_Db::array_merge2( array(
                'name' => 'row_' . $this->field_def->id . '[' . $attribute . ']',
                'value' => $this->attribute_value( $attribute ),
                'attributes' => array('id' => 'row_' . $this->field_def->id . '_' . $attribute),
            ),$config ) );

    switch ( true ) {

      case ( $attribute === 'selectable' ):
        $lines = array(
            '<div class="field-header">',
            '<span class="editor-opener dashicons dashicons-arrow-down" ></span>',
            '<span class="editor-opener dashicons dashicons-arrow-right" ></span>',
            $field_def_att->html(),
        );
        break;

      case ( $attribute === 'orderable' ):
        $lines = array(
            $field_def_att->html(),
        );
        break;

      case ( $attribute === 'deletable' ):
        $lines = array(
            $field_def_att->html(),
            '<h4>' . $this->field_def->title() . '</h4>',
            '</div>',
        );
        break;

      case ( $field_def_att->is_checkbox() ):
        $lines = array(
            '<div class="attribute-control ' . $attribute . '-attribute ' . $field_def_att->type() . '-control-wrap">',
            $field_def_att->has_label() ? '<label for="row_' . $this->field_def->id . '_' . $attribute . '">' . $field_def_att->label() : '',
            $field_def_att->html(),
            $field_def_att->has_label() ? '</label>' : '',
            '</div>',
        );
        break;

      default:
        $lines = array(
            $field_def_att->is_hidden() ? '' : '<div class="attribute-control ' . $attribute . '-attribute ' . $field_def_att->type() . '-control-wrap">',
            $field_def_att->html(),
            $field_def_att->has_label() ? '<label for="row_' . $this->field_def->id . '_' . $attribute . '">' . $field_def_att->label() . '</label>' : '',
            $field_def_att->is_hidden() ? '' : '</div>',
        );
    }

    return implode( $lines, PHP_EOL );
  }

  /**
   * provides the value of the field definition attribute
   * 
   * @param string $attribute name of the attribute
   * @param array $config
   * @return string
   */
  private function attribute_value( $attribute )
  {
    switch ( $attribute ) {
      case 'form_element':
      case 'title':
      case 'default':
      case 'name':
      case 'group':
      case 'options':
      case 'attributes':
      case 'help_text':
      case 'validation':
      case 'validation_message':
      case 'sortable':
      case 'csv':
      case 'persistent':
      case 'signup':
      case 'readonly':
        return $this->field_def->get_prop( $attribute );
      case 'groupable':
        return $this->field_def->group();
      case 'orderable':
        return true;
      case 'id':
      case 'selectable':
      case 'deletable':
        return false;
      case 'status':
        return null;
    }
  }

  /**
   * provides the validation methods
   * 
   * this can pnly be called when the current $this->definition_attributes element is the 
   * validation config array
   * 
   * @return array of validation methods
   */
  private function validation_methods()
  {
    $base_methods = array_flip( PDb_FormValidation::validation_methods() ) + array('null_select' => false);
    foreach ( current( $this->definition_attributes ) as $method => $switch ) {
      if ( !$switch ) {
        unset( $base_methods[$method] );
      }
    }
    return $base_methods;
  }

/**
 * provides the group selector options
 * 
 * @return array of options
 */
  public static function group_options()
  {
    $options = array();
    foreach( Participants_Db::get_groups('name,title') as $group_data ) {
      if ( $group_data['name'] === 'internal' ) continue;
      $options[$group_data['title']] = $group_data['name'];
    }
    return $options + array('null_select' => false);
  }

  /**
   * provides the set of default definition attribute switches
   * 
   * @return array
   */
  private function default_def_att_switches()
  {
    return array(
        'id' => true,
        'status' => true,
        'selectable' => true,
        'orderable' => true,
        'deletable' => true,
        'name' => true,
        'title' => true,
        'groupable' => true,
        'form_element' => true,
        'attributes' => true,
        'options' => false,
        'validation' => array(
            'no' => true,
            'yes' => true,
            'email-regex' => true,
            'captcha' => false,
            'other' => true,
        ),
        'validation_message' => true,
        'help_text' => true,
        'default' => true,
        'signup' => true,
        'csv' => true,
        'readonly' => true,
        'sortable' => true,
        'persistent' => true,
    );
  }

  /**
   * provides the configuration array for a form element
   * 
   * this array only provides the attributes that are different from the default
   * 
   * @return array
   */
  private function form_element_atts()
  {
    /**
     * @filter pdb-{$name}_form_element_def_att_switches
     * 
     * @param bool false
     * @return array of specific definition attributes for the form element
     */
    $def = Participants_Db::apply_filters( $this->field_def->form_element() . '_form_element_def_att_switches', false );

    if ( $def !== false ) {
      // if the attributes have been defined in the filter, return with that result
      return $def;
    }

    // set up the built-in form elements
    switch ( $this->field_def->form_element() ) {
      case 'text-line':
      case 'text-area':
      case 'rich-text':
      case 'password':
        $this->colorclass = 'text';
        $def = array();
        break;
      case 'checkbox':
      case 'radio':
      case 'dropdown':
      case 'dropdown-other':
      case 'multi-checkbox':
      case 'multi-dropdown':
      case 'select-other':
      case 'multi-select-other':
        $this->colorclass = 'selector';
        $def = array(
            'options' => true,
            'validation' => array(
                'email-regex' => false,
            ),
        );
        break;
      case 'date':
      case 'numeric':
      case 'decimal':
      case 'currency':
        $this->colorclass = 'numeric';
        $def = array(
            'validation' => array(
                'email-regex' => false,
            ),
        );
        break;
      case 'link':
      case 'image-upload':
      case 'file-upload':
        
        $this->colorclass = 'upload';
        $def = array(
            'validation' => array(
                'email-regex' => false,
            ),
            'sortable' => false,
            'persistent' => false,
        );
        break;
      case 'hidden':
        $def = array(
            'help_text' => false,
            'validation' => false,
            'attributes' => false,
            'validation_message' => false,
        );
        $this->colorclass = 'utility';
        break;
      case 'captcha':
        $this->colorclass = 'captcha';
        $def = array(
            'default' => false,
            'validation' => array(
                'email-regex' => false,
                'captcha' => true,
            ),
        );
        break;
      case 'placeholder':
        
        $this->colorclass = 'utility';
        $def = array(
            'help_text' => false,
            'attributes' => false,
            'validation' => false,
            'validation_message' => false,
            'sortable' => false,
            'csv' => false,
            'persistent' => false,
            'signup' => false,
            'readonly' => false,
        );
        break;
      case 'timestamp':
        
        $this->colorclass = 'numeric';
        $def = array(
            'help_text' => false,
            'attributes' => false,
            'readonly' => false,
            'default' => false,
            'groupable' => false,
            'form_element' => false,
        );
        break;
      default:
        
        $this->colorclass = 'custom';
        $def = array();
    }
    if ( $this->field_def->group() === 'internal' ) {
      $def = array_merge( $def, array(
          'selectable' => false,
          'orderable' => false,
          'deletable' => false,
          'default' => false,
          'groupable' => false,
          'help_text' => false,
          'form_element' => false,
          'attributes' => false,
          'validation' => false,
          'validation_message' => false,
          'signup' => false,
          'readonly' => false,
              )
      );
    }
    return $def;
  }

  /**
   * tells if the field's column has data in the main database
   * 
   * @global wpdb $wpdb
   * @param string $fieldname
   * @return bool true if the colum has data
   */
  private function column_has_data( $fieldname )
  {
    global $wpdb;
    $result = $wpdb->get_col( 'SELECT `' . $fieldname . '` FROM ' . Participants_Db::$participants_table );
    return count( array_filter( $result ) ) > 0;
  }

}

class PDb_Field_Def_Att_Item {

  /**
   * @var array the control form element configuration array
   */
  private $config;

  /**
   * @var string name of the attribute
   */
  private $name;

  /**
   * @var string
   */
  private $label;

  /**
   * sets up the item
   * 
   * @param string $name of the attribute
   * @param array $config
   */
  public function __construct( $name, $config )
  {
    $this->name = $name;
    $this->config = $config;
    $this->label = $this->get_label();
  }

  /**
   * provides the form element attribute control HTML
   * 
   * @return string
   */
  public function html()
  {
    switch ( $this->name ) {
      case 'attributes':
      case 'options':
        $this->config['value'] = Participants_Db::array_to_string_notation( $this->config['value'] );
        break;
      case 'deletable':
        return $this->delete_button();
    }
    return PDb_FormElement::get_element( $this->config );
  }
  
  /**
   * provides the HTML for the field delete button
   * 
   * @return string
   */
  private function delete_button()
  {
    return '<a href="javascript:return false" data-thing-name="delete_' . $this->config['id'] . '" class="delete" data-thing="field"><span class="dashicons dashicons-no"></span></a>';
  }

  /**
   *  provides the attribute label
   * 
   * @return string
   */
  public function label()
  {
    return $this->label;
  }

  /**
   * tells of the attribute needs a label
   * 
   * @return bool true if the attribute should have a label
   */
  public function has_label()
  {
    return $this->label !== '' && !$this->is_hidden();
  }

  /**
   * tells of the attribute is hidden
   * 
   * @return bool
   */
  public function is_hidden()
  {
    return $this->config['type'] === 'hidden';
  }

  /**
   * tells the control type
   * 
   * @return string
   */
  public function type()
  {
    return $this->config['type'];
  }

  /**
   * tells if the attribute control is a checkbox
   * 
   * @return bool
   */
  public function is_checkbox()
  {
    return $this->config['type'] === 'checkbox';
  }

  /**
   *  provides the attribute label
   * 
   * @return string
   */
  private function get_label()
  {
    $titles = PDb_Manage_Fields::get_i18n();

    switch ( $this->name ) {
      case 'default':
        $name = 'default';
        break;
      case 'groupable':
        $name = 'Group';
        break;
      case 'csv':
        $name = 'CSV';
        break;
      case 'readonly':
        $name = 'readonly';
        break;
      default:
        $name = $this->name;
    }
    return isset( $titles[$name] ) ? $titles[$name] : '';
  }

}
