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
   * @var NumberFormatter
   */
  private $formatter;
  
  /**
   * initializes the class
   */
  private function __construct()
  {
    if ( self::intl_available() ) {
      $this->formatter = new NumberFormatter( get_locale(), NumberFormatter::DECIMAL );
    } else {
      Participants_Db::debug_log('php intl module not available: numeric displays will not be localized');
    }
  }
  
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
    
    $localization = new self;
    
    if ( $display === $number && self::intl_available() ) {
      if ( isset($field->attributes['step']) && is_numeric($field->attributes['step']) ) {
        // set the number of decimal places based on the step attribute
        $parts = explode('.', $field->attributes['step'] );
        if ( isset($parts[1]) ) {
          $localization->formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, strlen($parts[1]) );
        } else {
          $localization->formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0 );
        }
      }
      $localization->formatter->setAttribute(NumberFormatter::GROUPING_USED, true );
      $display = $localization->formatter->format($number);
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
    
    $localization = new self;
    
    if ( $display === $number && self::intl_available() ) {
      $localization->formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
      $display = $localization->formatter->format($number);
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
