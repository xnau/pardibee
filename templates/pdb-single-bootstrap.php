<?php
/**
 * @name pdb single template bootstrap
 * @version 2.2
 * 
 * default template for displaying a single record for the twitter bootstrap framework
 *
 * http://twitter.github.com/bootstrap/
 * 
 */
?>

<div class="wrap  <?php echo $this->wrap_class ?>">

  <?php if ( $this->participant_id > 0 ) : ?>
	
  <?php while ( $this->have_groups() ) : $this->the_group(); ?>
  
  <section id="<?php echo Participants_Db::$prefix.$this->group->name?>" class="<?php $this->group->print_class() ?> field-group" style="overflow:auto">
  
    <?php $this->group->print_title( '<h2 class="field-group-title" >', '</h2>' ) ?>
    
    <?php $this->group->print_description( '<p>', '</p>' ) ?>
    
      <?php while ( $this->have_fields() ) : $this->the_field();
					
          // CSS class for empty fields
					$empty_class = $this->get_empty_class( $this->field );
      
      ?>
    
    <dl class="dl-horizontal <?php echo Participants_Db::$prefix.$this->field->name ?> <?php echo $empty_class ?>-group <?php echo $empty_class ?>">
      
      <dt class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_label() ?></dt>
      
      <dd class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_value() ?></dd>
      
    </dl>
  
    	<?php endwhile; // end of the fields loop ?>
    
    
  </section>
  
  <?php endwhile; // end of the groups loop ?>
  
<?php else : // content to show if no record is found ?>

  <?php $error_message = Participants_Db::plugin_setting( 'no_record_error_message', '' );
  
  if ( ! empty( $error_message ) ) : ?>
    <p class="alert alert-error"><?php echo $error_message ?></p>
    
  <?php endif ?>
    
<?php endif ?>
  
</div>