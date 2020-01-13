<?php
/**
 * handles feedback messages in the admin
 * 
 * see the API calls for usage
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2017  xnau webdesign
 * @license    GPL3
 * @version    1.3.2
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
  private $admin_notice_list;

  /**
   * @var string list of message types
   */
  const TYPES = 'error,warning,info,success';

  /**
   * @var string  the option name
   */
  const pdb_admin_notice = 'pdb_admin_notices';

  /**
   * @var string  GET var key
   */
  const get_key = 'pdb_admin_notices_dismiss';

  /**
   * this is the single general API call
   * 
   * @param string  $message the message to post
   * @param array $params
   *              'type' => string 'error', 'warning', 'info', 'success' defaults to 'info'
   *              'context' => string heading message
   *              'persistent' => bool true to show dismissable persistent message, defaults to true
   *              'global' => bool true to show the message on all admin pages, defaults to false
   * @return string id for the message
   */
  public static function post_admin_notice( $message, $params = array() )
  {
    extract( shortcode_atts( array(
        'type' => 'info',
        'context' => '',
        'persistent' => true,
        'global' => false,
        'force' => false,
                    ), $params ) );
    $notice = self::get_instance();
    return $notice->notice( $type, $message, $context, $persistent, $global );
  }

  /**
   * these are our specific API calls
   * 
   * the static calls are the most likely to be used, dynamic calls are for situations 
   * where the object needs to be set up and modified before posting the message.
   * 
   * @param string  $message can inclue HTML, remember it is wrapped in a <p> tag.
   * @param string  $context a context message (goes in the message heading)
   * @param bool    $persistent if true, the message persists across page loads until dismissed
   * @param bool    $force if true, show the message even if previously dismissed
   * 
   * @return string unique id for the notice
   */
  public static function post_error( $message, $context = '', $persistent = false, $force = false )
  {
    $notice = self::get_instance();
    return $notice->error( $message, $context, $persistent, $force );
  }

  public function error( $message, $context = '', $persistent = false, $force = false )
  {
    return $this->notice( 'error', $message, $context, $persistent, false, $force );
  }

  public static function post_warning( $message, $context = '', $persistent = false, $force = false )
  {
    $notice = self::get_instance();
    return $notice->warning( $message, $context, $persistent, $force );
  }

  public function warning( $message, $context = '', $persistent = false, $force = false )
  {
    return $this->notice( 'warning', $message, $context, $persistent, false, $force );
  }

  public static function post_success( $message, $context = '', $persistent = false )
  {
    $notice = self::get_instance();
    return $notice->success( $message, $context, $persistent );
  }

  public function success( $message, $context = '', $persistent = false )
  {
    return $this->notice( 'success', $message, $context, $persistent );
  }

  public static function post_info( $message, $context = '', $persistent = false )
  {
    $notice = self::get_instance();
    return $notice->info( $message, $context, $persistent );
  }

  public function info( $message, $context = '', $persistent = false )
  {
    return $this->notice( 'info', $message, $context, $persistent );
  }

  /**
   * deletes the named notice
   * 
   * @param string  $id the notice id
   * @param bool $dismiss if false, delete the notice, if true, dismiss it
   */
  public static function delete_notice( $id, $dismiss = true )
  {
    $notice = self::get_instance();
    $notice->dismiss( $id, $dismiss );
  }

  /**
   * 
   */
  private function __construct()
  {
    $this->admin_notice_list = $this->admin_notice_list();
    
//    error_log(__METHOD__.' admin notices: '.print_r($this->admin_notice_list,1));
    
    add_action( 'admin_notices', array($this, 'action_admin_notices') );
    add_action( 'admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts') );
    
    add_action( 'wp_ajax_' . self::get_key, array( $this, 'dismiss_notice' ) );
    
    add_action( 'participants_database_uninstall', array($this, 'uninstall') );
    
    /*
     * this looks for a 'clear_pdb_notices' variable in the URL and clears all admin 
     * messages if found
     */
    $this->check_for_message_purge();
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
   * enqueue the script
   */
  public function action_admin_enqueue_scripts()
  {
    wp_localize_script(Participants_Db::$prefix . 'admin-notices', 'PDb_Notices', array(
        'action' => self::get_key,
        'nonce' => wp_create_nonce( self::pdb_admin_notice ),
    ));
    wp_enqueue_script( Participants_Db::$prefix . 'admin-notices' );
  }

  /**
   * fired on admin_notices action
   */
  public function action_admin_notices()
  {
//    error_log(__METHOD__.' notices: '.print_r($this->admin_notice_list(),1));
    foreach ( $this->admin_notice_list() as $admin_notice ) {
      
      /* @var $admin_notice pdb_admin_notice_message */

      if ( $this->notice_is_shown( $admin_notice ) ) {
        ?><div
          class="notice pdb_admin_notices-notice notice-<?php
          echo $admin_notice->type;

          echo ' is-dismissible" data-dismiss="' . $admin_notice->id;
          ?>">
          <h4><?php echo Participants_Db::$plugin_title . $admin_notice->context ?>:</h4>
          <?php if ( $admin_notice->html_message() ) : 
            echo $admin_notice->message;
          else : ?>
          <p><span class="dashicons <?php echo $this->dashicon( $admin_notice->type ) ?>"></span>&nbsp;<?php echo $admin_notice->message ?></p>
          <?php endif ?>

        </div><?php
      }
    }

    $this->purge_transient_notices();
  }

  /**
   * supplies the current admin notice list
   * 
   * @return array
   */
  private function admin_notice_list()
  {
    return get_option( self::pdb_admin_notice, array() );
  }
  
  /**
   * handles the dismiss action
   * 
   */
  public function dismiss_notice()
  {
    if ( !wp_verify_nonce( filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_STRING ), self::pdb_admin_notice ) ) {
      wp_die();
    }
    $this->dismiss( filter_input( INPUT_GET, 'msgid', FILTER_SANITIZE_STRING ) );
    
    wp_die();
  }

  /**
   * marks a notice as dismissed
   * 
   * @param string  $notice_id
   * @param bool $dismiss if false, the message is deleted completely
   */
  public function dismiss( $notice_id, $dismiss = true )
  {
    if ( isset( $this->admin_notice_list[$notice_id] ) ) {
      if ( $dismiss ) {
        $this->admin_notice_list[$notice_id]->dismiss();
      } else {
        unset($this->admin_notice_list[$notice_id]);
      }
      $this->update_notices();
    }
  }

  /**
   * updates the notices option
   */
  private function update_notices()
  {
    update_option( self::pdb_admin_notice, $this->admin_notice_list );
  }
  
  /**
   * tells if the notice should be shown
   * 
   * @param pdb_admin_notice_message $notice
   * @return bool
   */
  private function notice_is_shown( pdb_admin_notice_message $notice )
  {
    return $notice->show_to_current_user() && $notice->is_dimissed() === false && ( $notice->is_global_message() || $this->is_plugin_screen() );
  }
  
  /**
   * tells if the notice needs to be added to the list
   * 
   * if the notice is in the list already, don't add it
   * 
   * @param pdb_admin_notice_message $notice
   * @return bool
   */
  private function notice_should_be_added( pdb_admin_notice_message $notice )
  {
    return array_key_exists($notice->id, $this->admin_notice_list) === false || $notice->is_dimissed();
  }

  /**
   * purges all non-persistent notices
   */
  private function purge_transient_notices()
  {
    $this->admin_notice_list = array_filter( $this->admin_notice_list, function ($notice) {
      return $notice->is_persistent();
    } );
    
    $this->update_notices();
  }

  /**
   * purges all notices
   */
  private function purge_all_notices()
  {
    $this->admin_notice_list = array();
    
    $this->update_notices();
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
   * checks for a purge all messages signal
   * 
   */
  public function check_for_message_purge()
  {
    if ( array_key_exists( 'clear_pdb_notices', $_GET ) || ( array_key_exists( Participants_Db::$participants_db_options,$_POST ) && filter_var( $_POST[Participants_Db::$participants_db_options]['clear_pdb_notices'], FILTER_SANITIZE_STRING ) == '1' )  ) {
      $this->purge_all_notices();
    }
  }

  /**
   * provides the dashicons icon text
   * 
   * @param string $type message type
   * @return string
   */
  private function dashicon( $type )
  {
    switch ( $type ) {
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
   * @param string  $context a context string fro the message header
   * @param bool    $persistent if true, message will persist across page loads
   * @param bool    $global if false, notice is only shown on plugin admin pages, true shown on all admin pages
   * @param bool    $force if true, show even if previously dismissed
   * 
   * @return string notice ID
   */
  private function notice( $type, $message, $context, $persistent, $global = false, $force = false )
  {
    $notice = new pdb_admin_notice_message( $type, $message, $context, $persistent, $global );
    
    if ( $force ) {
      $this->dismiss( $notice->id, false );
    }

    if ( $this->notice_should_be_added( $notice ) ) {
      $this->admin_notice_list[$notice->id] = $notice;
      $this->update_notices();
    }
    return $notice->id;
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
    $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE "' . self::pdb_admin_notice . '%";' );
  }

}

/**
 * models a single admin notice
 */
class pdb_admin_notice_message {

  /**
   * @var string the type of message
   */
  private $type;

  /**
   * @var string the messgae text
   */
  private $message;

  /**
   * @var string the context string
   */
  private $context;

  /**
   * @var bool whether the message should be persistent across page loads
   */
  private $persistent;

  /**
   * @var bool whether the notice should be seen globally in the admin or on plugin plages only
   */
  private $global;
  
  /**
   * @var bool whether the notice has been dismissed
   */
  private $dismissed = false;

  /**
   * @var string holds the unique id for the message
   */
  private $id;

  /**
   * @var string provides a joining string for using the context string in the message heading
   */
  private $context_joiner = '/';

  /**
   * instantiates the message
   * 
   * 
   * @param string  $type
   * @param string  $message
   * @param string  $context a context string fro the message header
   * @param bool    $persistent if true, message will persist across page loads
   * @param bool    $global if true, the message will be seen on all admin pages
   */
  public function __construct( $type, $message, $context, $persistent, $global = false )
  {
    $this->type = $type;
    $this->message = $message;
    $this->context = $context;
    $this->persistent = (bool) $persistent;
    $this->notice_id( $message );
    $this->global = $global;
  }

  /**
   * provides object property values
   * 
   * @param string $name property name
   * @return mixed
   */
  public function __get( $name )
  {
    switch ( $name ) {
      case 'id':
        return $this->id;
      case 'message':
        return wp_kses_post( $this->message );
      case 'context':
        return esc_html( $this->context_string() );
      case 'persistent':
      case 'global':
      case 'dismissed':
        return (bool) $this->{$name};
      case 'type':
        return $this->type;
    }
  }
  
  /**
   * tells if the current message should be shown to the current user
   * 
   * @return bool true if it should be shown
   */
  public function show_to_current_user()
  {
    $current_user = wp_get_current_user();
    return $this->message_user_id() == $current_user->ID;
  }
  
  /**
   * tells the user id associated with the message
   * 
   * @return int user ID
   */
  public function message_user_id()
  {
    list( $id, $hash ) = explode( '-', $this->id );
    return $id;
  }

  /**
   * provides a context string
   * 
   * @return string
   */
  private function context_string()
  {
    return empty( $this->context ) ? '' : $this->context_joiner . $this->context;
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
    $this->id = $current_user->ID . '-' . hash( 'crc32', $message );
  }
  
  /**
   * tells if the message is wrapped in a tag
   * 
   * @return bool
   */
  public function html_message()
  {
    return preg_match( '/^</', $this->message ) === 1;
  }
  
  /**
   * sets the dismissed flag
   * 
   */
  public function dismiss()
  {
    $this->dismissed = true;
  }
  
  /**
   * clears the dismissed flag
   * 
   */
  public function undismiss()
  {
    $this->dismissed = false;
  }
  
  /**
   * tells if the notice has been dismissed
   * 
   * @return bool
   */
  public function is_dimissed()
  {
    return (bool) $this->dismissed;
  }
  
  /**
   * tells if the notice should be shown on all admin pages
   * 
   * @return bool
   */
  public function is_global_message()
  {
    return $this->global;
  }
  
  /**
   * tells if the notice should persist across page loads
   * 
   * @return bool
   */
  public function is_persistent()
  {
    return $this->persistent;
  }

}
