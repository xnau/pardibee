<?php
/**
 * @name pdb single template bootstrap
 * @version 2.4
 * 
 * default template for displaying a single record for the twitter bootstrap framework
 *
 * http://twitter.github.com/bootstrap/
 * 
 */
?>

<div class="wrap  <?php esc_attr_e( $this->wrap_class ) ?>">

  <?php if ( $this->record_found() ) : ?>
	
  <?php while ( $this->have_groups() ) : $this->the_group(); ?>
  
  <section id="<?php esc_attr_e( Participants_Db::$prefix.$this->group->name ) ?>" class="<?php $this->group->print_class() ?> field-group" style="overflow:auto">
  
    <?php $this->group->print_title( '<h2 class="field-group-title" >', '</h2>' ) ?>
    
    <?php $this->group->print_description( '<p>', '</p>' ) ?>
    
      <?php while ( $this->have_fields() ) : $this->the_field();
					
          // CSS class for empty fields
					$empty_class = $this->get_empty_class( $this->field );
      ?>
    
    <dl class="dl-horizontal <?php echo esc_attr( Participants_Db::$prefix.$this->field->name.' '.$empty_class . ' ' . $this->field->element_class() ) ?> <?php echo esc_attr( empty( $empty_class ) ? '' : $empty_class . '-group' ) ?>">
      
      <dt class="<?php esc_attr_e( $this->field->name.' '.$empty_class ) ?>"><?php $this->field->print_label() ?></dt>
      
      <dd class="<?php esc_attr_e( $this->field->name.' '.$empty_class ) ?>"><?php $this->field->print_value() ?></dd>
      
    </dl>
  
    	<?php endwhile; // end of the fields loop ?>
    
    
  </section>
  
  <?php endwhile; // end of the groups loop ?>
  
<?php else : // content to show if no record is found ?>

  <?php $error_message = Participants_Db::plugin_setting( 'no_record_error_message', '' );
  
  if ( ! empty( $error_message ) ) : ?>
    <p class="alert alert-error"><?php echo wp_kses_post( $error_message ) ?></p>
    
  <?php endif ?>
    
<?php endif ?>
  
</div>