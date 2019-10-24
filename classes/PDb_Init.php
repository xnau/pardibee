<?php

/**
 * plugin initialization class
 * 
 * handles installation, activation, deactivation, deletion, updates
 *
 * @version 2.2
 *
 * The way db updates will work is we will first set the "fresh install" db
 * initialization to the latest version's structure. Then, we add the update
 * queries to the series of upgrade steps that follow. Whichever version the
 * plugin comes in with when activated, it will jump into the series at that
 * point and complete the series to bring the database up to date.
 *
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Init {

  // arrays for building default field set
  public static $internal_fields;
  public static $main_fields;
  public static $admin_fields;
  public static $personal_fields;
  public static $field_groups;

  //

  /**
   * 
   * @param string $mode the initialization mode
   * @param mixed $arg passed-in argument
   */
  function __construct( $mode = false, $arg = false )
  {
    if ( !$mode )
      wp_die( 'class must be called on the activation hooks', 'object not correctly instantiated' );

    switch ( $mode ) {
      case 'activate' :
        $this->_activate();
        break;

      case 'network_activate' :
        $this->_network_activate();
        break;

      case 'deactivate' :
        $this->_deactivate();
        break;

      case 'uninstall' :
        $this->_uninstall();
        break;

      case 'network_uninstall' :
        $this->_network_uninstall();
        break;

      case 'new_blog':
        $this->_new_blog( $arg );
        break;

      case 'delete_blog':
        $this->_delete_blog( $arg );
        break;
    }
  }

  /**
   * set up database, defaults
   * 
   * @param bool $networkwide
   */
  public static function on_activate( $networkwide = false )
  {
    $mode = $networkwide && self::is_network() ? 'network_activate' : 'activate';
    new PDb_Init( $mode );
  }

  /**
   * 
   */
  public static function on_deactivate()
  {
    new PDb_Init( 'deactivate' );
  }

  /**
   * remove all plugin settings and database tables
   */
  public static function on_uninstall()
  {
    $mode = self::is_network() ? 'network_uninstall' : 'uninstall';
    new PDb_Init( $mode );
  }

  /**
   * handle creating a new blog on a network
   */
  public static function new_blog( $blog_id )
  {
    new PDb_Init( 'new_blog', $blog_id );
  }

  /**
   * handle deleting a blog on a network
   * 
   * @param int $blog_id of the blog to delete
   * @param bool  $drop if true, delete tables
   */
  public static function delete_blog( $blog_id, $drop )
  {
    if ( $drop ) {
      new PDb_Init( 'delete_blog', $blog_id );
    }
  }

  /**
   * performs the activation
   * 
   * @global wpdb $wpdb
   */
  private function _activate()
  {
    global $wpdb;

    Participants_Db::setup_source_names();

    // install the db tables if needed
    $this->maybe_install();

    if ( is_callable( 'EAMann\WPSession\DatabaseHandler::create_table' ) )
      EAMann\WPSession\DatabaseHandler::create_table();
    
    // check for the need to run the fix for issue #2039
    $check = $wpdb->get_results('SELECT count(*) FROM `' . Participants_Db::$fields_table . '` WHERE `group` = ""');
    if ( $check > 0 ) {
      $fields = array( "id","private_id");
      foreach( $fields as $fieldname ) {
        $wpdb->update( Participants_Db::$fields_table, array('group' => 'internal', 'form_element' => 'text-line'), array('name' => $fieldname) );
      }
      $fields = array( "date_recorded","date_updated","last_accessed");
      foreach( $fields as $fieldname ) {
        $wpdb->update( Participants_Db::$fields_table, array('group' => 'internal', 'form_element' => 'timestamp'), array('name' => $fieldname) );
      }
    }

    do_action( 'pdb-plugin_activation' );

    error_log( Participants_Db::PLUGIN_NAME . ' plugin activated' );
  }

  /**
   * performs the activation on a network
   * 
   */
  private function _network_activate()
  {
    self::do_network_operation( array($this, 'maybe_install') );

    do_action( 'pdb-plugin_network_activation' );

    error_log( Participants_Db::PLUGIN_NAME . ' plugin activated' );
  }

  /**
   * install tables when a new blog is created
   * 
   * @global  wpdb  $wpdb
   * @param int $blog_id the id of the new blog
   */
  private function _new_blog( $blog_id )
  {
    global $wpdb;

    if ( is_plugin_active_for_network( 'participants-database/participants-database.php' ) ) {
      $current_blog = $wpdb->blogid;
      switch_to_blog( $blog_id );
      $this->maybe_install();
      switch_to_blog( $current_blog );
    }
  }

  /**
   * deletes a blog's PDB tables
   * 
   * @global wpdb $wpdb
   * @param int $blog_id
   * 
   */
  private function _delete_blog( $blog_id )
  {
    global $wpdb;

    // store the currently active blog id
    $current_blog = $wpdb->blogid;
    switch_to_blog( $blog_id );
    Participants_Db::setup_source_names();


    // delete tables
    $sql = 'DROP TABLE `' . Participants_Db::$fields_table . '`, `' . Participants_Db::$participants_table . '`, `' . Participants_Db::$groups_table . '`;';
    $wpdb->query( $sql );

    // return to the current blog selection
    switch_to_blog( $current_blog );
  }

  /**
   * triggers a database install if needed
   * 
   * @global wpdb $wpdb
   */
  public function maybe_install()
  {
    global $wpdb;
    Participants_Db::setup_source_names();

    if ( $this->needs_install() ) {
      $this->_install_database();
    }
  }

  /**
   * checks for the need to install the tables
   * 
   * @global wpdb $wpdb
   * @return bool true if the tables do not exist
   */
  private function needs_install()
  {
    global $wpdb;
    return $wpdb->get_var( 'SHOW TABLES LIKE "' . Participants_Db::$participants_table . '"' ) != Participants_Db::$participants_table;
  }

  /**
   * checks if multisite is active
   * 
   */
  public static function is_network()
  {
    return function_exists( 'is_multisite' ) && is_multisite();
  }

  /**
   * deactivates the plugin; does a litle housekeeping
   */
  private function _deactivate()
  {
    /*
     * this gives users with a session buildup issue a way to delete them
     * 
     * that bug was fixed, though, so maybe not much call for it
     */
    self::delete_user_sessions();

    error_log( Participants_Db::PLUGIN_NAME . ' plugin deactivated' );
  }

  /**
   * uninstalls the plugin network-wide
   * 
   * @global wpdb $wpdb
   */
  private function _network_uninstall()
  {
    self::do_network_operation( array($this, '_uninstall') );

    do_action( 'pdb-plugin_network_uninstall' );
  }

  /**
   * performs a network-wide operation
   * 
   * @global wpdb $wpdb
   * @param array|string $callback a PHP callable to execute on each blog
   */
  public static function do_network_operation( $callback )
  {
    add_action( 'switch_blog', 'PDb_Init::switch_blog', 10, 2 );

    global $wpdb;

    // store the currently active blog id
    $current_blog = $wpdb->blogid;

    // Get all blog ids
    $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    // cycle through all the blogs and make the callback on each one
    foreach ( $blogids as $blog_id ) {
      switch_to_blog( $blog_id );
      call_user_func( $callback );
    }
    // return to the current blog selection
    switch_to_blog( $current_blog );
  }

  /**
   * handles switching the blog
   * 
   * 
   * @param int $new_blog id
   * @param int $current_blog id
   */
  public static function switch_blog( $new_blog, $current_blog )
  {
    if ( $new_blog != $current_blog ) {
      Participants_Db::setup_source_names();
    }
  }

  /**
   * deletes all plugin tables, options and transients
   * 
   * @global wpdb $wpdb
   */
  private function _uninstall()
  {
    Participants_Db::initialize();
    Participants_Db::setup_source_names();

    do_action( 'participants_database_uninstall' );

    global $wpdb;

// delete tables
    $sql = 'DROP TABLE `' . Participants_Db::$fields_table . '`, `' . Participants_Db::$participants_table . '`, `' . Participants_Db::$groups_table . '`;';
    $wpdb->query( $sql );

// remove options
    delete_option( Participants_Db::$participants_db_options );
    delete_option( Participants_Db::$db_version_option );
    delete_option( Participants_Db::$default_options );
    delete_option( Participants_Db::one_time_notice_flag );
    delete_option( Participants_Db::$prefix . 'csv_import_params' );
    
// clear user options
    $delete_keys = array(
        Participants_Db::$prefix . PDb_List_Admin::$user_setting_name . '%',
        Participants_Db::$prefix . PDb_List_Admin::$filter_option . '%',
    );
    $sql = 'SELECT `option_name` FROM ' . $wpdb->prefix . 'options WHERE `option_name` LIKE "' . join( '" OR `option_name` LIKE "', $delete_keys ) . '"';
    $options = $wpdb->get_col( $sql );
    foreach ( $options as $name ) {
      delete_option( $name );
    }

// clear transients
    delete_transient( Participants_Db::$last_record );
    $delete_keys = array(
        '%' . PDb_List_Admin::$user_setting_name . '%',
        '%' . Participants_Db::$prefix . 'captcha_key',
        '%' . Participants_Db::$prefix . 'signup-email-sent',
        '%' . Participants_Db::$prefix . PDb_Live_Notification::cache_name . '%',
        PDb_Aux_Plugin::throttler . '%'
    );
    $sql = 'SELECT `option_name` FROM ' . $wpdb->prefix . 'options WHERE `option_name` LIKE "' . join( '" OR `option_name` LIKE "', $delete_keys ) . '"';
    $transients = $wpdb->get_col( $sql );
    foreach ( $transients as $name ) {
      delete_transient( $name );
    }

    PDb_Session::uninstall();

    error_log( Participants_Db::PLUGIN_NAME . ' plugin uninstalled' );
  }

  /**
   * deletes all sessions created by the WP_Session class
   * 
   * this could potentially interfere with another plugin that might use WP_Sessions, 
   * but they're meant to be temporary, so it would only result in some minor disruption
   * 
   * @global wpdb $wpdb
   */
  public static function delete_user_sessions()
  {
    global $wpdb;
// clear session transients
    $sql = 'SELECT `option_name` FROM ' . $wpdb->prefix . 'options WHERE ( `option_name` LIKE "_wp_session_%" AND `option_name` NOT LIKE "_wp_session_expires_%" )';
    $transients = $wpdb->get_col( $sql );
    foreach ( $transients as $name ) {
      delete_option( $name );
    }
  }

  /**
   * installs the plugin database tables and default fields
   * 
   * @global wpdb $wpdb
   */
  private function _install_database()
  {
    global $wpdb;
    
// define the arrays for loading the initial db records
    self::_define_init_arrays();

// create the field values table
    $sql = 'CREATE TABLE ' . Participants_Db::$fields_table . ' (
          `id` INT(3) NOT NULL AUTO_INCREMENT,
          `order` INT(3) NOT NULL DEFAULT 0,
          `name` VARCHAR(64) NOT NULL,
          `title` TINYTEXT NOT NULL,
          `default` TINYTEXT NULL,
          `group` VARCHAR(64) NOT NULL,
          `form_element` TINYTEXT NULL,
          `options` LONGTEXT NULL,
          `attributes` TEXT NULL,
          `validation` TINYTEXT NULL,
          `validation_message` TEXT NULL,
          `help_text` TEXT NULL,
          `display_column` INT(3) DEFAULT 0,
          `admin_column` INT(3) DEFAULT 0,
          `sortable` BOOLEAN DEFAULT 0,
          `CSV` BOOLEAN DEFAULT 0,
          `persistent` BOOLEAN DEFAULT 0,
          `signup` BOOLEAN DEFAULT 0,
					`readonly` BOOLEAN DEFAULT 0,
          UNIQUE KEY  ( `name` ),
          INDEX  ( `order` ),
          INDEX  ( `group` ),
          PRIMARY KEY  ( `id` )
          )
          DEFAULT CHARACTER SET utf8
          COLLATE utf8_unicode_ci
          AUTO_INCREMENT = 0
          ';
    $wpdb->query( $sql );

