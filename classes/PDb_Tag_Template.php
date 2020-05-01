<?php

/*
 * handles tag replacement in a text template
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2016  xnau webdesign
 * @license    GPL2
 * @version    0.6
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
   * @var bool  if true, replacement values will not contain HTML tags
   */
  private $raw;
  
  /**
   * @var int holds a fingerprint
   */
  private $dataprint;

  /**
   * sets up the class
   * 
   * @param string $template the template containing replacement tags
   * @param array|int $data associative array of data to draw from, or an integer record ID
   * @param bool  $raw  if true, don't use HTML tags in replacement data
   */
  private function __construct( $template, $data, $raw = false )
  {
    $this->template = $template;
    $this->raw = $raw;
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
    $tag_template = new self( $template, $data );
    return $tag_template->_replace_tags();
  }

  /**
   * provides a tag-replaced string
   * 
   * @param string $template
   * @param int|array $data
   * @return string
   */
  public static function replaced_text_raw( $template, $data )
  {
    $tag_template = new self( $template, $data, true );
    $tag_template->rich_text = false;

    return $tag_template->_replace_tags();
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
    $tag_template = new self( $template, $data );
    $tag_template->rich_text = true;

    return $tag_template->_replace_tags();
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
    switch ( $name ) {
      case 'template':
        $this->template = $template;
        break;
      case 'data':
        $this->add_data( $value );
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
    if ( !is_array( $data ) ) {
      $data = array((string) $data => (string) $data);
    }
    foreach ( $data as $fieldname => $value ) {
      $data[$fieldname] = $this->value_display( $fieldname, $value );
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
    /**
     * provides a way to add custom tags before the template is parsed
     * 
     * @filter pdb-tag_template_data_before_replace
     * @param array as $tag => $value
     * @return array
     */
    $tag_data = Participants_Db::apply_filters( 'tag_template_data_before_replace', $this->data );

    $placeholders = array();

    for ( $i = 1; $i <= count( $tag_data ); $i++ ) {
      $placeholders[] = '%' . $i . '$s';
    }

    // gets the tag strings and wrap them in brackets to match them in the template
    $tags = array_map( function ( $v ) {
      return '[' . $v . ']';
    }, array_keys( $tag_data ) );

    // replace the tags with variables
    $pattern = str_replace( $tags, $placeholders, $this->template );

    // replace the variables with strings
    $fulltext = vsprintf( $pattern, $tag_data );

//    error_log(__METHOD__.' pattern: '.$pattern.' data: '.print_r($this->data,1));
//    error_log(__METHOD__.' rich text? '.($this->rich_text?'yes':'no'). '   '.$fulltext);
//    error_log(__METHOD__.' template: '.$this->template);

    return $this->rich_text ? Participants_Db::process_rich_text( $fulltext, 'tag template' ) : $fulltext;
  }

  /**
   * sets up the data source
   * 
   * @param array|int|string $data
   */
  protected function setup_data( $data = false )
  {
    if ( is_array( $data ) ) {
      $this->data = $data;
    } elseif ( is_numeric( $data ) && $record = Participants_Db::get_participant( $data ) ) {
      $this->data = $record;
    } else {
      $this->data = array();
    }
    
    $this->set_dataprint(); 
    
    if ( isset( $this->data['id'] ) ) {
      
      $cached_data = wp_cache_get( $this->data_cache_key() );
      
      if ( $cached_data ) {
        $this->data = $cached_data;
      } else {
        $this->prepare_display_values();
        wp_cache_set( $this->data_cache_key(), $this->data, '', Participants_Db::cache_expire() );
      }
    }
  }

  /**
   * provides the data array cache key
   * 
   * @return string key
   */
  private function data_cache_key()
  {
    return $this->data['id'] . '-' . $this->dataprint;
  }
  
  /**
   * sets the dataprint
   * 
   */
  private function set_dataprint()
  {
    $this->dataprint = hash( 'crc32', implode( '', array_keys( $this->data ) ) );
  }

  /**
   * prepares the data array values for inclusion in the text
   * 
   * we use the PDb_Field_Item class to generate display strings for the various 
   * field types
   * 
   * tags that are not fieldnames are retained as-is
   */
  protected function prepare_display_values()
  {
    foreach ( $this->data as $fieldname => $value ) {
      $field = $this->field_item($fieldname);
      if ( $field ) {
        $this->data[$fieldname] = $this->value_display( $field, $value );
        $this->data['title:'.$fieldname] = $field->title();
        $this->data['value:'.$fieldname] = $this->raw_value( $field, $value );
        if ( $field->is_upload_field() ) {
          $this->data['url:'.$fieldname] = $field->file_uri(); 
        }
      }
    }
  }

  /**
   * prepares an individual display value
   * 
   * @param PDb_Field_Item $field object
   * @param string $value field value
   * @return string field value display string
   */
  private function value_display( $field, $value )
  {
    $field->set_value($value);
    
    $value = $this->raw ? $field->raw_value() : $field->get_value_display();

    /**
     * @filter pdb-tag_template_field_display_value
     * @param string display value
     * @param PDb_Field_Item
     * @return string display
     */
    return Participants_Db::apply_filters( 'tag_template_field_display_value', $value, $field );
  }

  /**
   * supplies the field's raw value
   * 
   * @param PDb_Field_Item $field object
   * @param string $value field value
   * @return string
   */
  private function raw_value( $field, $value )
  {
    $field->set_value($value);
    return $field->raw_value();
  }
  
  /**
   * provides the PDb_Field_Item object
   * 
   * @return PDb_Field_Item|bool the field item object or bool false if no defined field found
   */
  private function field_item( $fieldname )
  {
    $found = false;
    $cachegroup = 'tag-template-field-item';
    $record_id = isset( $this->data['id'] ) ? $this->data['id'] : null;
    $field = wp_cache_get( $fieldname.$record_id, $cachegroup, false, $found );
    
    if ( $found ) {
      return $field;
    }
    
    $field = false;
    
    if ( array_key_exists( $fieldname, Participants_Db::$fields ) ) {
      $field = new PDb_Field_Item( (object) array('name' => $fieldname, 'module' => self::template_module(), 'record_id' => $record_id ) );  
      wp_cache_set( $fieldname.$record_id, $field, $cachegroup, Participants_Db::cache_expire() ); 
    }
    
    return $field;
  }
  
  /**
   * provides the template module string
   * 
   * this is so we can differentiate when the template is for an email
   * 
   * @return string module name
   */
  public static function template_module()
  {
    return Participants_Db::apply_filters( 'tag_template_module', 'tag-template' );
  }

}
