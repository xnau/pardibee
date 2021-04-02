<?php

/**
 * class for handling the user feedback after a form submission
 * 
 * this class is a stub for more abstraction in future updates
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

namespace PDb_submission;

class feedback {
  
  /**
   * @var array of signup feedback property values
   */
  private $properties;

  /**
   * sets up the object
   * 
   * @param array $props
   */
  public function __construct( $props = array() )
  {
    $this->properties = $props;
  }

  /**
   * provides a property value
   * 
   * @param string $name of the property
   */
  public function __get( $name )
  {
    try {
      $value = $this->properties[ $name ];
    } catch ( Exception $exc ) {
      $value = '';
      Participants_Db::debug_log( __METHOD__ . ' property "' . $name . '" not found.' );
    }

    return $value;
  }

  /**
   * sets a property value
   * 
   * @param string $name of the property
   * @param mixed $value
   */
  public function __set( $name, $value )
  {
    $this->properties[ $name ] = $value;
  }

}