// create the groups table
    $sql = 'CREATE TABLE ' . Participants_Db::$groups_table . ' (
          `id` INT(3) NOT NULL AUTO_INCREMENT,
          `order` INT(3) NOT NULL DEFAULT 0,
          `mode` VARCHAR(64) NOT NULL,
          `display` BOOLEAN DEFAULT 1,
          `admin` BOOLEAN DEFAULT 0,
          `title` TINYTEXT NOT NULL,
          `name` VARCHAR(64) NOT NULL,
          `description` TEXT NULL,
          UNIQUE KEY ( `name` ),
          PRIMARY KEY ( `id` )
          )
          DEFAULT CHARACTER SET utf8
          COLLATE utf8_unicode_ci
          AUTO_INCREMENT = 1
          ';
    $wpdb->query( $sql );

// create the main data table
    $sql = 'CREATE TABLE ' . Participants_Db::$participants_table . ' (
          `id` int(6) NOT NULL AUTO_INCREMENT,
          `private_id` VARCHAR(9) NULL,
          ';
    foreach ( array_keys( self::$field_groups ) as $group ) {

      // these are not added to the sql in the loop
      if ( $group == 'internal' )
        continue;

      foreach ( self::${$group . '_fields'} as $name => &$defaults ) {

        if ( !isset( $defaults['form_element'] ) )
          $defaults['form_element'] = 'text-line';

        $datatype = PDb_FormElement::get_datatype( $defaults['form_element'] );

        $sql .= '`' . $name . '` ' . $datatype . ' NULL, ';
      }
    }

    $sql .= '`date_recorded` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `date_updated` TIMESTAMP NULL DEFAULT NULL,
          `last_accessed` TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY  (`id`)
          )
          DEFAULT CHARACTER SET utf8
          COLLATE utf8_unicode_ci
          ;';

    $wpdb->query( $sql );

