<?php

/**
 * processes the import submission
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_import;

defined( 'ABSPATH' ) || exit;

class process extends \WP_Background_Process {

  use store;
  
  /**
   * @var string name for the action
   */
  protected $action = 'pdb_import';
  
  /**
   * @var string name for the settings transient
   */
  const setting = 'pdb_import_settings';

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
   * sets up the store class
   * 
   * @param array $column_names
   * @param string $match_mode
   * @param string $match_field
   */
  public function setup( $column_names, $match_mode, $match_field )
  {
    set_transient( self::setting, array(
        'column_names' => $column_names,
        'match_mode' => $match_mode,
        'match_field' => $match_field
    ));
  }
  
  /**
   * performs the import on a single line
   * 
   * @param array $line a single line from the CSV file
   */
  protected function task( $line )
  {
    $this->import( $line, $this->settings() );
    
    return false;
  }
  
  /**
   * provides the settings array
   * 
   * @return array
   */
  private function settings()
  {
    return get_transient( self::setting );
  }
  
  /**
   * this is called when the queue is cleared and the process is complete
   */
  protected function complete()
  {
    parent::complete();
    
    tally::get_instance()->complete();
  }
}
