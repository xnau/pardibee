<?php
/**
 * @version 0.5
 * 
 * template for participants list shortcode output
 * 
 * this template uses a bootstrap-style HTML format, also suitable for responsive display
 */
// set up the bootstrap-style pagination block
// sets the indicator class for the pagination display
$this->pagination->set_current_page_class( 'active' );
// wrap the current page indicator with a dummy anchor
$this->pagination->set_anchor_wrap( false );
// set the wrap class and element
//  $this->pagination->set_wrappers( array(
//      'wrap_tag'=>'<div class="pagination">',
//      'wrap_tag_close' => '</div>'
//      ));
$this->pagination->set_props( array(
    'first_last' => false,
    'current_page_class' => 'active currentpage',
    'wrappers' => array(
        'wrap_class' => 'pagination-large pagination-centered',
        'list_class' => '',
    ),
) );
?>
<div class="wrap <?php esc_attr_e( $this->wrap_class ) ?>" id="<?php esc_attr_e( $this->list_anchor ) ?>">
  <?php /* SEARCH/SORT FORM */ ?>
  <?php if ( $filter_mode != 'none' ) : ?>
    <div class="pdb-searchform">

      <div class="alert alert-block" style="display:none">
        <a class="close" data-dismiss="alert" href="#">X</a>
        <p class="search_field_error"><?php echo PDb_List::setting_string( 'search_field_error' ) ?></p>
        <p class="value_error"><?php echo PDb_List::setting_string( 'search_value_error' ) ?></p>
      </div>

      <?php $this->search_sort_form_top( false, 'form-horizontal' ); ?>

      <?php if ( $filter_mode == 'filter' || $filter_mode == 'both' ) : ?>



        <div class="control-group">
          <label class="control-label"><?php echo PDb_List::setting_string( 'search_field_label' ) ?></label>
          <div class="controls">

            <?php
            // you can replace "false" with your own text for the "all columns" value
            $this->column_selector();
            ?>

            <?php $this->search_form() ?>

          </div>
        </div>
      <?php endif ?>
      <?php if ( $filter_mode == 'sort' || $filter_mode == 'both' ) : ?>


        <div class="control-group">
          <label class="control-label"><?php echo PDb_List::setting_string( 'sort_field_label' ) ?></label>
          <div class="controls">

            <?php $this->sort_form() ?>

          </div>
        </div>
      <?php endif ?>
    </form>
    </div>
  <?php endif ?>

  <?php /* LIST DISPLAY */ ?>


  <table class="table pdb-list list-container" >

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

    <?php if ( $record_count > 0 ) : ?>

      <thead>
        <tr>
          <?php
          /*
           * this function prints headers for all the fields
           * replacement codes:
           * %2$s is the form element type identifier
           * %1$s is the title of the field
           */
          $this->print_header_row( '<th class="%2$s" >%1$s</th>' );
          ?>
        </tr>
      </thead>
      <?php
      // print the table footer row if there is a long list
      if ( $records_per_page > 30 ) :
        ?>
        <tfoot>
          <tr>
    <?php $this->print_header_row( '<th class="%2$s">%1$s</th>' ) ?>
          </tr>
        </tfoot>
  <?php endif ?>

      <tbody>
          <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row  ?>
          <tr>
            <?php while ( $this->have_fields() ) : $this->the_field(); // each field is one cell  ?>

                <?php if ( $this->field->has_content() ) : ?>
                <td class="<?php esc_attr_e( $this->field->name() ) ?>-field" >
        <?php $this->field->print_value(); ?>
                </td>

              <?php else : // if the field is empty  ?>
                <td class="<?php esc_attr_e( $this->field->name() ) ?>-field <?php esc_attr_e( $this->get_empty_class( $this->field ) ) ?>" ></td>
              <?php endif ?>

          <?php endwhile; // each field  ?>
          </tr>
  <?php endwhile; // each record  ?>
      </tbody>

<?php else : // if there are no records  ?>

      <tbody>
        <tr>
          <td><?php if ( $this->is_search_result === true ) echo wp_kses_post( Participants_Db::plugin_setting('no_records_message') ) ?></td>
        </tr>
      </tbody>

  <?php endif; // $record_count > 0  ?>
  </table>
<?php $this->pagination->show(); // show the pagination control  ?>
</div>