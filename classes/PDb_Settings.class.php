<?php
/**
 * plugin settings class for participants-database plugin
 *
 * this uses the generic plugin settings class to build the settings specific to
 * the plugin
 */
class PDb_Settings extends Plugin_Settings {

  function __construct() {

    $this->WP_setting = Participants_Db::$participants_db_options ;

    // define the settings sections
    // no need to worry about the namespace, it will be prefixed
    $this->sections = array(
                            'main' => __('General Settings', Participants_Db::PLUGIN_NAME ),
                            'signup' => __('Signup Form Settings', Participants_Db::PLUGIN_NAME ),
                            'record' => __('Record Form Settings', Participants_Db::PLUGIN_NAME ),
                            );


    // run the parent class initialization to finish setting up the class 
    parent::__construct( __CLASS__, $this->WP_setting, $this->sections );

    $this->submit_button = __('Save Plugin Settings', Participants_Db::PLUGIN_NAME );
    
    // define the individual settings
    $this->_define_settings();

    // now that the settings have been defined, finish setting
    // up the plugin settings
    $this->initialize();

  }

  /**
   * defines the individual settings for the plugin
   *
   * @return null
   */
  private function _define_settings() {

    // general settings

    $this->plugin_settings[] = array(
        'name'       =>'list_limit',
        'title'      => __('Records per Page', Participants_Db::PLUGIN_NAME ),
        'group'      =>'main',
        'options'    =>array(
          'type'        =>'text',
          'help_text'   => __('the number of records to show on each page', Participants_Db::PLUGIN_NAME ),
          'attributes'  =>array( 'style'=>'width:40px' ),
          'value'       =>10,
          ),
        );

    $this->plugin_settings[] = array(
        'name'       =>'unique_email',
        'title'      => __('Don&#39;t Allow Duplicate Email Addresses', Participants_Db::PLUGIN_NAME ),
        'group'      =>'main',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => __('if someone registers with an email address that already exists, update the existing record, don&#39;t create a new one.', Participants_Db::PLUGIN_NAME ),
          'value'       => 1,
          'options'     => array( 1, 0 ),
          ),
        );

