<?php

/**
 * create the UI for managing columns for both the admin list and the default frontend list
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2018  xnau webdesign
 * @license    GPL3
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */
class PDb_Manage_List_Columns {

  /**
   * @var string name of the ajax action
   */
  const action = 'manage_list_columns';

  /**
   * shows the UI screen
   */
  public static function show_ui()
  {
    $ui = new self();
    $ui->CSS();
    wp_add_inline_script( 'pdb-admin', $ui->js() );
    $ui->display();
  }

  /**
   * handles the AJAX submission
   */
  public static function process_request()
  {
    $ui = new self();
    switch ( filter_input( INPUT_POST, 'group', FILTER_SANITIZE_STRING ) ) {
      case 'publicfields':
        $ui->set_column_config( $_POST['fieldlist'], 'public' ); // this is sanitized later
        break;
      case 'adminfields':
        $ui->set_column_config( $_POST['fieldlist'], 'admin' );
        break;
    }
    wp_die();
  }

  /**
   * update the fields db
   * 
   * @global wpdb $wpdb
   * @param array $update_list ordered list of field names to configure as $fieldname => $order
   * @param string $type admin or public
   */
  private function set_column_config( $update_list, $type )
  {
    global $wpdb;

    $column = $this->list_column( $type );

    $setlist = array();
    foreach ( $this->fieldlist( $update_list ) as $field => $order ) {
      $setlist[] = ' WHEN "' . $field . '" THEN ' . $order;
    }

    $sql = 'UPDATE ' . Participants_Db::$fields_table . ' SET ' . $column . ' = CASE name ' . PHP_EOL . implode( PHP_EOL, $setlist ) . PHP_EOL . ' END';

    $wpdb->query( $sql );
  }

  /**
   * provides a list of all fields with order values
   * 
   * the submitted list is sanitized in this func
   * 
   * @param array $update_list ordered list of fieldnames to configure
   * @return array as $fieldname => $order
   */
  private function fieldlist( $update_list )
  {
    $fieldlist = $this->field_reset_array();

    if ( count( $update_list ) === 0 ) {
      return $fieldlist;
    }

    foreach ( $update_list as $i => $rawname ) {
      $fieldname = filter_var( $rawname, FILTER_SANITIZE_STRING );
      if ( isset( $fieldlist[$fieldname] ) ) { // check against list of defined fields before adding
        $fieldlist[$fieldname] = filter_var( $i, FILTER_SANITIZE_NUMBER_INT ) + 1;
      }
    }
    return $fieldlist;
  }

  /**
   * provides an array of all fields given a zero value
   * 
   * this provides a reset value for all fields not included in the configuration
   * 
   * @return array
   */
  private function field_reset_array()
  {
    $reset_array = array();
    foreach ( array_keys( Participants_Db::$fields ) as $fieldname ) {
      $reset_array[$fieldname] = 0;
    }
    return $reset_array;
  }

  /**
   * provides a list of fields available to the admin list display
   * 
   * @return array of data objects
   */
  private function admin_field_list()
  {
    $where = 'WHERE v.form_element NOT IN ("' . implode( '","', $this->excluded_form_elements() ) . '")';
    return $this->_field_list( $where );
  }

  /**
   * provides a list of fields available to the frontend
   * 
   * @return array of data objects
   */
  private function public_field_list()
  {
    $where = 'WHERE v.form_element NOT IN ("' . implode( '","', $this->excluded_form_elements() ) . '")';
    return $this->_field_list( $where );
  }

  /**
   * provides a list of excluded form elements
   * 
   * @return array
   */
  private function excluded_form_elements()
  {
    /**
     * @filter pdb-list_columns_excluded_form_elements
     * @param array of form element names
     * @return array
     */
    return Participants_Db::apply_filters( 'list_columns_excluded_form_elements', array('captcha') );
  }

  /**
   * provides the list of source fields
   * 
   * @param string $type 'admin or 'public'
   * @return array of data objects
   */
  private function source_fields( $type )
  {
    $column = $this->list_column( $type );

    $list = array();
    foreach ( $this->{$type . '_field_list'}() as $field ) {
      if ( $field->{$column} == '0' ) {
        $field->title = $field->title === '' ? $field->name : Participants_Db::apply_filters( 'translate_string', $field->title );
        $list[$field->sortorder] = $field; // $field->id
      }
    }
    ksort( $list );
    
    return $list;
  }

