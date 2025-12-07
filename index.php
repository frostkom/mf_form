<?php
/*
Plugin Name: MF Form
Plugin URI: https://github.com/frostkom/mf_form
Description:
Version: 1.2.2.10
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_form
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_form = new mf_form();

	add_action('cron_base', 'activate_form', 1);
	add_action('cron_base', array($obj_form, 'cron_base'), mt_rand(2, 10));

	add_action('enqueue_block_editor_assets', array($obj_form, 'enqueue_block_editor_assets'));
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

		add_filter('post_row_actions', array($obj_form, 'post_row_actions'), 10, 2);

		add_action('rwmb_meta_boxes', array($obj_form, 'rwmb_meta_boxes'));

		add_action('wp_delete_post', array($obj_form, 'wp_delete_post'));
		add_action('deleted_user', array($obj_form, 'deleted_user'));

		add_filter('filter_last_updated_post_types', array($obj_form, 'filter_last_updated_post_types'), 10, 2);

		add_filter('wp_privacy_personal_data_exporters', array($obj_form, 'wp_privacy_personal_data_exporters'), 10);
		add_filter('wp_privacy_personal_data_erasers', array($obj_form, 'wp_privacy_personal_data_erasers'), 10);
	}

	else
	{
		add_filter('wp_sitemaps_post_types', array($obj_form, 'wp_sitemaps_post_types'));

		add_filter('the_content', array($obj_form, 'the_content'));
	}

	add_filter('single_template', 'custom_templates_form');

	add_action('phpmailer_init', array($obj_form, 'phpmailer_init'), 0);

	add_action('wp_ajax_api_form_fetch_info', array($obj_form, 'api_form_fetch_info'));
	add_action('wp_ajax_nopriv_api_form_fetch_info', array($obj_form, 'api_form_fetch_info'));

	add_action('wp_ajax_api_form_nonce', array($obj_form, 'api_form_nonce'));
	add_action('wp_ajax_nopriv_api_form_nonce', array($obj_form, 'api_form_nonce'));

	function activate_form()
	{
		global $wpdb, $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

		$arr_add_column = $arr_update_column = $arr_add_index = [];

		// Old
		############################
		$arr_update_column[$wpdb->base_prefix."form"] = array(
			'formDeletedID' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formCreated' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formDeletedDate' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formDeleted' => "ALTER TABLE [table] DROP COLUMN [column]", //250424
			'formPaymentFile' => "ALTER TABLE [table] DROP COLUMN [column]", //250426
			'formPaymentCheck' => "ALTER TABLE [table] DROP COLUMN [column]", //250426
			'userID' => "ALTER TABLE [table] DROP COLUMN [column]", //250428
		);

		$arr_add_column[$wpdb->base_prefix."form2type"] = array(
			'formTypeLength' => "ALTER TABLE [table] ADD [column] SMALLINT DEFAULT NULL AFTER formTypeClass", //240813
			'postID' => "ALTER TABLE [table] ADD [column] INT UNSIGNED DEFAULT '0' AFTER formID", //250501
		);

		$arr_update_column[$wpdb->base_prefix."form2type"] = array(
			'form2TypeCreated' => "ALTER TABLE [table] DROP COLUMN [column]", //250428
			'userID' => "ALTER TABLE [table] DROP COLUMN [column]", //250428
		);

		$arr_add_column[$wpdb->base_prefix."form2answer"] = array(
			'postID' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL AFTER formID", //250501
		);

		$arr_update_column[$wpdb->base_prefix."form2answer"] = array(
			'answerToken' => "ALTER TABLE [table] DROP COLUMN [column]", //250429
		);
		############################

		// New
		############################
		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."form (
			formID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			postID INT UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (formID),
			KEY postID (postID)
		) DEFAULT CHARSET=".$default_charset);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."form2type (
			form2TypeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			form2TypeID2 INT UNSIGNED NOT NULL DEFAULT '0',
			formID INT UNSIGNED DEFAULT '0',
			postID INT UNSIGNED DEFAULT '0',
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
			formTypeDisplay ENUM('0', '1') NOT NULL DEFAULT '1',
			formTypeRequired ENUM('0', '1') NOT NULL DEFAULT '0',
			formTypeAutofocus ENUM('0', '1') NOT NULL DEFAULT '0',
			formTypeEncrypt ENUM('no', 'yes') NOT NULL DEFAULT 'no',
			formTypeRemember ENUM('0', '1') NOT NULL DEFAULT '0',
			form2TypeOrder INT UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (form2TypeID),
			KEY formID (formID),
			KEY postID (postID),
			KEY formTypeID (formTypeID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."form2type"] = array(
			'formTypeEncrypt' => "ALTER TABLE [table] ADD [column] ENUM('no', 'yes') NOT NULL DEFAULT 'no' AFTER formTypeAutofocus",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."form_option (
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

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."form2answer (
			answerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			formID INT UNSIGNED NOT NULL,
			postID INT UNSIGNED NOT NULL,
			answerIP VARCHAR(32) DEFAULT NULL,
			answerFingerprint VARCHAR(129) DEFAULT NULL,
			answerStatus VARCHAR(32) DEFAULT NULL,
			answerSpam ENUM('0', '1') NOT NULL DEFAULT '0',
			spamID SMALLINT NOT NULL DEFAULT '0',
			answerCreated DATETIME DEFAULT NULL,
			PRIMARY KEY (answerID),
			KEY formID (formID),
			KEY postID (postID),
			KEY answerCreated (answerCreated)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."form2answer"] = array(
			'answerFingerprint' => "ALTER TABLE [table] ADD [column] VARCHAR(129) DEFAULT NULL AFTER answerIP", // 251121
			'answerStatus' => "ALTER TABLE [table] ADD [column] VARCHAR(32) DEFAULT NULL AFTER answerFingerprint", // 251207
		);

		$arr_update_column[$wpdb->prefix."form2answer"] = array(
			'answerFingerprint' => "ALTER TABLE [table] CHANGE [column] [column] VARCHAR(129) DEFAULT NULL", // 251122
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."form_answer (
			answerID INT UNSIGNED DEFAULT NULL,
			form2TypeID INT UNSIGNED DEFAULT '0',
			answerText TEXT,
			answerUpdated DATETIME DEFAULT NULL,
			KEY form2TypeID (form2TypeID),
			KEY answerID (answerID)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."form_answer"] = array(
			'answerUpdated' => "ALTER TABLE [table] ADD [column] DATETIME DEFAULT NULL AFTER answerText",
		);

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."form_answer_email (
			answerID INT UNSIGNED DEFAULT NULL,
			answerEmailFrom VARCHAR(100),
			answerEmail VARCHAR(100),
			answerType VARCHAR(20) DEFAULT NULL,
			answerSent ENUM('0', '1') NOT NULL DEFAULT '0',
			KEY answerID (answerID),
			KEY answerEmail (answerEmail)
		) DEFAULT CHARSET=".$default_charset);
		############################

		// Convert from old to new
		############################
		if($wpdb->base_prefix == $wpdb->prefix)
		{
			if(does_column_exist($wpdb->prefix."form", "blogID"))
			{
				$wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->prefix."form WHERE (blogID != 'null' AND blogID != '%d')", $wpdb->blogid));

				if($wpdb->num_rows > 0)
				{
					do_log(__FUNCTION__.": Form from other blogs still exist and should be deleted (".$wpdb->last_query.")");
				}

				else
				{
					$arr_update_column[$wpdb->base_prefix."form"] = array(
						'blogID' => "ALTER TABLE [table] DROP COLUMN [column]", //250516
					);
				}
			}

			if(does_column_exist($wpdb->prefix."form2type", "postID"))
			{
				if(does_column_exist($wpdb->prefix."form", "blogID"))
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT formID, postID FROM ".$wpdb->prefix."form WHERE blogID = '%d' OR blogID IS null", $wpdb->blogid));
				}

				else
				{
					$result = $wpdb->get_results("SELECT formID, postID FROM ".$wpdb->prefix."form");
				}

				foreach($result as $r)
				{
					$intFormID = $r->formID;
					$intPostID = $r->postID;

					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET postID = '%d' WHERE formID = '%d' AND (postID IS null OR postID = '0')", $intPostID, $intFormID));
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET postID = '%d' WHERE formID = '%d' AND (postID IS null OR postID = '0')", $intPostID, $intFormID));
				}
			}
		}

		else
		{
			if(does_column_exist($wpdb->base_prefix."form", "blogID"))
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT formID, postID FROM ".$wpdb->base_prefix."form WHERE blogID = '%d'", $wpdb->blogid));

				foreach($result as $r)
				{
					$intFormID = $r->formID;
					$intPostID = $r->postID;

					// form
					#############
					$wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->prefix."form WHERE formID = '%d' AND postID = '%d'", $intFormID, $intPostID));

					if($wpdb->num_rows > 0)
					{
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND postID = '%d'", $intFormID, $intPostID));
					}

					else
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form SET formID = '%d', postID = '%d'", $intFormID, $intPostID));
					}
					#############

					// form2type
					#############
					$result_form2type = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d'", $intFormID));

					foreach($result_form2type as $r)
					{
						$intForm2TypeID = $r->form2TypeID;

						$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $intForm2TypeID));

						if($wpdb->num_rows > 0)
						{
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $intForm2TypeID));
						}

						else
						{
							$copy_fields = "form2TypeID, form2TypeID2, formID, formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeLength, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeEncrypt, formTypeRemember, form2TypeOrder";

							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2type (postID, ".$copy_fields.") (SELECT '%d', ".$copy_fields." FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d')", $intPostID, $intForm2TypeID));
						}

						// form_option
						#############
						$result_form_option = $wpdb->get_results($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d'", $intForm2TypeID));

						foreach($result_form_option as $r)
						{
							$intFormOptionID = $r->formOptionID;

							$wpdb->get_results($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->prefix."form_option WHERE formOptionID = '%d'", $intFormOptionID));

							if($wpdb->num_rows > 0)
							{
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_option WHERE formOptionID = '%d'", $intFormOptionID));
							}

							else
							{
								$copy_fields = "formOptionID, form2TypeID, formOptionKey, formOptionValue, formOptionLimit, formOptionAction, formOptionOrder";

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_option (".$copy_fields.") (SELECT ".$copy_fields." FROM ".$wpdb->base_prefix."form_option WHERE formOptionID = '%d')", $intFormOptionID));
							}
						}
						#############
					}
					#############

					// form2answer
					#############
					$result_form2answer = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d'", $intFormID));

					foreach($result_form2answer as $r)
					{
						$intAnswerID = $r->answerID;

						$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d'", $intAnswerID));

						if($wpdb->num_rows > 0)
						{
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE answerID = '%d'", $intAnswerID));
						}

						else
						{
							$copy_fields = "answerID, formID, answerIP, answerSpam, spamID, answerCreated";

							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2answer (postID, ".$copy_fields.") (SELECT '%d', ".$copy_fields." FROM ".$wpdb->base_prefix."form2answer WHERE answerID = '%d')", $intPostID, $intAnswerID));
						}

						// form_answer
						#############
						$result_form_answer = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d'", $intAnswerID));

						foreach($result_form_answer as $r)
						{
							$intForm2TypeID = $r->form2TypeID;

							$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $intAnswerID, $intForm2TypeID));

							if($wpdb->num_rows > 0)
							{
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $intAnswerID, $intForm2TypeID));
							}

							else
							{
								$copy_fields = "answerID, form2TypeID, answerText, answerUpdated";

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer (".$copy_fields.") (SELECT ".$copy_fields." FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d')", $intAnswerID, $intForm2TypeID));
							}
						}
						#############

						// form_answer_email
						#############
						$result_form_answer_email = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d'", $intAnswerID));

						foreach($result_form_answer_email as $r)
						{
							$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d'", $intAnswerID));

							if($wpdb->num_rows > 0)
							{
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d'", $intAnswerID));
							}

							else
							{
								$copy_fields = "answerID, answerEmailFrom, answerEmail, answerType, answerSent";

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer_email (".$copy_fields.") (SELECT ".$copy_fields." FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d')", $intAnswerID));
							}
						}
						#############
					}
					#############
				}
			}
		}
		############################

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
			'options' => array('setting_form_redirect_emails', 'setting_form_test_emails', 'option_form_list_viewed'),
			'user_meta' => array('meta_forms_viewed'),
			'post_types' => array($obj_form->post_type),
			'tables' => array('form', 'form_check', 'form_type', 'form_option', 'form2answer', 'form2type', 'form_answer', 'form_answer_email', 'form_nonce'),
		));
	}

	function custom_templates_form($single_template)
	{
		global $post, $obj_form;

		if($post->post_type == $obj_form->post_type && !wp_is_block_theme())
		{
			// Get HTML from a generic page instead

			$single_template = plugin_dir_path(__FILE__)."templates/single-".$post->post_type.".php";
		}

		return $single_template;
	}
}