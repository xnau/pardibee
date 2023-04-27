<?php

/**
 * provides the list data using export values
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2023  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_submission\rest_api\get;
use PDb_submission\rest_api\db;

class list_html extends list_raw {

  /**
   * @var string 
   */
  protected $endpoint = 'list/html';
  
  /**
   * provides the response data
   * 
   * @return array
   */
  protected function response()
  {
    $list = db::get_list( $this->user_role, $this->list_filter_params() );
    
    $export = [];
    
    foreach( $list as $record_id => $record_data )
    {
      $record = [];
      foreach( $record_data as $fieldname => $value )
      {
        $config = [
            'name' => $fieldname,
            'value' => $value,
        ];
        $field = new \PDb_Field_Item( $config, $record_id );

        $record[$fieldname] = $field->get_value_display();
      }
      $export[$record_id] = $record;
    }
    
    return $export;
  }
  
}
