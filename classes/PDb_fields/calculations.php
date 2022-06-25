<?php

/**
 * provides functionality to numeric and date calculation fields
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

defined( 'ABSPATH' ) || exit;

trait calculations {

  /**
   * @var array of calculation steps
   */
  protected $calc_list;
  
  /**
   * @var bool true if the value should be localized
   */
  protected $localize = false;

  /**
   * calculates the total
   * 
   * @param array $replacement_data the prepared data from the template
   */
  protected function calculate_value( $replacement_data )
  {
    /**
     * @filter pdb-calculated_field_calc_value
     * 
     * provides a way to implement a custom calculation
     * 
     * if the filter returns false, the normal calculation is performed, if it 
     * returns a value, the normal calculation is skipped and the value is used 
     * as the calculated value
     * 
     * @param bool false the initial result
     * @param array $replacement_data
     * @param \PDb_Field_Item $field the current field
     * @return int|float the result of the calculation 
     */
    $this->result = \Participants_Db::apply_filters( 'calculated_field_calc_value', false, $replacement_data, $this->field );

    if ( defined( 'PDB_DEBUG' ) && PDB_DEBUG > 2 ) {
      \Participants_Db::debug_log( __METHOD__ . ' field: ' . $this->field->name() . ' template: ' . $this->template->calc_body(), 3 );
      \Participants_Db::debug_log( __METHOD__ . ' replacement data: ' . print_r( $replacement_data,1 ), 3 );
    }

    if ( $this->result === false ) {
      
      $this->complete = true;

      /**
       * @filter pdb-calculated_field_replacement_data
       * 
       * @param array $replacement_data the array of data for use in replacing the template tags
       * @param \PDb_Field_Item $field the current field
       * @return array the replacement data array
       */
      $this->build_calc_list( \Participants_Db::apply_filters( 'calculated_field_replacement_data', $replacement_data, $this->field ) );

      $this->result = $this->complete ? $this->get_calculated_value() : '';

      \Participants_Db::debug_log( __METHOD__ . " calc list: \n" . print_r( $this->calc_list, 1 ) ."\nresult: " . $this->result, 3 );
    }
  }

  /**
   * builds the calc list array
   * 
   * @param array $data replacement data
   * @return array
   */
  private function build_calc_list( $data )
  {
    $calc_list = array();
    $tag = false;
    $buffer = '';

    // build the list character by character
    foreach ( str_split( $this->template->calc_body() ) as $chr ) {

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
          if ( isset( $data[ $buffer ] ) ) {

            if ( is_numeric( $data[ $buffer ] ) ) {
              // get the value of the tag
              $calc_list[] = $data[ $buffer ];
            } else {
              // not a numeric value, can't process
              $this->mark_as_incomplete($buffer, $data[ $buffer ] );
            }
          } elseif ( strpos( $buffer, '#' ) === 0 ) {

            // if this is a value key, convert it to its value
            $calc_list[] = $this->key_value( $buffer );
          } elseif ( strpos( $buffer, '?' ) === 0 ) {

            // this is a formatting key
            $calc_list[] = $buffer;
          } else {
            
            $this->mark_as_incomplete($buffer);
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
   * mark the calculation as incomplete
   * 
   * @param string $tag_text the string inside the value tag that triggered the incompletion 
   * @param mixed $value the derived value
   */
  private function mark_as_incomplete( $tag_text, $value = false )
  {
    if ( !\PDb_Form_Field_Def::is_field($this->field->name()) ) {

      \Participants_Db::debug_log(__CLASS__.': ' . $this->field->title() .' field missing calculation value "' . $tag_text . '" for record '. $this->field->record_id(), 1 );

    } else {

      $value_display = ' calculation cannot be completed, value not found in "';
      if ( $value !== false ) {
        ob_start();
        var_dump( $value );
        $value_display = ' value can\'t be used: "' . ob_get_clean();
      }
      \Participants_Db::debug_log(__CLASS__.': ' . $this->field->title() . $value_display . $tag_text . '" for record '. $this->field->record_id(), 1 );

    }
    
    $this->complete = false;
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
          $value = strtotime( $numeric . ' days', 0 ); // provides a span of time
          break;

        case 'n_months':
          $value = strtotime( $numeric . ' months', 0 );
          break;

        case 'n_years':
          $value = strtotime( $numeric . ' years', 0 );
          break;

        default:
          // no key found, return the original string
          return $key;
      }

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
    $sum_count = 0;
    $product = 0;
    $op = false;
    $format_tag = '?unformatted';

    foreach ( $this->calc_list as $i => $item ) {

      switch ( $item ) {

        case '+':
        case '-':
        case '*';
        case '/':

          $op = $item;
          break;

        case '=';
          $format_tag = $this->calc_list[ $i + 1 ];
          break 2;

        default:
          // this will be a value
          if ( $op ) {
              
            $operand = floatval($item);

            switch ( $op ) {

              case '+':
                $product = $product + $operand;
                $sum_count++;
                break;

              case '-':
                $product = $product - $operand;
                $sum_count++;
                break;

              case '*':
                $product = $product * $operand;
                break;

              case '/':
                $product = empty( $operand ) ? 0 : ( $product / $operand );
                break;
            }
          } else {

            $product = $item;
          }
      }
    }
    
    return $this->is_display_only_format($format_tag) ? $product : $this->format( $product, $format_tag, $sum_count );
  }

  /**
   * formats the value based on the formatting tag
   * 
   * @param int|float $value
   * @param string $format_tag the format tag
   * @param int $sum_count the number of summed items
   * @param bool $localize if true, localize the display string
   * @return string the formatted value
   */
  private function format( $value, $format_tag, $sum_count, $localize = false )
  {
    if ( is_null( $value ) || $value === '' ) {
      return '';
    }
    
    $this->localize = $localize;
    
    // remove the leading ? and brackets
    $format_tag = str_replace( array('?', ']','['), '', $format_tag );

    $numeric = 0;
    if ( preg_match( '/(.+_)(\d+)$/', $format_tag, $matches ) === 1 ) {
      $numeric = $matches[ 2 ];
      $format_tag = $matches[ 1 ] . 'n';
    }
    
    if ( $format_tag === 'unformatted' && $this->is_numeric_field() && is_numeric( $value ) ) {
      $format_tag = 'auto_numeric';
    }

    switch ( $format_tag ) {

      case 'round_n':
        $formatted = $this->format_number( $value, $numeric );
        break;

      case 'integer':
        $formatted = $this->format_number( $this->truncate_number( $value ), $numeric );
        break;

      case 'average':
      case 'average_n':
        $average = $sum_count > 1 ? $value / $sum_count : $value;
        $formatted = $this->format_number( $average, $numeric );
        break;
      
      case 'currency':
        $formatted = \PDb_Localization::display_currency( $value, $this->field );
        break;

      case 'years':
      case 'weeks':
      case 'months':
      case 'days':
        $formatted = $this->truncate_number( $value / $this->date_divisor( $format_tag ) );
        break;

      case 'year':
      case 'month':
      case 'day_month':
      case 'month_day':
      case 'week':
      case 'day':
      case 'day_of_month':
      case 'day_of_week':
      case 'day_of_year':
        $formatted = $this->partial_date( $value, $format_tag );
        break;

      case 'date':
        $formatted = \PDb_Date_Display::get_date( $value );
        break;
      
      case 'auto_numeric':
        $formatted = $this->format_number( $value, \PDb_Localization::place_count( $value ) );
        break;

      case 'unformatted':
      default:
        $formatted = $value;
    }

    return $formatted;
  }
  
  /**
   * tells if the format should only be applied to displaying the value
   * 
   * this is for fields that are saved as an unformatted value, then 
   * formatted when displayed
   * 
   * @param string $format_tag
   * @return bool
   */
  public function is_display_only_format( $format_tag )
  {
    $is_numeric = $this->is_numeric_field();
    
    // remove the leading ? and brackets
    $format_tag = str_replace( array('?', ']','['), '', $format_tag );
    
    // convert the _n to a literal _n
    $format_tag = preg_replace( '/_\d+$/', '_n', $format_tag );
    
    switch ( $format_tag ) {
      
      case 'currency':
        return true;

      case 'round_n':
      case 'integer':
        return false;

      case 'average':
      case 'average_n':
        return false;

      case 'years':
      case 'weeks':
      case 'months':
      case 'days':
        return false;

      case 'year':
      case 'month':
      case 'day_month':
      case 'month_day':
      case 'week':
      case 'day':
      case 'day_of_month':
      case 'day_of_week':
      case 'day_of_year':
        return $is_numeric;

      case 'date':
        return $is_numeric;

      case 'unformatted':
      default:
        return false;
    }
  }
  
  /**
   * tells if the current field stores its value as a numeric
   * 
   * @return bool true if the value is stores as a numeric value
   */
  protected function is_numeric_field()
  {
    return apply_filters( sprintf( 'pdb-%s_is_numeric', $this->field->form_element() ), false );
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
      case 'day_of_month':
        $format = 'j';
        break;

      case 'day_of_year':
        $format = 'z';
        break;

      case 'day_of_week':
        $format = 'l';
        break;
      
      case 'day_month':
        $format = 'j F';
        break;
      
      case 'month_day':
        $format = 'F j';
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
    return $this->localize ? \PDb_Localization::format_number($number, $places) : number_format( $number, $places );
  }

}
