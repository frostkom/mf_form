<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description: 
Version: 11.2.7
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_form
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_form
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'activate_form', mt_rand(1, 10));
add_action('cron_base', 'cron_form', mt_rand(1, 10));

add_action('init', 'init_form', 1);
add_action('widgets_init', 'widgets_form');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_form');
	register_deactivation_hook(__FILE__, 'deactivate_form');
	register_uninstall_hook(__FILE__, 'uninstall_form');

	add_action('admin_init', 'settings_form');
	add_action('admin_menu', 'menu_form');
	add_action('delete_post', 'delete_form');
	add_action('deleted_user', 'deleted_user_form');

	add_filter('count_shortcode_button', 'count_shortcode_button_form');
	add_filter('get_shortcode_output', 'get_shortcode_output_form');
	add_filter('get_shortcode_list', 'get_shortcode_list_form');

	//$obj_form = new mf_form();
	//add_action('post_updated', array($obj_form, 'post_updated'), 10, 3);
	//add_filter('get_user_reminders', array($obj_form, 'get_user_reminders'), 10, 1);
}

add_shortcode('mf_form', 'shortcode_form');
add_action('wp_ajax_submit_form', 'submit_form');
add_action('wp_ajax_nopriv_submit_form', 'submit_form');

add_filter('single_template', 'custom_templates_form');

add_action('phpmailer_init', 'phpmailer_init_form', 0);