// save the db version
    update_option( Participants_Db::$db_version_option, Participants_Db::$db_version );

// now load the default values into the database
    $i = 0;
    unset( $defaults );
    foreach ( array_keys( self::$field_groups ) as $group ) {

      foreach ( self::${$group . '_fields'} as $name => $defaults ) {

        $defaults['name'] = $name;
        $defaults['group'] = $group;
        $defaults['order'] = $i;
        $defaults['validation'] = isset( $defaults['validation'] ) ? $defaults['validation'] : 'no';

        if ( isset( $defaults['options'] ) && is_array( $defaults['options'] ) ) {

          $defaults['options'] = serialize( $defaults['options'] );
        }

        $wpdb->insert( Participants_Db::$fields_table, $defaults );

        $i++;
      }
    }

// put in the default groups
    $i = 1;
    $defaults = array();
    foreach ( self::$field_groups as $group => $title ) {
      $defaults['name'] = $group;
      $defaults['title'] = $title;
      $defaults['mode'] = ( in_array( $group, array('internal', 'admin', 'source') ) ? 'admin' : 'public' );
      $defaults['admin'] = ( in_array( $group, array('internal', 'admin', 'source') ) ? '1' : '0' );
      $defaults['display'] = ( in_array( $group, array('internal', 'admin', 'source') ) ? '0' : '1' );
      $defaults['order'] = $i;

      $wpdb->insert( Participants_Db::$groups_table, $defaults );

      $i++;
    }
  }

  /**
   * performs an update to the database if needed
   * 
   * @global wpdb $wpdb
   */
  public static function on_update()
  {
    global $wpdb;

// determine the actual version status of the database
    self::set_database_real_version();

    $db_version = get_option( Participants_Db::$db_version_option );

    if ( PDB_DEBUG )
      Participants_Db::debug_log( 'participants database db version determined to be: ' . $db_version );

    if ( false === $db_version || '0.1' == $db_version ) {

      /*
       * updates version 0.1 database to 0.2
       *
       * adding a new column "display_column" and renaming "column" to
       * "admin_column" to accommodate the new frontend display shortcode
       */

      $sql = "ALTER TABLE " . Participants_Db::$fields_table . " ADD COLUMN `display_column` INT(3) DEFAULT 0 AFTER `validation`,";

      $sql .= "CHANGE COLUMN `column` `admin_column` INT(3)";

      if ( false !== $wpdb->query( $sql ) ) {

        // in case the option doesn't exist
        add_option( Participants_Db::$db_version_option );

        // set the version number this step brings the db to
        $db_version = '0.2';
      }

      // load some preset values into new column
      $values = array(
          'first_name' => 1,
          'last_name' => 2,
          'city' => 3,
          'state' => 4
      );
      foreach ( $values as $field => $value ) {
        $wpdb->update(
                Participants_Db::$fields_table, array('display_column' => $value), array('name' => $field)
        );
      }
    }

    if ( '0.2' == $db_version ) {

      /*
       * updates version 0.2 database to 0.3
       *
       * modifying the 'values' column of the fields table to allow for larger
       * select option lists
       */

      $sql = "ALTER TABLE " . Participants_Db::$fields_table . " MODIFY COLUMN `values` LONGTEXT NULL DEFAULT NULL";

      if ( false !== $wpdb->query( $sql ) ) {

        // set the version number this step brings the db to
        $db_version = '0.3';
      }
    }

    if ( '0.3' == $db_version ) {

      /*
       * updates version 0.3 database to 0.4
       *
       * changing the 'when' field to a date field
       * exchanging all the PHP string date values to UNIX timestamps in all form_element = 'date' fields
       *
       */

      // change the 'when' field to a date field
      $wpdb->update( Participants_Db::$fields_table, array('form_element' => 'date'), array('name' => 'when', 'form_element' => 'text-line') );

      //
      $date_fields = $wpdb->get_results( 'SELECT f.name FROM ' . Participants_Db::$fields_table . ' f WHERE f.form_element = "date"', ARRAY_A );

      $df_string = '';

      foreach ( $date_fields as $date_field ) {

        if ( !in_array( $date_field['name'], array('date_recorded', 'date_updated') ) )
          $df_string .= ',`' . $date_field['name'] . '` ';
      }

      // skip updating the Db if there's nothing to update
      if ( !empty( $df_string ) ) :

        $query = '
						
						SELECT `id`' . $df_string . '
						FROM ' . Participants_Db::$participants_table;

        $fields = $wpdb->get_results( $query, ARRAY_A );


        // now that we have all the date field values, convert them to N=UNIX timestamps
        foreach ( $fields as $row ) {

          $id = $row['id'];
          unset( $row['id'] );

          $update_row = array();

          foreach ( $row as $field => $original_value ) {

            if ( empty( $original_value ) )
              continue 2;

            // if it's already a timestamp, we don't try to convert
            $value = preg_match( '#^[0-9]+$#', $original_value ) > 0 ? $original_value : strtotime( $original_value );

            // if strtotime fails, revert to original value
            $update_row[$field] = ( false === $value ? $original_value : $value );
          }

          $wpdb->update(
                  Participants_Db::$participants_table, $update_row, array('id' => $id)
          );
        }

      endif;

      // set the version number this step brings the db to
      $db_version = '0.4';
    }

    if ( '0.4' == $db_version ) {

      /*
       * updates version 0.4 database to 0.5
       *
       * modifying the "import" column to be named more appropriately "CSV"
       */

      $sql = "ALTER TABLE " . Participants_Db::$fields_table . " CHANGE COLUMN `import` `CSV` TINYINT(1)";

      if ( false !== $wpdb->query( $sql ) ) {

        // set the version number this step brings the db to
        $db_version = '0.5';
      }
    }

    /* this fixes an error I made in the 0.5 DB update
     */
    if ( '0.5' == $db_version && false === Participants_Db::get_participant() ) {

      // define the arrays for loading the initial db records
      self::_define_init_arrays();

      // load the default values into the database
      $i = 0;
      unset( $defaults );
      foreach ( array_keys( self::$field_groups ) as $group ) {

        foreach ( self::${$group . '_fields'} as $name => $defaults ) {

          $defaults['name'] = $name;
          $defaults['group'] = $group;
          $defaults['CSV'] = 'main' == $group ? 1 : 0;
          $defaults['order'] = $i;
          $defaults['validation'] = isset( $defaults['validation'] ) ? $defaults['validation'] : 'no';

          if ( isset( $defaults['options'] ) && is_array( $defaults['options'] ) ) {

            $defaults['options'] = serialize( $defaults['options'] );
          }

          $wpdb->insert( Participants_Db::$fields_table, $defaults );

          $i++;
        }
      }
      // set the version number this step brings the db to
      $db_version = '0.5.1';
    }

    /*
     * this is to fix a problem with the timestamp having it's datatype
     * changed when the field attributes are edited
     */
    if ( '0.51' == $db_version ) {

      $sql = "SHOW FIELDS FROM " . Participants_Db::$participants_table . " WHERE `field` IN ('date_recorded','date_updated')";
      $field_info = $wpdb->get_results( $sql );

      foreach ( $field_info as $field ) {

        if ( $field->Type !== 'TIMESTAMP' ) {

          switch ( $field->Field ) {

            case 'date_recorded':

              $column_definition = '`date_recorded` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
              break;

            case 'date_updated':

              $column_definition = '`date_updated` TIMESTAMP NOT NULL DEFAULT 0';
              break;

            default:

              $column_definition = false;
          }

          if ( false !== $column_definition ) {

            $sql = "ALTER TABLE " . Participants_Db::$participants_table . " MODIFY COLUMN " . $column_definition;

            $result = $wpdb->get_results( $sql );
          }
        }
      }

      // delete the default record
      $wpdb->query( $wpdb->prepare( "DELETE FROM " . Participants_Db::$participants_table . " WHERE private_id = '%s'", 'RPNE2' ) );

      // add the new private ID admin column setting because we eliminated the redundant special setting
      $options = get_option( Participants_Db::$participants_db_options );
      if ( $options['show_pid'] ) {
        $wpdb->update( Participants_Db::$fields_table, array('admin_column' => 90), array('name' => 'private_id') );
      }

      /*
       * add the "read-only" column
       */
      $sql = "ALTER TABLE " . Participants_Db::$fields_table . " ADD COLUMN `readonly` BOOLEAN DEFAULT 0 AFTER `signup`";

      $wpdb->query( $sql );

      /*
       * change the old 'textarea' field type to the new 'text-area'
       */
      $sql = "
          UPDATE " . Participants_Db::$fields_table . "
          SET `form_element` = replace(`form_element`, \"textarea\", \"text-area\")";
      $wpdb->query( $sql );
      $sql = "
          UPDATE " . Participants_Db::$fields_table . "
          SET `form_element` = replace(`form_element`, \"text-field\", \"text-line\") ";


      if ( false !== $wpdb->query( $sql ) ) {

        // update the stored DB version number
        $db_version = '0.55';
      }
    }

    /*
     * this database version adds the "last_accessed" column to the main database
     * 
     */
    if ( '0.55' == $db_version ) {

      /*
       * add the "last_accessed" column
       */
      $sql = "ALTER TABLE " . Participants_Db::$participants_table . " ADD COLUMN `last_accessed` TIMESTAMP NOT NULL AFTER `date_updated`";

      $wpdb->query( $sql );

      /*
       * add the new field to the fields table
       */
      $data = array(
          'order' => '20',
          'name' => 'last_accessed',
          'title' => 'Last Accessed',
          'group' => 'internal',
          'sortable' => '1',
          'form_element' => 'date',
      );

      if ( false !== $wpdb->insert( Participants_Db::$fields_table, $data ) ) {

        // update the stored DB version number
        $db_version = '0.6';
      }
    }
    if ( '0.6' == $db_version ) {
      /*
       * this database version changes the internal timestamp fields from "date" 
       * type to "timestamp" type fields, also sets the 'readonly' flag of internal 
       * fields so we don't have to treat them as a special case any more.
       * 
       * set the field type of internal timestamp fields to 'timestamp'
       */
      $sql = "UPDATE " . Participants_Db::$fields_table . " p SET p.form_element = 'timestamp', p.readonly = '1' WHERE p.name IN ('date_recorded','last_accessed','date_updated')";
      if ( $wpdb->query( $sql ) !== false ) {
        // update the stored DB version number
        $db_version = '0.65';
      }
    }
    if ( '0.65' == $db_version ) {
      /*
       * adds a new column to the goups database so a group cna be designated as a "admin" group
       */
      $sql = "ALTER TABLE " . Participants_Db::$groups_table . " ADD COLUMN `admin` BOOLEAN NOT NULL DEFAULT 0 AFTER `order`";

      if ( $wpdb->query( $sql ) !== false ) {
        // update the stored DB version number
        $db_version = '0.7';
      }
      $sql = "UPDATE " . Participants_Db::$groups_table . " g SET g.admin = '1' WHERE g.name ='internal'";
      $wpdb->query( $sql );
    }
    if ( '0.7' == $db_version ) {
      /*
       * changes all date fields' datatype to BIGINT unless the user has modified the datatype
       */
      $sql = 'SELECT f.name FROM ' . Participants_Db::$fields_table . ' f INNER JOIN INFORMATION_SCHEMA.COLUMNS AS c ON TABLE_NAME = "' . Participants_Db::$participants_table . '" AND c.column_name = f.name COLLATE utf8_general_ci AND data_type = "TINYTEXT" WHERE f.form_element = "date"';

      $results = $wpdb->get_results( $sql, ARRAY_A );
      $fields = array();
      foreach ( $results as $result ) {
        $fields[] = $result['name'];
      }

      if ( count( $fields ) === 0 ) {

        // nothing to change, update the version number
        $db_version = '0.8';
      } else {

        $results = $wpdb->get_results( "SHOW COLUMNS FROM `" . Participants_Db::$participants_table . "`" );
        $columns = array();
        foreach ( $results as $result ) {
          $columns[] = $result->Field;
        }
        $fields = array_intersect( $columns, $fields );
        $sql = 'ALTER TABLE ' . Participants_Db::$participants_table . ' MODIFY COLUMN `' . implode( '` BIGINT NULL, MODIFY COLUMN `', $fields ) . '` BIGINT NULL';

        if ( false !== $wpdb->query( $sql ) ) {
          // set the version number this step brings the db to
          $db_version = '0.8';
        }
      }
    }
    if ( '0.8' == $db_version ) {
      /*
       * all field and group names need more staorage space, changing the datatype to VARCHAR(64)
       */
      $sql = "ALTER TABLE " . Participants_Db::$fields_table . " MODIFY `name` VARCHAR(64) NOT NULL, MODIFY `group` VARCHAR(64) NOT NULL";

      if ( false !== $wpdb->query( $sql ) ) {

        $sql = "ALTER TABLE " . Participants_Db::$groups_table . " MODIFY `name` VARCHAR(64) NOT NULL";

        if ( false !== $wpdb->query( $sql ) ) {
          // set the version number this step brings the db to
          $db_version = '0.9';
        }
      }
    }

    if ( '0.9' == $db_version ) {
      /*
       * set TIMESTAMP fields to allow NULL and set the default to NULL
       */
      $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$participants_table . "` MODIFY COLUMN `date_updated` TIMESTAMP NULL DEFAULT NULL" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$participants_table . "` MODIFY COLUMN `last_accessed` TIMESTAMP NULL DEFAULT NULL" );
      /*
       * set other "not null" columns to NULL so the empty default value won't trigger an error
       */
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$participants_table . "` MODIFY COLUMN `private_id` VARCHAR(6) NULL" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$fields_table . "` MODIFY COLUMN `name` VARCHAR(64) NULL" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$fields_table . "` MODIFY COLUMN `title` TINYTEXT NULL" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$fields_table . "` MODIFY COLUMN `group` VARCHAR(64) NULL" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$groups_table . "` MODIFY COLUMN `name` VARCHAR(64) NULL" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE `" . Participants_Db::$groups_table . "` MODIFY COLUMN `title` TINYTEXT NULL" );
      //
      if ( $success !== false ) {
        $table_status = $wpdb->get_results( "SHOW TABLE STATUS WHERE `name` = '" . Participants_Db::$participants_table . "'" );
        if ( current( $table_status )->Collation !== 'utf8_unicode_ci' ) {
          if ( $success !== false )
            $success = $wpdb->query( "alter table `" . Participants_Db::$participants_table . "` convert to character set utf8 collate utf8_unicode_ci" );
          if ( $success !== false )
            $success = $wpdb->query( "alter table `" . Participants_Db::$fields_table . "` convert to character set utf8 collate utf8_unicode_ci" );
          if ( $success !== false )
            $success = $wpdb->query( "alter table `" . Participants_Db::$groups_table . "` convert to character set utf8 collate utf8_unicode_ci" );
        }
      }

      if ( $success === false ) {
        error_log( __METHOD__ . ' database could not be updated: ' . $wpdb->last_error );
      } else {
        $db_version = '1.0';
      }
    }

    // update from 1.0 to 1.1
    if ( '1.0' == $db_version ) {

      $success = $wpdb->query( "ALTER TABLE " . Participants_Db::$fields_table . " ADD COLUMN `options` LONGTEXT NULL AFTER `values`" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE " . Participants_Db::$fields_table . " ADD COLUMN `attributes` TEXT NULL AFTER `options`" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE " . Participants_Db::$fields_table . " ADD COLUMN `validation_message` TEXT NULL AFTER `validation`" );
      if ( $success !== false )
        $success = $wpdb->query( "ALTER TABLE " . Participants_Db::$groups_table . " ADD COLUMN `mode` VARCHAR(64) NULL AFTER `order`" );


      if ( $success === false ) {
        error_log( __METHOD__ . ' database could not be updated: ' . $wpdb->last_error );
      } else {
        self::set_mode_column_values();
        self::update_field_def_values();
        $db_version = '1.1';
      }
    }
    
    update_option( Participants_Db::$db_version_option, $db_version );

    if ( PDB_DEBUG && $success ) {
      Participants_Db::debug_log( Participants_Db::PLUGIN_NAME . ' plugin updated to Db version ' . $db_version );
    }
  }

  /**
   * performs a series of tests on the database to determine it's actual version
   * 
   * this is because it is apparently possible for the database version option 
   * to be incorrect or missing. This way, we know with some certainty which version 
   * the database really is. Every time we create a new database version, we add 
   * a test for it here.
   * 
   * @global object $wpdb
   * @return null
   */
  private static function set_database_real_version()
  {

    global $wpdb;
    $current_version = '0.1';

// set up the option starting with the first version
    add_option( Participants_Db::$db_version_option, $current_version );

// check to see if the update to 0.2 has been performed
    $column_test = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$fields_table . ' LIKE "column"' );
    if ( empty( $column_test ) ) {
      $current_version = '0.2';
    }

// check for version 0.4
    $column_test = $wpdb->get_results( 'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "' . Participants_Db::$fields_table . '" AND COLUMN_NAME = "values"' );
    if ( strtolower( $column_test[0]->DATA_TYPE ) == 'longtext' ) {
// we're skipping update 3 because all it does is insert default values
      $current_version = '0.4';
    }

// check for version 0.51
    $column_test = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$fields_table . ' LIKE "import"' );
    if ( empty( $column_test ) ) {
      $current_version = '0.51';
    }
