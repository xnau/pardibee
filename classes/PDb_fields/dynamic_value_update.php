<?php

/**
 * handles updating dynamic db fields as a background process
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

defined( 'ABSPATH' ) || exit;

class dynamic_value_update extends \WP_Background_Process {

  /**
   * @var string stem name for the action
   */
  public $action = 'pdb_dynamic_value_update_';
  
  /**
   * @var \PDb_fields\dynamic_db_field the current field type object 
   */
  public $dynamic_db_field;

  /**
   * 
   * @param \PDb_fields\dynamic_db_field $dynamic_db_field
   */
  public function __construct( $dynamic_db_field )
  {
    $this->action .= $dynamic_db_field->name();
    $this->set_object($dynamic_db_field);
    parent::__construct();
  }
  
  /**
   * sets the dynamic_db_field property
   * 
   * @param \PDb_fields\dynamic_db_field $dynamic_db_field
   */
  public function set_object( $dynamic_db_field )
  {
    $this->dynamic_db_field = $dynamic_db_field;
  }

	/**
	 * Get post args
   * 
   * this serializes the data array to avoid hitting php max input vars limits 
   * with large imports
	 *
	 * @return array
	 */
	protected function get_post_args()
  {
    $args = parent::get_post_args();
    
    $args['body'] = serialize( $args['body'] );
    
    return $args;
  }

  /**
   * updates the dynamic value for a field
   * 
   * @param object $packet
   */
  protected function task( $packet )
  {
    $this->dynamic_db_field->update_record( $packet );

    return false;
  }

  /**
   * called when all operations are complete
   */
  protected function complete()
  {
    parent::complete();

    do_action( $this->action . '_complete' );
  }

}
