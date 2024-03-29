<?php
/*
 * default template to display private link retrieval form
 *
 */
$mode = isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'success' ? 'success' : 'request';
?>
<div class="wrap <?php esc_attr_e( $this->wrap_class . 'mode-' . $mode ) ?> " >

  <h4><?php echo wp_kses_post( Participants_Db::plugin_setting( 'retrieve_link_title', __( 'Request your Private Link', 'participants-database' ) ) ) ?></h4>

  <?php // output any validation errors
  $this->print_errors();
  ?>

<?php if ( $mode == 'success' ) : ?>

    <h5><?php echo wp_kses_post( Participants_Db::plugin_setting( 'retrieve_link_success', __( 'Success: your private link has been emailed to you.', 'participants-database' ) ) ) ?></h5>

  <?php else : ?>

  <?php $this->print_form_head(); // this must be included before any fields are output  ?>

    <table class="form-table pdb-signup">

  <?php while ( $this->have_groups() ) : $this->the_group(); ?>


        <tbody class="field-group pdb-group-<?php esc_attr_e( $this->group->name ) ?>">

          <?php while ( $this->have_fields() ) : $this->the_field(); ?>

            <?php
            if ( $this->field->name == Participants_Db::$plugin_options[ 'retrieve_link_identifier' ] ) {
              $this->field->help_text = sprintf( Participants_Db::plugin_setting( 'id_field_prompt' ), $this->field->title() );
            }
            ?>

            <tr class="<?php $this->field->print_element_class() ?>">

              <th><?php $this->field->print_label(); // this function adds the required marker  ?></th>

              <td id="<?php $this->field->print_element_id() ?>">

                <?php $this->field->print_element(); ?>

      <?php if ( $this->field->has_help_text() ) : ?>
                  <span class="helptext"><?php $this->field->print_help_text() ?></span>
      <?php endif ?>

              </td>

            </tr>

        <?php endwhile; // fields  ?>

          </tbody>

  <?php endwhile; // groups  ?>
          
      <tbody class="field-group field-group-submit">
        <tr>
          <td class="submit-buttons">

  <?php $this->print_submit_button( 'button-primary', __( 'Submit', 'participants-database' ) ); // you can specify a class for the button  ?>

          </td>
        </tr>

      </tbody>

    </table>

  <?php $this->print_form_close() ?>

<?php endif ?>

</div>