<?php

/*
 * handles tag replacement in a text template
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Tag_Template {

  /**
   * @var string the template
   */
  private $template = '';

  /**
   * @var array the data array
   */
  private $data = array();
  
  /**
   * @var bool rich text flag
   */
  private $rich_text = false;

  /**
   * sets up the class
   * 
   * @param string $template the template containing replacement tags
   * @param array|int $data associative array of data to draw from, or an integer record ID
   */
  private function __construct( $template, $data )
  {
    $this->template = $template;
    $this->setup_data( $data );
  }

  /**
   * provides a tag-replaced string
   * 
   * @param string $template
   * @param int|array $data
   * @return string
   */
  public static function replaced_text( $template, $data )
  {
    $template = new self( $template, $data );
    return $template->_replace_tags();
  }

  /**
   * provides a tag-replaced string that includes rich text
   * 
   * @param string $template
   * @param int|array $data
   * @return string
   */
  public static function replaced_rich_text( $template, $data )
  {
    $template = new self( $template, $data );
    $template->rich_text = true;
    return $template->_replace_tags();
  }
  /**
   * supplies a class instance
   * @param string $template
   * @param int|array $data
   * 
   * @return object
   */
  public static function get_instance( $template = '', $data = false )
  {
    return new self( $template, $data );
  }
  /**
   * provides the string-replaced text
   * 
   * @return string
   */
  public function get_replaced_text()
  {
    return $this->_replace_tags();
  }
  /**
   * sets a value
   * 
   * @param string $template
   */
  public function __set( $name, $value )
  {
    switch ($name) {
      case 'template':
        $this->template = $template;
        break;
      case 'data':
        $this->add_data($value);
        break;
      case 'rich_text':
        $this->rich_text = (bool) $value;
        break;
    }
  }
  
  /**
   * adds data fields to the data array
   * 
   * @param array the fields to add $name => $value
   */
  public function add_data( $data )
  {
    if ( ! is_array( $data ) ) {
      $data = array( (string) $data => (string) $data );
    }
    $this->data = $data + $this->data; 
  }

  /**
   * maps a sets of values to "tags"in a template, replacing the tags with the values
   * 
   * @return string template with all matching tags replaced with values
   */
  private function _replace_tags()
  {

    $placeholders = array();

    for ($i = 1; $i <= count($this->data); $i++) {
      $placeholders[] = '%' . $i . '$s';
    }
    
    // gets the tag strings and wrap them in brackets to match them in the template
    $tags = array_map( function ( $v ) { return '[' . $v . ']'; }, array_keys( $this->data ) );

    // replace the tags with variables
    $pattern = str_replace( $tags, $placeholders, $this->template );

    // replace the variables with strings
    $fulltext = vsprintf( $pattern, $this->data );
    
//    error_log(__METHOD__.' pattern: '.$pattern.' data: '.print_r($this->data,1));
    
    return $this->rich_text ? Participants_Db::process_rich_text( $fulltext, 'tag template' ) : $fulltext;
  }

  /**
   * sets up the data source
   * 
   * @param array|int $data
   */
  protected function setup_data( $data = false )
  {
    if (is_array($data)) {
      $this->data = $data;
    } elseif (is_numeric($data) && $record = Participants_Db::get_participant($data)) {
      $this->data =  $record;
    } else {
      $this->data = array();
    }
    $this->prepare_display_values();
  }
  
  /**
   * prepares the data array values for inclusion in the text
   * 
   * we use the PDb_Field_Item class to generate display strings for the various 
   * field types
   * 
   * @depends PDb_Field_Item
   * 
   */
  protected function prepare_display_values()
  {
    foreach ( $this->data as $fieldname => &$value ) {
      $field = new PDb_Field_Item( array( 'name' => $fieldname, 'value' => $value, 'module' => 'tag-template' ) );
      /**
       * @version 1.7.0.8 prevent non-pdb field items from using HTML Bug #1343
       * 
       */
      if ( ! $field->is_pdb_field() ) {
        $field->html_mode(false);
      }
      $value = $field->get_value();
    }
  }
}