  /**
   * provides the list of fields that have been configured to appear
   * 
   * @param string $typw 'admin or 'public'
   * @return array of data objects
   */
  private function configured_fields( $type )
  {
    $column = $this->list_column( $type );

    $list = array();
    foreach ( $this->{$type . '_field_list'}() as $field ) {
      if ( $field->{$column} != '0' ) {
        $field->title = Participants_Db::apply_filters( 'translate_string', $field->title );
        $list[intval( $field->{$column} )] = $field;
      }
    }
    ksort( $list );
    return $list;
  }

  /**
   * provides a field list from the database
   * 
   * @global wpdb $wpdb
   * @param string $where the where clause
   * @return array of data objects
   */
  private function _field_list( $where )
  {
    global $wpdb;

    $sql = 'SELECT v.id,v.name,v.title,v.display_column,v.admin_column,v.form_element,v.group,g.title AS grouptitle,((g.order * 10000) + v.order) AS sortorder FROM ' . Participants_Db::$fields_table . ' v INNER JOIN ' . Participants_Db::$groups_table . ' g ON v.group = g.name ' . $where . ' AND g.mode IN ("' . implode( '","', array_keys( PDb_Manage_Fields::group_display_modes() ) ) . '") ORDER BY sortorder';

    $result = $wpdb->get_results( $sql, OBJECT_K );

    return $result;
  }

  /**
   * provides the order column name for the given type of list
   * 
   * @param string $type 'admin' or 'public'
   * @return string
   */
  private function list_column( $type )
  {
    return $type === 'admin' ? 'admin_column' : 'display_column';
  }

  /**
   * prints the list columns management page
   */
  private function display()
  {
    ?>
    <div class="wrap participants_db">
      <?php Participants_Db::admin_page_heading() ?>
      <h3><?php echo Participants_Db::plugin_label( 'manage_list_columns' ) ?></h3>
      <?php Participants_Db::admin_message(); ?>
      <p><?php _e( 'Drag the fields you want shown from the "Available Fields" area to the "List Columns" area below. The fields can be re-ordered or removed from the List Columns area.', 'participants-database' ) ?>
      </p>
      <div class='column-setup-pair' id="publicfields">
        <h3><?php _e( 'Public List Column Setup', 'participants-database' ) ?></h3>
        <p><?php _e( 'Set up the columns for list displays using the [pdb_list] shortcode.', 'participants-database' ) ?></p>
        <div class='available-fields'>
          <p><?php _e( 'Available Fields:', 'participants-database' ) ?></p>
          <ul id="pubfields-source" class='field-list fields-sortable'>
            <?php
            $group = '';
            foreach ( $this->source_fields( 'public' ) as $field ) {
              if ( $field->group !== $group ) {
                $group = $field->group;
                $field->class = 'newgroup';
                echo '<li class="break"></li>';
              }
              echo $this->field_item( $field );
            }
            ?>
          </ul>
        </div>
        <div class='columns-setup'>
          <h3><?php _e( 'List Columns', 'participants-database' ) ?></h3>
          <ul id="pubfields-chosen" class='field-list columnsetup fields-sortable'>
            <?php
            foreach ( $this->configured_fields( 'public' ) as $field ) {
              echo $this->field_item( $field );
            }
            ?>
          </ul>
        </div>
      </div>
      <div class='column-setup-pair' id="adminfields">
        <h3><?php _e( 'Admin List Column Setup', 'participants-database' ) ?></h3>
        <p><?php _e( 'Set up the columns for list displays on the List Participants admin page.', 'participants-database' ) ?></p>
        <div class='available-fields'>
          <p><?php _e( 'Available Fields:', 'participants-database' ) ?></p>
          <ul id="adminfields-source" class='field-list fields-sortable'>
            <?php
            $group = '';
            foreach ( $this->source_fields( 'admin' ) as $field ) {
              if ( $field->group !== $group ) {
                $group = $field->group;
                $field->class = 'newgroup';
                echo '<li class="break"></li>';
              }
              echo $this->field_item( $field );
            }
            ?>
          </ul>
        </div>
        <div class='columns-setup'>
          <h3><?php _e( 'List Columns', 'participants-database' ) ?></h3>
          <ul id="adminfields-chosen" class='field-list columnsetup fields-sortable'>
            <?php
            foreach ( $this->configured_fields( 'admin' ) as $field ) {
              echo $this->field_item( $field );
            }
            ?>
          </ul>
        </div>
      </div>
    </div>
    <?php
  }

  /**
   * provides the individual field item HTML
   * 
   * @param object $field
   * @return string HTML
   */
  private function field_item( $field )
  {
    return sprintf( '<li class="ui-state-default %s" data-id="%s" data-fieldname="%s" data-order="%s">%s</li>', 'color-' . $this->colorclass( $field ), $field->id, $field->name, $field->sortorder, $field->title );
  }

