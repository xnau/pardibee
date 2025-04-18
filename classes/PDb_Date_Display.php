<?php

/*
 * class for processing a date into a display string
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Partcicipants_Db
 */

class PDb_Date_Display {

  /**
   * @var string output mode: read, mysql
   * 
   * this property is really just a shorthand method for determining the display format
   * 
   * read - human-readable date string using WP defaults
   * time - date string with time using WP defaults
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
   * @return string
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
   * @return string
   */
  public static function get_date_with_format( $timestamp, $format, $context = '' )
  {
    $date = new self( array('timestamp' => $timestamp, 'format' => $format, 'context' => $context ));
    return $date->output();
  }

  /**
   * gets a mysql timestamp date string for a given timestamp and format
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
   * 
   */
  public static function reassert_timezone()
  {
    if ( self::tz_sync_enabled() ) {
      set_transient(__CLASS__.'-initial-tz', date_default_timezone_get());
      // doing this will trigger a warning in Site Health
      date_default_timezone_set( self::timezone() );
    }
  }
  
  /**
   * reverts the timezone setting to its previously set value
   */
  public static function revert_timezone()
  {
    if ( self::tz_sync_enabled() ) {
      // doing this will trigger a warning in Site Health
      date_default_timezone_set( get_transient(__CLASS__.'-initial-tz') );
    }
  }
  
  /**
   * tells if timezone syncing is enabled
   * 
   * @return bool
   */
  private static function tz_sync_enabled()
  {
    return Participants_Db::apply_filters( 'php_timezone_sync', true ) && Participants_Db::plugin_setting_is_true( 'sync_timezone' );
  }
  
  /**
   * provides the timezone to use in date calculations
   * 
   * uses the WP timezone setting if available
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
    $this->_set_mode( $atts );
    $this->_set_format( $atts );
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
    $date_func = function_exists('wp_date') ? 'wp_date' : 'date_i18n';
    
    return $this->timestamp ? $date_func( $this->format(), $this->timestamp ) : '';
  }
  
  /**
   * sets the mode property
   * 
   * @param array $atts the field attributes array
   */
  private function _set_mode( $atts )
  {
    $this->set_mode( isset( $atts['mode'] ) ? $atts['mode'] : 'read' );
  }

  /**
   * provides a default date format
   * 
   * @param array $atts the field attributes
   */
  private function _set_format( $atts )
  {
    if ( isset( $atts['format'] ) )
    {
      $format = $atts['format'];
    }
    elseif ( $this->mode === 'mysql' ) 
    {
      $format = 'Y-m-d H:i:s';
    } 
    else 
    {
      $format = get_option( 'date_format' );
      if ( $this->mode === 'time' ) {
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
    /**
     * @filter pdb-date_display_format
     * @param string date format php date format
     * @param string context
     * @return string date format string
     */
    return Participants_Db::apply_filters( 'date_display_format', $this->format, $this->context );
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
    // first try using the display format
    $date = PDb_Date_Parse::timestamp( $date, array( 'input_format' => $this->format ), __METHOD__ . ' ( ' . $this->context . ' )' );
    
    if ( $date === false ) {
      // then try it using in input format
      $date = PDb_Date_Parse::timestamp( $date, array(), __METHOD__ . ' ( ' . $this->context . ' )' );
    }
    return $date;
  }

  /**
   * validates a time stamp
   *
   * @param string|int $timestamp string or number to test for validity as a unix timestamp
   * @return bool true if valid timestamp
   */
  public static function is_valid_timestamp( $timestamp )
  {
    return is_int( $timestamp ) || ( (string) (int) $timestamp === $timestamp) || ( (float) (int) $timestamp === $timestamp);
  }
}
