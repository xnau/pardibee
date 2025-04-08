<?php
/**
 * displays the CSV import UI page
 * 
 * @version 2.0
 */
if ( !defined( 'ABSPATH' ) )
  exit;
if ( !Participants_Db::current_user_has_plugin_role( 'admin', 'upload csv' ) )
  exit;

$CSV_import = new PDb_CSV_Import( 'csv_file_upload' );

$csv_params = PDb_admin_list\csv::csv_options();

foreach (array_keys( $csv_params ) as $param) {
  $new_value = '';
  if ( isset( $_POST[$param] ) ) {
    switch ( $param ) {
      case 'enclosure_character':
        $new_value = str_replace( array('"', "'"), array('&quot;', '&#39;'), filter_input( INPUT_POST, 'enclosure_character', FILTER_DEFAULT, Participants_Db::string_sanitize() ) );
        break;
      default:
        $new_value = filter_input( INPUT_POST, $param, FILTER_DEFAULT, Participants_Db::string_sanitize() );
    }
    $csv_params[$param] = $new_value;
  }
}

// update the option
PDb_admin_list\csv::update_option( $csv_params );

// make the parameters available as plain variables
extract( $csv_params );

// ensure the match field is valid
if ( !PDb_Form_Field_Def::is_field( $match_field ) ) {
  $match_field = 'id';
}

$import_columns = $CSV_import->has_errors() ? $CSV_import->export_columns() : $CSV_import->column_names();
$column_count = count( $import_columns );

?>
<div class="wrap <?php esc_attr_e( Participants_Db::$prefix ) ?>csv-upload">
<?php Participants_Db::admin_page_heading() ?>
  <div id="poststuff">
    <div id="post-body">
      <h2><?php esc_html_e( 'Import CSV File', 'participants-database' ) ?></h2>

<?php
if ( $CSV_import->has_errors() ):
  
  if ( $CSV_import->error_status === 'error' )
  {
    Participants_Db::debug_log( 'CSV Import page error: ' . implode( "\n", $CSV_import->get_errors() ), 2 );
  }
  ?>

        <div class="<?php echo esc_attr( $CSV_import->error_status ) ?> fade below-h2" id="message">
          <p><?php echo wp_kses_post( implode( '</p><p>', $CSV_import->get_errors() ) ) ?></p>
        </div>

  <?php
endif;
?>

      <form method="post" action="<?php echo esc_attr( $_SERVER["REQUEST_URI"] ) ?>">
        <input type="hidden" name="filename" value="blank_record.csv" />
        <input type="hidden" name="subsource" value="<?php echo esc_attr( Participants_Db::PLUGIN_NAME ) ?>">
        <input type="hidden" name="action" value="output CSV" />
        <input type="hidden" name="CSV type" value="blank" />
        <div class="postbox">
          <div class="inside">
            <h3><?php esc_html_e( 'Prepare a spreadsheet file with the correct format:', 'participants-database' ) ?></h3>
            <p><?php esc_html_e( 'To properly import your membership data, the columns in your spreadsheet must match exactly the columns in the database. Currently, the CSV export columns are as follows:', 'participants-database' ) ?></p>
            <div class="spreadsheet-frame">
              <table class="spreadsheet">
                <tr>
<?php
foreach ( $import_columns as $name ) {
  echo '<th>' . esc_html( $name ) . '</th>';
}
?>
                </tr>
                <tr>
