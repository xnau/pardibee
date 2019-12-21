<?php

/*
 * class for handling the display of images for the participants database plugin
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    0.5
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Image_Handler class
 */
if (!defined('ABSPATH'))
  die;

class PDb_Image extends xnau_Image_Handler {

  /**
   * intializes the object with a setup array
   *
   * @param array $config an array of optional parameters:
   *                      'filename' => an image path, filename or URL
   *                      'classname' => a classname for the image
   *                      'wrap_tags' => array of open and close HTML
   *                      'link' URI for a wrapping anchor tag
   *                      'relstring' => string to show in the "rel" attribute of the image
   *                      'mode' => display mode: as an image or a filename or both
   *                      'module' => calling module
   *                      'attributes' => HTML element attributes 
   */
  function __construct($config)
  {
    parent::__construct($config);

    $this->classname .= ' ' . Participants_Db::$prefix . 'image image-field-wrap';

    if (empty($this->link)) {
      $this->link = $this->image_defined && Participants_Db::plugin_setting_is_true('image_link') ? $this->image_uri : '';
    }
  }

  /**
   * supplies the HTML for the image
   * 
   * @return string HTML
   */
  public function get_image_html()
  {
    switch ($this->display_mode) {
      case 'both':
        $pattern = $this->image_wrap[0] . '<img src="%5$s" %7$s /><span class="image-filename">%6$s</span>' . $this->image_wrap[1];
        break;
      case 'filename':
        $pattern = $this->image_wrap[0] . '%6$s' . $this->image_wrap[1];
        break;
      case 'none':
        $pattern = '';
        break;
      case 'image':
      default:
        $pattern = $this->image_wrap[0] . '<img src="%5$s" %7$s />' . $this->image_wrap[1];
    }
    /**
     * @filter pdb-image_wrap_template
     * @param string  the template with sprintf-style numeric placeholders
     * @param object  this instance
     * 
     * @return string the template
     */
    return sprintf( Participants_Db::apply_filters( 'image_wrap_template', $pattern, $this ), 
                    $this->wrap_class(), 
                    esc_attr($this->link), 
                    basename($this->image_uri), 
                    $this->attribute_string( $this->attributes ),
                    esc_url($this->image_uri),
                    esc_attr($this->image_file),
                    $this->attribute_string( $this->image_attributes )
          );
  }
  
  
  
  /**
   * sets up the attributes property
   * 
   * @param array $attributes from the config array
   */
  protected function set_attributes( $attributes )
  {
    if ( is_array( $attributes ) ) :
    foreach ( $attributes as $key => $value ) {
      if ( in_array( $key, Participants_Db::apply_filters('image_class_image_attributes', array( 'height', 'width', 'alt', 'longdesc' ) ) ) ) {
        $this->image_attributes[$key] = $value;
      } else {
        if ( $key === $value || $key === 'allowed' ) continue; // this filters out allowed extension configs
        $this->attributes[$key] = $value;
      }
    }
    endif;
  }

  /**
   * sets the default path to the image directory
   *
   */
  public function set_image_directory()
  {
    $this->image_directory = Participants_Db::files_path();
    $this->image_directory_uri = Participants_Db::files_uri();
  }

  /**
   * defines the default image
   *
   * @param string $image path to the default image file, relative to the WP root
   */
  public function set_default_image($image = false)
  {
    $default_image = wp_cache_get('pdb-default_image_url');
    
    if ( false === $default_image ) {
      $default_image = $image === false ? $this->default_image_src() : $image;

      // check the path for a valid image
      if ( self::is_image_file( Participants_Db::base_files_path() . ltrim( $default_image, '/' ) ) ) {
        $default_image = Participants_Db::base_files_url() . ltrim( $default_image, '/' );
      }
      wp_cache_set('pdb-default_image_url', $default_image, Participants_Db::cache_expire() );
    }
    
    $this->default_image = $default_image;
  }
  
  /**
   * provides the default image source
   * 
   * @return string
   */
  protected function default_image_src()
  {
    return Participants_Db::plugin_setting( 'default_image' );
  }

  /**
   * sets up the image wrap html
   * 
   * this gives us a chance to structure the wrap based on the image file
   * 
   * @return null
   */
  protected function _set_image_wrap()
  {
    if ($this->link === false || empty($this->link)) {

      $this->image_wrap = array(
          '<span class="%1$s">',
          '</span>'
      );
    } elseif ( Participants_Db::plugin_setting_is_true ('image_link') && $this->image_defined ) {

      $this->image_wrap = array(
          '<span class="%1$s"><a href="%2$s" %4$s title="%3$s" >',
          '</a></span>'
      );
    } elseif (!empty($this->link) and $this->link !== false) {

      $this->image_wrap = array(
          '<span class="%1$s single-record-link"><a href="%2$s" title="%3$s" >',
          '</a></span>'
      );
    }
  }

  /**
   * tests a file and sets properties if extant
   *
   * @param string $filename name of the file, could be relative path
   *
   * sets the file_exists flag to true if the file exists
   */
  protected function set_up_file_props($filename = '')
  {

    //error_log(__METHOD__.' testing:'.$filename.' getting:'.($this->_file_exists($filename)?'yes':'no'));

    $filename = empty($filename) ? $this->image_file : $filename;

    $filepath = $this->concatenate_directory_path($this->image_directory, $filename, false);

    if ($this->_file_exists($filepath)) {
      /**
       * @version 1.6.3
       * @filter pdb-image_source
       */
      $this->image_uri = Participants_Db::apply_filters('image_source', $this->image_directory_uri.$this->image_file, $this->module);
      $this->file_exists = true;
    }
  }

}
