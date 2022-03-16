<?php

/*
 * parses an input into an epoch timestamp
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.7
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Partcipants_Db
 */

class PDb_Date_Parse {

  /**
   * @var bool if true, a string input is parsed according to a defined format
   */
  private $strict;

  /**
   * @var bool if true, output the date with the time set to 00:00
   */
  private $zero_time = false;

  /**
   * @var string the parse mode used
   * 
   * this class can use several modes for parsing a date to accomodate different 
   * PHP configurations, also localized strings
   */
  private $parse_mode = 'none';

  /**
   * @var bool if true, use "european" day/month order
   * 
   * "european" order is day/month, in the US it tends to be month/day. this is 
   * used when a date string parse without an input format is attempted
   */
  private $europen_order;

  /**
   * @var string the input to parse
   */
  private $input;

  /**
   * @var string the formatting string to use for parsing an input
   */
  private $input_format;

  /**
   * @var int|bool the epoch timestamp or bool false if not parsed
   */
  private $timestamp = false;
  
  /**
   * @var bool if true the conversion should use UTC, false use local time
   */
  private $utc = false;


  /**
   * @var string context label for use in filtering, erros, etc.
   */
  private $context;
  
  /**
   * parses the input for a epoch timestamp
   * 
   * @param string $input the date to parse
   * @param array $config optional configuration values to use
   *                      'strict' => bool,
   *                      'zero_time' => bool,
   *                      'european_order' => bool,
   *                      'input_format' => string,
   *                      'utc' => bool,
   * @param string $context optionally identifies the calling context
   * 
   * @return int|bool timestamp or bool false if it can't be parsed
   */
  public static function timestamp( $input, $config = array(), $context = '' )
  {
    $date = new self( $input, $config, $context );
    return $date->output();
  }
  
  /**
   * parses the input for a epoch timestamp
   * 
   * @param string $input
   * @param string $context
   * 
   * @return int|bool timestamp or bool false if it can't be parsed
   */
  public static function timestamp_zero_time( $input, $context = '' )
  {
    $date = new self( $input, array('zero_time' => true), $context );
    return $date->output();
  }
  
  /**
   * tests a string for being a mysql timestamp
   * 
   * also doesn't validate zero timestamp
   * 
   * @param string $timestamp
   * @return bool true if it matches the mysql timestamp format
   */
  public static function is_mysql_timestamp( $timestamp )
  {
    return $timestamp !== '0000-00-00 00:00:00' && preg_match( '#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$#', $timestamp ) === 1;
  }
  
  /**
   * provides the db timestamp timezone
   * 
   * this does not attempt to know the timezone that was in use when the timestamp 
   * was written, it simple returns the timezone code as determined by the 
   * "Record Timestamps Use Local Timezone" setting
   * 
   * @return string timezone code
   */
  public static function db_timestamp_timezone()
  {
    return Participants_Db::plugin_setting_is_true('db_timestamps_use_local_tz') ? wp_timezone_string() : 'UTC';
  }

  /**
   * sets up the class
   * 
   * @param string $input the date to parse
   * @param array $config optional configuration values to use
   *                      'strict' => bool,
   *                      'zero_time' => bool,
   *                      'europen_order' => bool,
   *                      'input_format' => string
   */
  private function __construct( $input, $config, $context = '' )
  {
    $this->context = $context;
    PDb_Date_Display::reassert_timezone();
    $this->setup_config( $config );
    $this->setup_input( $input );
  }

  /**
   * provides the timestamp output
   * 
   * @return int epoch timestamp
   */
  private function output()
  {
    $this->timestamp = Participants_Db::apply_filters( 'parse_date', $this->timestamp, $this->context );
    /*
     * if we don't have a timestamp already (due to filtering or the input was 
     * already a timestamp) then attempt to parse the input string into a timestamp
     */
    if ( $this->timestamp_not_found() ) {
      $this->parse_input();
    }
    
    PDb_Date_Display::revert_timezone();
    
    return $this->timestamp;
  }

  /**
   * parse the input string into an epoch timestamp using a series of possible methods
   */
  private function parse_input()
  {
    // if it is a default zero mysql timestamp, treat it as "no date"
    if ( $this->input === '0000-00-00 00:00:00' ) {
      return;
    }
    
    /*
     * if it is a mysql timestamp, parse it 
     */
    if ( self::is_mysql_timestamp( $this->input ) ) {
      if ( class_exists( 'DateTime' ) ) {
        $this->db_timestamp_parse();
      } else {
        $this->strtotime_parse();
      }
      return;
    }
    
    /*
     * now go through a series of possible methods
     */
    $this->intl_parse();
    
    if ( $this->timestamp_not_found() ) {
      $this->datetime_parse();
    }
    
    if ( $this->timestamp_not_found() ) {
      $this->strtotime_parse();
    }
  }

  /**
   * parses the input using PHP's strtotime
   */
  private function strtotime_parse()
  {
    $this->timestamp = xnau_strtotime::get_timestamp( $this->input, $this->input_format, $this->europen_order );
    $this->parse_mode = 'strtotime';
  }

