<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description: 
Version: 6.4.15
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_form
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_form
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'activate_form');

add_action('init', 'init_form');
add_action('widgets_init', 'widgets_form');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_form');
	register_deactivation_hook(__FILE__, 'deactivate_form');
	register_uninstall_hook(__FILE__, 'uninstall_form');

	add_action('admin_init', 'settings_form');
	add_action('admin_menu', 'menu_form');
	add_action('admin_notices', 'notices_form');
	add_action('before_delete_post', 'delete_form');
	add_action('deleted_user', 'deleted_user_form');

	add_filter('count_shortcode_button', 'count_shortcode_button_form');
	add_filter('get_shortcode_output', 'get_shortcode_output_form');
}

add_shortcode('mf_form', 'shortcode_form');
add_shortcode('form_shortcode', 'shortcode_form');

add_filter('single_template', 'custom_templates_form');

load_plugin_textdomain('lang_form', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_form()
{
	global $wpdb;

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query (
		queryID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		blogID INT UNSIGNED,
		postID INT UNSIGNED NOT NULL DEFAULT '0',
		queryName VARCHAR(100) DEFAULT NULL,
		queryAnswerURL VARCHAR(20) DEFAULT NULL,
		queryEmail VARCHAR(100) DEFAULT NULL,
		queryEmailNotify ENUM('0', '1') NOT NULL DEFAULT '1',
		queryEmailNotifyPage INT UNSIGNED NOT NULL DEFAULT '0',
		queryEmailName VARCHAR(100) DEFAULT NULL,
		queryEmailCheckConfirm ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
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
		queryCreated datetime DEFAULT NULL,
		queryDeleted ENUM('0', '1') NOT NULL DEFAULT '0',
		queryDeletedDate datetime DEFAULT NULL,
		queryDeletedID INT UNSIGNED DEFAULT '0',
		userID INT UNSIGNED DEFAULT '0',
		PRIMARY KEY (queryID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query2answer (
		answerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		queryID INT UNSIGNED NOT NULL,
		answerIP VARCHAR(15) DEFAULT NULL,
		answerToken VARCHAR(100) DEFAULT NULL,
		answerCreated datetime DEFAULT NULL,
		PRIMARY KEY (answerID),
		KEY queryID (queryID)
	) DEFAULT CHARSET=".$default_charset);

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

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_answer (
		answerID INT UNSIGNED DEFAULT NULL,
		query2TypeID INT UNSIGNED DEFAULT '0',
		answerText text,
		KEY query2TypeID (query2TypeID),
		KEY answerID (answerID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_answer_email (
		answerID INT UNSIGNED DEFAULT NULL,
		answerEmail VARCHAR(100),
		answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
		KEY answerID (answerID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_check (
		checkID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		checkPublic enum('0','1'),
		checkName VARCHAR(50),
		checkCode VARCHAR(10),
		checkPattern VARCHAR(200),
		PRIMARY KEY (checkID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_type (
		queryTypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		queryTypePublic ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
		queryTypeCode VARCHAR(30),
		queryTypeName VARCHAR(30) DEFAULT NULL,
		queryTypeResult enum('0','1') NOT NULL DEFAULT '1',
		PRIMARY KEY (queryTypeID)
	) DEFAULT CHARSET=".$default_charset); //queryTypeShowInForm ENUM('no', 'yes') NOT NULL DEFAULT 'yes',

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

	$arr_add_column = array();

	$arr_add_column[$wpdb->base_prefix."query"] = array(
		'queryEmail' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryAnswer",
		'queryEmailNotify' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '1' AFTER queryEmail",
		'queryEmailName' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryEmail",
		'queryMandatoryText' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryEmailName",
		'queryButtonText' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryMandatoryText",
		'queryEmailConfirm' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryEmailName",
		'queryShowAnswers' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryEmailName",
		'queryAnswerURL' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryAnswer",
		'queryDeleted' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryCreated",
		'queryDeletedDate' => "ALTER TABLE [table] ADD [column] datetime DEFAULT NULL AFTER queryDeleted",
		'queryDeletedID' => "ALTER TABLE [table] ADD [column] INT UNSIGNED DEFAULT '0' AFTER queryDeletedDate",
		'queryPaymentCheck' => "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryButtonText",
		'queryPaymentAmount' => "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryPaymentCheck",
		'queryPaymentHmac' => "ALTER TABLE [table] ADD [column] VARCHAR(200) DEFAULT NULL AFTER queryButtonText",
		'queryPaymentMerchant' => "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER queryPaymentHmac",
		'queryPaymentPassword' => "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER queryPaymentMerchant",
		'queryEmailConfirmPage' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryEmailConfirm",
		'blogID' => "ALTER TABLE [table] ADD [column] INT AFTER queryID",
		//'queryImproveUX' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryEmailName",
		'queryPaymentProvider' => "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryButtonText",
		//'queryURL' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryName",
		'queryPaymentCurrency' => "ALTER TABLE [table] ADD [column] VARCHAR(3) AFTER queryPaymentMerchant",
		'queryButtonSymbol' => "ALTER TABLE [table] ADD [column] VARCHAR(20) AFTER queryButtonText",
		'postID' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER blogID",
		'queryEmailCheckConfirm' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'yes' AFTER queryEmailName",
		'queryEmailNotifyPage' => "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryEmailNotify",
	);

	$arr_add_column[$wpdb->base_prefix."query2answer"] = array(
		'answerToken' => "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER answerIP",
	);

	$arr_add_column[$wpdb->base_prefix."query_check"] = array(
		'checkPattern' => "ALTER TABLE [table] ADD [column] VARCHAR(200) AFTER checkCode",
		'checkName' => "ALTER TABLE [table] ADD [column] VARCHAR(50) AFTER checkPublic",
	);

	$arr_add_column[$wpdb->base_prefix."query2type"] = array(
		'queryTypeClass' => "ALTER TABLE [table] ADD [column] VARCHAR(50) AFTER checkID",
		'queryTypeAutofocus' => "ALTER TABLE [table] ADD [column] enum('0','1') NOT NULL DEFAULT '0' AFTER queryTypeClass",
		'queryTypePlaceholder' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryTypeText",
		'queryTypeTag' => "ALTER TABLE [table] ADD [column] VARCHAR(20) AFTER checkID",
		'query2TypeID2' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER query2TypeID",
		'queryTypeFetchFrom' => "ALTER TABLE [table] ADD [column] VARCHAR(50) AFTER queryTypeClass",
		'queryTypeActionEquals' => "ALTER TABLE [table] ADD [column] VARCHAR(10) AFTER queryTypeFetchFrom",
		'queryTypeActionShow' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER queryTypeActionEquals",
		'queryTypeRemember' => "ALTER TABLE [table] ADD [column] ENUM('0','1') NOT NULL DEFAULT '0' AFTER queryTypeAutofocus",
	);

	$arr_add_column[$wpdb->base_prefix."query_type"] = array(
		'queryTypePublic' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'yes' AFTER queryTypeID",
		'queryTypeCode' => "ALTER TABLE [table] ADD [column] VARCHAR(30) AFTER queryTypePublic",
		//'queryTypeShowInForm' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'yes' AFTER queryTypeResult",
	);

	add_columns($arr_add_column);

	//Convert queryAnswerURL and queryEmailConfirmPage to INT
	#################################
	$result = $wpdb->get_results("SELECT queryID, queryAnswerURL, queryEmailConfirmPage FROM ".$wpdb->base_prefix."query WHERE queryAnswerURL LIKE '%_%' OR queryEmailConfirmPage LIKE '%_%'");

	foreach($result as $r)
	{
		$intQueryID = $r->queryID;
		$strQueryAnswerURL = $r->queryAnswerURL;
		$strQueryEmailConfirmPage = $r->queryEmailConfirmPage;

		if(strpos($strQueryAnswerURL, "_"))
		{
			list($rest, $strQueryAnswerURL) = explode("_", $strQueryAnswerURL);

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryAnswerURL = %s WHERE queryID = '%d'", $strQueryAnswerURL, $intQueryID));
		}

		if(strpos($strQueryEmailConfirmPage, "_"))
		{
			list($rest, $intQueryEmailConfirmPage) = explode("_", $strQueryEmailConfirmPage);

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryEmailConfirmPage = %s WHERE queryID = '%d'", $intQueryEmailConfirmPage, $intQueryID));
		}
	}
	#################################

	$arr_update_column = array();

	$arr_update_column[$wpdb->base_prefix."query"] = array(
		'queryEmailNotify' => "ALTER TABLE [table] CHANGE [column] [column] ENUM('0', '1') NOT NULL DEFAULT '1'",
		'queryPaymentMerchant' => "ALTER TABLE [table] CHANGE [column] [column] VARCHAR(100) DEFAULT NULL",
		'queryImproveUX' => "ALTER TABLE [table] DROP [column]",
		'queryURL' => "ALTER TABLE [table] DROP [column]",
		'queryAnswerURL' => "ALTER TABLE [table] CHANGE [column] [column] INT UNSIGNED NOT NULL DEFAULT '0'",
		'queryEmailConfirmPage' => "ALTER TABLE [table] CHANGE [column] [column] INT UNSIGNED NOT NULL DEFAULT '0'",
	);

	$arr_update_column[$wpdb->base_prefix."query"] = array(
		'queryEncrypted' => "ALTER TABLE [table] DROP [column]",
	);

	$arr_update_column[$wpdb->base_prefix."query2answer"] = array(
		'userID' => "ALTER TABLE [table] DROP [column]",
	);

	$arr_update_column[$wpdb->base_prefix."query2type"] = array(
		'queryTypeForced' => "ALTER TABLE [table] CHANGE [column] queryTypeRequired ENUM('0','1') NOT NULL DEFAULT '0'",
	);

	$arr_update_column[$wpdb->base_prefix."query_type"] = array(
		'queryTypeLang' => "ALTER TABLE [table] CHANGE [column] queryTypeName VARCHAR(30) DEFAULT NULL",
		'queryTypeShowInForm' => "ALTER TABLE [table] DROP [column]",
	);

	update_columns($arr_update_column);

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
		//16 => array('code' => 'email_text',			'name' => __("Email text", 'lang_form'),			'result' => 0,		'show_in_form' => 'no'),
	);

	foreach($arr_query_types as $key => $value)
	{
		if(!isset($value['public'])){	$value['public'] = 'yes';}
		//if(!isset($value['show_in_form'])){	$value['show_in_form'] = 'yes';}

		$arr_run_query[] = sprintf("INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '%d', queryTypeCode = '%s', queryTypeName = '%s', queryTypeResult = '%d', queryTypePublic = '%s'", $key, $value['code'], $value['name'], $value['result'], $value['public']); //, queryTypeShowInForm = '%s', $value['show_in_form']
	}

	$query_temp = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES";

	$arr_run_query[] = $query_temp."('1','1','".__("Number", 'lang_form')."','int','[0-9]*')";
	$arr_run_query[] = $query_temp."('5','1','".__("E-mail", 'lang_form')."','email','')";
	$arr_run_query[] = $query_temp."('6','1','".__("Phone no", 'lang_form')."','telno','')";
	$arr_run_query[] = $query_temp."('7','1','".__("Decimal number", 'lang_form')."','float','[-+]?[0-9]*[.,]?[0-9]+')";
	$arr_run_query[] = $query_temp."('8','1','".__("URL", 'lang_form')."','url','')";

	if(get_bloginfo('language') == "sv-SE")
	{
		$arr_run_query[] = $query_temp."('2','1','".__("Zip code", 'lang_form')." (Sv)','zip','[0-9]{5}')";
		$arr_run_query[] = $query_temp."('3','1','".__("Social security number", 'lang_form')." (10 ".__("digits", 'lang_form').") (Sv)','soc','[0-9]{10}')";
		$arr_run_query[] = $query_temp."('4','1','".__("Social security number", 'lang_form')." (12 ".__("digits", 'lang_form').") (Sv)','soc2','(?:18|19|20)[0-9]{10}')";

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

	//Migrate query table to posts table
	if(IS_ADMIN)
	{
		//Step 1: Create post in posts table
		#########################
		$result = $wpdb->get_results("SELECT queryID, blogID, postID, queryName, queryDeleted FROM ".$wpdb->base_prefix."query WHERE postID = '0'");

		foreach($result as $r)
		{
			$intQueryID = $r->queryID;
			$intBlogID = $r->blogID;
			$intPostID = $r->postID;
			$strFormName = $r->queryName;
			$intQueryDeleted = $r->queryDeleted;

			//if($intBlogID > 0 && $intBlogID != $wpdb->blog_id){}
			if(!($intPostID > 0))
			{
				//Switch to temp site
				####################
				$wpdbobj = clone $wpdb;
				$wpdb->blogid = $intBlogID;
				$wpdb->set_prefix($wpdb->base_prefix);
				####################

				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strFormName,
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET postID = '%d' WHERE queryID = '%d'", $intPostID, $intQueryID));

				if(!($intBlogID > 0))
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET blogID = '%d' WHERE queryID = '%d'", $wpdb->blog_id, $intQueryID));
				}

				if($intQueryDeleted == 1)
				{
					wp_trash_post($intPostID);
				}

				//Switch back to orig site
				###################
				$wpdb = clone $wpdbobj;
				###################
			}
		}
		#########################
	}
}

function deactivate_form()
{
	mf_uninstall_plugin(array(
		'tables' => array('query_check', 'query_type', 'query_zipcode'),
	));
}

function uninstall_form()
{
	mf_uninstall_plugin(array(
		'uploads' => "mf_form",
		'options' => array('setting_redirect_emails', 'setting_form_test_emails', 'setting_form_permission', 'setting_form_permission_see_all', 'mf_form_setting_replacement_form', 'mf_forms_viewed', 'answer_viewed'),
		'tables' => array('query', 'query2answer', 'query2type', 'query_answer', 'query_answer_email', 'query_check', 'query_type', 'query_zipcode'),
	));
}

function shortcode_form($atts)
{
	extract(shortcode_atts(array(
		'id' => ''
	), $atts));

	return show_query_form(array('query_id' => $id));
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