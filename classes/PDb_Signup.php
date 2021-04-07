<?php

/**
 * prints a signup form
 * provides user feedback
 * emails a receipt and a notification
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.8
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    xnau_FormElement class, Shortcode class
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Signup extends PDb_Shortcode {

  /**
   *
   * @var bool holds the submission status: false if the form has not been submitted
   */
  var $submitted = false;

  /**
   * @var \PDb_submission\feedback object
   */
  public $feedback;

  /**
   * instantiates the signup form object
   *
   * @param array $shortcode_atts   this array supplies the display parameters for the instance
   *
   */
  public function __construct( $shortcode_atts )
  {
    // define shortcode-specific attributes to use
    $shortcode_defaults = array(
        'module' => 'signup',
        'submit_button' => Participants_Db::plugin_setting( 'signup_button_text' ),
        'edit_record_page' => Participants_Db::plugin_setting( 'registration_page' ),
    );

    /*
     * status values: normal (signup form submission) or multipage
     */
    $form_status = $this->get_form_status();

    /*
     * get the record ID from the last submission or current multiform
     */
    $this->participant_id = Participants_Db::$session->record_id();
    $shortcode_atts[ 'record_id' ] = $this->participant_id;

    /*
     * if we've opened a regular signup form while in a multipage session, treat it 
     * as a normal signup form and terminate the multipage session
     */
    if ( $shortcode_atts[ 'module' ] === 'signup' && $this->participant_id !== false && !isset( $shortcode_atts[ 'action' ] ) && Participants_Db::is_multipage_form() ) {
      $this->participant_id = false;
      $this->clear_multipage_session();
      $this->clear_captcha_session();
    } elseif ( $shortcode_atts[ 'module' ] === 'signup' && $this->participant_id === false ) {
      $this->clear_multipage_session();
    }

    /*
     * if no ID is set, no submission has been received
     */
    if ( $this->participant_id === false ) {

      // override read-only in signup and link recovery forms
      add_action( 'pdb-before_field_added_to_iterator', array( $this, 'allow_readonly_fields_in_form' ) );

      if ( filter_input( INPUT_GET, 'm', FILTER_SANITIZE_STRING ) === 'r' || $shortcode_atts[ 'module' ] == 'retrieve' ) {
        /*
         * we're proceesing a link retrieve request
         */
        $shortcode_atts[ 'module' ] = 'retrieve';
      }
      /**
       * @filter pdb-signup_module_name
       * @prarm array of signup module names
       * @return array
       */
      if ( in_array( $shortcode_atts[ 'module' ], Participants_Db::apply_filters( 'signup_module_name', array( 'signup' ) ) ) ) {
        /*
         * we're showing the signup form
         */
        $this->participant_values = Participants_Db::get_default_record();
      }
    } else {

      /*
       * if we arrive here, the form has been submitted and is complete or is a multipage 
       * form and we've come back to the signup shortcode before the form was completed: 
       * in which case we show the saved values from the record
       */
      $this->participant_values = Participants_Db::get_participant( $this->participant_id );

      if ( $this->participant_values && ($form_status === 'normal' || ( $shortcode_atts[ 'module' ] === 'thanks' && Participants_Db::is_multipage_form() ) ) ) {
        /*
         * the submission (single or multi-page) is successful, set the submitted flag
         */
        $this->submitted = true;
        $shortcode_atts[ 'module' ] = 'thanks';

        if ( !Participants_Db::is_multipage_form() ) {
          $this->set_form_status( 'signup-thanks' );
        }
      }
      $shortcode_atts[ 'id' ] = $this->participant_id;
    }

    // run the parent class initialization to set up the $shortcode_atts property
    parent::__construct( $shortcode_atts, $shortcode_defaults );

    $record_edit_page = Participants_Db::find_permalink( $this->shortcode_atts[ 'edit_record_page' ] );

    add_filter( 'pdb-record_edit_page', function() use ( $record_edit_page ) {
      return $record_edit_page;
    } );

    // set up the signup form email preferences
    $this->feedback = new \PDb_submission\feedback( $this->_email_prefs() );

    // set the action URI for the form
    $this->_set_submission_page();

    // set up the template iteration object
    $this->_setup_iteration();

    if ( $this->submitted ) {
        
      $this->feedback->participant_values = $this->participant_values;
        
      if ( $this->form_submission_is_signup() ) {
        /**
         * filter provides access to the freshly-stored record and the email and 
         * thanks message properties so user feedback can be altered.
         * 
         * @action pdb-before_signup_thanks
         * @param object feedback messages      
         * @param string form status
         * 
         */
        do_action( Participants_Db::$prefix . 'before_signup_thanks', $this->feedback, $this->get_form_status() );
        
      } else {
        
        /**
         * filter provides access to the freshly-stored record and the email and 
         * thanks message properties so user feedback can be altered.
         * 
         * @action pdb-before_update_thanks
         * @param object feedback messages
         * 
         */
        do_action( Participants_Db::$prefix . 'before_update_thanks', $this->feedback );
        
      }
      
      $this->participant_values = $this->feedback->participant_values;

      $this->_send_email();

      $this->clear_captcha_session();
    }

    // print the shortcode output
    $this->_print_from_template();

    if ( $this->submitted ) {
      $this->clear_multipage_session();
    }
  }

  /**
   * prints a signup form called by a shortcode
   *
   * this function is called statically to instantiate the Signup object,
   * which captures the processed template output and returns it for display
   *
   * @param array $params parameters passed by the shortcode
   * @return string form HTML
   */
  public static function print_form( $params )
  {

    self::$instance = new PDb_Signup( $params );

    return self::$instance->output;
  }

  /**
   * includes the shortcode template
   */
  protected function _include_template()
  {
    include $this->template;
  }

  /**
   * sets up the hidden fields array
   * 
   * in this class, this simply adds all defined hidden fields
   * 
   * @return null
   */
  protected function _setup_hidden_fields()
  {
    foreach ( Participants_Db::field_defs() as $field ) {
      /* @var $field PDb_Form_Field_Def */
      if ( $field->is_hidden_field() && $field->signup ) {
        $field_item = new PDb_Field_Item( $field );
        $this->_set_field_value( $field_item );
        $this->hidden_fields[ $field_item->name() ] = $field_item->value();
      }
    }
  }

  /**
   * sets up the signup form email preferences
   */
  protected function _email_prefs()
  {
    return array(
        "send_notification" => Participants_Db::plugin_setting( 'send_signup_notify_email' ),
        "notify_recipients" => Participants_Db::plugin_setting( 'email_signup_notify_addresses' ),
        "notify_subject" => Participants_Db::plugin_setting( 'email_signup_notify_subject' ),
        "notify_body" => Participants_Db::plugin_setting( 'email_signup_notify_body' ),
        "receipt_subject" => Participants_Db::plugin_setting( 'signup_receipt_email_subject' ),
        "receipt_body" => Participants_Db::plugin_setting( 'signup_receipt_email_body' ),
        "email_header" => Participants_Db::$email_headers,
        "recipient" => $this->recipient_email(),
        "thanks_message" => $this->_thanks_message(),
    );
  }

  /**
   * tells if the signup receipt email is enabled
   * 
   * @filter pdb-send_signup_receipt
   * 
   * @return bool true if enabled
   */
  public function send_receipt()
  {
    return (bool) Participants_Db::apply_filters( 'send_signup_receipt', Participants_Db::plugin_setting_is_true( 'send_signup_receipt_email' ) );
  }

  /**
   * sets the form submission page
   * 
   * if the "action" attribute is not set in the shortcode, use the "thanks page" 
   * setting if set
   */
  protected function _set_submission_page()
  {
    $form_status = $this->get_form_status();
    $this->submission_page = false;
    /*
     * check for the "action" attribute
     */
    if ( !empty( $this->shortcode_atts[ 'action' ] ) ) {
      $this->submission_page = Participants_Db::find_permalink( $this->shortcode_atts[ 'action' ] );
      if ( $this->submission_page !== false ) {
        $form_status = 'multipage-signup';
      }
    }
    /*
     * action attribute is not set in the shortcode, use the global setting
     */
    if ( !$this->submission_page ) {
      if ( Participants_Db::plugin_setting( 'signup_thanks_page', 'none' ) != 'none' ) {
        $this->submission_page = Participants_Db::get_permalink( Participants_Db::plugin_setting( 'signup_thanks_page' ) );
      }
    }
    /*
     * it's not set in the global settings, or is set to "same page", use the current 
     * page as the submission page
     */
    if ( $this->submission_page === false ) {
      // the signup thanks page is not set up, so we submit to the page the form is on
      $this->submission_page = $_SERVER[ 'REQUEST_URI' ];
    }
    $this->set_form_status( $form_status );
  }

  /**
   * prints a signup form top
   * 
   * @param array array of hidden fields supplied in the template
   */
  public function print_form_head( $hidden = '' )
  {

    echo $this->_print_form_head( $hidden );
  }

  /**
   * prints the submit button
   *
   * @param string $class a classname for the submit button, defaults to 'button-primary'
   * @param string $button_value submit button text
   * 
   */
  public function print_submit_button( $class = 'button-primary', $button_value = false )
  {

    $button_value = $button_value ? $button_value : $this->shortcode_atts[ 'submit_button' ];

    PDb_FormElement::print_element( array(
        'type' => 'submit',
        'value' => $button_value,
        'name' => 'submit_button',
        'class' => $class . ' pdb-submit',
        'module' => $this->module,
    ) );
  }

  /**
   * prints a private link retrieval link
   * 
   * @param string $linktext
   */
  public function print_retrieve_link( $linktext = '', $open_tag = '<span class="pdb-retrieve-link">', $close_tag = '</span>' )
  {
    if ( Participants_Db::plugin_setting_is_true( 'show_retrieve_link' ) ) {

      $linktext = empty( $linktext ) ? Participants_Db::plugin_setting( 'retrieve_link_text' ) : $linktext;

      $retrieve_link = Participants_Db::plugin_setting( 'link_retrieval_page' ) !== 'none' ? Participants_Db::get_permalink( Participants_Db::plugin_setting( 'link_retrieval_page' ) ) : $_SERVER[ 'REQUEST_URI' ];
      echo $open_tag . '<a href="' . Participants_Db::add_uri_conjunction( $retrieve_link ) . 'm=r">' . $linktext . '</a>' . $close_tag;
    }
  }

  /**
   * prints a thank you note to the screen after submitting a signup
   * 
   * @param string $template an optional override template to use
   * @return string
   */
  protected function get_thanks_message( $template = '' )
  {
    $data = $this->participant_values;

    $thanks_message = $this->feedback->thanks_message;

    // add the "record_link" tag
    if ( isset( $data[ 'private_id' ] ) ) {
      $data[ 'record_link' ] = Participants_Db::get_record_link( $data[ 'private_id' ] );
    }

    if ( !empty( $this->participant_values ) ) {
      $this->output = PDb_Tag_Template::replaced_rich_text( $thanks_message, $data );
      unset( $_POST );
    } else {
      $this->output = '';
    }

    return $this->output;
  }
  
  /**
   * provides the thanks message from the settings according to the form type
   * @return string
   */
  protected function _thanks_message()
  {
    $thanks_message = '';
    
    if ( $this->form_submission_is_signup() ) {
      $thanks_message = $this->shortcode_thanks_message( 'signup_thanks' );
    } else {
      $thanks_message = $this->shortcode_thanks_message( 'record_updated_message' );
    }
    
    return $thanks_message;
  }
  
  /**
   * tells if the current form submission is a signup
   * 
   * @return bool
   */
  public function form_submission_is_signup()
  {
    return strpos( $this->get_form_status(), 'signup' ) !== false;
  }

  /**
   * provides the shortcode thanks message text
   * 
   * @param string $setting name of the setting to check for a message
   * @return string message
   */
  protected function shortcode_thanks_message( $setting )
  {
    return isset( $this->shortcode_atts[ 'content' ] ) && !empty( $this->shortcode_atts[ 'content' ] ) ? $this->shortcode_atts[ 'content' ] : Participants_Db::plugin_setting( $setting, '' );
  }

  /**
   * provides the signup receipt recipient email address
   * 
   * @return string
   */
  private function recipient_email()
  {
    return isset( $this->participant_values[ Participants_Db::plugin_setting( 'primary_email_address_field' ) ] ) ? $this->participant_values[ Participants_Db::plugin_setting( 'primary_email_address_field' ) ] : '';
  }

  /**
   * sends the notification and receipt emails
   * 
   * this handles both signups and updates using multi-page forms
   *
   */
  private function _send_email()
  {
    switch ( $this->get_form_status() ) {
      
      case 'multipage-update':
        // this is a record form with a separate thanks page
        if ( Participants_Db::plugin_setting_is_true( 'send_record_update_notify_email' ) ) {
          $this->_do_update_notify();
        }
        break;
        
      case 'multipage-signup':
        // this is a multipage signup form
        if ( $this->feedback->send_notification ) {
          $this->_do_notify();
        }
        if ( $this->send_receipt() ) {
          $this->_do_receipt();
        }
        break;
        
      case 'normal':
      case 'signup-thanks':
      default:
        if ( filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING ) === 'update' ) {
          // this is a record form using a thanks page
          if ( $this->feedback->send_notification ) {
            $this->_do_update_notify();
          }
        } else {
          // this is a normal signup form
          if ( $this->feedback->send_notification ) {
            $this->_do_notify();
          }
          if ( $this->send_receipt() ) {
            $this->_do_receipt();
          }
        }
        break;
    }
  }

  /**
   * sends a user receipt email
   */
  private function _do_receipt()
  {

    if ( filter_var( $this->feedback->recipient, FILTER_VALIDATE_EMAIL ) === false ) {
      Participants_Db::debug_log( Participants_Db::$plugin_title . ': no valid email address was found for the user receipt email, mail could not be sent.' );
      return NULL;
    }

    /**
     * filter
     * 
     * pdb-receipt_email_template 
     * pdb-receipt_email_subject
     * 
     * @param string email template
     * @param array of current record values
     * 
     * @return string template
     */
    PDb_Template_Email::send( array(
        'to' => $this->feedback->recipient,
        'subject' => Participants_Db::apply_filters( 'receipt_email_subject', $this->feedback->receipt_subject, $this->participant_values ),
        'template' => Participants_Db::apply_filters( 'receipt_email_template', $this->feedback->receipt_body, $this->participant_values ),
        'context' => __METHOD__,
            ), $this->participant_values );
  }

  /**
   * sends a new signup notification email to the admin
   */
  private function _do_notify()
  {

    PDb_Template_Email::send( array(
        'to' => $this->feedback->notify_recipients,
        'subject' => $this->feedback->notify_subject,
        'template' => $this->feedback->notify_body,
        'context' => __METHOD__,
            ), $this->feedback->participant_values );
  }

  /**
   * sends an update notification email to the admin
   */
  private function _do_update_notify()
  {
    PDb_Template_Email::send( array(
        'to' => $this->feedback->notify_recipients,
        'subject' => Participants_Db::plugin_setting( 'record_update_email_subject' ),
        'template' => Participants_Db::plugin_setting( 'record_update_email_body' ),
        'context' => __METHOD__,
            ), $this->feedback->participant_values );
  }

  /**
   * set the PHPMailer AltBody property with the text body of the email
   * 
   * @todo not in use #2506
   *
   * @param object $phpmailer an object of type PHPMailer
   * @return null
   */
  public function set_alt_body( &$phpmailer )
  {
    if ( is_object( $phpmailer ) )
      $phpmailer->AltBody = $this->_make_text_body( $this->current_body );
  }

  /**
   * strips the HTML out of an HTML email message body to provide the text body
   *
   * this is a fairly crude conversion here. I should include some kind of library
   * to do this properly.
   *
   * @param string $HTML the HTML body of the email
   * @return string
   */
  private function _make_text_body( $HTML )
  {

    return strip_tags( preg_replace( '#(</(p|h1|h2|h3|h4|h5|h6|div|tr|li) *>)#i', "\r", $HTML ) );
  }

  /**
   * changes the readonly status of fields used in the signup form
   * 
   * all fields are writable in the signup form
   * 
   * @param PDb_Field_Item $field
   */
  public function allow_readonly_fields_in_form( $field )
  {
    $field->set_readonly( false );
  }

  /**
   * clears the multipage form session values
   */
  public function clear_captcha_session()
  {
    foreach ( array( 'captcha_vars', 'captcha_result' ) as $value ) {
      Participants_Db::$session->clear( $value );
    }
  }

}