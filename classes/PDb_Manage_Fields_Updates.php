<?php

/**
 * handles updating the field and group definitions
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    1.4.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
defined( 'ABSPATH' ) || exit;

class PDb_Manage_Fields_Updates {

  /**
   * @var string action key
   */
  const action_key = 'pdb-manage-fields';

  /**
   * instantiate the class
   * 
   * @return null 
   */
  function __construct()
  {
    add_action( 'wp_ajax_' . self::action_key, array( $this, 'process_ajax_submit' ) );
    add_action( 'admin_post_update_fields', array( $this, 'update_fields' ) );
    add_action( 'admin_post_add_field', array( $this, 'add_field' ) );
    add_action( 'admin_post_add_group', array( $this, 'add_group' ) );
    add_action( 'admin_post_update_groups', array( $this, 'update_groups' ) );
  }

  /**
   * process the field definition update submission
   * 
   * @global wpdb $wpdb
   */
  public function update_fields()
  {
    if ( ! check_admin_referer( self::action_key ) || ! Participants_Db::current_user_has_plugin_role( 'admin', __METHOD__ ) )
    {
      wp_die('unauthorized');
    }
    
    global $wpdb;
    $current_user = wp_get_current_user();
    
    $changed_groups = array();

    self::clear_field_def_cache();

    foreach ( $this->sanitized_field_post() as $name => $row ) {

      // unescape quotes in values
      foreach ( $row as $k => $rowvalue ) {
        if ( !is_array( $rowvalue ) && ! empty( $rowvalue ) ) {
          $row[ $k ] = stripslashes( $rowvalue );
        }
      }

      if ( $row[ 'status' ] === 'changed' ) {

        $id = filter_var( $row[ 'id' ], FILTER_VALIDATE_INT );
        
        
        if ( isset($row['group']) ) {
          $changed_groups[] = $row['group'];
        }
        
        // get an array of all attributes that have array values
        $array_atts = array_intersect_key( $row, array_flip( array( 'values', 'options', 'attributes' ) ) );

        foreach ( array_keys( $array_atts ) as $attname ) {

          /*
           * format the value for attributes that use a values array
           * 
           * also, if the deprecated 'values' attribute is present, place its 
           * data into the correct attribute
           */

          $attvalue = $row[ $attname ];

          // handle depricated "values" parameter
          if ( $attname === 'values' && strlen( $attvalue ) > 0 ) {
            $correct_attribute = PDb_FormElement::is_value_set( $row[ 'form_element' ] ) ? 'options' : 'attributes';
            if ( strlen( $row[ $correct_attribute ] ) === 0 ) {
              $attname = $correct_attribute;
              $row[ 'values' ] = '';
            }
          }

          if ( is_string( $attvalue ) ) {
            $row[ $attname ] = self::string_notation_to_array( $attvalue );
          }

          if ( $attname === 'options' && is_array( $row[ $attname ] ) ) {
            // clean up the values so they are valid
            array_walk( $row[ $attname ], function (&$v) {
              $v = strip_tags( $v );
            } );
          }
        }

        // validate a regex/match validation setting
        if ( !empty( $row[ 'validation' ] ) && !in_array( $row[ 'validation' ], array_keys( PDb_FormValidation::validation_methods() ) ) ) {

          $row['validation'] = str_replace( '\\\\', '\\', $row['validation'] );
          
          // check for a valid regex
          if ( ! PDb_Form_Field_Def::is_field( $row['validation'] ) ) {
            // this could be an incorrect field name or a regex
            if ( ! PDb_FormValidation::is_regex( $row['validation'] ) ) {
                    Participants_Db::set_admin_message( sprintf(
                      __( 'The validation setting for the "%s" field is not a valid regex or field name.', 'participants-database' ), Participants_Db::apply_filters('translate_string', $row['title'])
              ), 'error' );
                    
              $this->return_to_the_manage_database_fields_page();
              
            }
          }
        }

        // remove empty values
        // prevents these attributes from getting cleared
        foreach ( array( 'group', 'form_element', 'validation' ) as $att )
        {  
          if ( array_key_exists( $att, $row ) && empty( $row[ $att ] ) ) {
            unset( $row[ $att ] );
          }
        }

        /*
         * modify the datatype if necessary
         * 
         * the 'datatype_warning' needs to be accepted for this to take place
         */
        if ( isset( $row[ 'group' ] ) && $row[ 'group' ] != 'internal' ) {
          
          $new_type = $this->new_datatype( $row[ 'name' ], $row[ 'form_element' ] );
          
          if ( $new_type ) {
                  
            if ( !isset( $row[ 'datatype_warning' ] ) || ( isset( $row[ 'datatype_warning' ] ) && $row[ 'datatype_warning' ] === 'accepted' ) ) {

              $wpdb->query( "ALTER TABLE " . Participants_Db::participants_table() . " MODIFY COLUMN `" . esc_sql( $row[ 'name' ] ) . "` " . $new_type );
            } else {
              unset( $row[ 'form_element' ] ); // prevent this from getting changed
            }
          }
        }
        unset( $row[ 'datatype_warning' ] );

        /*
         * add some form-element-specific processing
         */
        if ( isset( $row[ 'form_element' ] ) ) {
          
          switch ( $row[ 'form_element' ] )
          {
            case 'captcha':
              foreach ( array( 'title', 'help_text', 'default' ) as $field )
              {
                if ( isset( $row[ $field ] ) )
                {
                  $row[ $field ] = stripslashes( $row[ $field ] );
                }
              }
              $row[ 'validation' ] = 'captcha';
              foreach ( array( 'display_column', 'admin_column', 'CSV', 'persistent', 'sortable' ) as $c )
              {
                $row[ $c ] = 0;
              }
              $row[ 'readonly' ] = 1;
              break;
              
            case 'decimal':
              if ( !isset( $row[ 'attributes' ][ 'step' ] ) ) {
                $row[ 'attributes' ][ 'step' ] = 'any';
              }
              break;
          }
        }

        // produce a status array for the pdb-update_field_def filter
        $status = array_intersect_key( $row, array_flip( array( 'id', 'status', 'name' ) ) );

        // remove the fields we won't be updating
        unset( $row[ 'status' ], $row[ 'id' ], $row[ 'name' ], $row[ 'selectable' ] );

        // serialize all array values
        foreach ( $row as $name => $row_item ) {
          if ( is_array( $row_item ) ) {
            $row[ $name ] = serialize( $row[ $name ] );
          }
        }

        /**
         * provides access to the field definition parameters as the field is getting updated
         * 
         * @filter pdb-update_field_def
         * @param array of field definition parameters
         * @param array of non-saved status values for the field: id, status, name 
         * @return array
         */
        $result = $wpdb->update( Participants_Db::$fields_table, Participants_Db::apply_filters( 'update_field_def', $row, $status ), array( 'id' => $id ) );
        
        Participants_Db::debug_log( __METHOD__ . ' update fields: ' . $wpdb->last_query );

        if ( $result !== false ) {
          
          $with_selected_action = filter_input( INPUT_POST, 'with_selected', FILTER_DEFAULT, Participants_Db::string_sanitize( FILTER_NULL_ON_FAILURE ) );
          
          if ( $with_selected_action ) {
            PDb_List_Admin::set_user_setting( 'with_selected_selection', $with_selected_action, 'manage_fields' . $current_user->ID );
          }
          
          if ( $result > 0 ) {
            Participants_Db::set_admin_message( __( 'Fields Updated', 'participants-database' ), 'success' );
            /**
             * @action pdb-field_defs_updated
             * @param string action
             * @param string last query
             */
            do_action( Participants_Db::$prefix . 'field_defs_updated', 'update_fields', $wpdb->last_query );
          }
        } else {
          
          $error_message = empty( $wpdb->last_error ) ? 'Data size too large, cannot save.' : $wpdb->last_error;
          
          Participants_Db::set_admin_message( __( 'Field update error:', 'participants-database' ) . '<br/>' . $error_message, 'error' );
          Participants_Db::debug_log( __METHOD__ . ' field update error: ' . $error_message );
        }
      }
    }
    
    if ( ! empty( $changed_groups ) )
    {
      foreach( array_unique($changed_groups) as $groupname ) {
        self::rebuild_group_field_order($groupname);
      }
    }
    
    $this->return_to_the_manage_database_fields_page();
  }

  /**
   * adds a new field
   * 
   * @global wpdb $wpdb
   */
  public function add_field()
  {
    if ( ! check_admin_referer( self::action_key ) || ! Participants_Db::current_user_has_plugin_role( 'admin', __METHOD__ ) )
    {
      wp_die('unauthorized');
    }
    
    // set up the new field's parameters
    $new_field = array(
        'name' => filter_input( INPUT_POST, 'title', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::make_name' ) ),
        'title' => filter_input( INPUT_POST, 'title', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::sanitize_text' ) ),
        'group' => filter_input( INPUT_POST, 'group', FILTER_DEFAULT, Participants_Db::string_sanitize() ),
        'order' => filter_input( INPUT_POST, 'order', FILTER_SANITIZE_NUMBER_INT ),
        'validation' => 'no',
        'form_element' => filter_input( INPUT_POST, 'form_element', FILTER_DEFAULT, Participants_Db::string_sanitize() ),
    );
    
    /**
     * @filter pdb-new_field_params
     * @param array of the submitted new field values
     * @return array
     */
    $params = Participants_Db::apply_filters('new_field_params',  $new_field );

    if ( empty( $params[ 'name' ] ) ) {
      $this->return_to_the_manage_database_fields_page(); // ignore empty field name
    }

    // if they're trying to use a reserved name, stop them
    if ( in_array( $params[ 'name' ], Participants_Db::$reserved_names ) ) {

      Participants_Db::set_admin_message( sprintf(
                      '<strong>%s</strong> %s: %s', __( 'Cannot add a field with that name', 'participants-database' ), __( 'This name is reserved; please choose another. Reserved names are', 'participants-database' ), implode( ', ', Participants_Db::$reserved_names )
              ), 'error' );
      $this->return_to_the_manage_database_fields_page();
    }

    // if they're trying to use the same name as one that already exists
    if ( array_key_exists( $params[ 'name' ], Participants_Db::$fields ) ) {

      Participants_Db::set_admin_message( sprintf(
                      '<strong>%s</strong> %s', __( 'Cannot add a field with that name', 'participants-database' ), __( 'The name must be unique: a field with that name has been previously defined.', 'participants-database' )
              ), 'error' );
      $this->return_to_the_manage_database_fields_page();
    }

    // prevent name from beginning with a number
    if ( preg_match( '/^\d/', $params[ 'name' ] ) === 1 ) {

      Participants_Db::set_admin_message( sprintf(
                      '<strong>%s</strong> %s', __( 'The name cannot begin with a number', 'participants-database' ), __( 'Please choose another.', 'participants-database' )
              ), 'error' );
      $this->return_to_the_manage_database_fields_page();
    }

    $result = Participants_Db::add_blank_field( $params );

    self::clear_field_def_cache();

    if ( $result ) {
      Participants_Db::set_admin_message( __( 'The new field was added.', 'participants-database' ), 'update' );
      if ( PDb_FormElement::is_value_set( $params[ 'form_element' ] ) ) {
        Participants_Db::set_admin_message( __( 'Remember to define the "options" for your new field.', 'participants-database' ), 'update' );
      }
    } else {
      Participants_Db::set_admin_message( __( 'The field could not be added.', 'participants-database' ), 'error' );
    }

    $this->return_to_the_manage_database_fields_page();
  }

  /**
   * adds a new group
   * 
   * @global wpdb $wpdb
   */
  public function add_group()
  {
    global $wpdb;
    $atts = array(
        'name' => filter_input( INPUT_POST, 'group_title', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::make_name' ) ),
        'title' => filter_input( INPUT_POST, 'group_title', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::sanitize_text' ) ),
        'order' => $_POST[ 'group_order' ],
        'mode' => 'public',
        'description' => '',
    );

    // if the name already exists, add a numeral to make it unique
    $extant_groups = $wpdb->get_col( 'SELECT `name` FROM ' . Participants_Db::$groups_table );
    $i = 1;
    $stem = $atts[ 'name' ];
    while ( in_array( $atts[ 'name' ], $extant_groups ) ) {
      $atts[ 'name' ] = $stem . '-' . $i++;
    }

    $result = $wpdb->insert( Participants_Db::$groups_table, $atts );

    if ( $result ) {
      Participants_Db::set_admin_message( __( 'The new group was added.', 'participants-database' ), 'update' );
    }

    self::clear_field_def_cache();

    $this->return_to_the_manage_database_fields_page();
  }

  /**
   * processes the groups update submission
   * 
   * @global wpdb $wpdb
   */
  public function update_groups()
  {
    if ( !array_key_exists( '_wpnonce', $_POST ) || !wp_verify_nonce( $_POST[ '_wpnonce' ], self::action_key ) ) {
      return;
    }

    self::clear_field_def_cache();

    global $wpdb;

    $result = false;
    $data = array();

    foreach ( $this->sanitized_group_post() as $group_name => $row ) {

      $data[ 'title' ] = stripslashes( $row[ 'title' ] );
      $data[ 'description' ] = stripslashes( $row[ 'description' ] );
      $data[ 'mode' ] = $row[ 'mode' ];
      $data[ 'display' ] = $data[ 'mode' ] === 'public' ? '1' : '0';
      $data[ 'admin' ] = $data[ 'mode' ] === 'admin' ? '1' : '0';

      $result = $wpdb->update( Participants_Db::$groups_table, $data, array( 'name' => stripslashes( $group_name ) ) );
    }

    if ( $result === false ) {
      if ( $wpdb->last_error ) {
        Participants_Db::set_admin_message( $this->parse_db_error( $wpdb->last_error, $action ), 'error' );
      } elseif ( ! Participants_Db::has_admin_message ( ) ) {
        Participants_Db::set_admin_message( __( 'There was an error; the settings were not saved.', 'participants-database' ), 'error' );
      }
    } elseif ( $result ) {
      /**
       * @action pdb-field_defs_updated
       * @param string action
       * @param string last query
       */
      do_action( Participants_Db::$prefix . 'field_defs_updated', 'update_groups', $wpdb->last_query );
      PDb_Admin_Notices::post_success( __( 'Your groups have been updated', 'participants-database' ) );
    }

    $this->return_to_the_manage_database_fields_page();
  }

  /**
   * processes the ajax submission
   * 
   * @global wpdb $wpdb
   */
  public function process_ajax_submit()
  {
    if ( !array_key_exists( '_wpnonce', $_POST ) || !wp_verify_nonce( $_POST[ '_wpnonce' ], self::action_key ) ) {
      wp_send_json( 'failed' );
    }

    self::clear_field_def_cache();

    global $wpdb;
    $current_user = wp_get_current_user();

    switch ( filter_input( INPUT_POST, 'task', FILTER_DEFAULT, Participants_Db::string_sanitize() ) ) {

      case 'delete_field':

        $list = $this->sanitize_id_list();

        if ( count( $list ) < 1 ) {
          wp_send_json( 'error:no valid id list' );
        }

        /**
         * @action pdb-fields_deleted
         * @param array of field defs that are about to be deleted
         */
        $deleted_fields = array();
        foreach ( Participants_Db::$fields as $field_def ) {
          if ( in_array( $field_def->get_prop( 'id' ), $list ) ) {
            $deleted_fields[] = $field_def;
          }
        }
        do_action( Participants_Db::$prefix . 'fields_deleted', $deleted_fields );

        $result = $wpdb->query( '
      DELETE FROM ' . Participants_Db::$fields_table . '
      WHERE id IN ("' . implode( '","', $list ) . '")'
        );

        if ( $result ) {
          foreach( $deleted_fields as $field ) {
            $this->maybe_delete_db_column($field);
          }
          wp_send_json( array( 'status' => 'success', 'feedback' => $this->dismissable_message( __( 'Selected fields deleted', 'participants-database' ) ) ) );
        } else {
          if ( PDB_DEBUG ) {
            Participants_Db::debug_log( __METHOD__ . ' could not delete field: ' . $wpdb->last_error );
          }
          wp_send_json( array( 'status' => 'failure', 'feedback' => $this->dismissable_message( 'error: could not delete field', 'delete_field_failure', 'error' ) ) );
        }

      case 'delete_group':

        $group = current( $_POST[ 'list' ] );

        $group_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Participants_Db::$fields_table . ' WHERE `group` = "%s"', $group ) );

        $result = false;

        if ( $group_count == 0 )
          $result = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . Participants_Db::$groups_table . ' WHERE `name` = "%s"', $group ) );

        if ( $result ) {
          wp_send_json( array( 'status' => 'success', 'feedback' => $this->dismissable_message( __( 'Selected group deleted', 'participants-database' ) ) ) );
        } else {
          if ( PDB_DEBUG ) {
            Participants_Db::debug_log( __METHOD__ . ' could not delete group: ' . $wpdb->last_error );
          }
          wp_send_json( array( 'status' => 'failure', 'feedback' => $this->dismissable_message( 'error: could not delete group', 'delete_group_failure', 'error' ) ) );
        }

      case 'update_param':

        $list = $this->sanitize_id_list();

        if ( count( $list ) < 1 ) {
          wp_send_json( 'error:no valid id list' );
        }

        $param = filter_input( INPUT_POST, 'param', FILTER_DEFAULT, Participants_Db::string_sanitize() );
        $setting = filter_input( INPUT_POST, 'setting', FILTER_DEFAULT, Participants_Db::string_sanitize() ) === 'true' ? 1 : 0;

        $result = $wpdb->query( '
      UPDATE ' . Participants_Db::$fields_table . '
      SET `' . $param . '` = "' . $setting . '" 
      WHERE id IN ("' . implode( '","', $list ) . '")'
        );

        PDb_List_Admin::set_user_setting( 'with_selected_selection', filter_input( INPUT_POST, 'with_selected', FILTER_DEFAULT, Participants_Db::string_sanitize() ), 'manage_fields' . $current_user->ID );

        wp_send_json( array( 'status' => 'success', 'feedback' => $this->dismissable_message( __( 'Field settings updated.', 'participants-database' ) ) ) );

      case 'reorder_fields':

        parse_str( filter_input( INPUT_POST, 'list', FILTER_DEFAULT, Participants_Db::string_sanitize() ), $list );
        $update = array();
        foreach ( $list as $key => $value ) {
          $update[] = 'WHEN `id` = "' . filter_var( str_replace( 'row_', '', $key ), FILTER_SANITIZE_NUMBER_INT ) . '" THEN "' . filter_var( $value, FILTER_SANITIZE_NUMBER_INT ) . '"';
        }
        $result = $wpdb->query( 'UPDATE ' . Participants_Db::$fields_table . ' SET `order` = CASE ' . implode( " \r", $update ) . ' END WHERE `id` IN ("' . implode( '","', array_keys( $list ) ) . '")' );

        wp_send_json( array( 'status' => $result !== false ? 'success' : 'failed' ) );

      case 'reorder_groups':
        parse_str( filter_input( INPUT_POST, 'list', FILTER_DEFAULT, Participants_Db::string_sanitize() ), $list );
        $update = array();
        foreach ( $list as $key => $value ) {
          $update[] = 'WHEN `id` = "' . filter_var( str_replace( 'order_', '', $key ), FILTER_DEFAULT, Participants_Db::string_sanitize() ) . '" THEN "' . filter_var( $value, FILTER_SANITIZE_NUMBER_INT ) . '"';
        }
        $result = $wpdb->query( 'UPDATE ' . Participants_Db::$groups_table . ' SET `order` = CASE ' . implode( " \r", $update ) . ' END WHERE `id` IN ("' . implode( '","', array_keys( $list ) ) . '")' );

        wp_send_json( array( 'status' => $result ? 'success' : 'failed' ) );

      case 'open_close_editor':
        $fieldid = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
        switch ( filter_input( INPUT_POST, 'state', FILTER_DEFAULT, Participants_Db::string_sanitize() ) ) {
          case 'open':
            Participants_Db::$session->update( self::action_key, array( 'editoropen' => array( $fieldid => true ) ) );
            break;
          case 'close':
            Participants_Db::$session->update( self::action_key, array( 'editoropen' => array( $fieldid => false ) ) );
        }
        wp_send_json( 'set' );

      case 'open_close_all':

        $list = $this->sanitize_id_list();

        if ( count( $list ) < 1 ) {
          wp_send_json( 'error:no valid id list' );
        }

        foreach ( $list as $id ) {
          if ( filter_input( INPUT_POST, 'state', FILTER_DEFAULT, Participants_Db::string_sanitize() ) === 'open' ) {
            Participants_Db::$session->update( self::action_key, array( 'editoropen' => array( $id => true ) ) );
          } else {
            Participants_Db::$session->update( self::action_key, array( 'editoropen' => array( $id => false ) ) );
          }
        }
        wp_send_json( 'set' );
        
      case 'update_editor':
        
        $fieldname = filter_input( INPUT_POST, 'field', FILTER_DEFAULT, Participants_Db::string_sanitize() );
        $new_type = filter_input( INPUT_POST, 'type', FILTER_DEFAULT, Participants_Db::string_sanitize() );
        
        // update the field def with the new type
        global $wpdb;
        $wpdb->update( Participants_Db::$fields_table, array( 'form_element' => $new_type ), array('name' => $fieldname ) );
        
        $editor_html = PDb_Manage_Fields::field_editor_html( $fieldname );
        
        wp_send_json( array( 'body' => $editor_html, 'status' => empty( $editor_html ) ? 'no "' . $fieldname . '" field found' : 'success' ) );

      default:
        /**
         * @action pdb-with_selected_field_edit_action
         * @param string name of the task
         * @param array of selected ids
         */
        do_action( Participants_Db::$prefix . 'with_selected_field_edit_action', filter_input( INPUT_POST, 'task', FILTER_DEFAULT, Participants_Db::string_sanitize() ), $this->sanitize_id_list() );
    }
  }
  
  /**
   * deletes the field's db column if empty
   * 
   * @global \wpdb $wpdb
   * @param \PDb_Form_Field_Def $field
   * @return bool true if the column was deleted
   */
  private function maybe_delete_db_column( $field )
  {
    $result = false;
    
    if ( $this->yes_delete_db_column( $field ) ) {
      
      global $wpdb;
      
      $sql = 'ALTER TABLE ' . Participants_Db::participants_table() . ' DROP COLUMN `' . $field->name() . '`';
      
      $result = $wpdb->query( $sql );
    }
    
    return $result;
  }
  
  /**
   * rebuilds the field order of a group
   * 
   * the numbering for the fields of each group start with group->id * 1000
   * 
   * @global wpdb $wpdb
   * @param string $groupname
   */
  public static function rebuild_group_field_order( $groupname )
  {
    global $wpdb;
    
    $sql = 'SELECT f.id,f.order,g.id AS groupid FROM ' . Participants_Db::$fields_table . ' f INNER JOIN ' . Participants_Db::$groups_table . ' g ON f.group = g.name  WHERE f.group = "' . esc_sql($groupname) . '" ORDER BY f.order ASC';
    
    $field_list = $wpdb->get_results($sql);
    
    if ( !is_array( $field_list ) ) {
      Participants_Db::debug_log(__METHOD__.': empty response for query: ' . $wpdb->last_query );
      return;
    }
    
    $update = array();
    $field_id_list = array();
    $start_value = 1000 * reset( $field_list )->groupid;
    
    foreach ( $field_list as $item ) {
      $update[] = 'WHEN `id` = "' . $item->id . '" THEN "' . $start_value . '"';
      $field_id_list[] = $item->id;
      $start_value++;
    }
    
    $wpdb->query( 'UPDATE ' . Participants_Db::$fields_table . ' SET `order` = CASE ' . implode( " \r", $update ) . ' END WHERE `id` IN ("' . implode( '","', $field_id_list ) . '")' );
  }
  
  /**
   * tells if the db column should be deleted
   * 
   * @param \PDb_Form_Field_Def $field
   * @return bool
   */
  private function yes_delete_db_column( $field )
  {
    /**
     * @filter pdb-delete_column_on_field_delete
     * @param \PDb_Form_Field_Def $field
     * @return bool
     */
    return Participants_Db::apply_filters( 'delete_column_on_field_delete', ! $field->has_stored_data(), $field );
  }

  /**
   * clears the field def cache
   */
  public static function clear_field_def_cache()
  {
    wp_cache_delete( PDb_Form_Field_Def::def_cache );
  }

  /**
   * provides a sanitized post array
   * 
   * only the row data is included, all other elements are discarded
   * 
   * @return array
   */
  private function sanitized_field_post()
  {
    $postrows = array_filter( $_POST, function ($k) {
      return preg_match( '/^row_[0-9]{1,4}$/', $k ) === 1;
    }, ARRAY_FILTER_USE_KEY );

    $post = array();
    foreach ( $postrows as $key => $row ) {
      $post[ $key ] = self::sanitize_field_row( $row );
    }

    return $post;
  }

  /**
   * sanitizes a field definition row
   * 
   * @param array $row of field parameter data
   * @return array
   */
  private static function sanitize_field_row( $row )
  {
    $filters = array(
        'id' => FILTER_SANITIZE_NUMBER_INT,
        'status' => self::string_sanitize(),
        'name' => self::string_sanitize(),
        'title' => self::text_sanitize(),
        'group' => self::string_sanitize(),
        'form_element' => self::string_sanitize(),
        'help_text' => self::html_sanitize(),
        'options' => self::text_sanitize(),
        'validation' => self::string_sanitize(),
        'validation_message' => self::text_sanitize(),
        'default' => self::html_sanitize(),
        'attributes' => self::string_sanitize(),
        'signup' => self::bool_sanitize(),
        'csv' => self::bool_sanitize(),
        'readonly' => FILTER_SANITIZE_NUMBER_INT,
        'sortable' => self::bool_sanitize(),
        'persistent' => self::bool_sanitize(),
    );

    /**
     * @see https://www.php.net/manual/en/filter.filters.php
     * @filter pdb-field_update_sanitize_filters
     * @param array of php filters
     * @return array
     */
    return filter_var_array( $row, Participants_Db::apply_filters( 'field_update_sanitize_filters', $filters ) );
  }

  /**
   * provides a sanitized post array
   * 
   * only the row data is included, all other elements are discarded
   * 
   * @return array
   */
  private function sanitized_group_post()
  {
    $postrows = array_filter( $_POST, function ($v) {
      return is_array( $v );
    } );

    $post = array();
    foreach ( $postrows as $key => $row ) {
      $post[ $key ] = self::sanitize_group_row( $row );
    }

    return $post;
  }

  /**
   * sanitizes a group definition row
   * 
   * @param array $row of field parameter data
   * @return array
   */
  private static function sanitize_group_row( $row )
  {
    $filters = array(
        'id' => FILTER_SANITIZE_NUMBER_INT,
        'status' => self::string_sanitize(),
        'name' => self::string_sanitize(),
        'title' => self::text_sanitize(),
        'description' => self::text_sanitize(),
        'mode' => self::string_sanitize(),
    );

    /**
     * @see https://www.php.net/manual/en/filter.filters.php
     * @filter pdb-group_update_sanitize_filters
     * @param array of php filters
     * @return array
     */
    return filter_var_array( $row, Participants_Db::apply_filters( 'group_update_sanitize_filters', $filters ) );
  }

  /**
   * provides the sanitize filter config for a string
   * 
   * @return array
   */
  protected static function string_sanitize()
  {
    return array(
        'filter' => FILTER_DEFAULT,
        'flags' => FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW,
    );
  }
  
  /**
   * sanitizes a string with some HTML
   * 
   * @return array
   */
  protected static function html_sanitize()
  {
    return self::text_sanitize(true);
  }

  /**
   * provides a text sanitizing filter config
   * 
   * @param bool $expanded if true allows an expanded set of tags in the value
   * @return array
   */
  protected static function text_sanitize( $expanded = false )
  {
    if ( $expanded ) {
      return array(
          'filter' => FILTER_CALLBACK,
          'options' => 'PDb_Manage_Fields_Updates::sanitize_html'
      );
    }
    return array(
        'filter' => FILTER_CALLBACK,
        'options' => 'PDb_Manage_Fields_Updates::sanitize_text'
    );
  }

  /**
   * provides a boolean sanitize filter config
   * 
   * @return array
   */
  protected static function bool_sanitize()
  {
    return array(
        'filter' => FILTER_CALLBACK,
        'options' => function ($v) {
          return $v == '1' ? '1' : '0';
        },
    );
  }

  /**
   * redirects back to the manage database fields page after processing the submission
   * 
   * @link https://tommcfarlin.com/wordpress-admin-redirect/
   */
  private function return_to_the_manage_database_fields_page()
  {
    if ( !isset( $_POST[ '_wp_http_referer' ] ) ) {
      $_POST[ '_wp_http_referer' ] = wp_login_url();
    }

    $url = sanitize_text_field(
            wp_unslash( $_POST[ '_wp_http_referer' ] )
    );

    wp_safe_redirect( urldecode( $this->clean_url( $url ) ) );

    exit;
  }

  /**
   * clears out all url queries except page
   * 
   * @param string $url
   * @return string url
   */
  private function clean_url( $url )
  {
    $baseurl = strtok( $url, '?' );
    $url_components = parse_url( $url );
    parse_str( $url_components[ 'query' ], $query );
    return $baseurl . '?' . http_build_query( array( 'page' => $query[ 'page' ] ) );
  }

  /**
   * prepares a serialized array for display
   * 
   * displays an array as a series of comma-separated strings
   * 
   * @param string|array $array of field options or attributes
   * @return string the prepared string
   */
  public static function array_to_string_notation( $array )
  {
    $value_list = Participants_Db::unserialize_array( $array, false );

    if ( !is_array( $value_list ) ) {
      return $value_list;
    }

    /*
     * here, we create a string representation of an associative array, using 
     * :: to denote a name=>value pair but only if the name and the value are different
     */
    $temp = array();
    foreach ( $value_list as $key => $value ) {
      $key = trim( $key ); // remove the space hack for the field setting display
      $temp[] = $key === $value ? self::encode_delimiter( $value ) : self::encode_delimiter( $key ) . self::option_pair_delimiter() . self::encode_delimiter( $value );
    }
    $value_list = $temp;

    return implode( self::option_delimiter() . ' ', $value_list );
  }

  /**
   * substitutes the option delimiter with the html entity
   * 
   * @param string $input
   * @param string string with the delimiter converted to an entity
   */
  private static function encode_delimiter( $input )
  {
    $delimiter = self::option_delimiter();
    return str_replace( $delimiter, '&#' . ord( $delimiter ) . ';', $input );
  }

  /**
   * breaks the submitted comma-separated string of values into elements for use in 
   * select/radio/checkbox type form elements
   * 
   * if the substrings contain a '::' we split that, with the first substring being 
   * the key (title) and the second the value
   * 
   * there is no syntax checking...if there is no key string before the ::, the element 
   * will have an empty key, but it will be obvious to the user
   * 
   * @param string $values in the field settings notation
   * @return array as $title => $value
   */
  public static function string_notation_to_array( $values )
  {
    $pair_delim = self::option_pair_delimiter();

    $has_labels = strpos( $values, $pair_delim ) !== false;
    $values_array = array();
    $term_list = explode( self::option_delimiter(), $values );

    if ( $has_labels ) {

      foreach ( $term_list as $term ) {
        if ( strpos( $term, $pair_delim ) !== false && strpos( $term, $pair_delim ) !== 0 ) {
          list($key, $value) = explode( $pair_delim, $term );
          /*
           * @version 1.6
           * this is to allow for an optgroup label that is the same as a value 
           * label with an admittedly funky hack: adding a space to the end of the 
           * key for the optgroup label. This space is not seen by the user, it's 
           * for internal use only.
           */
          $array_key = $value === 'optgroup' ? trim( $key ) . ' ' : trim( $key );

          if ( strpos( $array_key, 'null_select' ) !== false ) {
            // make sure we are using the correct null_select key
            $array_key = PDb_FormElement::null_select_key();
          }

          $strip_slashes = true;

          if ( $array_key === 'pattern' ) {
            // slashes are allowed in regex patterns #2116
            $strip_slashes = false;
          }

          $values_array[ self::prep_value( $array_key ) ] = self::prep_value( filter_var( $value, FILTER_DEFAULT, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_ENCODE_LOW ), $strip_slashes );
        } else {
          // strip out the double colon in case it is present
          $term = str_replace( array( $pair_delim ), '', $term );
          $values_array[ self::prep_value( $term ) ] = self::prep_value( $term );
        }
      }
    } else {
      foreach ( $term_list as $term ) {
        $attribute = self::prep_value( $term );
        $values_array[ $attribute ] = $attribute;
      }
    }

    return PDb_Base::cleanup_array( $values_array );
  }

  /**
   * prepares a string for storage in the database
   * 
   * @param string $value
   * @param bool $strip_slashes if true, strip slashes from the value
   * @return string
   */
  private static function prep_value( $value, $strip_slashes = true )
  {
    if ( $strip_slashes ) {
      $value = stripslashes( $value );
    }

    // convert the html entity for the delimiter to its literal
    return trim( html_entity_decode( $value ) );
  }

  /**
   * provides the option delimiter for a string representation of an array
   * 
   * @return string delimiter character
   */
  public static function option_delimiter()
  {
    /**
     * @filter pdb-field_options_option_delim
     * @param string default character to use
     * @return string
     */
    return Participants_Db::apply_filters( 'field_options_option_delim', ',' );
  }

  /**
   * provides the value pair delimiter for a string representation of an array
   * 
   * @return string delimiter character
   */
  public static function option_pair_delimiter()
  {
    /**
     * @filter pdb-field_options_pair_delim
     * @param string default character to use
     * @return string
     */
    return Participants_Db::apply_filters( 'field_options_pair_delim', '::' );
  }

  /**
   * 
   * makes a legal database column name
   * 
   * @param string the proposed name
   * @retun string the legal name
   */
  public static function make_name( $string )
  {
    /*
     * truncate to 64 characters, then replace any characters that would cause problems 
     * in queries
     */
    $name = strtolower( str_replace(
                    array( ' ', '-', '/', "'", '"', '\\', '#', '.', '$', '&', '%', '>', '<', '`' ), array( '_', '_', '_', '', '', '', '', '', '', 'and', 'pct', '', '', '' ), stripslashes( substr( $string, 0, 64 ) )
            ) );
    /*
     * allow only proper unicode letters, numerals and legal symbols
     */
    if ( function_exists( 'mb_check_encoding' ) ) {
      $name = mb_check_encoding( $name, 'UTF-8' ) ? $name : mb_convert_encoding( $name, 'UTF-8' );
    }
    return preg_replace( '#[^\p{L}\p{N}_]#u', '', $name );
  }

  /**
   * sanitizes a string that allows simple HTML tags
   * 
   * this includes titles, group titles, help text
   * 
   * @param string $string
   * @return string
   */
  public static function sanitize_text( $string )
  {
    return strlen( trim( strval( $string ) ) ) === 0 ? '' : wp_kses( $string, self::allowed_text_html() );
   ///return wp_kses( PDb_List_Query::straighten_quotes( $string ), self::allowed_text_html() );
  }

  /**
   * sanitizes a string that allows an expanded range of HTML tags
   * 
   * @param string $string
   * @return string
   */
  public static function sanitize_html( $string )
  {
    $allowed = self::allowed_text_html();
    
    // add the additional tags
    foreach ( array( 'h1', 'h2', 'h3', 'h4', 'hr' ) as $tag ) {
      $allowed[$tag] = self::default_tag_attributes();
    }
    
    return wp_kses( PDb_List_Query::straighten_quotes( $string ), $allowed );
  }
  
  /**
   * provides the set of basic allowed HTML tags
   * 
   * @return array
   */
  private static function allowed_text_html()
  {
    $def_atts = self::default_tag_attributes();
    
    $allowed_html = array(
        'span' => $def_atts,
        'em' => $def_atts,
        'strong' => $def_atts,
        'a' => array(
            'class' => true,
            'style' => true,
            'href' => true,
            'rel' => true,
            'target' => true,
            'title' => true,
        ),
        'img' => array(
            'class' => true,
            'style' => true,
            'src' => true,
            'alt' => true,
        ),
        'br' => $def_atts,
        'b' => $def_atts,
        'i' => $def_atts,
        'ul' => $def_atts,
        'li' => $def_atts,
        'abbr' => $def_atts,
        'address' => $def_atts,
    );
    
    return Participants_Db::apply_filters( 'field_def_allowed_tags', $allowed_html );
  }
  
  /**
   * provides the default set of allowed attributes
   * 
   * @return array
   */
  private static function default_tag_attributes()
  {
    return array(
        'class' => true,
        'style' => true,
    );
  }

  /**
   * sanitizes a list of ids
   * 
   * @param string $name of the $_POST element
   * @return array
   */
  private function sanitize_id_list( $name = 'list' )
  {
    return filter_var_array( $_POST[ $name ], FILTER_SANITIZE_NUMBER_INT );
  }

  /**
   * checks for the need to change the field datatype
   * 
   * @global wpdb $wpdb
   * @param string  $fieldname
   * @param string $form_element the field form element
   * @return string|bool false if no change, new datatype if changed
   */
  protected function new_datatype( $fieldname, $form_element )
  {
    global $wpdb;
    $sql = "SHOW FIELDS FROM " . Participants_Db::participants_table() . ' WHERE `field` = "%s"';
    $field_info = $wpdb->get_results( $wpdb->prepare( $sql, $fieldname ) );
    $new_type = PDb_FormElement::get_datatype( array( 'name' => $fieldname, 'form_element' => $form_element ) );
    $current_type = is_object( current( $field_info ) ) ? current( $field_info )->Type : false;
    
    /**
     * provides a way to control which datatype if used for a changed field form element 
     * 
     * @filter pdb-new_field_form_element_datatype
     * @param string the proposed datatype
     * @param string the new form element
     * @param string name of the field
     * @return the datatype to use
     */
    $new_type = Participants_Db::apply_filters( 'new_field_form_element_datatype', $new_type, $form_element, $fieldname );
    return $this->datatype_has_changed( $current_type, $new_type ) ? $new_type : false;
  }

  /**
   * compares two field datatypes and tells if the are different
   * 
   * doesn't compare the value in parentheses so that decimals and other datatypes 
   * can be customized in the database table
   * 
   * @param string  $current_type
   * @param string  $default_type
   * 
   * @return bool true if the two types are different
   */
  private function datatype_has_changed( $current_type, $default_type )
  {
    $replace_pattern = '/^(.*)\(.+\)$/';
    // strip out the parenthesized part
    return preg_replace( $replace_pattern, '\1', $current_type ) !== preg_replace( $replace_pattern, '\1', $default_type );
  }
  
  /**
   * repairs an issue with internal fields losing their group assign
   */
  public static function repair_internal_fields()
  {
    Participants_Db::debug_log(__METHOD__);
    if ( $repairs = self::field_needs_repair() )
    {
      if ( count( $repairs ) ) {
        self::repair_fields( $repairs );
      }
    }
  }
  
  /**
   * checks for a corrupted field
   * 
   * @global \wpdb $wpdb
   * @return array of fields that need repair
   */
  private static function field_needs_repair()
  {
    global $wpdb;
    
    $internal_fields = array('id','private_id','date_updated','last_accessed','date_recorded','last_update_user');
    
    $sql = 'SELECT f.id,f.name,f.group FROM ' . \Participants_Db::$fields_table . ' f WHERE f.name IN ("' . implode( '","', $internal_fields ) . '") AND ( f.group IS NULL OR f.group <> "internal" )';
    
    $result = $wpdb->get_results( $sql );
    
    return $result;
  }
  
  /**
   * repairs the field
   * 
   * @global \wpdb $wpdb
   * @param array $list if fields needing repair
   */
  private static function repair_fields( $list )
  {
    global $wpdb;
    
    $repairs = [];
    foreach( $list as $field )
    {
      $repairs[] = sprintf( '(%d,"internal")', $field->id );
    }
    
    if ( count( $repairs ) )
    {
      $wpdb->query( 'INSERT INTO ' . Participants_Db::$fields_table . '(`id`,`group`) VALUES ' . implode( ',', $repairs ) . ' ON DUPLICATE KEY UPDATE  `group` = VALUES(`group`)' );
    }
  }

  /**
   * makes a readable string out of a database error
   * 
   * @param string $error
   * @param string $context
   * @return string
   */
  function parse_db_error( $error, $context )
  {

    // unless we find a custom message, use the class error message
    $message = $error;

    $item = false;

    switch ( $context ) {

      case $this->i18n( 'add group' ):

        $item = $this->i18n( 'group' );
        break;

      case $this->i18n( 'add field' ):

        $item = $this->i18n( 'field' );
        break;
    }

    if ( $item && false !== stripos( $error, 'duplicate' ) ) {

      $message = sprintf( __( 'The %1$s was not added. There is already a %1$s with that name, please choose another.', 'participants-database' ), $item );
    }

    return $message;
  }

  /**
   * provides a dismissable admin message
   * 
   * @param string $message
   * @param string $key id key for the message
   * @param string $type message type
   * @return string html
   */
  protected function dismissable_message( $message, $key = 'field_update', $type = 'notice' )
  {
    return '<div id="pdb-manage_fields_' . $key . '" class="notice updated ' . $type . ' is-dismissible"> 
<p><strong>' . $message . '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
  }

  /**
   * provides translation strings
   * 
   * @param string $key
   * @return string translated string
   */
  private function i18n( $name )
  {
    $i18n = PDb_Manage_Fields::get_i18n();
    return isset( $i18n[ 'name' ] ) ? $i18n[ 'name' ] : $name;
  }

}
