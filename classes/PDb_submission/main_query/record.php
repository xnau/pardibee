<?php

/**
 * maintains the query for the main record insert or update
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

namespace PDb_submission\main_query;

abstract class record {
  
  /**
   * @var array of column values
   */
  protected $values;
  
  /**
   * @var array of column clause strings
   */
  protected $column_clauses;
  
  /**
   * @var array the posted data
   */
  protected $post;
  
  /**
   * @var int holds the record id
   */
  protected $record_id;
  
  /**
   * @var bool tells if the current request is a CSV import
   */
  protected $is_import;
  
  /**
   * @var bool tells if the current request is a CSV import
   */
  protected $is_func_call;
  
  /**
   * @var PDb_submission\main_query\record
   */
  private static $instance = null;
  
  /**
   * provides the class instance
   * 
   * @param string $type update, insert or skip
   * @param array $post
   * @param int $record_id
   * @param bool $func_call true if the submission is a function call
   * @return PDb_submission\main_query\record
   */
  public static function set_instance( $type, $post, $record_id, $func_call  )
  {
    if ( self::$instance === null ) {
      $class = 'PDb_submission\main_query\\' . $type;
      self::$instance = new $class( $post, $record_id, $func_call );
    }
    
    return self::$instance;
  }
  
  /**
   * provides the class instance without specifying parameters
   * 
   * @return  PDb_submission\main_query\record
   */
  public static function get_instance()
  {
    return self::$instance;
  }
  
  /**
   * @param array $post
   * @param int $record_id
   * @param array|bool $column_names the supplied column names if any
   */
  public function __construct( $post, $record_id, $column_names )
  {
    $this->post = $post;
    $this->record_id = $record_id;
    $this->is_import = isset( $_POST['csv_file_upload'] );
    $this->is_func_call = is_array( $column_names );
  }
  
  /**
   * provides the query top clause
   * 
   * @return string
   */
  abstract protected function top_clause();
  
  /**
   * provides the query where clause
   * 
   * @return string
   */
  abstract protected function where_clause();
  
  /**
   * provides the name of the query mode: update or insert
   * 
   * @return string
   */
  abstract protected function query_mode();
  
  /**
   * provides the current query mode
   * 
   * @return string update or insert
   */
  public function write_mode()
  {
    return $this->query_mode();
  }

  /**
   * provides the column object array
   * 
   * @param array $function_columns
   * @return array
   */
  public function column_array( $function_columns )
  {
    return columns::column_array( $this->query_mode(), (bool) $this->record_id, $function_columns );
  }
  
  /**
   * provides the completed query
   * 
   * @return string
   */
  public function query()
  {
    return $this->top_clause() . $this->data_clause() . $this->where_clause();
  }
  
  /**
   * provides the query header
   * 
   * @return string
   */
  public function query_head()
  {
    return $this->top_clause();
  }
  
  /**
   * provides the query header
   * 
   * @return string
   */
  public function query_where()
  {
    return $this->where_clause();
  }
  
  /**
   * executes the query
   */
  public function execute_query()
  {
    return $wpdb->query( $this->sanitized_query() );
  }
  
  /**
   * provides the record ID
   * 
   * @return int
   */
  public function record_id()
  {
    return $this->record_id;
  }
  
  /**
   * tells if the request is an import
   * 
   * @return bool
   */
  public function is_import()
  {
    return $this->is_import;
  }
  
  /**
   * tells if the current request is a function call
   * 
   * @return bool
   */
  public function is_func_call()
  {
    return $this->is_func_call;
  }
  
  /**
   * provides the data body of the query
   * 
   * @return string
   */
  protected function data_clause()
  {
    
  }
  
  /**
   * provides the default record values
   * 
   * this also includes persistent values
   * 
   * @return array as $fieldname => $default_value
   */
  protected function default_record()
  {
    return \Participants_Db::get_default_record( $this->is_import === false );
  }
  
  /**
   * provides the default value for the named field
   * 
   * @param string $fieldname
   * @return string
   */
  public function default_value( $fieldname )
  {
    return isset( $this->default_record()[$fieldname] ) ? $this->default_record()[$fieldname] : '';
  }
  
  /**
   * provides the sanitized query
   * 
   * @global \wpdb $wpdb
   * @return string
   */
  protected function sanitized_query()
  {
    global $wpdb;
    
    $query = $this->query();
    
    if ( strpos( $query, '%s' ) !== false ) {
      return $wpdb->prepare($query);
    }
    
    return $query;
  }
  
  
}
