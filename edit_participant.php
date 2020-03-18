<?php
/**
 * this file is called by the admin menu item, also a link in the admin record list
 * 
 * submission processing happens in Participants_Db::process_page_request on the
 * admin_init action
 * 
 * @version 1.2
 *
 */
if ( !defined( 'ABSPATH' ) )
  die;

// clear out this unneeded value #2331
Participants_Db::$session->clear('form_status');

$participant_id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1), 'flags' => FILTER_NULL_ON_FAILURE) );

if ( !Participants_Db::current_user_has_plugin_role( 'editor', ( $participant_id === false ? 'admin add ' : 'admin edit ' ) . 'record' ) ) {
  exit;
}

if ( false === $participant_id ) {

  $action = 'insert';
  $page_title = Participants_Db::plugin_label( 'add_record_title' );
  $participant_values = Participants_Db::get_default_record();
} else {

  $action = 'update';
  $page_title = Participants_Db::plugin_label( 'edit_record_title' );
  $participant_values = Participants_Db::get_participant( $participant_id );
}

/*
 * if we have a valid ID or are creating a new record, show the form
 */
if ( $participant_values ) :

//error_log( basename( __FILE__).' record values:'.print_r( $participant_values,1));
//get the groups info
  $groups = Participants_Db::get_groups();

// get the current user's info
  //get_currentuserinfo();
  $current_user = wp_get_current_user();

  $options = get_option( self::$participants_db_options );

// set up the hidden fields
  $hidden = array(
      'action' => $action,
      'subsource' => Participants_Db::PLUGIN_NAME,
  );
  foreach ( array('id', 'private_id') as $i ) {
    if ( isset( $participant_values[$i] ) )
      $hidden[$i] = $participant_values[$i];
  }

  $section = '';
  
  do_action('pdb-before_edit_participant_body');
  
  $top_space = Participants_Db::apply_filters( 'show_edit_submit_top_bar', true ) ? 'top-bar-space' : '';
  ?>
  <div class="wrap pdb-admin-edit-participant participants_db <?php echo $top_space ?>">
    <h2><?php echo $page_title ?></h2>
    <?php
    if ( is_object( Participants_Db::$validation_errors ) ) {
      echo Participants_Db::$validation_errors->get_error_html();
    } else {
      Participants_Db::admin_message();
    }
    ?>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>" enctype="multipart/form-data" autocomplete="off" >
      <?php
      PDb_FormElement::print_hidden_fields( $hidden );
      
      if ( Participants_Db::plugin_setting_is_true( 'top_bar_submit', true ) ) :
        ?>
      <div class="top-bar-submit">
        <span class="field-group-title"><?php _e( 'Save the Record', 'participants-database' ) ?></span>
        <?php if ( !empty( $participant_id ) ) : ?><input class="button button-default button-leftarrow" type="submit" value="<?php echo self::$i18n['previous'] ?>" name="submit_button"><?php endif ?>
              <input class="button button-primary" type="submit" value="<?php echo self::$i18n['submit'] ?>" name="submit_button">
              <input class="button button-primary" type="submit" value="<?php echo self::$i18n['apply'] ?>" name="submit_button">
              <input class="button button-default button-rightarrow" type="submit" value="<?php echo self::$i18n['next'] ?>" name="submit_button">
      </div>
      <?php
      endif;
      
      // get the columns and output form
      foreach ( Participants_db::get_column_atts( 'backend' ) as $backend_column ) :

        $value = isset($participant_values[$backend_column->name]) ? $participant_values[$backend_column->name] : '';
      
        $column = new PDb_Field_Item( array(
            'name' => $backend_column->name,
            'module' => 'backend-edit',
            'value' => $value ),
                $participant_id
                );

        $id_line = '';

        $attributes = $column->attributes();

        // set a new section
        if ( $column->group != $section ) :
          if ( !empty( $section ) ) {
            ?>
            </table>
        </div>
        <?php
      } else {
//        $id_line = '<tr><th>' . _x( 'ID', 'abbreviation for "identification"', 'participants-database' ) . '</th><td>' . ( false === $participant_id ? _x( '(new record)', 'indicates a new record is being entered', 'participants-database' ) : $participant_id ) . '</td></tr>';
      }
      $section = $column->group()
      ?>
      <div  class="field-group field-group-<?php echo $groups[$section]['name'] ?>" >
        <h3 class="field-group-title"><?php echo Participants_Db::apply_filters( 'translate_string', $groups[$section]['title'] ) ?></h3>
        <?php if ( $options['show_group_descriptions'] ) echo '<p class="' . Participants_Db::$prefix . 'group-description">' . Participants_Db::apply_filters( 'translate_string', $groups[$section]['description'] ) . '</p>' ?>
        <table class="form-table">
          <tbody>
            <?php
          endif; // new section
