<?php

/**
 * handles locating the template for a shortcode
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2022  xnau webdesign
 * @license    GPL3
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
namespace PDb_shortcodes;

use \Participants_Db;

class template {

  /**
   * @var \PDb_shortcodes\template instance
   */
  private static $instance;

  /**
   * @var string name of the custom templates directory
   */
  private $directory_name;

  /**
   * provides the template path
   * 
   * @param string $template_name name of the template
   * @param string $module the current module
   * @return string template path
   */
  public static function template_path( $template_name, $module )
  {
    return self::get_instance()->find_template( $template_name, $module );
  }
  
  /**
   * provides the template's version value
   * 
   * this comes from the file header
   * 
   * @param string $template_path full path to the template file
   * @return string the version value
   */
  public static function template_version( $template_path )
  {
    return self::get_template_version( $template_path );
  }

  /**
   * provides the current instance
   * 
   * @return \PDb_shortcodes\template instance
   */
  private static function get_instance()
  {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * 
   */
  public function __construct()
  {
    /**
     * provides a way to use a custom template directory name
     * 
     * @filter pdb-custom_template_directory
     * @param string the directory name
     * @return string
     */
    $this->directory_name = Participants_Db::apply_filters( 'custom_template_directory', Participants_Db::PLUGIN_NAME . '-templates' );
  }

  /**
   * provides the template search method sequence
   * 
   * @return array of method names
   */
  private function template_method_sequence()
  {
    return array(
        'initial_template_path',
        'built_in_template_path',
        'custom_template_path',
        'theme_template_path',
    );
  }

  /**
   * provides the template path
   *
   * @param string $template_name name of the template
   * @param string $module the current module
   * @return string template path
   */
  private function find_template( $template_name, $module )
  {
    $template_filename = 'pdb-' . $module . '-' . $template_name . '.php';

    foreach ( $this->template_method_sequence() as $method ) {
      $template = $this->$method( $template_filename );

      if ( file_exists( $template ) ) {
        break;
      }
    }

    // use the default template if the named template can't be found
    if ( !file_exists( $template ) )
    {
      $default_template = Participants_Db::$plugin_path . 'templates/pdb-' . $module . '-default.php';

      if ( $module !== 'API' )
      {
        Participants_Db::debug_log( __METHOD__ . ' custom template not found: "' . $this->custom_template_path($template_filename) . '," using the default template' );
      }

      $template = $default_template;
    }

    if ( !file_exists( $template ) )
    {
      if ( $module !== 'API' )
      {
        // API calls don't use a template
        Participants_Db::debug_log( __METHOD__ . ' template not found: ' . $template );
      }
      $template = false;
    }

    return $template;
  }

  /**
   * provides the initial template path
   * 
   * @param string $template_filename
   * @return string path
   */
  private function initial_template_path( $template_filename )
  {
    /**
     * filter for providing a template path directly or to change the name of the 
     * requested template file
     * 
     * @filter pdb-template_select
     * @param string the name of the template file
     * @return template file name or absolute path to the template file
     */
    return Participants_Db::apply_filters( 'template_select', $template_filename );
  }

  /**
   * provides the built-in template path
   * 
   * @param string $template_filename
   * @return string path
   */
  private function built_in_template_path( $template_filename )
  {
    return trailingslashit( Participants_Db::$plugin_path ) . 'templates/' . $template_filename;
  }

  /**
   * provides the custom template path
   * 
   * @param string $template_filename
   * @return string path
   */
  private function custom_template_path( $template_filename )
  {
    return $this->template_directory() . $template_filename;
  }

  /**
   * provides the theme template path
   * 
   * this is for backward compatibility
   * 
   * @param string $template_filename
   * @return string path
   */
  private function theme_template_path( $template_filename )
  {
    return Participants_Db::apply_filters( 'custom_template_location', get_stylesheet_directory() . '/templates/' ) . $template_filename;
  }

  /**
   * provides the template directory path
   * 
   * creates it if it does not exist
   * 
   * @return string
   */
  public function template_directory()
  {
    $template_path = $this->template_base_path() . trailingslashit( $this->template_directory_name() );

//    error_log( __METHOD__ . ' template path: ' . $template_path );

    if ( !is_dir( $template_path ) ) {
      $this->make_template_directory( $template_path );
    }

    return $template_path;
  }

  /**
   * provides the absolute base path to the custom templates directory
   * 
   * this is normally the defined content directory
   * 
   * @filter pdb-custemp_templates_path
   * 
   * @return string
   */
  public function template_base_path()
  {
    return trailingslashit( apply_filters( 'pdb-custemp_templates_path', trailingslashit( WP_CONTENT_DIR ) ) );
  }

  /**
   * supplies a template directory name
   * 
   * this is to provide multisite support
   * 
   * @global wpdb $wpdb
   * @return string
   */
  private function template_directory_name()
  {
    $template_dirname = $this->directory_name;
    global $wpdb;
    $current_blog = $wpdb->blogid;
    if ( $current_blog > 0 ) {
      $template_dirname .= '/blog-' . $current_blog;
    }
    return $template_dirname;
  }

  /**
   * attempt to create the uploads directory
   *
   * sets an error if it fails
   * 
   * @param bool success
   */
  private function make_template_directory( $dir = '' )
  {

    $dir = empty( $dir ) ? $this->template_base_path() . trailingslashit( $this->template_directory_name() ) : $dir;
    $savedmask = umask( 0 );
    $status = true;
    if ( mkdir( $dir, 0755, true ) === false ) {

      Participants_Db::debug_log( __METHOD__ . sprintf( __( ' The template directory (%s) could not be created.', 'participants-database' ), $dir ) );

      $status = false;
    }
    umask( $savedmask );
    return $status;
  }

  /**
   * sets the template version property
   * 
   * @param string $template_path full path to the template
   * @return string the found template version
   */
  private static function get_template_version( $template_path )
  {
    $version = 0;
    if ( $template_path ) {
      $contents = file_get_contents( $template_path );
      $findversion = preg_match( '/@version (.+)\b/', $contents, $matches );
      if ( $findversion === 1 ) {
        $version = $matches[1];
      }
    }
    return $version;
  }

}
