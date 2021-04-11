<?php
/**
 * class that provides the HTML for a rich text field
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

use Participants_Db;

class rich_text_editor {

  /**
   * string holds the name of the field
   */
  private $fieldname;

  /**
   * string holds the field value
   */
  private $value;

  /**
   * array holds the editor configuration array
   */
  private $config;

  /**
   * array holds the tinymce config 
   */
  private $tinymce_config;

  /**
   * initializes the object
   * 
   * @param string  $fieldname name if the field
   * @param string  $value the content of the field
   * @param array   $config the configuration array (optional)
   */
  public function __construct( $fieldname, $value, $config = array() )
  {
    $this->fieldname = $fieldname;
    $this->value = htmlspecialchars_decode( $value );
    $this->setup_config( $config );
  }

  /**
   * prints the rich text editor
   */
  public function print_editor()
  {
    echo $this->get_editor();
  }

  /**
   * provides the rich text editor HTML
   * 
   * @return string
   */
  public function get_editor()
  {
    $output = array();

    $output[] = '<textarea name="' . $this->fieldname . '" id="' . $this->element_id() . '" ' . $this->textarea_dim_atts() . '>';
    $output[] = $this->value;
    $output[] = '</textarea>';
    $output[] = $this->field_js();

    return implode( PHP_EOL, $output );
  }

  /**
   * provides the field JS
   */
  private function field_js()
  {
    ob_start();
    ?>
    <script>
      jQuery(function ($) {
        if (wp.editor) {
          wp.editor.initialize("<?php echo $this->element_id() ?>", <?php echo $this->editor_config_object() ?>);
          <?php $this->field_editor_label_fix() ?>
        } else {
          console.warn('WP Core text editor not loaded: rich text editors are disabled.');
        }
      });
    </script>
    <?php
    return ob_get_clean();
  }

  /**
   * provides specific code for the manage database fields page
   * 
   * @return null
   */
  private function field_editor_label_fix()
  {
    if ( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) === 'participants-database-manage_fields' ) {
      ob_start();
      ?>
      <script>
        $(document).on('wp-before-quicktags-init', function () {
          var el = $('#<?php echo $this->element_id() ?>').closest('.wp-editor-wrap');
          if (el.length) {
            var label = el.nextAll('.attribute-control.rich-text-control-wrap').first();
            el.prepend(label.css({position: 'relative', bottom: '-2em', 'margin-top': '-1.5em'}));
          }
        });
      </script>
      <?php
      echo str_replace( array( '<script>', '</script>' ), '', ob_get_clean() );
    }
  }

  /**
   * provides the TinyMCE configuration object
   * 
   * @return string JS object
   */
  private function editor_config_object()
  {
    $settings = json_encode( $this->editor_settings(), JSON_FORCE_OBJECT );

    /*
     * these replacements set the correct js syntax for 2nd dimensional arrays 
     * and remove unneeded escapes
     */
    return str_replace( array( '"{', '}"', '"[', ']"', '\\' ), array( '{', '}', '[', ']', '' ), $settings ); // 
  }

  /**
   * provides the TinyMCE configuration
   * 
   * @return array
   */
  private function editor_settings()
  {
    $this->check_for_class_include();

    // use a filter to get the default configuration from WP core
    add_filter( 'tiny_mce_before_init', array( $this, 'get_tinymce_config' ) );

    $settings = \_WP_Editors::parse_settings( $this->element_id(), $this->config );

    \_WP_Editors::editor_settings( $this->element_id(), $settings );

    remove_filter( 'tiny_mce_before_init', array( $this, 'get_tinymce_config' ) );

    $settings[ 'tinymce' ] = $this->tinymce_config;

    return $settings;
  }

  /**
   * provides the element ID
   * 
   * @return string
   */
  private function element_id()
  {
    return Participants_Db::rich_text_editor_id( $this->fieldname );
  }

  /**
   * provides the text area dimensions string
   * 
   * @return array
   */
  private function textarea_dims()
  {
    return Participants_Db::apply_filters( 'rich_text_editor_dimensions', array( 'rows' => $this->rows(), 'cols' => 40 ) );
  }

  /**
   * gets the textarea row value
   * 
   * this will get the row value from the field definition attributes if provided
   * 
   * @return int
   */
  private function rows()
  {
    $rows = 20; // default

    if ( \PDb_Form_Field_Def::is_field( $this->fieldname ) ) {

      $field = \Participants_Db::$fields[ $this->fieldname ];
      /** @var \PDb_Form_Field_Def $field */
      $rows_att = intval( $field->get_attribute( 'rows' ) );

      if ( $rows_att > 0 ) {
        $rows = $rows_att;
      }
    }

    return $rows;
  }

  /**
   * provides the text area dimensions string
   * 
   * @return string
   */
  private function textarea_dim_atts()
  {
    $dims = $this->textarea_dims();

    return 'rows="' . $dims[ 'rows' ] . '" cols="' . $dims[ 'cols' ] . '"';
  }

  /**
   * provides the default editor config array
   * 
   * @return array
   */
  private function default_config()
  {
    return array(
        'wpautop' => true,
        'media_buttons' => false,
        'default_editor' => '',
        'drag_drop_upload' => false,
        'textarea_name' => $this->fieldname,
        'textarea_rows' => $this->textarea_dims()[ 'rows' ],
        'tabindex' => '',
        'tabfocus_elements' => ':prev,:next',
        'editor_css' => '',
        'editor_class' => '',
        'teeny' => false,
        '_content_editor_dfw' => false,
        'tinymce' => true,
        'quicktags' => true,
    );
  }

  /**
   * gets the tinymce config from a filter
   * 
   * this is called on the tiny_mce_before_init filter
   * 
   * @param array $tinymce_config
   * @return array
   */
  public function get_tinymce_config( $tinymce_config )
  {
    $tinymce_config[ 'height' ] = strval( $this->rows() * 1.2 ) . 'em';

    $this->tinymce_config = $tinymce_config;

    return $tinymce_config;
  }

  /**
   * sets the config array
   * 
   * @param array $config array of configuration values to set
   */
  private function setup_config( $config )
  {
    $this->config = array_merge( $this->default_config(), $config );
  }

  /**
   * checks for the need to include the editor class script
   */
  private function check_for_class_include()
  {
    if ( !class_exists( '\_WP_Editors' ) ) {
      require_once( ABSPATH . WPINC . '/class-wp-editor.php' );
    }
    \_WP_Editors::enqueue_default_editor();
  }

}
