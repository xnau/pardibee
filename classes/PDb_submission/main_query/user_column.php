<?php

/**
 * models a database column from any user field group
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.7
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission\main_query;

use \Participants_Db,
    \PDb_Date_Parse;

defined( 'ABSPATH' ) || exit;

class user_column extends base_column {

  /**
   * sets the value property
   * 
   * @global \wpdb $wpdb
   */
  protected function setup_value()
  {
    $initialvalue = $this->initial_value();

    switch ( $this->field->form_element() ) {

      case 'multi-select-other':
      case 'multi-checkbox':
      case 'multi-dropdown':

        $multi_value = \PDb_Field_Item::field_value_array( $initialvalue );

        if ( is_null( $initialvalue ) ) {

          $this->value = null;
        } elseif ( is_array( $multi_value ) ) {

          $this->value = Participants_Db::_prepare_array_mysql( array_values( $multi_value ) );
        }

        break;

      case 'link':

        // don't allow the user to change the clickable text if the hide_clickable attribute is set
        if ( $this->field->has_attribute( 'hide_clickable' ) ) {
          if ( is_array( $initialvalue ) && isset( $initialvalue[ 1 ] ) ) {
            $initialvalue[ 1 ] = '';
          }
        }

        /* translate the link markdown used in CSV files to the array format used in the database
         */
        if ( is_null( $initialvalue ) ) {

          $this->value = null;
        } elseif ( !is_array( $initialvalue ) ) {

          $this->value = Participants_Db::_prepare_array_mysql( Participants_Db::get_link_array( $initialvalue ) );
        } else {

          $this->value = Participants_Db::_prepare_array_mysql( $initialvalue );
        }

        break;

      case 'rich-text':

        global $allowedposttags;
        $this->value = is_null( $initialvalue ) ? null : wp_kses( stripslashes( $initialvalue ), $allowedposttags );
        break;

      case 'date':
      case 'date5':

        if ( $initialvalue !== '' && !is_null( $initialvalue ) ) {

          $this->value = PDb_Date_Parse::timestamp( $initialvalue, array(), __METHOD__ . ' date field value' );
        } elseif ( is_null( $initialvalue ) ) {
          $this->value = null;
        } else {
          $this->skip = true;
        }
        break;

      case 'captcha':

        $this->value = $initialvalue;
        $this->skip = true;
        break;

      case 'password':

        if ( !empty( $initialvalue ) && Participants_Db::is_new_password( $initialvalue ) ) {

          $this->value = wp_hash_password( trim( $initialvalue ) );
        } else {

          $this->skip = true;
        }
        break;

      case 'numeric':
      case 'decimal':
      case 'currency':
      case 'numeric-calc':

        if ( strlen( $initialvalue ) > 0 ) {

          $this->value = filter_var( $initialvalue, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
        } else {

          $this->value = null;
        }
        break;

      case 'image-upload':
      case 'file-upload':

        $this->value = is_null( $initialvalue ) ? null : Participants_Db::_prepare_string_mysql( trim( $initialvalue ) );

        $participant_id = $this->main_query()->record_id();

        if ( $this->file_delete_enabled() && $participant_id ) {

          global $wpdb;
          $record_filename = $wpdb->get_var( $wpdb->prepare( 'SELECT `' . $this->field->name() . '` FROM ' . Participants_Db::participants_table() . ' WHERE id = %s', $participant_id ) );
          
          $delete_checkbox = filter_input( INPUT_POST, $this->field->name() . '-deletefile', FILTER_SANITIZE_SPECIAL_CHARS ) === 'delete';
          $filename = '';

          if ( array_key_exists( $this->field->name(), $_POST ) ) { // it's a record update deleting the previously uploaded file
            
            $post_filename = filter_var( $_POST[$this->field->name()], FILTER_SANITIZE_SPECIAL_CHARS );
            
            if ( ! empty( $record_filename ) && $post_filename !== $record_filename && Participants_Db::plugin_setting_is_true( 'file_delete' ) ) {
              
              Participants_Db::debug_log(__METHOD__.' deleting overwrite '. $record_filename, 2 );
              Participants_Db::delete_file( $record_filename );
              
            } elseif ( $post_filename === $record_filename && $delete_checkbox ) {

              if ( Participants_Db::plugin_setting_is_true( 'file_delete' ) ) {
                Participants_Db::debug_log(__METHOD__.' deleting empty '. $record_filename, 2 );
                Participants_Db::delete_file( $record_filename );
              }
              
              unset( $_POST[ $this->field->name() ] );
              $this->value = null;
            }
            
          } else { // it's an import deleting the field value
            Participants_Db::delete_file( $record_filename );
          }
        }
        break;

      default:

        if ( is_null( $initialvalue ) ) {

          $this->value = null;
        } elseif ( is_array( $initialvalue ) ) {

          $this->value = Participants_Db::_prepare_array_mysql( $initialvalue );
        } else {

          $this->value = Participants_Db::_prepare_string_mysql( trim( $initialvalue ) );
        }
    }
  }

  /**
   * provides the column value
   * 
   * @return string|int|bool
   */
  public function validation_value()
  {
    switch ( $this->field->form_element() ) {
      case 'password':

        $value = $this->raw_value;
        break;

      default:
        $value = $this->value;
    }
    return $value;
  }

  /**
   * tells if the uploaded file should be deleted
   * 
   * @return bool true to delete the uploaded file
   */
  private function file_delete_enabled()
  {
    $delete_file = \Participants_Db::plugin_setting( 'file_delete', 0 );

    $import_null = $this->main_query()->is_import() && is_null( $this->value );

    return $delete_file || $import_null;
  }

  /**
   * tells if the incoming value should be added to the query
   * 
   * @param string $write_mode insert or update
   * @return bool true to add the column to the main query
   */
  public function add_to_query( $write_mode )
  {
    $add = !$this->skip && !$this->skip_imported_value();

    if ( $add && $write_mode === 'insert' && ( $this->value() === '' || is_null( $this->value() ) ) ) {
      $add = false;
    }
    
    return $add;
  }

  /**
   * provides the base value to use
   * 
   * @return string
   */
  private function initial_value()
  {
    if ( $this->field->is_date_field() && $this->raw_value === '' ) {
      $this->raw_value = 'null';
    }
    
    $initialvalue = $this->raw_value === 'null' ? null : $this->raw_value;

    if ( !$this->add_to_query( 'any' ) ) {

      // possibly use the default value
      $defaultvalue = $this->main_query()->default_value( $this->field->name() );

      if ( \Participants_Db::is_set_value( $defaultvalue ) ) {
        $initialvalue = $defaultvalue;
      }
    }

    return $initialvalue;
  }

}
