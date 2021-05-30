<?php

/**
 * defines a placeholder field type
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

class placeholder extends utility {

  /**
   * @var string name of the form element
   */
  const element_name = 'placeholder';

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct( self::element_name, _x( 'Placeholder', 'name of a field type that shows placeholder text', 'participants-database' ) );
  }

  /**
   * display the field value in a read context
   * 
   * @return string
   */
  protected function display_value()
  {
    if ( $this->field->has_link() ) {
      $template = '<a class="%3$s-link" href="%2$s" %4$s >%1$s</a>';
    } else {
      $template = '%1$s';
    }
    return sprintf( $template, $this->field->default_value, $this->field->link(), $this->field->name(), $this->anchor_tag_attributes() );
  }
  
}
