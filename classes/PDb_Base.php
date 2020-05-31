<?php

/*
 * this static class provides a set of utility functions used throughout the plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.10
 * @link       http://xnau.com/wordpress-plugins/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Base {

  /**
   * set if a shortcode is called on a page
   * @var bool
   */
  public static $shortcode_present = false;

  /**
   * finds the WP installation root
   * 
   * this uses constants, so it's not filterable, but the constants (if customized) 
   * are defined in the config file, so should be accurate for a particular installation
   * 
   * this works by finding the common path to both ABSPATH and WP_CONTENT_DIR which 
   * we can assume is the base install path of WP, even if the WP application is in 
   * another directory and/or the content directory is in a different place
   * 
   * @return string
   */
  public static function app_base_path()
  {
    $content_path = explode( '/', WP_CONTENT_DIR );
    $wp_app_path = explode( '/', ABSPATH );
    $end = min( array(count( $content_path ), count( $wp_app_path )) );
    $i = 0;
    $common = array();
    while ( $content_path[$i] === $wp_app_path[$i] and $i < $end ) {
      $common[] = $content_path[$i];
      $i++;
    }
    /**
     * @filter pdb-app_base_path
     * @param string  the base application path as calculated by the function
     * @return string
     */
    return Participants_Db::apply_filters( 'app_base_path', trailingslashit( implode( '/', $common ) ) );
  }

  /**
   * finds the WP base URL
   * 
   * this can be different from the home url if wordpress is in a different directory (http://site.com/wordpress/)
   * 
   * this is to accomodate alternate setups
   * 
   * @return string
   */
  public static function app_base_url()
  {
    $scheme = parse_url( site_url(), PHP_URL_SCHEME ) . '://';
    $content_path = explode( '/', str_replace( $scheme, '', content_url() ) );
    $wp_app_path = explode( '/', str_replace( $scheme, '', site_url() ) );


    $end = min( array(count( $content_path ), count( $wp_app_path )) );
    $i = 0;
    $common = array();
    while ( $i < $end and $content_path[$i] === $wp_app_path[$i] ) {
      $common[] = $content_path[$i];
      $i++;
    }
    return $scheme . trailingslashit( implode( '/', $common ) );
  }
  
  /**
   * provides the asset include path
   * 
   * @param string $asset name of the asset file with its subdirectory
   * @return asset URL
   */
  public static function asset_url( $asset )
  {
    $basepath = Participants_Db::$plugin_path . '/participants-database/';
    
    $asset_filename = self::asset_filename( $asset );
    
    if ( ! is_readable( trailingslashit(Participants_Db::$plugin_path) . $asset_filename ) ) {
      $asset_filename = $asset; // revert to the original name
    }
    
    return plugins_url( $asset_filename, $basepath );
  }
  
  /**
   * adds the minify extension to an asset filename
   * 
   * @param string $asset
   * @return asset filename with the minify extension
   */
  protected static function asset_filename( $asset ) {
    
    $info = pathinfo($asset);
    
    $presuffix = self::use_minified_assets() ? '.min' : '';
    
    return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') 
        . $info['filename'] 
        . $presuffix . '.' 
        . $info['extension'];
  }
  
  /**
   * tells if the minified assets should be used
   * 
   * @return bool true if the minified assets should be used
   */
  public static function use_minified_assets()
  {
    /**
     * @filter pdb-use_minified_assets
     * @param bool default: true if PDB_DEBUG not enabled
     * @return bool
     */
    return Participants_Db::apply_filters( 'use_minified_assets', ! ( defined('PDB_DEBUG') && PDB_DEBUG ) );
  }

  /**
   * provides a simplified way to add or update a participant record
   * 
   * 
   * @param array $post associative array of data to store
   * @param int $id the record id to update, creates new record if omitted
   * @return  int the ID of the record added or updated
   */
  public static function write_participant( Array $post, $id = '' )
  {
    $action = 'insert';
    if ( is_numeric( $id ) ) {
      $action = Participants_Db::get_participant( $id ) === false ? 'insert' : 'update';
    }  
    return Participants_Db::process_form( $post, $action, $id, array_keys( $post ) );
  }

  /**
   * parses a list shortcode filter string into an array
   * 
   * this creates an array that makes it easy to manipulate and interact with the 
   * filter string. The returned array is of format:
   *    'fieldname' => array(
   *       'column' => 'fieldname',
   *       'operator' => '=', (<, >, =, !, ~)
   *       'search_term' => 'string',
   *       'relation' => '&', (optional)
   *       ),
   * 
   * @param string $filter the filter string
   * @return array the string parsed into an array of statement arrays
   */
  public static function parse_filter_string( $filter )
  {
    $return = array();
    $statements = preg_split( '/(&|\|)/', html_entity_decode( $filter ), null, PREG_SPLIT_DELIM_CAPTURE );
    foreach ( $statements as $s ) {
      $statement = self::_filter_statement( $s );
      if ( $statement )
        $return[] = $statement;
    }
    return $return;
  }

  /**
   * builds a filter string from an array of filter statement objects or arrays
   * 
   * @param array $filter_array
   */
  public static function build_filter_string( $filter_array )
  {
    $filter_string = '';
    foreach ( $filter_array as $statement ) {
      $filter_string .= $statement['column'] . $statement['operator'] . $statement['search_term'] . $statement['relation'];
    }
    return rtrim( $filter_string, '&|' );
  }

  /**
   * merges two filter statement arrays
   * 
   * if a given target field is present in both arrays, all statements for that 
   * field will be eliminated from the first array, and the statements from the 
   * second array will be used. All other elements in the second array will follow the elements from the first array
   * 
   * @param array $array1
   * @param array $array2 the overriding array
   * @return array the combined array
   */
  public static function merge_filter_arrays( $array1, $array2 )
  {
    $return = array();
    foreach ( $array1 as $statement ) {
      $index = self::search_array_column( $array2, $statement['column'] );
      if ( $index === false ) {
        $return[] = $statement;
      }
    }
    return array_merge( $return, $array2 );
  }

  /**
   * searches for a matching column in an array
   * 
   * this function searches for a matching term of a given key in the second dimension 
   * of the array and returns the index of the matching array
   * 
   * @param array $array the array to search
   * @param string $term the term to search for
   * @param string the key of the element to search in
   * @return mixed the int index of the matching array or bool false if no match
   */
  private static function search_array_column( $array, $term, $key = 'column' )
  {
    for ( $i = 0; $i < count( $array ); $i++ ) {
      if ( $array[$i][$key] == $term )
        return $i;
    }
    return false;
  }

  /**
   * supplies an object comprised of the components of a filter statement
   * 
   * @param type $statement
   * @return array
   */
  private static function _filter_statement( $statement, $relation = '&' )
  {

    $operator = preg_match( '#^([^\2]+)(\>|\<|=|!|~)(.*)$#', $statement, $matches );

    if ( $operator === 0 )
      return false; // no valid operator; skip to the next statement

    list( $string, $column, $operator, $search_term ) = $matches;

    $return = array();

    // get the parts
    $return = compact( 'column', 'operator', 'search_term' );

    $return['relation'] = $relation;

    return $return;
  }
  
  /**
   * supplies a list of participant record ids
   * 
   * @param array $config with structure:
   *                    filter      a shortcode filter string
   *                    orderby     a comma-separated list of fields
   *                    order       a comma-separated list of sort directions, correlates 
   *                                to the $sort_fields argument
   * 
   * @return array of data arrays as $name => $value
   */
  public static function get_id_list( $config )
  {
    return self::get_list( $config, array('id') );
  }
  
  /**
   * supplies a list of participant data arrays
   * 
   * this provides only raw data from the database
   * 
   * @param array $config with structure:
   *                    filter      a shortcode filter string
   *                    orderby     a comma-separated list of fields
   *                    order       a comma-separated list of sort directions, correlates 
   *                                to the $sort_fields argument
   *                    fields      a comma-separated list of fields to get
   * @param array $columns list of field names to include in the results
   * 
   * @return array of data arrays as $name => $value
   */
  public static function get_participant_list( $config )
  {
    if ( !isset( $config['fields'] ) ) {
      // get all column names
      $columns = array_keys(self::field_defs());
    } else {
      $columns = explode(',', str_replace(' ','',$config['fields'] ) );
      if ( array_search( 'id', $columns ) ) {
        unset( $columns[array_search( 'id', $columns )] );
      }
      $columns = array_merge( array('id'), $columns );
    }
    
    return self::get_list( $config, $columns );
  }
  
  /**
   * provides an array of field definitions from main groups only
   * 
   * @global wpdb $wpdb
   * @return array of PDb_Form_Field_Def objects
   */
  public static function field_defs()
 {
    $cachekey = 'pdb_field_def_array';
    $fieldlist = wp_cache_get($cachekey);
    
    if ( ! $fieldlist ) {
      
      global $wpdb;
      
      $fieldlist = array();
      
      $sql = 'SELECT v.* 
              FROM ' . Participants_Db::$fields_table . ' v 
              JOIN ' . Participants_Db::$groups_table . ' g ON v.group = g.name
              WHERE g.mode IN ("' . implode( '","', array_keys(PDb_Manage_Fields::group_display_modes()) ) . '")
              ORDER BY v.order';
      
      $result = $wpdb->get_results( $sql );
      
      foreach ( $result as $column ) {
        $fieldlist[$column->name] = new PDb_Form_Field_Def( $column->name );
      }
      
      wp_cache_set($cachekey, $fieldlist, '', self::cache_expire() );
    }
    return $fieldlist;
  }
  
  /**
   * provides the name of the main database table
   * 
   * @return string
   */
  public static function participants_table()
  {
    return Participants_Db::apply_filters('participants_table', Participants_Db::$participants_table );
  }

  /**
   * supplies a list of PDB record data given a configuration object
   * 
   * @global wpdb $wpdb
   * @param array $config with structure:
   *                    filter      a shortcode filter string
   *                    orderby     a comma-separated list of fields
   *                    order       a comma-separated list of sort directions, correlates 
   *                                to the $sort_fields argument
   * @param array $columns optional list of field names to include in the results
   * 
   * @return array of record values (if single column) or id-indexed array of data objects
   */
  private static function get_list( $config, Array $columns )
  {
    $shortcode_defaults = array(
        'sort' => 'false',
        'search' => 'false',
        'list_limit' => '-1',
        'filter' => '',
        'orderby' => Participants_Db::plugin_setting( 'list_default_sort' ),
        'order' => Participants_Db::plugin_setting( 'list_default_sort_order' ),
        'suppress' => false,
        'module' => 'API',
        'fields' => implode(',',$columns),
        'instance_index' => '1',
    );
    $shortcode_atts = shortcode_atts( $shortcode_defaults, $config );
    
    $list = new PDb_List( $shortcode_atts );
    $list_query = new PDb_List_Query( $list );
    
    global $wpdb;
    if ( count( $list->display_columns ) === 1 ) {
      $result = $wpdb->get_col($list_query->get_list_query());
    } else {
      $result = $wpdb->get_results( $list_query->get_list_query(), OBJECT_K );
    }
    
    return $result;
  }

  /**
   * determines if an incoming set of data matches an existing record
   * 
   * @param array|string  $columns    column name, comma-separated series, or array 
   *                                  of column names to check for matching data
   * @param array         $submission the incoming data to test: name => value 
   *                                  (could be an unsanitized POST array)
   * 
   * @return int|bool record ID if the incoming data matches an existing record, 
   *                  bool false if no match
   */
  public static function find_record_match( $columns, $submission )
  {
    $matched_id = self::record_match_id( $columns, $submission );
    /**
     * @version 1.6
     * 
     * filter pdb-find_record_match
     * 
     * a callback on the filter can easily use the PDb_Base::record_match_id() 
     * method to find a match
     * 
     * @param int|bool  $matched_id the id found using the standard method, bool 
     *                              false if no match was found
     * @param string    $columns column name or names used to find the match
     * @param array     $submission the un-sanitized $_POST array
     * 
     * @return int|bool the found record ID
     */
    return self::apply_filters( 'find_record_match', $matched_id, $columns, $submission );
  }

  /**
   * determines if an incoming set of data matches an existing record
   * 
   * @param array|string  $columns    column name, comma-separated series, or array 
   *                                  of column names to check for matching data
   * @param array         $submission the incoming data to test: name => value 
   *                                  (could be an unsanitized POST array)
   * @global object $wpdb
   * @return int|bool record ID if the incoming data matches an existing record, 
   *                  bool false if no match
   */
  public static function record_match_id( $columns, $submission )
  {
    global $wpdb;
    $values = array();
    $where = array();
    $columns = is_array( $columns ) ? $columns : explode( ',', str_replace( ' ', '', $columns ) );
    foreach ( $columns as $column ) {
      if ( isset( $submission[$column] ) ) {
        $values[] = $submission[$column];
        $where[] = ' r.' . $column . ' = %s';
      } else {
        $where[] = ' (r.' . $column . ' IS NULL OR r.' . $column . ' = "")';
      }
    }
    $sql = 'SELECT r.id FROM ' . Participants_Db::$participants_table . ' r WHERE ' . implode( ' AND ', $where );
    $match = $wpdb->get_var( $wpdb->prepare( $sql, $values ) );

    return is_numeric( $match ) ? (int) $match : false;
  }

  /**
   * provides a permalink given a page name, path or ID
   * 
   * this allows a permalink to be found for a page name, relative path or post ID. 
   * If an absolute path is provided, the path is returned unchanged.
   * 
   * @param string|int $page the term indicating the page to get a permalink for
   * @global wpdb $wpdb
   * @return string|bool the permalink or false if it fails
   */
  public static function find_permalink( $page )
  {
    $permalink = false;
    $id = false;
    if ( filter_var( $page, FILTER_VALIDATE_URL ) ) {
      $permalink = $page;
    } elseif ( preg_match( '#^[0-9]+$#', $page ) ) {
      $id = $page;
    } elseif ( $post = get_page_by_path( $page ) ) {
      $id = $post->ID;
    } else {
      // get the ID by the post slug
      global $wpdb;
      $id = $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM $wpdb->posts p WHERE p.post_name = '%s' AND p.post_status = 'publish'", trim( $page, '/ ' ) ) );
      
    }
    if ( $id )
      $permalink = self::get_permalink( $id );
    return $permalink;
  }
  
  /**
   * provides the permalink for a WP page or post given the ID
   * 
   * this implements a filter to allow a multilingual plugin to alter the ID
   * 
   * @param int $id the post ID
   * @return string the permalink
   */
  public static function get_permalink( $id )
  {
    /**
     * allow a multilingual plugin to set the language post id
     * 
     * @filter pdb-lang_page_id
     * @param int the post ID
     * @return int the language page ID
     */
    return get_permalink( Participants_Db::apply_filters( 'lang_page_id', $id ) );
  }

  /**
   * supplies the current participant ID
   * 
   * there are several possibilities (depending on the context) for the location 
   * of this information, we need to check each one
   * 
   * @param string $id the id (if known)
   * @return string the ID, empty if not determined
   */
  public static function get_record_id( $id = '' )
  {
    if ( empty( $id ) ) {
      // this is for backward compatibility
      $id = filter_input( INPUT_GET, Participants_Db::$single_query, FILTER_SANITIZE_NUMBER_INT );
    }
    if ( empty( $id ) ) {
      $id = Participants_Db::get_participant_id( filter_input( INPUT_GET, Participants_Db::$record_query, FILTER_SANITIZE_STRING ) );
    }
    if ( empty( $id ) && is_admin() ) {
      $id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
    }
    if ( empty( $id ) ) {
      $id = Participants_Db::get_participant_id( get_query_var( 'pdb-record-edit-slug', false ) );
    }
    if ( empty( $id ) ) {
      $id = Participants_Db::get_record_id_by_term( 'record_slug', get_query_var( 'pdb-record-slug', 0 ) );
    }
    if ( empty( $id ) ) {
      $id = Participants_Db::$session->get( 'pdbid' );
    }
    return $id;
  }

  /**
   * determines if the field is the designated single record field
   * 
   * also checks that the single record page has been defined
   * 
   * @param object $field
   * @return bool
   */
  public static function is_single_record_link( $field )
  {
    $name = is_object( $field ) ? $field->name : $field;
    $page = Participants_Db::single_record_page();
    /**
     * @filter pdb-single_record_link_field
     * @param array the defined single record link field name
     * @return array of fieldnames
     */
    return !empty( $page ) && in_array( $name, self::apply_filters( 'single_record_link_field', (array) Participants_Db::plugin_setting( 'single_record_link_field' ) ) );
  }

  /*
   * prepares an array for storage in the database
   *
   * @param array $array
   * @return string prepped array in serialized form or empty if no data
   */

  public static function _prepare_array_mysql( $array )
  {

    if ( !is_array( $array ) )
      return Participants_Db::_prepare_string_mysql( $array );

    $prepped_array = array();

    $empty = true;

    foreach ( $array as $key => $value ) {

      if ( $value !== '' )
        $empty = false;
      $prepped_array[$key] = Participants_Db::_prepare_string_mysql( (string) $value );
    }

    return $empty ? '' : serialize( $prepped_array );
  }

  /**
   * prepares a string for storage
   * 
   * @param string $string the string to prepare
   */
  public static function _prepare_string_mysql( $string )
  {
    return stripslashes( $string );
  }
  
  /**
   * provides a rich text editor element ID
   * 
   * there are special rules in the wp_editor function for the $editor_id parameter
   * 
   * @param string $name usually the field name
   * @return string
   */
  public static function rich_text_editor_id( $name )
  {
    $texnum = array(
        '0' => 'zero','1' => 'one','2' => 'two','3' => 'three','4' => 'four','5' => 'five','6' => 'six','7' => 'seven','8' => 'eight','9' => 'nine'
    );
    $text_numbered = preg_replace_callback('/[0-9]/', function ($d) use ($texnum) {
      return '_' . $texnum[intval(current($d))];
    }, strtolower( Participants_Db::$prefix . $name . Participants_Db::$instance_index ) );
    
    return preg_replace( array('#-#', '#[^a-z_]#'), array('_', ''), $text_numbered );
  }
  
  
  /**
   * tells if a database value is set
   * 
   * this is mainly used as a callback for an array_filter function
   * 
   * @param string|null $v the raw value from the db
   * @return bool true if the value is set 
   */
  public static function is_set_value( $v ) {
    return ! is_null($v) && strlen($v) > 0;
  }

  /**
   * unserializes an array if necessary, provides an array in all cases
   * 
   * @param string $string the string to unserialize; does nothing if it is not 
   *                       a serialization
   * @return array
   */
  public static function unserialize_array( $string )
  {
    return (array) maybe_unserialize( $string );
  }

  /**
   * adds the URL conjunction to a GET string
   *
   * @param string $URI the URI to which a get string is to be added
   *
   * @return string the URL with the conjunction character appended
   */
  public static function add_uri_conjunction( $URI )
  {

    return $URI . ( false !== strpos( $URI, '?' ) ? '&' : '?');
  }

  /**
   * returns a path to the defined image location
   *
   * this func is superceded by the PDb_Image class methods
   *
   * can also deal with a path saved before 1.3.2 which included the whole path
   *
   * @return the file url if valid; if the file can't be found returns the
   *         supplied filename
   */
  public static function get_image_uri( $filename )
  {

    if ( !file_exists( $filename ) ) {

      $filename = self::files_uri() . basename( $filename );
    }

    return $filename;
  }

  /**
   * tests a filename for allowed file extentions
   * 
   * @param string  $filename the filename to test
   * @param array $field_allowed_extensions array of local allowed file extensions
   * 
   * @return bool true if the extension is allowed
   */
  public static function is_allowed_file_extension( $filename, $field_allowed_extensions = array() )
  {
    $extensions = empty( $field_allowed_extensions ) || ! is_array( $field_allowed_extensions ) ? self::global_allowed_extensions() : $field_allowed_extensions;
    
    if ( empty( $extensions ) ) {
      // nothing in the whitelist, don't allow
      return false;
    }

    $result = preg_match( '#^(.+)\.(' . implode( '|', $extensions ) . ')$#', strtolower( $filename ), $matches );
    
    return (bool) $result;
  }
  
  /**
   * provides a list of globally allowed file extensions
   * 
   * @return array
   */
  public static function global_allowed_extensions()
  {
    $global_setting = Participants_Db::plugin_setting_value('allowed_file_types');
    
    return explode( ',', str_replace( array( '.', ' ' ), '', strtolower( $global_setting ) ) );
  }

  /**
   * provides an array of allowed extensions from the field def "values" parameter
   * 
   * deprecated, get this from the PDb_Form_Field_Def instance
   * 
   * @param string|array $values possibly serialized array of field attributes or allowed extensions
   * @return string comma-separated list of allowed extensions, empty string if not defined in the field
   */
  public static function get_field_allowed_extensions( $values )
  {
    $value_list = array_filter( self::unserialize_array( $values ) );
    
    foreach( array('rel','download','target','type') as $att ) {
      if ( array_key_exists( $att, $value_list ) ) {
        unset( $value_list[$att] ); 
      }
    }
    
    // if the allowed attribute is used, return its values
    if ( array_key_exists( 'allowed', $value_list ) ) {
        return str_replace( '|', ',', $value_list['allowed'] );
    }
    
    return implode( ',', $value_list );
  }

  /**
   * parses the value string and obtains the corresponding dynamic value
   *
   * the object property pattern is 'object->property' (for example 'curent_user->name'),
   * and the presence of the  '->'string identifies it.
   * 
   * the superglobal pattern is 'global_label:value_name' (for example 'SERVER:HTTP_HOST')
   *  and the presence of the ':' identifies it.
   *
   * if there is no indicator, the field is treated as a constant
   *
   * @param string $value the current value of the field as read from the
   *                      database or in the $_POST array
   *
   */
  public static function get_dynamic_value( $value )
  {

    // this value serves as a key for the dynamic value to get
    $dynamic_key = html_entity_decode( $value );
    
    /**
     * @filter pdb-dynamic_value
     * 
     * @param string the initial result; empty string
     * @param string the dynamic value key
     * @return the computed dynamic value
     */
    $dynamic_value = Participants_Db::apply_filters( 'dynamic_value', '', $dynamic_key );
    
    // return the value if it was set in the filter
    if ( $dynamic_value !== '' )
      return $dynamic_value;

    if ( strpos( $dynamic_key, '->' ) > 0 ) {

      /*
       * here, we can get values from one of several WP objects
       * 
       * so far, that is only $post amd $current_user
       */
      global $post, $current_user;

      list( $object, $property ) = explode( '->', $dynamic_key );

      $object = ltrim( $object, '$' );

      if ( is_object( $$object ) && ! empty( $$object->$property ) ) {

        $dynamic_value = $$object->$property;
        
      } elseif ( $object === 'current_user' && $property === 'locale' ) {
        
        $dynamic_value = get_locale();
      }
    } elseif ( strpos( $dynamic_key, ':' ) > 0 ) {

      /*
       * here, we are attempting to access a value from a PHP superglobal
       */

      list( $global, $name ) = explode( ':', $dynamic_key );

      /*
       * if the value refers to an array element by including [index_name] or 
       * ['index_name'] we extract the indices
       */
      $indexes = array();
      if ( strpos( $name, '[' ) !== false ) {
        $count = preg_match( "#^([^]]+)(?:\['?([^]']+)'?\])?(?:\['?([^]']+)'?\])?$#", stripslashes( $name ), $matches );
        $match = array_shift( $matches ); // discarded
        $name = array_shift( $matches );
        $indexes = count( $matches ) > 0 ? $matches : array();
      }

      // clean this up in case someone puts $_SERVER instead of just SERVER
      $global = preg_replace( '#^[$_]{1,2}#', '', $global );

      /*
       * for some reason getting the superglobal array directly with the string
       * is unreliable, but this bascially works as a whitelist, so that's
       * probably not a bad idea.
       */
      switch ( strtoupper( $global ) ) {

        case 'SERVER':
          $global = $_SERVER;
          break;
        case 'SESSION':
          $global = $_SESSION;
          break;
        case 'REQUEST':
          $global = $_REQUEST;
          break;
        case 'COOKIE':
          $global = $_COOKIE;
          break;
        case 'POST':
          $global = $_POST;
          break;
        case 'GET':
          $global = $_GET;
      }

      /*
       * we attempt to evaluate the named value from the superglobal, which includes 
       * the possiblity that it will be referring to an array element. We take that 
       * to two dimensions only. the only way that I know of to do this open-ended 
       * is to use eval, which I won't do
       */
      if ( isset( $global[$name] ) ) {
        if ( is_string( $global[$name] ) ) {
          $dynamic_value = $global[$name];
        } elseif ( is_array( $global[$name] ) || is_object( $global[$name] ) ) {

          $array = is_object( $global[$name] ) ? get_object_vars( $global[$name] ) : $global[$name];
          switch ( count( $indexes ) ) {
            case 1:
              $dynamic_value = isset( $array[$indexes[0]] ) ? $array[$indexes[0]] : '';
              break;
            case 2:
              $dynamic_value = isset( $array[$indexes[0]][$indexes[1]] ) ? $array[$indexes[0]][$indexes[1]] : '';
              break;
            default:
              // if we don't have an index, grab the first value
              $dynamic_value = is_array( $array ) ? current( $array ) : '';
          }
        }
      }
    }

    /*
     * note: we need to sanitize the value, but we don't know what kind of value 
     * it will be so we're just going to treat them all as strings. It shouldn't 
     * be object or array anyway, so if a number is represented as a string, it's 
     * not a big deal.
     */
    return filter_var( $dynamic_value, FILTER_SANITIZE_STRING );
  }

  /**
   * determines if the field default value string is a dynamic value
   * 
   * @param string $value the value to test
   * @return bool true if the value is to be parsed as dynamic
   */
  public static function is_dynamic_value( $value )
  {
    $test_value = html_entity_decode( $value );
    
    /**
     * @filter pdb-dynamic_value
     * 
     * this filter is duplicated here so we can test the dynamic con_
     * 
     * @param string the initial result; empty string
     * @param string the dynamic value key
     * @return the computed dynamic value
     */
    $dynamic_value = Participants_Db::apply_filters( 'dynamic_value', '', $test_value );
    
    return strpos( $test_value, '->' ) > 0 || strpos( $test_value, ':' ) > 0 || $dynamic_value !== '';
  }
  
  /**
   * tells if the string is a new password
   * 
   * checks if the string is an encrypted WP password or the password dummy
   * 
   * @param string $string the string to test
   * @return bool true if the string is a new password
   */
  public static function is_new_password( $string )
  {
    // we're counting on the new password not beginning with $P$
    return strpos( $string, '$P$' ) !== 0 && $string !== PDb_FormElement::dummy;
  }

  /**
   * supplies a group object for the named group
   * 
   * @param string $name group name
   * @return object the group parameters as a stdClass object
   */
  public static function get_group( $name )
  {
    global $wpdb;
    $sql = 'SELECT * FROM ' . Participants_Db::$groups_table . ' WHERE `name` = "%s"';
    return current( $wpdb->get_results( $wpdb->prepare( $sql, $name ) ) );
  }
  
  /**
   * checks a plugin permission level and passes it through a filter
   * 
   * this allows for all plugin functions that are permission-controlled to be controlled 
   * with a filter callback
   * 
   * the context value will contain the name of the function or script that is protected
   * 
   * @see http://codex.wordpress.org/Roles_and_Capabilities
   * 
   * @param string $cap the plugin capability level (not WP cap) to check for
   * @param string $context provides the context of the request
   * 
   * @return string the name of the WP capability to use in the named context
   */
  public static function plugin_capability( $cap, $context = '' )
  {

    $capability = 'read'; // assume the lowest cap
    if ( in_array( $cap, array('plugin_admin_capability', 'record_edit_capability') ) ) {
      /**
       * provides access to individual access privileges
       * 
       * @filter pdb-access_capability
       * @param string the WP capability that identifies the default level of access
       * @param string the privilege being requested
       * @return string the WP capability that is allowed this privilege
       */
      $capability = self::apply_filters( 'access_capability', self::plugin_setting_value( $cap ), $context );
//      global $wp_filter;
//      error_log(__METHOD__.' filters placed on access_capability: '.print_r($wp_filter['pdb-access_capability'],1));
    }
    return $capability;
  }

  /**
   * check the current users plugin role
   * 
   * the plugin has two roles: editor and admin; it is assumed an admin has the editor 
   * capability
   * 
   * @param string $role optional string to test a specific role. If omitted, tests 
   *                     for editor role
   * @param string $context the function or action being tested for
   * 
   * @return bool true if current user has the role tested
   */
  public static function current_user_has_plugin_role( $role = 'editor', $context = '' )
  {
    $role = stripos( $role, 'admin' ) !== false ? 'plugin_admin_capability' : 'record_edit_capability';

    return current_user_can( self::plugin_capability( $role, $context ) );
  }

  /**
   * checks if a CSV export is allowed
   * 
   * first checks for a valid nonce, if that fails, checks the current user's capabilities
   * 
   * @return bool true if the export is allowed under the current circumstances
   */
  public static function csv_export_allowed()
  {
    $nonce = array_key_exists( '_wpnonce', $_POST ) ? filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING ) : false;
    if ( $nonce && wp_verify_nonce( $nonce, self::csv_export_nonce() ) ) {
      return true;
    }
    $csv_role = Participants_Db::plugin_setting_is_true( 'editor_allowed_csv_export' ) ? 'editor' : 'admin';
    return Participants_Db::current_user_has_plugin_role( $csv_role, 'csv export' );
  }

  /**
   * supplies a nonce tag for the CSV export
   * 
   * @return string
   */
  public static function csv_export_nonce()
  {
    return 'pdb-csv_export';
  }

  /**
   * loads the plugin translation fiels and sets the textdomain
   * 
   * the parameter is for the use of aux plugins
   * 
   * originally from: http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
   * 
   * @param string $path of the calling file
   * @param string $textdomain omit to use default plugin textdomain
   * 
   * @return null
   */
  public static function load_plugin_textdomain( $path, $textdomain = '' )
  {

    $textdomain = empty( $textdomain ) ? Participants_Db::PLUGIN_NAME : $textdomain;
    // The "plugin_locale" filter is also used in load_plugin_textdomain()
    $locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

    load_textdomain( $textdomain, WP_LANG_DIR . '/' . Participants_Db::PLUGIN_NAME . '/' . $textdomain . '-' . $locale . '.mo' );
    
    load_plugin_textdomain( $textdomain, false, dirname( plugin_basename( $path ) ) . '/languages/' );
  }

  /**
   * sends a string through a filter for affecting a multilingual translation
   * 
   * this is called on the pdb-translate_string filter, which is only enabled 
   * when PDB_MULTILINGUAL is defined true
   * 
   * this is meant for strings with embedded language tags, if the argument is not 
   * a non-numeric string, it is passed through
   * 
   * @since 1.9.5.7 eliminated call to gettext
   * 
   * @param string the unstranslated string
   * 
   * @return string the translated string or unaltered input value
   */
  public static function string_static_translation( $string )
  {
    if ( ! is_string( $string ) || is_numeric( $string ) ) {
      return $string;
    }
    
    return self::extract_from_multilingual_string( $string );
  }
  
  /**
   * extracts a language string from a multilingual string
   * 
   * this assumes a Q-TranslateX style multilingual string
   * 
   * this function is basically a patch to let multilingual strings work on the backend
   * 
   * @param string $ml_string
   * @return string
   */
  private static function extract_from_multilingual_string( $ml_string )
  {
    if ( has_filter( 'pdb-extract_multilingual_string' ) ) {
      /**
       * @filter pdb-extract_multilingual_string
       * @param string the multilingual string
       * @return the extracted string for the current language
       */
      return Participants_Db::apply_filters('extract_multilingual_string', $ml_string );
    }
    
    if ( strpos( $ml_string, '[:' ) === false && strpos( $ml_string, '{:' ) === false ) {
      return $ml_string;
    }
    
    if ( preg_match( '/\[:[a-z]{2}/', $ml_string ) === 1 ) {
      $brace = array( '\[', '\]' );
    } else {
      $brace = array( '\{', '\}' );
    }
    
    $lang = strstr( get_locale(), '_', true );
    
    return preg_filter( '/.*' . $brace[0] . ':' . $lang . '' . $brace[1] . '(([^' . $brace[0] . ']|' . $brace[0] . '[^:])*)(' . $brace[0] . ':.*|$)/s', '$1', $ml_string );
  }

  /**
   * creates a translated key string of the format title (name) where "name" is untranslated
   * 
   * @param string $title the title string
   * @param string $name the name string
   * 
   * @return string the translated title with the untranslated name added (if supplied)
   */
  public static function title_key( $title, $name = '' )
  {
    if ( empty( $name ) ) {
      return Participants_Db::apply_filters( 'translate_string', $title );
    }
    return sprintf( '%s (%s)', self::apply_filters( 'translate_string', $title ), $name );
  }

  /**
   * provides a plugin setting
   * 
   * @param string $name setting name
   * @param string|int|float $default a default value
   * @return string the plugin setting value or provided default
   */
  public static function plugin_setting( $name, $default = false )
  {
    if ( $default === false ) {
      $default = self::plugin_setting_default($name);
    }
    return self::apply_filters( 'translate_string', self::plugin_setting_value( $name, $default ) );
  }

  /**
   * provides a plugin setting
   * 
   * this one does not send the value through the translation filter
   * 
   * @param string $name setting name
   * @param string|int|float $default a default value
   * @return string the plugin setting value or provided default
   */
  public static function plugin_setting_value( $name, $default = false )
  {
    if ( $default === false ) {
      $default = self::plugin_setting_default($name);
    }
    /**
     * @filter pdb-{$setting_name}_setting_value
     * @param mixed the setting value
     * @return mixed setting value
     */
    return self::apply_filters( $name . '_setting_value', ( isset( Participants_Db::$plugin_options[$name] ) ? Participants_Db::$plugin_options[$name] : $default ) );
  }
  
  /**
   * provides the default setting for an option
   * 
   * @param string $name of the option
   * @return string|bool the option's default value, bool false if no default is set
   */
  public static function plugin_setting_default( $name )
  {
    $defaults = get_option( Participants_Db::$default_options );
    
    return isset( $defaults[$name ] ) ? $defaults[$name ] : false;
  }

  /**
   * checks a plugin setting for a saved value
   * 
   * returns false for empty string, true for 0
   * 
   * @param string $name setting name
   * @return bool false true if the setting has been saved by the user
   */
  public static function plugin_setting_is_set( $name )
  {
    return isset( Participants_Db::$plugin_options[$name] ) && strlen( Participants_Db::plugin_setting( $name ) ) > 0;
  }

  /**
   * provides a boolean plugin setting value
   * 
   * @param string $name of the setting
   * @param bool the default value
   * @return bool the setting value
   */
  public static function plugin_setting_is_true( $name, $default = false )
  {
    if ( $default === false ) {
      $default = self::plugin_setting_default($name);
    }
    
    if ( isset( Participants_Db::$plugin_options[$name] ) ) {
      return filter_var( self::plugin_setting_value( $name ), FILTER_VALIDATE_BOOLEAN );
    } else {
      return (bool) $default;
    }
  }

  /**
   * sets up an API filter
   * 
   * determines if a filter has been set for the given tag, then either filters 
   * the term or returns it unaltered
   * 
   * this function also allows for two extra parameters
   * 
   * @param string $slug the base slug of the plugin API filter
   * @param unknown $term the term to filter
   * @param unknown $var1 extra variable
   * @param unknown $var2 extra variable
   * @return unknown the filtered or unfiltered term
   */
  public static function set_filter( $slug, $term, $var1 = NULL, $var2 = NULL )
  {
    $slug = self::add_prefix( $slug );
    if ( !has_filter( $slug ) ) {
      return $term;
    }
    return apply_filters( $slug, $term, $var1, $var2 );
  }

  /**
   * sets up an API filter
   * 
   * alias for Participants_Db::set_filter()
   * 
   * @param string $slug the base slug of the plugin API filter
   * @param unknown $term the term to filter
   * @param unknown $var1 extra variable
   * @param unknown $var2 extra variable
   * @return unknown the filtered or unfiltered term
   */
  public static function apply_filters( $slug, $term, $var1 = NULL, $var2 = NULL )
  {
    return self::set_filter( $slug, $term, $var1, $var2 );
  }

  /**
   * triggers an action
   * 
   * @param string $slug the base slug of the plugin API filter
   * @param unknown $term the term to filter
   * @param unknown $var1 extra variable
   * @param unknown $var2 extra variable
   * @return unknown the filtered or unfiltered term
   */
  public static function do_action( $slug, $term, $var1 = NULL, $var2 = NULL )
  {
    do_action( self::add_prefix( $slug ), $term, $var1, $var2 );
  }

  /**
   * provides a prefixed slug
   * 
   * @param string  $slug the paybe-prefixed slug
   * @return string the prefixed slug
   */
  public static function add_prefix( $slug )
  {
    return strpos( $slug, Participants_Db::$prefix ) !== 0 ? Participants_Db::$prefix . $slug : $slug;
  }

  /**
   * writes the admin side custom CSS setting to the custom css file
   * 
   * @return bool true if the css file can be written to
   * 
   */
  protected static function _set_admin_custom_css()
  {
    return self::_setup_custom_css( Participants_Db::$plugin_path . '/css/PDb-admin-custom.css', 'custom_admin_css' );
  }

  /**
   * writes the custom CSS setting to the custom css file
   * 
   * @return bool true if the css file can be written to
   * 
   */
  protected static function _set_custom_css()
  {
    return self::_setup_custom_css( Participants_Db::$plugin_path . '/css/PDb-custom.css', 'custom_css' );
  }

  /**
   * writes the custom CSS setting to the custom css file
   * 
   * @param string  $stylesheet_path absolute path to the stylesheet
   * @param string  $setting_name name of the setting to use for the stylesheet content
   * 
   * @return bool true if the css file can be written to
   * 
   */
  protected static function _setup_custom_css( $stylesheet_path, $setting )
  {
    if ( !is_writable( $stylesheet_path ) ) {
      return false;
    }
    $file_contents = file_get_contents( $stylesheet_path );
    $custom_css = Participants_Db::plugin_setting( $setting );
    if ( $file_contents === $custom_css ) {
      // error_log(__METHOD__.' CSS settings are unchanged; do nothing');
    } else {
      file_put_contents( $stylesheet_path, $custom_css );
    }
    return true;
  }
  
  /**
   * provides a CSS dimension value with units
   * 
   * defaults to pixels, checks for a valid unit
   * 
   * @param string $value
   * @return string
   */
  public static function css_dimension_value( $value )
  {
    $keyword_check = preg_match( '#^(auto|inherit)$#', $value );
    
    if ( $keyword_check === 1 ) {
      return $value;
    }
    
    $fallback = preg_replace( "/[^0-9]/", "", $value ) . 'px';
    
    $value = str_replace( ' ', '', $value ); // remove any spaces
    
    $check = preg_match('/^[0-9]+.?([0-9]+)?(px|em|rem|ex|ch|%|lh|vw|vh|vmin|vmax)$/', $value );
    
    return $check === 1 ? $value : $fallback;
  }

  /**
   * supplies an image/file upload location
   * 
   * relative to WP root
   * 
   * @global  wpdb  $wpdb
   * 
   * @return string relative path to the plugin files location
   */
  public static function files_location()
  {
    $base_path = Participants_Db::plugin_setting( 'image_upload_location', 'wp-content/uploads/' . Participants_Db::PLUGIN_NAME . '/' );

    // multisite compatibility
    global $wpdb;
    if ( isset( $wpdb->blogid ) && $wpdb->blogid > 1 ) {
      $base_path = str_replace( Participants_Db::PLUGIN_NAME, 'sites/' . $wpdb->blogid . '/' . Participants_Db::PLUGIN_NAME, $base_path );
    }

    /**
     * @version 1.6.0
     * filter: pdb-files_location
     * 
     * allows access to the "image_upload_location" plugin setting value
     */
    return Participants_Db::apply_filters( 'files_location', $base_path );
  }

  /**
   * provides the base absolute path for files uploads
   * 
   * @return string
   */
  public static function base_files_path()
  {
    return Participants_Db::apply_filters( 'files_use_content_base_path', false ) ? trailingslashit( WP_CONTENT_DIR ) : self::app_base_path();
  }

  /**
   * provides the base URL for file and image uploads
   * 
   * @return string
   */
  public static function base_files_url()
  {
    return Participants_Db::apply_filters( 'files_use_content_base_path', false ) ? trailingslashit( content_url() ) : self::app_base_url();
  }

  /**
   * supplies the absolute path to the files location
   * 
   * @return string
   */
  public static function files_path()
  {
    return trailingslashit( PDb_Image::concatenate_directory_path( self::base_files_path(), Participants_Db::files_location() ) );
  }

  /**
   * supplies the URI to the files location
   * 
   * @return string
   */
  public static function files_uri()
  {
    return self::base_files_url() . trailingslashit( ltrim( Participants_Db::files_location(), DIRECTORY_SEPARATOR ) );
  }

  /**
   * deletes a file
   * 
   * @param string $filename
   * @return bool success
   */
  public static function delete_file( $filename )
  {
    /**
     * provides a way to override the delete method: if the filter returns bool 
     * true of false, the normal delete method will be skipped. If the filter returns 
     * a string, the string will be treated as the filename to delete
     * 
     * @since 1.7.6.2
     * @filter pdb-delete_file
     * @param string filename
     * @return string|bool filename or bool false to skip deletion
     */
    $result = self::apply_filters( 'delete_file', $filename );

    if ( !is_bool( $result ) ) {
      $current_dir = getcwd(); // save the current dir
      chdir( self::files_path() ); // set the plugin uploads dir
      $result = @unlink( $filename ); // delete the file
      chdir( $current_dir ); // change back to the previous directory
    }
    return $result;
  }

  /**
   * makes a title legal to use in anchor tag
   */
  public static function make_anchor( $title )
  {
    return str_replace( ' ', '', preg_replace( '#^[0-9]*#', '', strtolower( $title ) ) );
  }

  /**
   * checks if the current user's form submissions are to be validated
   * 
   * @return bool true if the form should be validated 
   */
  public static function is_form_validated()
  {
    if ( is_admin() && ! self::plugin_setting_is_true( 'admin_edits_validated', false) ) {
      
      return self::current_user_has_plugin_role( 'admin', 'forms not validated' ) === false;
    } else {
      
      return true;
    }
  }

  /**
   * replace the tags in text messages
   * 
   * provided for backward compatibility
   *
   * returns the text with the values replacing the tags
   * all tags use the column name as the key string
   *
   * @param  string  $text           the text containing tags to be replaced with 
   *                                 values from the db
   * @param  int     $participant_id the record id to use
   * @param  string  $mode           unused
   * @return string                  text with the tags replaced by the data
   */
  public static function proc_tags( $text, $participant_id, $mode = '' )
  {
    return PDb_Tag_Template::replaced_text( $text, $participant_id );
  }

  /**
   * clears empty elements out of an array
   * 
   * leaves "zero" values in
   * 
   * @param array $array the input
   * @return array the cleaned array
   */
  public static function cleanup_array( $array )
  {
    return array_filter( $array, function($v) {
      return $v || $v === 0 || $v === '0';
    } );
  }

  /**
   * recursively merges two arrays, overwriting matching keys
   *
   * if any of the array elements are an array, they will be merged with an array
   * with the same key in the base array
   *
   * @param array $array    the base array
   * @param array $override the array to merge
   * @return array
   */
  public static function array_merge2( $array, $override )
  {
    $x = array();
    foreach ( $array as $k => $v ) {
      if ( isset( $override[$k] ) ) {
        if ( is_array( $v ) ) {
          $v = Participants_Db::array_merge2( $v, (array) $override[$k] );
        } else
          $v = $override[$k];
        unset( $override[$k] );
      }
      $x[$k] = $v;
    }
    // add in the remaining unmatched elements
    return $x += $override;
  }

  /**
   * validates a time stamp
   *
   * @param mixed $timestamp the string to test
   * @return bool true if valid timestamp
   */
  public static function is_valid_timestamp( $timestamp )
  {
    return is_int( $timestamp ) or ( (string) (int) $timestamp === $timestamp);
  }

  /**
   * translates a PHP date() format string to a jQuery format string
   * 
   * @param string $PHP_date_format the date format string
   *
   */
  static function get_jqueryUI_date_format( $PHP_date_format = '' )
  {

    $dateformat = empty( $PHP_date_format ) ? get_option( 'date_format' ) : $PHP_date_format;

    return xnau_Date_Format_String::to_jQuery( $dateformat );
  }

  /**
   * returns the PHP version as a float
   *
   */
  function php_version()
  {

    $numbers = explode( '.', phpversion() );

    return (float) ( $numbers[0] + ( $numbers[1] / 10 ) );
  }
  
  /**
   * tells if the current operation is in the WP admin side
   * 
   * this won't give a positive for ajax calls
   * 
   * @return bool true if in the admin side
   */
  public static function is_admin()
  {
    return is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
  }

  /**
   * sets an admin area error message
   * 
   * @param string $message the message to be dislayed
   * @param string $type the type of message:
   *    error - red
   *    warning - yellow
   *    success - green
   *    info - blue
   */
  public static function set_admin_message( $message, $type = 'error' )
  {
    if ( is_admin() ) {
      switch ( $type ) {
        // this is to translate some legacy values
        case 'updated':
          $type = 'success';
      }
      Participants_Db::$session->set( 'admin_message', array($message, $type) );
      Participants_Db::$admin_message = $message;
      Participants_Db::$admin_message_type = $type;
    }
  }

  /**
   * prints the admin message
   */
  public static function admin_message()
  {
    if ( Participants_Db::$session->get( 'admin_message' ) ) {
      list(Participants_Db::$admin_message, Participants_Db::$admin_message_type) = Participants_Db::$session->get( 'admin_message' );
      $class = Participants_Db::$admin_message_type === 'error' ? 'notice notice-error' : 'notice notice-success';
      if ( !empty( Participants_Db::$admin_message ) ) {
        printf( '<div class="%s is-dismissible"><p>%s</p></div>', $class, Participants_Db::$admin_message );
        Participants_Db::$session->clear( 'admin_message' );
      }
    }
  }
  
  /**
   * displays a warning message if the php version is too low
   * 
   */
  protected static function php_version_warning()
  {
    $target_version = '5.6';

    if ( version_compare( PHP_VERSION, $target_version, '<' ) && ! get_option( Participants_Db::one_time_notice_flag ) ) {
      
      PDb_Admin_Notices::post_warning('<p><span class="dashicons dashicons-warning"></span>' . sprintf( __( 'Participants Database will require PHP version %1$s in future releases, you have PHP version %2$s. Please update your php version, future versions of Participants Database may not run without minimum php version %1$s', 'participants-database' ), $target_version, PHP_VERSION ) . '</p>', '', false);
      
      // mark the option as shown
      update_option(Participants_Db::one_time_notice_flag, true);
      
    }
  }

  /**
   * gets the PHP timezone setting
   * 
   * @return string
   */
  public static function get_timezone()
  {
    $php_timezone = ini_get( 'date.timezone' );
    return empty( $php_timezone ) ? 'UTC' : $php_timezone;
  }

  /**
   * collect a list of all the plugin shortcodes present in the content
   *
   * @param string $content the content to test
   * @param string $tag
   * @return array of plugin shortcode tags
   */
  public static function get_plugin_shortcodes( $content = '', $tag = '[pdb_' )
  {

    $shortcodes = array();
    // get all shortcodes
    preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
    // if no shortcodes, return empty array
    if ( empty( $matches ) )
      return array();
    // check each one for a plugin shortcode
    foreach ( $matches as $shortcode ) {
      if ( false !== strpos( $shortcode[0], $tag ) ) {
        $shortcodes[] = $shortcode[2] . '-shortcode';
      }
    }
    return $shortcodes;
  }

  /**
   * check a string for a shortcode
   *
   * modeled on the WP function of the same name
   * 
   * what's different here is that it will return true on a partial match so it can 
   * be used to detect any of the plugin's shortcode. Generally, we just check for 
   * the common prefix
   *
   * @param string $content the content to test
   * @param string $tag
   * @return boolean
   */
  public static function has_shortcode( $content = '', $tag = '[pdb_' )
  {

    // get all shortcodes
    preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
    // none found
    if ( empty( $matches ) )
      return false;
    
    // check each one for a plugin shortcode
    foreach ( $matches as $shortcode ) {
      if ( false !== strpos( $shortcode[0], $tag ) && false === strpos( $shortcode[0], '[[' ) ) {
        return true;
      }
    }
    return false;
  }

  /**
   * sets the shortcode present flag if a plugin shortcode is found in the post
   * 
   * runs on the 'wp' filter
   * 
   * @global object $post
   * @return array $posts
   */
  public static function remove_rel_link()
  {

    global $post;
    /*
     * this is needed to prevent Firefox prefetching the next page and firing the damn shortcode
     * 
     * as per: http://www.ebrueggeman.com/blog/wordpress-relnext-and-firefox-prefetching
     */
    if ( is_object( $post ) && $post->post_type === 'page' ) {
      remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
    }
  }

  /**
   * provides an array of field indices corresponding, given a list of field names
   * 
   * or vice versa if $indices is false
   * 
   * @param array $fieldnames the array of field names
   * @param bool  $indices if true returns array of indices, if false returns array of fieldnames
   * @return array an array of integers
   */
  public static function get_field_indices( $fieldnames, $indices = true )
  {
    global $wpdb;
    $sql = 'SELECT f.' . ($indices ? 'id' : 'name') . ' FROM ' . Participants_Db::$fields_table . ' f ';
    $sql .= 'WHERE f.' . ($indices ? 'name' : 'id') . ' ';
    if ( count( $fieldnames ) > 1 ) {
      $sql .= 'IN ("' . implode( '","', $fieldnames );
      if ( count( $fieldnames ) < 100 ) {
        $sql .= '") ORDER BY FIELD(f.name, "' . implode( '","', $fieldnames ) . '")';
      } else {
        $sql .= '") ORDER BY f.' . ($indices ? 'id' : 'name') . ' ASC';
      }
    } else {
      $sql .= '= "' . current( $fieldnames ) . '"';
    }
    return $wpdb->get_col( $sql );
  }

  /**
   * provides a list of field names, given a list of indices
   * 
   * @param array $ids of integer ids
   * @return array of field names
   * 
   */
  public static function get_indexed_names( $ids )
  {
    return self::get_field_indices( $ids, false );
  }

  /**
   * gets a list of column names from a dot-separated string of ids
   * 
   * @param string $ids the string of ids
   * @return array of field names
   */
  public static function get_shortcode_columns( $ids )
  {
    return self::get_field_indices( explode( '.', $ids ), false );
  }

  /**
   * provides a filter array for a search submission
   * 
   * filters a POST submission for displaying a list
   * 
   * @param bool $multi if true, filter a multi-field search submission
   * @return array of filter parameters
   */
  public static function search_post_filter( $multi = false )
  {
    $array_filter = array(
        'filter' => FILTER_SANITIZE_STRING,
        'flags' => FILTER_FORCE_ARRAY
    );
    $multi_validation = $multi ? $array_filter : FILTER_SANITIZE_STRING;
    return array(
        'filterNonce' => FILTER_SANITIZE_STRING,
        'postID' => FILTER_VALIDATE_INT,
        'submit' => FILTER_SANITIZE_STRING,
        'action' => FILTER_SANITIZE_STRING,
        'instance_index' => FILTER_VALIDATE_INT,
        'target_instance' => FILTER_VALIDATE_INT,
        'pagelink' => FILTER_SANITIZE_STRING,
        'sortstring' => FILTER_SANITIZE_STRING,
        'orderstring' => FILTER_SANITIZE_STRING,
        'search_field' => $multi_validation,
        'operator' => $multi_validation,
        'value' => $multi_validation,
        'logic' => $multi_validation,
        'sortBy' => FILTER_SANITIZE_STRING,
        'ascdesc' => FILTER_SANITIZE_STRING,
        Participants_Db::$list_page => FILTER_VALIDATE_INT,
    );
  }
  
  /**
   * provides a list of orphaned field columns in the main db
   * 
   * @global wpdb $wpdb
   * @return array of field names
   */
  public static function orphaned_db_columns()
  {
    global $wpdb;
    $columns = $wpdb->get_results( 'SHOW COLUMNS FROM ' . Participants_Db::$participants_table );
    
    $orphan_columns = array();
    
    foreach( $columns as $column ) {
      if ( !array_key_exists( $column->Field, Participants_Db::$fields ) ) {
        $orphan_columns[] = $column->Field;
      }
    }
    
    return $orphan_columns;
  }
  
  /**
   * provides a general cache expiration time
   * 
   * this is to prevent persistent caches from holding on to the cached values too long
   * 
   * this is tuned to generously cover a single page load
   * 
   * @return int cache valid time in seconds
   */
  public static function cache_expire()
  {
    return Participants_Db::apply_filters( 'general_cache_expiration', 10 );
  }

  /**
   * clears the shortcode session for the current page
   * 
   * 
   * shortcode sessions are used to provide asynchronous functions with the current 
   * shortcode attributes
   */
  public static function reset_shortcode_session()
  {
    global $post;
    if ( is_object( $post ) ) {
      $current_session = Participants_Db::$session->getArray( 'shortcode_atts' );
      /*
       * clear the current page's session
       */
      $current_session[$post->ID] = array();
      Participants_Db::$session->set( 'shortcode_atts', $current_session );
    }
  }
  
  /**
   * checks for the presence of the WP Session plugin
   * 
   * @return bool true if the plugin is present
   */
  public static function wp_session_plugin_is_active()
  {
    $plugins = get_option('active_plugins');
    return in_array('wp-session-manager/wp-session-manager.php', $plugins);
  }

  /**
   * determines if the current form status is a kind of multipage
   * 
   * @return bool true if the form is part of a multipage form
   */
  public static function is_multipage_form()
  {
    $form_status = Participants_Db::$session->get( 'form_status' );
    
    return stripos( $form_status, 'multipage' ) !== false;
  }

  /**
   * Remove slashes from strings, arrays and objects
   * 
   * @param    mixed   input data
   * @return   mixed   cleaned input data
   */
  public static function deep_stripslashes( $input )
  {
    if ( is_array( $input ) ) {
      $input = array_map( array(__CLASS__, 'deep_stripslashes'), $input );
    } elseif ( is_object( $input ) ) {
      $vars = get_object_vars( $input );
      foreach ( $vars as $k => $v ) {
        $input->{$k} = deep_stripslashes( $v );
      }
    } else {
      $input = stripslashes( $input );
    }
    return $input;
  }

  /**
   * performs a fix for some older versions of the plugin; does nothing with current plugins
   */
  public static function reg_page_setting_fix()
  {
    // if the setting was made in previous versions and is a slug, convert it to a post ID
    $regpage = isset( Participants_Db::$plugin_options['registration_page'] ) ? Participants_Db::$plugin_options['registration_page'] : '';
    if ( !empty( $regpage ) && !is_numeric( $regpage ) ) {

      Participants_Db::$plugin_options['registration_page'] = self::get_id_by_slug( $regpage );

      update_option( Participants_Db::$participants_db_options, Participants_Db::$plugin_options );
    }
  }

  /**
   * gets the ID of a page given it's slug
   *
   * this is to provide backwards-compatibility with previous versions that used a page-slug to point to the [pdb_record] page.
   * 
   * @global object $wpdb
   * @param string $page_slug slug or ID of a page or post
   * @param string $post_type name of the post type; defualts to page
   * @return string|bool the post ID; bool false if nothing found
   */
  public static function get_id_by_slug( $page_slug, $post_type = 'page' )
  {
    if ( is_numeric( $page_slug ) ) {
      $post = get_post( $page_slug );
    } else {
      $post = get_page_by_path( $page_slug );
    }

    if ( is_a( $post, 'WP_Post' ) ) {
      return $post->ID;
    }

    // fallback method
    global $wpdb;
    $id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s AND post_status = 'publish'", $page_slug, $post_type ) );

    return empty( $id ) ? false : $id;
  }

  /**
   * encodes or decodes a string using a simple XOR algorithm
   * 
   * @param string $string the tring to be encoded/decoded
   * @param string $key the key to use
   * @return string
   */
  public static function xcrypt( $string, $key = false )
  {
    if ( $key === false ) {
      $key = self::get_key();
    }
    $text = $string;
    $output = '';
    for ( $i = 0; $i < strlen( $text ); ) {
      for ( $j = 0; ($j < strlen( $key ) && $i < strlen( $text ) ); $j++, $i++ ) {
        $output .= $text{$i} ^ $key{$j};
      }
    }
    return $output;
  }

  /**
   * supplies a random alphanumeric key
   * 
   * the key is stored in a transient which changes every day
   * 
   * @return null
   */
  public static function get_key()
  {
    if ( !$key = Participants_Db::$session->get( PDb_CAPTCHA::captcha_key ) ) {
      $key = self::generate_key();
      Participants_Db::$session->set( PDb_CAPTCHA::captcha_key, $key );
    }
    //$key = Participants_Db::$session->get( PDb_CAPTCHA::captcha_key);
    //error_log(__METHOD__.' get new key: '.$key);
    return $key;
  }

  /**
   * returns a random alphanumeric key
   * 
   * @param int $length number of characters in the random string
   * @return string the randomly-generated alphanumeric key
   */
  private static function generate_key( $length = 8 )
  {

    $alphanum = self::get_alpha_set();
    $key = '';
    while ( $length > 0 ) {
      $key .= $alphanum[array_rand( $alphanum )];
      $length--;
    }
    return $key;
  }

  /**
   * supplies an alphanumeric character set for encoding
   * 
   * characters that would mess up HTML are not included
   * 
   * @return array of valid characters
   */
  private static function get_alpha_set()
  {
    return str_split( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.{}[]_-=+!@#$%^&*()~`' );
  }

  /**
   * decodes the pdb_data_keys value
   * 
   * this provides a security measure by defining which fields to process in a form submission
   * 
   * @param string $datakey the pdb_data_key value
   * 
   * @return array of column names
   */
  public static function get_data_key_columns( $datakey )
  {

    return self::get_indexed_names( explode( '.', $datakey ) );
//    return self::get_indexed_names( explode('.', self::xcrypt($datakey)));
  }

  /**
   * sets the debug mode
   * 
   * plugin debuggin is going to be enabled if the debug setting is enabled, 
   * or if WP_DEBUG is true
   * 
   * 
   * @global PDb_Debug $PDb_Debugging
   */
  protected static function set_debug_mode()
  {
    global $PDb_Debugging;
    if ( !defined( 'PDB_DEBUG' ) ) {
      $settings = get_option( Participants_Db::PLUGIN_NAME . '_options');
      if ( isset( $settings['pdb_debug'] ) ) {
        define( 'PDB_DEBUG', intval($settings['pdb_debug']) );
      } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        define( 'PDB_DEBUG', 1 );
      } else {
        define( 'PDB_DEBUG', 0 );
      }
    }
    
    if ( PDB_DEBUG > 0 && ! defined('WP_DEBUG') ) {
      define( 'WP_DEBUG', true );
    }
      
    if ( PDB_DEBUG && ! is_a( $PDb_Debugging, 'PDb_Debug' ) ) {
      $PDb_Debugging = new PDb_Debug();
    }
  }
  
  /**
   * writes a debug log message
   * 
   * @global PDb_Debug $PDb_Debugging
   * @param string $message the debugging message
   */
  public static function debug_log( $message )
  {
    global $PDb_Debugging;
    if ( $PDb_Debugging && method_exists( $PDb_Debugging, 'write_debug' ) ) {
      $PDb_Debugging->write_debug($message);
    } else {
      error_log( $message );
    }
  }
  
  /**
   * provides the user's IP
   * 
   * this function provides a filter so that a different method can be used if the 
   * site is behind a proxy, firewall, etc.
   * 
   * @return string IP
   */
  public static function user_ip()
  {
    return self::apply_filters('user_ip', $_SERVER['REMOTE_ADDR']);
  }

}
