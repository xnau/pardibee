=== Participants Database ===
Contributors: xnau
Donate link: https://xnau.com/wordpress-plugins/participants-database
Tags: database, directory, listing, mailing list, signup
Requires at least: 5.0
Tested up to: 6.6.2
Requires PHP: 7.4
Stable tag: 2.5.9.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Build and maintain a fully customizable database of participants, members or anything with signup forms, admin backend, custom lists, and CSV support.

== Description ==

This plugin offers all the functionality needed to build and maintain a database of people or anything you want. The database is fully configurable, allowing you to define all the fields of information you want to store for each record. The records can be entered individually in the WordPress admin section, imported with a CSV file, or let the individuals themselves create their own record. Display lists of records can be sorted and filtered by any field, and the result exported as a CSV file.

This plugin was developed for an organization with the mission of educating and empowering voters. This organization needed to quickly build a database of concerned voters, supporters, and volunteers, giving them an ability to collect input and feedback, organize volunteers, and mobilize its voter constituency with actions such as petition drives and voter education campaigns.

This database could be of use to any organization that needs to build and maintain lists of constituents, supporters, members, volunteers, etc. for any purpose. It is designed to be easy to use and serve multiple purposes, with several very powerful features to customize its functionality to the needs of your organization, club, sports team, or any other large group of people.

The plugin can be easily adapted to work as a database for other applications such as indexes, directories, catalogs, or anything, really. The plugin uses a system of customizable templates for all its displays, and an API for the customization and extension of its capabilities. The plugin is fully internationalized with a growing set of translations.