load_plugin_textdomain('lang_form', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_form()
{
	global $wpdb;

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$arr_add_column = $arr_update_column = $arr_add_index = array();

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form (
		formID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		blogID TINYINT UNSIGNED,
		postID INT UNSIGNED NOT NULL DEFAULT '0',
		formName VARCHAR(100) DEFAULT NULL,
		formAnswerURL VARCHAR(20) DEFAULT NULL,
		formEmail VARCHAR(100) DEFAULT NULL,
		formEmailNotify ENUM('0', '1') NOT NULL DEFAULT '1',
		formEmailNotifyPage INT UNSIGNED NOT NULL DEFAULT '0',
		formEmailName VARCHAR(100) DEFAULT NULL,
		formEmailConfirm ENUM('0', '1') NOT NULL DEFAULT '0',
		formEmailConfirmPage INT UNSIGNED NOT NULL DEFAULT '0',
		formShowAnswers ENUM('0', '1') NOT NULL DEFAULT '0',
		formMandatoryText VARCHAR(100) DEFAULT NULL,
		formButtonText VARCHAR(100) DEFAULT NULL,
		formButtonSymbol VARCHAR(20) DEFAULT NULL,
		formPaymentProvider INT DEFAULT NULL,
		formPaymentHmac VARCHAR(200) DEFAULT NULL,
		formPaymentMerchant VARCHAR(100) DEFAULT NULL,
		formPaymentPassword VARCHAR(100) DEFAULT NULL,
		formPaymentCurrency VARCHAR(3),
		formPaymentCheck INT DEFAULT NULL,
		formPaymentAmount INT DEFAULT NULL,
		formCreated DATETIME DEFAULT NULL,
		formDeleted ENUM('0', '1') NOT NULL DEFAULT '0',
		formDeletedDate DATETIME DEFAULT NULL,
		formDeletedID INT UNSIGNED DEFAULT '0',
		userID INT UNSIGNED DEFAULT '0',
		PRIMARY KEY (formID),
		KEY blogID (blogID),
		KEY postID (postID)
	) DEFAULT CHARSET=".$default_charset); //queryConverted ENUM('0', '1') NOT NULL DEFAULT '0',

	$arr_add_column[$wpdb->base_prefix."form"] = array(
		//'queryConverted' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER userID",
	);

	$arr_update_column[$wpdb->base_prefix."form"] = array(
		//'formAnswerURL' => "ALTER TABLE [table] CHANGE [column] [column] INT UNSIGNED NOT NULL DEFAULT '0'",
	);

	$arr_add_index[$wpdb->base_prefix."form"] = array(
		//'blogID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
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
		formTypeFetchFrom VARCHAR(50) DEFAULT NULL,
		formTypeActionEquals VARCHAR(10),
		formTypeActionShow INT UNSIGNED NOT NULL DEFAULT '0',
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
		//'formTypeAutofocus' => "ALTER TABLE [table] ADD [column] ENUM('0','1') NOT NULL DEFAULT '0' AFTER formTypeClass",
	);

	$arr_update_column[$wpdb->base_prefix."form2type"] = array(
		//'form2TypeID' => "ALTER TABLE [table] CHANGE [column] form2TypeID INT UNSIGNED NOT NULL AUTO_INCREMENT",
	);

	$arr_add_column[$wpdb->base_prefix."form2type"] = array(
		//'answerSpam' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER answerIP",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form2answer (
		answerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		formID INT UNSIGNED NOT NULL,
		answerIP VARCHAR(15) DEFAULT NULL,
		answerSpam ENUM('0', '1') NOT NULL DEFAULT '0',
		spamID SMALLINT NOT NULL DEFAULT '0',
		answerToken VARCHAR(100) DEFAULT NULL,
		answerCreated DATETIME DEFAULT NULL,
		PRIMARY KEY (answerID),
		KEY formID (formID),
		KEY answerCreated (answerCreated)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."form2answer"] = array(
		'spamID' => "ALTER TABLE [table] ADD [column] SMALLINT NOT NULL DEFAULT '0' AFTER answerSpam",
	);

	$arr_add_index[$wpdb->base_prefix."form2answer"] = array(
		//'answerCreated' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_answer (
		answerID INT UNSIGNED DEFAULT NULL,
		form2TypeID INT UNSIGNED DEFAULT '0',
		answerText TEXT,
		KEY form2TypeID (form2TypeID),
		KEY answerID (answerID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_answer_email (
		answerID INT UNSIGNED DEFAULT NULL,
		answerEmail VARCHAR(100),
		answerType VARCHAR(20) DEFAULT NULL,
		answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
		KEY answerID (answerID),
		KEY answerEmail (answerEmail)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."form_answer_email"] = array(
		//'answerType' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER answerEmail",
	);

	$arr_add_index[$wpdb->base_prefix."form_answer_email"] = array(
		//'answerEmail' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_check (
		checkID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		checkPublic ENUM('0','1'),
		checkName VARCHAR(50),
		checkCode VARCHAR(10),
		checkPattern VARCHAR(200),
		PRIMARY KEY (checkID),
		KEY checkCode (checkCode)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_index[$wpdb->base_prefix."form_check"] = array(
		//'checkCode' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_type (
		formTypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		formTypePublic ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
		formTypeCode VARCHAR(30),
		formTypeName VARCHAR(30) DEFAULT NULL,
		formTypeResult ENUM('0','1') NOT NULL DEFAULT '1',
		PRIMARY KEY (formTypeID),
		KEY formTypeResult (formTypeResult)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_index[$wpdb->base_prefix."form_type"] = array(
		//'formTypeResult' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	/*$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_spam (
		spamID INT unsigned NOT NULL AUTO_INCREMENT,
		spamInclude VARCHAR(30) DEFAULT NULL,
		spamExclude VARCHAR(30) DEFAULT NULL,
		spamText VARCHAR(100) DEFAULT NULL,
		spamExplain VARCHAR(50) DEFAULT NULL,
		PRIMARY KEY (spamID)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."form_spam"] = array(
		'spamExplain' => "ALTER TABLE [table] ADD [column] VARCHAR(50) DEFAULT NULL AFTER spamText",
	);*/

	update_columns($arr_update_column);
	add_columns($arr_add_column);
	add_index($arr_add_index);

	$arr_run_query = array();

	$arr_query_types = array(
		1 => array('code' => 'checkbox',			'name' => __("Checkbox", 'lang_form'),				'result' => 1),
		2 => array('code' => 'range',				'name' => __("Range", 'lang_form'),					'result' => 1),
		3 => array('code' => 'input_field',			'name' => __("Input Field", 'lang_form'),			'result' => 1),
		4 => array('code' => 'textarea',			'name' => __("Textarea", 'lang_form'),				'result' => 1),
		5 => array('code' => 'text',				'name' => __("Text", 'lang_form'),					'result' => 0),
		6 => array('code' => 'space',				'name' => __("Space", 'lang_form'),					'result' => 0),
		7 => array('code' => 'datepicker',			'name' => __("Datepicker", 'lang_form'),			'result' => 1),
		8 => array('code' => 'radio_button',		'name' => __("Radio Button", 'lang_form'),			'result' => 1),
		9 => array('code' => 'referer_url',			'name' => __("Referer URL", 'lang_form'),			'result' => 1),
		10 => array('code' => 'select',				'name' => __("Dropdown", 'lang_form'),				'result' => 1),
		11 => array('code' => 'select_multiple',	'name' => __("Multiple Selection", 'lang_form'),	'result' => 1),
		12 => array('code' => 'hidden_field',		'name' => __("Hidden Field", 'lang_form'),			'result' => 1),
		13 => array('code' => 'custom_tag',			'name' => __("Custom Tag", 'lang_form'),			'result' => 0),
		14 => array('code' => 'custom_tag_end',		'name' => __("Custom Tag (end)", 'lang_form'),		'result' => 0,		'public' => 'no'),
		15 => array('code' => 'file',				'name' => __("File", 'lang_form'),					'result' => 1),
		16 => array('code' => 'checkbox_multiple',	'name' => __("Multiple Checkboxes", 'lang_form'),	'result' => 1),
	);

	foreach($arr_query_types as $key => $value)
	{
		if(!isset($value['public'])){	$value['public'] = 'yes';}

		$arr_run_query[] = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->base_prefix."form_type SET formTypeID = '%d', formTypeCode = %s, formTypeName = %s, formTypeResult = '%d', formTypePublic = %s", $key, $value['code'], $value['name'], $value['result'], $value['public']);
	}

	$arr_query_check = array(
		1 => array('name' => __("Number", 'lang_form'),				'code' => 'int',		'pattern' => '[0-9]*'),
		5 => array('name' => __("E-mail", 'lang_form'),				'code' => 'email',		'pattern' => ''),
		6 => array('name' => __("Phone no", 'lang_form'),			'code' => 'telno',		'pattern' => ''),
		7 => array('name' => __("Decimal number", 'lang_form'),		'code' => 'float',		'pattern' => '[-+]?[0-9]*[.,]?[0-9]+'),
		8 => array('name' => __("URL", 'lang_form'),				'code' => 'url',		'pattern' => ''),
	);

	if(get_bloginfo('language') == "sv-SE")
	{
		$arr_query_check[2] = array('name' => __("Zip code", 'lang_form'),								'code' => 'zip',	'pattern' => '[0-9]{5}');
		$arr_query_check[3] = array('name' => __("Social security no", 'lang_form')." (8208041234)",	'code' => 'soc',	'pattern' => '[0-9]{10}');
		$arr_query_check[4] = array('name' => __("Social security no", 'lang_form')." (198208041234)",	'code' => 'soc2',	'pattern' => '(?:18|19|20)[0-9]{10}');
	}

	foreach($arr_query_check as $key => $value)
	{
		if(!isset($value['public'])){	$value['public'] = 1;}

		$arr_run_query[] = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->base_prefix."form_check SET checkID = '%d', checkPublic = '%d', checkName = %s, checkCode = %s, checkPattern = %s", $key, $value['public'], $value['name'], $value['code'], $value['pattern']);
	}

	run_queries($arr_run_query);

	//Check for loose ends
	#################################
	/*$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->base_prefix."form ON ".$wpdb->posts.".ID = ".$wpdb->base_prefix."form.postID WHERE post_type = 'mf_form' AND postID IS NULL");

	foreach($result as $r)
	{
		do_log(__("Remove from wp_posts because the corresponding form in wp_form does not exist", 'lang_form'));

		//wp_trash_post($r->ID);
	}*/
	#################################

	//Convert wp_query to wp_posts
	#################################
	/*$result = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."form WHERE post_type = 'mf_form' AND queryConverted = '0'");

	foreach($result as $r)
	{
		$intFormID = $r->formID;
		//...

		//$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET queryConverted = '1' WHERE formID = '%d'", $intFormID));
	}*/
	#################################

	//Move data from old tables to new ones
	#################################
	$arr_copy = array();

	$arr_copy[] = array(
		'table_from' => "query",
		'fields_from' => "queryID, blogID, postID, queryName, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailNotifyPage, queryEmailName, queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryMandatoryText, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryPaymentCheck, queryPaymentAmount, queryCreated, queryDeleted, queryDeletedDate, queryDeletedID, userID",

		'table_to' => "form",
		'fields_to' => "formID, blogID, postID, formName, formAnswerURL, formEmail, formEmailNotify, formEmailNotifyPage, formEmailName, formEmailConfirm, formEmailConfirmPage, formShowAnswers, formMandatoryText, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formPaymentMerchant, formPaymentPassword, formPaymentCurrency, formPaymentCheck, formPaymentAmount, formCreated, formDeleted, formDeletedDate, formDeletedID, userID",
	);

	$arr_copy[] = array(
		'table_from' => "query2type",
		'fields_from' => "query2TypeID, query2TypeID2, queryID, queryTypeID, queryTypeText, queryTypePlaceholder, checkID, queryTypeTag, queryTypeClass, queryTypeFetchFrom, queryTypeActionEquals, queryTypeActionShow, queryTypeRequired, queryTypeAutofocus, queryTypeRemember, query2TypeOrder, query2TypeCreated, userID",

		'table_to' => "form2type",
		'fields_to' => "form2TypeID, form2TypeID2, formID, formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow, formTypeRequired, formTypeAutofocus, formTypeRemember, form2TypeOrder, form2TypeCreated, userID",
	);

	$arr_copy[] = array(
		'table_from' => "query2answer",
		'fields_from' => "answerID, queryID, answerIP, answerSpam, answerToken, answerCreated",

		'table_to' => "form2answer",
		'fields_to' => "answerID, formID, answerIP, answerSpam, answerToken, answerCreated",
	);

	$arr_copy[] = array(
		'table_from' => "query_answer",
		'fields_from' => "answerID, query2TypeID, answerText",

		'table_to' => "form_answer",
		'fields_to' => "answerID, form2TypeID, answerText",
	);

	$arr_copy[] = array(
		'table_from' => "query_answer_email",
		'fields_from' => "answerID, answerEmail, answerType, answerSent",

		'table_to' => "form_answer_email",
		'fields_to' => "answerID, answerEmail, answerType, answerSent",
	);

	$option_form_list_viewed = get_option('option_form_list_viewed');

	foreach($arr_copy as $copy)
	{
		$log_message = sprintf(__("I am about to drop the table %s. Go to %sForms%s and make sure that the forms are working as they should before I do this.", 'lang_form'), $wpdb->base_prefix.$copy['table_from'], "<a href='".admin_url("admin.php?page=mf_form/list/index.php")."'>", "</a>");

		$wpdb->get_results("SHOW TABLES LIKE '".$wpdb->base_prefix.$copy['table_from']."'");
		$table_from_exists = $wpdb->num_rows;

		if($table_from_exists > 0)
		{
			$wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix.$copy['table_to']." LIMIT 0, 1");
			$table_to_rows = $wpdb->num_rows;

			if($table_to_rows == 0)
			{
				$wpdb->query("INSERT INTO ".$wpdb->base_prefix.$copy['table_to']." (".$copy['fields_to'].") (SELECT ".$copy['fields_from']." FROM ".$wpdb->base_prefix.$copy['table_from'].")");
			}

			else
			{
				/*$wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix.$copy['table_to']);
				$rows_table_to = $wpdb->num_rows;

				$wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix.$copy['table_from']);
				$rows_table_from = $wpdb->num_rows;*/

				if($option_form_list_viewed > DEFAULT_DATE) //$rows_table_to >= $rows_table_from &&
				{
					if($option_form_list_viewed < date("Y-m-d H:i:s", strtotime("-1 week")))
					{
						$wpdb->query("TRUNCATE ".$wpdb->base_prefix.$copy['table_from']);
						$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->base_prefix.$copy['table_from']);
					}
				}

				else
				{
					do_log($log_message);
				}
			}
		}

		else
		{
			do_log($log_message, 'trash');
		}
	}
	#################################
}

function deactivate_form()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_form_permission'),
		'tables' => array('form_check', 'form_type', 'form_spam', 'form_zipcode', 'query_check', 'query_type', 'query_zipcode'),
	));
}

function uninstall_form()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_form',
		'options' => array('setting_redirect_emails', 'setting_form_test_emails', 'setting_form_permission_see_all', 'setting_replacement_form', 'setting_replacement_form_text', 'setting_form_reload', 'setting_link_yes_text', 'setting_link_no_text', 'setting_link_thanks_text', 'option_form_list_viewed'), //, 'mf_forms_viewed', 'answer_viewed'
		'post_types' => array('mf_form'),
		'tables' => array('form', 'form2answer', 'form2type', 'form_answer', 'form_answer_email', 'form_check', 'form_type', 'form_spam', 'form_zipcode'),
	));
}

function custom_templates_form($single_template)
{
	global $post;

	if(in_array($post->post_type, array("mf_form")))
	{
		$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
	}

	return $single_template;
}