  /**
   * parses the input using the IntlDateFormatter class
   */
  private function intl_parse()
  {
    if ( !class_exists( 'IntlDateFormatter' ) || !class_exists( 'DateTime' ) ) {
      return;
    }
    
    // head off problems with old timezone string values #2422
    try {
      $DateFormat = new IntlDateFormatter( get_locale(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, self::time_zone_object($this->utc), NULL, $this->icu_format() );
    }
    catch ( Exception $e) {
      Participants_Db::debug_log(__METHOD__.' '.$e->getMessage().' TZ string: "'. get_option('timezone_string') . '"' );
      $DateFormat = new IntlDateFormatter( get_locale(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'UTC', NULL, $this->icu_format() );
    }
    
    $DateFormat->setLenient( false ); // we want it strict
    
    try {
      $timestamp = $DateFormat->parse( $this->input );
    } catch (Exception $e) { }

    $the_Date = '';
    
    if ( ! intl_is_failure( $DateFormat->getErrorCode() ) ) {
    
      $the_Date = new DateTime();
      $the_Date->setTimestamp( $timestamp ); // type cast this to int?
    }
    if ( is_a( $the_Date, 'DateTime' ) ) {
      $this->set_timestamp_from_datetime( $the_Date );
      $this->parse_mode = 'intl';
    }
  }
  
  /**
   * parses a database timestamp
   * 
   */
  private function db_timestamp_parse()
  {
    $this->input_format = 'Y-m-d H:i:s';
    
    $TZ = new DateTimeZone( self::db_timestamp_timezone() );
    
    $Date = DateTime::createFromFormat( $this->input_format, $this->input, $TZ );
      
    $this->set_timestamp_from_datetime( $Date );

    $this->parse_mode = 'datetime';
  }

  /**
   * parses the input using the DateTime class
   */
  private function datetime_parse()
  {
    $Date = DateTime::createFromFormat( $this->input_format, $this->input, self::time_zone_object( $this->utc ) );
    
    if ( is_a( $Date, 'DateTime' ) ) {
      
      $errors = $Date->getLastErrors();
      if ( $errors['warning_count'] === 0 && $errors['error_count'] === 0 ) {
        $errors = false;
      }
      if ( is_array( $errors ) ) {
        if ( PDB_DEBUG ) {
          Participants_Db::debug_log( __METHOD__ . ' value: ' . $this->input .' error: ' . print_r($errors,1) );
        }

        return;
      }
      
      $this->set_timestamp_from_datetime( $Date );
      
      $this->parse_mode = 'datetime';
    }
  }
  
  /**
   * provides the WP timezone
   * 
   * @param bool $utc if true, use UTC timezone
   * @return DateTimeZone object
   */
  public static function time_zone_object( $utc = false )
  {
    if ( $utc ) {
      return new DateTimeZone( 'UTC' );
    }
    
    // WP 5.3 and later
    if ( !$utc && function_exists( 'wp_timezone' ) ) {
      return wp_timezone();
    }
        
    $offset  = (float) get_option( 'gmt_offset' );
    $hours   = (int) $offset;
    $minutes = ( $offset - $hours );
 
    $sign      = ( $offset < 0 ) ? '-' : '+';
    $abs_hour  = abs( $hours );
    $abs_mins  = abs( $minutes * 60 );
    $tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
    
    return new DateTimeZone( $tz_offset );
  }

  /**
   * sets the timestamp property given a datetime object
   * 
   * @param DateTime $date
   */
  private function set_timestamp_from_datetime( DateTime $date )
  {
    // make sure we're using the WP timezone
    $date->setTimezone( self::time_zone_object() );
    
    if ( $this->zero_time ) {
      $hour = Participants_Db::apply_filters( 'zero_time_hour', 0 );
      $date->setTime( $hour, 0 );
    }
    
    $this->timestamp = $date->format( 'U' );
  }

  /**
   * provides the date format string in strftime format
   * 
   * @return string
   */
  private function strftime_format()
  {
    return xnau_Date_Format_String::to_strftime( $this->input_format );
  }

  /**
   * provides the date format string in ICU format
   * 
   * @return string
   */
  private function icu_format()
  {
    return xnau_Date_Format_String::to_ICU( $this->input_format );
  }

  /**
   * sets up the input property
   * 
   * @param string $input
   */
  private function setup_input( $input )
  {
    if ( PDb_Date_Display::is_valid_timestamp( $input ) ) {
      $this->timestamp = $input;
    } else {
      $this->input = $input;
    }
  }
  
  /**
   * finds a default input format
   * 
   * this will use the plugin input date format is strict dates is enabled, otherwise 
   * it will use the global date format
   * 
   */
  private function default_input_format()
  {
    return $this->strict ? Participants_Db::plugin_setting_value('input_date_format') : get_option('date_format');
  }
  
  /**
   * checks the timestamp to see if it has not been determined
   * 
   * @return bool true if the timestamp has not been determined
   */
  private function timestamp_not_found()
  {
    return PDb_Date_Display::is_valid_timestamp( $this->timestamp ) === false;
  }

  /**
   * sets up the config values
   * 
   * @param array $config
   */
  public function setup_config( $config )
  {
    
    foreach (  get_object_vars( $this ) as $name => $value) {
      
      switch ( $name ) {
        case 'input_format':
          $this->input_format = isset( $config[$name] ) ? $config[$name] : $this->default_input_format();
          break;
        case 'strict':
          $this->{$name} = (bool) ( isset( $config[$name] ) ? $config[$name] : Participants_Db::plugin_setting_is_true('strict_dates') );
          break;
        case 'european_order':
        case 'zero_time':
          $this->{$name} = (bool) ( isset( $config[$name] ) ? $config[$name] : false );
          break;
        case 'utc':
          if ( isset( $config['utc'] ) ) {
            $this->utc = (bool) $config['utc'];
          }
          break;
      }
    }
  }

}
