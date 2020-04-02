<?php
/*
 * shows the manage database fields UI
 * 
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    2.1
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( !defined( 'ABSPATH' ) )
  die;

class PDb_Manage_Fields {

  /**
   * @var array translations strings used by this class
   */
  var $i18n;

  /**
   * @var array of group definitions
   */
  private $group_defs;

  /**
   * @var array of field values from the database
   */
  var $fields_data;

  /**
   * @var array of error messages
   */
  var $error_msgs = array();

  /**
   * @var string base URL to the help page
   */
  const help_page = 'https://xnau.com/the-manage-database-fields-page';

  /**
   * instantiate the class
   * 
   * @return null 
   */
  function __construct()
  {
    if ( Participants_Db::current_user_has_plugin_role( 'admin', 'manage fields' ) ) {
      $this->i18n = self::get_i18n();

      $this->setup_group_data();
      $this->setup_field_data();

      $this->print_header();
      $this->print_group_tabs();
      $this->print_footer();
    }
  }

  /**
   * print the page header
   * 
   * @return null
   */
  protected function print_header()
  {
    $top_space = Participants_Db::apply_filters( 'show_edit_submit_top_bar', true ) ? 'top-bar-space' : '';
    ?>
    <div class="wrap participants_db <?php echo $top_space ?>">
      <?php Participants_Db::admin_page_heading() ?>
      <h3><?php _e( 'Manage Database Fields', 'participants-database' ) ?></h3>
      <?php Participants_Db::admin_message(); ?>
      <h4><?php _e( 'Field Groups', 'participants-database' ) ?>:</h4>
      <div id="fields-tabs">
        <ul>
          <?php
          $mask = '<span class="mask"></span>';
          foreach ( $this->group_defs as $group ) {
            echo '<li class="display-' . $group['mode'] . '"><a href="#' . $group['name'] . '" id="tab_' . $group['name'] . '">' . $this->group_title( $group['name'] ) . '</a>' . $mask . '</li>';
          }
          echo '<li class="utility"><a href="#field_groups">' . __( 'Field Groups', 'participants-database' ) . '</a>' . $mask . '</li>';
          echo '<li class="utility"><a href="#help">' . __( 'Help', 'participants-database' ) . '</a>' . $mask . '</li>';
          ?>
        </ul>
        <?php
      }

      /**
       * print all groups edit tab contents
       */
      protected function print_group_tabs()
      {
        foreach ( $this->groups() as $group ) {
          $this->print_group_tab_content( $group );
        }
        $this->print_group_edit_tab_content();
      }

      /**
       * prints an individual group tab content
       * 
       * @param string $group current group name
       */
      protected function print_group_tab_content( $group )
      {
        $hscroll = false; // Participants_Db::plugin_setting_is_true( 'admin_horiz_scroll' );
        // number of rows in the group
        $num_group_rows = count( $this->fields_data[$group] );

        $data_group_id = $num_group_rows > 1 ? $this->fields_data[$group][0]['group_id'] : '';
        ?>
        <div id="<?php echo $group ?>" class="manage-fields-wrap" data-group-id="<?php echo $data_group_id ?>" >
          <h3><?php printf(_x('%s Fields','Title of the field group','participants-database'),$this->group_title( $group )) ?></h3>
          <?php $this->general_fields_control( $group ); ?>
          <?php if ( $hscroll ) : ?>
            <div class="pdb-horiz-scroll-scroller">
              <div class="pdb-horiz-scroll-width">
              <?php endif ?>
              <form id="manage_<?php echo $group ?>_fields" method="post" autocomplete="off"  action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
                <?php if ( Participants_Db::plugin_setting_is_true( 'top_bar_submit', true ) ) : ?>
                <div class="submit top-bar-submit">
                  <span class="field-group-title"><?php echo $this->group_title( $group ) ?></span>
                  <button type="submit" class="button button-primary manage-fields-update" name="action" value="update_fields"  ><?php echo $this->i18n['update fields'] ?></button>
                </div>
                <?php endif ?>
                <?php
                PDb_FormElement::print_hidden_fields( array('group' => $group, 'order' => $this->next_field_order( $group ), 'with_selected' => '' ) );
                wp_nonce_field( PDb_Manage_Fields_Updates::action_key );
                ?>
                <div class="manage-fields" >
                  <section id="<?php echo $group ?>_fields">
                    <?php
                    if ( $num_group_rows < 1 ) { // there are no rows in this group to show
                      ?>
                      <p><?php _e( 'No fields in this group', 'participants-database' ) ?></p>
                      <?php
                    } else {
                      
                      // add the rows of the group
                      foreach ( $this->fields_data[$group] as $database_row ) :
                        
                        if ( ! $this->is_registered_type( $database_row['form_element'] ) ) {
                          // skip field types that are not currently registered
                          continue;
                        }

                        $field_definition_attributes = new PDb_Field_Editor( new PDb_Form_Field_Def( $database_row['name'] ) );
                        ?>
                        <div class="def-fieldset def-line <?php echo $field_definition_attributes->rowclass() ?>" id="db_row_<?php echo $database_row['id'] ?>" data-numid="<?php echo $database_row['id'] ?>" data-groupid="<?php echo $this->fields_data[$group][0]['group_id'] ?>">
                          <?php /* <input type="hidden" name="row_<?php echo $database_row['id'] ?>[order]" value="<?php echo $database_row['order'] ?>" /> */ ?>

                          <?php
                          while ( $control_html = $field_definition_attributes->get_next_control() ) {
                            echo $control_html;
                          }
                          ?>

                        </div>
                        <?php
                      endforeach; // rows
                    } // num group rows 
                    ?>
                  </section>
                </div>
                <?php if ( $hscroll ) : ?>
              </div>
            </div>
          <?php endif ?>
          <p class="submit">
            <button type="submit" class="button button-primary manage-fields-update" name="action" value="update_fields"  ><?php echo $this->i18n['update fields'] ?></button>
          </p>
          </form>
        </div><!-- tab content container -->
        <?php
      }

      /**
       * prints the groups edit tab content
       * 
       * @global object $wpdb
       * @return null 
       */
      protected function print_group_edit_tab_content()
      {
        global $wpdb;
        ?>
        <div id="field_groups" class="manage-fields-wrap">
          <form id="manage_field_groups" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
            <?php if ( Participants_Db::plugin_setting_is_true( 'top_bar_submit', true ) ) : ?>
            <div class="submit top-bar-submit">
              <span class="field-group-title"><?php _e( 'Field Groups', 'participants-database' ) ?></span>
              <button type="submit" class="button button-primary manage-groups-update" name="action" value="update_groups"><?php echo $this->i18n['update groups'] ?></button>
            </div>
            <?php endif ?>

            <?php wp_nonce_field( PDb_Manage_Fields_Updates::action_key ); ?>
            <h3><?php _e( 'Edit / Add / Remove Field Groups', 'participants-database' ) ?></h3>
            <p class="add-group-inputs">
              <button type="submit" class="button button-secondary add-group-submit disabled" name="action" value="add_group" disabled="disabled"><?php echo $this->i18n['add group'] ?></button>
              <?php
              PDb_FormElement::print_element( array(
                  'type' => 'text',
                  'name' => 'group_title',
                  'value' => '',
                  'attributes' => array(
                      'placeholder' => $this->i18n['new group title'] . '&hellip;',
                      'class' => 'add_field'
                  )
                      )
              );
              $next_order = count( $this->group_defs ) + 1;
              PDb_FormElement::print_hidden_fields( array('group_order' => $next_order) );
              ?>
            </p>
            <div class="manage-fields manage-field-groups" >
              <?php
              foreach ( $this->group_defs as $group => $group_def ) {
                
                $group_item = new PDb_Field_Group_Item( $group_def, 'admin-edit' );

                $group_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Participants_Db::$fields_table . ' WHERE `group` = "%s"', $group ) );
                ?>
                <div class="def-fieldset def-line color-group view-mode-<?php echo $group_def['mode'] ?>" data-id="<?php echo $group ?>" data-numid="<?php echo $group_def['id'] ?>" data-groupid="0">
                  <input type="hidden" name="<?php echo $group ?>[status]" id="status_<?php echo $group ?>" value />
                  <div class="field-header">
                    <a id="order_<?php echo $group ?>" class="dragger" href="#"><span class="dashicons dashicons-sort"></span></a>
                    <?php if ( $group_def['name'] !== 'internal' ) : ?>
                      <a href="<?php echo $group_count ?>" data-thing-name="delete_<?php echo $group ?>" class="delete" data-thing="<?php _e( 'group', 'participants-database' ) ?>"><span class="dashicons dashicons-no"></span></a>
                    <?php endif ?>
                    <div id="field_count_<?php echo $group ?>" title="<?php _e( 'field count', 'participants-database' ) ?>"><?php echo $group_count ?></div>
                  </div>
                  <?php
                  foreach ( $group_def as $column => $value ) {

                    $attributes = array();
                    $options = array();
                    $name = '';

                    switch ( $column ) {

                      case 'mode':
                        $type = 'dropdown';
                        $options = array_flip( self::group_display_modes() );
                        break;

                      case 'description':
                        $type = 'text-area';
                        break;

                      case 'name':
                        $type = 'text';
                        $attributes = array('readonly' => 'readonly');
                        break;

                      case 'title':
                        $type = 'text';
                        $attributes = array( 'data-title' => $group_item->title() );
                        break;

                      default:
                        continue 2;
                    }
                    $element_atts = array(
                        'name' => ( empty( $name ) ? $group . '[' . $column . ']' : $name ),
                        'value' => htmlspecialchars( stripslashes( $value ) ),
                        'type' => $type,
                        'attributes' => array_merge( array('id' => 'group-' . $group . '-attribute-' . $column), $attributes ),
                        'options' => $options,
                    );
                    ?>
                    <div class="attribute-control <?php echo $column ?>-attribute">
                      <?php PDb_FormElement::print_element( $element_atts ); ?>
                      <label for="<?php echo $element_atts['attributes']['id'] ?>"><?php echo $this->table_header( $column ) ?></label>
                    </div>
                    <?php
                  }
                  ?>
                </div>
                <?php
              }
              ?>
            </div>
            <p class="submit">
              <button type="submit" class="button button-primary manage-groups-update" name="action" value="update_groups"><?php echo $this->i18n['update groups'] ?></button>
            </p>
          </form>
          <script type="text/javascript">
            jQuery(function ($) {
              var setmode = function (el) {
                el.closest('.def-fieldset').removeClass('view-mode-public view-mode-private view-mode-admin').addClass('view-mode-' + el.val());
              };
              $('.manage-field-groups .mode-attribute select').on('change', function () {
                setmode($(this));
              });
            });
          </script>
        </div><!-- groups tab panel -->
        <?php
      }

      /**
       * prints the fields edit page footer
       */
      protected function print_footer()
      {
        ?>
        <div id="help">
          <?php include Participants_Db::$plugin_path . 'manage_fields_help.php' ?>
        </div>
      </div><!-- ui-tabs container -->
      <div id="dialog-overlay"></div>
      <div id="confirmation-dialog"></div>
      <?php
    }

    /**
     * provides the field edit general controls header
     * 
     * @param string  $group name
     */
    protected function general_fields_control( $group )
    {
      $current_user = wp_get_current_user();
      ?>
      <div class="general_fields_control_header">
        <form id="general_fields_control_<?php echo $group ?>" method="post" autocomplete="off"  action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
          <?php wp_nonce_field( PDb_Manage_Fields_Updates::action_key ); ?>
          <div id="check-all-<?php echo $group ?>" class="check-all">
            <input id="select-all-checkbox-<?php echo $group ?>" type="checkbox" /><label for="select-all-checkbox-<?php echo $group ?>" style="display:none" ><?php echo $this->i18n['all'] ?></label>
          </div>
          <?php if ( $group !== 'internal' ) : ?>
            <button type="button" class="button-secondary add-field showhide" for="add-field-inputs-<?php echo $group ?>"><span class="dashicons dashicons-plus"></span><?php echo $this->i18n['add field'] ?></button>
          <?php endif ?>
          <button type="button" class="button-secondary openclose-all" ><span class="dashicons field-open-icon"></span><?php echo $this->i18n['all'] ?></button>
          <?php if ( $group !== 'internal' ) : ?>
            <div id="add-field-inputs-<?php echo $group ?>" class="button-showhide add-field-inputs manage-fields-actions" style="display:none">
              <h4><?php echo $this->i18n['add field'] ?></h4>
              <label><?php echo $this->i18n['new field title'] ?></label>
              <?php
              PDb_FormElement::print_element( array(
                  'type' => 'hidden',
                  'name' => 'group',
                  'value' => $group,
                      )
              );
              PDb_FormElement::print_element( array(
                  'type' => 'text',
                  'name' => 'title',
                  'value' => '',
                  'attributes' => array(
                      'class' => 'add_field'
                  )
                      )
              );
              PDb_FormElement::print_element( array(
                  'type' => 'hidden',
                  'name' => 'order',
                  'value' => $this->next_field_order( $group ),
                      )
              );
              ?>
              <label><?php echo $this->i18n['new field form element'] ?></label>
              <?php
              PDb_FormElement::print_element( array(
                  'type' => 'dropdown',
                  'options' => array_flip( PDb_FormElement::get_types() ) + array(PDb_FormElement::null_select_key() => false),
                  'name' => 'form_element',
                  'value' => '',
                  'attributes' => array(
                      'class' => 'add_field',
                      'id' => 'pdb-form_element_' . $group,
                  )
                      )
              );
              ?>
              <div class="add-field-submit-wrap">
                <button type="submit" class="button button-primary add-field-submit disabled" name="action" value="add_field" disabled="disabled"  ><?php echo $this->i18n['add field'] ?></button>
                <button class="button button-secondary add-field-submit" name="add-field-cancel" ><?php _e( 'Cancel', 'participants-database' ) ?></button>
              </div>
            </div>
          <?php endif ?>
          <div id="with-selected-control-<?php echo $group ?>" class="with-selected-control" style="display:none">
            <label for="with_selected_action_selection_<?php echo $group ?>"><?php echo $this->i18n['with selected'] ?>: </label>
            <?php
            PDb_FormElement::print_element( array(
                'type' => 'dropdown',
                'options' => array_flip( $this->with_selected_options( $group ) ),
                'name' => 'with_selected_action_selection',
                'value' => PDb_List_Admin::get_user_setting( 'with_selected_selection', '', 'manage_fields' . $current_user->ID ),
                'attributes' => array(
                    'class' => 'with-selected-action-select',
                    'id' => 'with_selected_action_selection_' . $group,
                ),
                    )
            );
            PDb_FormElement::print_element( array(
                'type' => 'dropdown',
                'options' => PDb_Field_Editor::group_options(),
                'name' => 'with_selected_group_assign',
                'value' => $group,
                'attributes' => array(
                    'class' => 'with-selected-group-select',
                    'id' => 'pdb-with_selected_group_assign_' . $group,
                ),
                    )
            );
            ?>
            <button type="button" class="button-secondary apply-with-selected" ><span class="dashicons dashicons-yes"></span><?php echo $this->i18n['apply'] ?></button>
          </div>
        </form>
      </div>
      <?php
    }

    /**
     * provides the "with selected" actions
     * 
     * @param string $group name of the current group
     * @return array as $title => $value
     */
    protected function with_selected_options( $group )
    {
      if ( $group === 'internal' ) {
        // provide a limited list for the internal group
        return array(
            'add_csv' => __( 'Add to CSV', 'participants-database' ),
            'remove_csv' => __( 'Remove from CSV', 'participants-database' ),
        );
      }
      /**
       * @filter pdb-manage_fields_with_selected_actions
       * @param array of tasks as $task => $title
       * @param string name of the current group
       * @return array
       */
      return Participants_Db::apply_filters( 'manage_fields_with_selected_actions', array(
                  'delete' => $this->i18n['delete'],
                  'group' => __( 'Assign Group', 'participants-database' ),
                  'add_csv' => __( 'Add to CSV', 'participants-database' ),
                  'remove_csv' => __( 'Remove from CSV', 'participants-database' ),
                  'add_signup' => __( 'Add to Signup', 'participants-database' ),
                  'remove_signup' => __( 'Remove from Signup', 'participants-database' ),
              ) );
    }

    /**
     * sets up the groups management iterator
     * 
     * @return null 
     */
    protected function setup_group_data()
    {
      $this->group_defs = array();
      foreach ( Participants_Db::get_groups() as $group => $defs ) {
        // mode is the 1.8.5 group visibility mode
        // this converts the previous visibility settings to the new mode
        if ( !isset( $defs['mode'] ) || empty( $defs['mode'] ) ) {
          switch ( true ) {
            case $defs['admin'] == 0 && $defs['display'] == 0:
              // we set to 'public' not 'private' #1817
              $defs['mode'] = 'public';
              break;
            case $defs['admin'] == 0 && $defs['display'] == 1:
            case $defs['admin'] == 1 && $defs['display'] == 1:
              $defs['mode'] = 'public';
              break;
            case $defs['admin'] == 1 && $defs['display'] == 0:
              $defs['mode'] = 'admin';
          }
          $this->fix_group_mode( $defs );
        }
        unset( $defs['admin'], $defs['display'] ); // not needed
        // only include groups with main database display modes
        if ( in_array( $defs['mode'], array_keys( self::group_display_modes() ) ) ) {
          $this->group_defs[$group] = $defs;
        }
      }
    }

    /**
     * provides an array of group names
     * 
     * @return array
     */
    private function groups()
    {
      return array_keys( $this->group_defs );
    }

    /**
     * sets up the fields data
     * 
     * @global wpdb $wpdb
     * @return null 
     */
    protected function setup_field_data()
    {
      global $wpdb;
      // get an array with all the defined fields
      foreach ( $this->groups() as $group ) {

        $sql = "SELECT f.id,f.name,f.form_element,f.order,g.id AS group_id,g.name AS group_name FROM " . Participants_Db::$fields_table . ' f JOIN ' . Participants_Db::$groups_table . ' g ON f.group = g.name WHERE `group` = "' . $group . '" ORDER BY f.order ';

        $this->fields_data[$group] = $wpdb->get_results( $sql, ARRAY_A );
      }
    }
    
    /**
     * tells if the field element type is registered
     * 
     * @param string $form_element
     * @return bool
     */
    protected function is_registered_type ( $form_element )
    {
      // timestamp is a special case: visible in the editor but nor chooseable otherwise
      return PDb_FormElement::is_form_element($form_element) || $form_element === 'timestamp';
    }

    /**
     * provides the array of group modes
     * 
     * for use in the groups edit mode dropdown
     * 
     * @return array
     */
    public static function group_display_modes()
    {
      /**
       * @filter pdb-group_display_modes
       * @param array as $name => $title
       * @return array
       */
      return Participants_Db::apply_filters( 'group_display_modes', array(
                  'public' => __( 'Public', 'participants-database' ),
                  'private' => __( 'Private', 'participants-database' ),
                  'admin' => _x( 'Admin', 'short form of "administrator"', 'participants-database' ),
              ) );
    }

    /**
     * provides the title string for a group
     * 
     * @param name $group name
     * @return string title
     */
    protected function group_title( $group )
    {
      $group_title = Participants_Db::apply_filters( 'translate_string', stripslashes($this->group_defs[$group]['title']) );
      /**
       * @since 1.7.3.2
       * group titles on tabs and such are limited to 30 characters to preserve layout
       */
      $title_limit = Participants_Db::apply_filters( 'admin_group_title_length_limit', 30 );
      return empty( $group_title ) || strlen( $group_title ) > $title_limit ? ucwords( str_replace( '_', ' ', $group ) ) : $group_title;
    }

    /**
     * provides the group order value
     * 
     * @param string $group name
     * @return string order number
     */
    private function next_field_order( $group )
    {
      // number of rows in the group
      $num_group_rows = count( $this->fields_data[$group] );

      return $num_group_rows > 1 ? $this->fields_data[$group][$num_group_rows - 1]['order'] + 1 : 1;
    }

    /**
     * sets up the edit table headers
     * 
     * @param string $string the header text
     * @return string
     */
    function table_header( $string )
    {

      // check for a translated string, use it if found
      $string = isset( $this->i18n[$string] ) ? $this->i18n[$string] : $string;

      return str_replace( array('_'), array(" "), $string );
    }

    /**
     * fixes an old format group definition by storing the mode value
     * 
     * @global wpdb $wpdb
     * @param array $group array of group properties
     */
    private function fix_group_mode( $group )
    {
      global $wpdb;
      $wpdb->update( Participants_Db::$groups_table, array('mode' => $group['mode']), array('name' => $group['name']) );
    }

    /**
     * provides an array of translation strings
     * 
     * @return array of translation strings
     */
    public static function get_i18n()
    {
      return array(
          /* translators: these strings are used in logic matching, please test after translating in case special characters cause problems */
          'update fields' => __( 'Update Fields', 'participants-database' ),
          'update groups' => __( 'Update Groups', 'participants-database' ),
          'add field' => __( 'Add Field', 'participants-database' ),
          'add group' => __( 'Add Group', 'participants-database' ),
          'group' => __( 'group', 'participants-database' ),
          'field' => __( 'field', 'participants-database' ),
          'new field title' => __( 'new field title', 'participants-database' ),
          'new field form element' => __( 'new field form element', 'participants-database' ),
          'new group title' => __( 'new group title', 'participants-database' ),
          'fields' => _x( 'Fields', 'column name', 'participants-database' ),
          'Group' => _x( 'Group', 'column name', 'participants-database' ),
          'order' => _x( 'Order', 'column name', 'participants-database' ),
          'name' => _x( 'Name', 'column name', 'participants-database' ),
          'title' => _x( 'Title', 'column name', 'participants-database' ),
          'default' => _x( 'Default Value', 'column name', 'participants-database' ),
          'help_text' => _x( 'Help Text', 'column name', 'participants-database' ),
          'form_element' => _x( 'Form Element', 'column name', 'participants-database' ),
          'values' => _x( 'Values', 'column name', 'participants-database' ),
          'validation' => _x( 'Validation', 'column name', 'participants-database' ),
          'display_column' => str_replace( ' ', '<br />', _x( 'Display Column', 'column name', 'participants-database' ) ),
          'admin_column' => str_replace( ' ', '<br />', _x( 'Admin Column', 'column name', 'participants-database' ) ),
          'sortable' => _x( 'Sortable', 'column name', 'participants-database' ),
          'CSV' => _x( 'CSV', 'column name, acronym for "comma separated values"', 'participants-database' ),
          'persistent' => _x( 'Persistent', 'column name', 'participants-database' ),
          'signup' => _x( 'Signup', 'column name', 'participants-database' ),
          'readonly' => _x( 'Read Only', 'column name', 'participants-database' ),
          'admin' => _x( 'Admin', 'column name', 'participants-database' ),
          'delete' => _x( 'Delete', 'column name', 'participants-database' ),
          'display' => _x( 'Display', 'column name', 'participants-database' ),
          'description' => _x( 'Description', 'column name', 'participants-database' ),
          'attributes' => _x( 'Attributes', 'column name', 'participants-database' ),
          'options' => _x( 'Options', 'column name', 'participants-database' ),
          'validation_message' => _x( 'Validation Message', 'column name', 'participants-database' ),
          'with selected' => _x( 'With Selected', 'button label', 'participants-database' ),
          'apply' => _x( 'Apply', 'button label', 'participants-database' ),
          'all' => _x( 'All', 'select all button label', 'participants-database' ),
          'mode' => _x( 'View Mode', 'label for the view mode selector', 'participants-database' ),
      );
    }

  }
  