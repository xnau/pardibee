<?php
/*
 * class for displaying an single record on the frontend with the [pdb_single] shortcode
 *
 */
if ( ! defined( 'ABSPATH' ) ) die;
 class PDb_Single extends PDb_Shortcode {
  
  /**
   * initializes the record edit object
   */
  public function __construct( $shortcode_atts ) {
		
		// define shortcode-specific attributes to use
		$add_atts = array(
        'module' => 'single',
        'class' => $this->wrap_class,
        'term' => 'id',
    );
    
    // run the parent class initialization to set up the parent methods 
    parent::__construct($shortcode_atts, $add_atts);
    
    /*
     * determine the ID of the record to show
     *
     * Participants_Db::$single_query is a generic $_GET variable that indexes the record according to
     * the 'term' value, which defaults to 'id'
     *
     */
    if ( $this->shortcode_atts['record_id'] !== false && PDb_Form_Field_Def::is_field( $this->shortcode_atts['term'] ) ) {
      $record_id = Participants_Db::get_record_id_by_term( $this->shortcode_atts['term'], $this->shortcode_atts['record_id'] );
    } else {
      $record_id = filter_input( INPUT_GET, Participants_Db::$single_query, FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );; // Participants_Db::$session->record_id();
    }
    
    if ( false === $record_id && version_compare( $this->template_version, '0.2', '<') ) {
      
      $this->_not_found();
      
    } else {
      
      $this->participant_values = Participants_Db::get_participant( $record_id );
      $this->participant_id = $record_id;
      
      $this->_setup_iteration();
      
      $this->_print_from_template();
    }
    
  }
  
  /**
   * determines if the field should be added to the iterator
   * 
   * @param PDb_Field_Item $field
   * @return bool true if the field is to be added
   */
  protected function field_should_be_added( $field )
  {  
    $add = !$field->is_hidden_field();
    if ( $add ) {
      $add = !$field->is_match_validation();
    }
    return $add;
  }

	/**
	 * prints a signup form called by a shortcode
	 *
	 * this function is called statically to instantiate the Signup object,
	 * which captures the output and returns it for display
	 *
	 * @param array $params parameters passed by the shortcode
	 * @return string form HTML
	 */
	public static function print_record( $params ) {
		
		self::$instance = new PDb_Single( $params );
		
		return self::$instance->output;
		
	}
  
  /**
   * includes the shortcode template
   */
  protected function _include_template() {
    
    // set some template variables
    $id = $this->participant_id;
    
    include $this->template;
		
  }
  
 }