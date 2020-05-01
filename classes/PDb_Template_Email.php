<?php

/*
 * send an email using a template with placeholder tags 
 * 
 * the placeholder tags are mainly intending to be replaced with data from a PDB 
 * record, but it can also be supplied an associative array
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015  xnau webdesign
 * @license    GPL2
 * @version    0.8
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
if ( !defined( 'ABSPATH' ) )
  exit;

class PDb_Template_Email extends xnau_Template_Email {

  /**
   * sets up the class
   * 
   * @param array $config array of values to use in the email
   *                'to' => $email_to
   *                'from' => $email_from
   *                'subject' => $email_subject
   *                        'template'  => $email_template
   *                        'context'   => $context
   * @param int|array $data if an integer, gets the PDB record with that ID, if 
   *                        array, uses it as the data source; must be associative 
   *                        array with fields labeled
   */
  function __construct( $config, $data )
  {
    $this->prefix = Participants_Db::$prefix;
    $this->context = isset( $config['context'] ) ? $config['context'] : '';
    if ( !isset( $config['from'] ) || empty( $config['from'] ) ) {
      $config['from'] = self::email_from_name();
    }
    add_action( 'phpmailer_init', array($this, 'set_return_path') );
    
    add_filter('pdb-tag_template_field_display_value', array( $this, 'clean_display_values' ), 10, 2 );
    
    $this->setup_data( $data );
    parent::__construct( $config, $this->data );
    Participants_Db::$sending_email = true;
  }

  /**
   * sends a templated email
   * 
   * this function allows for the simple sending of an email using a static function
   * 
   * @param array $config array of values to use in the email
   *                'to'        => $email_to
   *                'from'      => $email_from
   *                'subject'   => $email_subject
   *                'template'  => $email_template
   *                'context'   => $context
   *                'attachments' => (array) $attachments
   * 
   * @param int|array $data if an integer, gets the PDB record with that ID, is 
   *                        array, uses it as the data source; must be associative 
   *                        array with fields labeled
   * 
   * @return  bool  true if the operation was successful
   */
  public static function send( $config, $data )
  {
    $instance = new self( $config, $data );
    return $instance->send_email();
  }

  /**
   * sends the email
   * 
   * @return bool true if successful
   */
  protected function send_email()
  {
    add_filter( 'pdb-tag_template_module', array( __CLASS__, 'module_name' ) );
    
    $body = $this->html_format() ? PDb_Tag_Template::replaced_rich_text( $this->email_template, $this->data ) : PDb_Tag_Template::replaced_text_raw( $this->email_template, $this->data );
      
    $success = $this->_mail( $this->email_to, PDb_Tag_Template::replaced_text_raw( $this->email_subject, $this->data ), $body );
    
    remove_filter('pdb-tag_template_field_display_value', array( $this, 'clean_display_values' ) );
    
    return $success;
  }
  
  /**
   * provides the module name
   * 
   * @param string $module
   * @return string
   */
  public static function module_name()
  {
    return 'email-template';
  }
  
  /**
   * determines if the email should be html formatted
   * 
   * @return bool true if the email body should be html formatted
   */
  protected function html_format()
  {
    /**
     * @filter pdb-html_format_email_body
     * @param bool current html_email setting
     * @param PDb_Template_Email current instance
     * @return bool
     */
    return Participants_Db::apply_filters('html_format_email_body', Participants_Db::plugin_setting_is_true( 'html_email' ), $this );
  }

  /**
   * adds pdb email tag data fields
   */
  private function add_email_data()
  {

    // add the "record_link" tag
    if ( isset( $this->data['private_id'] ) ) {
      $this->data['record_link'] = Participants_Db::get_record_link( $this->data['private_id'] );
    }

    // add the admin record link tag
    if ( isset( $this->data['id'] ) && is_numeric( $this->data['id'] ) ) {
      // add the admin record link tag
      $this->data['admin_record_link'] = Participants_Db::get_admin_record_link( $this->data['id'] );

      // add the single record link tag
      $this->data['single_record_link'] = Participants_Db::single_record_url( $this->data['id'] );
    }

    // add the date tag
    $this->data['date'] = PDb_Date_Display::get_date( null, __METHOD__ );

    // add the time tag
    $this->data['time'] = PDb_Date_Display::get_date_with_format( null, get_option( 'time_format' ), __METHOD__ );

    /**
     * @version 1.6.3
     * @filter pdb-template_email_tag_map
     * @param array as $tag => value
     * @return array
     */
    $this->data = Participants_Db::apply_filters( 'template_email_tag_map', $this->data, $this->context );
  }

  /**
   * sets the return path to match the "from" header
   * 
   * @param object $phpmailer
   */
  public function set_return_path( $phpmailer )
  {
    if ( Participants_Db::apply_filters( 'set_return_path_to_sender', true ) ) {
      $phpmailer->Sender = Participants_Db::apply_filters( 'return_path_email_header', $phpmailer->From );
    }
  }

  /**
   * supplies an email header
   * 
   * @filter pdb-template_email_header
   * 
   * @return string
   */
  protected function email_header()
  {
    /*
     * the main script has already built an email header that consists of the From 
     * and the Content-Type lines
     */
    return Participants_Db::apply_filters( 'template_email_header', $this->base_header() . 'X-Generator: ' . Participants_Db::$plugin_title . "\n", $this->context );
  }

  /**
   * supplies the base header string
   * 
   * @return string email header
   */
  protected function base_header()
  {
    $base_header = Participants_Db::$email_headers;
    if ( isset( $this->email_from ) && strpos( $this->email_from, '<>' ) === false ) {
      $base_header = preg_replace( '/^From: .+$/m', 'From: ' . $this->email_from, $base_header );
    }
    return $base_header;
  }

  /**
   * provides an email "from" string
   * 
   * @filter pdb-email_from_name
   * 
   * @param string $context identifies the context
   * @return string
   */
  public static function email_from_name( $context = '' )
  {
    return Participants_Db::apply_filters( 'email_from_name', Participants_Db::plugin_setting( 'receipt_from_name' ) . " <" . Participants_Db::plugin_setting( 'receipt_from_address' ) . ">", $context );
  }

  /**
   * clear out unneeded HTML from the display values
   * 
   * certain form elements should be inserted into an email as bare values, we do 
   * that by walking through the data array and stripping out the HTML on such fields
   * 
   * called on the pdb-tag_template_field_display_value filter
   * 
   * @param string $display_value
   * @param PDb_Field_Item $field
   * @return string value
   */
  public function clean_display_values( $display_value, $field )
  {
    if ( in_array( $field->form_element(), $this->striplist() ) ) {
      $display_value = strip_tags( $display_value );
    }
    return $display_value;
  }

  /**
   * defines a list of form elements that should have HTML stripped out
   * 
   * @return array
   */
  protected function striplist()
  {
    /**
     * @filter pdb-email_tag_form_element_strip_html
     * @param array of form element names that should have tags stripped out in an email template
     * @return array
     */
    return Participants_Db::apply_filters( 'email_tag_form_element_strip_html', array('text-line', 'checkbox', 'dropdown', 'radio', 'dropdown-other', 'multi-checkbox', 'multi-dropdown', 'select-other', 'multi-select-other', 'hidden', 'password') );
  }

  /**
   * sets up the data source
   * 
   * @param array|int $data
   * 
   * @return array
   */
  protected function setup_data( $data = false )
  {
    if ( is_array( $data ) ) {
      $this->data = $data;
    } elseif ( is_numeric( $data ) && $record = Participants_Db::get_participant( $data ) ) {
      $this->data = $record;
    } else {
      $this->data = array();
    }
    $this->add_email_data();
  }

}
