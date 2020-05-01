<?php

/**
 * class for handling the listing of participant records when called by the [pdb_list] shortcode
 *
 * static class for managing a set of modules which together out put a listing of 
 * records in various configurations
 *
 * the general plan is that this class's initialization method is called by the
 * shortcode [pdb_list] which will initialize the class and pass in the parameters
 * (if any) to print the list to the website.
 *
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 - 2015 xnau webdesign
 * @license    GPL2
 * @version    1.15
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_List extends PDb_Shortcode {

  /**
   *
   * @var PDb_List_Query
   */
  private $list_query;

  /**
   *
   * @var array translations strings for buttons
   */
  public $i18n;

  /**
   *
   * @var int holds the number of list items to show per page
   */
  public $page_list_limit;

  /**
   *
   * @var string the name of the list page variable
   */
  public $list_page;

  /**
   *
   * @var string name of the list anchor element
   */
  public $list_anchor;

  /**
   *
   * @var string holds the url of the registrations page
   */
  public $registration_page_url;

  /**
   *
   * @var string holds the url to the single record page
   */
  public $single_record_page = false;

  /**
   *
   * @var array holds the list of sortable columns
   */
  public $sortables;

  /**
   *
   * @var array holds the settings for the list filtering and sorting
   */
  private $filter;

  /**
   *
   * @var string holds the search error style statement
   */
  public $search_error_style = '';

  /**
   * the wrapper HTML for the pagination control
   * 
   * the first two elements wrap the whole control, the third wraps the buttons, 
   * the fourth wraps each button
   * 
   * @var array wrapper HTML elements
   */
  public $pagination_wrap = array(
      'wrap_tag' => 'div data-action="pdb_list_filter"',
      'all_buttons' => 'ul',
      'button' => 'li',
  );

  /**
   * this is set as the filters and search parameters are assembled
   *    
   * @var bool the suppression state: true suppresses list output
   */
  public $suppress = false;

  /**
   * set to true if list is the result of a search
   * 
   * @var bool
   */
  public $is_search_result;

  /**
   * @var int the current page number
   */
  private $current_page = 1;

  /**
   * @var string nonce key string
   */
  public static $list_filter_nonce_key = 'list-filter';

  /**
   * initializes and outputs the list on the frontend as called by the shortcode
   *
   * @param array $shortcode_atts display customization parameters
   *                              from the shortcode
   */
  public function __construct( $shortcode_atts )
  {
    $this->set_instance_index();


    // run the parent class initialization to set up the parent methods 
    parent::__construct( $shortcode_atts, $this->default_attributes() );
    
    /**
     * @filter pdb-list_anchor_name
     * @param string the default list anchor name
     * @return string
     */
    $this->list_anchor = Participants_Db::apply_filters( 'list_anchor_name', 'participants-list-' . $this->instance_index );

    $this->list_page = Participants_Db::$list_page;

    $this->_set_page_number();

//    error_log( __METHOD__.' $this->shortcode_atts:'.print_r( $this->shortcode_atts,1 ));

    $this->registration_page_url = get_bloginfo( 'url' ) . '/' . Participants_Db::plugin_setting( 'registration_page', '' );

    $this->i18n = self::i18n();

    $this->_set_single_record_url();

    $this->suppress= $this->attribute_true('suppress');

    global $wp_query;

    $ajax_params = array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
//        'filterNonce' => Participants_Db::nonce( self::$list_filter_nonce_key ),
        'postID' => ( isset( $wp_query->post ) ? $wp_query->post->ID : '' ),
        'prefix' => Participants_Db::$prefix,
        'loading_indicator' => Participants_Db::get_loading_spinner(),
        'allow_empty_term' => Participants_Db::plugin_setting_is_true( 'empty_search', false ),
    );

    wp_localize_script( Participants_Db::$prefix . 'list-filter', 'PDb_ajax', $ajax_params );

    // enqueue the filter/sort AJAX script
    if ( Participants_Db::plugin_setting_is_true( 'ajax_search' ) ) {

      wp_enqueue_script( Participants_Db::$prefix . 'list-filter' );
    }
    
    /*
     * instantiate the List Query object
     */
    $this->set_list_query_object();
    
    if ( $search_error = $this->list_query->get_search_error() ) {
      $this->search_error( $search_error );
    }

    // set up the iteration data
    $this->_setup_iteration();

    /*
     * set the initial sortable field list; this is the set of all fields that are 
     * both marked "sortable" and currently displayed in the list
     */
    $this->_set_default_sortables();

    $this->is_search_result = $this->list_query->is_search_result();

    $this->_print_from_template();
  }

  /**
   * returns the list
   * 
   * @deprecated since version 1.5
   * @var array $atts the shortcode attributes array
   * @return string the HTML
   */
  public static function print_record( $atts )
  {
    return self::get_list( $atts );
  }

  /**
   * prints a list of records called by a shortcode
   *
   * this function is called statically to instantiate the PDb_List object,
   * which captures the output and returns it for display
   *
   * @param array $shortcode_atts parameters passed by the shortcode
   * @return string list HTML
   */
  public static function get_list( $shortcode_atts )
  {
    self::$instance = new PDb_List( $shortcode_atts );

    return self::$instance->output;
  }
  
  
  
  /**
   * provides the default shortcode attributes
   *
   * @return array
   */
  protected function default_attributes()
  {
    return array(
        'sort' => 'false',
        'search' => 'false',
        'list_limit' => Participants_Db::plugin_setting( 'list_limit', '10' ),
        'class' => 'participants-database',
        'filter' => '',
        'orderby' => Participants_Db::plugin_setting( 'list_default_sort' ),
        'order' => Participants_Db::plugin_setting( 'list_default_sort_order' ),
        'fields' => '',
        'search_fields' => '',
        'default_search_field' => '',
        'single_record_link' => '',
        'display_count' => Participants_Db::plugin_setting( 'show_count' ),
        'template' => 'default',
        'module' => 'list',
        'action' => '',
        'suppress' => 'false',
    );
  }
  
  /**
   * includes the shortcode template
   */
  public function _include_template()
  {
    // set some local variables for use in the template
    $filter_mode = $this->_sort_filter_mode();
    $display_count = isset( $this->shortcode_atts['display_count'] ) ? filter_var( $this->shortcode_atts['display_count'], FILTER_VALIDATE_BOOLEAN ) : false;
    $record_count = $this->num_records;
    $records = $this->records;
    $fields = $this->display_columns;
    $single_record_link = $this->single_record_page;
    $records_per_page = $this->shortcode_atts['list_limit'];
    $filtering = $this->shortcode_atts['filtering'];

    if ( is_readable( $this->template ) ) {
      include $this->template;
    }
  }

  /**
   * sets up the template iteration object
   *
   * this takes all the fields that are going to be displayed and organizes them
   * under their group so we can easily run through them in the template
   * 
   * @global wpdb $wpdb
   */
  public function _setup_iteration()
  {

    /**
     * the list query object can be modified at this point to add a custom search
     * 
     * @action pdb-list_query_object
     * @param PDb_List_Query
     */
    do_action( Participants_Db::$prefix . 'list_query_object', $this->list_query );

    /**
     * allow the query to be altered before the records are retrieved
     * 
     * @filter pdb-list_query
     * @param string the list query
     * @return string list query
     */
    $list_query = Participants_Db::apply_filters( 'list_query', $this->list_query->get_list_query() );

    if ( PDB_DEBUG )
      Participants_Db::debug_log( __METHOD__ .' list query: ' . $list_query );

    // get the $wpdb object
    global $wpdb;

    // get the number of records returned
    $this->num_records = count( $wpdb->get_results( $list_query ) );

    $this->_set_list_limit();

    // set up the pagination object
    $pagination_defaults = Participants_Db::apply_filters( 'pagination_configuration', array(
                'link' => $this->prepare_page_link( $this->shortcode_atts['filtering'] ? filter_input( INPUT_POST, 'pagelink' ) : $_SERVER['REQUEST_URI']  ),
                'page' => $this->current_page,
                'size' => $this->page_list_limit,
                'total_records' => $this->num_records,
                'filtering' => $this->shortcode_atts['filtering'],
                'add_variables' => 'instance=' . $this->instance_index . $this->pagination_link_anchor(),
            ) );
    
    // instantiate the pagination object
    $this->pagination = new PDb_Pagination( $pagination_defaults );
    /*
     * get the records for this page, adding the pagination limit clause
     *
     * this gives us an array of objects, each one a set of field->value pairs
     */
    $records = $wpdb->get_results( $list_query . ' ' . $this->pagination->getLimitSql(), OBJECT );
    
    /*
     * build an array of record objects, indexed by ID
     */
    $this->records = array();
    foreach ( $records as $record ) {

      $id = $record->id;
      if ( !in_array( 'id', $this->display_columns ) )
        unset( $record->id );

      $this->records[$id] = $record;
    }

    foreach ( $this->records as $record_id => $record_fields ) {

      //$this->participant_values = Participants_Db::get_participant($record_id);

      foreach ( $record_fields as $field => $value ) {

        // add the field to the records object
        $this->records[$record_id]->{$field} = new PDb_Field_Item( (object) array( 
            'name' => $field, 
            'record_id' => $record_id, 
            'module' => $this->module, 
            'value' => $value,
                ) );
        
      }
    }
    
    reset( $this->records );
    
    /*
     * at this point, $this->records has been defined as an array of records,
     * each of which is an object that is a collection of objects: each one of
     * which is a PDb_Field_Item instance
     */
//     error_log( __METHOD__.' all records:'.print_r( $this->records,1));
  }
  
  /**
   * provides the pagination scroll anchor
   * 
   * the anchor is not added if AJAX is enabled because the scroll is enacted by the JS
   * 
   * @return string the anchor; empty string if not configured to add it
   */
  protected function pagination_link_anchor()
  {
    $anchor = '';
    if ( Participants_Db::plugin_setting_is_true( 'use_pagination_scroll_anchor' ) && !Participants_Db::plugin_setting_is_true( 'ajax_search') ) {
      $anchor = '#' . $this->list_anchor;
    }
    return $anchor;
  }

  /**
   * sets up the array of display columns
   */
  protected function _set_shortcode_display_columns()
  {
    if ( empty( $this->shortcode_atts['groups'] ) ) {
      $this->display_columns = $this->get_list_display_columns( 'display_column' );
    } else {
      parent::_set_shortcode_display_columns();
    }
  }

  /**
   * sets the page number
   * 
   * if the instance in the input array matches the current instance, we set the 
   * page number from the input array
   *
   * @return null
   */
  private function _set_page_number()
  {
    $input = false;
    $this->current_page = 1;
    /*
     * paginating using the pgae number in as a GET var doesn't require the instance number
     */
    if ( filter_input( INPUT_GET, $this->list_page, FILTER_VALIDATE_INT ) !== false ) {
      $input = INPUT_GET;
    }
    /*
     * paginating using the POST array does require the correct instance index value
     */
    if ( isset( $_POST[$this->list_page] ) && filter_input( INPUT_POST, 'instance_index', FILTER_VALIDATE_INT ) == $this->instance_index ) {
      $input = INPUT_POST;
    }
    if ( $input !== false ) {
      $this->current_page = filter_input( $input, $this->list_page, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'default' => 1)) );
    }
  }

  /**
   * prints the whole search/sort form as a shortcut function
   *
   */
  public function show_search_sort_form()
  {

    $output = array();

    if ( $this->_sort_filter_mode() != 'none' && !$this->shortcode_atts['filtering'] ) {

      $output[] = $this->search_error_style;
      $output[] = '<div class="pdb-searchform">';
      $output[] = '<div class="pdb-error pdb-search-error" style="display:none">';
      $output[] = sprintf( '<p class="search_field_error">%s</p>', __( 'Please select a column to search in.', 'participants-database' ) );
      $output[] = sprintf( '<p class="value_error">%s</p>', __( 'Please type in something to search for.', 'participants-database' ) );
      $output[] = '</div>';
      $output[] = $this->search_sort_form_top( false, false, false );

      if ( $this->_sort_filter_mode() == 'filter' || $this->_sort_filter_mode() == 'both' ) {

        $output[] = '<fieldset class="widefat inline-controls">';

        $output[] = sprintf( '<legend>%s:</legend>', __( 'Search', 'participants-database' ) );

        $output[] = $this->column_selector( false, false );
        $output[] = $this->search_form( false );

        $output[] = '</fieldset>';
      }

      if (
              ($this->_sort_filter_mode() == 'sort' || $this->_sort_filter_mode() == 'both') and ( !empty( $this->sortables ) and is_array( $this->sortables ) )
      ) {

        $output[] = '<fieldset class="widefat inline-controls">';

        $output[] = sprintf( '<legend>%s:</legend>', __( 'Sort by', 'participants-database' ) );

        $output[] = $this->sort_form( false );

        $output[] = '</fieldset>';
      }

      $output[] = '</form></div>';
    }

    /**
     * @filter pdb-search_sort_form_output
     * @param array of string HTML lines
     * 
     * @return array
     */
    echo $this->output_HTML( Participants_Db::apply_filters( 'search_sort_form_output', $output ) );
  }

  /**
   * prints the top of the search/sort form
   *
   * @param string $target set the action attribute of the search form to another 
   *                       page, giving the ability to have the search on a 
   *                       different page than the list, defaults to the same page
   * @global object $post
   */
  public function search_sort_form_top( $target = false, $class = false, $print = true )
  {

    $this->shortcode_atts['target_page'] = trim( $this->shortcode_atts['target_page'] );

    if ( !empty( $this->shortcode_atts['action'] ) && empty( $this->shorcode_atts['target_page'] ) )
      $this->shorcode_atts['target_page'] = $this->shortcode_atts['action'];

    global $post;

    $output = array();

    $ref = 'update';
    if ( $target === false && !empty( $this->shortcode_atts['target_page'] ) && $this->module == 'search' ) {
      $target = Participants_Db::find_permalink( $this->shortcode_atts['target_page'] );
    }
    if ( $target ) {
      $ref = 'remote';
    }

    $action = $target !== false ? $target : get_permalink( $post->ID ) . '#' . $this->list_anchor;

    $class_att = $class ? 'class="' . $class . '"' : '';

    $output[] = '<form method="post" class="sort_filter_form" action="' . $action . '"' . $class_att . ' data-ref="' . $ref . '" >';
    
    if ( Participants_Db::plugin_setting_is_true( 'use_session_alternate_method' ) ) {
      $hidden_fields[PDb_Session::id_var] = session_id();
    }
    
    if ( $ref === 'remote' ) {
      $hidden_fields['submit_button'] = 'search';
    }
    $output[] = PDb_FormElement::print_hidden_fields( $this->search_sort_form_hidden_fields(), false );

    if ( $print )
      echo $this->output_HTML( $output );
    else
      return $this->output_HTML( $output );
  }
  
  /**
   * provides the search sort form hidden fields
   * 
   * @return array
   */
  protected function search_sort_form_hidden_fields()
  {
    return array(
        'action' => 'pdb_list_filter',
        'target_instance' => $this->shortcode_atts['target_instance'],
        'instance_index' => $this->instance_index,
        'pagelink' => $this->prepare_page_link( $_SERVER['REQUEST_URI'] ),
        'sortstring' => $this->filter['sortstring'],
        'orderstring' => $this->filter['orderstring'],
//        'filterNonce' => Participants_Db::nonce( self::$list_filter_nonce_key ),
    );
  }

  /**
   * builds a dropdown element with a list of columns available for filtering
   *
   * @param string $all     sets the "all fields" or no search string, defaults to "show all"
   * @param bool   $print   if true prints the dropdown element
   * @param array  $columns array of columns to show in the dropdown, defaults to displayed columns
   * @param string $sort    sort method to apply to selector list
   * @param bool   $multi   if true, field name will include [] so the value is submitted as an array element
   *
   * @return NULL or HTML string if $print == false
   */
  public function column_selector( $all = false, $print = true, $columns = false, $sort = 'column', $multi = false )
  {

    static $multifield_count = 0;
    $value = $this->list_query->current_filter( 'search_field' );
    if ( empty( $value ) && isset( $_POST['search_field'] ) ) {
      $value = filter_input( INPUT_POST, 'search_field', FILTER_SANITIZE_STRING );
    }
    if ( $multi ) {
      $values = $this->list_query->current_filter( 'search_fields' );
      $value = isset( $values[$multifield_count] ) ? $values[$multifield_count] : '';
    }

    /**
     * @filter pdb-searchable_columns
     * 
     * @param array of column $title => $name
     * @return array
     */
    $search_columns = Participants_Db::apply_filters( 'searchable_columns', $this->searchable_columns( self::field_list( $columns ? $columns : $this->shortcode_atts['search_fields']  ) ) );
    
    $all_string = false === $all ? '(' . __( 'select', 'participants-database' ) . ')' : $all;
    $base_array = array( $all_string => 'none', PDb_FormElement::null_select_key() => false );
    
    if ( in_array( $this->shortcode_atts['default_search_field'], $search_columns ) ) {
      $base_array = array(PDb_FormElement::null_select_key() => false);
      if ( $value === false ) {
        $value = $this->shortcode_atts['default_search_field'];
      }
    }

    if ( count( $search_columns ) > 1 ) {
      $element = array(
          'type' => 'dropdown',
          'name' => 'search_field' . ($multi ? '[]' : ''),
          'value' => $value,
          'class' => 'search-item',
          'options' => $base_array + $search_columns,
      );
    } else {
      $element = array(
          'type' => 'hidden',
          'name' => 'search_field' . ($multi ? '[]' : ''),
          'value' => current( $search_columns ),
          'class' => 'search-item',
      );
    }
    $multifield_count++;
    if ( $print )
      PDb_FormElement::print_element( $element );
    else
      return PDb_FormElement::get_element( $element );
  }

  /**
   * supplies an array of searchable columns
   * 
   * this is needed because the shorcode can define which fields to show, so the 
   * total set of potentially searchable fields would be the shortcode defined 
   * fields plus the fields given a display column in the database
   * 
   * $this->display_columns only contains the columns currently defined as shown in the list
   * 
   * @param array $columns array of column names
   * @return array $title => $name
   */
  public function searchable_columns( $columns = false )
  {
    $return = array();
    $search_columns = is_array( $columns ) && !empty( $columns ) ? $columns : $this->display_columns;
    foreach ( $search_columns as $col ) {
      $column = $this->get_column_atts( $col );
      /**
       * 
       * @filter pdb-searchable_column_form_element_exceptions
       * @param array of exempt form elements
       * @return array
       */
      if ( $column && !in_array( $column->form_element, Participants_Db::apply_filters('searchable_column_form_element_exceptions', array('placeholder', 'captcha') ) ) )
        $return[$column->title] = $column->name;
    }
    return $return;
  }

  /**
   * print a search form
   * 
   * this is a shortcut to print a preset search form.
   * 
   * @param bool $print
   * @return null|string
   */
  public function search_form( $print = true )
  {

//    error_log(__METHOD__.' target: '.$this->shortcode_atts['target_instance'].' module: '.$this->module);

    $search_term = PDb_FormElement::get_value_title( $this->list_query->current_filter( 'search_term' ), $this->list_query->current_filter( 'search_field' ) );
    
    $search_input_config = array(
        'name' => 'value',
        'type' => 'text-line',
        'attributes' => Participants_Db::apply_filters( 'search_input_attributes' , array(
            'id' => 'participant_search_term',
            'class' => 'search-item',
        ) ),
        'value' => $search_term,
    );

    $output = array();

    $output[] = '<input name="operator" type="hidden" class="search-item" value="' . ( Participants_Db::plugin_setting_is_true( 'strict_search' ) ? '=' : 'LIKE' ) . '" />';
    $output[] = PDb_FormElement::get_element($search_input_config);
    //$output[] = '<input id="participant_search_term" type="text" name="value" class="search-item" value="' . esc_attr( $search_term ) . '">';
    $output[] = $this->search_submit_buttons();

    /**
     * @version 1.6.3
     * @filter pdb-search_control_html
     */
    $html = Participants_Db::apply_filters( 'search_control_html', $this->output_HTML( $output ) );

    if ( $print )
      echo $html;
    else
      return $html;
  }

  /**
   * supplies a search form submit and clear button with optional button text strings
   * 
   * the $values array can supply a locally-defined button text value, for example:
   * $values = array( 'submit' => 'Search Records', 'clear' => 'Clear the Search Parameters' );
   * 
   * @param array $values array of strings to set the "value" attribute
   * @return string the HTML
   */
  public function print_search_submit_buttons( $values = '' )
  {
    $submit_text = isset( $values['submit'] ) ? $values['submit'] : $this->i18n['search'];
    $clear_text = isset( $values['clear'] ) ? $values['clear'] : $this->i18n['clear'];
    $output = array();
    $output[] = '<input name="submit_button" class="search-form-submit" data-submit="search" type="submit" value="' . esc_attr( $submit_text ) . '">';
    $output[] = '<input name="submit_button" class="search-form-clear" data-submit="clear" type="submit" value="' . esc_attr( $clear_text ) . '">';
    print $this->output_HTML( $output );
  }

  /**
   * supplies a search form submit and clear button
   * 
   * @return string the HTML
   */
  public function search_submit_buttons()
  {
    $output = array();
    $output[] = '<input name="submit_button" class="search-form-submit" data-submit="search" type="submit" value="' . esc_attr( $this->i18n['search'] ) . '">';
    $output[] = '<input name="submit_button" class="search-form-clear" data-submit="clear" type="submit" value="' . esc_attr( $this->i18n['clear'] ) . '">';
    return $this->output_HTML( $output );
  }

  /**
   * 
   * @param bool $print
   * @return null|string
   */
  public function sort_form( $print = true )
  {

    $value = $this->list_query->current_filter( 'sort_field' );
    $options = array();
    if ( !in_array( $value, $this->sortables ) ) {
      $default_sort_field = Participants_Db::plugin_setting('list_default_sort', '');
      if ( empty($default_sort_field) ) {
        $options = array(PDb_FormElement::null_select_key() => '');
      } else {
        $sort_field_def = Participants_Db::$fields[$default_sort_field];
        $options = array( $sort_field_def->title => $default_sort_field );
      }
    }
    $element = array(
        'type' => 'dropdown',
        'name' => 'sortBy',
        'value' => $value,
        'options' => $options + $this->sortables,
        'class' => 'search-item',
    );
    $output[] = PDb_FormElement::get_element( $element );

    $element = array(
        'type' => 'radio',
        'name' => 'ascdesc',
        'value' => $this->list_query->current_filter( 'sort_order' ),
        'class' => 'checkbox inline search-item',
        'options' => array(
            __( 'Ascending', 'participants-database' ) => 'ASC',
            __( 'Descending', 'participants-database' ) => 'DESC'
        ),
    );
    $output[] = PDb_FormElement::get_element( $element );

    $output[] = '<input name="submit_button" data-submit="sort" type="submit" value="' . esc_attr( $this->i18n['sort'] ) . '" />';

    if ( $print )
      echo $this->output_HTML( $output );
    else
      return $this->output_HTML( $output );
  }

  /**
   * prints the list count if enabled in the shortcode
   * 
   * this can be optionally given an open tag to wrap the output in. Only the open 
   * tag is given: the close tag is derived from it. By default, the pattern is 
   * wrapped in a '<caption>' tag.
   * 
   * @var string $wrap_tag the HTML to wrap the count statement in
   * @var bool $print echo ouput if true
   */
  public function print_list_count( $wrap_tag = false, $print = true )
  {
    if ( $this->attribute_true( 'display_count' ) ) {
      if ( !$wrap_tag )
        $wrap_tag = '<caption class="%s" >';
      $wrap_tag_close = '';
      $css_class = $this->num_records == '0' ? 'pdb-list-count list-count-zero' : 'pdb-list-count';
      // create the close tag by reversing the order of the open tags
      $tag_count = preg_match_all( '#<([^ >]*)#', $wrap_tag, $matches );
      if ( $tag_count ) {
        $tags = $matches[1];
        $tags = array_reverse( $tags );
        $wrap_tag_close = '</' . implode( '></', $tags ) . '>';
      }
      $per_page = $this->shortcode_atts['list_limit'] == '-1' ? $this->num_records : $this->shortcode_atts['list_limit'];
      $output = sprintf( $wrap_tag, $css_class ) . sprintf(
              $this->list_count_template(), 
              $this->num_records, // total number of records found
              $per_page, // number of records to show each page
              (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']) + ($this->num_records > 1 ? 1 : 0), // starting record number
              ($this->num_records - (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']) > $this->shortcode_atts['list_limit'] ?
                  $this->pagination->page * $this->shortcode_atts['list_limit'] :
                  (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']) + ($this->num_records - (($this->pagination->page - 1) * $this->shortcode_atts['list_limit'])) ), // ending record number
              $this->pagination->page // current page
              ) . $wrap_tag_close;

      if ( $print )
        echo $output;
      else
        return $output;
    }
  }
  
  /**
   * provides the list result count template
   * 
   * @return string
   */
  protected function list_count_template()
  {
    $template = Participants_Db::plugin_setting( 'count_template', 'Total Records Found: %1$s, showing %2$s per page' );
    /**
     * @filter pdb-list_count_template
     * @param string template
     * @param int result count
     * @return string
     * 
     * %1$s - total number of records found
     * %2$s - number of records shown per page
     * %3$s - starting record number
     * %4$s - ending record number
     * %5$s - the current page number
     * 
     */
    return Participants_Db::apply_filters( 'list_count_template', $template, $this->num_records );
  }

  /**
   * sets the sortables list
   * 
   * this func is only used in templates to set up a custom sort dropdown
   * 
   * @param array  $columns supplies a list of columns to use, defaults to sortable 
   *                        displayed columns
   * @param string $sort    'column' sorts by the display column order, 'order' uses 
   *                        the defined group/fields order, 'alpha' sorts the list 
   *                        alphabetically
   * @return NULL just sets the sortables property
   */
  public function set_sortables( $columns = false, $sort = 'column' )
  {
    if ( $columns !== false or $sort != 'column' ) {
      $this->sortables = Participants_Db::get_sortables( $columns, $sort );
    }
  }

  /**
   * sets the default list of sortable columns
   * 
   */
  protected function _set_default_sortables()
  {
    $columns = array();
    foreach ( $this->display_columns as $column ) {
      if ( $this->fields[$column]->sortable > 0 ) {
        $columns[] = $column;
      }
    }
    // if no columns are set as sortable, use all displayed columns
    if ( empty( $columns ) ) {
      $columns = $this->display_columns;
    }
    /**
     * @filter pdb-list_sortable_columns
     * @param array of field names
     * @param PDb-List the current instance
     * @return array
     */
    $this->set_sortables( Participants_Db::apply_filters('list_sortable_columns', $columns, $this ) );
  }

  /**
   * echoes the pagination controls to the template
   *
   * this does nothing if filtering is taking place
   *
   */
  public function show_pagination_control()
  {

    // set the wrapper HTML parameters
    $this->pagination->set_wrappers( $this->pagination_wrap );

    // print the control
    echo $this->pagination->create_links();
  }

  /**
   * sets the pagination control HTML
   *
   * @param string $open the opening HTML for the whole control
   * @param string $close the close HTML for the whole control
   * @param string $all_buttons the wrap tag for the buttons
   * @param string $button the tag that wraps each button (which is an 'a' tag)
   */
  protected function set_pagination_wrap( $open = '', $close = '', $all_buttons = '', $button = '' )
  {
    foreach ( array('open', 'close', 'all_buttons', 'button') as $tag ) {
      if ( isset( $$e ) and ! empty( $$e ) )
        $this->pagination_wrap[$e] = $$e;
    }
  }

  /**
   * get the column form element type
   *
   * @return string the form element type
   *
   */
  public function get_field_type( $column )
  {
    $column_atts = $this->fields[$column];

    return $column_atts->form_element;
  }

  /**
   * are we setting the single record link?
   * 
   * @return bool true if the current field is the designated single record link field
   */
  public function is_single_record_link( $column )
  {

    return (
            Participants_Db::is_single_record_link( $column ) &&
            false !== $this->single_record_page &&
            !in_array( $this->get_field_type( $column ), array('rich-text', 'link') )
            );
  }

  /**
   * print a date string from a UNIX timestamp
   * 
   * @param int|string $value timestamp or date string
   * @param string $format format to use to override plugin settings
   * @param bool $print if true, echo the output
   * @return string formatted date value
   */
  public function show_date( $value, $format = false, $print = true )
  {

    $date = PDb_Date_Display::get_date( $value, __METHOD__ );

    if ( $print )
      echo $date;
    else
      return $date;
  }

  /**
   * converts an array value to a readable string
   * 
   * @param array $value
   * @param string $glue string to use for concatenation
   * @param bool $print if true, echo the output
   * @return string HTML
   */
  public function show_array( $value, $glue = ', ', $print = true )
  {

    $array = array_filter( Participants_Db::unserialize_array( $value ), array('PDb_FormElement', 'is_displayable') );

    $output = implode( $glue, $array );

    if ( $print )
      echo $output;
    else
      return $output;
  }

  /**
   * returns a concatenated string from an array of HTML lines
   * 
   * @version 1.6 added option to assemble HTML without linebreaks
   * 
   * @param array $output
   * @return type
   */
  public function output_HTML( $output = array() )
  {
    $glue = Participants_Db::plugin_setting_is_true( 'strip_linebreaks' ) ? '' : PHP_EOL;
    return implode( $glue, $output );
  }

  /**
   * sets up an anchored value
   * 
   * this uses any relevant plugin settings
   * 
   * @param array|string $value
   * @param string       $template for the link HTML (optional)
   * @param bool         $print    if true, HTML is echoed
   * @return string HTML
   */
  public function show_link( $value, $template = false, $print = false )
  {

    $params = maybe_unserialize( $value );

    if ( is_array( $params ) ) {

      if ( count( $params ) < 2 )
        $params[1] = $params[0];
    } else {

      // in case we got old unserialized data in there
      $params = array_fill( 0, 2, $value );
    }

    $output = Participants_Db::make_link( $params[0], $params[1], $template );

    if ( $print )
      echo $output;
    else
      return $output;
  }

  /* BUILT-IN OUTPUT METHODS */

  /**
   * prints a table header row
   */
  public function print_header_row( $head_pattern = '' )
  {
    if ( empty( $head_pattern ) ) {
      $head_pattern = '<th class="%2$s" scope="col">%1$s</th>';
    }
    // print the top header row
    foreach ( $this->display_columns as $column ) {
      
      $field = Participants_Db::$fields[$column];
      /* @var $field PDb_Form_Field_Def */
      
      $title = str_replace( array('"', "'"), array('&quot;', '&#39;'), stripslashes( $field->title() ) );
      /**
       * @filter pdb-list_header_title
       * @param string the title
       * @param PDb_Form_Field_Def
       * @param array the current list filter
       * @return string title
       */
      printf( 
              $head_pattern, 
              Participants_Db::apply_filters('list_header_title', $title, $field, $this->list_query->current_filter() ), 
              $column );
    }
  }

  /**
   * strips the page number out of the URI so it can be used as a link to other pages
   * 
   * we also strip out the request string for filtering values as they are added 
   * from the 'add_variables' element of the pagination config array
   *
   * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
   *
   * @return string the re-constituted URI
   */
  public function prepare_page_link( $uri )
  {

    $URI_parts = explode( '?', $uri );

    if ( empty( $URI_parts[1] ) ) {

      $values = array();
    } else {

      parse_str( $URI_parts[1], $values );

      /* clear out our filter variables so that all that's left in the URI are 
       * variables from WP or any other source-- this is mainly so query string 
       * page id can work with the pagination links
       */
      $filter_atts = array(
          $this->list_page,
          'search_field',
          'value',
          'operator',
          'sortBy',
          'ascdesc',
          'submit',
          'pagelink',
          'sortstring',
          'orderstring',
          'postID',
          'action',
//          'filterNonce',
          'instance',
      );
      foreach ( $filter_atts as $att )
        unset( $values[$att] );
    }

    return $URI_parts[0] . '?' . (count( $values ) > 0 ? http_build_query( $values ) . '&' : '') . $this->list_page . '=%1$s';
  }

  /**
   * builds the sort-filter mode setting
   */
  private function _sort_filter_mode()
  {
    $mode = $this->attribute_true( 'sort' ) ? 'sort' : 'none';

    return $this->attribute_true( 'search' ) ? ( $mode === 'sort' ? 'both' : 'filter' ) : $mode;
  }

  /**
   * builds a URI query string from the filter parameters
   *
   * @param  array  $values the incoming finter values
   * @return string URL-encoded filter parameters, empty string if filter is not active
   */
  private function _filter_query( $values )
  {

    if ( !empty( $values ) ) {

      return http_build_query( array_merge( $values, $this->filter ) ) . '&';
    } else
      return '';
  }

  /**
   * takes the $_POST array and constructs a filter statement to add to the list shortcode filter
   */
  private function _make_filter_statement( $post )
  {

    if ( !Participants_Db::is_column( $post['search_field'] ) )
      return '';

    $this->filter['search_field'] = $post['search_field'];


    switch ( $post['operator'] ) {

      case 'LIKE':

        $operator = '~';
        break;

      case 'NOT LIKE':
      case '!=':

        $operator = '!';
        break;

      case 'gt':

        $operator = '>';
        break;

      case 'lt':

        $operator = '<';
        break;

      default:

        $operator = '=';
    }

    $this->filter['operator'] = $operator;

    if ( empty( $post['value'] ) )
      return '';

    $this->filter['value'] = $post['value'];

    return $this->filter['search_field'] . $this->filter['operator'] . $this->filter['value'];
  }

  /**
   * sets the search error so it will be shown to the user
   * 
   * @param string $type sets the error type
   * @return string the CSS style rule to add
   */
  public function search_error( $type )
  {

    $css = array('.pdb-search-error');

    if ( $type == 'search' )
      $css[] = '.search_field_error';
    if ( $type == 'value' )
      $css[] = '.value_error';

    $this->search_error_style = sprintf( '<style>.pdb-search-error p { display:none } %s { display:inline-block !important }</style>', implode( ', ', $css ) );
  }

  /**
   * gets the first value of a comma-separated list
   */
  public function get_first_in_list( $list )
  {
    $listitems = explode( ',', $list );
    return trim( $listitems[0] );
  }

  /**
   * sets the single record page url
   * 
   */
  private function _set_single_record_url()
  {

    if ( !empty( $this->shortcode_atts['single_record_link'] ) )
      $page_id = Participants_Db::get_id_by_slug( $this->shortcode_atts['single_record_link'] );
    else {
      $page_id = Participants_Db::plugin_setting( 'single_record_page', false );
    }

    $this->single_record_page = Participants_Db::get_permalink( $page_id );
    
    // supply our page to the main script
    add_filter( 'pdb-single_record_page', array($this, 'single_record_page') );
  }

  /**
   * supplies the single record page URL
   * 
   * @return string the URL (without the record id)
   */
  public function single_record_page()
  {
    return $this->single_record_page;
  }

  /**
   * sets the record edit url
   * 
   * @return null
   */
  private function _set_record_edit_url()
  {
    $this->registration_page_url = get_bloginfo( 'url' ) . '/' . Participants_Db::plugin_setting( 'registration_page', '' );
  }
  
  /**
   * prints the CSV download form
   * 
   * @param array $config
   *          'title' => string the title for the export control
   *          'helptext' => string helptext to show with the control
   *          'button_text' => string the text shown on the download button
   *          'filename'  => string initial name of the file
   *          'allow_user_filename' => bool  if false, user cannot set filename
   *          'export_fields' => array of field names to include in the export
   * 
   * @return null
   */
  public function csv_export_form( $config )
  {
    extract( shortcode_atts( array(
        'title'         => Participants_Db::plugin_label( 'export_csv_title' ),
        'helptext'      => '<p>' . __( 'This will download the whole list of participants that match your search terms.', 'participants-database' ) . '</p>',
        'button_text'   => __( 'Download CSV for this list', 'participants-database' ),
        'filename'      => Participants_Db::PLUGIN_NAME . PDb_List_Admin::filename_datestamp(),
        'export_fields' => false,
        'allow_user_filename' => true,
        'field_titles_checkbox' => true,
        'field_titles_checkbox_label' => __( 'Include field titles', 'participants-database' ),
    ), $config ) );
    
    $suggested_filename = esc_attr( $filename )  . '.csv';
    
    Participants_Db::$session->set( 'csv_export_query', $this->list_query->get_list_query() );
    Participants_Db::$session->set( 'csv_export_fields', $export_fields );
    ?>
    <div class="pdb-pagination csv-export-box">
        <?php if ($title !== false ) : ?>
        <h3><?php echo $title ?></h3>
        <?php endif ?>
        <form method="post" class="csv-export">
          <input type="hidden" name="subsource" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
          <input type="hidden" name="action" value="output CSV" />
          <input type="hidden" name="CSV type" value="participant list" />
          <?php wp_nonce_field( PDb_Base::csv_export_nonce() ); ?>
          <fieldset class="inline-controls">
<?php if ( $allow_user_filename ) echo __( 'File Name', 'participants-database' ) . ':' ?>
            <input type="<?php echo ( $allow_user_filename ? 'text' : 'hidden' ) ?>" name="filename" value="<?php echo $suggested_filename ?>" />
            <input type="submit" name="submit-button" value="<?php echo $button_text  ?>" class="button button-primary" />
            <?php if ( $field_titles_checkbox ) : ?>
            <label for="include_csv_titles"><input type="checkbox" name="include_csv_titles" value="1"><?php echo $field_titles_checkbox_label ?></label>
            <?php endif ?>
          </fieldset>
          <?php echo $helptext ?>
        </form>
    </div>
    <?php
  }

  /**
   * merges two indexed arrays such that each element is unique in the resulting indexed array
   * 
   * @param array $array1 this array will take priority, it's elements will precede 
   *                      elements from the second array
   * @param array $array2
   * @return array an indexed array of unique values
   */
  public static function array_merge_unique( $array1, $array2 )
  {
    $array1 = array_combine( array_values( $array1 ), $array1 );
    $array2 = array_combine( array_values( $array2 ), $array2 );

    return array_values( array_merge( $array1, $array2 ) );
  }

  /**
   * converts a URL-encoded character to the correct utf-8 form
   *
   * @param string $string the string to convert to UTF-8
   * @return string the converted string
   */
  function to_utf8( $string )
  {

    $value = preg_match( '/%[0-9A-F]{2}/i', $string ) ? rawurldecode( $string ) : $string;
    if ( !function_exists( 'mb_detect_encoding' ) ) {
      Participants_Db::debug_log( __METHOD__ . ': Participants Database Plugin unable to process multibyte strings because "mbstring" module is not present' );
      return $value;
    }
    $encoding = mb_detect_encoding( $value . 'a', array('utf-8', 'windows-1251', 'windows-1252', 'ISO-8859-1') );
    return ($encoding == 'UTF-8' ? $value : mb_convert_encoding( $value, 'utf-8', $encoding ));
  }

  /**
   * sets the list limit value
   * 
   * @return null
   */
  private function _set_list_limit()
  {
    $limit = filter_input(
            INPUT_POST, 'list_limit', FILTER_VALIDATE_INT
    );

    if ( is_null( $limit ) || $limit === 0 ) {
      $limit = $this->shortcode_atts['list_limit'];
    }

    if ( $limit < 1 || $limit > $this->num_records ) {
      $this->page_list_limit = $this->num_records;
    } else {
      $this->page_list_limit = $limit;
    }
  }

  /**
   * instantiates the list query object for the list instance
   * 
   * @return null
   */
  private function set_list_query_object()
  {

    $this->list_query = new PDb_List_Query( $this );
    $search_term = $this->list_query->current_filter( 'search_term' );

    /*
     * if the current list instance doesn't have a search term, see if there is an 
     * incoming search that targets it
     */
    if ( empty( $search_term ) && $this->list_query->is_search_result() ) {
      $this->list_query->set_query_session( $this->shortcode_atts['target_instance'] );
    }
  }

  /**
   * provides the internationalization strings
   */
  public static function i18n()
  {

    /* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
    return array(
        'delete_checked' => _x( 'Delete Checked', 'submit button label', 'participants-database' ),
        'change' => _x( 'Change', 'submit button label', 'participants-database' ),
        'sort' => _x( 'Sort', 'submit button label', 'participants-database' ),
        'filter' => _x( 'Filter', 'submit button label', 'participants-database' ),
        'clear' => _x( 'Clear', 'submit button label', 'participants-database' ),
        'search' => _x( 'Search', 'search button label', 'participants-database' ),
    );
  }

}
