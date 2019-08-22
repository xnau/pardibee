<?php

/*
 * implements a variation on the PHP strtotime function that tries to detect a 
 * european date string and parse it correctly
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class xnau_strtotime {
  /**
   * @var string the divider character
   */
  private $divider;
  
  /**
   * @var bool if true, european date order is detected
   */
  private $european_order;
  
  /**
   * @var string the input date string
   */
  private $input;
  
  /**
   * @var string the date format
   */
  private $date_format;
  
  /**
   * supplies the parsed timestamp
   * 
   * @param string $date the date string
   * @param string $date_format the date format string (used to detect european formatting)
   * @param bool $euro european order setting
   */
  public static function get_timestamp( $date, $date_format = '', $euro = null )
  {
    $strtotime = new self( $date, $date_format, $euro );
    return $strtotime->parse();
  }
  
  /**
   * constructor
   * 
   * @param string $date the date string
   * @param string $date_format the date format string
   * @param bool $euro european order setting
   */
  private function __construct( $date, $date_format, $euro )
  {
    $this->input = $date;
    $this->european_order = $euro;
    $this->set_date_format( $date_format );
    
    if ( is_null( $this->european_order ) ) {
      $this->detect_european_order();
    }
    
  }
  
  /**
   * parses the date string
   * 
   * @return int timestamp
   */
  private function parse() {
    return strtotime( $this->date_string() );
  }
  
  /**
   * provides the possibly modified date string
   * 
   * @return string
   */
  private function date_string() {
    return $this->european_order ? $this->reorder_date_string() : $this->input;
  }

    /**
   * gets the date format string
   * 
   * uses the WP format setting if not format is supplied
   * 
   * @param string $format
   */
  private function set_date_format( $format )
  {
    $this->date_format = empty($format) ? get_option('date_format') : $format;
  }

  /**
   * re-orders the date string so it can be parsed by strtotime
   * 
   * 
   * @return string the reordered string
   */
  private function reorder_date_string()
  {
    list($day, $month, $year) = explode( $this->divider, $this->input );
    return sprintf( "%s$this->divider%s$this->divider%s", $month, $day, $year );
  }
  
  /**
   * detects a european order date format string
   * 
   */
  private function detect_european_order()
  {
    /*
     * test the input for a typical numeric date string, such as 3/10/16 or 25-9-2012 
     * and capture the divider character
     */
    $test = preg_match( '/^\d{1,2}([.\/-])\d{1,2}\1\d{2,4}$/', $this->input, $matches );
    $this->divider = $test === 0 ? false : $matches[1];
    /*
     * check the formatting string, see if it uses the same divider character
     */
    if ( $this->divider and strpos( $this->date_format, $this->divider ) !== false ) {
      /*
       * check the order in the format string to see if it's a European day/month/year type date
       */
      $date_parts = explode( $this->divider, $this->date_format );
      $day_index = array_search( 'd', $date_parts ) !== false ? array_search(  'd', $date_parts ) : array_search( 'j', $date_parts );
      $month_index = array_search( 'm', $date_parts ) !== false ? array_search( 'm', $date_parts ) : array_search( 'n', $date_parts );
      
      if ( $day_index !== false && $month_index !== false && $day_index < $month_index ) {
        $this->european_order = true;
        return;
      }
    }
    $this->european_order = false;
  }
}
