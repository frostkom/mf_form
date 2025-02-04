<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description:
Version: 1.1.5.9
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

	add_action('init', array($obj_form, 'init'), 1);

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_form');
		register_deactivation_hook(__FILE__, 'deactivate_form');
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

		add_action('wp_trash_post', array($obj_form, 'wp_trash_post'));
		add_action('deleted_user', array($obj_form, 'deleted_user'));

		add_action('do_clone_site', array($obj_form, 'do_clone_site'));
		add_action('do_switch_sites', array($obj_form, 'do_switch_sites'));

		add_filter('filter_last_updated_post_types', array($obj_form, 'filter_last_updated_post_types'), 10, 2);

		add_filter('wp_privacy_personal_data_exporters', array($obj_form, 'wp_privacy_personal_data_exporters'), 10);
		add_filter('wp_privacy_personal_data_erasers', array($obj_form, 'wp_privacy_personal_data_erasers'), 10);

		add_filter('count_shortcode_button', array($obj_form, 'count_shortcode_button'));
		add_filter('get_shortcode_output', array($obj_form, 'get_shortcode_output'));
		add_filter('get_shortcode_list', array($obj_form, 'get_shortcode_list'));
	}

	else
	{
		add_filter('wp_sitemaps_post_types', array($obj_form, 'wp_sitemaps_post_types'));

		add_action('wp_head', array($obj_form, 'wp_head'), 0);
		add_action('login_init', array($obj_form, 'login_init'), 0);

		add_filter('the_content', array($obj_form, 'the_content'));
	}

	add_shortcode($obj_form->post_type, array($obj_form, 'shortcode_form'));

	if(wp_is_block_theme() == false)
	{
		add_action('widgets_init', array($obj_form, 'widgets_init'));
	}

	add_filter('single_template', 'custom_templates_form');

	add_action('phpmailer_init', array($obj_form, 'phpmailer_init'), 0);

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
			postID INT UNSIGNED NOT NULL DEFAULT '0',
			formAcceptDuplicates ENUM('no', 'yes') NOT NULL DEFAULT 'yes',
			formSaveIP ENUM('no', 'yes') NOT NULL DEFAULT 'no',
			formAnswerURL VARCHAR(20) DEFAULT NULL,
			formEmail VARCHAR(100) DEFAULT NULL,
			formFromName VARCHAR(100) DEFAULT NULL,
			formEmailConditions TEXT DEFAULT NULL,
			formEmailNotify ENUM('0', '1') NOT NULL DEFAULT '1',
			formEmailNotifyFrom ENUM('admin', 'visitor', 'other') NOT NULL DEFAULT 'admin',
			formEmailNotifyFromEmail VARCHAR(100) DEFAULT NULL,
			formEmailNotifyFromEmailName VARCHAR(100) DEFAULT NULL,
			formEmailNotifyPage INT UNSIGNED NOT NULL DEFAULT '0',
			formEmailName VARCHAR(100) DEFAULT NULL,
			formEmailConfirm ENUM('0', '1') NOT NULL DEFAULT '0',
			formEmailConfirmID INT UNSIGNED DEFAULT NULL,
			formEmailConfirmFromEmail VARCHAR(100) DEFAULT NULL,
			formEmailConfirmFromEmailName VARCHAR(100) DEFAULT NULL,
			formEmailConfirmPage INT UNSIGNED NOT NULL DEFAULT '0',
			formShowAnswers ENUM('no', 'yes') NOT NULL DEFAULT 'no',
			formMandatoryText VARCHAR(100) DEFAULT NULL,
			formButtonDisplay ENUM('0', '1') NOT NULL DEFAULT '1',
			formButtonText VARCHAR(100) DEFAULT NULL,
			formButtonSymbol VARCHAR(20) DEFAULT NULL,
			formPaymentProvider INT DEFAULT NULL,
			formPaymentHmac VARCHAR(200) DEFAULT NULL,
			formTermsPage INT UNSIGNED DEFAULT NULL,
			formPaymentMerchant VARCHAR(100) DEFAULT NULL,
			formPaymentPassword VARCHAR(100) DEFAULT NULL,
			formPaymentCurrency VARCHAR(3),
			formPaymentCheck INT DEFAULT NULL,
			formPaymentCost DOUBLE UNSIGNED DEFAULT NULL,
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
		) DEFAULT CHARSET=".$default_charset); //formName VARCHAR(100) DEFAULT NULL,

		$arr_add_column[$wpdb->base_prefix."form"] = array(
			'formButtonDisplay' => "ALTER TABLE [table] ADD [column] ENUM('0', '1') NOT NULL DEFAULT '1' AFTER formMandatoryText", //220927
		);

		$arr_update_column[$wpdb->base_prefix."form"] = array(
			'formShowAnswers' => "ALTER TABLE [table] CHANGE [column] [column] ENUM('no', 'yes', '1') NOT NULL DEFAULT 'no'", //221024
			'formAcceptDuplicates' => "ALTER TABLE [table] CHANGE [column] [column] ENUM('no', 'yes') NOT NULL DEFAULT 'yes'", //230202
		);

		// Delete (formName)

		$wpdb->query("UPDATE ".$wpdb->base_prefix."form SET formShowAnswers = 'yes' WHERE formShowAnswers = '1'");
		$wpdb->query("UPDATE ".$wpdb->base_prefix."form SET formShowAnswers = 'no' WHERE (formShowAnswers = '0' OR formShowAnswers = '' OR formShowAnswers IS null)");

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."form_nonce (
			nonceID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			nonceKey VARCHAR(32),
			nonceCreated DATETIME DEFAULT NULL,
			PRIMARY KEY (nonceID),
			KEY nonceKey (nonceKey)
		) DEFAULT CHARSET=".$default_charset);

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
			formTypeCode VARCHAR(30),
			formTypeName VARCHAR(40) DEFAULT NULL,
			formTypeDesc TEXT DEFAULT NULL,
			PRIMARY KEY (formTypeID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->base_prefix."form_type"] = array(
			//'' => "ALTER TABLE [table] ADD [column] VARCHAR(40) DEFAULT NULL AFTER ",
		);

		$arr_update_column[$wpdb->base_prefix."form_type"] = array(
			//'' => "ALTER TABLE [table] DROP COLUMN [column]",
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

		$arr_run_query = array();

		foreach($obj_form->get_form_types() as $key => $value)
		{
			$arr_run_query[] = $wpdb->prepare("INSERT IGNORE INTO ".$wpdb->base_prefix."form_type SET formTypeID = '%d', formTypeCode = %s, formTypeName = %s, formTypeDesc = %s", $key, $value['code'], $value['name'], $value['desc']);
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

		// Convert wp_form to wp_posts
		#################################
		$arr_fields_db = $arr_fields_meta = array();

		$arr_fields_db[] = 'formButtonDisplay';				$arr_fields_db_bool[] = true;		$arr_fields_meta[] = 'button_display';
		$arr_fields_db[] = 'formButtonSymbol';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'button_symbol';
		$arr_fields_db[] = 'formButtonText';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'button_text';
		$arr_fields_db[] = 'formAnswerURL';					$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'answer_url';
		$arr_fields_db[] = 'formMandatoryText';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'mandatory_text';
		//$arr_fields_db[] = 'formAcceptDuplicates';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'accept_duplicates';
		//$arr_fields_db[] = 'formShowAnswers';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'show_answers';
		//$arr_fields_db[] = 'formSaveIP';					$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'save_ip';
		$arr_fields_db[] = 'formEmailName';					$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_name';
		$arr_fields_db[] = 'formEmailNotify';				$arr_fields_db_bool[] = true;		$arr_fields_meta[] = 'email_notify';
		$arr_fields_db[] = 'formEmail';						$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_admin';
		$arr_fields_db[] = 'formEmailNotifyFrom';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_notify_from';
		$arr_fields_db[] = 'formEmailNotifyFromEmail';		$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_notify_from_email';
		$arr_fields_db[] = 'formEmailNotifyFromEmailName';	$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_notify_from_email_name';
		$arr_fields_db[] = 'formEmailNotifyPage';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_notify_page';
		$arr_fields_db[] = 'formEmailConfirm';				$arr_fields_db_bool[] = true;		$arr_fields_meta[] = 'email_confirm';
		$arr_fields_db[] = 'formEmailConfirmFromEmail';		$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_confirm_from_email';
		$arr_fields_db[] = 'formEmailConfirmFromEmailName';	$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_confirm_from_email_name';
		$arr_fields_db[] = 'formEmailConfirmID';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_confirm_id';
		$arr_fields_db[] = 'formEmailConfirmPage';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_confirm_page';
		$arr_fields_db[] = 'formEmailConditions';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_conditions';
		$arr_fields_db[] = 'formPaymentProvider';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_provider';
		$arr_fields_db[] = 'formPaymentMerchant';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_merchant';
		$arr_fields_db[] = 'formPaymentPassword';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_password';
		$arr_fields_db[] = 'formPaymentHmac';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_hmac';
		$arr_fields_db[] = 'formTermsPage';					$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'terms_page';
		$arr_fields_db[] = 'formPaymentCurrency';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_currency';
		$arr_fields_db[] = 'formPaymentCost';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_cost';
		$arr_fields_db[] = 'formPaymentAmount';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_amount';
		$arr_fields_db[] = 'formPaymentTax';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_tax';
		$arr_fields_db[] = 'formPaymentCallback';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'payment_callback';

		$count_temp = count($arr_fields_db);

		$result = $wpdb->get_results($wpdb->prepare("SELECT formID, postID, ".implode(", ", $arr_fields_db)." FROM ".$wpdb->base_prefix."form WHERE (blogID = '0' OR blogID = '%d') AND formDeleted = '0'", $wpdb->blogid), ARRAY_A);

		foreach($result as $r)
		{
			$intFormID = $r['formID'];
			$post_id = $r['postID'];

			for($i = 0; $i < $count_temp; $i++)
			{
				if($r[$arr_fields_db[$i]] != '')
				{
					//replace_post_meta(array('old' => $arr_fields_meta[$i], 'new' => $obj_form->meta_prefix.$arr_fields_meta[$i]));

					if(get_post_meta($post_id, $obj_form->meta_prefix.$arr_fields_meta[$i], true) == '')
					{
						$default_value = "";

						if($arr_fields_db_bool[$i] == true)
						{
							$default_value = ($r[$arr_fields_db[$i]] == 1 ? 'yes' : 'no');
						}

						update_post_meta($post_id, $obj_form->meta_prefix.$arr_fields_meta[$i], $default_value);
					}
				}

				// Correct a previous bug
				if($arr_fields_db_bool[$i] == false)
				{
					if(get_post_meta($post_id, $obj_form->meta_prefix.$arr_fields_meta[$i], true) == 'no')
					{
						update_post_meta($post_id, $obj_form->meta_prefix.$arr_fields_meta[$i], "");
					}
				}
			}

			/*$query_set = "";

			for($i = 0; $i < $count_temp; $i++)
			{
				$query_set .= ($i > 0 ? ", " : "")$arr_fields_db[$i]." = ''";
			}

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET ".$query_set." WHERE formID = '%d'", $intFormID));*/
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
							}

							$i++;
						}

						else
						{
							$success = false;

							do_log("There was no value for the option (1) (".$intFormID.", ".$intForm2TypeID.", ".$strFormTypeText." -> ".$str_option.")");
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
		include_once("include/classes.php");

		$obj_form = new mf_form();

		mf_uninstall_plugin(array(
			'uploads' => $obj_form->post_type,
			'options' => array('setting_redirect_emails', 'setting_form_test_emails', 'setting_form_permission_see_all', 'setting_form_permission_edit_all', 'setting_replacement_form', 'setting_replacement_form_text', 'setting_link_yes_text', 'setting_link_no_text', 'setting_link_thanks_text', 'option_form_list_viewed'),
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
			// Get HTML from a generic page instead

			$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
		}

		return $single_template;
	}
}