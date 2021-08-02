<?php

/**
 * models a database column from any user field group
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

use \Participants_Db,
    \PDb_Date_Parse;

class user_column extends base_column {

  /**
   * provides the column value
   * 
   * @return string|int|bool
   */
  protected function setup_value()
  {
    $initialvalue = $this->initial_value();

    switch ( $this->field->form_element() ) {

      case 'multi-select-other':
      case 'multi-checkbox':
      case 'multi-dropdown':

        $this->value = Participants_Db::_prepare_array_mysql( array_values( $this->field->get_value() ) );

        break;

      case 'link':

        /* translate the link markdown used in CSV files to the array format used in the database
         */
        if ( !is_array( $initialvalue ) ) {

          $this->value = Participants_Db::_prepare_array_mysql( Participants_Db::get_link_array( $initialvalue ) );
        } else {

          $this->value = Participants_Db::_prepare_array_mysql( $initialvalue );
        }
        break;

      case 'rich-text':
        global $allowedposttags;
        $this->value = wp_kses( stripslashes( $initialvalue ), $allowedposttags );
        break;

      case 'date':

        if ( $initialvalue !== '' ) {
          $this->value = PDb_Date_Parse::timestamp( $initialvalue, array(), __METHOD__ . ' date field value' );
        } else {
          $this->value = null;
        }
        break;

      case 'captcha':
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
        if ( strlen( $initialvalue ) > 0 ) {
          $this->value = filter_var( $initialvalue, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
        } else {
          $this->value = null;
        }
        break;

      case 'image-upload':
      case 'file-upload':

        if ( filter_input( INPUT_POST, $this->field->name() . '-deletefile', FILTER_SANITIZE_STRING ) === 'delete' ) {

          if ( $participant_id ) {

            $filename = '';
            if ( array_key_exists( $this->field->name(), $_POST ) ) {
              $post_filename = filter_input( INPUT_POST, $this->field->name(), FILTER_SANITIZE_STRING );

              global $wpdb;
              $record_filename = $wpdb->get_var( $wpdb->prepare( 'SELECT `' . $this->field->name() . '` FROM ' . Participants_Db::participants_table() . ' WHERE id = %s', $participant_id ) );

              if ( $post_filename === $record_filename ) {
                if ( Participants_Db::plugin_setting_is_true( 'file_delete' ) || Participants_Db::is_admin() ) {
                  Participants_Db::delete_file( $record_filename );
                }
                unset( $_POST[ $this->field->name() ] );
                $initialvalue = '';
              }
            }
          }
        }
        $this->value = Participants_Db::_prepare_string_mysql( trim( $initialvalue ) );
        break;

      default:

        if ( is_array( $initialvalue ) ) {

          $this->value = Participants_Db::_prepare_array_mysql( $initialvalue );
        } else {

          $this->value = Participants_Db::_prepare_string_mysql( trim( $initialvalue ) );
        }
    }
    
    error_log(__METHOD__.' field: '.$this->field->name().' initial value: '.$initialvalue.' end value: '.$this->value );
  }
  
  /**
   * provides the base value to use
   * 
   * @return string
   */
  private function initial_value()
  {
    $initialvalue = $this->value;
    
    if ( ! $this->add_to_query() ) {
      
      // possibly use the default value
      $defaultvalue = $this->main_query()->default_value( $this->field->name() );
      if ( \Participants_Db::is_set_value( $defaultvalue ) ) {
        $initialvalue = $defaultvalue;
      }
    }
    
    return $initialvalue;
  }

}
