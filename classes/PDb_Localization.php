<?php

/**
 * handles various numeric localization tasks
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2019  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
defined( 'ABSPATH' ) || exit;

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
    if ( $this->intl_available() ) {
      $this->formatter = new NumberFormatter( get_locale(), NumberFormatter::DECIMAL );
    } else {
      Participants_Db::debug_log('php intl module not available: numeric displays will not be localized');
    }
  }
  
  /**
   * provides the localized display number based on the field definition parameters
   * 
   * @param int|float $number raw number in mysql format
   * @param \PDb_Field_Item $field the current field
   * @return string display
   */
  public static function display_number( $number, $field )
  {
    $localization = new self();
    
    if ( ! $localization->is_numeric( $number ) ) {
      return $number;
    }
    
    $display = Participants_Db::apply_filters('number_display', $number, $field );
    
    if ( $display !== $number ) {
      return $display;
    }
    
    if ( $localization->intl_available() ) {
      
      if ( isset($field->attributes['step']) && is_numeric($field->attributes['step']) ) {
        // set the number of decimal places based on the step attribute
        $parts = explode('.', $field->attributes['step'] );
        
        if ( isset($parts[1]) ) {
          
          $localization->formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, strlen($parts[1]) );
        } else {
          
          $localization->formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0 );
        }
      }
      
      $localization->formatter->setAttribute(NumberFormatter::GROUPING_USED, true );
      $display = $localization->formatter->format($number);
      
    } else {
      
      $display = number_format( $number, 2 );
    }
    
    return $display;
  }
  
  /**
   * provides the localized currency display based on the field definition parameters
   * 
   * this outputs a 2-decimal-place number with no denomination
   * 
   * @param int|float $number raw number in mysql format
   * @param object $field the current field
   * @return string display
   */
  public static function display_currency( $number, $field )
  {
    $localization = new self();
    
    if ( ! $localization->is_numeric( $number ) ) {
      return $number;
    }
    
    $display = Participants_Db::apply_filters('number_display', $number, $field );
    
    if ( $display !== $number ) {
      return $display;
    }
    
    if ( $localization->intl_available() ) {
      
      $localization->formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
      $display = $localization->formatter->format($number);
      
    } else {
      
      $display = number_format( $number, 2 );
    }
    
    return $display;
  }

  /**
   * formats a float with specified number of decimal places
   * 
   * @param float $number
   * @param int $places maximum number of decimal places to show
   * @return string formatted number
   */
  public static function format_number( $number, $places = 0 )
  {
    $localization = new self();
    
    if ( ! $localization->is_numeric( $number ) ) {
      return $number;
    }
    
    if ( $localization->intl_available() ) {
      
      $localization->formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $places );
      return $localization->formatter->format( $number );
      
    } else {
      
      return number_format( $number, $places );
    }
  }
  
  /**
   * formats a number with automatic places detection
   * 
   * @param string|int|float $number
   * @return string formatted number
   */
  public static function auto_format_number( $number )
  {
    return self::format_number( $number, self::place_count( $number ) );
  }


  /**
   * tells the number of decimal places in a number
   * 
   * @param int|float|string $number
   * @return int number of places
   */
  public static function place_count( $number )
  {
    $test = strval( $number );
    if ( strpos( $test, '.' ) === false ) {
      return 0;
    } 
    
    list( $int, $dec ) = explode( '.', $test );
    
    return strlen( $dec );
  }
  
  /**
   * tells if the value is numeric
   * 
   * @param string|int|float
   * @return bool
   */
  private function is_numeric( $number )
  {
    return is_numeric( $number );
  }
  
  /**
   * tells if the into extension is installed
   * 
   * @return bool true if installed
   */
  private function intl_available()
  {
    return class_exists('\NumberFormatter');
  }
}
