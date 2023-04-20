<?php
/**
 * parent class for auxiliary plugins to the Participants Database Plugin
 *
 * the main function here is to establish a connection to the parent plugin and
 * provide some common functionality
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    5.4
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( !defined( 'ABSPATH' ) )
  die;

if ( !class_exists( 'PDb_Aux_Plugin' ) ) :

  class PDb_Aux_Plugin {

    /**
     * boolean true if the Participants Database plugin is found and active
     * @var bool
     */
    public $connected = true;

    /**
     * the directory and path of the main plugin file
     * @var string
     */
    public $plugin_path;

    /**
     * holds the path to the parent plugin
     * @var string
     */
    public $parent_path;

    /**
     * name of the instantiating subclass
     * @var string
     */
    public $subclass;

    /**
     * slug of the aux plugin
     * @var string
     */
    public $aux_plugin_name;

    /**
     * title of the aux plugin
     * @var string
     */
    public $aux_plugin_title;

    /**
     * @var string abbreviated plugin name, should be unique in most contexts
     */
    public $aux_plugin_shortname;

    /**
     * 
     * @var string slug of the aux plugin settings page
     */
    public $settings_page;

    /**
     * name of the WP option used for the plugin settings
     * @var string
     */
    public $aux_plugin_settings;

    /**
     * 
     * @var array holds the plugin's options
     */
    public $plugin_options;

    /**
     * @var array of settings section definitions
     */
    public $settings_sections;

    /**
     * @var array of settings objects
     */
    protected $setting_definitions;

    /**
     * 
     * @var array holds the plugin info fields as parsed from the main plugin file header
     */
    public $plugin_data = array();

    /**
     * the updater class instance for this plugin
     * @var object
     */
    public $Updater;

    /**
     * status of the settings API
     * 
     * @var bool true if the settings API is in use
     */
    public $settings_API_status = true;

    /**
     * @var string holds the plugin version number and author attribution
     */
    public $attribution;

    /**
     * @var array of registered aux_plugin events as $tag => $title
     */
    public $aux_plugin_events = array();

    /**
     * @var string basename of the request throttling transient
     */
    const throttler = 'pdbaux-request_throttler_';

    /**
     * 
     * this is typically instantiated in the aux plugin with: 
     * parent::__construct( __CLASS__, $path_to_initial_script );
     * 
     * @param string $subclass name of the instantiating subclass
     * @param string $plugin_file absolute path
     */
    public function __construct( $subclass, $plugin_file )
    {
      if ( is_null( Participants_Db::$plugin_version ) ) {
        // main plugin has not been initialized
        return;
      }
      // provides the fallback values for these plugin data fields
      $this->plugin_data += array(
          'PluginURI' => 'https://xnau.com/shop/',
          'SupportURI' => 'https://xnau.com/product_support/',
      );
      $this->plugin_path = $plugin_file;
      $this->parent_path = plugin_dir_path( $plugin_file );

      $this->subclass = $subclass;

      $this->aux_plugin_settings = $this->aux_plugin_shortname . '_settings';
      $this->settings_page = Participants_Db::$plugin_page . '-' . $this->aux_plugin_name . '_settings';

      add_action( 'admin_menu', array($this, 'add_settings_page') );
      add_action( 'admin_init', array($this, 'settings_api_init') );
      add_action( 'admin_enqueue_scripts', array($this, 'enqueues'), 1 );
      add_action( 'plugins_loaded', array($this, 'set_plugin_options'), 1 );
      add_action( 'init', array($this, 'load_textdomain'), 1 );
      add_filter( 'plugin_row_meta', array($this, 'add_plugin_meta_links'), 10, 2 );
      add_action( 'plugins_loaded', array($this, 'register_global_events'), -10 );

      add_action( 'init', function() use ($plugin_file) {
        $this->setup_updates( $plugin_file );
      }, 50);
    }
    
    /**
     * sets up the xnau plugin updates
     * 
     * @param string $plugin_file absolute path
     */
    protected function setup_updates( $plugin_file )
    {
      if ( apply_filters( 'pdbaux-enable_auto_updates', true ) )
      {
        // if we are here, the aux plugin hasn't been updated
        Participants_Db::debug_log( __METHOD__ . ': update services not provided to the "' . Participants_Db::$plugin_title . ' ' .$this->aux_plugin_title . '" plugin', 2 );
        
        if ( $this->check_aux_plugin_updater() && method_exists( '\xnau_plugin_updates', 'setup' ) )
        {
          \xnau_plugin_updates::setup( $plugin_file, $this->aux_plugin_name );
        }
        
        self::missing_updater_plugin_notice($this->aux_plugin_title);
      }
    }

    /**
     * enqueues any resources needed in the admin
     */
    public function enqueues()
    {
      
    }

    /**
     * checks for a valid connection to the parent plugin
     * 
     * @return bool
     */
    public function check_connection()
    {
      // find the path to the parent plugin
      $active_plugins = get_option( 'active_plugins' );
      foreach ( $active_plugins as $plugin_file ) {
        if ( false !== stripos( $plugin_file, 'participants-database.php' ) ) {
          return true;
        }
      }
      return false;
    }

    /**
     * provides the setting definition
     * 
     * @param string  $name of the setting to get
     * @return object|bool false if setting is not found
     */
    public function setting_definition( $name )
    {
      return isset( $this->setting_definitions[$name] ) ? $this->setting_definitions[$name] : false;
    }
    
    /**
     * provides the setting default value
     * 
     * @param string $name name of the setting
     * @return string
     */
    protected function setting_default( $name )
    {
      $default = '';
      if ( $setting = $this->setting_definition( $name ) ) {
        $default = $setting->default;
      }
      
      return $default;
    }

    /**
     * provides a plugin option value or default if no option set
     * 
     * uses a filter of the form {$aux_plugin_shortmame}-{$option_name}
     * 
     * @version 1.7.0.13 passed through multilingual filter
     * 
     * @param string $option_name
     * @param mixed $default
     * 
     * @return mixed
     */
    public function plugin_option( $option_name, $default = false )
    {
      $default_setting = $default === false ? $this->setting_default($option_name) : $default;
      
      $option_value = apply_filters( $this->aux_plugin_shortname . '-' . $option_name, isset( $this->plugin_options[$option_name] ) ? $this->plugin_options[$option_name] : $default_setting );
      
      if ( ! isset( $this->plugin_options[$option_name] ) ) {
//        Participants_Db::debug_log(__METHOD__.' using fallback value for option "' . $this->aux_plugin_name . ': ' . $option_name . '"', 2);
//        Participants_Db::debug_log(__METHOD__.' TRACE: ' . print_r( wp_debug_backtrace_summary(),1), 3);
      }
      
      return is_string( $option_value ) ? Participants_Db::apply_filters( 'translate_string', $option_value ) : $option_value;
    }

    /**
     * loads the plugin text domain
     * 
     * defaults to the main plugin translation file
     * 
     */
    public function load_textdomain()
    {
      Participants_Db::load_plugin_textdomain( $this->plugin_path, $this->aux_plugin_name );
    }

    /**
     * sets the plugin options array
     */
    public function set_plugin_options()
    {
      $this->plugin_data = ( function_exists( 'get_plugin_data' ) ? get_plugin_data( $this->plugin_path ) : array('Author' => 'Roland Barker, xnau webdesign') ) + $this->plugin_data;
      $this->set_attribution();
      $this->register_option_for_translations();
      /*
       * this sets up the setting verfication callback mechanism
       * 
       * to set up a setting verification callback, create your function with a name 
       * like this: setting_callback_for_{setting name} It passes in the new value 
       * and the previous value. The callback must return the value to save.
       */
      if ( !has_filter( 'pre_update_option_' . $this->aux_plugin_settings, array($this, 'settings_callbacks') ) ) {
        add_filter( 'pre_update_option_' . $this->aux_plugin_settings, array($this, 'settings_callbacks'), 10, 2 );
      }
      
      $options = get_option( $this->settings_name() );
      $this->plugin_options = is_array( $options ) ? $options : array();
    }

    /**
     * gathers all the registered events
     * 
     */
    public function register_global_events()
    {
      /**
       * 
       * @filter 'pdb-register_global_event'
       * @param array $events as $tag => $title
       */
      $this->aux_plugin_events = Participants_Db::apply_filters( 'register_global_event', array() );
    }

    /**
     * provides the name of the plugin options
     * 
     * this is to allow a filter to modify the name
     * 
     * @param string $settings_name the base setting name for the plugin
     * @return string
     */
    public function settings_name( $settings_name = '' )
    {
      $settings_name = empty( $settings_name ) ? $this->aux_plugin_settings : $settings_name;
      if ( is_admin() ) {
        return Participants_Db::apply_filters( 'aux_plugin_admin_settings_name', $settings_name );
      }
      return Participants_Db::apply_filters( 'aux_plugin_settings_name', $settings_name );
    }

    /**
     * registers the aux plugin's options array with the multilingual plugin if present
     * 
     * @return bool true if the multilingual plugin is present and the option registered
     */
    public function register_option_for_translations()
    {
      global $PDb_Multilingual;
      if ( is_object( $PDb_Multilingual ) ) {
        $PDb_Multilingual->register_translation_filter( $this->aux_plugin_name . '-option_name', $this->aux_plugin_settings );
        return true;
      }
      return false;
    }

    /**
     * adds links and modifications to plugin list meta row
     * 
     * @param array  $links
     * @param string $file
     * @return array
     */
    public function add_plugin_meta_links( $links, $file )
    {

      $plugin = plugin_basename( $this->plugin_path );

      // create link
      if ( strstr( $file, '/', true ) == strstr( $plugin, '/', true ) ) {

        $links[1] = str_replace( $this->plugin_data['Author'], 'xn*au webdesign', $links[1] );
//        if ( !empty( $this->plugin_data['PluginURI'] ) ) {
//          $links[] = '<a href="' . $this->plugin_data['PluginURI'] . '">' . __( 'Submit a rating or review', 'participants-database' ) . ' </a>';
//        }
        $links[] = '<a href="' . $this->plugin_data['SupportURI'] . '">' . __( 'Support', 'participants-database' ) . ' </a>';
      }
      return $links;
    }

    /**
     * sets up the attribution string
     */
    protected function set_attribution()
    {
      $data = $this->plugin_data;
      $name_pattern = _x( '%s version %s', 'displays the name and version of the plugin', 'participants-database' );
      $author_pattern = _x( 'Authored by %s', 'displays the plugin author name', 'participants-database' );
      $this->attribution = sprintf( $name_pattern . '<br />' . $author_pattern, $data['Name'], $data['Version'], $data['Author'] );
    }

    /**
     * plugin options section
     */

    /**
     * initializes the settings API
     * 
     * this is expected to be overridden by the child class
     */
    public function settings_api_init()
    {
      register_setting( $this->aux_plugin_name . '_settings', $this->settings_name() );

      $this->settings_sections = array(
          array(
              'title' => __( 'General Settings', 'participants-database' ),
              'slug' => $this->aux_plugin_shortname . '_setting_section',
          )
      );
      $this->_add_settings_sections();
    }

    /**
     * sets up the plugin settings page
     */
    public function add_settings_page()
    {
      if ( $this->settings_API_status ) {
        // create the submenu page
        add_submenu_page(
                Participants_Db::$plugin_page, $this->aux_plugin_title . ' Settings', $this->aux_plugin_title, Participants_Db::plugin_capability( 'plugin_admin_capability', $this->aux_plugin_name ), $this->settings_page, array($this, 'render_settings_parent_page')
        );
      }
    }

    /**
     * renders the aux plugin settings parent page
     * 
     * adds an independent header to the settings page
     */
    public function render_settings_parent_page()
    {
      $header = $this->settings_page_header();
      if ( ! empty( $header) )
      {   
        echo wp_kses_post($header);
      }
      /**
       * @filter 'pdb-aux_plugin_settings_page_header'
       */
      do_action( Participants_Db::$prefix . 'aux_plugin_settings_page_header' );
      $this->render_settings_page();
    }

    /**
     * prints a header for an aux plugin settings page
     * 
     * @return string HTML
     */
    protected function settings_page_header()
    {
      return;
    }

    /**
     * renders a tabs control for a tabbed interface
     */
    protected function print_settings_tab_control()
    {
      if ( count( $this->settings_sections ) > 1 ) :
        ?>
        <ul class="ui-tabs-nav">
          <?php
          foreach ( $this->settings_sections as $section )
            printf( '<li><a href="#%s">%s</a></li>', Participants_Db::make_anchor( $section['slug'] ), $section['title'] );
          ?>
        </ul>
        <?php
      endif;
    }

    /**
     * registers the settings sections
     * 
     * @version 1.6.3 added $settings_sections property; making the parameter optional
     * 
     * @param array $sections
     */
    public function _add_settings_sections( $sections = false )
    {
      global $pagenow;
      if ( $pagenow !== 'admin.php' )
      {
        return;
      }
      
      if ( $sections ) {
        $this->settings_sections = $sections;
      }
      
      // enqueue the settings tabs script if there is more than one section
      if ( count( $this->settings_sections ) > 1 )
      {
        add_action( 'admin_enqueue_scripts', function() {
          wp_enqueue_script( Participants_Db::$prefix . 'aux_plugin_settings_tabs' );
        }, 50 );
      }
      
      foreach ( $this->settings_sections as $section )
      {
        $args = array(
            'before_section' => isset( $section['before_section'] ) ? $section['before_section'] : '',
            'after_section' => isset( $section['after_section'] ) ? $section['after_section'] : '',
            'section_class' => isset( $section['class'] ) ? $section['class'] : $section['slug'] . '-settings-section',
        );
        // Add the section to reading settings so we can add our
        // fields to it
        add_settings_section(
                $section['slug'], $section['title'], array($this, 'setting_section_callback_function'), $this->aux_plugin_name, $args
        );
      }
    }

    /**
     * adds a setting to the Settings API
     * 
     * @param array $atts an array of settings parameters
     * @return null
     * 
     */
    protected function add_setting( $atts )
    {

      $default = array(
          'type' => 'text',
          'name' => '',
          'title' => '',
          'default' => '',
          'help' => '',
          'options' => '',
          'style' => '',
          'class' => '',
          'attributes' => array(),
          'section' => $this->aux_plugin_shortname . '_setting_section'
      );
      $params = shortcode_atts( $default, $atts );

      // add the setting definition to the list of definition objects
      $this->setting_definitions[$params['name']] = (object) $params;

      add_settings_field(
              $params['name'], $params['title'], array($this, 'setting_callback_function'), $this->aux_plugin_name, $params['section'], array(
          'type' => $params['type'],
          'name' => $params['name'],
          'value' => isset( $this->plugin_options[$params['name']] ) ? $this->plugin_options[$params['name']] : $params['default'],
          'title' => $params['title'],
          'help' => $params['help'],
          'options' => $params['options'],
          'style' => $params['style'],
          'class' => $params['class'],
          'attributes' => $params['attributes'],
              )
      );
    }

    /**
     * renders the plugin settings page
     * 
     * this generic rendering is expected to be overridden in the subclass
     */
    public function render_settings_page()
    {
      ?>
      <div class="wrap pdb-admin-settings participants_db" >

        <?php Participants_Db::admin_page_heading( Participants_Db::$plugin_title . ' ' . $this->aux_plugin_title ) ?>

        <?php settings_errors(); ?>  

        <form method="post" action="options.php">  
          <?php
          settings_fields( $this->aux_plugin_name . '_settings' );
          do_settings_sections( $this->aux_plugin_name );
          submit_button();
          ?>  
        </form>  

        <aside class="attribution"><?php echo wp_kses_post( $this->attribution ) ?></aside>

      </div><!-- /.wrap -->  
      <?php
    }

    /**
     * renders a section heading
     * 
     * this is expected to be overridden in the subclass
     * 
     * @param array $section information about the section
     */
    public function setting_section_callback_function( $section )
    {
      printf( '<a name="%s"></a>', $section['id'] );
    }

    /**
     * shows a setting input field
     * 
     * @param array $atts associative array of attributes (* required)
     *                      name    - name of the setting*
     *                      type    - the element type to use for the setting, defaults to 'text'
     *                      value   - preset value of the setting
     *                      title   - title of the setting
     *                      class   - classname for the settting
     *                      style   - CSS style for the setting element
     *                      help    - help text
     *                      options - an array of options for multiple-option input types (name => title)
     *                      attributes - array of HTML attributes
     */
    public function setting_callback_function( $atts )
    {
      $options = get_option( $this->settings_name() );
      $defaults = array(
          'name' => '', // 0
          'type' => 'text', // 1
          'value' => isset( $options[$atts['name']] ) ? $options[$atts['name']] : '', // 2
          'title' => '', // 3
          'class' => '', // 4
          'style' => '', // 5
          'help' => '', // 6
          'options' => '', // 7
          'select' => '', // 8
          'attributes' => array(), // 9
      );
      $setting = shortcode_atts( $defaults, $atts );
      $setting['value'] = isset( $options[$atts['name']] ) ? $options[$atts['name']] : $atts['value'];
      // create an array of numeric keys
      for ( $i = 0; $i < count( $defaults ); $i++ )
        $keys[] = $i;
      // replace the string keys with numeric keys in the order defined in $defaults
      $values = array_combine( $keys, $setting );

      $values[3] = htmlspecialchars( $values[3] );
      $values[8] = $this->set_selectstring( $setting['type'] );
      $values[9] = $this->input_attributes( $setting['attributes'] );

      $build_function = '_build_' . $setting['type'];
      if ( !is_callable( array($this, $build_function) ) ) {
        $build_function = '_build_text';
      }
      $control_html = call_user_func( array($this, $build_function), $values );
      echo wp_kses( $control_html, Participants_Db::allowed_html('form') );
    }

    /**
     * builds a text setting element
     * 
     * @param array $values array of setting values
     *                       0 - setting name
     *                       1 - element type
     *                       2 - setting value
     *                       3 - title
     *                       4 - CSS class
     *                       5 - CSS style
     *                       6 - help text
     *                       7 - setting options array
     *                       8 - select string
     *                       9 - attributes array
     * @return string HTML
     */
    protected function _build_text( $values )
    {
      $pattern = "\n" . '<input name="' . $this->settings_name() . '[%1$s]" type="%2$s" value="%3$s" title="%4$s" class="%5$s" style="%6$s" %10$s  />';
      if ( !empty( $values[6] ) )
        $pattern .= "\n" . '<p class="description">%7$s</p>';
      return vsprintf( $pattern, $values );
    }

    /**
     * builds a text area setting element
     * 
     * @param array $values array of setting values
     * @return string HTML
     */
    protected function _build_textarea( $values )
    {
      $pattern = '<textarea name="' . $this->settings_name() . '[%1$s]" title="%4$s" class="%5$s" style="%6$s" %10$s  />%3$s</textarea>';
      if ( !empty( $values[6] ) )
        $pattern .= '<p class="description">%7$s</p>';
      return vsprintf( $pattern, $values );
    }

    /**
     * builds a wordpress editor element
     * 
     * @param array $values array of setting values
     * @return string HTML
     */
    protected function _build_richtext( $values )
    {
      $params = array(
          'media_buttons' => FALSE,
          'textarea_name' => $this->settings_name() . '[' . $values[0] . ']',
      );
      ob_start();
      wp_editor( $values[2], 'richtext-' . str_replace( '-', '_', $values[0] ), $params );
      $html = vsprintf( '<div class="%5$s" style="%6$s" %10$s >', $values );
      $html .= ob_get_clean();
      if ( !empty( $values[6] ) ) {
        $html .= '<p class="description">' . $values[6] . '</p>';
      }
      $html .= '</div>';
      return $html;
    }

    /**
     * builds a checkbox setting element
     * 
     * @param array $values array of setting values
     * @return string HTML
     */
    protected function _build_checkbox( $values )
    {
      $selectstring = $this->set_selectstring( $values[1] );
      $values[8] = $values[2] == 1 ? $selectstring : '';
      $pattern = '
<input name="' . $this->settings_name() . '[%1$s]" type="hidden" value="0" />
<input name="' . $this->settings_name() . '[%1$s]" type="%2$s" value="1" title="%4$s" class="%5$s" style="%6$s" %9$s %10$s />
';
      if ( !empty( $values[6] ) )
        $pattern .= '<p class="description">%7$s</p>';
      return vsprintf( $pattern, $values );
    }

    /**
     * builds a radio button setting element
     * 
     * @param array $values array of setting values
     * @return string HTML
     */
    protected function _build_radio( $values )
    {
      $selectstring = $this->set_selectstring( $values[1] );
      $set_value = $values[2];
      $html = '';
      $pattern = '<label style="%6$s"  title="%4$s"><input type="%2$s" %9$s %10$s value="%3$s" name="' . $this->settings_name() . '[%1$s]"> <span>%4$s</span></label>';
      $html .= '<div class="' . $values[1] . ' ' . $values[4] . '" >';
      foreach ( $values[7] as $name => $title ) {
        if ( is_int( $name ) ) {
          $name = $title;
        }
        $values[8] = $set_value == $name ? $selectstring : '';
        $values[2] = $name;
        $values[3] = $title;
        $html .= vsprintf( $pattern, $values );
      }
      $html .= "\n" . '</div>';
      if ( !empty( $values[6] ) )
        $html .= "\n" . '<p class="description">' . $values[6] . '</p>';
      return $html;
    }

    /**
     * builds a multi-checkbox setting element
     * 
     * @param array $values array of setting values
     * @return string HTML
     */
    protected function _build_multicheckbox( $values )
    {
      $selectstring = $this->set_selectstring( $values[1] );
      $html = '';
      $pattern = "\n" . '<label style="%6$s" title="%4$s"><input type="checkbox" %9$s %10$s value="%11$s" name="' . $this->settings_name() . '[%1$s][]"> <span>%4$s</span></label>';
      $html .= "\n" . '<div class="checkbox-group ' . $values[1] . ' ' . $values[4] . '" >';
      foreach ( $values[7] as $value => $title ) {
        $values[8] = in_array( $value, $values[2] ) ? $selectstring : '';
        $values[3] = $title;
        $values[10] = is_int( $value ) ? $title : $value;
        $html .= vsprintf( $pattern, $values );
      }
      $html .= "\n" . '</div>';
      if ( !empty( $values[6] ) )
        $html .= "\n" . '<p class="description">' . $values[6] . '</p>';
      return $html;
    }

    /**
     * builds a dropdown setting element
     * 
     * @param array $values array of setting values
     * @param bool  $multi if true, builds a multiselect
     * 
     * @return string HTML
     */
    protected function _build_dropdown( $values, $multi = false )
    {
      $selectstring = $this->set_selectstring( $values[1] );
      $html = '';
      $option_pattern = "\n" . '<option value="%4$s" %9$s ><span>%5$s</span></option>';
      $optgroup_pattern = "\n" . '<optgroup label="%4$s">';
      $in_optgroup = false;
      $is_associated = PDb_FormElement::is_assoc( $values[7] );
      
      if ( $multi ) {
        $html .= "\n" . '<div class="dropdown-group ' . $values[1] . ' ' . $values[4] . '" ><select name="' . $this->settings_name() . '[' . $values[0] . '][]" multiple ' . $values[9] . ' >';
      } else {
        $html .= "\n" . '<div class="dropdown-group ' . $values[1] . ' ' . $values[4] . '" ><select name="' . $this->settings_name() . '[' . $values[0] . ']" ' . $values[9] . ' >';
      }


      if ( $is_associated ) {
        
        foreach ( $values[7] as $name => $title ) {
          $values[8] = in_array( $name, (array) $values[2] ) ? $selectstring : '';
          $values[3] = Participants_Db::string_static_translation( $name );
          $values[4] = $title;
          $pattern = $option_pattern;
          if ( $title === 'optgroup' ) {
            if ( $in_optgroup ) {
              $html .= '</optgroup>';
              $in_optgroup = false;
            }
            $pattern = $optgroup_pattern;
            $in_optgroup = true;
          }
          $html .= vsprintf( $pattern, $values );
        }
      } else {
        foreach ( $values[7] as $value ) {
          $values[8] = in_array( $value, (array) $values[2] ) ? $selectstring : '';
          $values[3] = $value;
          $values[4] = $value;
          $html .= vsprintf( $option_pattern, $values );
        }
      }
      if ( $in_optgroup ) {
        $html .= '</optgroup>';
        $in_optgroup = false;
      }
      $html .= "\n" . '</select></div>';
      if ( !empty( $values[6] ) )
        $html .= "\n" . '<p class="description">' . $values[6] . '</p>';
      return $html;
    }

    /**
     * builds a multiselect drodown
     * 
     * @param array $values config array
     * 
     * @return atring HTML
     */
    protected function _build_multiselect( $values )
    {
      return $this->_build_dropdown( $values, true );
    }

    /**
     * provides a subsection divider
     * 
     * @param array $values array of setting values
     *                       0 - setting name (%1$s)
     *                       1 - element type (%2$s)
     *                       2 - setting value
     *                       3 - title
     *                       4 - CSS class
     *                       5 - CSS style
     *                       6 - help text
     *                       7 - setting options array
     *                       8 - select string
     * @return string HTML
     */
    protected function _build_subsection( $values )
    {
      return vsprintf( '<div class="settings-subsection">%7$s</div>', $values );
    }

    /**
     * builds a color selector
     * 
     * @param array $values
     * @return string html
     */
    protected function _build_color_selector( $values )
    {
      return vsprintf( '<input name="' . $this->settings_name() . '[%1$s]" type="color" value="%3$s" title="%4$s" class="%5$s" style="%6$s" %10$s  />', $values );
    }

    /**
     * provides an attributes string
     * 
     * @param array $attributes as $name => $value
     * @return HTML atributes string
     */
    protected function input_attributes( Array $attributes )
    {
      $atts_string = '';
      $exclude = array('name', 'value', 'class', 'style', 'type', 'title'); // atts we don't set this way
      foreach ( $attributes as $name => $value ) {
        if ( !in_array( $name, $exclude ) ) {
          $atts_string .= $name . '="' . esc_attr( $value ) . '" ';
        }
      }
      return $atts_string;
    }

    /**
     * sets the select string
     * 
     * define a select indicator string fro form elements that offer multiple slections
     * 
     * @param string $type the form element type
     */
    protected function set_selectstring( $type )
    {
      switch ( $type ) {
        case 'radio':
        case 'checkbox':
        case 'multicheckbox':
          return 'checked="checked"';
        case 'dropdown':
        case 'multiselect':
          return 'selected="selected"';
        default:
          return '';
      }
    }

    /**
     * builds a text setting control
     * 
     * @param array $setting the parameters of the setting
     * @param array $values  an array of setting values for use as a replacement array
     * @return string the setting control HTMLn
     */
    protected function _text_setting( $setting, $values )
    {
      $pattern = '<input name="' . $this->settings_name() . '[%1$s]" type="%2$s" value="%3$s" title="%4$s" class="%5$s" style="%6$s"  />';
      if ( !empty( $setting['help'] ) )
        $pattern .= '<p class="description">%7$s</p>';
      return vsprintf( $pattern, $values );
    }

    /**
     * provides a list of fields valid for use as a list selector
     * 
     * @param string $allowed comma-separated list of form_element types to allow
     * @retrun array of fields; 'title' => 'name'
     */
    protected function field_selector( $allowed )
    {
      $available_fields = array();
      foreach ( Participants_Db::field_defs() as $fieldname => $field ) {
        /* @var $field \PDB_Form_Field_Def */
        if (
                in_array( $field->form_element(), explode( ',', str_replace( ' ', '', $allowed ) ) )
        ) {
          $available_fields[$fieldname] = $field->title() . ' (' . $fieldname . ')';
        }
      }
      return $available_fields;
    }

    /**
     * executes save settings callbacks
     * 
     * when the plugin settings are saved, each one is checked for a callback to execute
     * 
     * @uses WP filter 'pre_update_option_{option_name}'
     */
    public function settings_callbacks( $new_value, $old_value )
    {
      $settings_values = $new_value;
      foreach ( $settings_values as $name => $value ) {
        $callback = array($this, 'setting_callback_for_' . $name);
        if ( is_callable( $callback ) ) {
          $prev_value = isset( $old_value[$name] ) ? $old_value[$name] : '';
          $new_value[$name] = call_user_func( $callback, $value, $prev_value );
        }
      }
      return $new_value;
    }

    /**
     * shows an error message in the admin
     */
    function _trigger_error( $message, $errno = E_USER_ERROR )
    {
      if ( isset( $_GET['action'] ) and false !== stripos( $_GET['action'], 'error_scrape' ) ) {
        Participants_Db::debug_log( 'Plugin Activation Failed: ' . $_GET['plugin'] );
        echo($message);
        exit;
      } else {
        trigger_error( $message, $errno );
      }
    }
    
    /**
     * check for setup_updates method in aux plugin and notifies user
     * 
     * @return bool true if the updater plugin is installed
     */
    protected function check_aux_plugin_updater()
    {
      $plugin_updater_installed = true;
      
      if ( ! is_plugin_active( 'xnau-plugin-updates/xnau-plugin-updates.php' ) ) {
        
        $plugin_updater_installed = false;
      }
      
      return $plugin_updater_installed;
    }
      
    /**
     * advises the user to install the updater plugin
     * 
     * @param string $plugin_name
     */
    public static function missing_updater_plugin_notice( $plugin_name )
    {
      $needs_update = false;
      // enable this once all the aux plugins have releases with the new updater available
      $needs_update = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['class'] === 'PDb_Aux_Plugin';
      
      $message = '';

      $notice = 'xnau-updater-';

      if ( ! is_plugin_active( 'xnau-plugin-updates/xnau-plugin-updates.php' ) )
      {
        $notice .= 'post-remove-';
        $message = sprintf( __('The <strong>xnau Plugin Updates</strong> plugin is not installed and activated. You must download and activate this free plugin to recieve updates to the following plugins. %sDownload now%s', 'participants-database' ), '<a href="https://xnau.com/the-xnau-plugin-updater/" target="_blank">', '</a>' );
      }
      
      $message .= '<ul>%s</ul>';

      if ( $needs_update )
      {
        $notice .= 'needs-update-';
        $message .= '<br\><strong>' . __('All Participants Database Add-On plugins must be updated to the latest version to recieve updates.', 'participants-database' ) . '</strong>';
      }

      $notice .= '7'; // notice will stay dismissed for 7 days

      $plugin_name_list = get_transient( 'xnau-updater-notice-plugins' );

      if ( !is_array($plugin_name_list) && $notice !== 'xnau-updater-7' )
      {
        $plugin_name_list = array( $plugin_name );

        add_action( 'admin_notices', function() use ($notice,$message) {

          if ( PAnD::is_admin_notice_active($notice) ) {

            $plugin_names = get_transient( 'xnau-updater-notice-plugins' );

            $plugin_message = sprintf( $message, '<li>' . implode( '</li><li>', (array) $plugin_names ) . '</li>' );

            printf( '<div class="notice notice-warning is-dismissible" data-dismissible="%s"><p><span class="dashicons dashicons-warning"></span>%s</p></div>', $notice, $plugin_message );
            
            delete_transient( 'xnau-updater-notice-plugins' );
          }

        } );
      
      } else
      {
        if ( ! is_array( $plugin_name_list ) )
        {
          $plugin_name_list = array( $plugin_name );
        }
        
        if ( ! in_array( $plugin_name, $plugin_name_list ) )
        {
          $plugin_name_list[] = $plugin_name;
        }
      }
      
      set_transient( 'xnau-updater-notice-plugins', $plugin_name_list, 5 );
    }
    
    /**
     * advises the user to update their aux plugin
     */
    private static function aux_plugin_update_notice()
    {
      $notice = $this->aux_plugin_name . '-requres-update';
      $message = sprintf( __( 'The %s plugin must be updated to its latest version to continue to recieve updates.') );
    }

  }

  endif;
