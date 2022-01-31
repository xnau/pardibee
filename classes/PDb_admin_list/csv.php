<?php

/**
 * handles global CSV functionality
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;

use \Participants_Db;

defined( 'ABSPATH' ) || exit;

class csv {
  
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
    $csv_paramdefaults = array(
        'delimiter_character' => 'auto',
        'enclosure_character' => 'auto',
        'match_field' => Participants_Db::plugin_setting( 'unique_field' ),
        'match_preference' => Participants_Db::plugin_setting( 'unique_email' )
    );

    $csv_options = get_option( Participants_Db::$prefix . 'csv_import_params', $csv_paramdefaults );
    
    // make sure these are not empty values #2669
    foreach( array( 'delimiter_character', 'enclosure_character' ) as $param ) {
      if ( empty( $csv_options[$param] ) ) {
        $csv_options[$param] = $csv_paramdefaults[$param];
      }
    }
    
    return $csv_options;
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
