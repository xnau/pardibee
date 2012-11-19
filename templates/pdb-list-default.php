<?php
/*
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


<?php
  /*
   * SEARCH/SORT FORM
   *
   * the search/sort form is only presented when enabled in the shortcode.
   * 
   */
  $this->show_search_sort_form()

  /* LIST DISPLAY */
?>
<div class="wrap <?php echo $this->wrap_class ?>">
<!-- template:<?php echo basename( __FILE__ ); // this is only to show which template is in use ?> -->
<a name="<?php echo $this->list_anchor ?>" id="<?php echo $this->list_anchor ?>"></a>

  <table class="wp-list-table widefat fixed pages pdb-list" cellspacing="0" >
  
    <?php // print the count if enabled in the shortcode
		if ( $display_count ) : ?>
    <caption>
      Total Records Found: <?php echo $record_count ?>, showing <?php echo $records_per_page ?> per page
    </caption>
    <?php endif ?>

    <?php if ( $record_count > 0 ) : // print only if there are records to show ?>

      <thead>
        <tr>
          <?php /*
           * this function prints headers for all the fields
           * replacement codes:
           * %2$s is the form element type identifier
           * %1$s is the title of the field
           */
          self::_print_header_row( '<th class="%2$s" scope="col">%1$s</th>' );
          ?>
        </tr>
      </thead>
  
      <tbody>
      <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row ?>
        <tr>
          <?php while( $this->have_fields() ) : $this->the_field(); // each field is one cell ?>
  
            <td class="<?php echo $this->field->name ?>-field">
              <?php $this->field->print_value() ?>
            </td>
          
        <?php endwhile; // each field ?>
        </tr>
      <?php endwhile; // each record ?>
      </tbody>
    
    <?php else : // if there are no records ?>

      <tbody>
        <tr>
          <td><?php _e('No records found', 'participants-database' )?></td>
        </tr>
      </tbody>

    <?php endif; // $record_count > 0 ?>
    
	</table>
  <?php
  /* 
   * PAGINATION
   */
  $this->show_pagination_control();
  ?>