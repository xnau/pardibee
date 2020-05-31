=== Participants Database ===
Contributors: xnau
Donate link: https://xnau.com/wordpress-plugins/participants-database
Tags: supporter, member, volunteer, database, sign-up form, directory, index, survey, management, non-profit, political, community, organization, mailing list, team, records
Requires at least: 5.0
Tested up to: 5.4.1
Requires PHP: 5.6
Stable tag: 1.9.5.15
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

* Hebrew: Gila Baam

* Indonesian: Jordan Silaen [ChameleonJohn.com](http://chameleonjohn.com)

* Italian: Michele Balderi

* Norwegian: Anders Kleppe

* Polish: Łukasz Markusik

* Brazilian Portuguese: Celso Coslop

* Romanian: Cornelia Năescu

* Russian: Konstantin Bashevoy [Полиатлон России](http://polyathlon-russia.com/base)

* Serbian: Cherry, NBG, [www.trade.in.rs](http://trade.in.rs/)

* Slovak: Branco Radenovich [WebHostingGeeks.com](http://webhostinggeeks.com/blog/)

* Spanish: Cristhofer Chávez

* Ukranian: Michael Yunat, [getvoip.com](http://getvoip.com/blog)

If you are multi-lingual and feel like contributing a translation, please contact me at: support@xnau.com.

Please note that several of these translations are out of date. If your language is in this list and you'd like to help me provide the latest translation, please contact me.

The latest POT file is always [available here.](http://plugins.svn.wordpress.org/participants-database/trunk/participants-database.pot)

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

= 1.9.5.16 =
* fixed HTML issue with mutliselect checkbox/other element

= 1.9.5.15 =
* fixed bug in use of "term" in single record shortcode
* fixed issue with dynamic hidden field not getting value

= 1.9.5.14 =
* user locale can now be captured in a hidden field
* fix for use of custom user roles
* fix for issue with multiple shortcodes generating duplicate ids
* on CSV export, multilingual strings filtered for current locale

= 1.9.5.13 =
* address screen reader accessibility on checkbox/radio inputs
* null select title now filtered for translation
* decimal field display decimal places now set by "step" attribute
* compatibility with mySQL 8.0

= 1.9.5.12 =
* fixed issue with remote searches not working in some browsers
* fixed issue with searches on multiselect fields not returning expected results

= 1.9.5.11 =
* fixed remote searches not showing results
* restored the CSS on the Manage List Columns page
* fixed issue with field group delete confirmations

= 1.9.5.10 =
* fixed missing cookie script in plugin admin
* more multilingual support
* several minor bugs fixed

= 1.9.5.9 =
* new German and French translations
* compatibility fixes for multilingual plugins

= 1.9.5.8 =
* restored missing help text in admin record edit
* fixed random list ordering

= 1.9.5.7 =
* new French translations
* admin list searches for blank values working
* fixed translation filtering for several display strings
* better support for multilingual strings

= 1.9.5.6 =
* patched an sql injection vulnerability in the admin list page
* long filenames can be contracted for display in file and image upload fields
* better checking for allowed filename extension in upload fields
* fixed several instances of display strings not available to multilingual plugins
* new default value selector for field types with options in the field editor

= 1.9.5.5 =
* fixed issues with CSV export of date and link fields
* fixed display issue with bare-value single template
* removed the manage database fields redesign notice

= 1.9.5.4 =
* better caching for templated content
* plugin debug synced with WP_DEBUG
* fixed several minor bugs

= 1.9.5.3 =
* fixed issue with missing multi-select field values
* fixed warning on wp-includes/class-wp-block-parser.php
* fixed HTML tags getting into email subjects

= 1.9.5.2 =
* fixed caching issue with template value tags

= 1.9.5.1 =
* fixed issue with dropdown fields not showing the value title
* empty date no longer shows current date when using the datepicker
* fixed several minor warnings

= 1.9.5 =
* new template classes for suppressing titles, blank fields
* new flex-based single record template
* new settings for image display sizes in list, single and record displays
* date fields may now define their own display format
* new value tags that can show title or bare value
* passwords now encrypted on import
* multiple minor bug fixes and UI improvements

= 1.9.4.4 =
* template tags can now show plain text values and titles
* fixed issue with template tags not showing most recent field value
* fixed error in admin with editor users on list page

= 1.9.4.3 =
* fixed bug on multiselect field displays when using tag templates

= 1.9.4.2 =
* improved access to custom record matching
* fixed issue with editors on admin list searches
* several minor bug fixes

= 1.9.4.1 =
* fixed issue with shortcodes in rich text fields
* fixed view permissions issue on new installs

= 1.9.4 =
* admin record and settings submit buttons now mirrored at the top of the page for easier access
* editors can now access "with selected" function in the admin list
* javascript minified for faster asset loading
* preference to delete uploaded files with record delete
* many optimizations and minor bug fixes

= 1.9.3.15 =
* fixed error when deleted fields are an admin user search preference
* fixed loading spinner preload time

= 1.9.3.14 =
* fixed issue with CSV import skipping records that don't validate
* simple HTML now allowed in option titles, also field default values

= 1.9.3.13 =
* fixed issue with importing a CSV with both skipped and added records

= 1.9.3.12 =
* fixed issue with numeric field value formatting when using PDb_Template class
* fixed duplicate element id issue with file upload fields

= 1.9.3.11 =
* fixed bug when using null_select options
* radio button null_select option can now be used as a "none" selector

= 1.9.3.10 =
* tags now allowed in option value titles
* new "default_search_field" shortcode attribute for lists
* fixed Participants_Db::get_id_list method 


= 1.9.3.9 =
* numeric displays are now localized
* fixed issue with PDb_Template class not showing link fields correctly

= 1.9.3.8 =
* decimal fields no longer show trailing zeroes
* pattern attribute in field now fully supported
* bare (valueless) field attributes now supported
* multiple minor bug fixes

= 1.9.3.7 =
* fixed CSV imports/exports of newlines in field value
* fixed issue with pagination overclicks

= 1.9.3.6 =
* additional tags allowed in field definitions
* fixed issue with "null_select" options in some field options settings
* line breaks now display as new lines in text area field content

= 1.9.3.5 =
* added new SVG "loading" spinner
* fixed several bugs in dropdown field options settings
* fixed issue with duplicate IDs for rich text editors
* fixed error message on duplicate record when importing a CSV

= 1.9.3.4 =
* fixed minor technical issue when updating or adding records 

= 1.9.3.3 =
* fixed issue with group updates going to login screen
* addressed warnings when no groups are configured to display

= 1.9.3.2 =
* added better fix for disappearing internal fields bug
* fixed missing fields with bootstrap signup template
* fixed blank title issue on manage list columns page
* several minor bug fixes

= 1.9.3.1 =
* fixed issue with disappearing internal fields when updating values on Manage Database Fields page
* fixed text values of 0 not shown in admin record editor
* fixed warnings on date_updated and date_recorded fields when importing CSV

= 1.9.3 =
* fixed issue with display field order when using the fields attribute in the shortcode
* access to edit readonly fields is now given to editor users
* fixed issue with field options when using the PDb_Template class in a custom template

= 1.9.2 =
* fixed admin list pagination in alternate session mode
* fixed missing link recovery error message
* several minor bug fixes

= 1.9.1 =
* fixed manage database fields order issue 
* fixed issue with chosen dropdown options not showing

= 1.9.0 =
* redesigned Manage Database Fields page
* new visibility modes for field groups
* new Lost Private Link Success Message setting
* new validation message setting for validated fields

= 1.8.4.9 =
* fixed fatal error on first install in multisite

= 1.8.4.8 =
* fixed debug log white screen issue
* signup form skips empty field groups
* admin notices now dismissed for each user
* removed spurious text from responsive list template

= 1.8.4.7 =
* fixed issue with images lacking a link in emails
* optgroup titles now passed through translation filter
* several minor bug fixes

= 1.8.4.6 =
* fixed caching issue with shortcode fields and groups attributes
* minor tweak for php 7.3 compatibility

= 1.8.4.5 =
* addressing settings page access issues for some users

= 1.8.4.4 =
* fixed issue with settings page blank

= 1.8.4.3 =
* fixed bug with custom templates not found in default location

= 1.8.4.2 =
* settings page timing issues fixed #1942
* handle early session starts without warnings #1943

= 1.8.4.1 =
* fixed warning when setting up the plugin first time

= 1.8.4 =
* WordPress 5.0 compatibility
* added php timezone sync preference
* updated the list responsive template for better element classnames
* optimized some database transactions

= 1.8.3.2 =
* fixes blank Manage Database Fields screen bug
* post logins now working

= 1.8.3.1 =
* bugfix: field attributes missing in backend record edit

= 1.8.5 =
* redesigned Manage Database Fields page
* important changes to how fields are defined

= 1.8.3 =
* bugfix for single record display no record

= 1.8.2 =
* added optional cookieless session method

= 1.8.1 =
* force required php 5.4 or better

= 1.8 =
* improved session management
* further optimize background processes
* improvements to the API
* fixed issues with plugin uninstall
* requires php 5.4 or better

= 1.7.9.12 =
* fixed issue with link field when using the PDb_Template class

= 1.7.9.11 =
* fixed issue with placeholder fields not showing correctly in some templates

= 1.7.9.10 =
* fixed: images not displaying when using the PDb_Template class
* added the pdb-prepend_to_list_container_content action to list templates

= 1.7.9.9 =
* sent emails now logged in the debugging log
* various efficiency improvements
* fixed incompatibility with WP Session plugin
* ? wildcard in admin list searches

= 1.7.9.8 =
* PDb_Field_Item::is_single_record_link method reinstated
* admin list on small screens is now much more usable
* radio buttons now default to defined default value on record edit page
* default values now correctly inherited to new records, frontend record edits

= 1.7.9.7 =
* fixed bug with multi-select-other field values
* prevent fatal error if duplicate field names are present in the shortcode

= 1.7.9.6 =
* added Manage List Columns admin page
* display bug with empty hidden fields in admin record edit
* link field link text character replace bug

= 1.7.9.5 =
* fixed display issue with link fields

= 1.7.9.4 =
* fixed display of values in email templates
* link fields now show unlinked text value if no URL
* increased efficiency when using email templates

= 1.7.9.3 =
* removed debugging setup in version check
* fixed link property error in field class

= 1.7.9.2 =
* php version warning only appears once
* fixed empty function fatal error
* several minor bug fixes 

= 1.7.9.1 =
* fixed method return value bug
* fixed issue with using the template class in custom templates

= 1.7.9 =
* version warning for php < 5.6
* refactored field definition and dynamic objects
* improved efficiency for database interactions
* fixed mass-approval bug with user field values
* multiselect data now exported as comma-separated list
* fixed issue with "stuck" admin messages
* added Finnish translation

= 1.7.8.11 =
* minor improvements to several API filters
* size of the debugging log is now limited
* minor bugs fixed

= 1.7.8.10 =
* new and updated records by admin are validated if "Admin Record Edits are Validated" is enabled #1761
* search using empty term doesn't show error is allow by settings #1756
* placeholder values can now include limited HTML #1755

= 1.7.8.9 =
* duplicate values prevented when updating records #1753 #1758

= 1.7.8.7 =
* fixed bug when attempting to update a record with matching field errors enabled #1752

= 1.7.8.6 =
* setting to enable form validation for admin users in the backend #1747
* duplicate field values prevented in frontend record edit #1746
* plugin debug mode and log #1737
* "current_year" date filter value fixed #1750
* updated translation template

= 1.7.8.5 =
* address formatting conflict issue with javascript confirmation pop-ups in the admin #1736
* added new n_days and n_months "dynamic date keys" for use in the list filter #1744

= 1.7.8.4 =
* fixed list query parenthesization bug when parenthesizing "and" statements #1734
* added "searchable form element" filter #1733
* check for mbstring module #1724

= 1.7.8.3 =
* fixed display bug on url-only link field #1729 #1732

= 1.7.8.2 =
* fixed persistent fields on CSV import #1718

= 1.7.8.1 =
* added new API methods for getting a list of records from the database #1716
* customized datatype parameters won't be reverted when the field definition is saved #1717

= 1.7.8 =
* added new responsive list template "flexbox" #1702
* link fields can now target new tag or page #1712
* password field shows dummy password if password is set #1675
* cleaned up rendering of custom HTML attributes in form elements #1705 #1712 
* fixed send limit bug when applying actions to a large number of records in the admin #1707
* several minor bugs fixed; compatibility with php 7 #1482

= 1.7.7.7 =
* fixed bug in password field display

= 1.7.7.6 =
* blank numeric fields now save as null
* underscores in filter/search values now match underscores in db #1688
* fixed session_cache_limiter warning in php 7.2

= 1.7.7.5 =
* internal timestamps are not editable unless allowed in plugin settings #1681
* password fields now show dummy data if a password is set #1675
* fixed issue with internal timestamps not correctly parsed #1680
* added new "submission not validated" actions #1679

= 1.7.7.4 =
* fixed security issue with upload CSV files #1665
* php7 compatibility fixes #1669
* fixed bug causing blank timestamp in updated record when using php7 #1672
* minor error fixes in French translation

= 1.7.7.3 =
* fixed bug when using read-only field in the signup form #1659

= 1.7.7.2 =
* added preference to allow some HTML tags in text-line form elements #1661
* fixed bug when using read-only field in the signup form #1659
* dropdown elements may now use a value of 0 #1658

= 1.7.7.1 =
* fixed bug saving spurious value on dropdown null select #1656
* functionality updates to the Admin Notices class

= 1.7.7 =
* added "current_date" feature to the list shortcode filter
* fixed issue with dropdowns defaulting to the first item
* added filter to allow override or alternate text field sanitizing

= 1.7.6.6 =
* blank fields don't overwrite record value on CSV import #1647
* apostrophes and quotes no longer escaped in field definitions #1644 

= 1.7.6.5 =
* fixed column order bug when including the "id" column in the list display #1645
* fixed bug in field definition when using numeric value titles #1646

= 1.7.6.4 =
* fixed fatal error on upload with invalid file extension #1638

= 1.7.6.3 =
* fixed PHP 5.3 compatibility issue

= 1.7.6.2 =
* fixed multicheckbox CSV exports as comma-separated list #1631
* better user feedback for file uploads #1629 #1630

= 1.7.6.1 =
* HTML allowed in field titles and help text #1607
* fixed issue with "strict user searches" not working with some templates #1620
* fixed issue with WP 1.8.3 not finding wpdb::remove_placeholder_escape method #1623


= 1.7.6 =
* compatibility with WP 4.8.3 #1618
* added cache control to allow browser caching #1610
* PHP Sessions optimizations #1611
* improved multisite adding/deleting blogs #1615

= 1.7.5.16 =
* CONTENT_URL preference performance improvements
* empty group and empty group field classnames

= 1.7.5.14 =
* fixed error in image class default image method

= 1.7.5.13 =
* text-area and rich-text field now set TEXT datatype in db #1605
* remote search from now shows error messages #1602
* better feedback on new field creation #1600
* fixed first column CSV imports failing with BOM #1601
* added WP_CONTENT_URL preference for file/image uploads path #1604

= 1.7.5.12 =
* added filter for each column on form submission #1381
* field object now includes record ID in single record context #1596
* admin list search bug for empty date fields fixed #1595

= 1.7.5.11 =
* single record link field value is now filterable #1592
* update Dutch and Brazilian Portuguese translations
* fixed the private id length filter #1582

= 1.7.5.10 =
* fixed several XSS vulnerabilities

= 1.7.5.9 =
* multiple lists on a page work more reliably #1576
* addressed issue with blank lists after search #1575 
* password fields won't require password entry if a password has already been set #1572
* addressed issue of case-mismatched value titles not finding a value #1569
* addressed PHP 7.0 incompatibility #1573

= 1.7.5.8 =
* fixed warning on shortcode class #1564
* fixed issue with aux plugin updates not coming in #1566

= 1.7.5.7 =
* bug fixes for the admin edit participant page

= 1.7.5.6 =
* better handling of allowed field types in file/image uploads
* aux plugin update checks optimized

= 1.7.5.5 =
* new record imported vis CSV now have private IDs #1554
* CSV are no longer validated by mime type #1553
* fixed 'Cannot unset string offsets' issue #1555

= 1.7.5.4 =
* added security check to CSV import #1549
* fixed issue with single-quote enclosures in CSV imports #1551

= 1.7.5.3 =
* fixed file upload field display #1546
* field extension now correctly validated for file and image uploads #1547
* updated Danish translation

= 1.7.5.2 =
* updated Danish translation
* fixed missing captcha bug #1544

= 1.7.5.1 =
* fixed bug when filtering for blank values in the backend list
* fixed issue with the recaptcha not appearing in some cases
* no response from plugin updater is handled gracefully now

= 1.7.5 =
* added multisite support
* new "decimal" and "currency" form elements
* before and after characters on numeric fields for units or denominations
* provides fallback methods when using AJAX searches and session not available
* improved "thanks" shortcodes for signup and record forms
* shortcodes and auto paragraphs option for rich text
* site-specific file-upload locations in multisite
* email return-path header now set to match sender address for better deliverability

= 1.7.3.2 =
* improved response to searches on multi select-type fields when using strict search
* added frontend CSV download feature
* long field group titles don't break fields admin page layout any more

= 1.7.3.1 =
* now using standard dashicons font for all icons
* list search results now retained on navigation back to the list page
* "auto-paragraphs" setting offers more flexible options

= 1.7.3 =
* updated WP_Session class to version 1.2.2
* prevent wildcard-only searches
* date_recorded time no longer reset when updating the record
* added 'pdb-php_timezone_sync' filter to optionally prevent timezone sync with WP and PHP
* better handling of strict searches on multi-value fields

= 1.7.2.3 =
* read-only fields in the signup form are treated as writable
* added Indonesian language
* updated Danish translation

= 1.7.2.2 =
* minor bug fixes for PHP 7
* fixed pagination bug on list search results

= 1.7.2.1 =
* new admin custom CSS setting
* various bug fixes

= 1.7.2 =
* added new list filter operator for matching whole words #1474
* added filter for enabling whole word match
* added filter for replacing or modifying the private ID generator #1473
* added Hebrew translation
* added Farsi translation
* fixed issue with default value in link fields #1472
* checkboxes with two values now validate correctly #1429
* pdb-process_form_matched_record filter added to allow for an alternative record matching method #1398
* added pdb-image_wrap_template filter to PDB_Image class

= 1.7.1.12 =
* added action triggered before a record is deleted
* html is tripped out of value tags in the subject line of a templated email
* fixed issue with confirmation icons not seen in the admin list

= 1.7.1.11 =
* fixed issue with field-defined allowed file types preventing field value from printing *1466

= 1.7.1.10 =
* pdb-before_signup_thanks action now called with do_action #1463

= 1.7.1.9 =
* list search parameters are now cleared when loading fresh list #1462
* bug fix for pdb-before_signup_thanks action #1463
* added Brazilian Portuguese translation files
* added pdb-validation_methods filter

= 1.7.1.8 =
* date parsing now uses global date format #1448
* fixed bug with blank CSV exports on some installations #1449

= 1.7.1.7 =
* fixed bug where readonly fields were not saved in the signup form

= 1.7.1.6 =
* added support for setting the "target" attribute in templates #1363
* readonly form fields can be used in the signup form

= 1.7.1.5 =
* aux plugin access level is now filterable
* improved user feedback on admin list operations
* fixed settings page bug in PHP 7 #1443

= 1.7.1.4 =
* improved compatibility with email expansion kit
* forward compatibility with PHP 7

= 1.7.1.3 =
* replaced missing script

= 1.7.1.2 =
* fixed syntax error for sites running PHP 5.3 #1423

= 1.7.1.1 =
* added filter for modifying the record edit URL #1426

= 1.7.1 =
* new "with selected" edit feature in admin list #1416
* developer ads can now be disabled #1418
* long TLDs in emails now validate #1413
* attachment handing in the PDb_Template_Email class #1412
* plus signs in search terms #1406
* avoid printing label tags for empty titles in record and signup forms #1397

= 1.7.0.16 =
* fixed duplicate field bug when adding new record #1411

= 1.7.0.15 =
* fixed bug saving timestamps in the admin when using a display format PHP can't natively parse #1408, #1409

= 1.7.0.14 =
* last_accsessed value left untouched when editing the record in the admin #1405
* readonly dropdown fields now use value title #1404
* total shortcode field value summing bug fixed

= 1.7.0.13 =
* aux plugin option passed through translation filter #1358
* edit_record_page shortcode attribute fixed #1387
* fixed file and image field display bugs #1391, #1393, #1390
* help text shown in read-only fields

= 1.7.0.12 =
* fixed bug that prevented regex validation from allowing a blank value

= 1.7.0.11 =
* fixed bug that affected installations with very large (>100) field counts #1373
* fixed bug affecting some values stored as 3-d arrays #1365 #1372

= 1.7.0.10 =
* fixed bug in date parser while using intl date parser #1367
* replaced anonymous function in version check #1357
* update notice detail now shows complete and current information #1355

= 1.7.0.10 =
* fixed bug in date parser while using intl date parser
* replaced anonymous function in version check
* update notice detail now shows complete and current information

= 1.7.0.9 =
Version 1.7.0.9 is a bugfix release for all users
* fixed bad call in regpage_setting_fix Bug #1317
* added pdb-form_element_html filter
* JS bug in aux_plugin_settings.js Bug #1352
* better implementation of HTML5 client-side validation for aux plugins
* read-only field can now be used for private link recovery #1342
* added Participants_db::write_participant API method #1353

= 1.7.0.8 =
* fixed settings class warning on plugin first activate #1346
* record_edit URLs in emails #1343
* email obfuscation leaves un-obfuscated email visible if linking is off #1344

= 1.7.0.7 =
* fixed private method access issue for PHP version 5.3 #1323
* gracefully handles servers that don't allow remote URLS opened as files #1324
* added Participants_Db::do_action method

= 1.7.0.6 =
* fixed reference to $this in anonymous function #1321
* template class now properly handles array values #1321
* apostrophes and quotes in search terms were failing in some cases #1319
* global $post access should be checked for availability #1318
* plugin setting initialization issue with new installs #1322

= 1.7.0.5 =
* added Belarusian translation
* updated German/Swiss German translations
* fixed incorrect update/import record counts on CSV import #1290
* list search/sort/pagination now uses JS scroller when AJAX is enabled #1299
* fixed bug in the PHP version checker #1309
* bare https links now get the correct linktext #1311
* password field is now blank instead of trying to show the hash #1315

= 1.7.0.4 =
* bugfix restores missing settings submit button

= 1.7.0.3 =
* fixed issue where the private ID was not saved if the user wasn't logged in #1303
* added setting to suppress scroll anchors (fragments) in pagination links #1298
* fixed list query error with multiple search terms #1302
* allow dropdowns and other single-value fields to be used as the primary email address #1301

= 1.7.0.3 =
* fixed issue where the private ID was not saved if the user wasn't logged in #1303
* added setting to suppress scroll anchors (fragments) in pagination links #1298
* fixed list query error with multiple search terms #1302
* allow dropdowns and other single-value fields to be used as the primary email address #1301

= 1.7.0.2 =
* Fixed issue where default email headers were used instead of the plugin settings #1296
* fixed bug where the CSS error maker for a match field wasn't cleared when the field value is a match #1293

= 1.7.0.1 =
* fixed data-offset bug in admin list #1289
* fixed CSV timestamp import bug #1292
* fixed non-working single_record_link attribute in list shortcode #1291
* fixed bug in template class that made stored values unavailable in some contexts #1287

= 1.7 =
* adds numeric field type
* adds support for add-on plugins
* refactoring and standardization of email-related code
* refactoring of all date-related code
* Further adjustments to time rendering to compensate for difference between server and local time
* added pdb-shortcode_present hook, several other useful hooks and filters
* all display strings are passed though a gettext call, but only if the global PDB_MULTILINGUAL is set to true
* plugin cleans up its own transients and options
* alternate directory structures are now supported automatically
* improved messaging on setting up upload preferences
* enforced minimum PHP version 5.3
* single and record query var names are now user-alterable
* improved date parsing
* fully implemented template email class
* aux plugin update support
* list shortcode filter values can use & | reserved characters
* added support for 'search_fields' list shortcode attribute
* image data caching for better performance
* added API filter to rich text processor
* API filter for multiple field matches on new records
* API filter pdb-captcha_validation
* API filter pdb-before_admin_delete_record triggered on record delete
* added 'pdb-initialized' hook for use by aux plugins
* replaced use of get_currentuserinfo()

= 1.6.2.8 =
* fixes broken AJAX search on some systems
* valid timezone setting is enforced
* prevents activation if PHP version is less than 5.3
* several minor bug fixes 

= 1.6.2.7.1 =
fixes bug where list pagination drops search

= 1.6.2.7 =
bug fixes:
* field/groups deletions in some translated versions
* email sending with multi-page signup forms
* showing time with timestamps in the admin
* otherselect when more than on per form
* dynamic fields getting re-set in record edit form
* strict search setting 

new:
* added "multi-dropdown" form element
* several efficiency optimizations, wider use of data caches
* allow use of "simple" multi-field frontend searches

= 1.6.2.5 =
fixed issue where remote search controls weren't targeting the correct list instance

= 1.6.2.3 =
bug fixes:
list pagination not refreshed with AJAX searches
missing object in PDb_Update_Notices class
fixed db error when user search overrides shortcode filter

implemented -1 list_limit value to show all records

= 1.6.2.2 =
bugfix: 
CAPTCHA not showing previous solution
CAPTCHA help text not shown
horizontal scroll setting on admin list

added top scrollbar to horizontal scroll elements in admin

= 1.6.2.1 =
fixed bug with dropdown- and checkbox-other fields

= 1.6.2 =
bugs fixed:
slashed numeric dates not parsing correctly
total shortcode not totaling data
default images broken
value titles not shown in lists

= 1.6.1 =
minor bug fixes

= 1.6 =

* database optimizations for large data sets
* scripts and stylesheets loaded only on active plugin pages
* code support for multilingual sites
* improvements to multi-page form handling
* improvements to internationalized date handling
* bug when using a single-field form fixed
* plugin now supports custom translation files and most translation plugins
* CSV import now allows delimiters and enclosures to be set by the user
* better support for values titles in search results
* better support for custom search forms
* new shortcode attributes for forms: "autocomplete", "edit_record_page" and "submit_button"
* improved security on user input and form submissions
* improved security on admin functions

= 1.5.4.9 =

* security patch for CSV download
* added Ukranian translation

= 1.5.4.8 =

* compatibility with WP 3.9 and PHP 5.5
* plugin admin menu visibility now controlled by plugin admin roles

= 1.5.4.7 =

* fixed checkbox lock bug

= 1.5.4.6 =

* fixed transaction errors when MySQL is in a strict mode
* checkboxes may now use value titles
* AJAX search response now uses template defined in the shortcode

= 1.5.4.5 =

* added otherselect.js to handle dropdown/other fields
* fixed bug in dropdowns when value is numeric 0

= 1.5.4.4 =

* readonly displays for dropdowns, radios and multiselects
* record updates leave private ID unchanged
* new setting to enable alternative sessions management if PHP sessions is not working
* fixed bug in PDbTemplate class that would return empty fields in a list

= 1.5.4.3 =

* undeclared property $readonly_fields error (this time for sure!)

= 1.5.4.2 =

bug fixes:

* undeclared property $readonly_fields error
* record updates not getting timestamp set
* problem with list search results not coming in in some cases
* readonly fields in form context now have "readonly" attribute instead of "disabled"
* record form now shows captcha if named in the shortcode "fields" attribute
* checkbox series now completely wrapped in checkbox group wrapper

Added Serbian translation

= 1.5.4.1 =

* field group tabs use group name if no title is defined for the group
* HTML entities can be used in all field option ("values") definitions
* fixed long field/group name bug. Names can be up to the maximum 64 characters
* cleaned up plugin function spillover into other admin pages
* better compatibility with pre-3.8 WP installs
* signup and record shortcodes won't try to validate unincluded fields
* all form submissions are validated for all users except plugin admin in the admin section
* in admin, last used settings now retained: sort field, sort order, search field, search operator
* better support for multi-page forms, user can't complete form by going back to the first page
* links and other HTML now allowed in field titles and help text
* field option titles used in all contexts


= 1.5.4 =

* more visual compatibility tweaks with 3.8 WP admin redesign, dashicons
* bug fixes
* plugin classes are now only included on pages with plugin shortcodes
* settings page feedback messages working
* fixed possible class/function collision with other plugins using WP_Session
* now compatible with HTML5 form element types
* bulletproofing, collision avoidance


= 1.5.3 =
** BETA RELEASE **

* admin compatible with WP 3.8
* plugin no longer relies on PHP sessions
* better compatibility with international characters in form validation
* fixed datetime bug with missing server timezone value
* updated POT file, internationalization complete
* added Norwegian translation
* bug fixes

= 1.5.2 =

** BETA RELEASE **
second round of bug fixes:

* absolute image URIs will now display correctly
* frontend list sort preferences are now heeded
* 'the_content' filter applied to rich text if enabled in settings
* improved link field data handling on import, doesn't require valid URL
* missing results on AJAX search bug fixed
* values of zero no longer considered empty
* date fields now stored as BINGINT datatype so it can be sorted correctly
* language files recompiled to correctly show selectors in the manage database fields page
* zero-division bug in Pagination fixed

= 1.5.1 =

** BETA RELEASE **
first round of bug fixes:

* frontend record edit submissions going to admin
* list_limit cannot be set to override pagination
* restored legacy public method Participants_Db::get_image_url()

= 1.5 =

**BETA RELEASE**

Please back up before installing

For critical production sites I recommend you try this new plugin version first in a development site.

* complete overhaul of the entire plugin
* new classes to handle templating and plugin updates
* added infrastructure for add-on and premium plugins
* dozens of bug fixes and code hardening for more reliable performance in your particular installation

**New Features:**

* **file upload field** allows any type of file to be uploaded
* **resend private link** for users who've lost theirs
* **math captcha** sets a simple test for a human user
* **custom CSS setting** for easy presentation tweaks
* **wildcard characters** allowed in searches
* **total shortcode** shows total records and more
* **search shortcode** to place search controls anywhere
* **groups attribute in shortcodes** to show only selected groups of fields
* **date range filters** in the admin list
* **expanded API** for more ways to customize functionality
* **“busy” spinner** image for AJAX-powered searches so the user knows something is happening while the data loads
* **labeled selection items** for better readability

= 1.4.9.3 =
* reworked class autoloading to avoid conflicts
* 'list_query' filter now happens before the length of the list is calculated so if it is altered, the length will be correct
* 'list_query' filter no longer includes the pagination limit statement

= 1.4.9.2 =
* improved date formatting for numeric non-American style dates (dd/mm/yyyy)
* fields omitted from the signup form are no longer validated, making it easier to construct different signup forms
* more control over search/sort dropdowns in the list display (see template)
* signup, record and single shortcodes now have the "fields" attribute
* list shortcode filters now correctly filter multi-select fields
* lists may now be sorted by more than one field in the shortcode
* list shortcode filter statements may now include "or" operators
* read-only text fields show the default value when empty
* adds several API hooks and filters

= 1.4.9.1 =
Taking defensive precautions against other plugins in the admin:
* admin.css is more specific about styling tabs
* no longer using .ui-tabs-hide class to show/hide tabs

= 1.4.9 =
* single record link doesn't get wrapped with anchor tag in some cases; works reliably now
* script handles were conflicting with some other plugins; script handles are now namespaced
* admin menu hook was conflicting with some other plugins; admin menu hooks are now more specific
* readonly fields were erased when record was edited in the frontend
* all date displays are now internationalized
* page links with some post types were incorrect; now using "get_permalink()" for all page links
* PDb_Pagination class was conflicting with some other plugins; renamed to PDb_Pagination
* checkbox fields now allowed for single page link
* default images are now not given full-size link
* checkbox fields now allowed for single page link

= 1.4.8 =
* readonly date fields no formatted
* better handling of multivalue fields
* internal date fields now correctly formatted
* improved safeguards against JS code collisions on the admin pages
* bug where hidden fields cause other fields to be dropped from the display fixed
* better handling of hidden dynamic values
* AJAX list filtering no longer loses pagination element

= 1.4.7 =
* internationalized dates are now displaying consistenyly on all screens
* email headers are now set on a per-message basis to avoid conflicts with text-only emails
* several bug fixes relating to date localization
* added Slovak translation by Branco Radenovich

= 1.4.6 =
* added image delete checkbox (doesn't delete file, only database reference)
* image handle file validation avoids costly CURL calls in validating files
* improved list AJAX javascript
* fixed admin page name conflict with some plugins
* international characters now work properly in user searches
* internationalization of date display
* image uploads now won't overwrite existing files of the same name by adding an index to the name

= 1.4.5.2 =
* fixes several issues brought up by the WP 3.5 release:
* admin section tabs
* plugin icon
* $wpdb->prepare new regimen

= 1.4.5.1 =
* bugfix for admin list javascript bug that deletes all records on a page if you try to delete a record

= 1.4.5 =
* AJAX search/sort internationalization
* fixed pagination issues with WP query-string page links
* improvements and expanded commenting in pdb-list-detailed template
* added database update failsafe to ensure database is in sync

= 1.4.4 =
* improvements to single record display template and stylesheet; less likely to break
* better notations and help text
* several minor bugfixes
* uploaded images and files pathing is now harder to break
* AJAX list searches are now compatible with pagination

= 1.4.3 =
* fixed bug that prevented a new uploads directory from being created in some cases
* it is now possible to have two different list shortcodes on the same page
* using WP auto formatting is now optional on rich-text fields
* fixed incompatibility with PHP 5.4

= 1.4.2 =
* fixes for several reported bugs

= 1.4 =
* now using templates for all shortcode output
* added 'read only' attribute for fields
* added random sort for list output
* added "match other field" validation option for field double-checks
* added default sort order for the admin list
* hidden fields can now capture cookie values as well as server values, WP user data, etc.
* placeholder tags may now be used in email subject lines
* new form element: "Rich Text" a textarea element with a rich text editor
* new form element: "Password" stored as a WP-compatible hash
* added 'search results only' functionality for list shortcode
* list searches now update the list without reloading the page (using AJAX)
* you can define a "default image" to show when no image has been uploaded into a record
* CSV export now requires admin privileges
* improved handling of rich text content displays

== Upgrade Notice ==

= 1.9.5.15 =
is a minor bugfix release


== Plugin Support ==

**[Plugin manual and documentation](https://xnau.com/work/wordpress-plugins/participants-database/participants-database-documentation/?utm_source=wporg_visitor&utm_medium=plugin_page_othernotes__tab&utm_campaign=pdb-addons-promo) is published on the developer's website, xnau.com**

Plugin technical support is available on the [WordPress Plugin Plugin Support Forum](https://wordpress.org/support/plugin/participants-database), and on xnau.com in the comments section.

A growing list of [plugin add-ons and functionality enhancements](https://xnau.com/shop/?utm_source=wporg_visitor&utm_medium=plugin_page_othernotes__tab&utm_campaign=pdb-addons-promo) are also available on xnau.com.
