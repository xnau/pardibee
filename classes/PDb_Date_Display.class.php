<?php

/*
 * class for processing a date into a display string
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Partcicipants_Db
 */

class PDb_Date_Display {

  /**
   * @var string output mode: read, mysql
   * 
   * read - human-readable date string
   * time - date string with time
   * mysql - mysql timestamp string
   */
  private $mode;

  /**
   * @var format the default output display format
   * 
   * this is a PHP date() format
   */
  private $format;

  /**
   * @bar int the instantiating timestamp
   */
  private $timestamp;

  /**
   * @var string a context label
   */
  private $context;

  /**
   * gets a date string for a given timestamp
   * 
   * @param string|int $timestamp
   * @param string $context
   */
  public static function get_date( $timestamp = '', $context = '' )
  {
    $date = new self( array('timestamp' => $timestamp, 'context' => $context ) );
    return $date->output();
  }

  /**
   * gets a date string for a given timestamp and format
   * 
   * @param string|int $timestamp
   * @param string $format
   * @param string $context
   */
  public static function get_date_with_format( $timestamp, $format, $context = '' )
  {
    $date = new self( array('timestamp' => $timestamp, 'format' => $format, 'context' => $context ));
    return $date->output();
  }

  /**
   * gets a date string for a given timestamp and format
   * 
   * @param string|int $timestamp
   * @param string $context
   */
  public static function get_mysql_timestamp( $timestamp, $context = '' )
  {
    $date = new self( array('timestamp' => $timestamp, 'mode' => 'mysql', 'context' => $context ) );
    return $date->output();
  }

  /**
   * supplies a human-readable date string with a time
   * 
   * @param string|int $timestamp
   * @param string $context
   */
  public static function get_date_time( $timestamp, $context = '' )
  {
    $date = new self( array('timestamp' => $timestamp, 'mode' => 'time', 'context' => $context ) );
    return $date->output();
  }

  /**
   * re-asserts the timezone to PHP according to the WordPress timezone setting
   */
  public static function reassert_timezone()
  {
    date_default_timezone_set( self::timezone() );
  }
  
  /**
   * provides the default timezone to use in date calculations
   * 
   * @return string
   */
  public static function timezone()
  {
    $wp_tz_setting = get_option( 'timezone_string' );
    $timezone = empty($wp_tz_setting) ? 'UTC' : $wp_tz_setting;
    return Participants_Db::apply_filters('timezone_assertion', $timezone );
  }

  /**
   * constructs the class instance
   * 
   * @param array $atts an array of configuration values
   *                      'timestamp' => date string or timestamp
   *                      'mode' => can be 'read', 'time', or 'mysql'
   *                      'format' => set the format string directly (will override the mode setting)
   */
  public function __construct( $atts = array('timestamp' => '' ) )
  {
    $this->context = isset( $atts['context'] ) ? $atts['context'] : '';
    $this->timestamp = $this->get_timestamp( isset( $atts['timestamp'] ) ? $atts['timestamp'] : ''  );
    $this->set_mode( isset( $atts['mode'] ) ? $atts['mode'] : 'read'  );
    $this->_set_format();
    if ( isset( $atts['format'] ) ) {
      $this->set_format( $atts['format'] );
    }
  }

  /**
   * sets the format mode
   * 
   * @param string $mode can be 'read', 'time', or 'mysql'
   */
  public function set_mode( $mode )
  {
    switch ( $mode ) {
      case 'read':
      case 'mysql':
      case 'time':
        $this->mode = $mode;
        break;
      default:
        $this->mode = 'read';
    }
  }

  /**
   * sets up the format string
   * 
   * @param string $format a format string to use, or omit to use environment defaults
   */
  public function set_format( $format = '' )
  {
    $this->format = $format;
  }

  /**
   * tells if the time should be included in the format
   * 
   * @return bool true if time is to be included
   */
  public function showing_time()
  {
    return $this->mode === 'time';
  }

  /**
   * supplies the formatted date string
   * 
   * @return string
   */
  public function output()
  {
    self::reassert_timezone();
    return $this->timestamp ? date_i18n( $this->format(), $this->timestamp ) : '';
  }

  /**
   * provides a default date format
   * 
   * @return string date format
   */
  private function _set_format()
  {
    if ( $this->mode === 'mysql' ) {
      $format = 'Y-m-d G:i:s';
    } else {
      $format = get_option( 'date_format' );
      if ( $this->showing_time() ) {
        $format .= ' ' . get_option( 'time_format' );
      }
    }
    $this->format = $format;
  }

  /**
   * provides the format string
   * 
   * @return string
   */
  private function format()
  {
    return Participants_Db::apply_filters( 'date_display_format', $this->format );
  }

  /**
   * sets the timestamp value
   * 
   * @param string|int $date Description
   */
  private function get_timestamp( $date )
  {
    if ( $date === '' ) {
      return time();
    }
    if ( self::is_valid_timestamp( $date ) ) {
      return $date;
    }
    // we parse it into a TS or false if it can't be parsed
    return PDb_Date_Parse::timestamp( $date, array(), __METHOD__ . ' ( ' . $this->context . ' )' );
  }

  /**
   * validates a time stamp
   *
   * @param string|int $timestamp string or number to test for validity as a unix timestamp
   * @return bool true if valid timestamp
   */
  public static function is_valid_timestamp( $timestamp )
  {
    return is_int( $timestamp ) or ( (string) (int) $timestamp === $timestamp);
  }
}
