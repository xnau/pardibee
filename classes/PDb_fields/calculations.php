<?php

/**
 * provides functionality to numeric and date calculation fields
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

trait calculations {

  /**
   * calculates the total
   * 
   * @param array $replacement_data the prepared data from the template
   */
  private function calculate_value( $replacement_data )
  {
    error_log( __METHOD__ . ' template: ' . $this->calc_template() );
    ob_start();
    var_dump( $replacement_data );
    error_log( __METHOD__ . ' replacement data: ' . ob_get_clean() );

    $this->calc_list( $replacement_data );

    error_log( __METHOD__ . ' calc list: ' . print_r( $this->calc_list, 1 ) );

    $this->result = $this->get_calculated_value();
  }

  /**
   * builds the calc list array
   * 
   * @param array $data replacement data
   * @return array
   */
  private function calc_list( $data )
  {
    $calc_list = array();
    $tag = false;
    $buffer = '';

    foreach ( str_split( $this->calc_template() ) as $chr ) {

      switch ( $chr ) {

        case '+':
        case '-':
        case '*':
        case '/':
        case '=':

          if ( is_numeric( $buffer ) ) {
            // add any literal number
            $calc_list[] = $buffer;
            $buffer = '';
          }
          
          // get the last item added to the list
          $previous = end( $calc_list );

          if ( $buffer === '#' ) {
            // we're building a tag, add the operator character to the buffer
            $buffer .= $chr;
          } elseif ( in_array( $previous, array( '+', '-', '*', '/', '=' ) ) ) {
            // if the previous step is also an operator, remove it and replace it with the current operator
            array_pop( $calc_list );
            $calc_list[] = $chr;
            $buffer = '';
          } elseif ( $previous !== false ) {
            // add the operator to the list if the preceding item is not undefined
            $buffer = '';
            $calc_list[] = $chr;
          }
          break;

        case '[':
          $tag = true;
          $buffer = '';
          break;

        case ']':
          $tag = false;

          /* tag is closed: get the matching value from the data, but only if it 
           * is a number of some kind
           */
          if ( isset( $data[ $buffer ] ) && is_numeric( $data[ $buffer ] ) ) {

            // get the value of the tag
            $calc_list[] = $data[ $buffer ];
          } elseif ( strpos( $buffer, '#' ) === 0 ) {

            // if this is a key value, convert it to a timestamp
            $calc_list[] = $this->key_value( $buffer );
          } elseif ( strpos( $buffer, '?' ) === 0 ) {
            // this is a formatting key
            $calc_list[] = $buffer;
          }

          $buffer = '';
          break;

        default:
          $buffer .= $chr;
      }
    }

    $this->calc_list = $calc_list;
  }

  /**
   * provides the value for a value key
   * 
   * @param string $key
   * @return int|float the converted value or the key if no matching key
   */
  private function key_value( $key )
  {
    $cachekey = calculated_field::keycache . $key;
    $matchkey = ltrim( $key, "#" );

    $has_n = preg_match( '/^([+-]?\d+)(_.+)$/', $matchkey, $matches );

    if ( $has_n ) {
      // get the numeric part, if included
      $numeric = (int) $matches[ 1 ];
      if ( is_numeric( $numeric ) ) {
        $matchkey = 'n' . $matches[ 2 ];
      }
    }

    $value = get_transient( $cachekey );

    if ( $value === false ) {
      error_log( __METHOD__ . ' getting conversion for ' . $matchkey );
      switch ( $matchkey ) {

        case 'current_date':
          $value = time();
          break;

        case 'current_day':
          $value = strtotime( date( 'M j,Y 00:00' ) );

        case 'current_week':
          $value = strtotime( date( 'M j,Y 00:00', strtotime( 'this week' ) ) );
          break;

        case 'current_month':
          $value = strtotime( date( 'M 01,Y 00:00', strtotime( 'this month' ) ) );
          break;

        case 'current_year':
          $value = strtotime( date( '\j\a\n 01,Y 00:00' ) );
          break;

        case 'n_days':
          $value = strtotime( date( 'M j,Y 00:00', strtotime( $numeric . ' days' ) ) );
          break;

        case 'n_months':
          $value = strtotime( date( 'M j,Y 00:00', strtotime( $numeric . ' months' ) ) );
          break;

        default:
          // no key found, return the original string
          return $key;
      }

      error_log( __METHOD__ . ' result ' . $value );
      set_transient( $cachekey, $value, HOUR_IN_SECONDS );
    }

    return $value;
  }

  /**
   * performs the calculation set up by the calc list
   * 
   * @return int|float
   */
  private function get_calculated_value()
  {
    $format = 'unformatted';
    $sum_list = array();
    $sum_count = 0;
    $sum = 0;
    $op = 'add';

    // first pass to calc the sums
    foreach ( $this->calc_list as $i => $item ) {

      switch ( $item ) {

        case '+':
          $op = 'add';
          break;

        case '-':
          $op = 'sub';
          break;

        case '=':
        case '/':
        case '*':
          $sum_list[] = $sum;
          $sum = 0;
          break;

        default:
          if ( is_numeric( $item ) ) {
            if ( $op === 'add' ) {
              $sum = $sum + $item;
              $sum_count++;
            } elseif ( $op === 'sub' ) {
              $sum = $sum - $item;
              $sum_count++;
            }
          }
      }

//      error_log( __METHOD__ . ' sum: ' . $sum . ' sum count: ' . $sum_count . ' sum list: ' . print_r( $sum_list, 1 ) );
    }

    $product = $sum;
    $sum_index = 0;

    // now perform the multiplication or division
    foreach ( $this->calc_list as $i => $item ) {

      switch ( $item ) {

        case '*':
          $product = $sum_list[ $sum_index ] * $sum_list[ $sum_index + 1 ];
          $sum_index = $sum_index + 2;
          break;

        case '/':
          $product = $sum_list[ $sum_index ] / $sum_list[ $sum_index + 1 ];
          $sum_index = $sum_index + 2;
          break;

        case '=':
          if ( $product === $sum ) {
            $product = $sum_list[ $sum_index ];
            $sum_index++;
          }
          $format = $this->calc_list[ $i + 1 ];
          break 2;
      }
    }

    return $this->format( $product, $format, $sum_count );
  }

  /**
   * formats the value based on the formatting tag
   * 
   * @param int|float $value
   * @param string $format_tag the format tag
   * @param int $sum_count the number of summed items
   * @return string the formatted value
   */
  private function format( $value, $format_tag, $sum_count )
  {
    // remove the leading ?
    $format_tag = str_replace( '?', '', $format_tag );

    $numeric = 0;
    if ( preg_match( '/(.+_)(\d+)$/', $format_tag, $matches ) === 1 ) {
      $numeric = $matches[ 2 ];
      $format_tag = $matches[ 1 ] . 'n';
    }

    switch ( $format_tag ) {

      case 'round_n':
        $formatted = $this->format_number( $value, $numeric );
        break;

      case 'integer':
        $formatted = $this->truncate_number( $value );
        break;

      case 'years':
      case 'weeks':
      case 'months':
      case 'days':
        $formatted = $this->truncate_number( $value / $this->date_divisor( $format_tag ) );
        break;

      case 'year':
      case 'month':
      case 'week':
      case 'day':
      case 'day_of_week':
        $formatted = $this->partial_date( $value, $format_tag );
        break;

      case 'average':
      case 'average_n':
        $average = $sum_count > 0 ? $value / $sum_count : $value;
        $formatted = $this->format_number( $average, $numeric );
        break;

      case 'date':
        $formatted = \PDb_Date_Display::get_date( $value );
        break;

      case 'unformatted':
      default:
        $formatted = $value;
    }

    return $formatted;
  }

  /**
   * provides the date divisor value
   * 
   * @param string $format_tag
   * @return int
   */
  private function date_divisor( $format_tag )
  {
    $const = strtoupper( rtrim( $format_tag, 's' ) ) . '_IN_SECONDS';

    $divisor = 1;
    // check if our tag produces a defined constant
    if ( defined( $const ) ) {
      $divisor = constant( $const );
    }

    return $divisor;
  }

  /**
   * truncates the fractional part of a float without rounding
   * 
   * @param float $number
   * @return int
   */
  private function truncate_number( $number )
  {
    if ( strpos( $number, '.' ) === false ) {
      return $number;
    }

    list( $int, $frac ) = explode( '.', $number );

    return (int) $int;
  }

  /**
   * gets a partial date
   * 
   * @param int $timestamp
   * @param string $format_tag
   * @return string the partial date
   */
  private function partial_date( $timestamp, $format_tag )
  {
    $format = false;
    switch ( $format_tag ) {
      case 'year':
        $format = 'Y';
        break;

      case 'month':
        $format = 'F';
        break;

      case 'week':
        $format = 'W';
        break;

      case 'day':
        $format = 'j';
        break;

      case 'day_of_week':
        $format = 'l';
        break;
    }
    return $format ? date( $format, $timestamp ) : $timestamp;
  }

  /**
   * checks the replacement data for NULLs
   * 
   * @param array $data
   * @return bool true if there are no nulls
   */
  private function check_data( $data )
  {
    foreach ( $data as $item ) {
      if ( is_null( $item ) ) {
        return false;
      }
    }

    return true;
  }

  /**
   * formats a float with specified number of decimal places
   * 
   * @param float $number
   * @param int $places maximum number of decimal places to show
   * @return string formatted number
   */
  private function format_number( $number, $places = 0 )
  {
    if ( class_exists( '\NumberFormatter' ) ) {
      $formatter = new \NumberFormatter( get_locale(), \NumberFormatter::DECIMAL );
      $formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $places );
      return $formatter->format( $number );
    } else {
      return number_format( $number, $places );
    }
  }

}