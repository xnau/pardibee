<?php

/**
 * defines a field type that calculates dates
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class date_calc extends calculated_field {

  /**
   * @var string name of the form element
   */
  const element_name = 'date-calc';

  /**
   * @var string the calculation tag
   */
  const calc_tag = 'pdb_date_calc_result';

  /**
   * provides the field's title
   * 
   * @return string
   */
  protected function field_title()
  {
    return _x( 'Date Calculation', 'name of a field type that shows the result of a date calculation', 'participants-database' );
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
    foreach ( $this->template_field_list() as $fieldname ) {

      $template_field = new \PDb_Field_Item( array( 'name' => $fieldname, 'module' => 'list' ), $this->field->record_id() );

      if ( isset( $post[ $template_field->name() ] ) && !\PDb_FormElement::is_empty( $post[ $template_field->name() ] ) ) {
        // get the value from the provided data
        $field_value = \PDb_Date_Parse::timestamp( $post[ $template_field->name() ] );
      } else {
        $field_value = $template_field->db_value();
      }
      
      if ( $template_field->form_element() === 'timestamp' ) {
        $field_value = \PDb_Date_Parse::timestamp( $field_value );
      }

      $replacement_data[ $fieldname ] = $field_value;
    }
    
    $this->calculate_value( $this->filter_data( $replacement_data ) );

    $replacement_data[ self::calc_tag ] = $this->result;

    return $replacement_data;
  }

  /**
   * provides a list of the fields that are included in the template
   * 
   * @return array of field names
   */
  protected function template_field_list()
  {
    foreach ( $this->template->field_list() as $fieldname ) {
      if ( \PDb_Form_Field_Def::is_field( $fieldname ) ) {
        $field_def = \Participants_Db::$fields[ $fieldname ];
        /** @var \PDb_Form_Field_Def $field_def */
        
        // this field only works with date values from the db
        if ( in_array( $field_def->form_element(), array( 'date', 'timestamp' ) ) ) {
          $list[] = $fieldname;
        }
      }
    }

    return $list;
  }
  
  /**
   * provides the default format tag
   * 
   * @return string
   */
  protected function default_format_tag()
  {
    return '[?date]';
  }

  /**
   * provides the form element's mysql datatype
   * 
   * @return string
   */
  protected function element_datatype()
  {
    return 'BIGINT(20)';
  }
  
  /**
   * tells if the current field stores a numeric value
   * 
   * @return bool
   */
  protected function is_numeric_field()
  {
    return true;
  }
  
  /**
   * converts a mysql timestamp to a unix timestamp
   * 
   * @param string $mysql_timestamp
   * @return int unix timestamp
   */
  private function unix_timestamp( $mysql_timestamp )
  {
    return \PDb_Date_Parse::timestamp($mysql_timestamp);
  }

}
