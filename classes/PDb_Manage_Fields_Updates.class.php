<?php

/**
 * handles updating the field and group definitions
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
class PDb_Manage_Fields_Updates {

  /**
   * instantiate the class
   * 
   * @return null 
   */
  function __construct()
  {
    add_action( 'wp_ajax_' . PDb_Manage_Fields::action_key, array($this, 'process_ajax_submit') );
    add_action( 'admin_post_update_fields', array($this, 'update_fields') );
    add_action( 'admin_post_add_field', array($this, 'add_field') );
    add_action( 'admin_post', array($this, 'process_submit') );
  }

  /**
   * process the field definition update submission
   * 
   * @global wpdb $wpdb
   */
  public function update_fields()
  {
    global $wpdb;

    // dispose of these now unneeded fields
    unset( $_POST['action'], $_POST['submit-button'] );

    foreach ( $_POST as $name => $row ) {

      // skip all non-row elements
      if ( false === strpos( $name, 'row_' ) )
        continue;

      // unescape quotes in values
      foreach ( $row as $k => $rowvalue ) {
        if ( !is_array( $rowvalue ) ) {
          $row[$k] = stripslashes( $rowvalue );
        }
      }

      if ( $row['status'] == 'changed' ) {

        $id = filter_var( $row['id'], FILTER_VALIDATE_INT );

        foreach ( array('options', 'attributes') as $attname ) {
          if ( isset( $row[$attname] ) ) {
            $row[$attname] = self::prep_values_array( $row[$attname] );
          }
        }

        // clear out the deprecated values column
        $row['values'] = null;

        if ( !empty( $row['validation'] ) && !in_array( $row['validation'], array('yes', 'no') ) ) {

          $row['validation'] = str_replace( '\\\\', '\\', $row['validation'] );
        }

        /*
         * modify the datatype if necessary
         * 
         * the 'datatype_warning' needs to be accepted for this to take place
         */
        if ( isset( $row['group'] ) && $row['group'] != 'internal' && $new_type = $this->new_datatype( $row['name'], $row['values'], $row['form_element'] ) ) {
          if ( !isset( $row['datatype_warning'] ) || ( isset( $row['datatype_warning'] ) && $row['datatype_warning'] === 'accepted' ) ) {
            $wpdb->query( "ALTER TABLE " . Participants_Db::$participants_table . " MODIFY COLUMN `" . esc_sql( $row['name'] ) . "` " . $new_type );
          } else {
            unset( $row['form_element'] ); // prevent this from getting changed
          }
        }
        unset( $row['datatype_warning'] );

        /*
         * add some form-element-specific processing
         */
        if ( isset( $row['form_element'] ) ) {
          switch ( $row['form_element'] ) {
            case 'captcha':
              foreach ( array('title', 'help_text', 'default') as $field ) {
                if ( isset( $row[$field] ) )
                  $row[$field] = stripslashes( $row[$field] );
              }
              $row['validation'] = 'captcha';
              foreach ( array('display_column', 'admin_column', 'CSV', 'persistent', 'sortable') as $c )
                $row[$c] = 0;
              $row['readonly'] = 1;
              break;
            case 'decimal':
              if ( !isset( $row['values']['step'] ) ) {
                $row['values']['step'] = 'any';
              }
              break;
          }
        }

        $status = array_intersect_key( $row, array('id' => '', 'status' => '', 'name' => '') );

        // remove the fields we won't be updating
        unset( $row['status'], $row['id'], $row['name'], $row['selectable'] );

        foreach ( $row as $name => $row_item ) {
          if ( is_array( $row_item ) ) {
            $row[$name] = serialize( $row[$name] );
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
        $result = $wpdb->update( Participants_Db::$fields_table, Participants_Db::apply_filters( 'update_field_def', $row, $status ), array('id' => $id) );
        if ( PDB_DEBUG >= 1 ) {
          Participants_Db::debug_log( __METHOD__ . ' update fields: ' . $wpdb->last_query );
        }
        if ( $result ) {
          PDb_Admin_Notices::post_success( __( 'Fields Updated', 'participants-database' ) );
        }
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
    // set up the new field's parameters
    $params = array(
        'name' => filter_input( INPUT_POST, 'title', FILTER_CALLBACK, array( 'options' => 'PDb_Manage_Fields_Updates::make_name' ) ),
        'title' => filter_input( INPUT_POST, 'title', FILTER_SANITIZE_STRING, array( 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES|FILTER_FLAG_STRIP_BACKTICK ) ),
        'group' => filter_input( INPUT_POST, 'group', FILTER_SANITIZE_STRING ),
        'order' => '0',
        'validation' => 'no',
        'form_element' => filter_input( INPUT_POST, 'form_element', FILTER_SANITIZE_STRING ),
    );

    if ( empty( $params['name'] ) ) {
      $this->return_to_the_manage_database_fields_page(); // ignore empty field name
    }

    // if they're trying to use a reserved name, stop them
    if ( in_array( $params['name'], Participants_Db::$reserved_names ) ) {

      Participants_Db::set_admin_message( sprintf(
                      '<strong>%s</strong> %s: %s', __( 'Cannot add a field with that name', 'participants-database' ), __( 'This name is reserved; please choose another. Reserved names are', 'participants-database' ), implode( ', ', Participants_Db::$reserved_names )
              ), 'error' );
      $this->return_to_the_manage_database_fields_page();
    }

    // if they're trying to use the same name as one that already exists
    if ( array_key_exists( $params['name'], Participants_Db::$fields ) ) {

      Participants_Db::set_admin_message( sprintf(
                      '<strong>%s</strong> %s', __( 'Cannot add a field with that name', 'participants-database' ), __( 'The name must be unique: a field with that name has been previously defined.', 'participants-database' )
              ), 'error' );
      $this->return_to_the_manage_database_fields_page();
    }

    // prevent name from beginning with a number
    if ( preg_match( '/^\d/', $params['name'] ) === 1 ) {

      Participants_Db::set_admin_message( sprintf(
                      '<strong>%s</strong> %s', __( 'The name cannot begin with a number', 'participants-database' ), __( 'Please choose another.', 'participants-database' )
              ), 'error' );
      $this->return_to_the_manage_database_fields_page();
    }

    $result = Participants_Db::add_blank_field( $params );

    if ( $result ) {
      Participants_Db::set_admin_message( __( 'The new field was added.', 'participants-database' ), 'update' );
      if ( PDb_FormElement::is_value_set( $params['form_element'] ) )
        Participants_Db::set_admin_message( __( 'Remember to define the "options" for your new field.', 'participants-database' ), 'update' );
    } else {
      Participants_Db::set_admin_message( __( 'The field could not be added.', 'participants-database' ), 'error' );
    }

    $this->return_to_the_manage_database_fields_page();
  }

  /**
   * processes the general form submission
   * 
   * @global wpdb $wpdb
   * @return null 
   */
  public function process_submit()
  {

    if ( !array_key_exists( '_wpnonce', $_POST ) || !wp_verify_nonce( $_POST['_wpnonce'], PDb_Manage_Fields::action_key ) ) {
      return;
    }

    global $wpdb;

    // process form submission
    $action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

    $result = true;

    switch ( $action ) {

      case $this->i18n( 'update groups' ):

        // dispose of these now unneeded fields
        unset( $_POST['action'], $_POST['submit-button'], $_POST['group_title'], $_POST['group_order'] );

        foreach ( $_POST as $name => $row ) {

          foreach ( array('title', 'description') as $field ) {
            if ( isset( $row[$field] ) )
              $row[$field] = stripslashes( $row[$field] );
          }

          $result = $wpdb->update( Participants_Db::$groups_table, $row, array('name' => stripslashes_deep( $name )) );
        }
        break;

      // add a new blank field
      case $this->i18n( 'add field' ):



      // add a new blank field
      case $this->i18n( 'add group' ):

        $wpdb->hide_errors();

        $atts = array(
            'name' => self::make_name( $_POST['group_title'] ),
            'title' => htmlspecialchars( stripslashes( $_POST['group_title'] ), ENT_QUOTES, "UTF-8", false ),
            'order' => $_POST['group_order'],
        );

        $result = $wpdb->insert( Participants_Db::$groups_table, $atts );

        break;

      case 'delete_' . $this->i18n( 'group' ):

        global $wpdb;
        //$wpdb->hide_errors();

        $group_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Participants_Db::$fields_table . ' WHERE `group` = "%s"', $_POST['delete'] ) );

        if ( $group_count == 0 )
          $result = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . Participants_Db::$groups_table . ' WHERE `name` = "%s"', $_POST['delete'] ) );

        break;
    }

    if ( $result === false ) {
      if ( $wpdb->last_error ) {
        Participants_Db::set_admin_message( $this->parse_db_error( $wpdb->last_error, $action ), 'error' );
      } elseif ( empty( Participants_Db::$admin_message ) ) {
        Participants_Db::set_admin_message( __( 'There was an error; the settings were not saved.', 'participants-database' ), 'error' );
      }
    } elseif ( is_int( $result ) ) {
      /**
       * @action pdb-field_defs_updated
       * @param string action
       * @param string last query
       */
      do_action( Participants_Db::$prefix . 'field_defs_updated', $action, $wpdb->last_query );
      Participants_Db::set_admin_message( __( 'Your information has been updated', 'participants-database' ), 'updated' );
    }
  }

  /**
   * processes the ajax submission
   * 
   * @global wpdb $wpdb
   */
  public function process_ajax_submit()
  {
    if ( !array_key_exists( '_wpnonce', $_POST ) || !wp_verify_nonce( $_POST['_wpnonce'], PDb_Manage_Fields::action_key ) ) {
      wp_send_json( 'failed' );
    }

    global $wpdb;

    switch ( filter_input( INPUT_POST, 'task', FILTER_SANITIZE_STRING ) ) {

      case 'delete_field':

        $list = $this->sanitize_id_list();

        if ( count( $list ) < 1 ) {
          wp_send_json( 'error:no valid id list' );
        }

        $result = $wpdb->query( '
      DELETE FROM ' . Participants_Db::$fields_table . '
      WHERE id IN ("' . implode( '","', $list ) . '")'
        );

        wp_send_json( array('status' => 'success', 'feedback' => $this->dismissable_message( __( 'Selected fields deleted', 'participants-database' ) )) );

      case 'update_param':

        $list = $this->sanitize_id_list();

        if ( count( $list ) < 1 ) {
          wp_send_json( 'error:no valid id list' );
        }

        $param = filter_input( INPUT_POST, 'param', FILTER_SANITIZE_STRING );
        $setting = filter_input( INPUT_POST, 'setting', FILTER_SANITIZE_STRING ) === 'true' ? 1 : 0;

        $result = $wpdb->query( '
      UPDATE ' . Participants_Db::$fields_table . '
      SET `' . $param . '` = "' . $setting . '" 
      WHERE id IN ("' . implode( '","', $list ) . '")'
        );

        wp_send_json( array('status' => 'success', 'feedback' => $this->dismissable_message( __( 'Field settings updated.', 'participants-database' ) )) );

      case 'reorder_fields':
        parse_str( filter_input( INPUT_POST, 'list', FILTER_SANITIZE_STRING ), $list );
        $update = array();
        foreach ( $list as $key => $value ) {
          $update[] = 'WHEN `id` = "' . filter_var( str_replace( 'row_', '', $key ), FILTER_SANITIZE_NUMBER_INT ) . '" THEN "' . filter_var( $value, FILTER_SANITIZE_NUMBER_INT ) . '"';
        }
        $result = $wpdb->query( 'UPDATE ' . Participants_Db::$fields_table . ' SET `order` = CASE ' . implode( " \r", $update ) . ' END' );

        wp_send_json( array('status' => $result ? 'success' : 'failed') );

      case 'reorder_groups':
        unset( $_POST['action'], $_POST['submit-button'] );
        foreach ( $_POST as $key => $value ) {
          $result = $wpdb->update(
                  Participants_Db::$groups_table, array('order' => filter_var( $value, FILTER_SANITIZE_NUMBER_INT )), array('name' => filter_var( str_replace( 'order_', '', $key ), FILTER_SANITIZE_STRING ))
          );
        }
        wp_send_json( array('status' => 'success') );
    }
  }

  /**
   * redirects back to the manage database fields page after processing the submission
   */
  private function return_to_the_manage_database_fields_page()
  {
    wp_redirect( add_query_arg( 'page', 'participants-database-manage_fields', admin_url( 'admin.php' ) ) );
    exit();
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
   * @param string $values
   * @return array
   */
  public static function prep_values_array( $values )
  {
    /**
     * allows for alternate strings to be used in structuring the field options 
     * definition string 
     * 
     * @filter pdb-field_options_pair_delim
     * @filter pdb-field_options_option_delim
     * @param string the default string
     * @return string the string to use for the structure
     */
    $pair_delim = Participants_Db::apply_filters( 'field_options_pair_delim', '::' );
    $option_delim = Participants_Db::apply_filters( 'field_options_option_delim', ',' );

    $has_labels = strpos( $values, $pair_delim ) !== false;
    $values_array = array();
    $term_list = explode( $option_delim, stripslashes( $values ) );
    if ( $has_labels ) {
      foreach ( $term_list as $term ) {
        if ( strpos( $term, $pair_delim ) !== false && strpos( $term, $pair_delim ) !== 0 ) {
          list($key, $value) = explode( $pair_delim, $term );
          /*
           * @version 1.6
           * this is to allow for an optgroup label that is the same as a value label...
           * with an admittedly funky hack: adding a space to the end of the key for the 
           * optgroup label. In most cases it will be unnoticed.
           */
          $array_key = in_array( $value, array('false', 'optgroup', false) ) ? trim( $key ) . ' ' : trim( $key );
          $values_array[$array_key] = self::prep_value( trim( $value ), true );
        } else {
          // strip out the double colon in case it is present
          $term = str_replace( array($pair_delim), '', $term );
          $values_array[self::prep_value( $term, true )] = self::prep_value( $term, true );
        }
      }
    } else {
      foreach ( $term_list as $term ) {
        $attribute = self::prep_value( $term, true );
        $values_array[$attribute] = $attribute;
      }
    }
    return PDb_Base::cleanup_array( $values_array );
  }

  /**
   * prepares a string for storage in the database
   * 
   * @param string $value
   * @param bool $single_encode if true, don't encode entities 
   * @return string
   */
  private static function prep_value( $value, $single_encode = false )
  {
    if ( $single_encode )
      return trim( stripslashes( $value ) );
    else
      return htmlentities( trim( stripslashes( $value ) ), ENT_QUOTES, "UTF-8", true );
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
            array(' ', '-', '/', "'", '"', '\\', '#', '.', '$', '&', '%', '>', '<', '`'), 
            array('_', '_', '_', '', '', '', '', '', '', 'and', 'pct', '', '', ''), 
            stripslashes( substr( $string, 0, 64 ) )
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
   * sanitizes a list of ids
   * 
   * @param string $name of the $_POST element
   * @return array
   */
  private function sanitize_id_list( $name = 'list' )
  {
    return filter_var_array( $_POST[$name], FILTER_SANITIZE_NUMBER_INT );
  }

  /**
   * checks for the need to change the field datatype
   * 
   * @global wpdb $wpdb
   * @param string  $fieldname
   * @param array $field_values the new values definition
   * @param string $form_element the field form element
   * @return string|bool false if no change, new datatype if changed
   */
  protected function new_datatype( $fieldname, $field_values, $form_element )
  {
    global $wpdb;
    $sql = "SHOW FIELDS FROM " . Participants_Db::$participants_table . ' WHERE `field` = "%s"';
    $field_info = $wpdb->get_results( $wpdb->prepare( $sql, $fieldname ) );
    $new_type = PDb_FormElement::get_datatype( array('name' => $fieldname, 'values' => $field_values, 'form_element' => $form_element) );
    $current_type = is_object( current( $field_info ) ) ? current( $field_info )->Type : false;
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
    return '<div id="pdb-manage_fields_' . $key . '" class="updated settings-error ' . $type . ' is-dismissible"> 
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
    return isset( $i18n['name'] ) ? $i18n['name'] : $name;
  }

}
