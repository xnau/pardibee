<?php

/**
 * PDb subclass for printing and managing form elements
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.6.3
 * @link       http://wordpress.org/extend/plugins/participants-database/
 *
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_FormElement extends xnau_FormElement {

  /**
   * @var string dummy password
   * 
   * this string is used to show that a password is present; it is not saved to the database
   */
  const dummy = '***************';

  /**
   * instantiates a xnau_FormElement object
   * 
   *
   * @param array $parameters carries the parameters to build a form element
   *                    type         string sets the type of element to print
   *                    value        string the current value of the element
   *                    name         string the name attribute of the element
   *                    options      mixed  an optional array of values for checkboxes, selects, etc. Can also
   *                                        be serialized array. A special element in this array has the key 
   *                                        "null_select" which if bool false prevents the selected null case of 
   *                                        dropdown elements from being added. If it has another value, the null 
   *                                        case (which has a blank label) will hold this value and be selected 
   *                                        if no value property is provided to the instance
   *                    attributes   array  an optional array of name=>value set of HTML attributes to include
   *                                        (can include a class attribute)
   *                    class        string a class name for the element; more than one class name must be
   *                                        space-separated string
   *                    indent       int    starting indent value
   *                    size         int    the size of the field
   *                    container_id string CSS id for the element containter (if any)
   *
   * @return NULL
   */
  public function __construct( $parameters )
  {
    $this->prefix = Participants_Db::$prefix;

    parent::__construct( $parameters );
  }

  /**
   * builds the HTML string for display
   *
   * @var array parameters as per __construct()
   * @static
   */
  public static function _HTML( $parameters )
  {

    $Element = new PDb_FormElement( $parameters );

    return $Element->_output();
  }

  /*   * *******************
   * PUBLIC METHODS
   */

  /**
   * prints a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public static function print_element( $parameters )
  {

    $Element = new PDb_FormElement( $parameters );

    echo $Element->_output();
  }

  /**
   * returns a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public static function get_element( $parameters )
  {
    $Element = new PDb_FormElement( $parameters );

    return $Element->_output();
  }

  /**
   * outputs a set of hidden inputs
   *
   * @param array $fields name=>value pairs for each hidden input tag
   */
  public static function print_hidden_fields( $fields, $print = true )
  {

    $output = array();

    $atts = array('type' => 'hidden');

    foreach ( $fields as $k => $v ) {

      $atts['name'] = $k;
      $atts['value'] = $v;

      $output[] = self::_HTML( $atts );
    }

    if ( $print )
      echo implode( PHP_EOL, $output );
    else
      return implode( PHP_EOL, $output );
  }

  /**
   * builds an output string
   */
  protected function _output()
  {

    /**
     * @version 1.7.0.9
     * @filter pdb-form_element_html
     * @param string html
     * @return string
     */
    return Participants_Db::apply_filters( 'form_element_html', parent::_output() );
  }

  /**
   * builds the form element
   * 
   * allows an external func to build the element. If that doens't happen, uses 
   * the parent method to build it
   * 
   * @return null
   */
  function build_element()
  {
    /**
     * we pass the object to an external function with 
     * a filter handle that includes the name of the custom form element. The 
     * filter callback is expected to fill the output property
     * 
     * @action pdb-form_element_build_{$type}
     */
    Participants_Db::do_action( 'form_element_build_' . $this->type, $this );

    if ( empty( $this->output ) ) {
      $this->call_element_method();
    }
  }

  /**
   * returns an element value formatted for display or storage
   * 
   * this supplants the function Participants_Db::prep_field_for_display
   * 
   * @param object|string $field a PDb_Field_Item object or field name
   * @param bool   $html  if true, returns the value wrapped in HTML, false returns 
   *                      the formatted value alone
   * @return string the object's current value, formatted
   */
  public static function get_field_value_display( $field, $html = true )
  {

    if ( !is_a( $field, 'PDb_Field_Item' ) ) {
      // now we can use our field class methods
      $field = new PDb_Field_Item( $field );
    }
    
    /* @var $field PDb_Field_Item */
    
    $field->html_mode($html);

    $return = false;

    /**
     * @filter pdb-before_display_form_element
     * 
     * @param bool false
     * @param PDb_Field_Item $field the field object
     * @return string the field value display or false if not altering the value
     * 
     * formerly, this was set as "pdb-before_display_field" and included a more limited set of arguments
     */
    if ( has_filter( Participants_Db::$prefix . 'before_display_form_element' ) ) {
      $return = Participants_Db::apply_filters( 'before_display_form_element', $return, $field );
    } elseif ( has_filter( Participants_Db::$prefix . 'before_display_field' ) ) {
      // provided for backward-compatibility
      $return = Participants_Db::apply_filters( 'before_display_field', $return, $field->value(), $field->form_element() );
    }

    if ( $return === false ) {

      switch ( $field->form_element() ) :

        
        case 'image-upload' :
          
          switch ( $field->module() ) {
            case 'single':
            case 'list':
            case 'tag-template':
              $display_mode = 'image';
              break;
            case 'signup':
            case 'admin-edit':
            case 'record':
              $display_mode = 'both';
              break;
            default :
              $display_mode = 'none';
          }

          $image = new PDb_Image( array(
              'filename' => $field->value(),
              'link' => $field->link(),
              'module' => $field->module(),
              'mode' => $display_mode,
              'attributes' => $field->attributes(),
                  ) );

          if ( $html ) {

            $image->set_image_wrap();

            $return = $image->get_image_html();
            
          } elseif ( $image->file_exists ) {
            $return = $image->get_image_file();
          } else {
            $return = $field->value();
          }

          break;

        case 'file-upload' :
          
          if ( $html && $field->is_not_default() ) {
            $return = '';
            if ( $field->module === 'signup' ) {
              $field->set_link( false );
              $return = $field->value();
            } elseif ( $field->has_content() && Participants_Db::is_allowed_file_extension( $field->value(), $field->attributes() ) ) {
              $field->set_link( filter_var( Participants_Db::files_uri() . $field->value, FILTER_VALIDATE_URL ) );
              if ( (!is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) && $field->link() && strlen( $field->default ) > 0 ) {
                $field->set_value( $field->default );
              }
              $return = self::make_link( $field );
            }
            break;
          } else {
            // no valid filename in the value, show a blank
            $return = '';
            break;
          }

        case 'date' :
          if ( $field->has_value() ) {
            $return = PDb_Date_Display::get_date( $field->value, __METHOD__ . ' date field' );
          }
          $return = $return ? $return : '';
          break;

        case 'timestamp' :

          if ( $field->has_value() ) {
            $return = Participants_Db::plugin_setting_is_true( 'show_time' ) ? PDb_Date_Display::get_date_time( $field->value(), __METHOD__ . ' timestamp field with time' ) : PDb_Date_Display::get_date( $field->value(), __METHOD__ . ' timestamp field' );
          }
          $return = $return ? $return : '';
          break;

        case 'multi-checkbox' :
        case 'multi-select-other' :
        case 'multi-dropdown':

          /*
           * these elements are stored as serialized arrays of values, the data is displayed 
           * a comma-separated string of the values, using the value titles if defined
           */
          $return = $field->display_array_value();
          break;

        case 'link' :

          $linkdata = maybe_unserialize( $field->value() );
          
          if ( !empty( $linkdata ) && is_array( $linkdata ) ) {
            list( $url, $value ) = $linkdata + array('','');
          } else {
            $url = $field->link();
            $value = $field->value();
          }
          
          if ( strlen( $value ) < 1 ) {
            if ( strlen( $url ) > 0) {
              $value = $field->has_default() ? $field->default : preg_replace( '#https?://#', '', $url );
            } else {
              $value = '';
            }
          }

          if ( $html )
            $return = sprintf( ( empty( $url ) ? '%1$s%2$s' : '<a href="%1$s" %3$s >%2$s</a>' ), $url, $value, self::html_attributes( $field->attributes ) );
          else
            $return = empty($url) ? $value : $url;
          break;

        case 'text-line' :

          if ( $html ) {

            $return = self::make_link( $field );

            break;
          } else {

            $return = esc_html( $field->value() );

            break;
          }

        case 'text-area':
        case 'textarea':

          $pattern = $html ? '<span ' . self::class_attribute( 'textarea' ) . '>%s</span>' : '%s';
          $return = sprintf( $pattern, esc_textarea( $field->value() ) );
          break;

        case 'rich-text':

          if ( $html ) {
            $return = sprintf( '<span ' . self::class_attribute( 'textarea richtext' ) . '>%s</span>', Participants_Db::process_rich_text( $field->value(), 'rich-text field' ) );
          } else {
            $return = strip_tags( esc_textarea( $field->value() ) );
          }
          
          break;

        case 'dropdown':
        case 'radio':
        case 'checkbox':
        case 'dropdown-other':
        case 'select-other':

          if ( $html ) {
            $temp = $field->value();
            $field->set_value( $field->display_array_value() );
            $return = sprintf( '<span %s>%s</span>', self::class_attribute( $field->form_element() ), self::make_link( $field ) );
            $field->set_value( $temp );
          } else {
            $return = $field->display_array_value();
          }
          
          break;

        case 'placeholder':

          $field->set_value( $field->default_value() );
          
          $return = $html ? self::make_link( $field ) : $field->value();
          
          break;

        case 'password':
          // password hashes are never shown
          $return = '';
          break;

        case 'decimal':
        case 'currency':
        case 'numeric':
          
          $field_display = $field->get_value();
          
          // localize the display value
          switch ( $field->form_element() ) {
            case 'decimal':
              // this is to remove any trailing zeroes
              $field_display = PDb_Localization::display_number( floatval( $field->value() ), $field );
              break;
            case 'currency':
              $field_display = PDb_Localization::display_currency( $field->value(), $field );
              break;
          }

          if ( isset( $field->attributes['data-before'] ) && $field->has_content() ) {
            $field_display = '<span class="pdb-added-content"><span class="pdb-precontent">' . esc_html( $field->attributes['data-before'] ) . '</span>' . esc_html( $field_display ) . '</span>';
          } elseif ( isset( $field->attributes['data-after'] ) && $field->has_content() ) {
            $field_display = '<span class="pdb-added-content">' . esc_html( $field_display ) . '<span class="pdb-postcontent">' . esc_html( $field->attributes['data-after'] ) . '</span></span>';
          }
          
          $return = $field_display;
          break;

        case 'hidden':

          if ( $field->is_dynamic_hidden_field() && !$field->is_not_default() ) {
            // this is to prevent the dynamic value key from getting printed
            $field->set_value( '' );
          } elseif ( !$field->is_dynamic_hidden_field() && !$field->has_content() ) {
            // show the default value if it's not a dynamic field value and there is no set value
            $field->set_value( $field->default );
          }
        // don't break here so we can assign the return value
        default :

          $return = $html ? self::make_link( $field ) : $field->value();

      endswitch;
    }

    return $return;
  }

  /**
   * builds a checkbox or radio input series
   *
   * @param string $type sets the type of input series, defaults to checkbox
   * @param string|bool if string, add an "other" option with this label
   */
  protected function _add_input_series( $type = 'checkbox', $otherlabel = false )
  {
    if ( empty( $this->options ) )
      return;

    // checkboxes are grouped, radios are not
    $this->group = $type === 'checkbox';

    // checkboxes are given a null select so an "unchecked" state is possible
    $null_select = (isset( $this->options[self::null_select_key()] )) ? $this->options[self::null_select_key()] : ($type == 'checkbox' ? true : false);

    if ( $null_select !== false ) {
      if ( $type === 'checkbox' ) {
        $id = $this->element_id();
        $this->attributes['id'] = $id . '-default';
        $this->_addline( $this->_input_tag( 'hidden', (is_string( $null_select ) && $null_select !== 'false' ? $null_select : '' ), false ), 1 );
        $this->attributes['id'] = $id;
      } elseif ( $this->options[self::null_select_key()] !== 'false' ) { 
        // for radio buttons, include a "none" if configured
        $this->options[$this->options[self::null_select_key()]] = '';
      }
    }
    unset( $this->options[self::null_select_key()] );

    $this->_addline( '<div class="' . $type . '-group" >' );

    $optgroup = false;
    
    // use the default value (if defined) if there is no value
//    if ( $this->value === '' && Participants_Db::$fields[$this->name]->default_value() !== '' ) {
//      $this->value = Participants_Db::$fields[$this->name]->default_value();
//    }

    foreach ( $this->_make_assoc( $this->options ) as $option_key => $option_value ) {

      $option_key = Participants_Db::apply_filters( 'translate_string', stripslashes( $option_key ) );

      if ( ( $option_value === 'optgroup') and ! empty( $option_key ) ) {
        if ( $optgroup ) {
          $this->_addline( '</fieldset>' );
        }
        $id = $this->legal_name( $this->name . '-' . ($option_value === '' ? '_' : trim( strtolower( $option_key ) )) );
        $this->_addline( '<fieldset class="' . esc_attr( $type . '-subgroup ' . $this->name . '-subgroup' ) . '" id="' . esc_attr( $id ) . '"><legend>' . esc_html( $option_key ) . '</legend>' );
        $optgroup = true;
      } else {
        $id = $this->element_id();
        $this->attributes['id'] = $this->legal_name( $this->prefix . $this->name . '-' . ($option_value === '' ? '_' : trim( strtolower( $option_value ) )) );
        $this->_addline( '<label ' . $this->_class() . ' for="' . esc_attr( $this->attributes['id'] ) . '">' );
        $this->_addline( $this->_input_tag( $type, $option_value, 'checked' ), 1 );
        $this->_addline( $option_key . '</label>' );
        $this->attributes['id'] = $id;
      }
    }
    if ( $optgroup ) {
      $this->_addline( '</fieldset>' );
      $optgroup = false;
    }
    if ( $otherlabel ) {

      $value = $type == 'checkbox' ? (isset( $this->value['other'] ) ? $this->value['other'] : '') : $this->value;
      $this->_addline( '<div class="othercontrol">' );
      $id = $this->element_id();
      $this->attributes['id'] = $id . '_otherselect';
      $this->_addline( '<label ' . $this->_class() . ' for="' . esc_attr( $this->attributes['id'] ) . '">' );
      $this->_addline( sprintf( '<input type="%s" name="%s"  value="%s" %s %s />', esc_attr( $type ), $type === 'radio' ? esc_attr( $this->name ) : 'pdb-otherselector', esc_attr( $otherlabel ), $this->_set_selected( $this->options, $value, 'checked', $value === '' ), $this->_attributes() . $this->_class( 'otherselect' )
              ), 1 );
      $this->attributes['id'] = $id;
      $this->_addline( esc_html( $otherlabel ) . ':' );
      $this->_addline( '</label>', -1 );
      $this->_addline( '</div>', -1 );
    }

    $this->_addline( '</div>' );
  }

  /**
   * provides a display string for an array field value
   * 
   * for multi-select form elements
   * 
   * @param PDb_FIeld_Item $field the field object
   * 
   * @return string the array presented as a string
   */
  static function array_display( $field )
  {
    $titles = array();
    foreach ( self::field_value_array( $field->value() ) as $value ) {
      $titles[] = $field->value_title( $value );
    }

    return sanitize_post( implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $titles ) );
  }

  /*   * *********************** 
   * ELEMENT CONSTRUCTORS
   */

  /**
   * builds a rich-text editor (textarea) element
   */
  protected function _rich_text_field()
  {

    if ( !is_admin() and ! Participants_Db::plugin_setting_is_true('rich_text_editor') )
      $this->_text_field();
    else
      parent::_rich_text_field();
  }

  /**
   * builds a captcha element
   * 
   */
  protected function _captcha()
  {
    $captcha = new PDb_CAPTCHA( $this );
    $this->_addline( $captcha->get_html() );
  }

  /**
   * builds a numeric input element
   */
  protected function _numeric()
  {

    if ( is_array( $this->value ) ) {
      $this->value = current( $this->value );
    }

    $this->add_options_to_attributes();

    $this->_addline( $this->_input_tag( 'number' ) );
  }

  /**
   * builds a date field
   */
  protected function _date_field()
  {

    $this->add_class( 'date_field' );

    if ( !empty( $this->value ) ) {
      $this->value = PDb_Date_Display::get_date( $this->value, __METHOD__ . ' date field' );
    }

    $this->_addline( $this->_input_tag() );
  }

  /**
   * builds a file upload element
   * 
   * @param string $type the upload type: file or image
   */
  protected function _upload( $type )
  {
    $field_def = Participants_Db::$fields[$this->name];
    /* @var $field_def PDb_Form_field_Def */
    $this->_addline( '<div class="' . $this->prefix . 'upload">' );
    // if a file is already defined, show it
    if ( $this->value !== $field_def->default_value() ) {

      $this->_addline( self::get_field_value_display( $this ) );
    }

    // add the MAX_FILE_SIZE field
    // this is really just for guidance, not a valid safeguard; this must be checked on submission
    if ( isset( $this->options['max_file_size'] ) )
      $max_size = $this->options['max_file_size'];
    else
      $max_size = ( (int) ini_get( 'post_max_size' ) / 2 ) * 1048576; // half it to give a cushion

    $this->_addline( $this->print_hidden_fields( array('MAX_FILE_SIZE' => $max_size, $this->name => $this->value), false ) );

    if ( !isset( $this->attributes['readonly'] ) ) {

      $this->_addline( $this->_input_tag( 'file' ) );

      // add the delete checkbox if there is a file defined
      if ( $this->value !== $field_def->default_value() && $this->module !== 'signup' ) {
        unset($this->attributes['id']);
        $this->_addline( '<span class="file-delete" ><label><input type="checkbox" value="delete" name="' . esc_attr( $this->name . '-deletefile' ) . '" ' . $this->_attributes( 'no validate' ) . '>' . __( 'delete', 'participants-database' ) . '</label></span>' );
      }
    }

    $this->_addline( '</div>' );
  }

  /**
   * builds a password text element
   */
  protected function _password()
  {
    if ( !empty( $this->value ) ) {
      $this->value = self::dummy;
    }

    $this->_addline( $this->_input_tag( 'password' ) );
  }

  /*   * ************************* 
   * UTILITY FUNCTIONS
   */

  /**
   * sets up the null select for dropdown elements
   */
  protected function _set_null_select()
  {
    $field = Participants_Db::get_column( $this->name );

    $default = '';
    if ( $field ) {
      $default = $field->default;
    }

    /*
     * this is to add a blank null select option if there is no default, no defined 
     * null select and no set field value
     */
    if ( self::is_empty( $default ) && !isset( $this->options[self::null_select_key()] ) && self::is_empty( $this->value ) ) {
      $this->options[self::null_select_key()] = '';
    }

    parent::_set_null_select();
  }

  /**
   * outputs a link (HTML anchor tag) in specified format if enabled by "make_links"
   * option
   * 
   * this func validates the link as being either an email addres or URI, then
   * (if enabled) builds the HTML and returns it
   * 
   * @param PDb_Field_Item|object $field the field object
   * @param string $linktext the clickable text (optional)
   * @param string $template the format of the link (optional)
   * @param array  $get an array of name=>value pairs to include in the get string
   *
   * @return string HTML or HTML-escaped string (if it's not a link)
   */
  public static function make_link( $field, $template = false, $get = false )
  {

    // convert the PDb_Field_Item object to a stdClass
    // for backward compatibility
    if ( ! is_a( $field, 'PDb_Field_Item' ) ) {
      if ( PDB_DEBUG ) Participants_Db::debug_log ( __METHOD__.' called with: '.print_r($field,1));
      $field = new PDb_Field_Item( $field );
    }
    /* @var PDb_Field_Item $field */

    /**
     * links may only be placed on string values
     */
    if ( is_array( $field->get_value() ) )
      return $field->get_value();

    // clean up the provided string
    $URI = str_replace( 'mailto:', '', trim( strip_tags( $field->get_value() ) ) );
    
    if ( $field->has_link() ) {
      /*
       * the field is a single record link or other field with the link property 
       * set, which becomes our href
       */
      $URI = $field->link();
      $linktext = PDb_Manage_Fields_Updates::sanitize_text($field->get_value());
    } elseif ( filter_var( $URI, FILTER_VALIDATE_URL ) !== false && Participants_Db::plugin_setting_is_true( 'make_links' ) ) {

      // convert the get array to a get string and add it to the URI
      if ( is_array( $get ) ) {

        $URI .= false !== strpos( $URI, '?' ) ? '&' : '?';

        $URI .= http_build_query( $get );
      }
    } elseif ( filter_var( $URI, FILTER_VALIDATE_EMAIL ) !== false && Participants_Db::plugin_setting_is_true( 'make_links' ) ) {

      if ( Participants_Db::plugin_setting_is_true( 'email_protect' ) && !Participants_Db::$sending_email ) {

        // the email gets displayed in plaintext if javascript is disabled; a clickable link if enabled
        list( $URI, $linktext ) = explode( '@', $URI, 2 );
        $template = '<a class="obfuscate" data-email-values=\'{"name":"%1$s","domain":"%2$s"}\'>%1$s AT %2$s</a>';
      } else {
        $linktext = strip_tags( $URI );
        $URI = 'mailto:' . $URI;
      }
    } elseif ( filter_var( $URI, FILTER_VALIDATE_EMAIL ) !== false && Participants_Db::plugin_setting_is_true( 'email_protect' ) && !Participants_Db::$sending_email ) {

      /**
       * @todo obfuscate other email links
       * if the email address is wrapped in a link, we should obfuscate it
       */
      return $URI;
    } else {
      // if it is neither URL nor email address simply display the sanitized text
      return PDb_Manage_Fields_Updates::sanitize_text($field->get_value());
    }

    // default template for links
    $linktemplate = $template === false ? '<a href="%1$s" %3$s >%2$s</a>' : $template;

    $linktext = empty( $linktext ) ? str_replace( array('http://', 'https://'), '', $URI ) : $linktext;
    
    $attributes = self::html_attributes($field->attributes, array('rel','download','target','type'));

    //construct the link
    return sprintf( $linktemplate, $URI, $linktext, $attributes );
  }

  /**
   * tells if the current screen is the admin list page
   * 
   * @return bool true if on that page
   */
  private static function is_admin_list_page()
  {
    if ( function_exists( 'get_current_screen' ) && $screen = get_current_screen() ) {
      return $screen->id === 'toplevel_page_participants-database';
    }
    return false;
  }

  /**
   * get the title that corresponds to a value from a value series
   * 
   * this func grabs the value and matches it to a title from a list of values set 
   * for a particular field
   * 
   * if there is no title defined, or if the values is stored as a simple string, 
   * the value is returned unchanged
   * 
   * @global object $wpdb
   * @param array $values
   * @param string $fieldname
   * @return array of value=>title pairs
   */
  public static function get_value_titles( $values, $fieldname )
  {
    $options_array = Participants_Db::$fields[$fieldname]->options();
    return array_flip( $options_array );
  }

  /**
   * get the title that corresponds to a value from a value series
   * 
   * this func grabs the value and matches it to a title from a list of values set 
   * for a particular field
   * 
   * if there is no title defined, or if the values are stored as a simple string, 
   * the value is returned unchanged
   * 
   * @param string $value
   * @param string $fieldname
   * @return string the title matching the value
   */
  public static function get_value_title( $value, $fieldname )
  {
    $field = PDb_Form_Field_Def::is_field( $fieldname ) ? Participants_Db::$fields[$fieldname] : false;
    /* @var $field PDb_Form_field_Def */
    return $field ? $field->value_title($value) : $value;
  }

  /**
   * gets the option value that corresponds to an option title from a set of field options
   * 
   * this uses a progressive match, first trying an exact match, then substring 
   * matches, then a similar string match to efficiently find direct matches but 
   * return a best-guess close match if no direct match is found
   * 
   * @param string $title the title of the value
   * @param string $fieldname the name of the field
   * @return string the value that matches the title given
   */
  public static function get_title_value( $title, $fieldname )
  {
    $value = $title; // if no title is found, return the title argument
    
    $field = Participants_Db::get_field_def( $fieldname );
    
    if ( $field && $field->is_value_set() ) {
      
      $options_array = $field->options();
      
      // first check if there is a direct match
      if ( isset( $options_array[$title] ) ) {
        return $options_array[$title];
      }
      
      $options_array = self::sanitize_keys($field->options());
      
      // first check if there is a direct match with a sanitized title
      if ( isset( $options_array[$title] ) ) {
        return $options_array[$title];
      }
      
     /*
      * if a direct match doesn't find it, use a regex to find a close exact match
      * 
      * if that doesn't find a match, get a set of possible close matches and choose 
      * the closest one from that subset
      * 
      */
      
      // first try to find a full match
      if ( $match = current( preg_grep( '/^' . preg_quote( $title, '/' ) . '$/i', array_keys( $options_array ) ) ) ) {
        $value = $options_array[$match];
      } else {
        
        // get a list of the substring matches from the options
        $match_list = preg_grep( '/' . preg_quote( $title, '/' ) . '/i', array_keys( $options_array ) );
        
        // if we find a substring match, find the closest mathching title
        if ( ! empty( $match_list ) ) {
          // find the closest match
          $ranked_matches = array();
          foreach( $match_list as $match ) {
            similar_text( strtolower($title), strtolower($match), $rank );
            $ranked_matches[(string)$rank] = $match;
          }
          ksort( $ranked_matches, SORT_NUMERIC );

          $value = $options_array[ end($ranked_matches) ];
        }
      }
      
    }
    return $value;
  }
  
  /**
   * provides an options array with sanitized keys
   * 
   * @param array
   * @return array
   */
  protected static function sanitize_keys( $array )
  {
    $sanitized = array();
    foreach ( $array as $key => $value ) {
      $sanitized[strip_tags($key)] = $value;
    }
    return $sanitized;
  }

  /**
   * builds a string of attributes for inclusion in an HTML element
   *
   * @param string $filter to apply to the array
   * @return string
   */
  protected function _attributes( $filter = 'none' )
  {
    /**
     * @version 1.7.0.9
     * @filter pdb-form_element_attributes_filter
     * @param array the attributes array in name=>value format
     * @param string the name of the filter called
     */
    $attributes_array = Participants_Db::apply_filters( 'form_element_attributes_filter', $this->attributes, $filter );
    switch ( $filter ) {
      case 'none':
        break;
      case 'no validate':
        foreach ( array('required', 'maxlength', 'pattern') as $att ) {
          unset( $attributes_array[$att] );
        }
        break;
      // any more filters...add them here
    }

    return parent::_attributes( $attributes_array );
  }

  /**
   * provides a list of all defined form elements
   * 
   * @return array as $name => $title
   * 
   */
  public static function get_types()
  {
    $types = array(
        'text-line' => __( 'Text-line', 'participants-database' ),
        'text-area' => __( 'Text Area', 'participants-database' ),
        'rich-text' => __( 'Rich Text', 'participants-database' ),
        'checkbox' => __( 'Checkbox', 'participants-database' ),
        'radio' => __( 'Radio Buttons', 'participants-database' ),
        'dropdown' => __( 'Dropdown List', 'participants-database' ),
        'date' => __( 'Date Field', 'participants-database' ),
        'numeric' => __( 'Numeric', 'participants-database' ),
        'decimal' => __( 'Decimal', 'participants-database' ),
        'currency' => __( 'Currency', 'participants-database' ),
        'dropdown-other' => __( 'Dropdown/Other', 'participants-database' ),
        'multi-checkbox' => __( 'Multiselect Checkbox', 'participants-database' ),
        'multi-dropdown' => __( 'Multiselect Dropdown', 'participants-database' ),
        'select-other' => __( 'Radio Buttons/Other', 'participants-database' ),
        'multi-select-other' => __( 'Multiselect/Other', 'participants-database' ),
        'link' => __( 'Link Field', 'participants-database' ),
        'image-upload' => __( 'Image Upload Field', 'participants-database' ),
        'file-upload' => __( 'File Upload Field', 'participants-database' ),
        'hidden' => __( 'Hidden Field', 'participants-database' ),
        'password' => __( 'Password Field', 'participants-database' ),
        'captcha' => __( 'CAPTCHA', 'participants-database' ),
        'placeholder' => __( 'Placeholder', 'participants-database' ),
//         'timestamp'          => __('Timestamp', 'participants-database'),
    );
    /**
     * this gives access to the list of form element types for alteration before
     * it is set
     * @filter pdb-set_form_element_types
     * @param array of core form element types
     * @return array of all form element types
     */
    return Participants_Db::apply_filters( 'set_form_element_types', $types );
  }
  
  /**
   * tells if the string matches a defined form element type
   * 
   * @param string $form_element
   * @return bool true if the form element type is defined
   */
  public static function is_form_element( $form_element )
  {
    return array_key_exists( $form_element, self::get_types() );
  }

  /**
   *  tells if a field stores it's value as an array
   * 
   * any new form element that does this is expected to register with this list
   * 
   * @param string  $form_element the name of the form element
   * 
   * @return bool true if the element is stored as an array
   */
  public static function is_multi( $form_element )
  {
    return in_array( $form_element, Participants_Db::apply_filters( 'multi_form_elements_list', array('multi-checkbox', 'multi-select-other', 'link', 'multi-dropdown') ) );
  }

  /**
   *  tells if a field is represented as a set of values, such as a dropdown, checkbox or radio control
   * 
   * any new form element that does this is expected to register with this list
   * 
   * @param string  $form_element the name of the form element
   * 
   * @return bool true if the element is represented as a set of values
   */
  public static function is_value_set( $form_element )
  {
    return in_array( $form_element, Participants_Db::apply_filters( 'value_set_form_elements_list', array(
                'dropdown',
                'radio',
                'checkbox',
                'dropdown-other',
                'select-other',
                'multi-checkbox',
                'multi-select-other',
                'multi-dropdown',
            ) ) );
  }

  /**
   * determines if a field type is "linkable"
   * 
   * meaning it is displayed as a string that can be wrapped in an anchor tag
   * 
   * @param object $field the field object
   * @return bool true if the type is linkable
   */
  public static function field_is_linkable( $field )
  {
    $linkable = in_array( $field->form_element, array(
        'text-line',
        'image-upload',
        'file-upload',
        'dropdown',
        'checkbox',
        'radio',
        'placeholder',
        'hidden',
            )
    );
    return Participants_Db::apply_filters( 'field_is_linkable', $linkable, $field->form_element );
  }

  /**
   * returns a MYSQL datatype appropriate to the form element type
   * 
   * @param string|array $element the (string) form element type or (array) field definition array
   * @return string the name of the MySQL datatype
   */
  public static function get_datatype( $element )
  {
    $form_element = is_array( $element ) ? $element['form_element'] : $element;
    $fieldname = is_array( $element ) ? $element['name'] : '';
    /**
     * @version 1.7.0.7
     * @filter pdb-form_element_datatype
     * 
     * @param string $datatype the datatype found by the parent method
     * @param string  $form_element the name of the form element
     * @param string name of the field if defined
     * @return string $datatype 
     */
    return Participants_Db::apply_filters( 'form_element_datatype', parent::get_datatype( $form_element ), $form_element, $fieldname );
  }

}
