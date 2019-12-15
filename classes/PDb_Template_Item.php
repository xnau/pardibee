<?php
/*
 * class providing common functionality for displaying
 * database item objects (records, fields and groups) in templates
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.4
 * @link       http://xnau.com/wordpress-plugins/
 *
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Template_Item {
  
  /**
   *
   * @var string the unique string identifier for the object
   */
  var $name;
	
  /**
   *
   * @var string the printable title of the object
   */
  var $title;
	
	/**
   *
   * @var int the id of the current record
   */
	var $record_id;
	
	/**
   *
   * @var string the instantiating module
   */
	var $module;
  
  /**
   *
   * @var type array of field objects
   */
  var $fields;
  
  /**
   * @var array of all record values, indexed by name
   */
  public $values;
  
  /**
   * @var array holds an array of class strings
   */
  private $class = array();
  
  /**
   * @var bool true if the item is a Participants Database field
   */
  protected $is_pdb_field = false;
  
  /**
   * tests a value for emptiness, includinf arrays with empty elements
   * 
   * @param mixed $value the value to test
   * @return bool
   */
  public function is_empty($value) {
    
    if (is_array($value)) $value = implode('', $value);
    
    return empty($value);
  }
  
  /**
   * adds a CSS class name
   * 
   * @param string $class class the classname to add 
   */
  public function add_class( $class )
  {
    $this->class[] = esc_attr( $class );
  }

  /**
   * displays an object value with deslashing and entity encoding
   *
   * @param string $string the value to be printed
   */
  protected function print_value() {
    
    echo prepare_display_value($this->value);
    
  }
  
  /**
   * prepare a field for display
   *
   * primarily to deal with encoded characters, quotes and slashes
   * 
   * @param string $string the value to be prepared
   */
  protected function prepare_display_value( $string ) {
    
    return Participants_Db::apply_filters('translate_string', $string);
    //str_replace(array('"',"'"), array('&quot;','&#39;'), stripslashes($string));
    //htmlspecialchars( stripslashes( $string ), ENT_QUOTES, "UTF-8", false );
  
  }
  
  /**
   * preps a display tring allowing HTML
   * 
   * @param string $string the string to display
   * @return string sanitized for HMTL
   */
  public static function html_allowed( $string )
  {
    return wp_kses( $string, self::allowed_tags() );
  }
  
  /**
   * provides a list of allowed tags for HTML-enabled output
   * 
   * @return array
   */
  public static function allowed_tags()
  {
    return Participants_Db::apply_filters('field_attributes_allowed_html_tags',array(
        'a' => array('href' => array(), 'title' => array(), 'target' => array()),
        'pre' => array(), 'em' => array(), 'strong' => array(), 'br' => array(), 'span' => array( 'class' => array() ),
        'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
        'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
        'img' => array('src' => array(), 'class' => array(), 'alt' => array())
    ) );
  }
  
  /**
   * prints a CSS classname
   *
   * @param string $name    string identifier for the class, defaults to the
   *                        name of the object
   */
  protected function print_CSS_class( $name = false, $prefix = true ) {
    
    $name = false === $name? $this->name : $name;
    
    echo $this->prepare_CSS_class( $name, $prefix );
    
  }
  
  /**
   * prepares a CSS classname
   *
   * attempts to make sure the classname is valid since this can be set in the
   * shortcode and you never know what people will try to do
   *
   * this will also add the global CSS prefix to maintain namespace
   *
   * @param string $name    string identifier for the class, defaults to the
   *                        name of the object
   * @param bool   $prefix  true to add global prefix, defaults to true
   *
   * @return string the prepared class name
   *
   */
  protected function prepare_CSS_class( $name = false, $prefix = true ) {
    
    if ( false === $name ) $name = $this->name;
    
    $classname = self::prep_css_class_string($name);
    
    return $prefix ? Participants_Db::$prefix.$classname : $classname;
    
  }
  
  /**
   * prepares a string for use as a css class
   * 
   * @param string $string string to prepare
   * @return string
   */
  public static function prep_css_class_string( $string )
  {
    // make sure it does not begin with a numeral
    $classname = preg_replace( '/^([0-9])/','n$1', $string );
    // clean out any non-valid CSS name characters
    return preg_replace( '/[^_a-zA-Z0-9-]/','', $classname );
  }
  
  /**
   * assigns the object properties that match properties in the supplied object
   * 
   * @param object $item the supplied object or config array
   */
  protected function assign_props( $item ) {
    
    $item = (object) $item;
    
    $class_properties = array_keys( get_class_vars( get_class( $this ) ) );
    
    // grab and assign the class properties from the provided object
    foreach( $class_properties as $property ) {
      
      if ( isset( $item->$property ) ) {
        
        $this->$property = $item->$property;
      
      }
      
    }
    
  }
  
  /**
   * prints an HTML class value
   */
  public function print_class() {
    echo $this->get_class();
  }
  
  /**
   * supplies the full class name
   * 
   * @return string class name
   */
  public function get_class()
  {
    return implode(' ', $this->class);
  }
  
  /**
   * tells if the item is a Participants Database defined field
   * 
   * @return bool
   */
  public function is_pdb_field()
  {
    return (bool) $this->is_pdb_field;
  }
  
}