<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description:
Version: 1.1.7.1
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_form
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_form
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_form = new mf_form();

	add_action('cron_base', 'activate_form', mt_rand(1, 10));
	add_action('cron_base', array($obj_form, 'cron_base'), mt_rand(1, 10));

	add_action('init', array($obj_form, 'init'));

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_form');
		register_uninstall_hook(__FILE__, 'uninstall_form');

		add_action('admin_init', array($obj_form, 'settings_form'));
		add_action('admin_init', array($obj_form, 'admin_init'), 0);
		add_action('admin_menu', array($obj_form, 'admin_menu'));

		add_filter('filter_sites_table_settings', array($obj_form, 'filter_sites_table_settings'));
		add_filter('filter_sites_table_pages', array($obj_form, 'filter_sites_table_pages'));

		add_filter('manage_'.$obj_form->post_type.'_posts_columns', array($obj_form, 'column_header'), 5);
		add_action('manage_'.$obj_form->post_type.'_posts_custom_column', array($obj_form, 'column_cell'), 5, 2);

		add_filter('post_row_actions', array($obj_form, 'row_actions'), 10, 2);
		add_filter('page_row_actions', array($obj_form, 'row_actions'), 10, 2);

		add_action('rwmb_meta_boxes', array($obj_form, 'rwmb_meta_boxes'));

		//add_action('wp_trash_post', array($obj_form, 'wp_trash_post'));
		add_action('wp_delete_post', array($obj_form, 'wp_delete_post'));
		add_action('deleted_user', array($obj_form, 'deleted_user'));

		add_action('do_clone_site', array($obj_form, 'do_clone_site'));
		add_action('do_switch_sites', array($obj_form, 'do_switch_sites'));

		add_filter('filter_last_updated_post_types', array($obj_form, 'filter_last_updated_post_types'), 10, 2);

		add_filter('wp_privacy_personal_data_exporters', array($obj_form, 'wp_privacy_personal_data_exporters'), 10);
		add_filter('wp_privacy_personal_data_erasers', array($obj_form, 'wp_privacy_personal_data_erasers'), 10);

		//add_filter('count_shortcode_button', array($obj_form, 'count_shortcode_button'));
		//add_filter('get_shortcode_output', array($obj_form, 'get_shortcode_output'));
		//add_filter('get_shortcode_list', array($obj_form, 'get_shortcode_list'));
	}

	else
	{
		add_filter('wp_sitemaps_post_types', array($obj_form, 'wp_sitemaps_post_types'));

		add_action('wp_head', array($obj_form, 'wp_head'), 0);
		add_action('login_init', array($obj_form, 'login_init'), 0);

		add_filter('the_content', array($obj_form, 'the_content'));
	}

	//add_filter('get_post_types_for_metabox', array($obj_form, 'get_post_types_for_metabox'));

	add_shortcode($obj_form->post_type, array($obj_form, 'shortcode_form'));

	add_action('widgets_init', array($obj_form, 'widgets_init'));

	add_filter('single_template', 'custom_templates_form');

	add_action('phpmailer_init', array($obj_form, 'phpmailer_init'), 0);

	add_action('wp_ajax_api_form_nonce', array($obj_form, 'api_form_nonce'));
	add_action('wp_ajax_nopriv_api_form_nonce', array($obj_form, 'api_form_nonce'));

	/*add_action('wp_ajax_api_form_zipcode', array($obj_form, 'api_form_zipcode'));
	add_action('wp_ajax_nopriv_api_form_zipcode', array($obj_form, 'api_form_zipcode'));*/

	function activate_form()
	{
		global $wpdb, $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

		$arr_add_column = $arr_update_column = $arr_add_index = array();

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form (
			formID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			blogID TINYINT UNSIGNED,
			postID INT UNSIGNED NOT NULL DEFAULT '0',"
			//."formCreated DATETIME DEFAULT NULL,"
			."userID INT UNSIGNED DEFAULT NULL,"
			//."formDeleted ENUM('0', '1') NOT NULL DEFAULT '0',"
			//."formDeletedDate DATETIME DEFAULT NULL,"
			//."formDeletedID INT UNSIGNED DEFAULT NULL,"
			."PRIMARY KEY (formID),
			KEY blogID (blogID),
			KEY postID (postID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."form"] = array(
			//'' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'yes' AFTER ",
		);

		$arr_update_column[$wpdb->base_prefix."form"] = array(
			'formDeletedID' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formCreated' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formDeletedDate' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formDeleted' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form2type (
			form2TypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			form2TypeID2 INT UNSIGNED NOT NULL DEFAULT '0',
			formID INT UNSIGNED DEFAULT '0',
			formTypeID INT UNSIGNED DEFAULT '0',
			formTypeText TEXT,
			formTypePlaceholder VARCHAR(100),
			checkID INT UNSIGNED DEFAULT NULL,
			formTypeTag VARCHAR(20) DEFAULT NULL,
			formTypeClass VARCHAR(50) DEFAULT NULL,
			formTypeLength SMALLINT DEFAULT NULL,
			formTypeFetchFrom TEXT DEFAULT NULL,
			formTypeConnectTo INT UNSIGNED NOT NULL DEFAULT '0',
			formTypeActionEquals VARCHAR(10),
			formTypeActionShow INT UNSIGNED NOT NULL DEFAULT '0',
			formTypeDisplay ENUM('0','1') NOT NULL DEFAULT '1',
			formTypeRequired ENUM('0','1') NOT NULL DEFAULT '0',
			formTypeAutofocus ENUM('0','1') NOT NULL DEFAULT '0',
			formTypeRemember ENUM('0','1') NOT NULL DEFAULT '0',
			form2TypeOrder INT UNSIGNED NOT NULL DEFAULT '0',
			form2TypeCreated DATETIME DEFAULT NULL,
			userID INT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (form2TypeID),
			KEY formID (formID),
			KEY formTypeID (formTypeID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."form2type"] = array(
			'formTypeConnectTo' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER formTypeFetchFrom",
			'formTypeLength' => "ALTER TABLE [table] ADD [column] SMALLINT DEFAULT NULL AFTER formTypeClass",
		);

		$arr_update_column[$wpdb->base_prefix."form2type"] = array(
			'formTypeFetchFrom' => "ALTER TABLE [table] CHANGE [column] formTypeFetchFrom TEXT DEFAULT NULL",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_option (
			formOptionID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			form2TypeID INT UNSIGNED NOT NULL,
			formOptionKey VARCHAR(10) DEFAULT NULL,
			formOptionValue TEXT,
			formOptionLimit SMALLINT UNSIGNED,
			formOptionAction INT UNSIGNED,
			formOptionOrder INT UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (formOptionID),
			KEY form2TypeID (form2TypeID),
			KEY formOptionOrder (formOptionOrder)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."form_option"] = array(
			//'' => "ALTER TABLE [table] ADD [column] INT UNSIGNED AFTER ",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form2answer (
			answerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			formID INT UNSIGNED NOT NULL,
			answerIP VARCHAR(32) DEFAULT NULL,
			answerSpam ENUM('0', '1') NOT NULL DEFAULT '0',
			spamID SMALLINT NOT NULL DEFAULT '0',
			answerToken VARCHAR(100) DEFAULT NULL,
			answerCreated DATETIME DEFAULT NULL,
			PRIMARY KEY (answerID),
			KEY formID (formID),
			KEY answerCreated (answerCreated)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."form2answer"] = array(
			//'' => "ALTER TABLE [table] ADD [column] SMALLINT NOT NULL DEFAULT '0' AFTER ",
		);

		$arr_update_column[$wpdb->base_prefix."form2answer"] = array(
			//'answerIP' => "ALTER TABLE [table] CHANGE [column] answerIP VARCHAR(32) DEFAULT NULL", //221007
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_answer (
			answerID INT UNSIGNED DEFAULT NULL,
			form2TypeID INT UNSIGNED DEFAULT '0',
			answerText TEXT,
			KEY form2TypeID (form2TypeID),
			KEY answerID (answerID)
		) DEFAULT CHARSET=".$default_charset);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_answer_meta (
			answerID INT UNSIGNED DEFAULT NULL,
			metaKey VARCHAR(40) DEFAULT NULL,
			metaValue TEXT,
			KEY answerID (answerID),
			KEY metaKey (metaKey)
		) DEFAULT CHARSET=".$default_charset);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_answer_email (
			answerID INT UNSIGNED DEFAULT NULL,
			answerEmailFrom VARCHAR(100),
			answerEmail VARCHAR(100),
			answerType VARCHAR(20) DEFAULT NULL,
			answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
			KEY answerID (answerID),
			KEY answerEmail (answerEmail)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."form_answer_email"] = array(
			//'' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER ",
			'answerEmailFrom' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER answerID",
		);

		update_columns($arr_update_column);
		add_columns($arr_add_column);
		add_index($arr_add_index);
	}

	function uninstall_form()
	{
		include_once("include/classes.php");

		$obj_form = new mf_form();

		mf_uninstall_plugin(array(
			'uploads' => $obj_form->post_type,
			'options' => array('setting_form_redirect_emails', 'setting_form_test_emails', 'setting_form_permission_see_all', 'setting_form_permission_edit_all', 'setting_form_replacement', 'setting_form_replacement_text', 'setting_link_yes_text', 'setting_link_no_text', 'setting_link_thanks_text', 'option_form_list_viewed'),
			'meta' => array('meta_forms_viewed'),
			'post_types' => array($obj_form->post_type),
			'tables' => array('form', 'form_check', 'form_type', 'form_option', 'form2answer', 'form2type', 'form_answer', 'form_answer_email', 'form_nonce'),
		));
	}

	function custom_templates_form($single_template)
	{
		global $post, $obj_form;

		if($post->post_type == $obj_form->post_type)
		{
			// Get HTML from a generic page instead

			$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
		}

		return $single_template;
	}
}