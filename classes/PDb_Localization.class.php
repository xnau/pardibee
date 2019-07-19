<?php

/**
 * handles various localization tasks
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2019  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Localization {
  
  /**
   * provides the localized display number
   * 
   * @param int|float $number raw number in mysql format
   * @param object $field the current field
   * @return string display
   */
  public static function display_number( $number, $field )
  {
    $display = Participants_Db::apply_filters('number_display', $number, $field );
    
    if ( $display === $number /* && strpos( $number, '.' ) !== false */ ) {
      $numberFormatter = new NumberFormatter( get_locale(), NumberFormatter::DECIMAL );
      $numberFormatter->setAttribute(NumberFormatter::GROUPING_USED, true );
      
      $display = $numberFormatter->format($number);
    }
    
    return $display;
  }
  
  /**
   * provides the localized currency display
   * 
   * this outputs a 2-decimal-place number with no denomination
   * 
   * @param int|float $number raw number in mysql format
   * @param object $field the current field
   * @return string display
   */
  public static function display_currency( $number, $field )
  {
    $display = Participants_Db::apply_filters('number_display', $number, $field );
    
    if ( $display === $number ) {
      $numberFormatter = new NumberFormatter( get_locale(), NumberFormatter::DECIMAL );
      $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
      $display = $numberFormatter->format($number);
    }
    
    return $display;
  }
  
  /**
   * tells if the into extension is installed
   * 
   * @return bool true if installed
   */
  private static function intl_available()
  {
    return class_exists('NumberFormatter');
  }
}
