<?php

/**
 * defines a field that shows the result of a simple calculation
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

class numeric_calc extends templated_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'numeric-calc';
  
  /**
   * @var string the calculation tag
   */
  const calc_tag = 'pdb_numeric_calc_result';
  
  /**
   * @var array of calculation steps
   */
  private $calc_list;
  
  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name );
  }
  
  /**
   * provides the field's title
   * 
   * @return string
   */
  protected function field_title()
  {
    return _x( 'Numeric Calculation', 'name of a field type that shows the result of a calculation', 'participants-database' );
  }
  
  /**
   * replaces the template with string from the data
   * 
   * @param array|bool $data associative array of data or bool false if no data
   * @return string
   */
  protected function replaced_string( $data )
  {
    $replacement_data = $this->replacement_data( $data );
    
    return \PDb_Tag_Template::replace_text( $this->prepped_template(), $replacement_data );
  }
  
  /**
   * extracts the calculation part of the template
   * 
   * @return string
   */
  private function calc_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$2', $this->field->default_value() );
  }
  
  /**
   * replaces the calculation part of the template with the calculation tag
   * 
   * @return string
   */
  private function prepped_template()
  {
    return preg_replace( '/^(.*?)(\[.+\])(.*)$/', '$1['. self::calc_tag . ']$3', $this->field->default_value() );
  }
  
  /**
   * provides the replacement data
   * 
   * @param array|bool $post the provided data
   * @return array as $tagname => $value
   */
  protected function replacement_data( $post )
  {
    $source_data = $post ? $post : array();
    $replacement_data = array();
    
    // iterate through the fields named in the template
    foreach( $this->template_field_list() as $fieldname ) {
      
      $template_field = new \PDb_Field_Item( array('name' => $fieldname, 'module' => 'list' ), $this->field->record_id );
      
      if ( $template_field->form_element() === $this->name ) {
        
        /* if we are using the value from another string combine field, get the 
         * value directly from the db to avoid recursion
         */
        $replacement_data[$fieldname] = $this->field_db_value( $template_field->name() );
        $replacement_data['value:'.$fieldname] = $source_data[$fieldname];
        
      } else {
      
        if ( isset( $post[$template_field->name()] ) && ! \PDb_FormElement::is_empty( $post[$template_field->name()] ) ) {
          // get the value from the provided data
          $template_field->set_value( $post[$template_field->name()] );
        }

        $replacement_data[$fieldname] = $template_field->has_content() ? $template_field->value : '';
      }
    }
    
    $replacement_data[ self::calc_tag ] = $this->calculated_value( $replacement_data );
    
    return $replacement_data;
  }
  
  /**
   * provides the calculated total
   * 
   * @param array $replacement_data the prepared data from the template
   * @return string
   */
  private function calculated_value( $replacement_data )
  {
    error_log(__METHOD__.' template: '.$this->calc_template() );
    error_log(__METHOD__.' replacement data: '.print_r($replacement_data,1));
    
    $this->calc_list($replacement_data);
    
    error_log(__METHOD__.' calc list: '.print_r($this->calc_list,1));
    
    return $this->get_calculated_value();
  }
  
  /**
   * performs the calculation on the calc list
   * 
   * @return int|float
   */
  private function get_calculated_value()
  {
    // first pass to calc the sums
    $sum_list = array();
    $sum_count = 0;
    $sum = 0;
    $op = 'add';
    
    foreach( $this->calc_list as $item ) {
      
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
            }
          }
          
      }
      
    error_log(__METHOD__.' sum: '.$sum.' sum count: '.$sum_count.' sum list: '.print_r($sum_list,1));
      
    }
    
    $product = null;
    $sum_index = 0;
    // now perform the multiplication or division
    foreach( $this->calc_list as $i => $item ) {
      
      switch ( $item ) {
        
        case '*':
          $product = $sum_list[$sum_index] * $sum_list[$sum_index+1];
          $sum_index = $sum_index+2;
          break;
        
        case '/':
          $product = $sum_list[$sum_index] / $sum_list[$sum_index+1];
          $sum_index = $sum_index+2;
          break;
        
        case '=':
          if ( is_null( $product ) ) {
            $product = $sum_list[$sum_index];
            $sum_index++;
          }
          $format = $this->calc_list[$i+1];
          break 2;
      }
    }
    
    $total = $product;
    
    if ( $format ) {
      $total = $this->format( $total, $format, $sum_count );
    }
    
    return $total;
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
    
    if ( preg_match( '/(.+_)(\d+)$/', $format_tag, $matches ) === 1 ) {
      $numeric = $matches[2];
      $format_tag = $matches[1] . 'n';
    }
    
    switch ($format_tag) {
      
      case 'round_n':
        $formatted = number_format( $value, $numeric );
        break;
      
      case 'integer':
        $formatted = $this->truncate_number( $value );
        break;
      
      case 'year':
      case 'week':
      case 'month':
      case 'day':
        $formatted = $this->truncate_number( $value / constant( strtoupper( $format_tag ) . '_IN_SECONDS' ) );
        break;
      
      case 'average':
      case 'average_n':
        $formatted = number_format( $value / $sum_count, $numeric );
        break;
        
      default:
        $formatted = $value;
    }
    
    return $formatted;
  }
  
  /**
   * truncates the fractional part of a float
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
    
    return $int;
  }
  
  /**
   * provides the calc list array
   * 
   * @param array $data replacement data
   * @return array
   */
  private function calc_list ( $data )
  {
    $calc_list = array();
    $tag = false;
    $buffer = '';
    
    foreach( str_split( $this->calc_template() ) as $chr ) {
      
      switch ($chr) {
        
        case '+':
        case '-':
        case '*':
        case '/':
        case '=':
          $calc_list[] = $buffer;
          $calc_list[] = $chr;
          $buffer = '';
          break;
        
        case '[':
          $tag = true;
          $buffer = '';
          break;
        
        case ']':
          $tag = false;
          
          /* tag is closed: get the value from the data, but only if it is a 
           * number of some kind
           */
          if ( isset( $data[$buffer] ) && is_numeric( $data[$buffer] ) ) {
            
            // get the value of the tag
            $calc_list[] = $data[$buffer];
            
          } elseif ( preg_match( '/^(#|\?).+/', $buffer ) ) {
            
            // this is a key value, add it to the list as-is
            $calc_list[] = $buffer;
          } elseif ( is_numeric( $buffer ) ) {
            // add any literal number
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
  
}