<?php
echo str_repeat( '<td>&nbsp;</td>', $column_count );
?>
                </tr>
              </table>
            </div>
            <p><?php esc_html( printf( __( 'This means your spreadsheet needs to have %s columns, and the heading in each of those columns needs to match exactly the names above. If there is no data for a particular column, you can include it and leave it blank, or leave it out entirely. The order of the columns doesn&#39;t matter.', 'participants-database' ), $column_count ) ) ?></p>
            <p><?php esc_html_e( 'If the imported CSV file has a different column set, that column set will be imported and used. If a column name does not match a defined column in the database, the import will be aborted and a list of the incorrect column names will be displayed.', 'participants-database' ) ?></p>
            <p><input class="button button-default" type="submit" value="<?php esc_attr_e( 'Get Blank CSV File', 'participants-database' ) ?>" style="float:left;margin:0 5px 5px 0" /><?php esc_html_e( 'You can download this file, then open it in Open Office, Excel or Google Docs.', 'participants-database' ) ?></p>
          </div>
        </div>
      </form>
      <div class="postbox">
        <div class="inside">
          <h3><?php esc_html_e( 'Export the .csv file', 'participants-database' ) ?></h3>
          <p><?php esc_html_e( 'When you have your spreadsheet properly set up and filled with data, export it as any of the following: "comma-delimited csv", or just "csv". Save it to your computer then upload it here.', 'participants-database' ) ?></p>
          <h4><?php esc_html_e( 'Exported CSV files should be comma-delimited and enclosed with double-quotes ("). Encoding should be "UTF-8."', 'participants-database' ) ?></h4>
        </div>
      </div>
      <div class="postbox">
        <div class="inside">
          <h3><?php esc_html_e( 'Upload the .csv file', 'participants-database' ) ?></h3>
          <form enctype="multipart/form-data" action="<?php esc_attr_e( $_SERVER["REQUEST_URI"] ) ?>" method="POST">
<?php wp_nonce_field( PDb_CSV_Import::nonce ) ?>
            <input type="hidden" name="csv_file_upload" id="file_upload" value="true" />
            <fieldset class="widefat inline-controls settings-row">
              <p>
                <label>
<?php _e( 'Enclosure character', 'participants-database' ); ?>
:  
<?php
$parameters = array(
    'type' => 'dropdown',
    'name' => 'enclosure_character',
    'value' => $enclosure_character,
    'options' => array(
        PDb_FormElement::null_select_key() => 'false',
        __( 'Auto', 'participants-database' ) => 'auto',
        '&quot;' => '&quot;',
        "&#39;" => "&#39;"
    )
);
PDb_FormElement::print_element( $parameters );
?>
                </label>
                <label>
<?php _e( 'Delimiter character', 'participants-database' ); ?>
: 
<?php $parameters = array(
    'type' => 'dropdown',
    'name' => 'delimiter_character',
    'value' => $delimiter_character,
    'options' => array(
        PDb_FormElement::null_select_key() => 'false',
        __( 'Auto', 'participants-database' ) => 'auto',
        ',' => ',',
        ';' => ';',
        __( 'tab', 'participants-database' ) => "\t"
    )
);
PDb_FormElement::print_element( $parameters );
?>
                </label>
                
                
                                <label>
<?php echo __( 'Allow blank value overwrite', 'participants-database' ) . ':'; ?>
<?php $parameters = array(
    'type' => 'checkbox',
    'name' => 'blank_overwrite',
    'value' => $csv_params['blank_overwrite'],
    'options' => [1,0],
);
PDb_FormElement::print_element( $parameters );
echo $CSV_import->help_link( 'overwriting' );
?>
                </label>
                
                
                
                
                
                
                
              </p>
            </fieldset>

            <fieldset class="widefat inline-controls">
              <p>
                <label>
<?php
echo __( 'Duplicate Record Preference', 'participants-database' ) . ': ';
$parameters = array(
    'type' => 'dropdown',
    'name' => 'match_preference',
    'value' => $match_preference,
    'options' => array(
        __( 'Create a new record with the submission', 'participants-database' ) => 'add',
        __( 'Update matching record with new data', 'participants-database' ) => 'update',
        __( "Don't import the record", 'participants-database' ) => 'skip',
        PDb_FormElement::null_select_key() => false,
    )
);
PDb_FormElement::print_element( $parameters );
?>
                </label>
              </p>
              <p>
                <label>
<?php
echo __( 'Duplicate Record Check Field', 'participants-database' ) . ': ';
$parameters = array(
    'type' => 'dropdown',
    'name' => 'match_field',
    'value' => $match_field,
    'options' => PDb_Settings::_get_identifier_columns( false, array('rich-text', 'multi-checkbox','multi-dropdown','multi-select-other', 'image-upload', 'file-upload', 'password', 'placeholder', 'timestamp','captcha') ),
);
PDb_FormElement::print_element( $parameters );
?>
                </label>
              </p>
            </fieldset>
            <p><?php _e( '<strong>Note:</strong> Depending on the "Duplicate Record Preference" setting, imported records are checked against existing records by the field set in the "Duplicate Record Check Field" setting. If a record matching an existing record is imported, one of three things can happen, based on the "Duplicate Record Preference" setting:', 'participants-database' ) ?></p>
            <h4 class="inset" id="match-preferences"><?php esc_html_e( 'Current Setting', 'participants-database' ) ?>: 
