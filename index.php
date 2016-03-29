<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description: 
Version: 4.2.5
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
		queryEmailName VARCHAR(100) DEFAULT NULL,
		queryEmailCheckConfirm ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
		queryEmailConfirm ENUM('0', '1') NOT NULL DEFAULT '0',
		queryEmailConfirmPage VARCHAR(20) DEFAULT NULL,
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
	) DEFAULT CHARSET=".$default_charset); //queryImproveUX ENUM('0', '1') NOT NULL DEFAULT '0',

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
		queryTypeRequired ENUM('0','1') NOT NULL DEFAULT '0',
		queryTypeAutofocus ENUM('0','1') NOT NULL DEFAULT '0',
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
		queryTypeName VARCHAR(30) DEFAULT NULL,
		queryTypeResult enum('0','1') NOT NULL DEFAULT '1',
		PRIMARY KEY (queryTypeID)
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
	);

	$arr_add_column[$wpdb->base_prefix."query_type"] = array(
		'queryTypePublic' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'yes' AFTER queryTypeID",
	);

	add_columns($arr_add_column);

	$arr_update_column = array();

	$arr_update_column[$wpdb->base_prefix."query"] = array(
		'queryTypePublic' => "ALTER TABLE [table] DROP [column]",
		'queryTypeOrder' => "ALTER TABLE [table] DROP [column]",
		'queryEmailNotify' => "ALTER TABLE [table] CHANGE [column] [column] ENUM('0', '1') NOT NULL DEFAULT '1'",
		'queryPaymentMerchant' => "ALTER TABLE [table] CHANGE [column] [column] VARCHAR(100) DEFAULT NULL",
		//'queryImproveUX' => "ALTER TABLE [table] CHANGE [column] [column] ENUM('0', '1') NOT NULL DEFAULT '0'",
		'queryImproveUX' => "ALTER TABLE [table] DROP [column]",
		'queryURL' => "ALTER TABLE [table] DROP [column]",
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
	);

	update_columns($arr_update_column);

	$arr_run_query = array();

	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('1','1','".__("Number", 'lang_form')."','int','[0-9]*')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('5','1','".__("E-mail", 'lang_form')."','email','')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('6','1','".__("Phone no", 'lang_form')."','telno','')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('7','1','".__("Decimal number", 'lang_form')."','float','[-+]?[0-9]*[.,]?[0-9]+')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('8','1','".__("URL", 'lang_form')."','url','')";

	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '1', queryTypeName = '".__("Checkbox", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '2', queryTypeName = '".__("Range", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '3', queryTypeName = '".__("Input field", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '4', queryTypeName = '".__("Textarea", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '5', queryTypeName = '".__("Text", 'lang_form')."', queryTypeResult = '0'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '6', queryTypeName = '".__("Space", 'lang_form')."', queryTypeResult = '0'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '7', queryTypeName = '".__("Datepicker", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '8', queryTypeName = '".__("Radio button", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '9', queryTypeName = '".__("Referer URL", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '10', queryTypeName = '".__("Dropdown", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '11', queryTypeName = '".__("Multiple selection", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '12', queryTypeName = '".__("Hidden field", 'lang_form')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '13', queryTypeName = '".__("Custom tag", 'lang_form')."', queryTypeResult = '0'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '14', queryTypePublic = 'no', queryTypeName = '".__("Custom tag (end)", 'lang_form')."', queryTypeResult = '0'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '15', queryTypeName = '".__("File", 'lang_form')."', queryTypeResult = '1'";

	if(get_bloginfo('language') == "sv-SE")
	{
		$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('2','1','".__("Zip code", 'lang_form')." (Sv)','zip','[0-9]{5}')";
		$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('3','1','".__("Social security number", 'lang_form')." (10 ".__("digits", 'lang_form').") (Sv)','soc','[0-9]{10}')";
		$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('4','1','".__("Social security number", 'lang_form')." (12 ".__("digits", 'lang_form').") (Sv)','soc2','(?:18|19|20)[0-9]{10}')";

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
		$result = $wpdb->get_results("SELECT queryID, postID, queryName, queryDeleted FROM ".$wpdb->base_prefix."query WHERE postID = '0'");

		foreach($result as $r)
		{
			$intQueryID = $r->queryID;
			$intPostID = $r->postID;
			$strFormName = $r->queryName;
			$intQueryDeleted = $r->queryDeleted;

			if(!($intPostID > 0))
			{
				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strFormName,
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET postID = '%d' WHERE queryID = '%d'", $intPostID, $intQueryID));

				if($intQueryDeleted == 1)
				{
					wp_trash_post($intPostID);
				}
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
		'options' => array('setting_redirect_emails', 'setting_form_test_emails', 'setting_form_permission', 'setting_form_permission_see_all', 'mf_form_setting_replacement_form'),
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