    $this->plugin_settings[] = array(
        'name'       =>'show_pid',
        'title'      =>__('Show the Private ID in List', Participants_Db::PLUGIN_NAME ),
        'group'      =>'main',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => __('whether to show the private ID in the participant list in the admin', Participants_Db::PLUGIN_NAME ),
          'value'       => 1,
          'options'     => array( 1, 0 ),
          ),
        );

    $this->plugin_settings[] = array(
        'name'       =>'empty_field_message',
        'title'      =>__('Missing Field Error Message', Participants_Db::PLUGIN_NAME ),
        'group'      =>'main',
        'options'    =>array(
          'type'       =>'text',
          'help_text'  => __('the message shown when a field is required, but left empty (the %s is replaced by the name of the field)', Participants_Db::PLUGIN_NAME ),
          'value'      => __('The %s field is required.', Participants_Db::PLUGIN_NAME ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'invalid_field_message',
        'title'      =>__('Invalid Field Error Message', Participants_Db::PLUGIN_NAME ),
        'group'      =>'main',
        'options'    =>array(
          'type'       =>'text',
          'help_text'  => __("the message shown when a field's value does not pass the validation test", Participants_Db::PLUGIN_NAME ),
          'value'      => __('The %s field appears to be incorrect.', Participants_Db::PLUGIN_NAME ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'field_error_style',
        'title'      =>__('Field Error Style', Participants_Db::PLUGIN_NAME ),
        'group'      => 'main',
        'options'    =>array(
          'type'        =>'text',
          'help_text'   => __('the CSS style applied to an input or text field that is missing or has not passed validation', Participants_Db::PLUGIN_NAME ),
          'value'       => __('border: 1px solid red', Participants_Db::PLUGIN_NAME ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'image_upload_location',
        'title'      => __('Image Upload Location', Participants_Db::PLUGIN_NAME ),
        'group'      => 'main',
        'options'    =>array(
          'type'        => 'text',
          'help_text'   => __('this defines where the uploaded files will go, relative to the WordPress root.', Participants_Db::PLUGIN_NAME ),
          'value'       => Participants_Db::$uploads_path,
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'image_upload_limit',
        'title'      =>__('Image Upload Limit', Participants_Db::PLUGIN_NAME ),
        'group'      => 'main',
        'options'    =>array(
          'type'        =>'dropdown',
          'help_text'   => __('the maximum allowed file size for an uploaded image', Participants_Db::PLUGIN_NAME ),
          'value'       => '100K',
					'options'     => array( '10K'=>10,'20K'=>20,'50K'=>50,'100K'=>100,'150K'=>150,'250K'=>250,'500K'=>500, '750K'=>750 ),
          )
        );

    // signup form settings

    $this->plugin_settings[] = array(
        'name'       =>'signup_button_text',
        'title'      =>__('Signup Button Text', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('text shown on the button to sign up', Participants_Db::PLUGIN_NAME ),
          'value'       => __('Sign Up', Participants_Db::PLUGIN_NAME ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'send_signup_receipt_email',
        'title'      => __('Send Signup Response Email', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => __('Send a receipt email to people who sign up', Participants_Db::PLUGIN_NAME ),
          'value'       => 1,
          'options'     => array( 1, 0 ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'receipt_from_address',
        'title'      =>__('Signup Email From Address', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('the "From" address on signup receipt emails. If the recipient hits "reply", their reply will go to this address', Participants_Db::PLUGIN_NAME ),
          'value'       => get_bloginfo( 'admin_email' ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'receipt_from_name',
        'title'      =>__('Signup Email From Name', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('the "From" name on signup receipt emails.', Participants_Db::PLUGIN_NAME ),
          'value'       => get_bloginfo( 'name' ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'signup_receipt_email_subject',
        'title'      =>__('Signup Response Email Subject', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('subject line for the signup response email', Participants_Db::PLUGIN_NAME ),
          'value'       => sprintf( __("You've just signed up on %s", Participants_Db::PLUGIN_NAME ), get_bloginfo('name') ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'signup_receipt_email_body',
        'title'      =>__('Signup Response Email', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text-field',
          'help_text'   => __('Body of the email a visitor gets when they sign up. It includes a link ([record_link]) back to their record so they can fill it out. Can include HTML, placeholders:[first_name],[last_name],[email],[record_link]. Placing the [record_link] here is not recommended.', Participants_Db::PLUGIN_NAME ),
					/* translators: the %s will be the name of the website */
          'value'       =>sprintf( __('<p>Thank you, [first_name] for signing up with %s.</p><p>You may complete your registration with additional information or update your information by visiting this link at any time: <a href="[record_link]">[record_link]</a>.</p>', Participants_Db::PLUGIN_NAME ),get_bloginfo('name') ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'signup_thanks',
        'title'      =>__('Signup Thanks Message', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text-field',
          'help_text'   => __('Note to display on the web page after someone has submitted a signup form. Can include HTML, placeholders:[first_name],[last_name],[email],[record_link].', Participants_Db::PLUGIN_NAME ),
          'value'       =>__('<p>Thank you, [first_name] for signing up!</p><p>You will receive an email acknowledgement shortly. You may complete your registration with additional information or update your information by visiting the link provided in the email.</p>', Participants_Db::PLUGIN_NAME ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'send_signup_notify_email',
        'title'      => __('Send Signup Notification Email', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => __('Send an email notification that a signup has occurred.', Participants_Db::PLUGIN_NAME ),
          'value'       => 1,
          'options'     => array( 1, 0 ),
          )
        );


    $this->plugin_settings[] = array(
        'name'       =>'email_signup_notify_addresses',
        'title'      =>__('Signup Notification Recipients', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('comma-separated list of email addresses to send signup notifications to', Participants_Db::PLUGIN_NAME ),
          'value'       => get_bloginfo( 'admin_email' ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'email_signup_notify_subject',
        'title'      =>__('Signup Notification Email Subject', Participants_Db::PLUGIN_NAME ),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('subject of the notification email', Participants_Db::PLUGIN_NAME ),
					/* translators: the %s will be the name of the website */
          'value'       => sprintf( __('New signup on %s', Participants_Db::PLUGIN_NAME ), get_bloginfo('name') ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'email_signup_notify_body',
        'title'      =>__('Signup Notification Email'),
        'group'      => 'signup',
        'options'    => array(
          'type'        =>'text-field',
          'help_text'   => __('notification email body'),
          'value'       => __('<p>A new signup has been submitted</p><ul><li>Name: [first_name] [last_name]</li><li>Email: [email]</li></ul>'),
          )
        );
		
		// record form settings

    $this->plugin_settings[] = array(
        'name'       =>'registration_page',
        'title'      =>__('Participant Record Page', Participants_Db::PLUGIN_NAME ),
        'group'      =>'record',
        'options'    =>array
					(
          'type'       =>'dropdown',
          'help_text'  => __('the page where your participant record ([pdb_record] shortcode) is displayed', Participants_Db::PLUGIN_NAME ),
					'options'    => $this->_get_pagelist(),
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'show_group_descriptions',
        'title'      => __('Show Group Descriptions', Participants_Db::PLUGIN_NAME ),
        'group'      => 'record',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => __('Show the group description under each group title in the record form.', Participants_Db::PLUGIN_NAME ),
          'value'       => 0,
          'options'     => array( 1, 0 ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'record_updated_message',
        'title'      =>__('Record Updated Message', Participants_Db::PLUGIN_NAME ),
        'group'      =>'record',
        'options'    =>array(
          'type'       =>'text',
          'help_text'  => __("the message shown when a record form has be successfully submitted", Participants_Db::PLUGIN_NAME ),
          'value'      => __('Your information has been updated:', Participants_Db::PLUGIN_NAME ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'send_record_update_notify_email',
        'title'      => __('Send Record Form Update Notification Email', Participants_Db::PLUGIN_NAME ),
        'group'      => 'record',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => __('Send an email notification that a record has been updated.', Participants_Db::PLUGIN_NAME ),
          'value'       => 0,
          'options'     => array( 1, 0 ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'record_update_email_subject',
        'title'      =>__('Record Update Email Subject', Participants_Db::PLUGIN_NAME ),
        'group'      => 'record',
        'options'    => array(
          'type'        =>'text',
          'help_text'   => __('subject line for the record update notification email', Participants_Db::PLUGIN_NAME ),
          'value'       => sprintf( __("A record has just been updated on %s", Participants_Db::PLUGIN_NAME ), get_bloginfo('name') ),
          )
        );

    $this->plugin_settings[] = array(
        'name'       =>'record_update_email_body',
        'title'      =>__('Record Update Notification Email', Participants_Db::PLUGIN_NAME ),
        'group'      => 'record',
        'options'    => array(
          'type'        =>'text-field',
          'help_text'   => __('Body of the the email sent when a user updates their record. Any field from the form can be included by using a replacement code of the form: [field_name]. For instance: [last_name],[address],[email] etc. (The field name is under the "name" column on the "Manage Database Fields" page.)  Also available is [date] which will show the date and time of the update', Participants_Db::PLUGIN_NAME ),
          'value'       =>__('<p>The following record was updated on [date]:</p><ul><li>Name: [first_name] [last_name]</li><li>Address: [address]</li><li>[city], [state], [country]</li><li>Phone: [phone]</li><li>Email: [email]</li></ul>', Participants_Db::PLUGIN_NAME ),
          )
        );

  }
	
	private function _get_pagelist() {
		
		$pagelist = array();
		
		$pages = get_pages( array() );
		
		foreach( $pages as $page ) {
			
			$pagelist[ $page->post_name ] = $page->ID;
		
		}
		
		return $pagelist;
		
	}

  /**
   * displays a settings page form using the WP Settings API
   *
   * this function is called by the plugin on it's settings page
   *
   * @return null
   */
  public function show_settings_form() {
    ?>
    <div class="wrap participants_db settings-class">
      <h2><?php echo Participants_Db::$plugin_title?> <?php _e('Settings', Participants_Db::PLUGIN_NAME )?></h2>
      <ul>
      <?php foreach ( $this->sections as $id => $title ) printf('<li><a href="#%s">%s</a></li>',Participants_Db::make_anchor( $id ), $title ); ?>
      </ul>

      <?php parent::show_settings_form() ?>

    </div>
    <?php

  }
	
	
	/**
	 * displays a section subheader
	 *
	 * note: the header is displayed by WP; this is only what would go under that
	 */
	public function options_section( $section ) {
		
		$name = Participants_db::make_anchor( end( explode( '_',$section['id'] ) ) );
		
		return printf('<a id="%1$s" name="%1$s" class="%2$s" ></a>', $name, Participants_Db::$css_prefix.'anchor' );
	
	}

}
?>