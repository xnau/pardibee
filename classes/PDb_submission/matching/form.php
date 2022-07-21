<?php

/**
 * provides matching services for a form submission
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

namespace PDb_submission\matching;

defined( 'ABSPATH' ) || exit;

class form extends record {

  /**
   * tells if the current operation is a csv import
   * 
   * @return bool
   */
  public function is_csv_import()
  {
    return false;
  }

  /**
   * sets the default match mode (duplicate preference)
   */
  protected function setup_match_mode()
  {
    /*
     * match mode is set according to the "Duplicate Record Preference" setting
     * * add
     * * update
     * * validation error
     * 
     */
    $this->set_match_mode( \Participants_Db::plugin_setting( 'unique_email', '0' ) );

    if ( \Participants_Db::plugin_setting( 'admin_edits_validated', '0' ) == '0' && \Participants_Db::is_admin() && \Participants_Db::current_user_has_plugin_role( 'admin', 'record edit/add skip validation' ) ) {
      /*
       * set the preference to 0 if current user is an admin in the admin and not 
       * importing a CSV
       * 
       * this allows administrators to create new records without being affected 
       * by the duplicate record preference
       */
      $this->set_match_mode( 'add' );
    }

    /*
     * to prevent possible exposure of private data when using multipage forms we 
     * don't allow the "update" preference for multipage forms
     * 
     * we also don't allow the "insert" preference because duplicate records can be 
     * created if the user goes back to the signup form page
     */
    if ( \Participants_Db::is_multipage_form() ) {
      $this->set_match_mode( 'skip' );
    }
  }

  /**
   * provides the record ID based on the action
   * 
   * this changes the action according to the match mode
   * 
   * @param string @action
   * @return string the updated action value
   */
  public function get_action( $action )
  {
    /**
     * @version 1.6.2.6
     * 
     * if we are adding a record in the admin, we don't perform a record update 
     * on a matching record if the intent is to add a new record
     */
    if ( \Participants_Db::is_admin() && $action === 'insert' ) {
      $this->set_match_mode( 'skip' );
    }
    
    return parent::get_action($action);
  }

  /**
   * sets the current match field
   * 
   * this is the field that is checked for a match between two records
   * 
   */
  protected function set_match_field()
  {
    $this->match_field = \Participants_Db::plugin_setting( 'unique_field', 'id' );
  }

}