  /**
   * provides the javascript
   * 
   */
  private function js()
  {
    ?>
    <script>
      jQuery(document).ready(function ($) {
        var columngroup;
        var itemsort = function (a,b) {
          return +a.dataset.order - +b.dataset.order;
        };
        $(".fields-sortable").sortable({
          placeholder : "ui-state-highlight",
          start : function (event, ui) {
            columngroup = ui.item.closest('.column-setup-pair');
          },
          stop : function (event, ui) {
            var fieldlist = columngroup.find('.columns-setup li').map(function () {
              return $(this).data('fieldname');
            }).get();
            var data = {
              'action' : "<?php echo self::action ?>",
              'group' : columngroup.attr('id'),
              'fieldlist' : fieldlist
            };
            $.post(ajaxurl, data);
            var sourcelist = columngroup.find('.available-fields ul.field-list');
            sourcelist.find('li')
                    .sort(itemsort)
                    .appendTo(sourcelist);
          },
        }).disableSelection();
        $('#publicfields .fields-sortable').sortable("option", "connectWith", "#publicfields .fields-sortable");
        $('#adminfields .fields-sortable').sortable("option", "connectWith", "#adminfields .fields-sortable");
        $('.available-fields ul.field-list').each(function () {
          $(this).find('li')
                  .sort(itemsort)
                  .appendTo($(this));
        });
      });
    </script>
    <?php
  }

  /**
   * prints the page CSS
   */
  private function CSS()
  {
    ?>
    <style>
      .pdb-jquery-ui .column-setup-pair {
        padding: 10px;
        border: 1px solid #ccc;
        margin: 0 0 1em 0;
      }

      .pdb-jquery-ui .field-list {
        display: flex;
        flex-wrap: wrap;
        padding: 5px;
        height: auto;
        background-color: white;
        min-height: 1.7em;
      }

      .pdb-jquery-ui .field-list.fields-sortable .ui-state-highlight {
        height: 1.1rem;
        background-color: transparent;
        border: 1px dashed grey;
      }

      .pdb-jquery-ui .field-list li {
        cursor: move;
        display: inline-block;
        padding: 5px 10px;
        border-radius: 5px;
        -moz-border-radius: 5px;
        /*        background: #fff;*/
        margin: 5px;
        vertical-align: bottom;
        border-width: 2px;
        border-style: solid;
        /*border: 1px solid #ccc;*/
      }
      .pdb-jquery-ui .field-list li.break {
        flex-basis: 100%;
        height: 0;
        padding: 0;
        border: none;
        border-top: 2px dashed rgba(0,0,0,0.1);
        margin: 5px 0;
      }
      .pdb-jquery-ui .field-list li.break:first-of-type {
        margin: 0;
        border: none;
      }

      .pdb-jquery-ui .field-list.columnsetup li {
        /*        background-color: #f1f1f1;*/
      }

      .pdb-jquery-ui .columns-setup {
        background: #e9e9e9;
        border-radius: 4px;
        -moz-border-radius: 4px;
        padding: 13px 18px;
        margin: 18px 0 0 0;
      }

      .pdb-jquery-ui .columns-setup h3 {
        font-size: 13px;
        margin: 0;
      }

    </style>
    <?php
  }

  /**
   * provides a color class string for a field
   * 
   * @param PDb_Field_Item $field
   * @return string
   */
  private function colorclass( $field )
  {
    $baseclass = isset($field->class) ? $field->class : '';
    switch ( $field->form_element ) {

      case 'text-line':
      case 'text-area':
      case 'rich-text':
      case 'password':
        $colorclass = 'text';
        break;

      case 'checkbox':
      case 'radio':
      case 'dropdown':
      case 'dropdown-other':
      case 'multi-checkbox':
      case 'multi-dropdown':
      case 'select-other':
      case 'multi-select-other':
        $colorclass = 'selector';
        break;

      case 'date':
      case 'numeric':
      case 'decimal':
      case 'currency':
        $colorclass = 'numeric';
        break;

      case 'link':
      case 'image-upload':
      case 'file-upload':
        $colorclass = 'upload';
        break;

      case 'hidden':
        $colorclass = 'utility';
        break;

      case 'captcha':
        $colorclass = 'captcha';
        break;

      case 'placeholder':

        $colorclass = 'utility';
        break;

      case 'timestamp':

        $colorclass = 'numeric';
        break;

      default:

        /**
         * sets the color classname for the elemenmt
         * 
         * @filter pdb-{$form_element_name}_form_element_colorclass
         * 
         * @param string default classname
         * @return string classname
         */
        $colorclass = Participants_Db::apply_filters( $field->form_element . '_form_element_colorclass', 'custom' );
    }

    return $colorclass . ' ' . $baseclass;
  }

}
