<?php

/**
 * initializes all the custom fields in the namespace
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2021  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_fields;

class initialize {

  function __construct()
  {
    new heading();
    new media_embed();
    new string_combine();
    new shortcode();
    new placeholder();
  }

}
