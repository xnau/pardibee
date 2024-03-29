<?php
/**
 * @version 0.6
 * 
 * template for participants list shortcode output
 *
 * this is the default template which formats the list of records as a table
 * using shortcut functions to display the componenets
 *
 * If you need more control over the display, look at the detailed template
 * (pdb-list-detailed.php) for an example of how this can be done
 *
 */
?>
<div class="wrap <?php esc_attr_e( $this->wrap_class ) ?>" id="<?php esc_attr_e( $this->list_anchor ) ?>">
  <?php
  /*
   * SEARCH/SORT FORM
   *
   * the search/sort form is only presented when enabled in the shortcode.
   *
   */
  $this->show_search_sort_form();

  /* LIST DISPLAY */
  /*
   * NOTE: the container for the list itself (excluding search and pagination 
   * controls) must have a class of "list-container" for AJAX search/sort to 
   * function
   */
  ?>
  <table class="wp-list-table widefat fixed pages list-container" >

    <?php if ( has_action( 'pdb-prepend_to_list_container_content' ) ) : ?>
      <caption>
        <?php do_action( 'pdb-prepend_to_list_container_content' ) ?>
        <?php $this->print_list_count( '<div class="%s"><span class="list-display-count">' ) ?>
      </caption>
    <?php else : ?>
      <?php
      /* print the count if enabled in the shortcode
       * 
       * the tag wrapping the count statment can be supplied in the function argument, example here
       */
      $this->print_list_count( '<caption class="%s" ><span class="list-display-count">' );
      ?>
    <?php endif ?>


    <?php if ( $record_count > 0 ) : // print only if there are records to show ?>

      <thead>
        <tr>
          <?php
          /*
           * this function prints headers for all the fields
           * replacement codes:
           * %2$s is the form element type identifier
           * %1$s is the title of the field
           */
          $this->print_header_row( '<th class="%2$s" scope="col">%1$s</th>' );
          ?>
        </tr>
      </thead>

      <tbody>
          <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row  ?>
          <tr>
    <?php while ( $this->have_fields() ) : $this->the_field(); // each field is one cell  ?>

              <td class="<?php esc_attr_e( $this->field->name() ) ?>-field">
                
      <?php $this->field->print_value() ?>
              </td>

          <?php endwhile; // each field  ?>
          </tr>
  <?php endwhile; // each record  ?>
      </tbody>

<?php else : // if there are no records  ?>

      <tbody>
        <tr>
          <td><?php if ( $this->is_search_result ) echo wp_kses_post( Participants_Db::plugin_setting('no_records_message') ) ?></td>
        </tr>
      </tbody>

<?php endif; // $record_count > 0  ?>

  </table>
  <?php
  /*
   * this shortcut function presents a pagination control with default layout
   */
  $this->show_pagination_control();
  ?>
</div>