<?php

/**
 * PDb subclass for printing and managing form elements
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.7
 * @link       http://wordpress.org/extend/plugins/participants-database/
 *
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_FormElement extends xnau_FormElement {

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
   * @param object $field a Field_Item object or similar
   * @param bool   $html  if true, retuns the value wrapped in HTML, false returns 
   *                      the formatted value alone
   * @return string the object's current value, formatted
   */
  public static function get_field_value_display( $field, $html = true )
  {

    if ( !is_a( $field, 'PDb_Field_Item' ) ) {
      // now we can use our field classs methods
      $field = new PDb_Field_Item( $field );
    }

    $return = '';

    /**
     * filter: pdb-before_display_form_element
     * 
     * @since 1.6
     * 
     * @param string $return the value display
     * @param object $field the field object
     * 
     * formerly, this was set as "pdb-before_display_field" and included a more limited set of arguments
     */
    if ( has_filter( Participants_Db::$prefix . 'before_display_form_element' ) ) {
      $return = Participants_Db::apply_filters( 'before_display_form_element', $return, $field );
    } elseif ( has_filter( Participants_Db::$prefix . 'before_display_field' ) ) {
      // provided for backward-compatibility
      $return = Participants_Db::apply_filters( 'before_display_field', $return, $field->value, $field->form_element );
    }

    if ( empty( $return ) ) {

      switch ( $field->form_element ) :

        case 'image-upload' :
          switch ( $field->module ) {
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
              'filename' => $field->value,
              'link' => $field->link,
              'module' => $field->module,
              'mode' => $display_mode,
                  ) );

          if ( $html ) {

            $image->set_image_wrap();

            $return = $image->get_image_html();
          } elseif ( $image->file_exists ) {
            $return = $image->get_image_file();
          } else {
            $return = $field->value;
          }

          break;

        case 'file-upload' :

          if ( $html && $field->value !== $field->default ) {

            $return = '';
            if ( $field->module == 'signup' ) {
              $field->link = false;
              $return = $field->value;
            } elseif ( !empty( $field->value ) ) {
              $field->link = Participants_Db::files_uri() . $field->value;
              if ( $field->default ) {
                $field->value = $field->default;
              }
              $return = self::make_link( $field );
            }
            break;
          } else {
            /*
             * no file is defined, show nothing
             */
            $return = ''; // $field->value;
            break;
          }

        case 'date' :
          if ( !self::is_empty( $field->value ) ) {
            $return = PDb_Date_Display::get_date( $field->value, __METHOD__ . ' date field' );
          }
          $return = $return ? $return : '';
          break;

        case 'timestamp' :

          if ( !self::is_empty( $field->value ) ) {
            $return = Participants_Db::plugin_setting_is_true( 'show_time' ) ? PDb_Date_Display::get_date_time( $field->value, __METHOD__ . ' timestamp field with time' ) : PDb_Date_Display::get_date( $field->value, __METHOD__ . ' timestamp field' );
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
          $return = self::array_display( $field );
          break;

        case 'link' :

          $linkdata = maybe_unserialize( $field->value );

          if ( !is_array( $linkdata ) ) {

            $return = '';
            break;
          }

          if ( empty( $linkdata[1] ) ) {
            $linkdata[1] = preg_replace( '#(https?://)#', '', $linkdata[0] );
          }

          if ( $html )
            $return = vsprintf( ( empty( $linkdata[0] ) ? '%1$s%2$s' : '<a href="%1$s">%2$s</a>' ), $linkdata );
          else
            $return = $linkdata[0];
          break;

        case 'text-line' :

          if ( $html ) {

            $field->value = self::get_value_title( $field->value, $field->name );
            $return = self::make_link( $field );
            break;
          } else {

            $return = $field->value;
            break;
          }

        case 'text-area':
        case 'textarea':

          $pattern = $html ? '<span ' . self::class_attribute( 'textarea' ) . '>%s</span>' : '%s';
          $return = sprintf( $pattern, $field->value );
          break;

        case 'rich-text':

          if ( $html ) {
            $return = sprintf( '<span ' . self::class_attribute( 'textarea richtext' ) . '>%s</span>', Participants_Db::process_rich_text( $field->value, 'rich-text field' ) );
          } else {
            $return = strip_tags( $field->value );
          }
          break;

        case 'dropdown':
        case 'radio':
        case 'checkbox':
        case 'dropdown-other':
        case 'select-other':

          $field->value = self::array_display( $field );

          if ( $html ) {
            $return = sprintf( '<span %s>%s</span>', self::class_attribute( $field->form_element ), self::make_link( $field ) );
          } else {
            $return = $field->value;
          }
          break;

        case 'placeholder':

          $field->value = $field->default;
          $return = $html ? self::make_link( $field ) : $field->value;
          break;

        case 'password':
          // password hashes are never shown
          $return = '';
          break;

        case 'hidden':

          if ( Participants_Db::is_dynamic_value( $field->default ) && $field->value === $field->default ) {
            // this is to prevent the dynamic value key from getting printed
            $field->value = '';
          } elseif ( !Participants_Db::is_dynamic_value( $field->default ) && strlen( $field->value ) === 0 ) {
            // show the default value if it's not a dynamic field value and there is no set value
            $field->value = $field->default;
          }
        // don't break here so we can assign the return value
        default :

          $return = $html ? self::make_link( $field ) : $field->value;

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
    $null_select = (isset( $this->options['null_select'] )) ? $this->options['null_select'] : ($type == 'checkbox' ? true : false);

    if ( $null_select !== false ) {
      $id = $this->element_id();
      $this->attributes['id'] = $id . '-default';
      $this->_addline( $this->_input_tag( 'hidden', (is_string( $null_select ) ? $null_select : '' ), false ), 1 );
      $this->attributes['id'] = $id;
    }
    unset( $this->options['null_select'] );

    $this->_addline( '<div class="' . $type . '-group" >' );

    $optgroup = false;

    foreach ( $this->_make_assoc( $this->options ) as $option_key => $option_value ) {

      $option_key = Participants_Db::apply_filters( 'translate_string', stripslashes( $option_key ) );

      if ( ($option_value === false or $option_value === 'false' or $option_value === 'optgroup') and ! empty( $option_key ) ) {
        if ( $optgroup ) {
          $this->_addline( '</fieldset>' );
        }
        $id = $this->legal_name( $this->name . '-' . ($option_value === '' ? '_' : trim( strtolower( $option_key ) )) );
        $this->_addline( '<fieldset class="' . $type . '-subgroup ' . $this->name . '-subgroup" id="' . $id . '"><legend>' . $option_key . '</legend>' );
        $optgroup = true;
      } else {
        $id = $this->element_id();
        $this->attributes['id'] = $this->legal_name( $this->prefix . $this->name . '-' . ($option_value === '' ? '_' : trim( strtolower( $option_value ) )) );
        $this->_addline( '<label ' . $this->_class() . ' for="' . $this->attributes['id'] . '">' );
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
      $this->_addline( '<label ' . $this->_class() . ' for="' . $this->attributes['id'] . '">' );
      $this->_addline( sprintf( '<input type="%s" name="%s"  value="%s" %s %s />', $type, $type === 'radio' ? $this->name : 'pdb-otherselector', $otherlabel, $this->_set_selected( $this->options, $value, 'checked', $value === '' ), $this->_attributes() . $this->_class( 'otherselect' )
              ), 1 );
      $this->attributes['id'] = $id;
      $this->_addline( $otherlabel . ':' );
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
   * @param object $field the field object
   * 
   * @return string the array presented as a string
   */
  static function array_display( $field )
  {
    $multivalues = maybe_unserialize( $field->value );
    if ( !is_array( $multivalues ) )
      return self::get_value_title( $field->value, $field->name );
    // remove empty elements and convert to string for display
    $multivalues = array_filter( (array) $multivalues, array(__CLASS__, 'is_displayable') );

    $titles = array();
    foreach ( $multivalues as $value ) {
      $titles[] = self::get_value_title( $value, $field->name );
    }
    return implode( Participants_Db::apply_filters( 'stringify_array_glue', ', ' ), $titles );
  }

  /*   * *********************** 
   * ELEMENT CONSTRUCTORS
   */

  /**
   * builds a rich-text editor (textarea) element
   */
  protected function _rich_text_field()
  {

    if ( !is_admin() and ! Participants_Db::$plugin_options['rich_text_editor'] )
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
   * builds a file upload element
   * 
   * @param string $type the upload type: file or image
   */
  protected function _upload( $type )
  {

    $this->_addline( '<div class="' . $this->prefix . 'upload">' );
    // if a file is already defined, show it
    if ( !empty( $this->value ) ) {

      $this->_addline( self::get_field_value_display( $this ) );
    }

    // add the MAX_FILE_SIZE field
    // this is really just for guidance, not a valid safeguard; this must be checked on submission
    if ( isset( $this->options['max_file_size'] ) )
      $max_size = $this->options['max_file_size'];
    else
      $max_size = ( ini_get( 'post_max_size' ) / 2 ) * 1048576; // half it to give a cushion

    $this->_addline( $this->print_hidden_fields( array('MAX_FILE_SIZE' => $max_size, $this->name => $this->value) ) );

    if ( !isset( $this->attributes['readonly'] ) ) {

      $this->_addline( $this->_input_tag( 'file' ) );

      // add the delete checkbox if there is a file defined
      if ( !empty( $this->value ) )
        $this->_addline( '<span class="file-delete" ><label><input type="checkbox" value="delete" name="' . $this->name . '-deletefile" ' . $this->_attributes('no validate') . '>' . __( 'delete', 'participants-database' ) . '</label></span>' );
    }

    $this->_addline( '</div>' );
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
     * this is to add a blank mull select option if there is no default, no defined 
     * null select and no set field value
     */
    if ( self::is_empty( $default ) && !isset( $this->options['null_select'] ) && self::is_empty( $this->value ) ) {
      $this->options['null_select'] = '';
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
   * @param object $field the field object
   * @param string $linktext the clickable text (optional)
   * @param string $template the format of the link (optional)
   * @param array  $get an array of name=>value pairs to include in the get string
   *
   * @return string HTML or HTML-escaped string (if it's not a link)
   */
  public static function make_link( $field, $template = false, $get = false )
  {

    /**
     * links may only be placed on string values
     */
    if ( is_array( $field->value ) )
      return $field->value;

    // clean up the provided string
    $URI = str_replace( 'mailto:', '', trim( strip_tags( $field->value ) ) );

    if ( isset( $field->link ) && !empty( $field->link ) ) {
      /*
       * the field is a single record link or other field with the link property 
       * set, which becomes our href
       */
      $URI = $field->link;
      $linktext = $field->value;
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
        $linktext = $URI;
        $URI = 'mailto:' . $URI;
      }
    } elseif ( filter_var( $URI, FILTER_VALIDATE_EMAIL ) !== false && Participants_Db::plugin_setting_is_true( 'email_protect' ) && !Participants_Db::$sending_email ) {

      /**
       * @todo obfuscate other email links
       * if the email address is wrapped in a link, we should obfuscate it
       */
      return $URI;
    } else {
      return $field->value; // if it is neither URL nor email address and we're not formatting it as html
    }

    // default template for links
    $linktemplate = $template === false ? '<a href="%1$s" >%2$s</a>' : $template;

    $linktext = empty( $linktext ) ? str_replace( array('http://', 'https://'), '', $URI ) : $linktext;

    //construct the link
    return sprintf( $linktemplate, $URI, esc_html( $linktext ) );
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
    $options_array = maybe_unserialize( Participants_Db::$fields[$fieldname]->values );
    $return = array();
    if ( is_array( $options_array ) ) {
      $i = 0;
      foreach ( $options_array as $index => $option_value ) {
        if ( !is_string( $index ) or $index == 'other' ) {
          // we use the stored value
          $return[$option_value] = $option_value;
        } elseif ( $option_value == $values[$i] ) {
          // grab the option title
          $return[$option_value] = $index;
        }
        $i++;
      }
    }
    return $return;
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
   * @global object $wpdb
   * @param string $value
   * @param string $fieldname
   * @return string the title matching the value
   */
  public static function get_value_title( $value, $fieldname )
  {
    if ( isset( Participants_Db::$fields[$fieldname] ) ) {
      $options_array = maybe_unserialize( Participants_Db::$fields[$fieldname]->values );
      if ( is_array( $options_array ) ) {
        foreach ( $options_array as $option_title => $option_value ) {
          if ( !is_string( $option_title ) or $option_title === 'other' ) {
            // we use the stored value
          } elseif ( $option_value === $value ) {
            // grab the option title
            return Participants_Db::apply_filters( 'translate_string', stripslashes( $option_title ) );
          }
        }
      }
    }
    return $value;
  }

  /**
   * gets the value that corresponds to a value title
   * 
   * @version 1.6.2.6 checks for the "title" in the values so that a value doesn't 
   *                  get treated as though it were a title
   * 
   * @param string $title the title of the value
   * @param string $fieldname the name of the field
   * @return string the value that matches the title given
   */
  public static function get_title_value( $title, $fieldname )
  {
    $value = $title;
    if ( isset( Participants_Db::$fields[$fieldname] ) ) {
      $options_array = maybe_unserialize( Participants_Db::$fields[$fieldname]->values );
      if ( is_array( $options_array ) && array_search( $title, $options_array ) === false ) {
        if ( isset( $options_array[$title] ) ) {
          $value = $options_array[$title];
        } else {
          /*
           * we still haven't located the corresponding value, maybe we're looking for 
           * a match within the title
           * 
           * this is necessary when titles are tagged with translations: the search 
           * can take place in multiple languages, and a match will still happen
           * 
           * we're expecting a title with translations to look like this:
           * [en:]English[de:]Deutsch[es:]Espanol[:]
           * 
           * we use a regex to pick out the titles and attempt a match
           * 
           */
          /**
           * @version 1.6.2.6
           * 
           * added filter: pdb-value_title_match_pattern
           */
          $title_pattern = Participants_Db::apply_filters( 'value_title_match_pattern', '/.*?\]([^[]+?)\[[^]]*/' );
          foreach ( $options_array as $key => $option ) {
            preg_match_all( $title_pattern, $key, $matches );
            if ( is_array( $matches[1] ) && in_array( $title, $matches[1] ) ) {
              $value = $option;
              break;
            }
          }
        }
      }
    }
    return $value;
  }

  /**
   * tests for a displayble value
   * 
   * this is used as a callback for a array_filter function
   * 
   * @param string|int $string the test subject
   * @return bool true if is non-empty string or integer
   */
  public static function is_displayable( $string )
  {
    return strlen( $string ) > 0;
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
    $attributes_array = Participants_Db::apply_filters('form_element_attributes_filter', $this->attributes, $filter );
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

  /*
   * static function for assembling the types array
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
    /*
     * this gives access to the list of form element types for alteration before
     * it is set
     */
    return Participants_Db::apply_filters( 'set_form_element_types', $types );
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
    /**
     * @version 1.7.0.7
     * @filter pdb-form_element_datatype
     * 
     * @param string $datatype the datatype found by the parent method
     * @param string  $form_element the name of the form element
     * @return string $datatype 
     */
    return Participants_Db::apply_filters( 'form_element_datatype', parent::get_datatype( $element ), is_array( $element ) ? $element['form_element'] : $element  );
  }

}
