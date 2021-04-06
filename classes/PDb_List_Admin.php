<?php
/**
 * class for handling the listing of participant records in the admin
 *
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.3
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_List_Admin {

  /**
   * @var string translations strings for buttons
   */
  public static $i18n;

  /**
   * @var object holds the pagination object
   */
  public static $pagination;

  /**
   * @var int holds the number of list items to show per page
   */
  public static $page_list_limit;

  /**
   * @var string the name of the list page variable
   */
  public static $list_page = 'listpage';

  /**
   * @var string name of the list anchor element
   */
  public static $list_anchor = 'participants-list';

  /**
   * @var array all the records are held in this array
   */
  public static $participants;

  /**
   * @var string holds the url of the registrations page
   */
  public static $registration_page_url;

  /**
   * holds the columns to display in the list
   * 
   * @var array of field objects
   */
  public static $display_columns;

  /**
   * @var \PDb_admin_list\query holds the admin_list_query instance
   */
  public static $query;

  /**
   * @var \PDb_admin_list\filter the current filter object
   */
  public static $list_filter;

  /**
   *  @var string base name of admin user options
   */
  public static $user_setting_name = 'admin-user-settings';

  /**
   *  @var string name of admin user options
   */
  public static $user_settings;

  /**
   * @param array $errors array of error messages
   */
  public static $error_messages = array();

  /**
   * initializes and outputs the list for the backend
   * 
   * @global wpdb $wpdb
   */
  public static function initialize()
  {

    self::_setup_i18n();

    /**
     * @filter pdb-admin_list_with_selected_action_conf_messages
     * 
     * @param array of feedback messages, keyed by the name of the action in the 
     *              form: $action => array( 
     *                'singular' => $singular_message,  
     *                'plural' => $plural_message )
     * @return array
     */
    $apply_confirm_messages = Participants_Db::apply_filters( 'admin_list_with_selected_action_conf_messages', array(
                'delete' => array(
                    "singular" => __( "Do you really want to delete the selected record?", 'participants-database' ),
                    "plural" => __( "Do you really want to delete the selected records?", 'participants-database' ),
                ),
                'approve' => array(
                    "singular" => __( "Approve the selected record?", 'participants-database' ),
                    "plural" => __( "Approve the selected records?", 'participants-database' ),
                ),
                'unapprove' => array(
                    "singular" => __( "Unapprove the selected record?", 'participants-database' ),
                    "plural" => __( "Unapprove the selected records?", 'participants-database' ),
                ),
                'send_signup_email' => array(
                    "singular" => __( "Send the signup email to the selected record?", 'participants-database' ),
                    "plural" => __( "Send the signup email to the selected records?", 'participants-database' ),
                ),
                'send_resend_link_email' => array(
                    "singular" => __( 'Send the "resend link" email to the selected record?', 'participants-database' ),
                    "plural" => __( 'Send the "resend link" email to the selected records?', 'participants-database' ),
                ),
                'recipient_count_exceeds_limit' => sprintf( __( 'The number of selected records exceeds the %s email send limit.%s Only the first %s will be sent.', 'participants-database' ), '<a href="https://xnau.com/product_support/email-expansion-kit/#email_session_send_limit" target="_blank" >', '</a>', '{limit}' ),
                    )
    );

    wp_localize_script(
            Participants_Db::$prefix . 'list-admin', 'list_adminL10n', array(
        'delete' => self::$i18n[ 'delete_checked' ],
        'cancel' => self::$i18n[ 'change' ],
        'apply' => self::$i18n[ 'apply' ],
        'apply_confirm' => $apply_confirm_messages,
        'send_limit' => (int) Participants_Db::apply_filters( 'mass_email_session_limit', Participants_Db::$mass_email_session_limit ),
        /**
         * @filter pdb-unlimited_with_selected_actions
         * @param array of actions that are not quantity limited
         * @return array
         */
        'unlimited_actions' => Participants_Db::apply_filters( 'unlimited_with_selected_actions', array( 'delete', 'approve', 'unapprove' ) ),
            )
    );
    wp_enqueue_script( Participants_Db::$prefix . 'list-admin' );
    wp_enqueue_script( Participants_Db::$prefix . 'debounce' );

    // set up email error feedback
    add_action( 'wp_mail_failed', array( __CLASS__, 'get_email_error_feedback' ) );
    add_action( 'pdb-list_admin_head', array( __CLASS__, 'show_email_error_feedback' ) );

    // delete images and files when record is deleted
    if ( Participants_Db::plugin_setting_is_true( 'delete_uploaded_files', false ) ) {
      add_action( 'pdb-list_admin_with_selected_delete', array( 'PDb_submission\delete_uploads', 'delete_record_uploaded_files' ) );
    }

    $current_user = wp_get_current_user();

    // set up the user settings options
    self::$user_settings = Participants_Db::$prefix . self::$user_setting_name . '-' . $current_user->ID;

//    error_log(__METHOD__.' session: '.print_r(Participants_Db::$session,1));

    self::set_list_limit();

    self::$registration_page_url = get_bloginfo( 'url' ) . '/' . Participants_Db::plugin_setting( 'registration_page', '' );

    self::setup_display_columns();

    self::$list_filter = new \PDb_admin_list\filter();

    self::$query = new \PDb_admin_list\query( self::$list_filter );

    // process list form submissions
    new \PDb_admin_list\process();

    /*
     * save the query in a session value so it can be used by the export CSV functionality
     */
    if ( self::user_can_export_csv() ) {
      Participants_Db::$session->set( Participants_Db::$prefix . 'admin_list_query-' . $current_user->ID, self::list_query() );
    }

    // get the $wpdb object
    global $wpdb;

    // set the pagination object
    $current_page = filter_input( INPUT_GET, self::$list_page, FILTER_VALIDATE_INT, array( 'options' => array( 'default' => 1, 'min_range' => 1 ) ) );

    // include the session ID if using the alternate method
    $sess = Participants_Db::plugin_setting_is_true( 'use_session_alternate_method' ) ? '&' . PDb_Session::id_var . '=' . Participants_Db::$session->session_id() : '';

    /**
     * @filter pdb-admin_list_pagination_config
     * @param array of configuration values
     * @return array
     */
    self::$pagination = new PDb_Pagination( Participants_Db::apply_filters( 'admin_list_pagination_config', array(
                'link' => self::prepare_page_link( $_SERVER[ 'REQUEST_URI' ] ) . $sess . '&' . self::$list_page . '=%1$s',
                'page' => $current_page,
                'size' => self::$page_list_limit,
                'total_records' => self::$query->result_count(),
//        'wrap_tag' => '<div class="pdb-list"><div class="pagination"><label>' . _x('Page', 'noun; page number indicator', 'participants-database') . ':</label> ',
//        'wrap_tag_close' => '</div></div>',
                'add_variables' => '#pdb-list-admin',
            ) ) );

    // get the records for this page, adding the pagination limit clause
    self::$participants = $wpdb->get_results( self::$query->query() . ' ' . self::$pagination->getLimitSql(), ARRAY_A );

    if ( PDB_DEBUG ) {
      Participants_Db::debug_log( __METHOD__ . '
  list query: ' . $wpdb->last_query );
    }

    // ok, setup finished, start outputting the form
    // add the top part of the page for the admin
    self::_admin_top();

    // print the sorting/filtering forms
    self::_sort_filter_forms();

    // add the delete and items-per-page controls for the backend
    self::_general_list_form_top();

    // print the main table
    self::_main_table();

    // output the pagination controls
    echo '<div class="pdb-list">' . self::$pagination->links() . '</div>';

    // print the CSV export form (authorized users only)
    if ( self::user_can_export_csv() ) {
      self::_print_export_form();
    }

    // print the plugin footer
    Participants_Db::plugin_footer();
  }

  /**
   * checks if the current user is allowed to export a CSV
   * 
   * @return  bool  true if it is allowed
   */
  public static function user_can_export_csv()
  {
    $csv_role = Participants_Db::plugin_setting_is_true( 'editor_allowed_csv_export' ) ? 'record_edit_capability' : 'plugin_admin_capability';

    return current_user_can( Participants_Db::plugin_capability( $csv_role, 'export csv' ) );
  }

  /**
   * provides a default admin list query
   * 
   * @return string
   */
  public static function default_query()
  {
    global $wpdb;
    return 'SELECT * FROM ' . $wpdb->prefix . 'participants_database p ORDER BY p.date_recorded desc';
  }

  /**
   * provides the last list query with the placeholders removed
   * 
   * @return string
   */
  public static function list_query()
  {
    return self::$query->query();
  }

  /**
   * strips the page number out of the URI so it can be used as a link to other pages
   *
   * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
   *
   * @return string the re-constituted URI
   */
  public static function prepare_page_link( $uri )
  {

    $URI_parts = explode( '?', $uri );

    if ( empty( $URI_parts[ 1 ] ) ) {

      $values = array();
    } else {

      parse_str( $URI_parts[ 1 ], $values );

      // take out the list page number
      unset( $values[ self::$list_page ] );

      /* clear out our filter variables so that all that's left in the URI are 
       * variables from WP or any other source-- this is mainly so query string 
       * page id can work with the pagination links
       */
      $filter_atts = array(
          'search',
          'sortBy',
          'ascdesc',
          'column_sort',
      );
      foreach ( $filter_atts as $att )
        unset( $values[ $att ] );
    }

    return $URI_parts[ 0 ] . '?' . http_build_query( $values );
  }

  /**
   * top section for admin listing
   */
  private static function _admin_top()
  {
    ?>
    <div id="pdb-list-admin"   class="wrap participants_db">
      <?php Participants_Db::admin_page_heading() ?>
      <?php do_action( 'pdb-list_admin_head' ); ?>
      <div id="poststuff">
        <div class="post-body">
          <h2><?php echo Participants_Db::plugin_label( 'list_participants_title' ) ?></h2>
          <?php
        }

        /**
         * prints the sorting and filtering forms
         *
         * @param string $mode determines whether to print filter, sort, both or 
         *                     none of the two functions
         */
        private static function _sort_filter_forms()
        {

          global $post;
          $filter_count = self::$list_filter->list_fiter_count();

          //build the list of columns available for filtering
          $filter_columns = array();
          $group_title = '';

          foreach ( self::filter_columns() as $column ) {

            if ( empty( $column->grouptitle ) ) {
              $column->grouptitle = $column->group;
            }

            if ( $column->grouptitle !== $group_title ) {
              $group_title = $column->grouptitle;
              $filter_columns[ $group_title ] = 'optgroup';
            }

            // add the field name if a field with the same title is already in the list
            $title = Participants_Db::apply_filters( 'translate_string', $column->title );
            $select_title = ( isset( $filter_columns[ $column->title ] ) || strlen( $column->title ) === 0 ) ? $title . ' (' . $column->name . ')' : $title;

            $filter_columns[ $select_title ] = $column->name;
          }
          
          // add the multi-field selection
          $filter_columns = array_merge( $filter_columns, PDb_admin_list\search_field_group::group_selector() );
          
          ?>
          <div class="pdb-searchform">
            <form method="post" id="sort_filter_form" action="<?php echo self::prepare_page_link( $_SERVER[ 'REQUEST_URI' ] ) ?>" >
              <input type="hidden" name="action" value="admin_list_filter">
              <table class="form-table">
                <tbody><tr><td>
                      <?php
                      for ( $i = 0; $i <= $filter_count - 1; $i++ ) :
                        $filter_set = self::get_filter_set( $i );
                        ?>
                        <fieldset class="widefat inline-controls">
                          <?php if ( $i === 0 ): ?>
                            <legend><?php _e( 'Show only records with', 'participants-database' ) ?>:</legend>
                            <?php
                          endif;

                          $element = array(
                              'type' => 'dropdown',
                              'name' => 'search_field[' . $i . ']',
                              'value' => $filter_set[ 'search_field' ],
                              'options' => $filter_columns,
                          );
                          PDb_FormElement::print_element( $element );
                          _ex( 'that', 'joins two search terms, such as in "Show only records with last name that is Smith"', 'participants-database' );
                          $element = array(
                              'type' => 'dropdown',
                              'name' => 'operator[' . $i . ']',
                              'value' => $filter_set[ 'operator' ],
                              'options' => array(
                                  PDb_FormElement::null_select_key() => false,
                                  __( 'is', 'participants-database' ) => '=',
                                  __( 'is not', 'participants-database' ) => '!=',
                                  __( 'contains', 'participants-database' ) => 'LIKE',
                                  __( 'doesn&#39;t contain', 'participants-database' ) => 'NOT LIKE',
                                  __( 'is greater than', 'participants-database' ) => 'gt',
                                  __( 'is less than', 'participants-database' ) => 'lt',
                              ),
                          );
                          PDb_FormElement::print_element( $element );
                          ?>
                          <input id="participant_search_term_<?php echo $i ?>" type="text" name="value[<?php echo $i ?>]" value="<?php echo esc_attr( $filter_set[ 'value' ] ) ?>">
                          <?php
                          if ( $i < $filter_count - 1 ) {
                            echo '<br />';
                            $element = array(
                                'type' => 'radio',
                                'name' => 'logic[' . $i . ']',
                                'value' => $filter_set[ 'logic' ],
                                'options' => array(
                                    __( 'and', 'participants-database' ) => 'AND',
                                    __( 'or', 'participants-database' ) => 'OR',
                                ),
                            );
                          } else {
                            $element = array(
                                'type' => 'hidden',
                                'name' => 'logic[' . $i . ']',
                                'value' => $filter_set[ 'logic' ],
                            );
                          }
                          PDb_FormElement::print_element( $element );
                          ?>

                        </fieldset>
                      <?php endfor ?>
                      <fieldset class="widefat inline-controls">
                        <input class="button button-default" name="submit-button" type="submit" value="<?php echo self::$i18n[ 'filter' ] ?>">
                        <input class="button button-default" name="submit-button" type="submit" value="<?php echo self::$i18n[ 'clear' ] ?>">
                        <div class="widefat inline-controls filter-count">
                          <label for="list_filter_count"><?php _e( 'Number of filters to use: ', 'participants-database' ) ?><input id="list_filter_count" name="list_filter_count" class="number-entry single-digit" type="number" max="5" min="1" value="<?php echo $filter_count ?>"  /></label>
                        </div>
                      </fieldset>
                    </td></tr><tr><td>
                      <fieldset class="widefat inline-controls">
                        <legend><?php _e( 'Sort by', 'participants-database' ) ?>:</legend>
                        <?php
                        $element = array(
                            'type' => 'dropdown',
                            'name' => 'sortBy',
                            'value' => self::$list_filter->value( 'sortBy' ),
                            'options' => $filter_columns,
                        );
                        PDb_FormElement::print_element( $element );

                        $element = array(
                            'type' => 'radio',
                            'name' => 'ascdesc',
                            'value' => strtolower( self::$list_filter->value( 'ascdesc' ) ),
                            'options' => array(
                                __( 'Ascending', 'participants-database' ) => 'asc',
                                __( 'Descending', 'participants-database' ) => 'desc'
                            ),
                        );
                        PDb_FormElement::print_element( $element );
                        ?>
                        <input class="button button-default"  name="submit-button" type="submit" value="<?php echo self::$i18n[ 'sort' ] ?>">
                      </fieldset>
                    </td></tr></tbody></table>
            </form>
          </div>
          <?php
        }

        /**
         * prints the general list form controls for the admin lising: deleting and items-per-page selector
         */
        private static function _general_list_form_top()
        {
          ?>

          <form id="list_form"  method="post">
            <?php PDb_FormElement::print_hidden_fields( array( 'action' => 'list_action' ) ) ?>
            <input type="hidden" id="select_count" value="0" />
            <?php
            /**
             * action pdb-admin_list_form_top
             * @since 1.6
             * 
             * todo: add relevant data to action
             * 
             * good for adding functionality to the admin list
             */
//            do_action(Participants_Db::$prefix . 'admin_list_form_top', $this);
            do_action( Participants_Db::$prefix . 'admin_list_form_top' );
            ?>
            <table class="form-table">
              <tbody>
                <tr>
                  <td>
                    <fieldset class="list-controls">
                      <?php
                      $list_limit = PDb_FormElement::get_element( array(
                                  'type' => 'text-line',
                                  'name' => 'list_limit',
                                  'value' => self::$page_list_limit,
                                  'attributes' => array(
                                      'style' => 'width:2.8em',
                                      'maxLength' => '3'
                                  )
                                      )
                              )
                      ?>
                      <?php printf( __( 'Show %s items per page.', 'participants-database' ), $list_limit ) ?>
                      <?php PDb_FormElement::print_element( array( 'type' => 'submit', 'name' => 'submit-button', 'class' => 'button button-default', 'value' => self::$i18n[ 'change' ] ) ) ?>
                    </fieldset>
                  </td>
                </tr>
                <?php if ( self::user_can_use_with_selected() ) : ?>
                <tr>
                  <td>
                    <fieldset class="list-controls">
                      <?php echo self::with_selected_control(); ?>
                    </fieldset>
                  </td>
                </tr>
                <?php endif ?>
              </tbody>
            </table>
            <?php
          }

          /**
           * prints the main body of the list, including headers
           *
           * @param string $mode determines the print mode: 'noheader' skips headers, (other choices to be determined)
           */
          private static function _main_table( $mode = '' )
          {
            self::list_count_display();
            
            $hscroll = Participants_Db::plugin_setting_is_true( 'admin_horiz_scroll' );
            ?>
            <?php if ( $hscroll ) : ?>
              <div class="pdb-horiz-scroll-scroller">
                <div class="pdb-horiz-scroll-width" style="width: <?php echo count( self::$display_columns ) * 10 ?>em">
                <?php endif ?>
                <table class="wp-list-table widefat fixed pages pdb-list stuffbox" cellspacing="0" >
                  <?php
                  $PID_pattern = '<td><a href="%2$s">%1$s</a></td>';
                  //template for outputting a column
                  $col_pattern = '<td>%s</td>';

                  if ( count( self::$participants ) > 0 ) :

                    if ( $mode != 'noheader' ) :
                      ?>
                      <thead>
                        <tr>
                          <?php self::_print_header_row() ?>
                        </tr>
                      </thead>
                      <?php
                    endif; // table header row
                    // print the table footer row if there is a long list
                    if ( $mode != 'noheader' && count( self::$participants ) > 10 ) :
                      ?>
                      <tfoot>
                        <tr>
                          <?php self::_print_header_row() ?>
                        </tr>
                      </tfoot>
                    <?php endif; // table footer row 
                    ?>
                    <tbody>
                      <?php
                      // output the main list
                      foreach ( self::$participants as $value ) {
                        ?>
                        <tr>
                          <?php // print delete check     ?>
                          <td>
                            <?php if ( self::user_can_use_with_selected() ) : ?>
                              <input type="checkbox" class="delete-check" name="pid[]" value="<?php echo $value[ 'id' ] ?>" />
                            <?php endif ?>
                            <a href="admin.php?page=<?php echo 'participants-database' ?>-edit_participant&amp;action=edit&amp;id=<?php echo $value[ 'id' ] ?>" title="<?php _e( 'Edit', 'participants-database' ) ?>"><span class="dashicons dashicons-edit"></span></a>
                          </td>
                          <?php
                          foreach ( self::$display_columns as $column ) {

                            $field = new PDb_Field_Item( (object) array_merge( (array) $column, array( 'value' => ( isset( $value[ $column->name ] ) ? $value[ $column->name ] : '' ), 'record_id' => $value[ 'id' ], 'module' => 'admin-list' ) ) );
                            $display_value = '';

                            // this is where we place form-element-specific text transformations for display
                            switch ( $field->form_element() ) {

                              case 'image-upload':

                                $image_params = array(
                                    'filename' => $field->value(),
                                    'link' => '',
                                    'mode' => Participants_Db::plugin_setting_is_true( 'admin_thumbnails' ) ? 'image' : 'filename',
                                );

                                // this is to display the image as a linked thumbnail
                                $image = new PDb_Image( $image_params );

                                $display_value = $image->get_image_html();

                                break;

                              case 'file-upload':
                                $display_value = PDb_FormElement::get_field_value_display( $field, true );
                                break;
                              case 'date':
                              case 'timestamp':

                                $column->value = $field->value();
                                $display_value = PDb_FormElement::get_field_value_display( $field, false );
                                break;
                              case 'multi-select-other':
                              case 'multi-checkbox':
                                // multi selects are displayed as comma separated lists

                                $display_value = PDb_FormElement::get_field_value_display( $field, false );

                                //$display_value = is_serialized($value[$column->name]) ? implode(', ', unserialize($value[$column->name])) : $value[$column->name];
                                break;

                              case 'link':

                                $display_value = $field->get_value_display();

                                break;

                              case 'rich-text':

                                if ( $field->has_content() ) {
                                  $display_value = '<span class="textarea">' . $field->get_value_display() . '</span>';
                                }
                                break;

                              case 'text-line':

                                if ( Participants_Db::plugin_setting_is_true( 'make_links' ) ) {
                                  if ( $field->has_content() ) {
                                    $display_value = PDb_FormElement::make_link( $field );
                                  }
                                } else {
                                  //$display_value = $value[$column->name] === '' ? $column->default : esc_html( $value[$column->name] );
                                  $display_value = $field->get_value_display();
                                }
                                break;

                              case 'hidden':

                                $display_value = $field->get_value_display();
                                break;

                              default:

                                $display_value = $field->get_value_display();
                            }

                            if ( $column->name === 'private_id' && Participants_Db::plugin_setting_is_set( 'registration_page' ) ) {
                              printf( $PID_pattern, $display_value, Participants_Db::get_record_link( $display_value ) );
                            } else {
                              printf( $col_pattern, $display_value );
                            }
                          }
                          ?>
                        </tr>
                      <?php } ?>
                    </tbody>

                  <?php else : // if there are no records to show; do this
                    ?>
                    <tbody>
                      <tr>
                        <td><?php _e( 'No records found', 'participants-database' ) ?></td>
                      </tr>
                    </tbody>
                  <?php
                  endif; // participants array
                  ?>
                </table>
                <?php if ( $hscroll ) : ?>
                </div>
              </div>
            <?php endif ?>
          </form>
          <?php
        }

        /**
         * prints the CSV export form
         */
        private static function _print_export_form()
        {
          Participants_Db::$session->clear( 'csv_export_fields' ); // reset the stored export field list #2406
          $base_filename = self::get_admin_user_setting( 'csv_base_filename', Participants_Db::PLUGIN_NAME );
          ?>

          <div class="postbox">
            <div class="inside">
              <h3><?php echo Participants_Db::plugin_label( 'export_csv_title' ) ?></h3>
              <form method="post" class="csv-export">
                <input type="hidden" name="subsource" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
                <input type="hidden" name="action" value="output CSV" />
                <input type="hidden" name="CSV type" value="participant list" />
                <?php
                $suggested_filename = $base_filename . self::filename_datestamp() . '.csv';
                $namelength = round( strlen( $suggested_filename ) * 0.9 );
                ?>
                <fieldset class="inline-controls">
                  <?php _e( 'File Name', 'participants-database' ) ?>:
                  <input type="text" name="filename" value="<?php echo $suggested_filename ?>" size="<?php echo $namelength ?>" />
                  <input type="submit" name="submit-button" value="<?php _e( 'Download CSV for this list', 'participants-database' ) ?>" class="button button-primary" />
                  <label for="include_csv_titles"><input type="checkbox" name="include_csv_titles" value="1"><?php _e( 'Include field titles', 'participants-database' ) ?></label>
                </fieldset>
                <p>
                  <?php _e( 'This will download the whole list of participants that match your search terms, and in the order specified by the sort. The export will include records on all list pages. The fields included in the export are defined in the "CSV" column on the Manage Database Fields page.', 'participants-database' ) ?>
                </p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  /**
   * prints a table header row
   */
  private static function _print_header_row()
  {
    $head_pattern = '
<th class="%2$s" scope="col">
  <span>%1$s%3$s</span>
</th>
';
    $sortable_head_pattern = '
<th class="%2$s" scope="col">
  <span><a href="' . self::sort_link_base_URI() . '&amp;column_sort=%2$s">%1$s%3$s</a></span>
</th>
';

    $sorticon_class = strtolower( self::$list_filter->value( 'ascdesc' ) ) === 'asc' ? 'dashicons-arrow-up' : 'dashicons-arrow-down';
    $sorticon = '<span class="dashicons ' . $sorticon_class . ' sort-icon"></span>';

    // print the "select all" header 
    ?>
    <th scope="col" style="width:3em">
      <?php if ( self::user_can_use_with_selected() ) : ?>
        <?php /* translators: uses the check symbol in a phrase that means "check all"  printf('<span class="checkmark" >&#10004;</span>%s', __('all', 'participants-database'))s */ ?>
        <input type="checkbox" name="checkall" id="checkall" ><span class="dashicons dashicons-edit" style="opacity: 0"></span>
      <?php endif ?>
    </th>
    <?php
    // print the top header row
    foreach ( self::$display_columns as $column ) {
      $title = Participants_Db::apply_filters( 'translate_string', strip_tags( stripslashes( $column->title ) ) );
      $field = Participants_Db::$fields[ $column->name ];
      printf(
              $field->sortable ? $sortable_head_pattern : $head_pattern, str_replace( array( '"', "'" ), array( '&quot;', '&#39;' ), $title ), $column->name, $column->name === self::$list_filter->value( 'sortBy' ) ? $sorticon : ''
      );
    }
  }
  
  /**
   * prints the list count display
   */
  private static function list_count_display()
  {
    ?>
    <h3><?php printf( _n( '%s record found, sorted by: %s.', '%s records found, sorted by: %s.', self::$query->result_count(), 'participants-database' ), self::$query->result_count(), Participants_Db::column_title( self::$list_filter->value( 'sortBy' ) ) ) ?></h3>
    <?php
  }

  /**
   * tells if the current user can utilize the "with selected" functionality
   * 
   * @return bool true if the user is allowed
   */
  private static function user_can_use_with_selected()
  {
    return current_user_can( Participants_Db::plugin_capability( 'record_edit_capability', 'delete participants' ) ) || current_user_can( Participants_Db::plugin_capability( 'record_edit_capability', 'with selected actions' ) );
  }

  /**
   * provides the "with selected" control HTML
   * 
   * @return string HTML
   */
  private static function with_selected_control()
  {
    $with_selection_actions = array();

    // add the approval actions
    $approval_field_name = Participants_Db::apply_filters( 'approval_field', 'approved' );
    if ( PDb_Form_Field_Def::is_field( $approval_field_name ) ) {
      $with_selection_actions = array(
          __( 'approve', 'participants-database' ) => 'approve',
          __( 'unapprove', 'participants-database' ) => 'unapprove',
      );
    }

    // add the delete action
    if ( current_user_can( Participants_Db::plugin_capability( 'record_edit_capability', 'delete participants' ) ) ) {
      $with_selection_actions = array(
          __( 'delete', 'participants-database' ) => 'delete'
              ) + $with_selection_actions;
    }

    /**
     * filter to add additional actions to the with selected selector
     * 
     * @filter pdb-admin_list_with_selected_actions
     * @param array as $title => $action of actions to apply to selected records
     * @return array
     */
    $with_selected_selections = Participants_Db::apply_filters( 'admin_list_with_selected_actions', $with_selection_actions );
    $with_selected_value = array_key_exists( 'with_selected', $_POST ) ? filter_input( INPUT_POST, 'with_selected', FILTER_SANITIZE_STRING ) : self::get_admin_user_setting( 'with_selected' );

    $selector = array(
        'type' => 'dropdown',
        'name' => 'with_selected',
        'value' => $with_selected_value,
        'options' => $with_selected_selections,
    );

    $html = array(
        '<span style="padding-right:20px" >',
        self::$i18n[ 'with_selected' ],
        PDb_FormElement::get_element( $selector ),
        '<input type="submit" name="submit-button" class="button button-default" value="' . self::$i18n[ 'apply' ] . '" id="apply_button"  >',
        '</span>',
    );

    return implode( PHP_EOL, Participants_Db::apply_filters( 'admin_list_with_selected_control_html', $html ) );
  }

  /**
   * builds a column sort link
   * 
   * this just removes the 'column_sort' variable from the URI
   * 
   * @return string the base URI for the sort link
   */
  private static function sort_link_base_URI()
  {
    $uri = parse_url( $_SERVER[ 'REQUEST_URI' ] );
    parse_str( $uri[ 'query' ], $query );
    unset( $query[ 'column_sort' ] );
    return $uri[ 'path' ] . '?' . http_build_query( $query );
  }

  /**
   * sets up the main list columns
   * 
   * @global wpdb $wpdb
   */
  private static function setup_display_columns()
  {
    global $wpdb;
    $sql = '
          SELECT f.name, f.form_element, f.default, f.group, f.title
          FROM ' . Participants_Db::$fields_table . ' f 
          WHERE f.name IN ("' . implode( '","', PDb_Shortcode::get_list_display_columns( 'admin_column' ) ) . '") 
          ORDER BY f.admin_column ASC';

    self::$display_columns = $wpdb->get_results( $sql );
  }

  /**
   * sets the admin list limit value
   */
  private static function set_list_limit()
  {
    $limit_value = self::get_admin_user_setting( 'list_limit', Participants_Db::plugin_setting( 'list_limit' ) );
    $input_limit = filter_input( INPUT_GET, 'list_limit', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
    if ( empty( $input_limit ) ) {
      $input_limit = filter_input( INPUT_POST, 'list_limit', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
    }
    if ( !empty( $input_limit ) ) {
      $limit_value = $input_limit;
    }
    self::$page_list_limit = $limit_value;
    self::set_admin_user_setting( 'list_limit', $limit_value );
  }

  /**
   * gets a search array from the filter
   * 
   * provides a blank array if there is no defined filter at the index given
   * 
   * @param int $index filter array index to get
   * 
   * @return array
   */
  public static function get_filter_set( $index )
  {
    return self::$list_filter->get_set( $index );
  }

  /**
   * supplies an array of display fields
   * 
   * @return array array of field names
   */
  public static function get_display_columns()
  {
    $display_columns = array();
    foreach ( self::$display_columns as $col ) {
      $display_columns[] = $col->name;
    }
    return $display_columns;
  }

  /**
   * gets a user preference
   * 
   * @param string $name name of the setting to get
   * @param string|bool $setting if there is no setting, supply this value instead
   * @return string|bool the setting value or false if not found
   */
  public static function get_admin_user_setting( $name, $setting = false )
  {
    return self::get_user_setting( $name, $setting, self::$user_settings );
  }

  /**
   * sets a user preference
   * 
   * @param string $name
   * @param string|int $value the setting value
   * @return null
   */
  public static function set_admin_user_setting( $name, $value )
  {
    self::set_user_setting( $name, $value, self::$user_settings );
  }

  /**
   * sets a settings transient
   * 
   * @param string $name of the setting value to set
   * @param string|array $value new value of the setting
   * @param string $setting_name of the setting transient
   */
  public static function set_user_setting( $name, $value, $setting_name )
  {

    $settings = array();
    $saved_settings = get_option( $setting_name );
    if ( is_array( $saved_settings ) ) {
      $settings = $saved_settings;
    }
    $settings[ $name ] = $value;
    update_option( $setting_name, $settings );
  }

  /**
   * gets a user setting
   * 
   * @param string $name name of the setting to get
   * @param string|bool $setting if there is no setting, supply this value instead
   * @param string $setting_name the name of the transient to use
   * @return string|bool the setting value or false if not found
   */
  public static function get_user_setting( $name, $setting, $setting_name )
  {
    if ( $settings = (array) get_option( $setting_name ) ) {
      $setting = isset( $settings[ $name ] ) ? $settings[ $name ] : $setting;
    }
    return $setting;
  }

  /**
   * supplies the second part of a download filename
   * 
   * this is usually appended to the end of the base fielname for a plugin-generated file
   * 
   * @return string a filename-compatible datestamp
   */
  public static function filename_datestamp()
  {
    return '-' . str_replace( array( '/', '#', '.', '\\', ', ', ',', ' ' ), '-', PDb_Date_Display::get_date() );
  }

  /**
   * registers admin list events
   * 
   * this is called by the PDb Email Expansion Add-On
   * 
   * @return array of event definitions
   */
  public static function register_admin_list_events( $list )
  {
    $prefix = __( 'PDb Admin List With Selected: ', 'participants-database' );
    $admin_list_events = array(
        'pdb-list_admin_with_selected_delete' => $prefix . __( 'delete', 'participants-database' ),
        'pdb-list_admin_with_selected_approve' => $prefix . __( 'approve', 'participants-database' ),
        'pdb-list_admin_with_selected_unapprove' => $prefix . __( 'unapprove', 'participants-database' ),
        'pdb-list_admin_with_selected_send_signup_email' => $prefix . __( 'send signup email', 'participants-database' ),
    );
    return $list + $admin_list_events;
  }

  /**
   * registers error messages
   * 
   * called on wp_mail_failed hook
   * 
   * @param WP_Error
   * 
   */
  public static function get_email_error_feedback( WP_Error $errors )
  {
    //error_log(__METHOD__.' error: '.print_r($errors,1));
    $pattern = '
<p>%s</p>
';
    foreach ( $errors->get_error_messages() as $code => $message ) {
      self::$error_messages[ $code ] = sprintf( $pattern, esc_html( $message ) );
    }
  }

  /**
   * provides a list columns to use in the list filter and sort
   * 
   * @return array of column definitions
   */
  private static function filter_columns()
  {
    add_filter( 'pdb-access_capability', array( __CLASS__, 'column_filter_user' ), 10, 2 );
    $columns = Participants_db::get_column_atts( 'backend' );
    remove_filter( 'pdb-access_capability', array( __CLASS__, 'column_filter_user' ) );
    return $columns;
  }

  /**
   * filters the available columns by user role
   * 
   * @param $cap the plugin user capability
   * @param string $context
   * @return string the plugin user capability
   */
  public static function column_filter_user( $cap, $context )
  {
    if ( $context === 'access admin field groups' ) {
      $cap = 'edit_others_posts';
    }
    return $cap;
  }

  /**
   * registers error messages
   * 
   * called on wp_mail_failed hook
   * 
   */
  public static function show_email_error_feedback()
  {
    $error_class = 'notice-error';
    $wrap = '
<div class="notice %s is-dismissible ">
	%s
</div>
';
    if ( !empty( self::$error_messages ) ) {
      printf( $wrap, $error_class, implode( "\r", self::$error_messages ) );
    }
  }

  /**
   * sets up the internationalization strings
   */
  private static function _setup_i18n()
  {

    /* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
    self::$i18n = array(
        'delete_checked' => _x( 'Delete Checked', 'submit button label', 'participants-database' ),
        'change' => _x( 'Change', 'submit button label', 'participants-database' ),
        'sort' => _x( 'Sort', 'submit button label', 'participants-database' ),
        'filter' => _x( 'Filter', 'submit button label', 'participants-database' ),
        'clear' => _x( 'Clear', 'submit button label', 'participants-database' ),
        'search' => _x( 'Search', 'search button label', 'participants-database' ),
        'apply' => __( 'Apply' ),
        'with_selected' => _x( 'With selected', 'phrase used just before naming the action to perform on the selected items', 'participants-database' ),
    );
  }

}
