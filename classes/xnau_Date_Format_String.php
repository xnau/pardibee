<?php

/*
 * static class for converting between date format strings
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class xnau_Date_Format_String {
  /**
   * @var string the unconverted date format string
   */
  private $date_string;
  /**
   * provides a PHP date format string to an UCU format string
   * 
   * @param string $date_string
   * @return string
   */
  public static function to_ICU($date_string = '') {
    $date = new self($date_string);
    return $date->translate_date_format('ICU');
  }
  /**
   * provides a PHP date format string to a strftime format string
   * 
   * @param string $date_string
   * @return string
   */
  public static function to_strftime($date_string = '') {
    $date = new self($date_string);
    return $date->translate_date_format('strftime');
  }
  /**
   * provides a PHP date format string to a strftime format string
   * 
   * @param string $date_string
   * @return string
   */
  public static function to_jQuery($date_string = '') {
    $date = new self($date_string);
    return $date->translate_date_format('jQuery');
  }
  /**
   * 
   */
  private function __construct($date_string)
  {
    $this->setup_date_string($date_string);
  }
  /**
   * Convert a date format to a strftime format 
   * 
   * Timezone conversion is done for unix. Windows users must exchange %z and %Z. 
   * 
   * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r 
   * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x 
   * 
   * Props: http://php.net/manual/en/function.strftime.php#96424
   * 
   * @return string 
   */
  private function dateFormatToStrftime()
  {
    $windows = stripos( PHP_OS, 'win' ) !== false;
    $code_map = array(
        // Day - no strf eq : S 
        'd' => '%d', 'D' => '%a', 'j' => ( $windows ? '%#d' : '%e' ), 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j',
        // Week - no date eq : %U, %W 
        'W' => '%V',
        // Month - no strf eq : n, t 
        'F' => '%B', 'm' => '%m', 'M' => '%b',
        // Year - no strf eq : L; no date eq : %C, %g 
        'o' => '%G', 'Y' => '%Y', 'y' => '%y',
        // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X 
        'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S',
        // Timezone - no strf eq : e, I, P, Z 
        'O' => '%z', 'T' => '%Z',
        // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x  
        'U' => '%s'
    );

    return strtr((string) $this->date_string, $code_map);
  }

  /**
   * translates date format strings from PHP to other formats
   *
   * @param string $format_type selected the format type to translate to: 'ICU', 'jQuery', 'strftime'
   * @return string the translated format string
   */
  private function translate_date_format($format_type)
  {

    // these are the PHP date codes
    $pattern = array(
        //day
        'd', //day of the month
        'j', //1 or 2 digit day of month
        'l', //full name of the day of the week
        'D', // abbreviated day of the week
        'z', //day of the year
        //month
        'F', //Month name full
        'M', //Month name short
        'n', //numeric month no leading zeros
        'm', //numeric month leading zeros
        //year
        'Y', //full numeric year
        'y',  //numeric year: 2 digit
        // time
        'g', // 12-hour hour
        'i', // minute with leading zero
        'a', // AM or PM
    );
    switch ($format_type) {
      case 'strftime':
        return self::dateFormatToStrftime($this->date_string);
        break;
      case 'ICU':
        $replace = array(
            'dd', 'd', 'EEEE', 'EEEE', 'D',
            'MMMM', 'MMM', 'M', 'MM',
            'yyyy', 'yy',
            'h', 'mm', 'a',
        );
        break;
      case 'jQuery':
        $replace = array(
            'dd', 'd', 'DD', 'D', 'o',
            'MM', 'M', 'm', 'mm',
            'yy', 'y',
            'h', 'MM', 'tt',
        );
        break;
    }
    $i = 1;
    foreach ($pattern as $p) {
      $this->date_string = str_replace($p, '%' . $i . '$s', $this->date_string);
      $i++;
    }
    return vsprintf($this->date_string, $replace);
  }
  
  /**
   * sets up the $date_string property
   * 
   * @param string the incoming date format string (or empty string)
   */
  private function setup_date_string( $date_string ) {
    $this->date_string = $date_string === '' ? get_option( 'date_format' ) : $date_string;
  }
}