<?php
$preferences = array(
    'add' => sprintf( __( '%sCreate New%s adds all imported records as new records without checking for a match.', 'participants-database' ), '<span class="emphasized">', '</span>', '</span>' ),
    'update' => sprintf( __( '%sOverwrite%s an existing record with a matching %s will be updated with the data from the imported record. Blank or missing fields will not overwrite existing data.', 'participants-database' ), '<span class="emphasized">', '</span>', '<em class="match-field">' . Participants_Db::$fields[$match_field]->title() . '</em>' ),
    'skip' => sprintf( __( '%sDon&#39;t Import%s does not import the new record if it matches the %s of an existing one.', 'participants-database' ), '<span class="emphasized">', '</span>', '<em class="match-field">' . Participants_Db::$fields[$match_field]->title() . '</em>' ),
);
foreach ( $preferences as $i => $preference ) {
  $hide = $i == $match_preference ? '' : 'style="display:none"';
  printf( '<span class="preference" %s data-index="%s" >%s</span>', $hide, $i, $preference );
}
?></h4>


<?php _e( 'Choose .csv file to import:', 'participants-database' ) ?> <input name="<?php esc_attr_e( PDb_CSV_Import::csv_field ) ?>" type="file" />
<p><input type="submit" id="csv-upload-submit" class="button button-primary" value="<?php esc_attr_e( 'Upload File', 'participants-database' ) ?>" />&emsp;
  <?php if (Participants_Db::plugin_setting_is_true( 'background_import', true ) )
  {
    _e('Records will be imported in the background.','participants-database');
  } else {
    _e('Records will be imported as the file is uploaded, this may take some time if the file is large.', 'participants-database');
    echo '<span class="csv-import-spinner">' . Participants_Db::get_loading_spinner() . '</span>';
  } ?>
</p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  UploadCSV = (function ($) {
    var
            prefs,
            matchfield,
            set_visible_pref = function (i) {
              hide_prefs();
              show_pref(i);
            },
            hide_prefs = function () {
              prefs.find('.preference').hide();
            },
            show_pref = function (i) {
              prefs.find('.preference[data-index=' + i + ']').show();
            },
            set_pref = function () {
              set_visible_pref($(this).val());
            },
            set_match_field_text = function (f) {
              matchfield.text(f);
            },
            set_match_field = function () {
              set_match_field_text($(this).find('option:selected').text());
            };
    return {
      run : function () {
        prefs = $('#match-preferences');
        matchfield = prefs.find('.match-field');
        $('[id^="pdb-match_preference"]').change(set_pref);
        $('[id^="pdb-match_field"]').change(set_match_field);
        $('#csv-upload-submit').on('click', () => $('.csv-import-spinner .ajax-loading').css('visibility', 'visible'));
      }
    }
  }(jQuery));
  jQuery(function () {
    "use strict";
    UploadCSV.run();
  });
</script>
<style>
  .csv-import-spinner .ajax-loading {
    visibility: hidden;
    display: inline-block;
    vertical-align: middle;
  }
  .csv-import-spinner {
    margin-left: 1em;
  }
  progress {
    -webkit-appearance:none;
    -moz-appearance:none;        
    appearance: none;
    border-radius: 3px;
    border: 1px solid #1d232733;
    width: 100%;
  }
  progress::-webkit-progress-value,
  progress::-moz-progress-bar{
    background-color: #2271b1;
  }
  progress.complete::-webkit-progress-value,
  progress.complete::-moz-progress-bar{
    background-color: #00a32a;
  }
  progress::-webkit-progress-value,
  progress::-webkit-progress-bar {
    border-radius: 3px;
  }
  #message .progressbar {
    position: relative;
  }
  #message .dashicons.dashicons-flag {
    color: #00a32a;
  }
</style>