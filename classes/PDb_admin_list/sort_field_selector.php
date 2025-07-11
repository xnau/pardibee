<?php

/**
 * provides the options for the admin list sort field selector
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2025  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_admin_list;
use \Participants_Db;

class sort_field_selector extends field_selector {
  
  
  /**
   * provides the selector options
   * 
   * @return array
   */
  public function options()
  {
    return $this->options;
  }
  
  
  /**
   * provides the list of additional groups
   * 
   * for sorting fields, we don't include this #3208
   * 
   * @return array
   */
  protected function additional_groups()
  {
    return [];
  }
  
  /**
   * provides a list of form element types to omit from the selector
   * 
   * these are fields that won't have meaningful content for a sort
   * 
   * @return array
   */
  protected function omit_element_types()
  {
    return Participants_Db::apply_filters('omit_backend_edit_form_element_type', ['captcha','placeholder','heading', 'link', 'rich-text', 'iframe', 'password', 'media-embed','shortcode'] );
  }
  
}
