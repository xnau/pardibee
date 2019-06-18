<?php

/*
 * send an email using a template with placeholder tags 
 * 
 * the placeholder tags are mainly intending to be replaced with data from a PDB 
 * record, but it can also be supplied an associative array
 *
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015  xnau webdesign
 * @license    GPL2
 * @version    0.5
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
if ( !defined( 'ABSPATH' ) )
  exit;

class xnau_Template_Email {

  /**
   * @var string holds the "to" email field
   */
  protected $email_to;

  /**
   * @var string holds the "from" email field
   */
  protected $email_from;

  /**
   * @var string holds the email subject
   */
  protected $email_subject;

  /**
   * @var string holds the raw email body template
   */
  protected $email_template;

  /**
   * @var string a context identification string
   * 
   * this is used to help filter callbacks
   */
  protected $context = '';

  /**
   * @var string a prefix for filter slugs
   */
  protected $prefix = 'xnau-';

  /**
   * @var array and associative array of values for use by the template
   */
  protected $data;
  
  /**
   * @var array of attachment paths
   */
  protected $attachments = array();
  
  /**
   * @var string the last mail error message
   */
  public static $error_message = '';

  /**
   * instantiates the class instance
   * 
   * @param array $config array of values to use in the email
   *                'to'        => $email_to
   *                'from'      => $email_from
   *                'subject'   => $email_subject
   *                'template'  => $email_template
   *                'context'   => $context
   * @param array $data the data source; must be associative array with fields labeled
   */
  function __construct( $config, $data )
  {
    $this->setup_email_configuration( $config );
    $this->data = $data;
    add_action( 'wp_mail_failed', array( $this, 'get_error_message' ) );
  }

  /**
   * sends the email
   * 
   * @return bool true if successful
   */
  protected function send_email()
  {
    return $this->_mail( $this->email_to, $this->replace_tags( $this->email_subject, $this->data ), $this->replace_tags( $this->email_template, $this->data ) );
  }

  /**
   * sends a templated email
   * 
   * this function allwos for the simple sending of an email using a static function
   * 
   * @param array $config array of values to use in the email
   *                'to'        => $email_to
   *                'from'      => $email_from
   *                'subject'   => $email_subject
   *                'template'  => $email_template
   *                'context'   => $context
   *                'attachments' => $attachments
   * @param int|array $data if an integer, gets the PDB record with that ID, is 
   *                        array, uses it as the data source; must be associative 
   *                        array with fields labeled
   */
  public static function send( $config, $data )
  {
    $instance = new self( $config, $data );
    return $instance->send_email();
  }

  /**
   * sends a mesage through the WP mail handler function
   *
   * @param string $recipients comma-separated list of email addresses
   * @param string $subject    the subject of the email
   * @param string $body       the body of the email
   *
   * @return bool success
   */
  protected function _mail( $recipients, $subject, $body )
  {
    if ( PDB_DEBUG ) {
      Participants_Db::debug_log( __METHOD__ . '
      
context: '. $this->context . '
header: ' . $this->email_header() . '
to: ' . $recipients . ' 
attachments: ' . print_r(  $this->attachments,1 ) . '
subj.: ' . $subject . ' 
message:
' . $body
            );
    }

    $sent = wp_mail( $recipients, $subject, $body, $this->email_header(), $this->attachments );

    if ( false === $sent )
      error_log( __METHOD__ . ' sending failed for: ' . $recipients . ' while doing: ' . $this->context );
    return $sent;
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
    return apply_filters( $this->prefix . 'template_email_header', 'From: ' . $this->email_from . "\n" . 'Content-Type: text/html; charset="' . get_option( 'blog_charset' ) . '"' . "\n", $this->context );
  }

  /**
   * maps a sets of values to "tags"in a template, replacing the tags with the values
   * 
   * @param string $text the tag-containing template string
   * @param array  $data array of record values: $name => $value
   * 
   * @return string template with all matching tags replaced with values
   */
  protected function replace_tags( $text, array$data )
  {

    $values = $tags = array();

    foreach ($data as $name => $value) {

      $tags[] = '[' . $name . ']';

      $values[] = $value;
    }

    $placeholders = array();

    for ($i = 1; $i <= count( $tags ); $i++) {

      $placeholders[] = '%' . $i . '$s';
    }

    // replace the tags with variables
    $pattern = str_replace( $tags, $placeholders, $text );

    // replace the variables with strings
    return vsprintf( $pattern, $values );
  }

  /**
   * sets up the email parameters
   * 
   * @param array $config the config array
   * 
   * @return null
   */
  protected function setup_email_configuration( $config )
  {
    $this->email_to = apply_filters( $this->prefix . 'email_to',  $config['to'], $this->context );
    $this->email_from = apply_filters( $this->prefix . 'email_from',  $config['from'], $this->context );
    $this->email_subject = apply_filters( $this->prefix . 'email_subject',  $config['subject'], $this->context );
    $this->email_template = apply_filters( $this->prefix . 'email_template',  $config['template'], $this->context );
    if ( isset( $config['attachments'] ) ) {
      $this->attachments = apply_filters( $this->prefix . 'email_attachments',  $config['attachments'], $this->context );
    }
  }
  
  /**
   * stores the last mail error message
   * 
   * @param WP_Error $error
   */
  public function get_error_message( WP_Error $error )
  {
    self::$error_message = $error->get_error_message();
  }

}