//          echo $id_line;
          ?>

          <tr class="<?php echo ( $column->is_hidden_field() ? 'text-line' : $column->form_element() ) . ' ' . $column->name() . '-field' ?>">
            <?php
            $column_title = str_replace( array('"', "'"), array('&quot;', '&#39;'), Participants_Db::apply_filters( 'translate_string', stripslashes( $column->title ) ) );
            if ( $options['mark_required_fields'] && $column->validation != 'no' ) {
              $column_title = sprintf( Participants_Db::plugin_setting( 'required_field_marker' ), $column_title );
            }
            ?>
            <?php
            $add_title = array();
            $fieldnote_pattern = ' <span class="fieldnote">%s</span>';
            if ( $column->is_hidden_field() ) {
              $add_title[] = _x( 'hidden', 'label for a hidden field', 'participants-database' );
            }
            
            if ( $column->is_readonly() ) {

              if (
                      $column->form_element() === 'timestamp' && Participants_Db::apply_filters( 'edit_record_timestamps', false ) === true ||
                      $column->name() === 'private_id' && Participants_Db::apply_filters( 'private_id_is_read_only', true ) === false ) {
                // don't mark these fields as read-only if editing is enabled
                $column->make_readonly( false );
                
              } else {

                $attributes['class'] = 'readonly-field';
                /**
                 * @version 1.7.0.13
                 * @filter  pdb-field_readonly_override
                 * @param object $column the current field object
                 * @return bool if true the field is rendered as readonly
                 */
                if (
                        !Participants_Db::current_user_has_plugin_role( 'editor', 'readonly access' ) && Participants_Db::apply_filters( 'field_readonly_override', true, $column ) ||
                        $column->name() === 'private_id' && Participants_Db::apply_filters( 'private_id_is_read_only', true ) ||
                        $column->name() === 'id' && Participants_Db::apply_filters( 'record_id_is_read_only', true )
                ) {
                  $attributes['readonly'] = 'readonly';
                }
                $add_title[] = __( 'read only', 'participants-database' );
              }
            }
            ?>
            <th><?php echo $column_title . ( empty( $add_title ) ? '' : sprintf( $fieldnote_pattern, implode( ', ', $add_title ) ) ) ?></th>
            <td id="<?php echo Participants_Db::$prefix . $column->name() ?>-field" >
              <?php
              /*
               * get the value from the record; if it is empty, use the default value if the 
               * "persistent" flag is set.
               */

              // handle the persistent feature
              if ( $column->is_persistent() && strlen( $participant_values[$column->name()] ) == 0 ) {
                $column->set_value(  $column->default  );
              }

              // get the existing value if any
              //$column->value = isset($participant_values[$column->name]) ? Participants_Db::unserialize_array($participant_values[$column->name]) : '';
              // replace it with the new value if provided
              if ( array_key_exists( $column->name(), $_POST ) ) {

                if ( is_array( $_POST[$column->name()] ) )
                  $column->value = filter_var_array( $_POST[$column->name], FILTER_SANITIZE_STRING );

                elseif ( 'rich-text' === $column->form_element() )
                  $column->set_value( filter_input( INPUT_POST, $column->name(), FILTER_SANITIZE_SPECIAL_CHARS ) );
                else
                  $column->set_value( filter_input( INPUT_POST, $column->name(), FILTER_SANITIZE_SPECIAL_CHARS ) );
              }

              $field_class = ( $column->validation != 'no' ? "required-field" : '' ) . ( in_array( $column->form_element(), array('text-line', 'date') ) ? ' regular-text' : '' );

              switch ( $column->form_element() ) {

//                  case 'timestamp':
                case 'date':

                  /*
                   * if it's not a timestamp, format it for display; if it is a
                   * timestamp, it will be formatted by the xnau_FormElement class
                   */
                  if ( $column->has_content() ) {
                    $column->value = PDb_Date_Parse::timestamp( $column->value, array(), 'Edit Participant value display date element' );
                  }

                  break;

                case 'password':

                  $value = '';
                  if ( $column->has_content() ) {
                    $value = PDb_FormElement::dummy;
                  }
                  $column->set_value( $value );
                  break;

                case 'hidden':
                  
                  $column->set_form_element( 'text-line' );
                  if (!Participants_Db::current_user_has_plugin_role( 'admin', 'readonly access' ) ) {
                    $column->make_readonly();
                  }
                  break;

                case 'timestamp':

                  if ( !PDb_Date_Parse::is_mysql_timestamp( $column->value() ) )
                    $column->set_value( '' );
                  break;
              }

              if ( 'rich-text' == $column->form_element ) {

                wp_editor(
                        $column->value(), Participants_Db::rich_text_editor_id( $column->name() ), array(
                    'media_buttons' => false,
                    'textarea_name' => $column->name(),
                    'editor_class' => $field_class,
                        )
                );
              } else {
                
                PDb_FormElement::print_element( array(
                    'type' => $column->form_element(),
                    'value' => $column->get_value(),
                    'name' => $column->name(),
                    'options' => $column->options(),
                    'class' => $field_class,
                    'attributes' => $attributes,
                    'module' => 'admin-edit',
                    'link' => $column->link(),
                ) );
              }

              if ( !empty( $column->help_text ) ) :
                ?>
                <span class="helptext"><?php echo Participants_Db::apply_filters( 'translate_string', stripslashes( trim( $column->help_text ) ) ) ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php
        endforeach;
        ?>
      </tbody>
    </table>
  </div>
  <div  class="field-group field-group-submit" >
    <h3 class="field-group-title"><?php _e( 'Save the Record', 'participants-database' ) ?></h3>
    <table class="form-table">
      <tbody>
        <?php if ( is_admin() ) : ?>
          <tr>
            <td class="submit-buttons">
              <?php if ( !empty( $participant_id ) ) : ?><input class="button button-default button-leftarrow" type="submit" value="<?php echo self::$i18n['previous'] ?>" name="submit_button"><?php endif ?>
              <input class="button button-primary" type="submit" value="<?php echo self::$i18n['submit'] ?>" name="submit_button">
              <input class="button button-primary" type="submit" value="<?php echo self::$i18n['apply'] ?>" name="submit_button">
              <input class="button button-default button-rightarrow" type="submit" value="<?php echo self::$i18n['next'] ?>" name="submit_button">
            </td>
          </tr>
          <tr>
            <td >
              <?php _e( '<strong>Submit:</strong> save record and return to list<br><strong>Apply:</strong> save record and continue with same record<br><strong>Next:</strong> save record and then start a new one', 'participants-database' ) ?>
              <br />
              <?php
              if ( !empty( $participant_id ) ) {
                _e( '<strong>Previous:</strong> save and move to previous record', 'participants-database' );
              }
              ?>
            </td>
          </tr>
        <?php else : ?>
          <tr>
            <th><h3><?php echo Participants_Db::apply_filters( 'translate_string', $options['save_changes_label'] ) ?></h3></th>
            <td class="submit-buttons">
              <input class="button button-primary pdb-submit" type="submit" value="<?php echo Participants_Db::apply_filters( 'translate_string', $options['save_changes_button'] ) ?>" name="save">
              <input name="submit_button" type="hidden" value="<?php echo self::$i18n['apply'] ?>">
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </form>
  </div>
  <?php
  do_action('pdb-after_edit_participant_body');
 endif;