<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description: 
Version: 1.0.0.10
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_form
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_form
*/

include_once("include/classes.php");
include_once("include/functions.php");

$obj_form = new mf_form();

add_action('cron_base', 'activate_form', mt_rand(1, 10));
add_action('cron_base', array($obj_form, 'cron_base'), mt_rand(1, 10));

add_action('init', array($obj_form, 'init'), 1);

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_form');
	register_deactivation_hook(__FILE__, 'deactivate_form');
	register_uninstall_hook(__FILE__, 'uninstall_form');

	add_action('admin_init', array($obj_form, 'settings_form'));
	add_action('admin_init', array($obj_form, 'admin_init'), 0);
	add_action('admin_menu', array($obj_form, 'admin_menu'));

	add_action('delete_post', array($obj_form, 'delete_post'));
	add_action('deleted_user', array($obj_form, 'deleted_user'));

	add_filter('wp_get_default_privacy_policy_content', array($obj_form, 'add_policy'));
	add_filter('wp_privacy_personal_data_exporters', array($obj_form, 'wp_privacy_personal_data_exporters'), 10);
	add_filter('wp_privacy_personal_data_erasers', array($obj_form, 'wp_privacy_personal_data_erasers'), 10);

	add_filter('count_shortcode_button', array($obj_form, 'count_shortcode_button'));
	add_filter('get_shortcode_output', array($obj_form, 'get_shortcode_output'));
	add_filter('get_shortcode_list', array($obj_form, 'get_shortcode_list'));

	//add_filter('get_user_reminders', array($obj_form, 'get_user_reminders'), 10, 1);
}

else
{
	add_action('wp_head', array($obj_form, 'wp_head'), 0);
	add_action('login_init', array($obj_form, 'login_init'), 0);

	add_filter('the_content', array($obj_form, 'the_content'));
}

add_shortcode('mf_form', array($obj_form, 'shortcode_form'));

add_action('widgets_init', array($obj_form, 'widgets_init'));

/*add_action('wp_ajax_submit_form', 'submit_form');
add_action('wp_ajax_nopriv_submit_form', 'submit_form');*/

add_filter('single_template', 'custom_templates_form');

add_action('phpmailer_init', array($obj_form, 'phpmailer_init'), 0);

