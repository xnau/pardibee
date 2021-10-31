<?php

/**
 * handles all files uploads
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2017  xnau webdesign
 * @license    GPL3
 * @version    0.7
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
defined( 'ABSPATH' ) || die( '-1' );

class PDb_File_Uploads {

  /**
   * handles the submission uploads
   * 
   * @param array $post the submitted data
   * @return array $post with updated filenames
   */
  public static function process_submission_uploads( $post )
  {
    // don't process a CSV import
    if ( \PDb_submission\main_query\base_query::is_csv_import() || empty( $_FILES ) || ! isset( $post['id'] ) ) {
      return $post;
    }
    
    $upload = new self();

    foreach ( $_FILES as $fieldname => $attributes ) {

      if ( UPLOAD_ERR_NO_FILE == $attributes[ 'error' ] ) {
        continue;
      }

      $filepath = $upload->handle_file_upload( $fieldname, $attributes, $post['id'] );

      if ( false !== $filepath ) {

        // place the path to the file in the field value
        $post[ $fieldname ] = $filepath;

        $_POST[ $fieldname ] = basename( $filepath );
      }
    }
      
    // clear the files array
    $_FILES = array();
    
    return $post;
  }

  /**
   * handle the file upload
   * 
   * @param string  $field_name name of the current field
   * @param array $attributes
   * @param int|bool $record_id id of the current record or bool false new record
   * 
   * @return string|bool the path to the uploaded file or bool false if error
   */
  public static function upload( $field_name, $attributes, $record_id = false )
  {
    $upload = new self();
    return $upload->handle_file_upload( $field_name, $attributes, $record_id );
  }

  /**
   * handles a file upload
   *
   * @param string $name the name of the current field
   * @param array  $file the $_FILES array element corresponding to one file
   * @param int|bool record id if the action is an update
   *
   * @return string|bool the path to the uploaded file or bool false if error
   */
  public function handle_file_upload( $field_name, $file, $id )
  {
    $field_def = new PDb_Form_Field_Def( $field_name );

    $is_image_field = $field_def->form_element() === 'image-upload';

    // attempt to create the target directory if it does not exist
    if ( !is_dir( Participants_Db::files_path() ) ) {

      if ( false === Participants_Db::_make_uploads_dir() ) {
        Participants_Db::debug_log( 'Uploads directory could not be created at: ' . Participants_Db::files_path() );
        return false;
      }
    }

    /* this will fail either because the file size exceeds php configured maximums 
     * or the file itself is not valid for some reason
     */
    if ( !is_uploaded_file( realpath( $file[ 'tmp_name' ] ) ) ) {

      if ( filesize( realpath( $field_file[ 'tmp_name' ][ $i ] ) ) > ini_get( 'upload_max_filesize' ) ) {

        \Participants_Db::validation_error( sprintf( __( 'The file you tried to upload is too large. The file must be smaller than %sK.', 'participants-database' ), ceil( Participants_Db::shorthand_bytes_value( ini_get( 'upload_max_filesize' ) ) / 1000 ) ), $field_name );

        Participants_Db::debug_log( "File size exceeded php configuration limits: " . $file[ 'name' ] );

        return false;
      }

      Participants_Db::validation_error( __( 'There is something wrong with the file you tried to upload. Try another.', 'participants-database' ), $field_name );

      Participants_Db::debug_log( "File upload could not be validated by the server: " . $file[ 'name' ] );

      return false;
    }

    $field_allowed_extensions = $field_def->allowed_extensions();
    $allowed_extensions = empty( $field_allowed_extensions ) ? Participants_Db::global_allowed_extensions() : $field_allowed_extensions;

    if ( !Participants_Db::is_allowed_file_extension( $file[ 'name' ], $allowed_extensions ) ) {

      if ( $is_image_field && empty( $field_allowed_extensions ) ) {
        Participants_Db::validation_error( sprintf( __( 'For "%s", you may only upload image files like JPEGs, GIFs or PNGs.', 'participants-database' ), $field_def->title() ), $field_name );
      } else {
        Participants_Db::validation_error( sprintf( __( 'The file selected for "%s" must be one of these types: %s. ', 'participants-database' ), $field_def->title(), implode( ', ', $allowed_extensions ) ), $field_name );
      }

      Participants_Db::debug_log( "File upload rejected, not of an allowed type: " . $file[ 'name' ] );

      return false;
    } else {

      // validate and construct the new filename using only the allowed file extension
      preg_match( '#^(.+)\.(' . implode( '|', $allowed_extensions ) . ')$#', strtolower( $file[ 'name' ] ), $matches );

      /**
       * @filter pdb-file_upload_filename
       * @param string the sanitized filename (without extension)
       * @param PDb_Form_Field_Def the field definition parameters
       * @param int|bool the record id or bool false if the ID hasn't been determined yet (as in a signup form)
       * @return string filename without it's extension
       */
      $new_filename = Participants_Db::apply_filters( 'file_upload_filename', preg_replace( array( '#\.#', "/\s+/", "/[^-\.\w]+/" ), array( "-", "_", "" ), $matches[ 1 ] ), $field_def, $id ) . '.' . $matches[ 2 ];

      // now make sure the name is unique by adding an index if needed
      $index = 1;
      while ( file_exists( Participants_Db::files_path() . $new_filename ) ) {
        $filename_parts = pathinfo( $new_filename );
        $new_filename = preg_replace( array( '#_[0-9]+$#' ), array( '' ), $filename_parts[ 'filename' ] ) . '_' . $index . '.' . $filename_parts[ 'extension' ];
        $index++;
      }
    }

    if ( $is_image_field ) {

      if ( !PDb_Image::is_image_file( $file[ 'tmp_name' ] ) ) {

        Participants_Db::validation_error( sprintf( __( 'For "%s", you may only upload image files like JPEGs, GIFs or PNGs.', 'participants-database' ), $field_def->title() ), $field_name );

        Participants_Db::debug_log( "Image upload does not validate as an image file: " . $file[ 'name' ] );

        return false;
      }
    }

    if ( $file[ 'size' ] > intval( Participants_Db::plugin_setting_value( 'image_upload_limit' ) ) * 1024 ) {

      Participants_Db::validation_error( sprintf( __( 'The file you tried to upload is too large. The file must be smaller than %sK.', 'participants-database' ), Participants_Db::plugin_setting_value( 'image_upload_limit' ) ), $field_name );

      Participants_Db::debug_log( sprintf( "File upload is too large: %s is %s K bytes.", $file[ 'name' ], round( $file[ 'size' ] / 1024 ) ) );

      return false;
    }

    if ( false === move_uploaded_file( $file[ 'tmp_name' ], Participants_Db::files_path() . $new_filename ) ) {

      Participants_Db::validation_error( __( 'The file could not be saved.', 'participants-database' ) );

      Participants_Db::debug_log( sprintf( "The file %s could not be saved in %s", $file[ 'name' ], Participants_Db::files_path() ) );

      return false;
    }

    Participants_Db::debug_log( sprintf( __METHOD__ . ": The file was successfully uploaded as %s", Participants_Db::files_path() . $new_filename ) );

    return $new_filename;
  }

  /**
   * quick empty test works on arrays and serialized arrays
   * 
   * @param string|array $input
   * @return bool if empty
   */
  public function is_empty( $input )
  {
    $test = implode( '', (array) maybe_unserialize( $input ) );
    return empty( $test );
  }

}
