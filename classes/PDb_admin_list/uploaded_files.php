<?php

/**
 * handles interacting with files associated with records
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

class uploaded_files {

  /**
   * @var array of file names to delete
   */
  private $file_list;
  
  /**
   * @var array list of filtered file types
   */
  private $type_filter;
  
  /**
   * deletes the files associated with a list of record ids
   * 
   * @param array $record_ids
   */
  public static function delete_record_files( $record_ids )
  {
    $delete_files = new self();
    
    $delete_files->set_record_file_list( $record_ids );
    $delete_files->perform_delete();
  }
  
  /**
   * deletes orphaned files in the Participants Database uploads directory
   * 
   * @param string $type_filter
   * @return int number of files deleted
   */
  public static function delete_orphaned_files( $type_filter = '' )
  {
    $delete_files = new self();
    
    $delete_files->setup_type_filter($type_filter);
    
    return $delete_files->_delete_orphaned_files();
  }

  /**
   * 
   */
  private function __construct()
  {
    
  }

  /**
   * deletes all uploaded files for the record
   */
  private function perform_delete()
  {
    foreach( $this->file_list as $filename ) {
      
      if ( empty( $filename ) ) {
        continue;
      }
      
      $filepath = $this->pdb_uploads_directory_path() . $filename;  
      
      if ( is_file( $filepath ) ) {
        
        \Participants_Db::debug_log(' On record delete, deleting file: '. $filepath, 1 );
        unlink( $filepath );
      }
    }
  }

  /**
   * provides a list of upload fields
   * 
   * @global \wpdb $wpdb
   * @return array of field names
   */
  private static function upload_fields()
  {
    global $wpdb;

    $sql = '
      SELECT f.name 
      FROM ' . \Participants_Db::$fields_table . ' f 
        JOIN ' . \Participants_Db::$groups_table . ' g 
          ON f.group = g.name
        WHERE f.form_element IN ("image-upload","file-upload") AND g.mode IN ("admin","private","public")';

    return $wpdb->get_col( $sql );
  }

  /**
   * provides an array of record uploaded file names
   * 
   * @global \wpdb $wpdb
   * @param array $record_ids
   */
  private function set_record_file_list( $record_ids )
  {
    $file_list = array();
    global $wpdb;

    $sql = '
      SELECT p.' . implode( ', p.', self::upload_fields() ) . ' 
      FROM ' . \Participants_Db::$participants_table . ' p 
        WHERE p.id IN (' . implode( ', ', array_fill( 0, count( $record_ids ), '%s' ) ) . ')';
    
    $result = $wpdb->get_results( $wpdb->prepare( $sql, $record_ids ), ARRAY_N );
    
    foreach( $result as $record ) {
      $file_list = array_merge( $file_list, array_filter( array_values($record) ) );
    }
    
    $this->file_list = $file_list;
  }
  
  /**
   * delets all files that are not attached to a record
   * 
   * @return int number of files deleted
   */
  private function _delete_orphaned_files()
  {
    $attached_files = $this->db_file_list();
    $tally = 0;
    
    foreach( $this->uploaded_file_list() as $filename ) {
      
      if ( ! in_array( $filename, $attached_files ) ) {
        
        $filepath = $this->pdb_uploads_directory_path() . $filename;
        
        if ( is_file( $filepath ) ) {
          $deleted = unlink( $filepath );
          $tally += ( $deleted ? 1 : 0 ); 
          \Participants_Db::debug_log(__METHOD__.' deleting: ' . $filepath );
        }
      }
    }
    
    return $tally;
  }
  
  /**
   * provides a list of files found in the database
   * 
   * @global \wpdb $wpdb
   * @return array of filenames
   */
  private function db_file_list()
  {
    global $wpdb;
    
    $sql = 'SELECT p.' . implode( ', p.', self::upload_fields() ) . ' 
      FROM ' . \Participants_Db::$participants_table . ' p
        WHERE CONCAT_WS( "|", p.' . implode( ', p.', self::upload_fields() ) . ') <> ""';
    
    $result = $wpdb->get_results($sql, ARRAY_N );
    
    $file_list = array();
    
    foreach ( $result as $record ) {
      
      $file_list = array_merge( $file_list, array_filter( $record ) );
    }
    
    return $file_list;
  }
  
  /**
   * supplies the list of all uploaded files
   * 
   * this is filtered by the type_filter property
   * 
   * @return array of filenames
   */
  private function uploaded_file_list()
  {
    $raw_scan = scandir( $this->pdb_uploads_directory_path() );
    
    if ( ! empty( $this->type_filter ) ) {
      
      $file_list = array();
      
      foreach( $this->type_filter as $ext ) {
        
        $filtered_list = array_filter( $raw_scan, function($v) use ($ext) {
          return strpos( $v, '.' . $ext ) !== false;
        });
        
        $file_list = array_merge( $file_list, $filtered_list );
      }
      
    } else {
      
      // if we get here all unattached files will be deleted
      $file_list = $raw_scan;
    }
    
    return $file_list;
  }
  
  /**
   * sets up the type filter property
   * 
   * @param string $type_filer
   */
  private function setup_type_filter( $type_filter )
  {
    $this->type_filter = explode( '|', $type_filter );
  }
  
  /**
   * provides the PArticipants Database uploads directory path
   * 
   * @return string path
   */
  private function pdb_uploads_directory_path()
  {
    return trailingslashit( \Participants_Db::files_path() );
  }

}
