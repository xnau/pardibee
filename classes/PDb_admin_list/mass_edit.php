<?php

/**
 * handles the mass edit functionality
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;

defined( 'ABSPATH' ) || exit;

class mass_edit {

  /**
   * @var string name of the AJAX action
   */
  const action = 'pdb_mass_edit';

  /**
   * @var string name of the edit action
   */
  const edit_action = 'mass_edit';
  
  /**
   * @var string name of the field selector
   */
  const field_selector = 'mass_edit_field';

  /**
   * 
   */
  public function __construct()
  {
    add_action( 'wp_ajax_' . self::action, array( $this, 'serve_field_input_control' ) );

    add_filter( 'pdb-admin_list_with_selected_actions', array( $this, 'add_dropdown_actions' ), 1 );
    add_filter( 'pdb-admin_list_with_selected_action_conf_messages', array( $this, 'set_confirmation_message' ) );

//    add_action( 'pdb_admin_list_with_selected/' . self::edit_action, array( $this, 'update_records' ) );

    add_filter( 'pdb-admin_list_with_selected_control_html', array( $this, 'add_edit_controls' ) );
  }

  /**
   * adds the action to the dropdown
   * 
   * @param array $action_list list of the defined actions
   * @return array
   */
  public function add_dropdown_actions( $action_list )
  {
    $action_list += array( __( 'mass edit', 'participants-database' ) => self::edit_action );
    
    return $action_list;
  }

  /**
   * adds the edit controllers to the with selected control
   * 
   * @param array $html
   * @return array
   */
  public function add_edit_controls( $html )
  {
    return $this->add_selector( $html );
  }
  
  /**
   * provides the selected field input control
   * 
   * @return string html
   */
  public function serve_field_input_control()
  {
    wp_send_json(  array( 'input' => $this->field_input( $this->get_field_name() ) ) );
  }
  
  /**
   * adds the confirmation messages
   * 
   * @param array $messages
   * @return array
   */
  public function set_confirmation_message( $messages )
  {
    $messages[ self::edit_action ] = array(
        'singular' => __( 'Apply the field edit to the selected record?', 'participants-database' ),
        'plural' => __( 'Apply the field edit to the selected records?', 'participants-database' )
        );
    return $messages;
  }

  /**
   * splices in the type selector
   * 
   * @param array $html
   * @return array
   */
  private function add_selector( $html )
  {
    foreach ( $html as $index => $row ) {
      if ( strpos( $row, 'type="submit"' ) !== false ) {
        break;
      }
    }

    array_splice( $html, $index, 0, $this->field_selector() );

    return $html;
  }

  /**
   * supplies the fields selector HTML
   * 
   * @return string html
   */
  private function field_selector()
  {
    $config = array(
        'name' => self::field_selector,
        'type' => 'dropdown',
        'value' => $this->selected_field(),
        'options' => $this->editable_field_list() + array( 'null_select' => false ),
    );
    
    $html = array( '<span class="mass-edit-control" style="display:none">' );
    
    $html[] = '<label>' . __('Field','participants-database') . ':';
    
    $html[] = \PDb_FormElement::get_element( $config );
    
    $html[] = '</label>';
    
    $html[] = '<div class="mass-edit-input">';
    
    $html[] = $this->field_input( $this->selected_field() );
    
    $html[] = '</div>';
    
    $html[] = '</span>';
    
    return implode( PHP_EOL, $html );
  }
  
  /**
   * provides the name of the currently selected field
   * 
   * @return string
   */
  private function selected_field()
  {
    return \PDb_List_Admin::get_admin_user_setting( self::field_selector, $this->get_field_name() );
  }

  /**
   * provides the name of the selected field from the post array
   * 
   * @return string
   */
  private function get_field_name()
  {
    return (string) filter_input( INPUT_POST, self::field_selector, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE );
  }
  
  /**
   * provides the input control for a field
   * 
   * @param string $fieldname
   * @return string HTML the input control for the field
   */
  private function field_input( $fieldname )
  {
    $value = \PDb_List_Admin::get_admin_user_setting( $fieldname, filter_input( INPUT_POST, $fieldname, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE ) );
    
    return field_input::html($fieldname, $value );
  }

  /**
   * provides the list of fields that can be mass-edited
   * 
   * @global \wpdb $wpdb
   * @return array as $title => $value
   */
  private function editable_field_list()
  {
    global $wpdb;

    $sql = "
          SELECT f.name, g.name AS groupname, REPLACE(f.title,'\\\','') AS fieldtitle, REPLACE(g.title,'\\\','') AS grouptitle
          FROM " . \Participants_Db::$fields_table . " f
            JOIN " . \Participants_Db::$groups_table . " g ON f.group = g.name
          WHERE 
            f.form_element IN (" . $this->included_types() . ") AND
            g.mode IN ('public','admin','private') AND 
            f.group NOT IN ('internal') 
            " . $this->internal_fields() . "
          ORDER BY g.order ASC, f.order ASC";
    
    $result = $wpdb->get_results( $sql );
    
    $field_list = $this->recent_fields();
    $group = '';
    
    foreach( $result as $field_data ) {
      
      if ( $field_data->groupname !== $group ) {
        
        $label = $this->filter_title( $field_data->grouptitle );
        $field_list[ $label === '' ? $field_data->groupname : $label ] = 'optgroup';
        $group = $field_data->groupname;
      }
      
      $label = $this->filter_title( $field_data->fieldtitle );
      
      if ( isset( $field_list[ $label ] ) ) {
        $label = $label . ' (' . $field_data->name . ')';
      }
      
      $field_list[ $label === '' ? $field_data->name : $label  ] = $field_data->name;
      
    }

    return \Participants_Db::apply_filters( 'with_selected_mass_edit_fields', $field_list );
  }
  
  /**
   * provides the list of recent fields
   * 
   * @return array
   */
  private function recent_fields()
  {
    return filter::recent_field_option( \PDb_List_Admin::get_admin_user_setting( self::field_selector . '_recents', array() ) );
  }
  
  /**
   * provides the where clause for internal fields
   * 
   * @return string
   */
  private function internal_fields()
  {
    $clause = "";
    
    if ( \Participants_Db::$plugin_options['allow_record_timestamp_edit'] == 1 ) {
      $clause = "OR f.group = 'internal' AND f.form_element = 'timestamp'";
    }
    
    return $clause;
  }
  
  /**
   * provides the title string
   * 
   * @param string $title
   * @return string translated title
   */
  private function filter_title( $title )
  {
    return $title === '' ? '' : \Participants_Db::apply_filters('translate_string', $title );
  }

  /**
   * provides the included types list for the fields query
   * 
   * if a custom field is defined that should be included, it will need to use the filter to add to this list
   * 
   * @return string
   */
  private function included_types()
  {
    $included = \Participants_Db::apply_filters( 'with_selected_mass_edit_included_field_types', array(
                'text-line',
                'text-area',
                'rich-text',
                'checkbox',
                'radio',
                'dropdown',
                'date',
                'numeric',
                'decimal',
                'currency',
                'dropdown-other',
                'multi-checkbox',
                'multi-dropdown',
                'select-other',
                'multi-select-other',
                'hidden',
            ) );
    
    return "'" . implode( "','", $included ) . "'";
  }

}
