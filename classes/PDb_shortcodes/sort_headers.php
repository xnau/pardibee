<?php

/**
 * adds-click-to-sort headers to the list display table
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2023  xnau webdesign
 * @license    GPL3
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

namespace PDb_shortcodes;

class sort_headers {

  /**
   * 
   * this is called if the header_sort attribute is set in the list shortcode
   * 
   */
  public function __construct()
  {
    add_filter( 'pdb-list_header_title', [ $this, 'add_sort_link' ], 10, 2 );
    
    add_action( 'pdb-before_include_shortcode_template', function($shortcode){
      /** @var \PDb_Shortcode $shortcode */
      if ($shortcode->module === 'list')
      {
        $this->print_icon_css();
      }
    });
  }

  /**
   * adds the sort link to the title
   *  
   * @param string $title
   * @param PDb_Form_Field_Def $field
   * @return string title
   */
  public function add_sort_link( $title, $field )
  {
    if ( !$field->is_sortable() ) {
      return $title;
    }

    parse_str( $_SERVER[ 'QUERY_STRING' ], $get_array );
    global $post;
    $base_url = get_permalink( $post->ID );

    $ascdesc = 'ASC';
    if ( isset( $get_array[ 'sortBy' ] ) && $field->name() === $get_array[ 'sortBy' ] && isset( $get_array[ 'ascdesc' ] ) ) {
      // we need to set the sort link to the opposite of the submitted sort direction
      $ascdesc = stripos( $get_array[ 'ascdesc' ], 'ASC' ) !== false ? 'DESC' : 'ASC';
    }

    $arg_array = array(
        'sortBy' => $field->name(),
        'ascdesc' => $ascdesc,
    );

    // add the search values if present in the request
    if ( $this->get_input_value( 'search_field' ) ) {
      $arg_array = array_merge( $arg_array,
              array(
                  'search_field' => $this->get_input_value( 'search_field' ) ?: 'searchfield',
                  'value' => $this->get_input_value( 'value' ) ?: '*',
              ) );
    }

    return sprintf( '<a href="%1$s">%2$s%3$s</a>', add_query_arg( $arg_array, $base_url ), $title, $this->sort_icon( $ascdesc ) );
  }

  /**
   * provides the sort icon
   * 
   * @param string $ascdesc
   * @return string icon HTML
   */
  private function sort_icon( $ascdesc )
  {
    return sprintf( '<span class="sort-arrow-%s"></span>', ( $ascdesc === 'ASC' ? 'down' : 'up' ) );
  }
  
  /**
   * prints the sort icon CSS
   * 
   * this can be overridden by user CSS
   * 
   */
  public function print_icon_css()
  {
    ?>
  <style>
    .sort-arrow-up::after {
      content: '\25B2';
    }
    .sort-arrow-down::after {
      content: '\25BC';
    }
  </style>
<?php
  }

  /**
   * supplies the requested value from the input
   * 
   * @param string $name of the value
   * @return the value or bool false if not found
   */
  private function get_input_value( $name )
  {
    $inputval = filter_input( INPUT_POST, $name, FILTER_DEFAULT, \Participants_Db::string_sanitize( FILTER_NULL_ON_FAILURE ) );

    if ( $inputval === false ) {
      $inputval = filter_input( INPUT_GET, $name, FILTER_DEFAULT, \Participants_Db::string_sanitize( FILTER_NULL_ON_FAILURE ) );
    }

    return $inputval;
  }

}
