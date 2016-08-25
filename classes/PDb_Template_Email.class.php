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
 * @version    0.6
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
   * sends the email
   * 
   * @return bool true if successful
   */
  protected function send_email()
  {
    return $this->_mail( $this->email_to, PDb_Tag_Template::replaced_text( $this->email_subject, $this->data ), PDb_Tag_Template::replaced_rich_text( $this->email_template, $this->data ) );
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
    }

    // add the date tag
    $this->data['date'] = PDb_Date_Display::get_date( null, __METHOD__ );

    // add the time tag
    $this->data['time'] = PDb_Date_Display::get_date_with_format( null, get_option( 'time_format' ), __METHOD__ );

//    error_log(__METHOD__.' tag map: '.print_r($this->data,1));

    /**
     * @version 1.6.3
     * @filter pdb-template_email_tag_map
     */
    $this->data = Participants_Db::apply_filters( 'template_email_tag_map', $this->data, $this->context );
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
    return Participants_Db::apply_filters( 'template_email_header', Participants_Db::$email_headers . "\n" . 'Return-Path:' . $this->return_path() . "\n" . 'X-Generator: ' . Participants_Db::$plugin_title . "\n", $this->context );
  }
  
  /**
   * provides the return-path address
   * 
   * @return string
   */
  protected function return_path()
  {
    return Participants_Db::apply_filters('return_path_email_header', '<' . Participants_Db::plugin_setting( 'receipt_from_address' ) . '>' );
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
