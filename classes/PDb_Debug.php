<?php

/**
 * sets up plugin logging
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
class PDb_Debug {

  /**
   * @var string slug name for this class
   */
  const name = 'pdb_debugging';

  /**
   * @var string title of the debugging log
   */
  public $log_title;

  /**
   * @var string name of the settings page
   */
  public $settings_page;

  /**
   * @var resource|bool the log file resource or bool false if no resource
   */
  private $log_file;

  /**
   * @var string name of the logging option
   */
  private $logging_option;

  /**
   * @var string the ajax action
   */
  private $action;

  /**
   * 
   */
  public function __construct()
  {
    $this->log_title = __( 'Debugging Log', 'participants-database' );
    $this->settings_page = Participants_Db::$plugin_page . '-' . self::name . '_settings';

    $this->logging_option = self::name . '-settings';
    $this->action = self::name . '-action';

    add_action( 'admin_menu', array($this, 'add_log_page'), 12 );

    add_action( 'admin_init', array($this, 'initialize_logging') );

    add_action( 'admin_enqueue_scripts', array($this, 'assets'), 15 );

    add_action( 'participants_database_uninstall', array(__CLASS__, 'uninstall') );

    if ( PDB_DEBUG > 1 )
      set_error_handler( array($this, 'write_php_error') );

    add_action( 'wp_ajax_' . $this->action, array($this, 'handle_refresh') );
  }

  /**
   * enqueues the class assets
   * 
   * @param string $hook the current admin page slug
   */
  public function assets( $hook )
  {
    if ( strpos( $hook, 'participants-database-pdb_debugging' ) !== false ) {

      wp_localize_script( Participants_Db::$prefix . 'debug', 'PDb_Debug', array(
          'action' => $this->action,
          'spinner' => Participants_Db::get_loading_spinner(),
              )
      );
      wp_enqueue_script( Participants_Db::$prefix . 'debug' );
    }
  }

  /**
   * writes a message to the log
   * 
   * @param int $errno the php error number
   * @param string $errstr the php error message
   * @param string  $errfile the file where the error occurred
   * @param int $errline line number where the error was triggered
   * @return mixed false to allow default error handling to continue
   */
  public function write_php_error( $errno, $errstr, $errfile, $errline )
  {
    $this->write_log_entry( "\n\n<header>" . $this->timestamp() . '</header> ' . $errstr . '<footer>in ' . $errfile . ' on line ' . $errline . '</footer>' );
    return false;
  }

  /**
   * writes a Participants Database debug message
   * 
   * copies the message to the php debug log
   * 
   * @param string $message the debug message
   */
  public function write_debug( $message )
  {
    $this->write_log_entry( "\n<header>" . $this->timestamp() . '</header> ' . str_replace( array(PHP_EOL, "\t"), array('<br/>', '&emsp;'), htmlspecialchars( $message ) ) );
    error_log( $message );
  }

  /**
   * clears the log
   * 
   * truncates the file and writes the initial header
   */
  public function clear_log()
  {
    fclose( $this->log_file_resource() );
    $this->log_file = fopen( $this->log_filepath(), 'w+b' );
    $this->write_initial_header();
    fclose( $this->log_file );
  }

  /**
   * handles the AJAX submission
   * 
   */
  public function handle_refresh()
  {
    if ( !wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING ), $this->action ) ) {
      wp_die( 'nonce failed' );
    }
    switch ( filter_input( INPUT_POST, 'command', FILTER_SANITIZE_STRING ) ) {
      case 'clear':
        $this->clear_log();
      case 'refresh':
        wp_send_json( $this->get_log() );
    }
    wp_die();
  }

  /**
   * retrieves the contents of the log
   * @return array of lines from the log
   */
  private function get_log()
  {
    $buffer = array();
    if ( $this->log_file_resource() ) {
      rewind( $this->log_file );
      $line_limit = $this->line_limit();
      while ( ($line = fgets( $this->log_file, 4096 )) !== false ) {
        $buffer[] = $line;
        if ( count( $buffer ) >= $line_limit ) {
          $buffer = array(); // clear it if it gets too large
        }
      }
      if ( !feof( $this->log_file ) ) {
        error_log( __METHOD__ . ' file read fail' );
      }
    }
    return '<article>' . implode( '</article><article>', $buffer ) . '</article>';
  }

  /**
   * prints the contents of the log
   */
  private function print_log()
  {
    echo $this->get_log();
  }

  /**
   * renders the plugin settings page
   * 
   * this generic rendering is expected to be overridden in the subclass
   */
  public function render_settings_page()
  {
    ?>
    <div class="wrap pdb-admin-settings participants_db pdb-debugging" >

    <?php Participants_Db::admin_page_heading() ?>  
      <h2><?php echo Participants_Db::$plugin_title . ' ' . $this->log_title ?></h2>
      <div class="pdb-log-display-frame form-group">

        <div class="pdb-log-display">
    <?php $this->print_log() ?>
        </div>

      </div>
      <div class="form-group">
        <form id="pdb-debug-refresh" action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ) ?>">      
      <?php wp_nonce_field( $this->action ) ?>
          <div class="form-group">
            <button class="button-secondary pdb-debugging-clear" data-action="clear" ><?php _e( 'Clear', 'participants-database' ) ?></button>
            <button class="button-primary pdb-debugging-refresh" data-action="refresh" ><?php _e( 'Refresh', 'participants-database' ) ?></button>
          </div>
        </form>

        <p><?php printf( __( 'Log file: %s', 'participants-database' ), '<br/>' . $this->log_filepath() ) ?></p>
            
          </div>
    <?php
  }

  /**
   * provides a timestamp display
   * 
   * @return string
   */
  private function timestamp()
  {
    return '[' . date( 'm/d/y g:ia T' ) . ']';
  }

  /**
   * close the resource
   */
  public function __destruct()
  {
    if ( $this->log_file )
      fclose( $this->log_file );
  }

  /**
   * initializes the logging
   */
  public function initialize_logging()
  {
    if ( !is_resource( $this->log_file ) ) {


      $this->log_file_resource(); // set up the resource

      if ( !is_resource( $this->log_file ) ) {
        $this->clear_log_filename();
        Participants_Db::debug_log( __METHOD__ . ' unable to open file for logging: ' . $this->log_filepath() );
        PDb_Admin_Notices::post_admin_notice( sprintf( __( 'Unable to open the debugging log file: %s Check the "File Upload Location" setting.', 'participants-database' ), $this->log_filepath() ) . '<a href="https://xnau.com/work/wordpress-plugins/participants-database/participants-database-documentation/participants-database-settings-help/#File-and-Image-Uploads-Use-WP-"><span class="dashicons dashicons-editor-help"></span></a>', array(
            'type' => 'error',
            'context' => __( 'Debugging', 'participants-database' ),
        ) );
        return;
      }
    }

    $this->truncate_large_file(); // truncate the file if it's too big

    if ( $this->empty_log() ) {
      $this->write_initial_header();
    }
  }

  /**
   * provides the log file resource
   * 
   * @return resource
   */
  private function log_file_resource()
  {
    if ( !is_resource( $this->log_file ) ) {
      $this->log_file = fopen( $this->log_filepath(), 'a+b' );
    }
    return $this->log_file;
  }

  /**
   * sets the debug log path
   */
  private function log_filepath()
  {
    // attempt to create the target directory if it does not exist
    if ( !is_dir( Participants_Db::files_path() ) ) {
      Participants_Db::_make_uploads_dir();
    }
    return Participants_Db::apply_filters( 'debug_log_filepath', Participants_Db::files_path() . $this->log_filename() );
  }

  /**
   * provides the debug log filename
   * 
   * @return string filename
   */
  private function log_filename()
  {
    $filename = $this->option( 'log_filename' );
    if ( !$filename ) {
      $filename = self::name . '_' . uniqid() . '.txt';
      $this->set_option( 'log_filename', $filename );
    }
    return $filename;
  }

  /**
   * clears the log filename
   * 
   */
  public function clear_log_filename()
  {
    $this->set_option( 'log_filename', false );
  }

  /**
   * writes an entry to the log
   * 
   * @param string $entry
   */
  private function write_log_entry( $entry )
  {
    fwrite( $this->log_file_resource(), $entry );
  }

  /**
   * writes the initial header to the file
   * 
   */
  private function write_initial_header()
  {
    $this->write_log_entry( $this->log_header() );
  }

  /**
   * checks for an empty file
   * 
   * @return bool true if the file is empty
   */
  private function empty_log()
  {
    $first = fread( $this->log_file_resource(), 4096 );
    return empty( $first );
  }

  /**
   * provides the limit to the number of lines from the log to buffer
   * 
   * @return int
   */
  private function line_limit()
  {
    return Participants_Db::apply_filters( 'debug_log_line_buffer_limit', 200 );
  }

  /**
   * truncates the log file if it is too large
   */
  private function truncate_large_file()
  {
    if ( is_resource( $this->log_file ) ) {
      $stat = fstat( $this->log_file );

      $too_big = $stat['size'] > Participants_Db::apply_filters( 'debug_log_max_size_mb', 5 ) * MB_IN_BYTES; // 5MB

      if ( $too_big ) {
        $this->clear_log();
      }
    }
  }

  /**
   * provides a debugging option value
   * 
   * @param string  $name of the option to get
   * @return string|bool string value or bool false if not set
   */
  private function option( $name )
  {
    $options = $this->get_option_array();
    return isset( $options[$name] ) ? $options[$name] : false;
  }

  /**
   * sets an option value
   * 
   * @param string $name of the option
   * @param mixed $value to store
   * 
   */
  private function set_option( $name, $value )
  {
    $options = $this->get_option_array();
    $options[$name] = $value;
    update_option( $this->logging_option, $options );
  }

  /**
   * provides the options array
   * 
   * uses the main PDB options array
   * 
   * @return array
   */
  private function get_option_array()
  {
    return get_option( $this->logging_option );
  }

  /**
   * sets up the plugin settings page
   */
  public function add_log_page()
  {
    add_submenu_page(
            Participants_Db::$plugin_page, $this->log_title, $this->log_title, Participants_Db::plugin_capability( 'plugin_admin_capability', self::name ), Participants_Db::$plugin_page . '-' . self::name, array($this, 'render_settings_page')
    );
  }

  /**
   * provides a log header string
   * 
   * @return string
   */
  private function log_header()
  {
    return '<header class="loghead">' . sprintf( __( 'Log file initiated at: %s', 'participants-database' ), date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' T' ) ) . '</header>';
  }

  /**
   * clears the log files
   * 
   */
  private static function delete_all_logs()
  {
    foreach ( scandir( Participants_Db::files_path() ) as $filename ) {
      if ( strpos( $filename, self::name . '_' ) === 0 ) {
        unlink( Participants_Db::files_path() . $filename );
      }
    }
  }

  /**
   * uninstalls the plugin
   */
  public static function uninstall()
  {
    delete_option( self::name . '-settings' );
    self::delete_all_logs();
  }

}
