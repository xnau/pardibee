<?php

/**
 * sets up the CSV import operation
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

namespace PDb_import;

defined( 'ABSPATH' ) || exit;

class controller {
  
  /**
   * @var \PDb_Import\process instance
   */
  private $process;
  
  /**
   * 
   */
  public function __construct()
  {
    add_action( 'init', array( $this, 'process_handler' ) );
    add_filter( 'pdb-get_import_process', array( $this, 'get_process_handler' ) );
  }
  
  /**
   * provides the process handler object
   * 
   * @return \PDb_Import\process instance
   */
  public function get_process_handler()
  {
    return $this->process;
  }
  
  /**
   * initializes the process handler
   */
  public function process_handler()
  {
    $this->process = new process();
  }
}
