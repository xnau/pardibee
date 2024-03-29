<?php
/**
 * bootstrap template for signup form
 *
 * outputs a Twitter Bootstrap-compatible form
 * http://twitter.github.com/bootstrap/index.html
 * 
 * @version 0.5
 *
 */
?>

<div class="wrap <?php esc_attr_e( $this->wrap_class ) ?>" >

  <?php
  // this is how the html wrapper for the error messages can be customized
  $this->print_errors( '<div class="alert %1$s">%2$s</div>', '<p>%s</p>' );
  ?>

  <?php $this->print_form_head(); // this must be included before any fields are output   ?>

  <div class="form-horizontal pdb-signup">

    <?php while ( $this->have_groups() ) : $this->the_group(); ?>

      <?php if ( $this->group->has_fields() ) : ?>

        <?php if ( $this->group->printing_title() ) : ?>
          <fieldset class="field-group field-group-<?php esc_attr_e( $this->group->name ) ?>">
            <?php $this->group->print_title( '<legend>', '</legend>' ) ?>
            <?php $this->group->print_description() ?>
          <?php endif ?>

          <?php while ( $this->have_fields() ) : $this->the_field(); ?>

            <?php $feedback_class = $this->field->has_error() ? 'error' : ''; ?>

            <div class="<?php $this->field->print_element_class() ?> control-group <?php esc_attr_e( $feedback_class ) ?>">
              <?php if ( $this->field->has_title() ) : ?>
                <label class="control-label" for="<?php $this->field->print_element_id() ?>" ><?php $this->field->print_label(); // this function adds the required marker   ?></label>
              <?php endif ?>
              <div class="controls"><?php $this->field->print_element_with_id(); ?>

                <?php if ( $this->field->has_help_text() ) : ?>
                  <span class="help-block">
                    <?php $this->field->print_help_text() ?>
                  </span>
                <?php endif ?>

              </div>

            </div>

          <?php endwhile ?>
          <?php if ( $this->group->printing_title() ) : ?>
          </fieldset>
          <?php endif ?>
      <?php endif ?>

    <?php endwhile; // groups   ?>
    <fieldset class="field-group field-group-submit">
      <div id="submit-button" class="controls">
        <?php $this->print_submit_button( 'btn btn-primary' ); // you can specify a class for the button ?>
        <?php $this->print_retrieve_link(); ?>
      </div>
    </fieldset>
  </div>
  <?php $this->print_form_close() ?>
</div>
