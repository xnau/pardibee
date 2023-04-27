<?php

/**
 * sets up the route for getting the record's export values
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

class record_export extends record_raw {
  

  /**
   * @var string 
   */
  protected $endpoint = 'record';
  

  /**
   * provides the response data
   * 
   * @return array
   */
  protected function response()
  {
    $record = db::get_record( $this->params[ 'id' ], $this->user_role );
    
    $export = [];
    
    foreach( $record as $fieldname => $value )
    {
      $config = [
          'name' => $fieldname,
          'value' => $value,
      ];
      $field = new \PDb_Field_Item( $config, $this->params[ 'id' ] );
      
      $export[$fieldname] = $field->export_value();
    }
  
    return $export;
  }
}
