<?php

/**
 * models a class of fields that save a dynamically-generated value to the db
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

abstract class dynamic_db_field extends core {
  
  /**
   * sets up the field type
   * 
   * @param string $name of the field type
   * @param string title of the field type
   */
  public function __construct( $name, $title )
  {
    parent::__construct( $name, $title );
    
    $this->is_dynamic_field();
    
    $this->field_list(); // cache the field list
    
    add_filter( 'pdb-before_submit_update', array( $this, 'update_db_value' ) );
    add_filter( 'pdb-before_submit_add', array( $this, 'update_db_value' ) );
  }
  
  
  
  /**
   * provides the dynamically-generated value
   * 
   * @param array $data the data
   * @return string
   */
  abstract protected function dynamic_value( $data = false );
  
  /**
   * updates the database value for the field
   * 
   * @param array $post
   * @retrn array
   */
  public function update_db_value( $post )
  {
    foreach( $this->field_list() as $dynamic_db_field ) {
      
      /** @var \PDb_Form_Field_Def $dynamic_db_field */
      
      if ( isset( $post[ $dynamic_db_field->name() ] ) ) {
      
        $field = new \PDb_Field_Item( $dynamic_db_field );
        $field->set_record_id( $post['id'] );
        $this->setup_field( $field );

        $post[ $dynamic_db_field->name() ] = $this->dynamic_value( $post );
      }
      
    }
    return $post;
  }
  
  /**
   * gets the string combine field list
   * 
   * caches a list of all the string combine fields
   * 
   * @return array of PDb_Form_Field_Def objects
   */
  private function field_list()
  {
    $cachekey = $this->name . '_field_list';
    $list = wp_cache_get( $cachekey );
    
    if ( ! is_array( $list ) ) {
      
      $list = array();
      
      foreach( \Participants_Db::$fields as $field ) {
        if ( $field->form_element() === $this->name ) {
          
          $list[] = $field;
        }
      }
      
      wp_cache_add( $cachekey, $list );
    }
    
    return $list;
  }
}
