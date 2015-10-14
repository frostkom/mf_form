<?php
/*
Plugin Name: MF Form
Plugin URI: http://github.com/frostkom/mf_form
Version: 2.7.9
Author: Martin Fors
Author URI: http://frostkom.se
*/

add_action('init', 'init_form');
add_action('widgets_init', 'widgets_form');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_form');
	register_deactivation_hook(__FILE__, 'deactivate_form');

	add_action('admin_init', 'settings_form');
	add_action('admin_menu', 'menu_forms');
	add_action('admin_notices', 'message_form');
	add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'add_action_form');
	add_filter('network_admin_plugin_action_links_'.plugin_basename(__FILE__), 'add_action_form');
}

else
{
	add_shortcode('mf_form', 'shortcode_form');
	add_shortcode('form_shortcode', 'shortcode_form');
}

add_filter('single_template', 'custom_templates_form');

load_plugin_textdomain('lang_forms', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_form()
{
	global $wpdb;

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query (
		queryID INT unsigned NOT NULL AUTO_INCREMENT,
		blogID INT unsigned,
		postID INT unsigned NOT NULL DEFAULT '0',
		queryName varchar(100) DEFAULT NULL,
		queryAnswerURL VARCHAR(20) DEFAULT NULL,
		queryEmail varchar(100) DEFAULT NULL,
		queryEmailNotify ENUM('0', '1') NOT NULL DEFAULT '1',
		queryEmailName varchar(100) DEFAULT NULL,
		queryImproveUX ENUM('0', '1') NOT NULL DEFAULT '0',
		queryEmailConfirm ENUM('0', '1') NOT NULL DEFAULT '0',
		queryEmailConfirmPage VARCHAR(20) DEFAULT NULL,
		queryShowAnswers ENUM('0', '1') NOT NULL DEFAULT '0',
		queryMandatoryText varchar(100) DEFAULT NULL,
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
		queryDeletedID INT unsigned DEFAULT '0',
		userID INT unsigned DEFAULT '0',
		PRIMARY KEY (queryID)
	) DEFAULT CHARSET=".$default_charset); //queryURL VARCHAR(100),

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query2answer (
		answerID INT unsigned NOT NULL AUTO_INCREMENT,
		queryID INT unsigned NOT NULL,
		answerIP varchar(15) DEFAULT NULL,
		answerToken VARCHAR(100) DEFAULT NULL,
		userID INT unsigned DEFAULT NULL,
		answerCreated datetime DEFAULT NULL,
		PRIMARY KEY (answerID),
		KEY queryID (queryID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query2type (
		query2TypeID INT unsigned NOT NULL AUTO_INCREMENT,
		queryID INT unsigned DEFAULT '0',
		queryTypeID INT unsigned DEFAULT '0',
		queryTypeText TEXT,
		queryTypePlaceholder VARCHAR(100),
		checkID INT unsigned DEFAULT NULL,
		queryTypeClass VARCHAR(50) DEFAULT NULL,
		queryTypeRequired ENUM('0','1') NOT NULL DEFAULT '0',
		queryTypeAutofocus ENUM('0','1') NOT NULL DEFAULT '0',
		query2TypeOrder INT unsigned NOT NULL DEFAULT '0',
		query2TypeCreated DATETIME DEFAULT NULL,
		userID INT unsigned DEFAULT NULL,
		PRIMARY KEY (query2TypeID),
		KEY queryID (queryID),
		KEY queryTypeID (queryTypeID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_answer (
		answerID INT unsigned DEFAULT NULL,
		query2TypeID INT unsigned DEFAULT '0',
		answerText text,
		KEY query2TypeID (query2TypeID),
		KEY answerID (answerID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_answer_email (
		answerID INT unsigned DEFAULT NULL,
		answerEmail VARCHAR(100),
		answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
		KEY answerID (answerID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_check (
		checkID INT unsigned NOT NULL AUTO_INCREMENT,
		checkPublic enum('0','1'),
		checkName varchar(50),
		checkCode varchar(10),
		checkPattern VARCHAR(200),
		PRIMARY KEY (checkID)
	) DEFAULT CHARSET=".$default_charset);

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_type (
		queryTypeID INT unsigned NOT NULL AUTO_INCREMENT,
		queryTypeName varchar(30) DEFAULT NULL,
		queryTypeResult enum('0','1') NOT NULL DEFAULT '1',
		PRIMARY KEY (queryTypeID)
	) DEFAULT CHARSET=".$default_charset);

	if(get_bloginfo('language') == "sv-SE")
	{
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."query_zipcode (
			addressZipCode INT NOT NULL DEFAULT '0',
			cityName varchar(20) DEFAULT NULL,
			municipalityName varchar(20) DEFAULT NULL,
			countyName varchar(20) DEFAULT NULL,
			PRIMARY KEY (addressZipCode)
		) DEFAULT CHARSET=".$default_charset);
		//ALTER DATABASE databasename CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	}

	$arr_add_column = array();

	$arr_add_column[$wpdb->base_prefix."query"]['queryEmail'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryAnswer";
	$arr_add_column[$wpdb->base_prefix."query"]['queryEmailNotify'] = "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '1' AFTER queryEmail";
	$arr_add_column[$wpdb->base_prefix."query"]['queryEmailName'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryEmail";
	$arr_add_column[$wpdb->base_prefix."query"]['queryMandatoryText'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryEmailName";
	$arr_add_column[$wpdb->base_prefix."query"]['queryButtonText'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryMandatoryText";
	$arr_add_column[$wpdb->base_prefix."query"]['queryEmailConfirm'] = "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryEmailName";
	$arr_add_column[$wpdb->base_prefix."query"]['queryShowAnswers'] = "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryEmailName";
	$arr_add_column[$wpdb->base_prefix."query"]['queryAnswerURL'] = "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryAnswer";
	$arr_add_column[$wpdb->base_prefix."query"]['queryDeleted'] = "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryCreated";
	$arr_add_column[$wpdb->base_prefix."query"]['queryDeletedDate'] = "ALTER TABLE [table] ADD [column] datetime DEFAULT NULL AFTER queryDeleted";
	$arr_add_column[$wpdb->base_prefix."query"]['queryDeletedID'] = "ALTER TABLE [table] ADD [column] INT unsigned DEFAULT '0' AFTER queryDeletedDate";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentCheck'] = "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryButtonText";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentAmount'] = "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryPaymentCheck";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentHmac'] = "ALTER TABLE [table] ADD [column] VARCHAR(200) DEFAULT NULL AFTER queryButtonText";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentMerchant'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER queryPaymentHmac";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentPassword'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER queryPaymentMerchant";
	$arr_add_column[$wpdb->base_prefix."query"]['queryEmailConfirmPage'] = "ALTER TABLE [table] ADD [column] VARCHAR(20) DEFAULT NULL AFTER queryEmailConfirm";
	$arr_add_column[$wpdb->base_prefix."query"]['blogID'] = "ALTER TABLE [table] ADD [column] INT AFTER queryID";
	$arr_add_column[$wpdb->base_prefix."query"]['queryImproveUX'] = "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '0' AFTER queryEmailName";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentProvider'] = "ALTER TABLE [table] ADD [column] INT DEFAULT NULL AFTER queryButtonText";
	//$arr_add_column[$wpdb->base_prefix."query"]['queryURL'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryName";
	$arr_add_column[$wpdb->base_prefix."query"]['queryPaymentCurrency'] = "ALTER TABLE [table] ADD [column] VARCHAR(3) AFTER queryPaymentMerchant";
	$arr_add_column[$wpdb->base_prefix."query"]['queryButtonSymbol'] = "ALTER TABLE [table] ADD [column] VARCHAR(20) AFTER queryButtonText";
	$arr_add_column[$wpdb->base_prefix."query"]['postID'] = "ALTER TABLE [table] ADD [column] INT unsigned NOT NULL DEFAULT '0' AFTER blogID";

	$arr_add_column[$wpdb->base_prefix."query2answer"]['answerToken'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) DEFAULT NULL AFTER answerIP";

	$arr_add_column[$wpdb->base_prefix."query_check"]['checkPattern'] = "ALTER TABLE [table] ADD [column] VARCHAR(200) AFTER checkCode";

	$arr_add_column[$wpdb->base_prefix."query2type"]['queryTypeClass'] = "ALTER TABLE [table] ADD [column] varchar(50) AFTER checkID";
	$arr_add_column[$wpdb->base_prefix."query2type"]['queryTypeAutofocus'] = "ALTER TABLE [table] ADD [column] enum('0','1') NOT NULL DEFAULT '0' AFTER queryTypeClass";
	$arr_add_column[$wpdb->base_prefix."query2type"]['queryTypePlaceholder'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER queryTypeText";

	add_columns($arr_add_column);

	$arr_update_column = array();

	$arr_update_column[$wpdb->base_prefix."query"]['queryTypePublic'] = "ALTER TABLE [table] DROP [column]";
	$arr_update_column[$wpdb->base_prefix."query"]['queryTypeOrder'] = "ALTER TABLE [table] DROP [column]";
	$arr_update_column[$wpdb->base_prefix."query"]['queryTypeLang'] = "ALTER TABLE [table] CHANGE [column] queryTypeName VARCHAR(30) DEFAULT NULL";
	$arr_update_column[$wpdb->base_prefix."query"]['queryEmailNotify'] = "ALTER TABLE [table] CHANGE [column] [column] ENUM('0', '1') NOT NULL DEFAULT '1'";
	$arr_update_column[$wpdb->base_prefix."query"]['queryPaymentMerchant'] = "ALTER TABLE [table] CHANGE [column] [column] VARCHAR(100) DEFAULT NULL";
	$arr_update_column[$wpdb->base_prefix."query"]['queryImproveUX'] = "ALTER TABLE [table] CHANGE [column] [column] ENUM('0', '1') NOT NULL DEFAULT '0'";

	$arr_update_column[$wpdb->base_prefix."query"]['queryEncrypted'] = "ALTER TABLE [table] DROP [column]";

	$arr_update_column[$wpdb->base_prefix."query2type"]['queryTypeForced'] = "ALTER TABLE [table] CHANGE [column] queryTypeRequired ENUM('0','1') NOT NULL DEFAULT '0'";

	update_columns($arr_update_column);

	$arr_run_query = array();

	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('1','1','".__("Number", 'lang_forms')."','int','[0-9]*')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('5','1','".__("E-mail", 'lang_forms')."','email','')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('6','1','".__("Phone no", 'lang_forms')."','telno','')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('7','1','".__("Decimal number", 'lang_forms')."','float','[-+]?[0-9]*[.,]?[0-9]+')";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('8','1','".__("URL", 'lang_forms')."','url','')";

	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '1', queryTypeName = '".__("Checkbox", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '2', queryTypeName = '".__("Range", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '3', queryTypeName = '".__("Input field", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '4', queryTypeName = '".__("Textarea", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '5', queryTypeName = '".__("Text", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '6', queryTypeName = '".__("Space", 'lang_forms')."', queryTypeResult = '0'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '7', queryTypeName = '".__("Datepicker", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '8', queryTypeName = '".__("Radio button", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '9', queryTypeName = '".__("Referer URL", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '10', queryTypeName = '".__("Dropdown", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '11', queryTypeName = '".__("Multiple selection", 'lang_forms')."', queryTypeResult = '1'";
	$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_type SET queryTypeID = '12', queryTypeName = '".__("Hidden field", 'lang_forms')."', queryTypeResult = '1'";

	$arr_run_query[] = "UPDATE ".$wpdb->base_prefix."query_type SET queryTypeResult = '1' WHERE queryTypeID = '5'";

	if(get_bloginfo('language') == "sv-SE")
	{
		$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('2','1','".__("Zip code", 'lang_forms')." (Sv)','zip','[0-9]{5}')";
		$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('3','1','".__("Social security number", 'lang_forms')." (10 ".__("digits", 'lang_forms').") (Sv)','soc','[0-9]{10}')";
		$arr_run_query[] = "INSERT IGNORE INTO ".$wpdb->base_prefix."query_check VALUES('4','1','".__("Social security number", 'lang_forms')." (12 ".__("digits", 'lang_forms').") (Sv)','soc2','(?:18|19|20)[0-9]{10}')";

		require_once("include/zipcode.php");

		$count_temp = count($arr_run_query);

		$arr_exclude = array("å", "ä", "ö", "Å", "Ä", "Ö");
		$arr_include = array(__("aring", 'lang_forms'), __("auml", 'lang_forms'), __("ouml", 'lang_forms'), __("Aring", 'lang_forms'), __("Auml", 'lang_forms'), __("Ouml", 'lang_forms'));

		for($i = 0; $i < $count_temp; $i++)
		{
			$arr_run_query[$i] = str_replace($arr_exclude, $arr_include, $arr_run_query[$i]);
		}
	}

	run_queries($arr_run_query);

	//Migrate query table to posts table
	if(current_user_can("update_core"))
	{
		//Step 1: Create post in posts table
		#########################
		$result = $wpdb->get_results("SELECT queryID, postID, queryName, queryDeleted FROM ".$wpdb->base_prefix."query WHERE postID = '0'");

		foreach($result as $r)
		{
			$intQueryID = $r->queryID;
			$intPostID = $r->postID;
			$strQueryName = $r->queryName;
			$intQueryDeleted = $r->queryDeleted;

			if(!($intPostID > 0))
			{
				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strQueryName,
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
	global $wpdb;

	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->base_prefix."query_check");
	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->base_prefix."query_type");
	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->base_prefix."query_zipcode");
}

include("include/classes.php");
include("include/functions.php");

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