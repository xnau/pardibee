<?php

/**
 * initializes all the additional fields in the namespace
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

defined( 'ABSPATH' ) || exit;

class initialize {

  function __construct()
  {
    new heading();
    new media_embed();
    new string_combine();
    new shortcode();
    new placeholder();
    new numeric_calc();
    new date_calc();
    new last_update_user();
  }

}
