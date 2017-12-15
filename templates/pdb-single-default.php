<?php
/**
 * @name pdb single template default
 * @version 2.0
 * 
 * default template for displaying a single record
 *
 */
if ( $this->participant_id > 0 ) :
?>

<div class="wrap <?php echo $this->wrap_class ?>">
	
  <?php while ( $this->have_groups() ) : $this->the_group(); ?>
  
  <div class="section <?php $this->group->print_class() ?>" id="<?php echo Participants_Db::$prefix.$this->group->name ?>">
  
    <?php $this->group->print_title( '<h2 class="pdb-group-title">', '</h2>' ) ?>
    
    <?php $this->group->print_description() ?>
    
    
      <?php while ( $this->have_fields() ) : $this->the_field();
					
          // CSS class for empty fields
					$empty_class = $this->get_empty_class( $this->field );
      
      ?>
    <dl class="<?php echo Participants_Db::$prefix.$this->field->name.' '.$this->field->form_element.' '.$empty_class?>">
      
      <dt class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_label() ?></dt>
      
      <dd class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_value() ?></dd>
    
    </dl>
  
    	<?php endwhile; // end of the fields loop ?>
    
  </div>
  
  <?php endwhile; // end of the groups loop ?>
  
</div>
<?php else : // content to show if no record is found ?>

  <?php $error_message = Participants_Db::plugin_setting( 'no_record_error_message', '' );
  
  if ( ! empty( $error_message ) ) : ?>

    <p class="alert alert-error"><?php echo $error_message ?></p>
    
  <?php endif ?>
    
<?php endif ?>