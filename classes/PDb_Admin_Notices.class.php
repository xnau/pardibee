<?php

/**
 * handles feedback messages in the admin
 * 
 * we can enable PHP errors in the admin messaging with this:
 *  set_error_handler( array( 'PDb_Admin_Notices', 'error_handler' ) );
 * 
 * remember to disconnect the handler when the script is finished:
 *  restore_error_handler();
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Alexandros Georgiou & Roland Barker <webdesign@xnau.com>
 * @copyright  2017  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       https://www.alexgeorgiou.gr/persistently-dismissible-notices-wordpress/
 * @depends    
 */
defined( 'ABSPATH' ) || die( '-1' );

class PDb_Admin_Notices {

  /**
   *
   * @var PDb_Admin_Notices the class instance 
   */
  private static $_instance;
  
  /**
   *
   * @var object  the current notices
   */
  private $admin_notices;

  /**
   * @var string list of message types
   */
  const TYPES = 'error,warning,info,success';
  
  /**
   * @var string  the option stem
   */
  const dismiss_option = 'pdb_admin_notices_dismissed_';
  
  /**
   * @var string  GET var key
   */
  const get_key = 'pdb_admin_notices_dismiss';

  /**
   * these are our API calls
   * 
   * @param string  $message
   * @param bool    $dismiss_option
   * @param bool    $force_show if true, overrides the dismiss
   */
  public function error( $message, $dismiss_option = true, $force_show = false )
  {
    $this->notice( 'error', $message, $dismiss_option, $force_show );
  }

  public function warning( $message, $dismiss_option = true, $force_show = false )
  {
    $this->notice( 'warning', $message, $dismiss_option, $force_show );
  }

  public function success( $message, $dismiss_option = true, $force_show = false )
  {
    $this->notice( 'success', $message, $dismiss_option, $force_show );
  }

  public function info( $message, $dismiss_option = true, $force_show = false )
  {
    $this->notice( 'info', $message, $dismiss_option, $force_show );
  }

  /**
   * 
   */
  private function __construct()
  {
    $this->admin_notices = new stdClass();
    foreach ( explode( ',', self::TYPES ) as $type ) {
      $this->admin_notices->{$type} = array();
    }
    add_action( 'admin_init', array( $this, 'action_admin_init'), 20 );
    add_action( 'admin_notices', array( $this, 'action_admin_notices') );
    add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts') );
    add_action( 'participants_database_uninstall', array( $this, 'uninstall' ) );
  }

  /**
   * provides a class instance
   * 
   * @return PDb_Admin_Notices a class instance
   */
  public static function get_instance()
  {
    if ( !( self::$_instance instanceof self ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * initialize the class functionality
   */
  public function action_admin_init()
  {
    $message_id = filter_input( INPUT_GET, self::get_key, FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE );
    if ( $message_id ) {
      update_option( self::dismiss_option . $message_id, true );
      wp_die();
    }
  }

  public function action_admin_enqueue_scripts()
  {
    if ( $this->is_plugin_screen() ) {
      wp_enqueue_script( Participants_Db::$prefix . 'admin-notices');
    }
  }

  /**
   * fired on admin_notices action
   */
  public function action_admin_notices()
  {
    if ( $this->is_plugin_screen() ):
      foreach ( explode( ',', self::TYPES ) as $type ) {
      foreach ( $this->admin_notices->{$type} as $admin_notice ) {

        $dismiss_url = add_query_arg( array(
            self::get_key => $admin_notice->id
                ), admin_url() );

        if ( !get_option( self::dismiss_option . $admin_notice->id ) ) {
          ?><div
            class="notice pdb_admin_notices-notice notice-<?php
            echo $type;

            if ( $admin_notice->dismiss_option ) {
              echo ' is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
            }
            ?>">
            <h4><?php echo Participants_Db::$plugin_title ?>:</h4>
            <p><span class="dashicons <?php echo $this->dashicon( $type ) ?>"></span>&nbsp;<?php echo esc_html( $admin_notice->message ) ?></p>

          </div><?php
        }
      }
    }
    endif;
  }
  
  /**
   * tells if the current admin screen is a plugin screen
   * 
   * @return bool
   * 
   */
  private function is_plugin_screen()
  {
    $page = get_current_screen();
    return stripos( $page->id, Participants_Db::PLUGIN_NAME ) !== false;
  }
  
  /**
   * provides the dashicons icon text
   * 
   * @param string $type message type
   * @return string
   */
  private function dashicon( $type )
  {
    switch ($type) {
      case 'warning':
      case 'error':
        return 'dashicons-warning';
      case 'success':
        return 'dashicons-yes';
      case 'info':
      default:
        return 'dashicons-info';
    }
  }

  /**
   * adds the notice
   * 
   * @param string  $type
   * @param string  $message
   * @param bool    $dismiss_option if true, is dismissable
   * @param bool    $force_show if true, overrides the dismiss
   */
  private function notice( $type, $message, $dismiss_option, $force_show )
  {
    $notice = new stdClass();
    $notice->message = $message;
    $notice->dismiss_option = $dismiss_option;
    $notice->id = $this->notice_id( $message );
    
    if ( $force_show ) {
      $this->clear_dismissed( $notice->id );
    }
    
    $this->admin_notices->{$type}[] = $notice;
  }
  
  /**
   * provides a unique ID for each message
   * 
   * @param string $message the message
   * @return string
   */
  private function notice_id( $message )
  {
    $current_user = wp_get_current_user();
    return $current_user->ID . '-' . hash( 'crc32', $message );
  }
  
  /**
   * cleasrs a dismissed flag
   * 
   * @param string  $id the message id
   */
  private function clear_dismissed( $id )
  {
    delete_option( self::dismiss_option . $id );
  }

  /**
   * prints a PHP error in the admin message
   * 
   * @param int $errno
   * @param string $errstr
   * @param string $errfile
   * @param string $errline
   * @param string $errcontext
   * @return boolean
   */
  public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext )
  {
    if ( !( error_reporting() & $errno ) ) {
      // This error code is not included in error_reporting
      return;
    }

    $message = "errstr: $errstr, errfile: $errfile, errline: $errline, PHP: " . PHP_VERSION . " OS: " . PHP_OS;

    $self = self::get_instance();

    switch ( $errno ) {
      case E_USER_ERROR:
        $self->error( $message );
        break;

      case E_USER_WARNING:
        $self->warning( $message );
        break;

      case E_USER_NOTICE:
      default:
        $self->notice( $message );
        break;
    }

    // write to wp-content/debug.log if logging enabled
    error_log( $message );

    // Don't execute PHP internal error handler
    return true;
  }
  
  /**
   * clears the options on uninstall
   * 
   * @global wpdb $wpdb
   */
  public function uninstall()
  {
    global $wpdb;
    $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE "' . self::dismiss_option . '%";' );
  }

}