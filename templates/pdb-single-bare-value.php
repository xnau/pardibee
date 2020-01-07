<?php
/*
 * template for showing a single value with no tags from a record
 *
 * the field to show must be defined in the shortcode using the "fields" attribute: 
 * [pdb_single template="bare-value" fields="first_name"]
 * 
 * this template requres the id of the record to get the value from be provided, 
 * either as a variable in the URI (?pdb=123) or as a shortcode attribute 
 * [pdb_single template="bare-value" record_id="123" fields="first_name"]
 *
 */
while ($this->have_groups()) : $this->the_group();
 
  while ($this->have_fields()) : $this->the_field();
  ?>
 		
    <?php echo $this->field->raw_value() ?>
    
  <?php
    endwhile;
    
  break;
 	
endwhile;