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
 * @version    0.4
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
if (!defined('ABSPATH')) exit;
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
   * @param int|array $data if an integer, gets the PDB record with that ID, is 
   *                        array, uses it as the data source; must be associative 
   *                        array with fields labeled
   */
  function __construct($config, $data)
  {
    $this->prefix = Participants_Db::$prefix;
    parent::__construct($config, $data);
  }
  /**
   * maps a sets of values to "tags"in a template, replacing the tags with the values
   * 
   * @param string $text the tag-containing template string
   * @param array  $data array of record values: $name => $value
   * 
   * @return string template with all matching tags replaced with values
   */
  private function replace_tags($text, array$data) {

    $values = $tags = array();

    foreach ($data as $name => $value) {

      $tags[] = '[' . $name . ']';

      $values[] = $value;
    }

    // add the "record_link" tag
    if (isset($data['private_id'])) {
      $tags[] = '[record_link]';
      $values[] = Participants_Db::get_record_link($data['private_id']);
    }

    // add the date tag
    $tags[] = '[date]';
    $values[] = Participants_Db::format_date();

    // add the time tag
    $tags[] = '[time]';
    $values[] =  Participants_Db::format_date(false, false, get_option('time_format')); 

    $placeholders = array();

    for ($i = 1; $i <= count($tags); $i++) {

      $placeholders[] = '%' . $i . '$s';
    }

    // replace the tags with variables
    $pattern = str_replace($tags, $placeholders, $text);

    // replace the variables with strings
    return vsprintf($pattern, $values);
    
  }
  /**
   * sets up the data source
   * 
   * @param array|int $data
   * 
   * @return array
   */
  private function setup_data($data = false) {
    if (is_array($data)) {
      return $data;
    }
    if (is_numeric($data) && $record = Participants_Db::get_participant($data)) {
      return $record;
    }
    return array();
  }
}