[GDPR Compliance Information](https://xnau.com/work/wordpress-plugins/participants-database/gdpr-compliance-and-participants-database/) for users of Participants Database

= Some of the features of the Participants Database Plugin: =

* fully configurable database for holding any kind of information about people (or anything, really!)
* customizable forms for collecting and maintaining records
* plugin enhancements and add-ons are available from a growing list of [free and premium downloads on xnau.com](https://xnau.com/shop/?utm_source=wporg_visitor&utm_medium=plugin_page_description_tab&utm_campaign=pdb-addons-promo)
* shortcode for inserting a configurable sign-up short form into WordPress pages, posts, etc.
* completing the sign-up form can direct visitors to another page for a thank you message or reward
* shortcode for inserting a full-length form for people to fill out and maintain their own records
* shortcode for displaying the list on the site, including the ability to select and order columns to display, sorting and filtering rules to determine which records are shown and in what order
* shortcode for showing a search form that takes the user to the search results page
* email notification and confirmation with secure individual access link
* email notification when a user edits a record
* searchable, sortable record listings in the WordPress admin
* many form elements to choose from including dropdowns, checkboxes, radio buttons, image upload, rich text, etc.
* export CSV files for interacting with other databases, mass email, print records
* import CSV files to add large numbers of records from spreadsheets such as Open Office or Google Docs
* forms can be organized into groups of fields, making long forms easier to navigate and fill out
* comes with a comprehensive API for deep customization of the plugin functionality

= Database =

The heart of this plugin is the participants database, which is completely configurable. It comes pre-filled with standard fields such as name, address, phone, etc., but you can define any fields you want, including the type of field, validation, help text and a print title for each field. Fields are also organized into groups so large amounts of information can be better managed, and long forms broken up into logical sections.

Fields can be defined as text-lines, text-areas, rich text (with a rich-text editor), single and multiple-select dropdowns, checkboxes, radio buttons or image uploads. Each field has its own validation which can be required, not required, or validated with a regular expression.

= Sign Up Form =

The plugin provides a shortcode for a sign-up form that presents a customizable subset of the fields for a quick signup. For example, your signup form could ask only for a name and email address, creating an easy point-of-entry for new members, supporters or volunteers. The signup can generate two emails: one to an administrator to notify them of the signup, and also to the person signing up. Their email can contain a link to their full record, which they can return and fill out at their leisure. This full form (which is placed on the website with another shortcode) can include any information you want to collect from your signups.

Signup forms are produced by a template, making it easy to add extra functionality and match the presentation of the form to your theme.

= Frontend Record Edit Form =

This is where people who have signed up can fill in any additional information about themselves you wish to collect. It can be additional demographic info, survey questions, what they would be willing to offer in support. This form is accessible to the signups via an individual link containing an ID number, which is emailed to them when they sign up. They don't need to register as a user or enter a password, they just need the link.

= Backend Record Editing =

For your backend users, the ability to edit and enter new records is provided. This backend form can also contain administrative fields that won't be visible to the front-end (not logged-in) user, so organization staff can keep internal records of volunteer activities, availability, contributions, personal notes, etc.

The backend form is set up for rapid manual entry of multiple records, such as after a signup drive, doorbelling, or public event.

For textarea fields, a rich-text editor will be used if enabled in the settings.

= List Display =

Display the list on your website with the `[pdb_list]` shortcode. You can determine which fields get shown, and for long lists, the list can be broken up into pages. You can specify which records get displayed and in what order. Optionally, search and sorting controls can be displayed. Each record listing can be linked to the full record showing all the details of the record.

= Record Display =

Each individual record can be displayed using a shortcode and accessed by the ID if the record. A template file formats the output of the shortcode. A plugin setting determines how a link to the individual record may be placed on the list of records.

= Import/Export Records =

All records can be exported as a CSV-formatted text file that can be read by spreadsheet applications and used for mass email campaigns, hard-copy lists, and other applications. The records exported can be filtered by column values: for instance, only people who have consented to receive a newsletter will be included. Records may also be sorted by any column. Which fields are included in the export/import is determined by the "CSV" column of the field definition.

Records can also be mass-imported with a CSV file, allowing you to use existing lists from spreadsheets, or for offline compilation of records using a spreadsheet such as Libre Office or Google Docs. A blank spreadsheet can be exported from the plugin to get people started in entering records offline.

= Internationalization and Translations =

This plugin is fully compliant with WordPress Internationalization standards and includes several translations, some of which are incomplete at the moment. All of the front-end text is user-customizable, so even if a translation isn't available for your language, your users will be able to use the plugin in their language.

= Translation Credits =

* Belarusian: Natasha Dyatko [UStarCash](https://www.ustarcash.com)

* Danish: LarsHdg

* Dutch: At Voogt [www.wederzijdsgenoegen.nl](http://www.wederzijdsgenoegen.nl)

* Estonian: Laura Vunk

* Farsi: Mohsen Azarteymoor [CodHa](http://www.codha.ir)

* Finnish: Visa Jokela

* French: Pierre Fischer

* German: Martin Sauter

* German Formal: Hanno Bolte [Hanno Bolte IT Consulting](https://www.bsoft.de)

* Greek: Toni Bishop [Jrop](https://www.jrop.com)

* Hebrew: Gila Baam

* Indonesian: Jordan Silaen [ChameleonJohn.com](http://chameleonjohn.com)

* Italian: Michele Balderi

* Norwegian: Anders Kleppe

* Polish: Łukasz Markusik

* Brazilian Portuguese: Eric Sornoso [Mealfan.com](https://Mealfan.com)

* Romanian: Cornelia Năescu

* Russian: Konstantin Bashevoy [Полиатлон России](http://polyathlon-russia.com/base)

* Serbian: Cherry, NBG, [www.trade.in.rs](http://trade.in.rs/)

* Slovak: Branco Radenovich [WebHostingGeeks.com](http://webhostinggeeks.com/blog/)

* Spanish: Chema Bescos [IBIDEM GROUP](https://www.ibidemgroup.com)

* Ukranian: Michael Yunat, [getvoip.com](http://getvoip.com/blog)

If you are multi-lingual and feel like contributing a translation, please contact me at: support@xnau.com.

Please note that several of these translations are out of date. If your language is in this list and you'd like to help me provide the latest translation, please contact me.

The latest POT file is always [available here.](https://plugins.trac.wordpress.org/browser/participants-database/trunk/languages/participants-database.pot)

= Key Image Credit =

By Tukulti65 (Own work) [CC BY-SA 4.0 (http://creativecommons.org/licenses/by-sa/4.0)], via Wikimedia Commons

== Installation ==

1. In the admin for your WordPress site, click on "add new" in the plug-ins menu.
2. Search for "participants database" in the WP plugin repository and install
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Place `[pdb_record]` in your blog posts and pages to show the signup form
5. Additonal features and instructions can be found on the help tab of the plugin's settings page

**or**

1. Download the zip file
2. Click on "Add New" in the plugins menu
3. At the top of the "Add Plugins" page find and click the "Upload Plugin" button
4. Select the zip file on your computer and upload it
5. The plugin will install itself. Click on "activate" to activate the plugin

= Using the Plugin: =

This is a complex plugin that can be configured in many ways. I am happy to answer support questions, but please read the documentation, there are also many articles and tutroials to help you get the most out of Participants Database. Here are some helpful links:

* [Participants Database](https://xnau.com/work/wordpress-plugins/participants-database/)
* [Participants database Documentation](https://xnau.com/work/wordpress-plugins/participants-database/participants-database-documentation/)
* [Add-Ons and UI Enhancements](https://xnau.com/shop/?utm_source=wporg_visitor&utm_medium=plugin_page_inatallation_tab&utm_campaign=pdb-addons-promo)
* [Quick Setup Guide](https://xnau.com/participants-database-quick-setup-guide/)

== Frequently Asked Questions ==

= How do I get the form to display? Where does the shortcode go? =

Put the `[pdb_signup]` shortcode where you want your signup form to go.

= I can't find the shortcode. Where is it? =

A shortcode is a WordPress feature that lets you insert plugin content into your posts and pages. It is just a special bit of text that is replaced with the plugin content when the page or post is viewed. Just put this: `[pdb_signup]` into your page, and when you view the page, the signup form will be displayed.

= What happens when someone signs up? =

Their record is added to the database with the information provided in the signup form. Also, two emails will be sent (if you want) one to the admin to notify them of the signup, and also to the user to tell them they've signed up and also give them a private link to their record edit page so they can update or add to their record.

= What do you mean by a users record edit page? =

This form appears on the page where you have placed the `[pdb_record]` shortcode. It is another form where the record can be edited on the frontend.

An individual record can be edited on the frontend of your website by someone who has the private link to that record. The purpose here is to let people maintain their own records. It's also possible to put things like survey questions in the form so you can get feedback from people. You have complete control over what fields appear on this form. Fields can be designated as showing up on the signup form, on the frontend user's form, and on the backend admin form.

= The email goes out to the person when they register, but the "edit link" is blank =

This means your "Participant Record Page" is not configured. First, you need a page for the record edit form. On that page, place the [pdb_record] shortcode. Then, look under the "Record Form" tab in the plugin settings, make sure the page where you have the [pdb_record] shortcode is selected in the Participant Record Page setting.

= How do I delete all the records but leave everything else in place? =

The best way to do this is to perform a "truncate" on the table. You'll need to get direct access to the database to do this, usually using phpMyAdmin, which is typically found in your hosting control panel. Open the database and find the main participants database table...usually called "wp_participants_database". Perform a truncate on that table only. The truncate command is found by selecting the table, then under the "operations" tab in the lower right. 

= What if I just want them to sign up and fill out all their info at that point? =

OK, just assign all the fields you want them to fill out to the signup form (this is done in the "manage fields" page). That form can be as big as you want. Then, remove the private link from the email they get when they sign up.

= How do I change the text "Participant Info" seen at the top of the single record page? =

This is a group title. Groups are defined on the Manage Database Fields page under the "Field Groups" tab. You will see that each group has a title and a description that you can use to organize and label the ouput of the plugin.

= How do I let people upload an image or file? =

Go to the "manage database fields" page and create a new field for your image. Give it a descriptive name like "avatar" or something. Click "add field" and when your new field appears, set the "field type" to "image upload" for images and "file-upload" for other types of file. Be sure to check "signup" if you want it to appear in the signup form. When the form is presented to the user, they will be allowed to upload an image.

There are several settings you should look at before you go live with your uploads fields. Under the "General Settings" tab, you'll find:
* File Upload Location - this tells the plugin where to put the uploaded files
* File Upload Limit - this is the size limit in Kilobytes for your file uploads. Be careful not to put this too high, if you have lots of users, it could use a lot of space on your server!
* Allowed File Types - this is a comma-separated list of allowed file extensions. You can also define this on a per-field basis.
* Allow File Delete - if this is checked, the file will be removed from the server when a user deletes it.

Each file upload field can have its allowed files determined in the "values" field on the Manage Database Fields page. For instance, if you want them to upload only a pdf file, put "pdf" in the values field for that field. The maximum size is determined globally in the settings only.

= When someone signs up, are they now users on my WordPress site? =

No, these signups and records are separate from people who can register on your site as users. If there are things only registered users can do (such as a forum or comments), they will have to register or sign in as usual.

The idea is to use the database as a roster of volunteers, supporters, etc. for the organization, not the website.

If you are interested in having this plugin work with WordPress users, read this article: [Using Participants Database with WordPress Users](http://xnau.com/using-participants-database-with-wordpress-users/).

= People are signing up, but emails are not getting sent =

Use my [Email Troubleshooting Flowchart](http://xnau.com/participants-database-email-troubleshooting/) to help you diagnose the problem.

Of course make sure your settings are set to send emails.

The most common reason emails are not being sent is because the WP application cannot send email. If you are having this problem, I suggest you install an SMTP plugin (like WP-Mail-SMTP) and use that plugin to test your email sending. Sometimes it is necessary to set up and use SMTP (which is not the default on most systems) to successfully send email.

Another common source of email trouble is other plugins that send email. It is not uncommon for such plugins to "hijack" the WP mail function and this can break it for other plugins. Try turning off other plugins that send email to see who the troublemaker is.

Finally, your emails may be getting caught in spam filters. If you find the plugin's emails in the spam folder, using a "from" email from the same domain as the site can help. If you are using HTML email (the default) keep it short and don't include lots of links, images or spammy words.

= I don't see anything on the page where I put the `[pdb_record]` shortcode. What's up? =

The form will only appear if someone uses a valid private link to access the page. All that's required for a private link is a valid "pid" value has to be included in the URI. (it looks like "pid=TH65J" in the link) This code can be seen in the record if you want to make your own links.

= I don't want Administrative Fields showing up on the user's edit record page. How do I control that? =

You can control which groups of fields show up in the frontend record edit screen (the one with the `[pdb_record]` shortcode) by going to the "manage database fields" page, clicking on the "field groups" tab and deselecting the "display" checkbox for those field groups you don't want shown on the frontend.

= I want people to provide their email when they sign up, but I don't want that information showing to the public =

It's a good practice to use field groups for something like this. Place all your "don't show to the public" fields in a group with its "display" checkbox unchecked. This will prevent those fields from being shown in record detail pages, and also the signup form, but you can force them to display by specifying in the shortcode which groups you want included. The normally hidden groups will be included, but only those fields marked with the "signup" checkbox will appear. 

For example, let's say you have two groups: 'public' and 'private.' The email field is in the private group because you don't want it displayed. In the signup form shortcode, specify the groups to show like this: `[pdb_signup groups=public,private]` Now, both groups will be included in the signup form. Remember, only those fields marked as "signup" fields will be shown.

= I don't want group titles showing in the forms, how do I do that? =

The easiest way to do this is to simply blank out the title for the group.

= What if someone loses their private link? =

You can show a "Resend Private Link" link on your signup form, just check "Enable Lost Private Link" under the "Retrieve Link Settings" tab. You must define which field is used to identify the record. This must be a unique identifier, usually an email address, but it could be anything. The rest of the settings for this feature are under that tab.

It's also possible to send them the link again in an email, but the plugin does not currently provide a way to do this. You will have to sent them a link to the edit record page (the one with the `[pdb_record]` shortcode), adding their code at the end of the link like this: ?pid=RH45L (using whatever the code for their record is.) The code is visible when you view the record from the "list participants" page.

= Is it possible for users to upload files? =

File uploads use the "file upload" field type. You should define a set of allowed file extensions in the settings: "allowed file types" under the "general settings" tab.

= My site is not in English and searches using non-English characters are not working properly. =

If you have a non-English site, you should convert your database to the correct "collation" for your language. 

= I'm seeing strange characters in my CSV export. What's going on? =

The plugin exports its CSV files in "UTF-8" format. Make sure the program you're using to read the file knows this...it should detect it automatically, but can fail under some circumstances. Often this can be set on the import screen of the spreadsheet program when you open the CSV.

= Is the private link to an individual record secure? =

It is what I would call "reasonably secure" in other words, the private code in the link is not easily guessed. It can be sent in an email, which is not secure, but emails getting compromised is not that much a risk for most of us. The level of security is reasonable for the kind of information it is designed to store.

Therefore, this plugin is *absolutely not* for the storage of any kind of information requiring good security such as credit card numbers, passwords, social security numbers, etc. And I certainly couldn't be held liable if someone were to irresponsibly use the plugin for such a purpose.

= Can I make links in records clickable? =

Yes, there is a plugin setting called "Make Links Clickable" that scans the fields looking for something that starts with "http" it will then wrap that in a link tag so it will be clickable. It will also render email addresses clickable.

There is also a form field type called "link" that lets people fill in a URL and also give it a text label such as "My Website" that will click to the URL.

= Is a CAPTCHA available for the forms? =

You can define a "captcha" form element which will show a simple math question for the user to answer.

== Screenshots ==

1. Managing Database fields: this is where you set up your database fields and all attributes for each field
2. Edit Record: this is where an individual record can be created or edited
3. Import CSV File: page where CSV files can be imported, includes detailed instructions and a blank spreadsheet download

== Changelog ==

= 2.5.10 =
* added preference to disallow HTML in text fields
* admin list column preferences now on a per-user basis
* added German Formal translation
* minor bug fixes

= 2.5.9.5 =
* fixed classname reference error causing error when saving dynamic fields

= 2.5.9.4 =
* fixed issue with link type field not validating
* fixed issue in PDb_Template where the value method didn't return the correct value in some cases

= 2.5.9.3 =
* further hardening against code injection vulnerability
* fixed issue with false reporting of HTTP loopback failure

= 2.5.9.2 =
* fixed issue with list header sorting not working on AJAX search results
* fixed code injection vulnerability in form submissions
* list search "clear" now clears the last submitted search value

= 2.5.9.1 =
* fixed issue win incorrect messaging on file upload failure
* minor bug fixes
* batter tracking of possible issues with background imports

= 2.5.9 =
* fixed issue with media library images sometimes not displaying
* better handling of "0" default values in field definitions
* fixed localization issue when using a translation filter with some settings

= 2.5.8.1 =
* fixed bug with using exclusive options on dropdown element
* prevented failed session start

= 2.5.8 =
* new "exclusive" option disables options that have been selected in other records
* improved handling of php sessions
* selector options can now be disabled using a code filter

= 2.5.7 =
* new setting to suppress uploads directory warning if not needed
* fixed occasional save bug in admin edit_participant
* fixed record link in images in the admin list
* "target" attribute now allowed on dynamic fields that are links
* date_recorded value now available to dynamic fields in signup submissions
* dynamic fields no longer ignore "null" values from the database
* added the ability for custom code to update dynamic fields on a cron
* field name editing disabled since it is impossible anyway

= 2.5.6 =
* new settings for the list search UI strings
* scroll to thanks message after successful signup
* fixed pagination issue with multiple list shortcodes on the same page
* fixed issue with sticky uploads directory error notice
* search results by URL now includes instance index and page number targeting
* updated the Finnish translation
* fixed the "accessed with private link" actions
* fixed access vulnerability in admin manage fields page

= 2.5.5 =
* fixed issue with CSV import fatal error
* error on uninstall fixed
* compatibility with php 8.2

= 2.5.4 =
* several bug fixes
* fixed issue with importing date_update values
* now possible to run shortcodes (using a shortcode field) in a list display

= 2.5.3 =
* fixed issue with signup shortcode value assignments
* internal timestamp values can now be imported with CSV
* fixed issue with media embeds on the record edit page

= 2.5.2 =
* improved efficiency of the main cache
* fixed bug in click-to-sort headers in the list display
* background process won't get stuck in a loop if there is an error in the task

= 2.5.1 =
* fixed the "complete_only" directive on string combine fields
* filtering by record timestamp values improved, a simple date match works now
* fixed undefined index warning message
* admin list can now filter on empty/non-empty link fields
* duplicate record checks on link fields now possible
* fixed db issue with timestamp sorting on some systems

= 2.5 =
* new find duplicates operator on the admin List Participants page
* click-to-sort headers for the list shortcode
* fixed security issue with the admin "with selected" operation
* frontend list can now be sorted with URL variables
* added new external REST API
* sort now places blank values at the end of the list
* fixed javascript issue with non-latin fieldnames
* fixed major bug related to changing the title of internal fields
* fixed frontend CSV export issue with timestamp in shortcode filter

= 2.4.9 =
* fixed fatal error when updater plugin is deactivated

= 2.4.8 =
* php 8.1 compatibility
* enhanced format tag functionality
* improved use of WP Cache API

= 2.4.7 =
* field order maintained when moving field to another group
* fixed issue with field groups not deletable on non-English sites
* issue with high ascii (accented and other non-English) characters in search terms
* new setting to allow javascript event attributes in fields

= 2.4.6 =
* fixed CSRF vulnerability on the Manage List Columns page
* fixed issue with accented characters in group names breaking field group tabs
* several minor bug fixes

= 2.4.5 =
* fixed fatal error with record updates on the backend

= 2.4.4 =
* avoid validating fields that are not part of the submission
* fixed issue with the use of custom roles for plugin admins
* fixed PDb_Template class error on empty record
* fixed error on plugin delete

= 2.4.3 =
* signup forms that don't validate remember previously submitted values
* improved UI on otherselect fields
* some HTML tags allowed in field attributes

= 2.4.2 =
* otherselect inputs no longer getting focus on page load
* media embed and shortcode fields now working
* HTML5 date fields now included in date calc field calculations
* newly-uploaded image now seen after upload

= 2.4.1 =
* fixed issue with list search error HTML showing
* fixed javascript email protection

= 2.4 =
* fixed issue with captcha field escaping validation
* fixed issues with search using unicode characters
* fixed display issues with image uploads
* hardening against XSS

= 2.3.4 =
* fixed filter that was removing unicode characters

= 2.3.3 =
* fixed issue with captcha field not validated in some cases
* list search terms now have extra spaces trimmed 
* php 8.1 compatibility

= 2.3.2 =
* bug fix: fatal error on activation

= 2.3.1 =
* security updates: escaping outputs
* removed unused PDb_Update class

= 2.3 =
* fixed visible HTML tags in some displayed admin text
* removed included plugin updater library

= 2.2 =
* added compatibility with the xnau Plugin Updater plugin
* fixed issue with admin custom CSS not loaded
* custom print CSS implemented
* security updates: escaping outputs
* removed wp-load.php include

= 2.1.11 =
* added new "strip_tags" attribute for string combine fields
* admin last used tab remembered
* fixed issue with link field validation
* fixed issue with blank "allowed" attribute in upload field
* improved form validation error highlighting

= 2.1.10 =
* several db call optimizations
* fixed bug affecting certain date calculation field setups

= 2.1.9 =
* improved user feedback on uploads directory issues
* ampersands and other special characters now allowed in admin list search

= 2.1.8 =
* Custom Template Folder plugin no longer needed: this is now incorporated into the main plugin
* fixed issue with importing multiselect values
* fixed invalid HTML on list pagination control
* "with selected" field delete now shows correct field name in the confirmation dialog

= 2.1.7 =
* fixed issue with math captcha not validating
* fixed admin list searches that include an undescore character

= 2.1.6 =
* an empty value in a date field will overwrite an existing value
* prevent fatal error with out of sync message queue
* webp files now allowed in the native plugin
* min php version updated to 7.4

= 2.1.5 =
* fixed issue with password field preventing record update
* fixed issue with empty file uploads triggering size error

= 2.1.4 =
* password field regex validation fixed
* image and file upload delete options improved
* selected file name is now shown when uploading an image or file to a field that already has an uploaded file

= 2.1.3 =
* date field can now be used for the duplicate record check
* class attribute now used in the single record templates

= 2.1.2 =
* added cache buster option for signup submissions
* list shortcode don't show pagination option

= 2.1.1 =
* fixed fatal error in dynamic_db_field class

= 2.1 =
* now export selected records on admin List Participants page
* new setting: CSV imports in the background (by default) or immediately
* calculated fields supported in Participant Log add-on

= 2.0.10 =
* added an unsaved changes warning to the add/edit participant page
* fixed issue with payment log field losing its configuration on update
* fixed issue with string combine templates getting an "unformatted" tag
* several minor bug and compatibility issues fixed

= 2.0.9 =
* better logging of CSV imports
* fixed several minor bugs and messaging errors

= 2.0.8 =
* added new "currency" format tag
* fixed issues with record timestamp using the wrong timezone
* better handling of errors when updating the database structure
* private ids on CSV import now handled correctly

= 2.0.7 =
* fixed fatal error with php 8.1 when defining field with options
* heading field now has auto paragraphs applied

= 2.0.6 =
* now possible to use another calculated field in a calculation template
* fixed issue with spaces in list search term
* fixed record id matching mode in signup form

= 2.0.5 =
* improved localization of numeric values
* avoid divide by zero error
* dropdown/other respects default setting if no value set 

= 2.0.4 =
* new calculated field values are shown in the edited record
* Duplicate field titles now work in the mass edit field selector

= 2.0.3 =
* fixed setup issue with the PDb_Template class, affecting Field Group Tabs

= 2.0.2 =
* fixed display issues with some multibyte characters
* php 8 compatibility

= 2.0.1 =
* fixed issue with image uploads in the signup form
* timestamps may now be set to use local timezone instead of UTC
* new setting to allow editor users to access administrative fields
* several minor bug fixes

= 2.0 =
New Features:
* "Mass Edit" on the admin list participants page
* new field types: Numeric Calculation and Date Calculation
* CSV imports in the background, avoids timeouts on very large imports
* option to delete the associated uploaded files when record deleted
* CSV import with "null" value in a field clears the field's db value
* uploaded files deleted on CSV import when upload field value cleared
* recently used fields convenience list in the admin list filter selector
* new Last Updater ID field records the id of the last user to update a record
* hidden field value can be determined by literal string in the shortcode
* numeric value displays now localized
* new setting to use the private ID to display single records
* compatibility with block-based WP themes

Also:
* many minor bug fixes and code optimizations

== Upgrade Notice ==

2.5.10 is a feature and bugfix release

== Plugin Support ==

**[Plugin manual and documentation](https://xnau.com/work/wordpress-plugins/participants-database/participants-database-documentation/?utm_source=wporg_visitor&utm_medium=plugin_page_othernotes__tab&utm_campaign=pdb-addons-promo) is published on the developer's website, xnau.com**

Plugin technical support is available on the [WordPress Plugin Plugin Support Forum](https://wordpress.org/support/plugin/participants-database), and on xnau.com in the comments section.

A growing list of [plugin add-ons and functionality enhancements](https://xnau.com/shop/?utm_source=wporg_visitor&utm_medium=plugin_page_othernotes__tab&utm_campaign=pdb-addons-promo) are also available on xnau.com.
