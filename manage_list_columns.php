<?php
/*
 * sets up the list columns management interface
 */
if ( ! defined( 'ABSPATH' ) ) die;
if (!Participants_Db::current_user_has_plugin_role('admin', 'manage fields')) exit;

PDb_Manage_List_Columns::show_ui();