// check for version 0.55
    $column_test = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$fields_table . ' LIKE "readonly"' );
    if ( !empty( $column_test ) ) {
      $current_version = '0.55';
    }

// check for version 0.6
    $column_test = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$participants_table . ' LIKE "last_accessed"' );
    if ( !empty( $column_test ) ) {
      $current_version = '0.6';
    }

// check for version 0.65
    $value_test = $wpdb->get_var( 'SELECT `form_element` FROM ' . Participants_Db::$fields_table . ' WHERE `name` = "date_recorded"' );
    if ( $value_test == 'timestamp' ) {
      $current_version = '0.65';
    }

// check for version 0.7
    $column_test = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$groups_table . ' LIKE "admin"' );
    if ( !empty( $column_test ) ) {
      $current_version = '0.7';
    }

// check for version 0.9
    $column_test = $wpdb->get_results( 'SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "' . Participants_Db::$fields_table . '" AND COLUMN_NAME = "name"' );
    if ( $column_test[0]->CHARACTER_MAXIMUM_LENGTH === '64' ) {
      $current_version = '0.9';
    }

    // check for version 1.0
    $table_status = $wpdb->get_results( "SHOW TABLE STATUS WHERE `name` = '" . Participants_Db::$participants_table . "'" );
    if ( current( $table_status )->Collation == 'utf8_unicode_ci' ) {
      $current_version = '1.0';
    }

    // check for version 1.1
    $column_test = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$groups_table . ' LIKE "mode"' );
    if ( !empty( $column_test ) ) {
      $current_version = '1.1';
    }

    update_option( Participants_Db::$db_version_option, $current_version );

    return;
  }

  /**
   * fills the new group mode column with values based on the old data
   * 
   * @global wpdb $wpdb
   */
  private static function set_mode_column_values()
  {
    global $wpdb;

    $group_data_list = $wpdb->get_results( 'SELECT * FROM ' . Participants_Db::$groups_table );

    if ( !is_array( $group_data_list ) ) {
      error_log( __METHOD__ . ' groups data not obtained' );
      return;
    }

    $update = array();
    foreach ( $group_data_list as $group ) {
      if ( !empty( $group->mode ) ) {
        continue 1;
      }
      switch ( true ) {
        case $group->display == '0' && $group->admin == '1':
        case $group->name === 'admin':
        case $group->name === 'internal':
          $mode = 'admin';
          break;
        case $group->display == '1' && $group->admin == '1':
        case $group->display == '1' && $group->admin == '0':
        case $group->display == '0' && $group->admin == '0':
          $mode = 'public';
          break;
      }
      $wpdb->update( Participants_Db::$groups_table, array('mode' => $mode), array('id' => $group->id) );
    }
  }
  
  /**
   * updates the field definitions to use the new columns as of db version 1.1
   * 
   * value set field now use the "options" column to hold the options values, other 
   * fields use only the attributes column
   * 
   * @global wpdb $wpdb
   */
  public static function update_field_def_values()
  {
    global $wpdb;
    $field_def_list = $wpdb->get_results( 'SELECT v.* 
              FROM ' . Participants_Db::$fields_table . ' v 
              ORDER BY v.order' );
    
    foreach ( $field_def_list as $field_def ) {
      if ( Participants_Db::$fields[$field_def->name]->is_value_set() ) {
        $update = array( 'values' => NULL );
        if ( empty( $field_def->options ) ) {
          $update['options'] = $field_def->values;
        }
        $wpdb->update( Participants_Db::$fields_table, 
                $update, 
                array( 
                    'id' => $field_def->id 
                ) );
      } elseif ( empty( $field_def->attributes ) && ! empty( $field_def->values ) ) {
        $wpdb->update( Participants_Db::$fields_table, 
                array( 
                    'attributes' => $field_def->values, 
                    'values' => NULL 
                    ), 
                array( 
                    'id' => $field_def->id 
                ) );
      }
    }
  }

  /**
   * defines arrays containing a starting set of fields, groups, etc.
   *
   * @return void
   */
  private static function _define_init_arrays()
  {

// define the default field groups
    self::$field_groups = array(
        'main' => __( 'Participant Info', 'participants-database' ),
        'personal' => __( 'Personal Info', 'participants-database' ),
        'admin' => __( 'Administrative Info', 'participants-database' ),
        'internal' => __( 'Record Info', 'participants-database' ),
    );

// fields for keeping track of records; not manually edited, but they can be displayed
    self::$internal_fields = array(
        'id' => array(
            'title' => 'Record ID',
            'signup' => 1,
            'form_element' => 'text-line',
            'CSV' => 1,
            'readonly' => 1,
        ),
        'private_id' => array(
            'title' => 'Private ID',
            'signup' => 1,
            'form_element' => 'text',
            'admin_column' => 90,
            'default' => 'RPNE2',
            'readonly' => 1,
        ),
        'date_recorded' => array(
            'title' => 'Date Recorded',
            'form_element' => 'timestamp',
            'admin_column' => 100,
            'sortable' => 1,
            'readonly' => 1,
        ),
        'date_updated' => array(
            'title' => 'Date Updated',
            'form_element' => 'timestamp',
            'sortable' => 1,
            'readonly' => 1,
        ),
        'last_accessed' => array(
            'title' => 'Last Accessed',
            'form_element' => 'timestamp',
            'sortable' => 1,
            'readonly' => 1,
        ),
    );


    /*
     * these are some fields just to get things started
     * in the released plugin, these will be defined by the user
     *
     * the key is the id slug of the field
     * the fields in the array are:
     *  title - a display title
     *  help_text - help text to appear on the form
     *   default - a default value
     *   sortable - a listing can be sorted by this value if set
     *   column - column in the list view and order (missing or 0 for not used)
     *   persistent - is the field persistent from one entry to the next (for
     *                convenience while entering multiple records)
     *   CSV - is the field one to be imported or exported
     *   validation - if the field needs to be validated, use this regex or just
     *               yes for a value that must be filled in
     *   form_element - the element to use in the form--defaults to
     *                 input, Could be text-line (input), text-field (textarea),
     *                 radio, dropdown (option) or checkbox, also select-other
     *                 multi-checkbox and asmselect.(http: *www.ryancramer.com/journal/entries/select_multiple/)
     *                 The mysql data type is determined by this.
     *   values array title=>value pairs for checkboxes, radio buttons, dropdowns
     *               for checkbox, first item is visible option, if value
     *               matches 'default' value then it defaults checked
     */
    self::$main_fields = array(
        'first_name' => array(
            'title' => 'First Name',
            'form_element' => 'text-line',
            'validation' => 'yes',
            'sortable' => 1,
            'admin_column' => 2,
            'display_column' => 1,
            'signup' => 1,
            'CSV' => 1,
        ),
        'last_name' => array(
            'title' => 'Last Name',
            'form_element' => 'text-line',
            'validation' => 'yes',
            'sortable' => 1,
            'admin_column' => 3,
            'display_column' => 2,
            'signup' => 1,
            'CSV' => 1,
        ),
        'address' => array(
            'title' => 'Address',
            'form_element' => 'text-line',
            'CSV' => 1,
        ),
        'city' => array(
            'title' => 'City',
            'sortable' => 1,
            'persistent' => 1,
            'form_element' => 'text-line',
            'admin_column' => 0,
            'display_column' => 3,
            'CSV' => 1,
        ),
        'state' => array(
            'title' => 'State',
            'sortable' => 1,
            'persistent' => 1,
            'form_element' => 'text-line',
            'display_column' => 4,
            'CSV' => 1,
        ),
        'country' => array(
            'title' => 'Country',
            'sortable' => 1,
            'persistent' => 1,
            'form_element' => 'text-line',
            'CSV' => 1,
        ),
        'zip' => array(
            'title' => 'Zip Code',
            'sortable' => 1,
            'persistent' => 1,
            'form_element' => 'text-line',
            'CSV' => 1,
        ),
        'phone' => array(
            'title' => 'Phone',
            'help_text' => 'Your primary contact number',
            'form_element' => 'text-line',
            'CSV' => 1,
        ),
        'email' => array(
            'title' => 'Email',
            'form_element' => 'text-line',
            'admin_column' => 4,
            'validation' => 'email-regex',
            'signup' => 1,
            'CSV' => 1,
        ),
        'mailing_list' => array(
            'title' => 'Mailing List',
            'help_text' => 'do you want to receive our newsletter and occasional announcements?',
            'sortable' => 1,
            'signup' => 1,
            'form_element' => 'checkbox',
            'CSV' => 1,
            'default' => 'Yes',
            'options' => array(
                'Yes',
                'No',
            ),
        ),
    );
    self::$personal_fields = array(
        'photo' => array(
            'title' => 'Photo',
            'help_text' => 'Upload a photo of yourself. 300 pixels maximum width or height.',
            'form_element' => 'image-upload',
        ),
        'website' => array(
            'title' => 'Website, Blog or Social Media Link',
            'form_element' => 'link',
            'help_text' => 'Put the URL in the left box and the link text that will be shown on the right',
        ),
        'interests' => array(
            'title' => 'Interests or Hobbies',
            'form_element' => 'multi-select-other',
            'options' => array(
                'Sports' => 'sports',
                'Photography' => 'photography',
                'Art/Crafts' => 'crafts',
                'Outdoors' => 'outdoors',
                'Yoga' => 'yoga',
                'Music' => 'music',
                'Cuisine' => 'cuisine',
            ),
        ),
    );
    self::$admin_fields = array(
        'approved' => array(
            'title' => 'Approved',
            'sortable' => 1,
            'form_element' => 'checkbox',
            'default' => 'no',
            'options' => array(
                'yes',
                'no',
            ),
        ),
    );
  }

}
