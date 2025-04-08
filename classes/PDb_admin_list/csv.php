<?php

/**
 * handles global CSV preferences
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;

use \Participants_Db;

defined( 'ABSPATH' ) || exit;

class csv {
  
  /**
   * updates a single CSV option
   * 
   * @param string $option_name
   * @param string|int $option_value
   */
  public static function set_csv_option( $option_name, $option_value )
  {
    $csv_options = self::csv_options();
    
    $csv_options[$option_name] = $option_value;
    
    self::update_option( $csv_options );
  }
  
  /**
   * updates the csv options
   * 
   * @param array $csv_params option values to store
   */
  public static function update_option( $csv_params )
  {
    update_option( Participants_Db::$prefix . 'csv_import_params', $csv_params );
  }

  /**
   * provides the stored CSV parameters
   * 
   * @return array
   */
  public static function csv_options()
  {
    $csv_paramdefaults = [
        'delimiter_character' => 'auto',
        'enclosure_character' => 'auto',
        'match_field' => Participants_Db::plugin_setting( 'unique_field' ),
        'match_preference' => Participants_Db::plugin_setting( 'unique_email' ),
        'blank_overwrite' => Participants_Db::apply_filters( 'allow_imported_empty_value_overwrite', 0 ),
    ];

    $csv_options = get_option( Participants_Db::$prefix . 'csv_import_params', $csv_paramdefaults );
    
    $all_options = [];
    
    // make sure these are not empty values #2669
    foreach( $csv_paramdefaults as $param_name => $param_value ) {
      if ( array_key_exists( $param_name, $csv_options ) && $csv_options[$param_name] !== '' ) {
        $all_options[$param_name] = $csv_options[$param_name];
      } else {
        $all_options[$param_name] = $param_value;
      }
    }
    
    return $all_options;
  }

  /**
   * provides the delimiter character
   * 
   * @return string
   */
  public static function delimiter()
  {
    $delimiter = self::csv_options()[ 'delimiter_character' ];

    return $delimiter === 'auto' ? ',' : html_entity_decode( $delimiter, ENT_QUOTES, 'UTF-8' );
  }

  /**
   * provides the enclosure character
   * 
   * @return string
   */
  public static function enclosure()
  {
    $enclosure = self::csv_options()[ 'enclosure_character' ];

    return $enclosure === 'auto' ? '"' : html_entity_decode( $enclosure, ENT_QUOTES, 'UTF-8' );
  }

}