load_plugin_textdomain('lang_form', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_form()
{
	global $wpdb;

	$obj_form = new mf_form();

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$arr_add_column = $arr_update_column = $arr_add_index = array();

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form (
		formID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		blogID TINYINT UNSIGNED,
		postID INT UNSIGNED NOT NULL DEFAULT '0',
		formName VARCHAR(100) DEFAULT NULL,
		formSaveIP ENUM('no', 'yes') NOT NULL DEFAULT 'no',
		formAnswerURL VARCHAR(20) DEFAULT NULL,
		formEmail VARCHAR(100) DEFAULT NULL,
		formEmailConditions TEXT DEFAULT NULL,
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
		formTermsPage INT UNSIGNED DEFAULT NULL,
		formPaymentMerchant VARCHAR(100) DEFAULT NULL,
		formPaymentPassword VARCHAR(100) DEFAULT NULL,
		formPaymentCurrency VARCHAR(3),
		formPaymentCheck INT DEFAULT NULL,
		formPaymentCost SMALLINT UNSIGNED DEFAULT NULL,
		formPaymentAmount INT UNSIGNED DEFAULT NULL,
		formPaymentTax TINYINT UNSIGNED DEFAULT NULL,
		formPaymentCallback VARCHAR(100) DEFAULT NULL,
		formCreated DATETIME DEFAULT NULL,
		userID INT UNSIGNED DEFAULT NULL,
		formDeleted ENUM('0', '1') NOT NULL DEFAULT '0',
		formDeletedDate DATETIME DEFAULT NULL,
		formDeletedID INT UNSIGNED DEFAULT NULL,
		PRIMARY KEY (formID),
		KEY blogID (blogID),
		KEY postID (postID)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."form"] = array(
		'formTermsPage' => "ALTER TABLE [table] ADD [column] INT UNSIGNED DEFAULT NULL AFTER formPaymentHmac",
		'formPaymentCallback' => "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER formPaymentAmount",
		'formPaymentTax' => "ALTER TABLE [table] ADD [column] TINYINT UNSIGNED DEFAULT NULL AFTER formPaymentAmount",
		'formSaveIP' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'no' AFTER formName",
		'formPaymentCost' => "ALTER TABLE [table] ADD [column] SMALLINT UNSIGNED DEFAULT NULL AFTER formPaymentCheck",
		'formEmailConditions' => "ALTER TABLE [table] ADD [column] TEXT DEFAULT NULL AFTER formEmail",
	);

	$arr_update_column[$wpdb->base_prefix."form"] = array(
		'formPaymentFunction' => "ALTER TABLE [table] CHANGE [column] formPaymentCallback VARCHAR(100) DEFAULT NULL",
	);

	/*$arr_add_index[$wpdb->base_prefix."form"] = array(
		'' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);*/

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_check (
		checkID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		checkPublic ENUM('0','1'),
		checkName VARCHAR(50),
		checkCode VARCHAR(10),
		checkPattern VARCHAR(200),
		PRIMARY KEY (checkID),
		KEY checkCode (checkCode)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_type (
		formTypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		formTypePublic ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
		formTypeCode VARCHAR(30),
		formTypeName VARCHAR(40) DEFAULT NULL,
		formTypeDesc TEXT DEFAULT NULL,
		formTypeResult ENUM('0','1') NOT NULL DEFAULT '1',
		PRIMARY KEY (formTypeID),
		KEY formTypeResult (formTypeResult)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."form_type"] = array(
		'formTypeDesc' => "ALTER TABLE [table] ADD [column] VARCHAR(40) DEFAULT NULL AFTER formTypeName",
	);

	$arr_update_column[$wpdb->base_prefix."form_type"] = array(
		'formTypeName' => "ALTER TABLE [table] CHANGE [column] formTypeName VARCHAR(40) DEFAULT NULL",
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
		'formTypeDisplay' => "ALTER TABLE [table] ADD [column] ENUM('0','1') NOT NULL DEFAULT '1' AFTER formTypeActionShow",
	);

	if(get_site_option('setting_convert_form_options') == 'yes')
	{
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_option (
			formOptionID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			form2TypeID INT UNSIGNED NOT NULL,
			formOptionKey VARCHAR(10) DEFAULT NULL,
			formOptionValue TEXT,
			formOptionLimit SMALLINT UNSIGNED,
			formOptionOrder INT UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (formOptionID),
			KEY form2TypeID (form2TypeID),
			KEY formOptionOrder (formOptionOrder)
		) DEFAULT CHARSET=".$default_charset);
	}

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
		answerEmail VARCHAR(100),
		answerType VARCHAR(20) DEFAULT NULL,
		answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
		KEY answerID (answerID),
		KEY answerEmail (answerEmail)
	) DEFAULT CHARSET=".$default_charset);

	update_columns($arr_update_column);
	add_columns($arr_add_column);
	add_index($arr_add_index);

	$arr_run_query = array();

	foreach($obj_form->get_form_types() as $key => $value)
	{
		if(!isset($value['public'])){	$value['public'] = 'yes';}

		$arr_run_query[] = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->base_prefix."form_type SET formTypeID = '%d', formTypeCode = %s, formTypeName = %s, formTypeDesc = %s, formTypeResult = '%d', formTypePublic = %s", $key, $value['code'], $value['name'], $value['desc'], $value['result'], $value['public']);
	}

	$arr_form_check = array(
		1 => array('name' => __("Number", 'lang_form'),				'code' => 'int',		'pattern' => '[0-9]*'),
		2 => array('name' => __("Zip Code", 'lang_form'),			'code' => 'zip',		'pattern' => '[0-9]{5}'),
		5 => array('name' => __("E-mail", 'lang_form'),				'code' => 'email',		'pattern' => ''),
		6 => array('name' => __("Phone no", 'lang_form'),			'code' => 'telno',		'pattern' => '\d*'),
		7 => array('name' => __("Decimal number", 'lang_form'),		'code' => 'float',		'pattern' => '[-+]?[0-9]*[.,]?[0-9]+'),
		8 => array('name' => __("URL", 'lang_form'),				'code' => 'url',		'pattern' => ''),
		9 => array('name' => __("Name", 'lang_form'),				'code' => 'name',		'pattern' => ''),
		10 => array('name' => __("Street Address", 'lang_form'),	'code' => 'address',	'pattern' => ''),
		11 => array('name' => __("City", 'lang_form'),				'code' => 'city',		'pattern' => ''),
		11 => array('name' => __("Country", 'lang_form'),			'code' => 'country',	'pattern' => ''),
	);

	if(get_bloginfo('language') == "sv-SE")
	{
		$arr_form_check[3] = array('name' => __("Social security no", 'lang_form')." (8208041234)",		'code' => 'soc',	'pattern' => '[0-9]{10}');
		$arr_form_check[4] = array('name' => __("Social security no", 'lang_form')." (198208041234)",	'code' => 'soc2',	'pattern' => '(?:18|19|20)[0-9]{10}');
	}

	foreach($arr_form_check as $key => $value)
	{
		if(!isset($value['public'])){	$value['public'] = 1;}

		$arr_run_query[] = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->base_prefix."form_check SET checkID = '%d', checkPublic = '%d', checkName = %s, checkCode = %s, checkPattern = %s", $key, $value['public'], $value['name'], $value['code'], $value['pattern']);
	}

	run_queries($arr_run_query);

	// Convert wp_query to wp_posts
	#################################
	/*$result = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."form WHERE post_type = 'mf_form' AND queryConverted = '0'");

	foreach($result as $r)
	{
		$intFormID = $r->formID;
		//...

		//$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET queryConverted = '1' WHERE formID = '%d'", $intFormID));
	}*/
	#################################

	// Move data from old tables to new ones
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

		if(does_table_exist($wpdb->base_prefix.$copy['table_from']))
		{
			$wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix.$copy['table_to']." LIMIT 0, 1");
			$table_to_rows = $wpdb->num_rows;

			if($table_to_rows == 0)
			{
				$wpdb->query("INSERT INTO ".$wpdb->base_prefix.$copy['table_to']." (".$copy['fields_to'].") (SELECT ".$copy['fields_from']." FROM ".$wpdb->base_prefix.$copy['table_from'].")");
			}

			else
			{
				if($option_form_list_viewed > DEFAULT_DATE)
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

	// Start using form_option
	#################################
	if($obj_form->form_option_exists)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT formID, form2TypeID, formTypeText FROM ".$wpdb->base_prefix."form2type WHERE formTypeID IN ('10', '11', '16', '17') AND formTypeText LIKE %s", "%|%"));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intFormID = $r->formID;
				$intForm2TypeID = $r->form2TypeID;
				$strFormTypeText = $r->formTypeText;

				list($strFormLabel, $strFormOptions) = explode(":", $strFormTypeText, 2);

				$arr_options = explode(",", $strFormOptions);

				$success = true;
				$i = 0;

				foreach($arr_options as $str_option)
				{
					@list($option_key, $option_value, $option_limit) = explode("|", $str_option, 3);

					if($option_value != '')
					{
						$intFormOptionID = $wpdb->get_var($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' AND (formOptionKey = %s OR formOptionValue = %s)", $intForm2TypeID, $option_key, $option_value));

						if($intFormOptionID > 0)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_option SET form2TypeID = '%d', formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionOrder = '%d' WHERE formOptionID = '%d'", $intForm2TypeID, $option_key, $option_value, $option_limit, $i, $intFormOptionID));
						}

						else
						{
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_option SET form2TypeID = '%d', formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionOrder = '%d'", $intForm2TypeID, $option_key, $option_value, $option_limit, $i));

							$intFormOptionID = $wpdb->insert_id;
						}

						if($option_key != '')
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = '%d' WHERE form2TypeID = '%d' AND answerText = %s", $intFormOptionID, $intForm2TypeID, $option_key));

							//do_log("Updated form_answer (".$wpdb->last_query.")");
						}

						$i++;
					}

					else
					{
						$success = false;

						do_log("There was no value for the option (".$intFormID.", ".$intForm2TypeID.", ".$strFormTypeText." -> ".$str_option.")");
					}
				}

				if($success == true)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeText = %s WHERE form2TypeID = '%d'", $strFormLabel, $intForm2TypeID));
				}
			}
		}
	}
	#################################

	replace_user_meta(array('old' => 'answer_viewed', 'new' => 'meta_answer_viewed'));
	replace_user_meta(array('old' => 'mf_forms_viewed', 'new' => 'meta_forms_viewed'));
}

function deactivate_form()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_form_permission', 'setting_form_reload'),
		'meta' => array('meta_answer_viewed'),
		'tables' => array('form_check', 'form_type', 'form_spam', 'form_zipcode', 'query_check', 'query_type', 'query_zipcode'),
	));
}

function uninstall_form()
{
	global $obj_form;

	mf_uninstall_plugin(array(
		'uploads' => $obj_form->post_type,
		'options' => array('setting_redirect_emails', 'setting_form_test_emails', 'setting_form_permission_see_all', 'setting_form_permission_edit_all', 'setting_replacement_form', 'setting_replacement_form_text', 'setting_link_yes_text', 'setting_link_no_text', 'setting_link_thanks_text', 'setting_convert_form_options', 'option_form_list_viewed'),
		'meta' => array('meta_forms_viewed'),
		'post_types' => array($obj_form->post_type),
		'tables' => array('form', 'form_option', 'form2answer', 'form2type', 'form_answer', 'form_answer_email'),
	));
}

function custom_templates_form($single_template)
{
	global $post, $obj_form;

	if($post->post_type == $obj_form->post_type)
	{
		$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
	}

	return $single_template;
}