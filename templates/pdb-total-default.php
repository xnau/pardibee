<?php
/*
 *
 * template for a total value output
 * 
 * this demonstrates how to show various types of totals from the database
 *
 */
  
if (empty($this->shortcode_atts['fields'])) {

  /* if no "fields" attribute is defined, show a simple count of records, including 
   * the effect of a defined shortcode filter attribute
   */
  echo wp_kses_post( $record_count );
  
} else {

  /*
   * if the "fields" attribute is defined, we will be totaling values from the named fields
   */
  $total = 0;
  /* 
   * make an array of the fields attribute, then total all the named fields from all the records
   * 
   */
  $fields = explode(',', str_replace(' ', '', $this->shortcode_atts['fields']));
  
  while ($this->have_records()) : $this->the_record();
  
      foreach ($this->record->values as $name => $value) {
        foreach ($fields as $field) {
          if ($name == $field) {
            $total = $total + floatval($value);
          }
        }
      }
      
  endwhile;

  echo wp_kses_post( PDb_Localization::auto_format_number($total) );
  
}