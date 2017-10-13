<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description: 
Version: 10.6.13
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

	$obj_form = new mf_form();

	add_action('post_updated', array($obj_form, 'post_updated'), 10, 3);
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

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query (
		queryID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		blogID TINYINT UNSIGNED,
		postID INT UNSIGNED NOT NULL DEFAULT '0',
		queryName VARCHAR(100) DEFAULT NULL,
		queryAnswerURL VARCHAR(20) DEFAULT NULL,
		queryEmail VARCHAR(100) DEFAULT NULL,
		queryEmailNotify ENUM('0', '1') NOT NULL DEFAULT '1',
		queryEmailNotifyPage INT UNSIGNED NOT NULL DEFAULT '0',
		queryEmailName VARCHAR(100) DEFAULT NULL,
		queryEmailConfirm ENUM('0', '1') NOT NULL DEFAULT '0',
		queryEmailConfirmPage INT UNSIGNED NOT NULL DEFAULT '0',
		queryShowAnswers ENUM('0', '1') NOT NULL DEFAULT '0',
		queryMandatoryText VARCHAR(100) DEFAULT NULL,
		queryButtonText VARCHAR(100) DEFAULT NULL,
		queryButtonSymbol VARCHAR(20) DEFAULT NULL,
		queryPaymentProvider INT DEFAULT NULL,
		queryPaymentHmac VARCHAR(200) DEFAULT NULL,
		queryPaymentMerchant VARCHAR(100) DEFAULT NULL,
		queryPaymentPassword VARCHAR(100) DEFAULT NULL,
		queryPaymentCurrency VARCHAR(3),
		queryPaymentCheck INT DEFAULT NULL,
		queryPaymentAmount INT DEFAULT NULL,
		queryCreated DATETIME DEFAULT NULL,
		queryDeleted ENUM('0', '1') NOT NULL DEFAULT '0',
		queryDeletedDate DATETIME DEFAULT NULL,
		queryDeletedID INT UNSIGNED DEFAULT '0',
		userID INT UNSIGNED DEFAULT '0',
		PRIMARY KEY (queryID),
		KEY blogID (blogID),
		KEY postID (postID)
	) DEFAULT CHARSET=".$default_charset); //queryConverted ENUM('0', '1') NOT NULL DEFAULT '0',

	$arr_add_column[$wpdb->base_prefix."query"] = array(
		'queryDeleted' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryCreated",
		'queryDeletedDate' => "ALTER TABLE [table] ADD [column] DATETIME DEFAULT NULL AFTER queryDeleted",
		'queryDeletedID' => "ALTER TABLE [table] ADD [column] INT UNSIGNED DEFAULT '0' AFTER queryDeletedDate",
		'queryPaymentCheck' => "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryButtonText",
		'queryPaymentAmount' => "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryPaymentCheck",
		'queryPaymentHmac' => "ALTER TABLE [table] ADD [column] VARCHAR(200) DEFAULT NULL AFTER queryButtonText",
		'queryPaymentMerchant' => "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER queryPaymentHmac",
		'queryPaymentPassword' => "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER queryPaymentMerchant",
		'queryEmailConfirmPage' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryEmailConfirm",
		'blogID' => "ALTER TABLE [table] ADD [column] INT AFTER queryID",
		'queryPaymentProvider' => "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryButtonText",
		'queryPaymentCurrency' => "ALTER TABLE [table] ADD [column] VARCHAR(3) AFTER queryPaymentMerchant",
		'queryButtonSymbol' => "ALTER TABLE [table] ADD [column] VARCHAR(20) AFTER queryButtonText",
		'postID' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER blogID",
		'queryEmailNotifyPage' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryEmailNotify",
		//'queryConverted' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER userID",
	);

	$arr_update_column[$wpdb->base_prefix."query"] = array(
		'queryAnswerURL' => "ALTER TABLE [table] CHANGE [column] [column] INT UNSIGNED NOT NULL DEFAULT '0'",
		'queryEmailConfirmPage' => "ALTER TABLE [table] CHANGE [column] [column] INT UNSIGNED NOT NULL DEFAULT '0'",
	);

	$arr_add_index[$wpdb->base_prefix."query"] = array(
		'blogID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
		'postID' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query2type (
		query2TypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		query2TypeID2 INT UNSIGNED NOT NULL DEFAULT '0',
		queryID INT UNSIGNED DEFAULT '0',
		queryTypeID INT UNSIGNED DEFAULT '0',
		queryTypeText TEXT,
		queryTypePlaceholder VARCHAR(100),
		checkID INT UNSIGNED DEFAULT NULL,
		queryTypeTag VARCHAR(20) DEFAULT NULL,
		queryTypeClass VARCHAR(50) DEFAULT NULL,
		queryTypeFetchFrom VARCHAR(50) DEFAULT NULL,
		queryTypeActionEquals VARCHAR(10),
		queryTypeActionShow INT UNSIGNED NOT NULL DEFAULT '0',
		queryTypeRequired ENUM('0','1') NOT NULL DEFAULT '0',
		queryTypeAutofocus ENUM('0','1') NOT NULL DEFAULT '0',
		queryTypeRemember ENUM('0','1') NOT NULL DEFAULT '0',
		query2TypeOrder INT UNSIGNED NOT NULL DEFAULT '0',
		query2TypeCreated DATETIME DEFAULT NULL,
		userID INT UNSIGNED DEFAULT NULL,
		PRIMARY KEY (query2TypeID),
		KEY queryID (queryID),
		KEY queryTypeID (queryTypeID)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."query2type"] = array(
		'queryTypeAutofocus' => "ALTER TABLE [table] ADD [column] ENUM('0','1') NOT NULL DEFAULT '0' AFTER queryTypeClass",
		'queryTypePlaceholder' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryTypeText",
		'queryTypeTag' => "ALTER TABLE [table] ADD [column] VARCHAR(20) AFTER checkID",
		'query2TypeID2' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER query2TypeID",
		'queryTypeFetchFrom' => "ALTER TABLE [table] ADD [column] VARCHAR(50) AFTER queryTypeClass",
		'queryTypeActionEquals' => "ALTER TABLE [table] ADD [column] VARCHAR(10) AFTER queryTypeFetchFrom",
		'queryTypeActionShow' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER queryTypeActionEquals",
		'queryTypeRemember' => "ALTER TABLE [table] ADD [column] ENUM('0','1') NOT NULL DEFAULT '0' AFTER queryTypeAutofocus",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query2answer (
		answerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		queryID INT UNSIGNED NOT NULL,
		answerIP VARCHAR(15) DEFAULT NULL,
		answerSpam ENUM('0', '1') NOT NULL DEFAULT '0',
		answerToken VARCHAR(100) DEFAULT NULL,
		answerCreated DATETIME DEFAULT NULL,
		PRIMARY KEY (answerID),
		KEY queryID (queryID),
		KEY answerCreated (answerCreated)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."query2answer"] = array(
		'answerSpam' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER answerIP",
	);

	$arr_add_index[$wpdb->base_prefix."query2answer"] = array(
		'answerCreated' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_answer (
		answerID INT UNSIGNED DEFAULT NULL,
		query2TypeID INT UNSIGNED DEFAULT '0',
		answerText TEXT,
		KEY query2TypeID (query2TypeID),
		KEY answerID (answerID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_answer_email (
		answerID INT UNSIGNED DEFAULT NULL,
		answerEmail VARCHAR(100),
		answerType VARCHAR(20) DEFAULT NULL,
		answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
		KEY answerID (answerID),
		KEY answerEmail (answerEmail)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."query_answer_email"] = array(
		'answerType' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER answerEmail",
	);

	$arr_add_index[$wpdb->base_prefix."query_answer_email"] = array(
		'answerEmail' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_check (
		checkID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		checkPublic ENUM('0','1'),
		checkName VARCHAR(50),
		checkCode VARCHAR(10),
		checkPattern VARCHAR(200),
		PRIMARY KEY (checkID),
		KEY checkCode (checkCode)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_index[$wpdb->base_prefix."query_check"] = array(
		'checkCode' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_type (
		queryTypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		queryTypePublic ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
		queryTypeCode VARCHAR(30),
		queryTypeName VARCHAR(30) DEFAULT NULL,
		queryTypeResult ENUM('0','1') NOT NULL DEFAULT '1',
		PRIMARY KEY (queryTypeID),
		KEY queryTypeResult (queryTypeResult)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_index[$wpdb->base_prefix."query_type"] = array(
		'queryTypeResult' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_spam (
		spamID INT unsigned NOT NULL AUTO_INCREMENT,
		spamInclude VARCHAR(30) DEFAULT NULL,
		spamExclude VARCHAR(30) DEFAULT NULL,
		spamText VARCHAR(100) DEFAULT NULL,
		PRIMARY KEY (spamID)
	) DEFAULT CHARSET=".$default_charset);

	if(get_bloginfo('language') == "sv-SE")
	{
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_zipcode (
			addressZipCode INT NOT NULL DEFAULT '0',
			cityName VARCHAR(20) DEFAULT NULL,
			municipalityName VARCHAR(20) DEFAULT NULL,
			countyName VARCHAR(20) DEFAULT NULL,
			PRIMARY KEY (addressZipCode)
		) DEFAULT CHARSET=".$default_charset);
	}

	add_columns($arr_add_column);
	update_columns($arr_update_column);
	add_index($arr_add_index);

	$arr_run_query = array();

	$arr_query_types = array(
		1 => array('code' => 'checkbox',			'name' => __("Checkbox", 'lang_form'),				'result' => 1),
		2 => array('code' => 'range',				'name' => __("Range", 'lang_form'),					'result' => 1),
		3 => array('code' => 'input_field',			'name' => __("Input field", 'lang_form'),			'result' => 1),
		4 => array('code' => 'textarea',			'name' => __("Textarea", 'lang_form'),				'result' => 1),
		5 => array('code' => 'text',				'name' => __("Text", 'lang_form'),					'result' => 0),
		6 => array('code' => 'space',				'name' => __("Space", 'lang_form'),					'result' => 0),
		7 => array('code' => 'datepicker',			'name' => __("Datepicker", 'lang_form'),			'result' => 1),
		8 => array('code' => 'radio_button',		'name' => __("Radio button", 'lang_form'),			'result' => 1),
		9 => array('code' => 'referer_url',			'name' => __("Referer URL", 'lang_form'),			'result' => 1),
		10 => array('code' => 'select',				'name' => __("Dropdown", 'lang_form'),				'result' => 1),
		11 => array('code' => 'select_multiple',	'name' => __("Multiple selection", 'lang_form'),	'result' => 1),
		12 => array('code' => 'hidden_field',		'name' => __("Hidden field", 'lang_form'),			'result' => 1),
		13 => array('code' => 'custom_tag',			'name' => __("Custom tag", 'lang_form'),			'result' => 0),
		14 => array('code' => 'custom_tag_end',		'name' => __("Custom tag (end)", 'lang_form'),		'result' => 0,		'public' => 'no'),
		15 => array('code' => 'file',				'name' => __("File", 'lang_form'),					'result' => 1),
	);

	foreach($arr_query_types as $key => $value)
	{
		if(!isset($value['public'])){	$value['public'] = 'yes';}

		$arr_run_query[] = sprintf("INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '%d', queryTypeCode = '%s', queryTypeName = '%s', queryTypeResult = '%d', queryTypePublic = '%s'", $key, $value['code'], $value['name'], $value['result'], $value['public']);
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

		$arr_run_query[] = sprintf("INSERT IGNORE INTO ".$wpdb->base_prefix."query_check SET checkID = '%d', checkPublic = '%d', checkName = '%s', checkCode = '%s', checkPattern = '%s'", $key, $value['public'], $value['name'], $value['code'], $value['pattern']);
	}

	$arr_query_check = array(
		1 => array('exclude' => "select_multiple",	'text' => "contains_html"),
		2 => array('exclude' => "referer_url",		'text' => "/(http|https|ftp|ftps)\:/i"),
		3 => array(									'text' => "/([qm]){5}/"),
		4 => array(									'text' => "/(bit\.ly)/"),
		5 => array(									'text' => "/([bs][url[bs]=)/"),
	);

	foreach($arr_query_check as $key => $value)
	{
		if(!isset($value['include'])){	$value['include'] = "";}
		if(!isset($value['exclude'])){	$value['exclude'] = "";}

		$arr_run_query[] = sprintf("INSERT IGNORE INTO ".$wpdb->base_prefix."form_spam SET spamID = '%d', spamInclude = '%s', spamExclude = '%s', spamText = '".$value['text']."'", $key, $value['include'], $value['exclude']);
	}

	if(get_bloginfo('language') == "sv-SE")
	{
		require_once("include/zipcode.php");

		$count_temp = count($arr_run_query);

		$arr_exclude = array("å", "ä", "ö", "Å", "Ä", "Ö");
		$arr_include = array(__("aring", 'lang_form'), __("auml", 'lang_form'), __("ouml", 'lang_form'), __("Aring", 'lang_form'), __("Auml", 'lang_form'), __("Ouml", 'lang_form'));

		for($i = 0; $i < $count_temp; $i++)
		{
			$arr_run_query[$i] = str_replace($arr_exclude, $arr_include, $arr_run_query[$i]);
		}
	}

	run_queries($arr_run_query);

	//Check for loose ends
	#################################
	$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->base_prefix."query ON ".$wpdb->posts.".ID = ".$wpdb->base_prefix."query.postID WHERE post_type = 'mf_form' AND postID IS NULL");

	foreach($result as $r)
	{
		wp_trash_post($r->ID);
	}
	#################################

	//Convert wp_query to wp_posts
	#################################
	/*$result = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."query WHERE post_type = 'mf_form' AND queryConverted = '0'");

	foreach($result as $r)
	{
		$intFormID = $r->queryID;
		//...

		//$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryConverted = '1' WHERE queryID = '%d'", $intFormID));
	}*/
	#################################
}

function deactivate_form()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_form_permission'),
		'tables' => array('query_check', 'query_type', 'form_spam', 'query_zipcode'),
	));
}

function uninstall_form()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_form',
		'options' => array('setting_redirect_emails', 'setting_form_test_emails', 'setting_form_permission_see_all', 'setting_replacement_form', 'setting_replacement_form_text', 'setting_form_reload', 'setting_link_yes_text', 'setting_link_no_text', 'setting_link_thanks_text', 'mf_forms_viewed', 'answer_viewed'),
		'post_types' => array('mf_form'),
		'tables' => array('query', 'query2answer', 'query2type', 'query_answer', 'query_answer_email', 'query_check', 'query_type', 'form_spam', 'query_zipcode'),
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