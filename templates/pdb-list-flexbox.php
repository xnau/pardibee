<?php
/**
 * template for a responsive list layout using CSS3 flexbox
 * 
 * @version 0.2
 */
?>
<div class="wrap <?php echo $this->wrap_class ?> pdb-flexbox-list" id="<?php echo $this->list_anchor ?>">
  <?php /* SEARCH/SORT FORM */ ?>
  <?php if ( $filter_mode != 'none' ) : ?>
    <div class="pdb-searchform">

      <div class="alert alert-block" style="display:none">
        <a class="close" data-dismiss="alert" href="#">X</a>
        <p class="search_field_error"><?php _e( 'Please select a column to search in.', 'participants-database' ) ?></p>
        <p class="value_error"><?php _e( 'Please type in something to search for.', 'participants-database' ) ?></p>
      </div>

      <?php $this->search_sort_form_top( false, 'form-horizontal' ); ?>

      <?php if ( $filter_mode == 'filter' || $filter_mode == 'both' ) : ?>

        <div class="control-group">
          <label class="control-label"><?php _e( 'Search', 'participants-database' ) ?>:</label>
          <div class="controls">

            <?php
            /*
             * you can replace "false" with your own text for the "all columns" value
             * for more info on using the column_selector method, see pdb-list-detailed.php
             */
            $this->column_selector( false );
            ?>

            <?php $this->search_form() ?>
          </div>

        </div>
      <?php endif ?>
      <?php if ( $filter_mode == 'sort' || $filter_mode == 'both' ) : ?>


        <div class="control-group">
          <label class="control-label"><?php _e( 'Sort by', 'participants-database' ) ?>:</label>
          <div class="controls">

            <?php $this->sort_form() ?>

          </div>
        </div>
      <?php endif ?>

    </div>
  <?php endif ?>

  <div class="pdb-list list-container" >

    <?php do_action( 'pdb-prepend_to_list_container_content' ) ?>

    <?php
    /* print the count if enabled in the shortcode
     * 
     * the tag wrapping the count statment can be supplied in the function argument, example here
     */
    $this->print_list_count( '<h5>' );
    ?>  

    <?php if ( $record_count > 0 ) : ?>

      <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row ?>
        <section id="record-<?php echo $this->record->record_id ?>">

          <?php while ( $this->have_fields() ) : $this->the_field(); // each field is one cell ?>

            <div class="pdb-field pdb-field-<?php echo $this->field->name() ?> <?php echo $this->get_empty_class() ?>">
              <span class="pdb-field-title"><?php echo $this->field->title() ?></span>
              <span class="pdb-field-data"><?php $this->field->print_value() ?></span>
            </div>

          <?php endwhile; // each field  ?>

        </section>

      <?php endwhile; // each record   ?>

    <?php else : // if there are no records    ?>

      <h4><?php if ( $this->is_search_result === true ) echo Participants_Db::$plugin_options['no_records_message'] ?></h4>

    <?php endif; // $record_count > 0   ?>
  </div>
  <?php
  // set up the bootstrap pagination classes and wrappers
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
  $this->pagination->show();
  ?>
</div>
<?php // this is an example of a way to style the records, delete this or edit as needed ?>
<style type="text/css">
  .pdb-list.list-container {
    display: flex;
    flex-direction: column;
  }
  section {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: flex-start;
    align-items: flex-start;
    margin: 0 0 0.75em 0;
    padding-bottom: 0.75em;
    border-bottom: 2px solid rgba(0,0,0,0.1);
  }
  section:last-of-type {
    border-bottom: none;
  }
  .pdb-field {
    margin-right: 1em;
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    font-weight: bold;
    margin-bottom: 0.5em;
  }
  .pdb-field.blank-field {
    display: none;
  }
  .pdb-field-title {
    font-weight: normal;
    padding-right: 0.25em;
    font-size: 70%;
  }
  .pdb-list .pagination ul, .pdb-pagination ul {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    width: 100%;
  }
  .pdb-list .pagination li, .pdb-pagination li {
    width: 20%;
    max-width: 4em;
  }
  .pdb-pagination span.dashicons {
    vertical-align: middle;
  }
  .pdb-pagination span.ajax-loading {
    postion: absolute;
  }
  .pdb-list .pagination li > span, 
  .pdb-pagination li > span, 
  .pdb-list .pagination a, 
  .pdb-pagination a {
    padding: 0.5em 0;
    width: 100%;
  }

  @media only screen and (max-width: 600px) {
    /* 
    this will hide the direct number pagination links, resulting in a more 
    compact display 
    */
    .pdb-flexbox-list .pdb-pagination li.direct-page {
      display: none;
    }
  }
</style>