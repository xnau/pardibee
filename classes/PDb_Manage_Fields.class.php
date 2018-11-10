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
 * @version    1.12
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( !defined( 'ABSPATH' ) )
  die;
if ( !Participants_Db::current_user_has_plugin_role( 'admin', 'manage fields' ) )
  exit;

class PDb_Manage_Fields {

  /**
   * @var array translations strings used by this class
   */
  var $i18n;

  /**
   * @var array all defined groups
   */
  var $groups;

  /**
   * @var array of group title strings
   */
  var $group_titles;

  /**
   * @var array of group values
   */
  var $group_values;

  /**
   * @var array of field values from the database
   */
  var $fields_data;

  /**
   * @var array of error messages
   */
  var $error_msgs = array();

  /**
   * instantiate the class
   * 
   * @return null 
   */
  function __construct()
  {
    $this->i18n = self::get_i18n();

    $this->set_groups();
    $this->setup_group_edit_values();

    $this->print_header();
    $this->print_group_tabs();
    $this->print_footer();
  }

  /**
   * print the edit form header
   * 
   * @return null
   */
  protected function print_header()
  {
    ?>
    <div class="wrap participants_db">
      <?php Participants_Db::admin_page_heading() ?>
      <h3><?php _e( 'Manage Database Fields', 'participants-database' ) ?></h3>
      <?php Participants_Db::admin_message(); ?>
      <h4><?php _e( 'Field Groups', 'participants-database' ) ?>:</h4>
      <div id="fields-tabs">
        <ul>
          <?php
          $mask = '<span class="mask"></span>';
          foreach ( $this->groups as $group ) {
            echo '<li><a href="#' . $group . '" id="tab_' . $group . '">' . $this->group_titles[$group] . '</a>' . $mask . '</li>';
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
        foreach ( $this->groups as $group ) {
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
        $internal_group = $group === 'internal';
        $hscroll = Participants_Db::plugin_setting_is_true( 'admin_horiz_scroll' );

        // number of rows in the group
        $num_group_rows = count( $this->fields_data[$group] );

        $last_order = $num_group_rows > 1 ? $this->fields_data[$group][$num_group_rows - 1]['order'] + 1 : 1;
        ?>
        <div id="<?php echo $group ?>" class="manage-fields-wrap" data-group-id="<?php echo $this->fields_data[$group][0]['group_id'] ?>" >
          <h3><?php echo $this->group_titles[$group], ' ', $this->i18n['fields'] ?></h3>
          <?php
          if ( !$internal_group )
            $this->general_fields_control( $group );
          ?>
          <?php if ( $hscroll ) : ?>
            <div class="pdb-horiz-scroll-scroller">
              <div class="pdb-horiz-scroll-width">
              <?php endif ?>
              <form id="manage_<?php echo $group ?>_fields" method="post" autocomplete="off"  action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
                <?php
                PDb_FormElement::print_hidden_fields( array('group' => $group, 'order' => $last_order) );
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

                        $field_definition_attributes = new PDb_Form_Element_Def( new PDb_Form_Field_Def( $database_row['name'] ) );
                        ?>
                        <div class="def-fieldset def-line <?php echo $field_definition_attributes->rowclass() ?> editor-open" id="db_row_<?php echo $database_row['id'] ?>">

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
              $next_order = count( $this->group_values ) + 1;
              PDb_FormElement::print_hidden_fields( array('group_order' => $next_order) );
              ?>
            </p>
            <table class="wp-list-table widefat fixed manage-fields manage-field-groups" >
              <thead>
                <tr>
                  <th scope="col" class="fields vertical-title"><span><?php echo $this->table_header( __( 'fields', 'participants-database' ) ) ?></span></th>
                  <th scope="col" class="delete vertical-title"><span><?php echo $this->table_header( __( 'delete', 'participants-database' ) ) ?></span></th>
                  <?php
                  foreach ( current( $this->group_values ) as $column => $value ) {

                    $column_class = in_array( $column, array('order', 'admin', 'display') ) ? $column . ' vertical-title' : $column;
                    ?>
                    <th scope="col" class="<?php echo $column_class ?>"><span><?php echo $this->table_header( $column ) ?></span></th>
                    <?php
                  }
                  ?>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ( $this->group_values as $group => $group_values ) {
                  //  if ($group == 'internal')
                  //    continue;

                  $group_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Participants_Db::$fields_table . ' WHERE `group` = "%s"', $group ) );
                  ?>
                  <tr class="def-line">
                    <td id="field_count_<?php echo $group ?>"><?php echo $group_count ?></td>
                    <td>
                      <a href="<?php echo $group_count ?>" data-thing-name="delete_<?php echo $group ?>" class="delete" data-thing="<?php _e( 'group', 'participants-database' ) ?>"><span class="dashicons dashicons-no"></span></a>
                    </td>
                    <?php
                    foreach ( $group_values as $column => $value ) {

                      $attributes = array();
                      $options = array();
                      $name = '';

                      switch ( $column ) {

                        case 'display':
                        case 'admin':
                          $attributes = array('style' => 'width:20px');
                          $type = 'checkbox';
                          $options = array(1, 0);
                          break;

                        case 'description':
                          $type = 'text-area';
                          break;

                        case 'order':
                          $attributes = array('style' => 'width:30px');
                          $name = 'order_' . $group;
                          $type = 'drag-sort';
                          break;

                        case 'name':
                          $type = 'text';
                          $attributes = array('readonly' => 'readonly');

                        default:
                          $type = 'text';
                      }
                      $element_atts = array(
                          'name' => ( empty( $name ) ? $group . '[' . $column . ']' : $name ),
                          'value' => htmlspecialchars( stripslashes( $value ) ),
                          'type' => $type,
                      );
                      if ( !empty( $attributes ) )
                        $element_atts['attributes'] = $attributes;
                      if ( !empty( $options ) )
                        $element_atts['options'] = $options;
                      ?>
                      <td class="<?php echo $column ?>-attribute"><?php PDb_FormElement::print_element( $element_atts ); ?></td>
                      <?php
                    }
                    ?>
                  </tr>
                  <?php
                }
                ?>
              </tbody>
            </table>
            <p class="submit">
              <button type="submit" class="button button-primary manage-groups-update" name="action" value="update_groups"><?php echo $this->i18n['update groups'] ?></button>
            </p>
          </form>
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
      ?>
      <div class="general_fields_control_header">
        <form id="general_fields_control_<?php echo $group ?>" method="post" autocomplete="off"  action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
          <?php wp_nonce_field( PDb_Manage_Fields_Updates::action_key ); ?>
        <div id="check-all-<?php echo $group ?>" class="check-all">
          <input id="select-all-checkbox-<?php echo $group ?>" type="checkbox" /><label for="select-all-checkbox-<?php echo $group ?>" style="display:none" ><?php echo $this->i18n['all'] ?></label>
        </div>
        <button type="button" class="button-secondary add-field showhide" for="add-field-inputs-<?php echo $group ?>"><span class="dashicons dashicons-plus"></span><?php echo $this->i18n['add field'] ?></button>
        <button type="button" class="button-secondary openclose-all" ><span class="dashicons dashicons-arrow-right"></span><?php echo $this->i18n['all'] ?></button>
        <div id="add-field-inputs-<?php echo $group ?>" class="button-showhide add-field-inputs manage-fields-actions">
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
          ?>
          <label><?php echo $this->i18n['new field form element'] ?></label>
          <?php
          PDb_FormElement::print_element( array(
              'type' => 'dropdown',
              'options' => array_flip( PDb_FormElement::get_types() ) + array('null_select' => false),
              'name' => 'form_element',
              'value' => '',
              'attributes' => array(
                  'class' => 'add_field'
              )
                  )
          );
          ?>
          <button type="submit" class="button button-primary add-field-submit disabled" name="action" value="add_field" disabled="disabled"  ><?php echo $this->i18n['add field'] ?></button>
        </div>
        <div id="with-selected-control-<?php echo $group ?>" class="with-selected-control">
          <label for="with_selected_action_selection_<?php echo $group ?>"><?php echo $this->i18n['with selected'] ?> : </label>
          <?php
          PDb_FormElement::print_element( array(
              'type' => 'dropdown',
              'options' => array_flip( $this->with_selected_options() ),
              'name' => 'with_selected_action_selection',
              'value' => '',
              'attributes' => array(
                  'class' => 'with-selected-action-select',
                  'id' => 'with_selected_action_selection_' . $group,
              ),
                  )
          );
          PDb_FormElement::print_element( array(
              'type' => 'dropdown',
              'options' => PDb_Form_Element_Def::group_options(),
              'name' => 'with_selected_group_assign',
              'value' => $group,
              'attributes' => array(
                  'class' => 'with-selected-group-select',
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
     * @return array as $title => $value
     */
    protected function with_selected_options()
    {
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
     * sets up the group values
     * 
     * properties set here: $groups, $fields_data, $group_titles
     * 
     * @global object $$wpdb
     * @return null 
     */
    protected function set_groups()
    {
      global $wpdb;
      // get the defined groups
      $this->groups = Participants_Db::get_groups( 'name' );
      // get an array with all the defined fields
      foreach ( $this->groups as $group ) {

        $sql = "SELECT f.id,f.name,f.order,g.id AS group_id,g.name AS group_name FROM " . Participants_Db::$fields_table . ' f JOIN '.Participants_Db::$groups_table.' g ON f.group = g.name WHERE `group` = "' . $group . '" ORDER BY f.order ';
        $this->fields_data[$group] = $wpdb->get_results( $sql, ARRAY_A );

        $group_title = $wpdb->get_var( 'SELECT `title` FROM ' . Participants_Db::$groups_table . ' WHERE `name` = "' . $group . '"' );
        /**
         * @since 1.7.3.2
         * group titles on tabs and such are limited to 30 characters to preserve layout
         */
        $title_limit = Participants_Db::apply_filters( 'admin_group_title_length_limit', 30 );
        $this->group_titles[$group] = empty( $group_title ) || strlen( $group_title ) > $title_limit ? ucwords( str_replace( '_', ' ', $group ) ) : $group_title;
      }
    }

    /**
     * sets up the groups management iterator
     * 
     * @return null 
     */
    protected function setup_group_edit_values()
    {
      $this->group_values = Participants_Db::get_groups( '`order`,`display`,`admin`,`name`,`title`,`description`' );
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
      );
    }

  }
  