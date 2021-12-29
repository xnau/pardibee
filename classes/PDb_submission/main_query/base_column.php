<?php

/**
 * models the column for use in assembling the main query
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

namespace PDb_submission\main_query;

use \Participants_Db,
    \PDb_Date_Parse;

abstract class base_column {

  /**
   * @var \PDb_Field_Item current field item object
   */
  protected $field;

  /**
   * @var string the incoming column value
   */
  protected $value;
  
  /**
   * @var bool skip flag
   * 
   * if true, the column is not added to the query
   */
  protected $skip = false;

  /**
   * @param object $column the field definition data
   * @param string $value
   */
  public function __construct( $column, $value )
  {
    $this->field = new \PDb_Field_Item( $column );
    $this->value = $value;
    $this->setup_value();
    $this->setup_readonly();
  }

  /**
   * provides the column's query clause
   * 
   * @return string
   */
  public function query_clause()
  {
    return "`" . $this->field->name() . "` = " . ( $this->value === null ? "NULL" : "%s" );
  }

  /**
   * provides the column value
   * 
   * @return string|int|bool
   */
  public function value()
  {
    return $this->value;
  }
  
  /**
   * provides the column value from imported data
   * 
   * @return string|int|bool
   */
  public function import_value()
  {
    $import_value = $this->value === 'null' ? null : $this->value;
    
    if ( ! is_null( $import_value ) && $this->field->is_multi() ) {
      $import_value = serialize( \PDb_Field_Item::field_value_array( $this->value ) );
    }
    
    return $import_value;
  }

  /**
   * provides the main_query object
   * 
   * @return \PDb_submission\main_query\base_query
   */
  protected function main_query()
  {
    return base_query::instance();
  }
  
  /**
   * tells if the incoming value should be added to the query
   * 
   * @param string $write_mode insert or update
   * @return bool
   */
  public function add_to_query( $write_mode )
  {
    return ! ( $this->skip || $this->skip_imported_value() );
  }

  /**
   * tells if the imported value should be skipped
   * 
   * @return bool
   */
  public function skip_imported_value()
  {
    // don't update the value if importing a CSV and the incoming value is empty #1647
    /**
     * @filter pdb-allow_imported_empty_value_overwrite
     * @param bool whether to skip or not
     * @param mixed the importing value
     * @param \PDb_Field_Item the current field
     * @return bool if true, skip importing the column
     */
    return $this->main_query()->is_import() && Participants_Db::apply_filters( 'allow_imported_empty_value_overwrite', false, $this->value, $this->field ) === false && $this->value === '';
  }

  /**
   * sets the value property
   */
  protected abstract function setup_value();

  /**
   * checks for a readonly exception
   * 
   * this is for the purpose of preventing an unauthorized user from changing a 
   * read only value in the record
   */
  private function setup_readonly()
  {
    if (
            $this->field->is_readonly() &&
            !$this->field->is_hidden_field() &&
            \Participants_Db::current_user_has_plugin_role( 'editor', 'readonly access' ) === false &&
            \Participants_Db::apply_filters( 'post_action_override', filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING ) ) !== 'signup' &&
            $this->main_query()->is_func_call() === false
    ) {
      $this->value = '';
    }
  }

}
