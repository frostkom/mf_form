<?php

class mf_form
{
	function __construct($data = array())
	{
		global $wpdb;

		$this->id = isset($data['id']) && $data['id'] > 0 ? $data['id'] : check_var('intFormID');

		$this->post_status = "";
		$this->form2type_id = $this->post_id = 0;

		$this->post_type = 'mf_form';
		$this->meta_prefix = $this->post_type.'_';

		$this->edit_mode = $this->is_spam = $this->is_spam_id = $this->is_sent = false;

		if($this->id > 0)
		{
			$this->get_post_id();
		}

		$this->form_option_exists = does_table_exist($wpdb->base_prefix."form_option");

		$this->type = (isset($data['type']) ? $data['type'] : '');
	}

	function check_allow_edit()
	{
		global $wpdb;

		$out = false;

		$setting_form_permission_edit_all = get_option('setting_form_permission_edit_all');

		if($setting_form_permission_edit_all != '')
		{
			$out = current_user_can($setting_form_permission_edit_all);

			if($out == false && $this->id > 0)
			{
				$intUserID = $wpdb->get_var($wpdb->prepare("SELECT userID FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $this->id));

				if($intUserID == get_current_user_id())
				{
					$out = true;
				}
			}
		}

		else
		{
			$out = IS_ADMIN;
		}

		return $out;
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			// Delete old nonces
			if(does_table_exist($wpdb->base_prefix."form_nonce"))
			{
				$wpdb->query("DELETE FROM ".$wpdb->base_prefix."form_nonce WHERE nonceCreated < DATE_SUB(NOW(), INTERVAL 10 HOUR)");
			}

			// Delete old spam answers
			##############################
			$setting_form_clear_spam = get_option_or_default('setting_form_clear_spam', 6);

			$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE answerSpam = '1' AND answerCreated < DATE_SUB(NOW(), INTERVAL %d MONTH)", $setting_form_clear_spam));

			if($wpdb->num_rows > 0)
			{
				foreach($result as $r)
				{
					$intAnswerID = $r->answerID;

					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d'", $intAnswerID));
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE answerID = '%d'", $intAnswerID));
				}
			}
			##############################
		}

		$obj_cron->end();
	}

	function init()
	{
		$labels = array(
			'name' => _x(__("Forms", 'lang_form'), 'post type general name'),
			'singular_name' => _x(__("Form", 'lang_form'), 'post type singular name'),
			'menu_name' => __("Forms", 'lang_form'),
		);

		$args = array(
			'labels' => $labels,
			'public' => is_user_logged_in(), // Previously true but changed to hide publicly in sitemap.xml
			'show_ui' => true,
			'show_in_menu' => false,
			'exclude_from_search' => true,
			'rewrite' => array(
				'slug' => "form",
			),
		);

		register_post_type($this->post_type, $args);
	}

	function settings_form()
	{
		global $wpdb;

		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_redirect_emails' => __("Redirect all e-mails", 'lang_form'),
			'setting_form_test_emails' => __("Redirect test e-mails", 'lang_form'),
			'setting_form_permission_see_all' => __("View All", 'lang_form'),
			'setting_form_permission_edit_all' => __("Edit All", 'lang_form'),
			'setting_form_spam' => __("Spam Filter", 'lang_form'),
		);

		$wpdb->get_results("SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE answerSpam = '1' LIMIT 0, 1");

		if($wpdb->num_rows > 0)
		{
			$arr_settings['setting_form_clear_spam'] = __("Clear Spam", 'lang_form');
		}

		$arr_settings['setting_replacement_form'] = __("Form to replace all e-mail links", 'lang_form');

		if(get_option('setting_replacement_form') > 0)
		{
			$arr_settings['setting_replacement_form_text'] = __("Text to replace all e-mail links", 'lang_form');
		}

		if($this->has_confirm_template() && is_plugin_active("mf_webshop/index.php"))
		{
			$arr_settings['setting_link_yes_text'] = __("Text to send as positive response", 'lang_form');

			if(get_option('setting_link_yes_text') != '')
			{
				$arr_settings['setting_link_no_text'] = __("Text to send as negative response", 'lang_form');
				$arr_settings['setting_link_thanks_text'] = __("Thank you message after sending response", 'lang_form');
			}
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_form_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Forms", 'lang_form'));
	}

	function setting_redirect_emails_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		$description = setting_time_limit(array('key' => $setting_key, 'value' => $option));

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("When a visitor sends an e-mail through the site it is redirected to the admins address", 'lang_form'), 'description' => $description));
	}

	function setting_form_test_emails_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		$description = setting_time_limit(array('key' => $setting_key, 'value' => $option));

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("When an admin is logged in and testing to send e-mails all outgoing e-mails are redirected to the admins address", 'lang_form'), 'description' => $description));
	}

	function setting_form_permission_see_all_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_select(array('data' => get_roles_for_select(array('add_choose_here' => true)), 'name' => $setting_key, 'value' => $option));
	}

	function setting_form_permission_edit_all_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_select(array('data' => get_roles_for_select(array('add_choose_here' => true)), 'name' => $setting_key, 'value' => $option));
	}

	function get_spam_types_for_select()
	{
		$arr_data = array(
			'honeypot' => __("Honeypot", 'lang_form'),
			'email' => __("Recurring E-mail", 'lang_form'),
			'filter' => sprintf(__("%s and Links", 'lang_form'), "HTML"),
			'contains_urls' => __("Contains URLs", 'lang_form'),
			'contains_emails' => __("Contains E-mails", 'lang_form'),
			'contains_phone_numbers' => __("Contains Phone Numbers", 'lang_form'),
			'contains_page_title' => __("Contains Page Title", 'lang_form'),
		);

		return $arr_data;
	}

	function setting_form_spam_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, array('honeypot', 'email', 'filter'));

		echo show_select(array('data' => $this->get_spam_types_for_select(), 'name' => $setting_key."[]", 'value' => $option));
	}

	function get_option_form_suffix($data)
	{
		if($data['value'] > 0)
		{
			$out = "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$data['value'])."'><i class='fa fa-wrench fa-lg'></i></a>";
		}

		else
		{
			$out = "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-plus-circle fa-lg'></i></a>";
		}

		return $out;
	}

	function setting_replacement_form_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_select(array('data' => $this->get_for_select(array('local_only' => true, 'force_has_page' => false)), 'name' => $setting_key, 'value' => $option, 'suffix' => $this->get_option_form_suffix(array('value' => $option)), 'description' => __("If you would like all e-mail links in text to be replaced by a form, choose one here", 'lang_form')));
	}

	function setting_replacement_form_text_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Click here to send e-mail", 'lang_form')));
	}

	function setting_form_clear_spam_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 6);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'suffix' => __("months", 'lang_form')));
	}

	function setting_link_yes_text_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
		));
	}

	function setting_link_no_text_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
		));
	}

	function setting_link_thanks_text_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
			'class' => "hide_media_button hide_tabs",
			'mini_toolbar' => true,
			'editor_height' => 100,
		));
	}

	function preg_email_concat($matches)
	{
		$email = $matches[1];
		$attributes = $matches[2];
		$title = $matches[3];

		if($title == $email || strpos($title, $email))
		{
			$title = get_option_or_default('setting_replacement_form_text', __("Click here to send e-mail", 'lang_form'));
		}

		$this->id = get_option('setting_replacement_form');

		$form_md5 = md5("form_link_".$email."_".mt_rand(1, 1000));

		if($attributes != '')
		{
			$attributes = str_replace("class='", "class='form_link ", $attributes);
		}

		else
		{
			$attributes = " class='form_link'";
		}

		$out = "<a href='#'".$attributes." rel='".$form_md5."'>".$title."</a>
		<div id='inline_form_".$form_md5."' class='form_inline hide'>"
			.$this->process_form(array('send_to' => $email))
		."</div>";

		return $out;
	}

	function the_content($html)
	{
		global $wpdb, $post;

		if(get_option('setting_replacement_form') > 0)
		{
			$char_before = "?<=^|\s|\(|\[";
			$chars = "[-A-Za-z\d_.]+[@][A-Za-z\d_-]+([.][A-Za-z\d_-]+)*[.][A-Za-z]{2,8}";
			$char_after = "?=\s|$|\)|\'|\!|(\?)|\.|\]|\<|\[|;";

			$html = preg_replace("/(".$char_before.")(".$chars.")(".$char_after.")/", "<a href='mailto:$1'>$1</a>", $html);
			$html = preg_replace_callback("/<a.*?href=['\"]mailto:(.*?)['\"](.*?)>(.*?)<\/a>/si", array($this, 'preg_email_concat'), $html);
		}

		$intAnswerID = check_var('answer_id', 'int');

		if($intAnswerID > 0)
		{
			$value = $wpdb->get_var($wpdb->prepare("SELECT SUM(formOptionKey) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form_option ON ".$wpdb->base_prefix."form_answer.answerText = ".$wpdb->base_prefix."form_option.formOptionID WHERE answerID = '%d' AND formTypeID IN('8', '17')", $intAnswerID)); //radio_button, radio_multiple

			$if_statement = get_match("/\[if (.*?)\]/i", $html, false);

			if($if_statement != '')
			{
				$if_parts = explode(" ", $if_statement);

				$log_message = $post->ID." -> <a href='".get_permalink($post->ID)."'>".get_post_title($post->ID)."</a> -> ".$if_statement;

				switch($if_parts[0])
				{
					case 'value':
						switch($if_parts[1])
						{
							case '>':
								if($value > $if_parts[2])
								{
									$html = preg_replace(array("/\[if ".$if_statement."\](\<br\>)?/i", "/\[end_if\](\<br\>)?/i", "/\[else\](.*?)?\[end_else\](\<br\>)?/is"), "", $html);
								}

								else
								{
									$html = preg_replace(array("/\[if ".$if_statement."\](.*?)\[end_if\](\<br\>)?/is", "/\[else\](\<br\>)?/i", "/\[end_else\](\<br\>)?/i"), "", $html);
								}
							break;

							case '<':
								if($value < $if_parts[2])
								{
									$html = preg_replace(array("/\[if ".$if_statement."\](\<br\>)?/i", "/\[end_if\](\<br\>)?/i", "/\[else\](.*?)?\[end_else\](\<br\>)?/is"), "", $html);
								}

								else
								{
									$html = preg_replace(array("/\[if ".$if_statement."\](.*?)?\[end_if\](\<br\>)?/is", "/\[else\](\<br\>)?/i", "/\[end_else\](\<br\>)?/i"), "", $html);
								}
							break;

							default:
								do_log("Unknown if statement (2/".count($if_parts)."): ".$log_message);
							break;
						}
					break;

					default:
						do_log("Unknown if statement (1/".count($if_parts)."): ".$log_message);
					break;
				}
			}
		}

		else
		{
			$html = preg_replace(array("/\[if(.*?)\](.*?)?\[end_if\](\<br\>)?/is", "/\[else\](\<br\>)?/i", "/\[end_else\](\<br\>)?/i"), "", $html);
		}

		return $html;
	}

	function combined_head()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_form', $plugin_include_url."style.css", $plugin_version);
		mf_enqueue_script('script_form', $plugin_include_url."script.js", array('ajax_url' => admin_url('admin-ajax.php'), 'plugins_url' => plugins_url(), 'plugin_url' => $plugin_include_url, 'please_wait' => __("Please wait", 'lang_form')), $plugin_version);
	}

	function admin_init()
	{
		global $pagenow;

		$this->combined_head();

		if($pagenow == 'admin.php')
		{
			$page = check_var('page');

			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			if($page == 'mf_form/list/index.php')
			{
				mf_enqueue_script('script_forms_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')), $plugin_version);
			}

			else
			{
				if($page == 'mf_form/create/index.php')
				{
					$plugin_base_include_url = plugins_url()."/mf_base/include/";

					wp_enqueue_script('jquery-ui-sortable');
					mf_enqueue_script('script_touch', $plugin_base_include_url."jquery.ui.touch-punch.min.js", $plugin_version);
				}

				if($page == 'mf_form/create/index.php' || $page == 'mf_form/answer/index.php')
				{
					mf_enqueue_style('style_forms_wp', $plugin_include_url."style_wp.css", $plugin_version);
					mf_enqueue_script('script_forms_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')), $plugin_version);
				}
			}
		}

		if(function_exists('wp_add_privacy_policy_content'))
		{
			if($this->get_amount() > 0)
			{
				$content = __("Forms that collect personal information stores the data in the database to make sure that the entered information is sent to the correct recipient.", 'lang_form');

				if($this->has_remember_fields())
				{
					$content .= "\n\n"
					.sprintf(__("When a visitor enters personal information in a form it is also saved in the so called %s which makes the browser remember what was last entered in each field. This is only used for return visitors and can be removed by the visitor.", 'lang_form'), "'localStorage'");
				}

				wp_add_privacy_policy_content(__("Forms", 'lang_form'), $content);
			}
		}
	}

	function filter_sites_table_settings($arr_settings)
	{
		$arr_settings['settings_form'] = array(
			'setting_redirect_emails' => array(
				'type' => 'bool',
				'global' => false,
				'icon' => "fas fa-share",
				'name' => __("Redirect all e-mails", 'lang_form'),
			),
			'setting_form_test_emails' => array(
				'type' => 'bool',
				'global' => false,
				'icon' => "fas fa-share",
				'name' => __("Redirect test e-mails", 'lang_form'),
			),
			'setting_replacement_form' => array(
				'type' => 'string',
				'global' => false,
				'icon' => "fas fa-at",
				'name' => __("Form to replace all e-mail links", 'lang_form'),
			),
		);

		return $arr_settings;
	}

	function get_count_answer_message($data = array())
	{
		global $wpdb;

		if(!isset($data['form_id'])){		$data['form_id'] = 0;}
		if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}
		if(!isset($data['return_type'])){	$data['return_type'] = 'html';}

		$out = "";

		$last_viewed = get_user_meta($data['user_id'], 'meta_forms_viewed', true);

		$query_xtra = get_form_xtra(" WHERE answerCreated > %s".($data['form_id'] > 0 ? " AND formID = '".$data['form_id']."'" : ""))
			." AND (blogID = '".$wpdb->blogid."' OR blogID IS null)"
			." AND answerSpam = '0'";

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->base_prefix."form2answer USING (formID)".$query_xtra, ($last_viewed > DEFAULT_DATE ? $last_viewed : date("Y-m-d H:i:s"))));
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			switch($data['return_type'])
			{
				default:
				case 'html':
					$out = "&nbsp;<span class='update-plugins' title='".__("Unread answers", 'lang_form')."'>
						<span>".$rows."</span>
					</span>";
				break;

				case 'array':
					$out = array(
						'title' => $rows > 1 ? sprintf(__("There are %d new answers", 'lang_form'), $rows) : __("There is one new answer", 'lang_form'),
						'tag' => 'form',
						'link' => admin_url("admin.php?page=mf_form/list/index.php"),
					);
				break;
			}
		}

		else if(!($data['form_id'] > 0))
		{
			$result = $wpdb->get_results("SELECT answerCreated FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->base_prefix."form2answer USING (formID) ORDER BY answerCreated DESC LIMIT 0, 2");

			if($wpdb->num_rows == 2)
			{
				$dteAnswerNew = $dteAnswerOld = "";

				$i = 0;

				foreach($result as $r)
				{
					if($i == 0)
					{
						$dteAnswerNew = $r->answerCreated;
					}

					else
					{
						$dteAnswerOld = $r->answerCreated;
					}

					$i++;
				}

				if($dteAnswerNew > DEFAULT_DATE && $dteAnswerOld > DEFAULT_DATE)
				{
					$date_diff_old = time_between_dates(array('start' => $dteAnswerOld, 'end' => $dteAnswerNew, 'type' => 'round', 'return' => 'minutes'));
					$date_diff_new = time_between_dates(array('start' => $dteAnswerNew, 'end' => date("Y-m-d H:i:s"), 'type' => 'round', 'return' => 'minutes'));

					if($date_diff_new > ($date_diff_old * 2) && $date_diff_new > (60 * 24 * 2) && $last_viewed < date("Y-m-d H:i:s", strtotime("-".$date_diff_new." minute")))
					{
						$message_temp = sprintf(__("There are no answers since %s", 'lang_form'), format_date($dteAnswerNew));

						switch($data['return_type'])
						{
							default:
							case 'html':
								$out = "&nbsp;<span title='".$message_temp."'>
									<i class='fa fa-exclamation-triangle yellow'></i>
								</span>";
							break;

							case 'array':
								$out = array(
									'title' => $message_temp,
									'tag' => 'form',
									'link' => admin_url("admin.php?page=mf_form/list/index.php"),
								);
							break;
						}
					}
				}
			}
		}

		return $out;
	}

	function admin_menu()
	{
		$count_forms = $this->get_amount();

		$menu_root = 'mf_form/';
		$menu_start = ($count_forms > 0 ? $menu_root."list/index.php" : $menu_root."create/index.php");
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

		$count_message = ($count_forms > 0 ? $this->get_count_answer_message() : "");

		$menu_title = __("Forms", 'lang_form');
		add_menu_page("", $menu_title.$count_message, $menu_capability, $menu_start, '', 'dashicons-forms', 21);

		$menu_title = __("List", 'lang_form');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		if($count_forms > 0)
		{
			$menu_title = __("Add New", 'lang_form');
			add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, $menu_root.'create/index.php');

			$menu_title = __("Answers", 'lang_form');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'answer/index.php');

			$menu_title = __("Edit Answer", 'lang_form');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'view/index.php');
		}

		$menu_title = __("Settings", 'lang_form');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_form"));
	}

	function wp_trash_post($post_id)
	{
		global $wpdb;

		if(get_post_type($post_id) == $this->post_type)
		{
			do_log("Delete postID (#".$post_id.") from ".$wpdb->base_prefix."form");

			/*$this->get_form_id($post_id);

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = (SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d')", $this->id));

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d'", $this->id));
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = (SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d')", $this->id));
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d'", $this->id));
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $this->id));*/
		}
	}

	function deleted_user($user_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
	}

	function do_clone_site($data)
	{
		global $wpdb;

		//do_log("Cloned Forms: ".var_export($data, true));

		$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."form WHERE blogID = '%d'", $data['from']));

		foreach($result as $r)
		{
			$intFormID = $r->formID;

			if($this->clone_form(array('id' => $intFormID, 'blog_id_to' => $data['to'], 'include_answers' => true))) //, 'blog_id_from' => $data['from']
			{
				$done_text = __("The form was successfully copied", 'lang_form');
			}

			else
			{
				$error_text = __("Something went wrong. Contact your admin and add this URL as reference", 'lang_form');
			}
		}
	}

	function do_switch_sites($data)
	{
		global $wpdb;

		do_log("Switched Forms: ".var_export($data, true));

		$result = $wpdb->get_results($wpdb->prepare("SELECT formID, blogID FROM ".$wpdb->base_prefix."form WHERE (blogID = '%d' OR blogID = '%d')", $data['from'], $data['to']));

		foreach($result as $r)
		{
			$intFormID = $r->formID;
			$intBlogID = $r->blogID;

			if($intBlogID == $data['from'])
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET blogID = '%d' WHERE formID = '%d'", $data['to'], $intFormID));
			}

			else
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET blogID = '%d' WHERE formID = '%d'", $data['from'], $intFormID));
			}
		}
	}

	function filter_last_updated_post_types($array, $type)
	{
		if($type == 'auto')
		{
			$array[] = $this->post_type;
		}

		return $array;
	}

	function has_remember_fields()
	{
		global $wpdb;

		$form_id = $wpdb->get_var("SELECT formID FROM ".$wpdb->base_prefix."form2type WHERE formTypeRemember = '1'");

		return ($form_id > 0);
	}

	function export_personal_data($email_address, $page = 1)
	{
		$number = 200;
		$page = (int)$page;

		$group_id = $this->meta_prefix;
		$group_label = __("Forms", 'lang_form');

		$export_items = array();

		$tbl_group = new mf_answer_table(array('search' => $email_address));

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated",
			'limit' => (($page - 1) * $number),
			'amount' => $number,
		));

		foreach($tbl_group->data as $r)
		{
			$item_id = $this->meta_prefix."-".$r->answerID;

			$data = array(
				array(
					'name' => __("Created"),
					'value' => $r->answerCreated,
				),
			);

			$export_items[] = array(
				'group_id' => $group_id,
				'group_label' => $group_label,
				'item_id' => $item_id,
				'data' => $data,
			);
		}

		return array(
			'data' => $export_items,
			'done' => (count($tbl_group->data) < $number),
		);
	}

	function wp_privacy_personal_data_exporters($exporters)
	{
		$exporters[$this->meta_prefix] = array(
			'exporter_friendly_name' => __("Forms", 'lang_form'),
			'callback' => array($this, 'export_personal_data'),
		);

		return $exporters;
	}

	function erase_personal_data($email_address, $page = 1)
	{
		$number = 200;
		$page = (int)$page;

		$items_removed = false;

		$tbl_group = new mf_answer_table(array('search' => $email_address));

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated",
			//'limit' => (($page - 1) * $number),
			//'amount' => $number,
		));

		foreach($tbl_group->data as $r)
		{
			//$this->delete_answer($r->answerID)
			do_log("Delete Answer ".$r->answerID);

			$items_removed = true;
		}

		return array(
			'items_removed' => $items_removed,
			'items_retained' => false, // always false in this example
			'messages' => array(), // no messages in this example
			'done' => (count($result) < $number),
		);
	}

	function wp_privacy_personal_data_erasers($erasers)
	{
		$erasers[$this->meta_prefix] = array(
			'eraser_friendly_name' => __("Forms", 'lang_form'),
			'callback' => array($this, 'erase_personal_data'),
		);

		return $erasers;
	}

	function count_shortcode_button($count)
	{
		if($count == 0 && $this->get_amount(array('post_status' => 'publish')) > 0)
		{
			$count++;
		}

		return $count;
	}

	function get_shortcode_output($out)
	{
		$tbl_group = new mf_form_table();

		$tbl_group->select_data(array(
			//'select' => "*",
		));

		if(count($tbl_group->data) > 0)
		{
			$arr_data = array(
				'' => "-- ".__("Choose Here", 'lang_form')." --",
			);

			foreach($tbl_group->data as $r)
			{
				$arr_data[$this->get_form_id($r['ID'])] = $r['post_title'];
			}

			$out .= "<h3>".__("Choose a Form", 'lang_form')."</h3>"
			.show_select(array('data' => $arr_data, 'xtra' => "rel=".$this->post_type));
		}

		return $out;
	}

	function get_shortcode_list($data)
	{
		$post_id = $data[0];
		$content_list = $data[1];

		if($post_id > 0)
		{
			$this->get_form_id_from_post_content($post_id);

			if($this->id > 0)
			{
				$content_list .= "<li><a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id)."'>".$this->get_form_name()."</a> <span class='grey'>[mf_form id=".$this->id."]</span></li>";
			}
		}

		return array($post_id, $content_list);
	}

	function filter_cookie_types($array)
	{
		global $wpdb;

		$form_id_with_ip = 0;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE blogID = '%d' AND formSaveIP = %s AND formDeleted = '0' LIMIT 0, 1", $wpdb->blogid, 'yes'));

		foreach($result as $r)
		{
			$form_id_with_ip = $r->formID;
		}

		if($form_id_with_ip == 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE blogID = '%d' AND formSaveIP = %s AND formDeleted = '0'", $wpdb->blogid, 'no'));

			foreach($result as $r)
			{
				if($this->is_poll(array('id' => $r->formID)))
				{
					$form_id_with_ip = $r->formID;
					break;
				}
			}
		}

		if($form_id_with_ip > 0)
		{
			$array['ip']['form_save_ip'] = array('label' => sprintf(__("The form %s collects IP address", 'lang_form'), "<em>".$this->get_form_name($form_id_with_ip)."</em>"), 'used' => false, 'lifetime' => "1 year");
		}

		return $array;
	}

	function delete_answer($answer_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d'", $answer_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d'", $answer_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE answerID = '%d'", $answer_id));

		return $wpdb->rows_affected;
	}

	function wp_sitemaps_post_types($post_types)
	{
		unset($post_types[$this->post_type]);

		return $post_types;
	}

	function wp_head()
	{
		$this->combined_head();
	}

	function login_init()
	{
		$this->combined_head();
	}

	function shortcode_form($atts)
	{
		extract(shortcode_atts(array(
			'id' => '',
		), $atts));

		$this->id = $id;

		unset($atts['id']);
		$this->form_atts = $atts;

		return $this->process_form();
	}

	function widgets_init()
	{
		if(count($this->get_for_select(array('local_only' => true, 'force_has_page' => false))) > 1)
		{
			register_widget('widget_form');
		}
	}

	function phpmailer_init($phpmailer)
	{
		if(is_user_logged_in() && defined('IS_ADMIN') && IS_ADMIN && get_option('setting_form_test_emails') == 'yes')
		{
			$user_data = get_userdata(get_current_user_id());

			$mail_to = $phpmailer->getToAddresses();
			$mail_to_old = $mail_to[0][0];
			$mail_to_new = $user_data->user_email;

			$reject_subject_start = "[".get_bloginfo('name')."] ";

			if($mail_to_new != $mail_to_old && $phpmailer->FromName != "WordPress" && substr($phpmailer->Subject, 0, strlen($reject_subject_start)) != $reject_subject_start)
			{
				$phpmailer->Subject = __("Redirect Test", 'lang_form')." (".$mail_to_old."): ".$phpmailer->Subject;
				$phpmailer->clearAddresses();
				$phpmailer->addAddress($mail_to_new);
			}
		}

		else if(get_option('setting_redirect_emails') == 'yes')
		{
			$mail_to = $phpmailer->getToAddresses();
			$mail_to_old = $mail_to[0][0];
			$mail_to_new = get_bloginfo('admin_email');

			if($mail_to_new != $mail_to_old)
			{
				$phpmailer->Subject = __("Redirect All", 'lang_form')." (".$mail_to_old."): ".$phpmailer->Subject;
				$phpmailer->clearAddresses();
				$phpmailer->addAddress($mail_to_new);
			}
		}
	}

	/*function get_user_reminders($array)
	{
		$user_id = $array['user_id'];
		$reminder_cutoff = $array['cutoff'];

		do_log("obj_form->get_user_reminder was run for ".$user_id." (".$reminder_cutoff.")");

		$update_form = $this->get_count_answer_message(array('form_id' => $this->id));

		if($update_form != '')
		{
			$array['reminder'][] = $update_form;
		}

		return $array;
	}*/

	function meta($data)
	{
		if($data['action'] == "get")
		{
			return get_post_meta($this->post_id, $this->meta_prefix.$data['key'], true);
		}

		else
		{
			update_post_meta($this->post_id, $this->meta_prefix.$data['key'], $data['value']);
		}
	}

	function save_options($intForm2TypeID, $arrFormTypeSelect_id, $arrFormTypeSelect_key, $arrFormTypeSelect_value, $arrFormTypeSelect_limit, $arrFormTypeSelect_action)
	{
		global $wpdb;

		$count_temp = count($arrFormTypeSelect_value);

		for($i = 0; $i < $count_temp; $i++)
		{
			if($arrFormTypeSelect_id[$i] > 0)
			{
				$wpdb->get_results($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' AND formOptionID = '%d'", $intForm2TypeID, $arrFormTypeSelect_id[$i]));
				$rows = $wpdb->num_rows;

				if($rows == 1)
				{
					if($arrFormTypeSelect_value[$i] != '')
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_option SET formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionAction = '%d', formOptionOrder = '%d' WHERE form2TypeID = '%d' AND formOptionID = '%d'", $arrFormTypeSelect_key[$i], $arrFormTypeSelect_value[$i], $arrFormTypeSelect_limit[$i], $arrFormTypeSelect_action[$i], $i, $intForm2TypeID, $arrFormTypeSelect_id[$i]));

						if($wpdb->rows_affected == 1)
						{
							//$updated = true;
						}

						/*else // If nothing has changed, don't log it
						{
							do_log("I could not update the option (".var_export($wpdb->last_query, true).")");
						}*/
					}

					else
					{
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' AND formOptionID = '%d'", $intForm2TypeID, $arrFormTypeSelect_id[$i]));

						if($wpdb->rows_affected == 1)
						{
							//$reload = $updated = true;
						}

						else
						{
							do_log("I could not remove the option (".$wpdb->last_query.")");
						}
					}
				}

				/*else
				{
					do_log("I could not find just one (".$wpdb->last_query." -> ".$rows.")");
				}*/
			}

			else
			{
				if($arrFormTypeSelect_value[$i] != '')
				{
					$intFormOptionOrder_temp = $wpdb->get_var($wpdb->prepare("SELECT formOptionOrder FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder DESC LIMIT 0, 1", $intForm2TypeID));

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_option SET form2TypeID = '%d', formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionAction = '%d', formOptionOrder = '%d'", $intForm2TypeID, $arrFormTypeSelect_key[$i], $arrFormTypeSelect_value[$i], $arrFormTypeSelect_limit[$i], $arrFormTypeSelect_action[$i], ($intFormOptionOrder_temp + 1)));

					if($wpdb->rows_affected == 1)
					{
						//$reload = $updated = true;
					}

					else
					{
						do_log("I could not save the option (".$wpdb->last_query.")");
					}
				}
			}
		}
	}

	function validate_select_array()
	{
		$i = 0;
		$is_updated = false;
		$formTypeSelect_new = "";

		$arr_options = explode(",", $this->formTypeSelect);

		foreach($arr_options as $key => $str_option)
		{
			$arr_option = explode("|", $str_option);

			if(is_numeric($arr_option[0]) && $arr_option[0] > $i)
			{
				$i = $arr_option[0];
			}
		}

		foreach($arr_options as $key => $str_option)
		{
			$arr_option = explode("|", $str_option);

			if($arr_option[0] == '')
			{
				$arr_option[0] = ++$i;

				$is_updated = true;
			}

			$formTypeSelect_new .= ($formTypeSelect_new != '' ? "," : "").implode("|", $arr_option);
		}

		if($is_updated)
		{
			$this->formTypeSelect = $formTypeSelect_new;
		}
	}

	function parse_range_label()
	{
		list($this->label, $rest) = explode("|", $this->label);
	}

	function parse_select_info($strAnswerText)
	{
		global $wpdb;

		if($this->form_option_exists)
		{
			if($strAnswerText != '')
			{
				$strAnswerText = $wpdb->get_var($wpdb->prepare("SELECT formOptionValue FROM ".$wpdb->base_prefix."form_option WHERE formOptionID = '%d'", $strAnswerText));
			}
		}

		else
		{
			list($this->label, $str_select) = explode(":", $this->label);

			$arr_options = explode(",", $str_select);

			foreach($arr_options as $str_option)
			{
				$arr_option = explode("|", $str_option);

				if($strAnswerText == $arr_option[0])
				{
					$strAnswerText = $arr_option[1];
				}
			}
		}

		return $strAnswerText;
	}

	function parse_multiple_info($strAnswerText, $return_value)
	{
		global $wpdb;

		@list($this->label, $str_select) = explode(":", $this->label);

		$arr_answer_text = explode(",", str_replace($this->prefix, "", $strAnswerText));
		$strAnswerText = "";

		if($return_value && count($arr_answer_text) > 0)
		{
			if($this->form_option_exists)
			{
				$result = $wpdb->get_results("SELECT formOptionValue FROM ".$wpdb->base_prefix."form_option WHERE formOptionID IN ('".implode("','", $arr_answer_text)."') AND formOptionKey != '0'");

				foreach($result as $r)
				{
					$strAnswerText .= ($strAnswerText != '' ? ", " : "").$r->formOptionValue;
				}
			}

			else
			{
				$arr_options = explode(",", $str_select);

				foreach($arr_options as $str_option)
				{
					$arr_option = explode("|", $str_option);

					if(in_array($arr_option[0], $arr_answer_text))
					{
						$strAnswerText .= ($strAnswerText != '' ? ", " : "").$arr_option[1];
					}
				}
			}
		}

		if($strAnswerText == '')
		{
			$strAnswerText = implode(",", $arr_answer_text);
		}

		return $strAnswerText;
	}

	function get_form_types()
	{
		return array(
			1 => array('code' => 'checkbox',			'name' => "&#xf14a; ".__("Checkbox", 'lang_form'),					'desc' => __("To choose one or many alternatives", 'lang_form')), //, 'result' => 1
			2 => array('code' => 'range',				'name' => "&#xf258; ".__("Range", 'lang_form'),						'desc' => __("To choose a min and max value and create a slider for that", 'lang_form')), //, 'result' => 1
			3 => array('code' => 'input_field',			'name' => "&#xf044; ".__("Input Field", 'lang_form'),				'desc' => __("To enter a short text", 'lang_form')), //, 'result' => 1
			4 => array('code' => 'textarea',			'name' => "&#xf044; ".__("Textarea", 'lang_form'),					'desc' => __("To enter a longer text on multiple rows", 'lang_form')), //, 'result' => 1
			5 => array('code' => 'text',				'name' => "&#xf27a; ".__("Text", 'lang_form'),						'desc' => __("To present information to the visitor", 'lang_form')), //, 'result' => 0
			6 => array('code' => 'space',				'name' => "&#xf2d1; ".__("Space", 'lang_form'),						'desc' => __("To separate fields in the form with empty space", 'lang_form')), //, 'result' => 0
			7 => array('code' => 'datepicker',			'name' => "&#xf073; ".__("Datepicker", 'lang_form'),				'desc' => __("To choose a date", 'lang_form')), //, 'result' => 1
			8 => array('code' => 'radio_button',		'name' => "&#xf192; ".__("Radio Button", 'lang_form'),				'desc' => __("To choose one alternative", 'lang_form')), //, 'result' => 1
			9 => array('code' => 'referer_url',			'name' => "&#xf164; ".__("Referer URL", 'lang_form'),				'desc' => __("To get which URL the visitor came from", 'lang_form')), //, 'result' => 1
			10 => array('code' => 'select',				'name' => "&#xf022; ".__("Dropdown", 'lang_form'),					'desc' => __("To choose one alternative", 'lang_form')), //, 'result' => 1
			11 => array('code' => 'select_multiple',	'name' => "&#xf022; ".__("Multiple Selection", 'lang_form'),		'desc' => __("To choose one or many alternatives", 'lang_form')), //, 'result' => 1
			12 => array('code' => 'hidden_field',		'name' => "&#xf070; ".__("Hidden Field", 'lang_form'),				'desc' => __("To add hidden data to the form", 'lang_form')), //, 'result' => 1
			13 => array('code' => 'custom_tag',			'name' => "&#xf070; ".__("Custom Tag", 'lang_form'),				'desc' => __("To add a custom tag", 'lang_form')), //, 'result' => 0
			14 => array('code' => 'custom_tag_end',		'name' => "&#xf070; ".__("Custom Tag (end)", 'lang_form'),			'desc' => __("To add a custom end tag", 'lang_form')), //, 'result' => 0, 'public' => 'no'
			15 => array('code' => 'file',				'name' => "&#xf07c; ".__("File", 'lang_form'),						'desc' => __("To add a file upload to the form", 'lang_form')), //, 'result' => 1
			16 => array('code' => 'checkbox_multiple',	'name' => "&#xf14a; ".__("Multiple Checkboxes", 'lang_form'),		'desc' => __("To choose one or many alternatives", 'lang_form')), //, 'result' => 1
			17 => array('code' => 'radio_multiple',		'name' => "&#xf192; ".__("Multiple Radio Buttons", 'lang_form'),	'desc' => __("To choose one alternative", 'lang_form')), //, 'result' => 1
		);
	}

	function get_form_types_for_select($data = array())
	{
		global $wpdb;

		if(!isset($data['form_type_id'])){		$data['form_type_id'] = 0;}

		$arr_data = array();

		$result = $wpdb->get_results("SELECT formTypeID, formTypeCode, formTypeName, formTypeDesc, COUNT(formTypeID) AS formType_amount FROM ".$wpdb->base_prefix."form_type LEFT JOIN ".$wpdb->base_prefix."form2type USING (formTypeID) WHERE formTypeCode NOT IN('custom_tag_end') GROUP BY formTypeID ORDER BY formType_amount DESC, formTypeName ASC"); //formTypePublic = 'yes'

		if($wpdb->num_rows > 0)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";

			foreach($result as $r)
			{
				if($data['form_type_id'] != $r->formTypeID && in_array($r->formTypeCode, array('radio_button')))
				{
					//Don't let new fields be old style radio button
				}

				else if(in_array($data['form_type_id'], array(10, 11, 16, 17)) && !in_array($r->formTypeCode, array('select', 'select_multiple', 'checkbox_multiple', 'radio_multiple')))
				{
					//Don't let the user change from any of these fields to one that does not have the same structure
				}

				else if(in_array($data['form_type_id'], array(2, 3, 4, 5, 6, 7, 9, 12, 15)) && !in_array($r->formTypeCode, array('range', 'input_field', 'textarea', 'text', 'space', 'datepicker', 'referer_url', 'hidden_field', 'file')))
				{
					//Don't let the user change from any of these fields to one that does not have the same structure
				}

				else if($data['form_type_id'] == 13 && $r->formTypeCode != 'custom_tag')
				{
					//Don't let the use change from Custom Tag
				}

				else
				{
					$arr_data[$r->formTypeID] = array(
						'name' => $r->formTypeName,
						'desc' => $r->formTypeDesc,
					);
				}
			}
		}

		return $arr_data;
	}

	function get_form_checks_for_select()
	{
		global $wpdb;

		$arr_data = array();

		$result = $wpdb->get_results("SELECT checkID, checkName FROM ".$wpdb->base_prefix."form_check WHERE checkPublic = '1' ORDER BY checkName ASC");

		if($wpdb->num_rows > 0)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";

			foreach($result as $r)
			{
				$arr_data[$r->checkID] = __($r->checkName, 'lang_form');
			}
		}

		return $arr_data;
	}

	function get_form_tag_types_for_select()
	{
		return array(
			'div' => "div",
			'fieldset' => "fieldset",
		);
	}

	function get_tags_for_select()
	{
		return array(
			'' => "-- ".__("Choose Here", 'lang_form')." --",
			'h1' => "h1",
			'h2' => "h2",
			'h3' => "h3",
			'h4' => "h4",
			'h5' => "h5",
			'p' => "p",
			'blockquote' => "blockquote",
		);
	}

	function get_pages_for_select()
	{
		$arr_data = array();
		get_post_children(array('add_choose_here' => true), $arr_data);

		return $arr_data;
	}

	function get_payment_providers_for_select()
	{
		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_form')." --",
		);

		return apply_filters('form_payment_alternatives', $arr_data);
	}

	function get_payment_currency_for_select($intFormPaymentProvider)
	{
		global $obj_base;

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_form')." --"
		);

		switch($intFormPaymentProvider)
		{
			case 1:
				$arr_data[208] = __("Danish Krone", 'lang_form')." (DKK)";
				$arr_data[978] = __("Euro", 'lang_form')." (EUR)";
				$arr_data[840] = __("US Dollar", 'lang_form')." (USD)";
				$arr_data[826] = __("English Pound", 'lang_form')." (GBP)";
				$arr_data[752] = __("Swedish Krona", 'lang_form')." (SEK)";
				$arr_data[036] = __("Australian Dollar", 'lang_form')." (AUD)";
				$arr_data[124] = __("Canadian Dollar", 'lang_form')." (CAD)";
				$arr_data[352] = __("Icelandic Krona", 'lang_form')." (ISK)";
				$arr_data[392] = __("Japanese Yen", 'lang_form')." (JPY)";
				$arr_data[554] = __("New Zealand Dollar", 'lang_form')." (NZD)";
				$arr_data[578] = __("Norwegian Krone", 'lang_form')." (NOK)";
				$arr_data[756] = __("Swiss Franc", 'lang_form')." (CHF)";
				$arr_data[949] = __("Turkish Lira", 'lang_form')." (TRY)";
			break;

			default:
				$arr_data['DKK'] = __("Danish Krone", 'lang_form')." (DKK)";
				$arr_data['EUR'] = __("Euro", 'lang_form')." (EUR)";
				$arr_data['USD'] = __("US Dollar", 'lang_form')." (USD)";
				$arr_data['GBP'] = __("English Pound", 'lang_form')." (GBP)";
				$arr_data['SEK'] = __("Swedish Krona", 'lang_form')." (SEK)";
				$arr_data['AUD'] = __("Australian Dollar", 'lang_form')." (AUD)";
				$arr_data['CAD'] = __("Canadian Dollar", 'lang_form')." (CAD)";
				$arr_data['ISK'] = __("Icelandic Krona", 'lang_form')." (ISK)";
				$arr_data['JPY'] = __("Japanese Yen", 'lang_form')." (JPY)";
				$arr_data['NZD'] = __("New Zealand Dollar", 'lang_form')." (NZD)";
				$arr_data['NOK'] = __("Norwegian Krone", 'lang_form')." (NOK)";
				$arr_data['CHF'] = __("Swiss Franc", 'lang_form')." (CHF)";
				$arr_data['TRY'] = __("Turkish Lira", 'lang_form')." (TRY)";
			break;
		}

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		return $obj_base->array_sort(array('array' => $arr_data, 'on' => 1, 'keep_index' => true));
	}

	function get_payment_amount_for_select()
	{
		list($result, $rows) = $this->get_form_type_info(array('query_type_code' => array('select', 'hidden_field')));

		return $this->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));
	}

	function is_select_value_used($data)
	{
		global $wpdb;

		$out = false;

		if($data['form2type_id'] > 0 && $data['option_id'] > 0) // && $data['option_id'] != ''
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $data['form2type_id'], $data['option_id']));

			$out = ($wpdb->num_rows > 0);
		}

		return $out;
	}

	function fetch_request()
	{
		switch($this->type)
		{
			case 'create':
				$this->name = check_var('strFormName');
				$this->import = check_var('strFormImport');

				$this->url = check_var('strFormURL');
				$this->deadline = check_var('dteFormDeadline');

				$this->form2type_id = check_var('intForm2TypeID');
				$this->form2type_order = check_var('intForm2TypeOrder');

				$this->email_confirm = isset($_POST['intFormEmailConfirm']);
				$this->email_confirm_from_email = check_var('strFormEmailConfirmFromEmail');
				$this->email_confirm_from_email_name = check_var('strFormEmailConfirmFromEmailName');
				$this->email_confirm_id = check_var('intFormEmailConfirmID');
				$this->email_confirm_page = check_var('intFormEmailConfirmPage');
				$this->show_answers = check_var('strFormShowAnswers');
				$this->accept_duplicates = check_var('strFormAcceptDuplicates', 'char', true, 'yes');
				$this->save_ip = check_var('strFormSaveIP');
				$this->answer_url = check_var('strFormAnswerURL');
				$this->email_admin = check_var('strFormEmail', 'email');
				$this->email_admin_name = check_var('strFormFromName');
				$this->email_conditions = check_var('strFormEmailConditions');
				$this->email_notify = check_var('intFormEmailNotify');
				$this->email_notify_from_email = check_var('strFormEmailNotifyFromEmail');
				$this->email_notify_from_email_name = check_var('strFormEmailNotifyFromEmailName');
				$this->email_notify_from = check_var('strFormEmailNotifyFrom');
				$this->email_notify_page = check_var('intFormEmailNotifyPage');
				$this->email_name = check_var('strFormEmailName');
				$this->mandatory_text = check_var('strFormMandatoryText');
				$this->button_display = check_var('intFormButtonDisplay');
				$this->button_text = check_var('strFormButtonText');
				$this->button_symbol = check_var('strFormButtonSymbol');

				// Payments
				$this->payment_provider = check_var('intFormPaymentProvider');
				$this->payment_hmac = check_var('strFormPaymentHmac');
				$this->terms_page = check_var('intFormTermsPage');
				$this->payment_merchant = check_var('strFormPaymentMerchant');
				$this->payment_password = check_var('strFormPaymentPassword');
				$this->payment_currency = check_var('strFormPaymentCurrency');
				$this->payment_cost = check_var('dblFormPaymentCost');
				$this->payment_amount = check_var('intFormPaymentAmount');
				$this->payment_tax = check_var('intFormPaymentTax');
				$this->payment_callback = check_var('strFormPaymentCallback');

				$this->type_id = check_var('intFormTypeID');
				$this->type_text = (isset($_POST['strFormTypeText']) ? $_POST['strFormTypeText'] : ""); //Allow HTML here
				$this->type_text2 = check_var('strFormTypeText2');
				$this->check_id = check_var('intCheckID');
				$this->type_placeholder = check_var('strFormTypePlaceholder');
				$this->type_tag = check_var('strFormTypeTag');
				$this->type_class = check_var('strFormTypeClass');
				$this->type_fetch_from = check_var('strFormTypeFetchFrom');
				$this->type_connect_to = check_var('intFormTypeConnectTo');
				$this->type_action_equals = check_var('strFormTypeActionEquals');
				$this->type_action_show = check_var('intFormTypeActionShow');

				// Select
				$this->arr_type_select_id = check_var('arrFormTypeSelect_id');
				$this->arr_type_select_key = check_var('arrFormTypeSelect_key');
				$this->arr_type_select_value = check_var('arrFormTypeSelect_value');
				$this->arr_type_select_limit = check_var('arrFormTypeSelect_limit');
				$this->arr_type_select_action = check_var('arrFormTypeSelect_action');
				//$this->arr_type_select_display = check_var('arrFormTypeSelect_display');

				// Range
				$this->type_min = check_var('strFormTypeMin', '', true, "0");
				$this->type_max = check_var('strFormTypeMax', '', true, 100);
				$this->type_default = check_var('strFormTypeDefault', '', true, 1);
			break;

			default:
				$this->answer_id = check_var('intAnswerID');
			break;
		}
	}

	function get_type_connect_to_root($data)
	{
		global $wpdb;

		$type_connect_to_temp = $data['connect_to'];
		$connect_done = false;

		while($connect_done == false)
		{
			$intFormTypeConnectTo = $wpdb->get_var($wpdb->prepare("SELECT formTypeConnectTo FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $type_connect_to_temp));

			if($intFormTypeConnectTo > 0)
			{
				$type_connect_to_temp = $intFormTypeConnectTo;
			}

			else
			{
				$connect_done = true;
			}
		}

		return ($type_connect_to_temp > 0 ? $type_connect_to_temp : $data['field_id']);
	}

	function clone_form($data)
	{
		global $wpdb;

		if(!isset($data['include_answers'])){	$data['include_answers'] = false;}
		//if(!isset($data['blog_id_from'])){	$data['blog_id_from'] = 0;}
		if(!isset($data['blog_id_to'])){		$data['blog_id_to'] = 0;}

		$success = true;

		$arr_form_id = $arr_form2type_id = $arr_answer_id = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $data['id'])); //, formName

		foreach($result as $r)
		{
			$intFormID = $r->formID;
			//$strFormName = $r->formName;

			$copy_fields = ", blogID, formAnswerURL, formEmail, formFromName, formEmailNotify, formEmailNotifyFrom, formEmailNotifyPage, formEmailName, formEmailConfirm, formEmailConfirmID, formEmailConfirmPage, formShowAnswers, formMandatoryText, formButtonDisplay, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formTermsPage, formPaymentMerchant, formPaymentCurrency, formPaymentCheck, formPaymentCost, formPaymentTax, formPaymentCallback";

			$strFormName = $this->get_form_name($intFormID);

			$post_data = array(
				'post_type' => $this->post_type,
				'post_status' => 'publish',
				'post_title' => $strFormName." (".__("copy", 'lang_form').")",
			);

			$intPostID = wp_insert_post($post_data);

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form (formName, postID".$copy_fields.", formCreated, userID) (SELECT CONCAT(formName, ' (".__("copy", 'lang_form').")'), '%d'".$copy_fields.", NOW(), '%d' FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0')", $intPostID, get_current_user_id(), $intFormID));
			$intFormID_new = $wpdb->insert_id;

			if($intFormID_new > 0)
			{
				$arr_form_id[$intFormID] = $intFormID_new;

				if($data['blog_id_to'] > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET blogID = '%d' WHERE formID = '%d'", $data['blog_id_to'], $intFormID_new));
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' ORDER BY form2TypeID DESC", $intFormID));

				foreach($result as $r)
				{
					$intForm2TypeID = $r->form2TypeID;

					$copy_fields = "formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeRemember, form2TypeOrder";

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type (formID, ".$copy_fields.", form2TypeCreated, userID) (SELECT %d, ".$copy_fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d')", $intFormID_new, $intForm2TypeID));
					$intForm2TypeID_new = $wpdb->insert_id;

					if($intForm2TypeID_new > 0)
					{
						$arr_form2type_id[$intForm2TypeID] = $intForm2TypeID_new;

						$copy_fields = "formOptionKey, formOptionValue, formOptionLimit, formOptionOrder, formOptionAction";

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_option (form2TypeID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d')", $intForm2TypeID_new, $intForm2TypeID));
					}

					else
					{
						$success = false;
					}

					if($data['include_answers'] == true)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."wp_form2answer WHERE formID = '%d'", $intFormID));

						foreach($result as $r)
						{
							$intAnswerID = $r->answerID;

							$copy_fields = "answerIP, answerSpam, spamID, answerToken, answerCreated";

							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_option (formID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->base_prefix."form_option WHERE answerID = '%d')", $intFormID_new, $intAnswerID));
							$intAnswerID_new = $wpdb->insert_id;

							if($intAnswerID_new > 0)
							{
								$arr_answer_id[$intAnswerID] = $intAnswerID_new;

								$result_answer = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d'", $intAnswerID));

								foreach($result_answer as $r)
								{
									$intForm2TypeID = $r->form2TypeID;
									$strAnswerText = $r->answerText;

									$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s", $intAnswerID_new, $arr_form2type_id[$intForm2TypeID], $strAnswerText));
								}

								$copy_fields = "answerEmailFrom, answerEmail, answerType, answerSent";

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_email (answerID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d')", $intAnswerID_new, $intAnswerID));

								$copy_fields = "metaKey, metaValue";

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_meta (answerID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->base_prefix."form_answer_meta WHERE answerID = '%d')", $intAnswerID_new, $intAnswerID));
							}
						}

						do_log("Cloned answers from ".$intFormID." -> ".$intFormID_new);
					}
				}
			}

			else
			{
				$success = false;
			}
		}

		return $success;
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		switch($this->type)
		{
			case 'create':
				if((isset($_POST['btnFormPublish']) || isset($_POST['btnFormDraft'])) && wp_verify_nonce($_POST['_wpnonce_form_update'], 'form_update_'.$this->id))
				{
					if($this->name == '')
					{
						$error_text = __("Please, enter all required fields", 'lang_form');
					}

					else
					{
						if($this->id > 0)
						{
							$post_data = array(
								'ID' => $this->post_id,
								'post_status' => (isset($_POST['btnFormPublish']) ? 'publish' : 'draft'),
								'post_title' => $this->name,
								'post_name' => $this->url,
								'post_author' => get_current_user_id(),
							);

							wp_update_post($post_data);

							$this->meta(array('action' => 'update', 'key' => 'deadline', 'value' => $this->deadline));

							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET blogID = '%d', formEmailConfirm = '%d', formEmailConfirmFromEmail = %s, formEmailConfirmFromEmailName = %s, formEmailConfirmID = '%d', formEmailConfirmPage = %s, formShowAnswers = %s, formName = %s, formAcceptDuplicates = %s, formSaveIP = %s, formAnswerURL = %s, formEmail = %s, formFromName = %s, formEmailConditions = %s, formEmailNotify = '%d', formEmailNotifyFromEmail = %s, formEmailNotifyFromEmailName = %s, formEmailNotifyFrom = %s, formEmailNotifyPage = %s, formEmailName = %s, formMandatoryText = %s, formButtonDisplay = '%d', formButtonText = %s, formButtonSymbol = %s, formPaymentProvider = '%d', formPaymentHmac = %s, formTermsPage = '%d', formPaymentMerchant = %s, formPaymentPassword = %s, formPaymentCurrency = %s, formPaymentCost = '%f', formPaymentAmount = '%d', formPaymentTax = '%d', formPaymentCallback = %s WHERE formID = '%d' AND formDeleted = '0'", $wpdb->blogid, $this->email_confirm, $this->email_confirm_from_email, $this->email_confirm_from_email_name, $this->email_confirm_id, $this->email_confirm_page, $this->show_answers, $this->name, $this->accept_duplicates, $this->save_ip, $this->answer_url, $this->email_admin, $this->email_admin_name, $this->email_conditions, $this->email_notify, $this->email_notify_from_email, $this->email_notify_from_email_name, $this->email_notify_from, $this->email_notify_page, $this->email_name, $this->mandatory_text, $this->button_display, $this->button_text, $this->button_symbol, $this->payment_provider, $this->payment_hmac, $this->terms_page, $this->payment_merchant, $this->payment_password, $this->payment_currency, $this->payment_cost, $this->payment_amount, $this->payment_tax, $this->payment_callback, $this->id));

							do_action('update_form_fields', $this);

							$done_text = __("I have updated the form for you", 'lang_form');
						}

						else
						{
							$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_title = '%d' LIMIT 0, 1", $this->post_type, $this->name));

							if($wpdb->num_rows > 0)
							{
								$error_text = __("There is already a form with that name. Try with another one.", 'lang_form');
							}

							else
							{
								$post_data = array(
									'post_type' => $this->post_type,
									'post_status' => (isset($_POST['btnFormPublish']) ? 'publish' : 'draft'),
									'post_title' => $this->name,
								);

								$this->post_id = wp_insert_post($post_data);

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form SET blogID = '%d', postID = '%d', formName = %s, formCreated = NOW(), userID = '%d'", $wpdb->blogid, $this->post_id, $this->name, get_current_user_id()));
								$this->id = $wpdb->insert_id;

								if($this->import != '')
								{
									$arr_import_rows = explode("\n", $this->import);

									foreach($arr_import_rows as $import_row)
									{
										list($this->type_id, $this->type_text, $this->type_placeholder, $this->check_id, $this->type_tag, $this->type_class, $this->type_fetch_from, $this->type_connect_to, $intFormTypeDisplay, $intFormTypeRequired, $intFormTypeAutofocus, $intFormTypeRemember, $this->form2type_order) = explode(",", $import_row);

										switch($this->type_id)
										{
											case 10:
											case 11:
											case 16:
											case 17:
												list($this->type_text, $strFormOptions) = explode(":", $this->type_text, 2);
											break;
										}

										$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type SET formID = '%d', formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeFetchFrom = %s, formTypeConnectTo = '%d', formTypeDisplay = '%d', formTypeRequired = '%d', formTypeAutofocus = '%d', formTypeRemember = '%d', form2TypeOrder = '%d', userID = '%d'", $this->id, $this->type_id, $this->type_text, $this->type_placeholder, $this->check_id, $this->type_tag, $this->type_class, $this->type_fetch_from, $this->type_connect_to, $intFormTypeDisplay, $intFormTypeRequired, $intFormTypeAutofocus, $intFormTypeRemember, $this->form2type_order, get_current_user_id()));

										$intForm2TypeID = $wpdb->insert_id;

										if($this->type_connect_to > 0)
										{
											do_log("Connect the copied field in ".$this->id."->".$intForm2TypeID." to ".$this->type_connect_to." if possible...");
										}

										switch($this->type_id)
										{
											case 10:
											case 11:
											case 16:
											case 17:
												$arr_options = explode(";", $strFormOptions);

												$success = true;
												$i = 0;

												foreach($arr_options as $str_option)
												{
													@list($option_key, $option_value, $option_limit, $option_action) = explode("|", $str_option, 4);

													if($option_value != '')
													{
														$intFormOptionID = $wpdb->get_var($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->base_prefix."form_option WHERE (form2TypeID = '%d' OR form2TypeID = '0') AND (formOptionKey = %s OR formOptionValue = %s)", $intForm2TypeID, $option_key, $option_value));

														if($intFormOptionID > 0)
														{
															$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_option SET form2TypeID = '%d', formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionAction = '%d', formOptionOrder = '%d' WHERE formOptionID = '%d'", $intForm2TypeID, $option_key, $option_value, $option_limit, $option_action, $i, $intFormOptionID));
														}

														else
														{
															$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_option SET form2TypeID = '%d', formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionAction = '%d', formOptionOrder = '%d'", $intForm2TypeID, $option_key, $option_value, $option_limit, $option_action, $i));

															$intFormOptionID = $wpdb->insert_id;
														}

														$i++;
													}

													else
													{
														$success = false;

														do_log("There was no value for the option (2) (".$this->id.", ".$intForm2TypeID.", ".$this->type_text." -> ".$strFormOptions." -> ".$str_option.")");
													}
												}
											break;
										}
									}
								}

								$done_text = __("I have created the form for you", 'lang_form');
							}
						}
					}
				}

				else if(isset($_POST['btnFormAdd']) && wp_verify_nonce($_POST['_wpnonce_form_add'], 'form_add_'.$this->id))
				{
					switch($this->type_id)
					{
						case 2:
						//case 'range':
							$this->type_text = str_replace("|", "", $this->type_text)."|".str_replace("|", "", $this->type_min)."|".str_replace("|", "", $this->type_max)."|".str_replace("|", "", $this->type_default);
						break;

						case 10:
						//case 'select':
						case 11:
						//case 'select_multiple':
						case 16:
						//case 'checkbox_multiple':
						case 17:
						//case 'radio_multiple':
							if(count($this->arr_type_select_value) == 0 || $this->arr_type_select_value[0] == '')
							{
								$error_text = __("Please, enter all required fields", 'lang_form');
							}

							else
							{
								if($this->form_option_exists)
								{
									if($this->form2type_id > 0)
									{
										$this->save_options($this->form2type_id, $this->arr_type_select_id, $this->arr_type_select_key, $this->arr_type_select_value, $this->arr_type_select_limit, $this->arr_type_select_action);
									}
								}

								else
								{
									$count_temp = count($this->arr_type_select_value);

									$this->formTypeSelect = "";

									for($i = 0; $i < $count_temp; $i++)
									{
										if($this->arr_type_select_value[$i] != '')
										{
											$this->formTypeSelect .= ($i > 0 ? "," : "").$this->arr_type_select_id[$i]."|".$this->arr_type_select_value[$i]."|".$this->arr_type_select_limit[$i];
										}
									}

									$this->validate_select_array();

									$this->type_text = str_replace(":", "", $this->type_text).":".str_replace(":", "", $this->formTypeSelect);
								}
							}
						break;

						case 13:
						//case 'custom_tag':
						case 14:
						//case 'custom_tag_end':
							$this->type_text = $this->type_text2;
						break;
					}

					if($error_text == '')
					{
						if($this->form2type_id > 0)
						{
							if($this->type_id > 0 && ($this->type_id == 6 || $this->type_id == 9 || $this->type_text != '')) //'space', 'referer_url'
							{
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeFetchFrom = %s, formTypeConnectTo = '%d', formTypeActionEquals = %s, formTypeActionShow = %s, userID = '%d' WHERE form2TypeID = '%d'", $this->type_id, $this->type_text, $this->type_placeholder, $this->check_id, $this->type_tag, $this->type_class, $this->type_fetch_from, $this->type_connect_to, $this->type_action_equals, $this->type_action_show, get_current_user_id(), $this->form2type_id));

								switch($this->type_id)
								{
									case 13:
									//case 'custom_tag':
										$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeText = %s, userID = '%d' WHERE form2TypeID2 = '%d'", $this->type_text, get_current_user_id(), $this->form2type_id));
									break;
								}

								$this->form2type_id = $this->type_id = $this->type_text = $this->type_placeholder = $this->check_id = $this->type_tag = $this->type_class = $this->type_fetch_from = $this->type_action_equals = $this->type_action_show = "";
							}

							else
							{
								$error_text = __("I could not update the field", 'lang_form');
							}
						}

						else
						{
							if($this->id > 0 && $this->type_id > 0 && ($this->type_id == 6 || $this->type_id == 9 || $this->type_text != '')) //'space', 'referer_url'
							{
								$this->form2type_order = $wpdb->get_var($wpdb->prepare("SELECT (form2TypeOrder + 1) FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder DESC", $this->id));

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type SET formID = '%d', formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeFetchFrom = %s, formTypeConnectTo = '%d', formTypeActionEquals = %s, formTypeActionShow = %s, form2TypeOrder = '%d', form2TypeCreated = NOW(), userID = '%d'", $this->id, $this->type_id, $this->type_text, $this->type_placeholder, $this->check_id, $this->type_tag, $this->type_class, $this->type_fetch_from, $this->type_connect_to, $this->type_action_equals, $this->type_action_show, $this->form2type_order, get_current_user_id()));

								$this->form2type_id = $wpdb->insert_id;

								switch($this->type_id)
								{
									case 13:
									//case 'custom_tag':
										$this->type_id = 14;
										$this->form2type_order++;

										$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type SET form2TypeID2 = '%d', formID = '%d', formTypeID = '%d', formTypeText = %s, form2TypeOrder = '%d', form2TypeCreated = NOW(), userID = '%d'", $this->form2type_id, $this->id, $this->type_id, $this->type_text, $this->form2type_order, get_current_user_id()));
									break;

									case 10:
									//case 'select':
									case 11:
									//case 'select_multiple':
									case 16:
									//case 'checkbox_multiple':
									case 17:
									//case 'radio_multiple':
										if($this->form_option_exists)
										{
											$this->save_options($this->form2type_id, $this->arr_type_select_id, $this->arr_type_select_key, $this->arr_type_select_value, $this->arr_type_select_limit, $this->arr_type_select_action);
										}
									break;
								}

								if($wpdb->rows_affected > 0)
								{
									$this->form2type_id = $this->type_id = $this->type_text = $this->type_placeholder = $this->check_id = $this->type_tag = $this->type_class = $this->type_fetch_from = $this->type_action_equals = $this->type_action_show = "";

									if($this->form_option_exists == false)
									{
										@list($this->type_text, $strFormTypeSelect) = explode(":", $this->type_text);
									}
								}
							}

							else
							{
								$error_text = __("I could not insert the new field for you", 'lang_form');
							}
						}
					}

					if($this->type_id == 0)
					{
						mf_redirect(admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id));
						//echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id)."'</script>";
					}
				}

				if(!isset($_POST['btnFormPublish']) && !isset($_POST['btnFormDraft']) && $this->id > 0)
				{
					if(isset($_GET['recover']))
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET formDeleted = '0' WHERE formID = '%d'", $this->id));
					}

					$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmFromEmail, formEmailConfirmFromEmailName, formEmailConfirmID, formEmailConfirmPage, formShowAnswers, formAcceptDuplicates, formSaveIP, formAnswerURL, formEmail, formFromName, formEmailConditions, formEmailNotify, formEmailNotifyFromEmail, formEmailNotifyFromEmailName, formEmailNotifyFrom, formEmailNotifyPage, formEmailName, formMandatoryText, formButtonDisplay, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formTermsPage, formPaymentMerchant, formPaymentPassword, formPaymentCurrency, formPaymentCost, formPaymentAmount, formPaymentTax, formPaymentCallback, formCreated FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

					if($wpdb->num_rows > 0)
					{
						foreach($result as $r)
						{
							$this->email_confirm = $r->formEmailConfirm;
							$this->email_confirm_from_email = $r->formEmailConfirmFromEmail;
							$this->email_confirm_from_email_name = $r->formEmailConfirmFromEmailName;
							$this->email_confirm_id = $r->formEmailConfirmID;
							$this->email_confirm_page = $r->formEmailConfirmPage;
							$this->show_answers = $r->formShowAnswers;
							$this->accept_duplicates = $r->formAcceptDuplicates;
							$this->save_ip = $r->formSaveIP;
							$this->answer_url = $r->formAnswerURL;
							$this->email_admin = $r->formEmail;
							$this->email_admin_name = $r->formFromName;
							$this->email_conditions = $r->formEmailConditions;
							$this->email_notify = $r->formEmailNotify;
							$this->email_notify_from_email = $r->formEmailNotifyFromEmail;
							$this->email_notify_from_email_name = $r->formEmailNotifyFromEmailName;
							$this->email_notify_from = $r->formEmailNotifyFrom;
							$this->email_notify_page = $r->formEmailNotifyPage;
							$this->email_name = $r->formEmailName;
							$this->mandatory_text = $r->formMandatoryText;
							$this->button_display = $r->formButtonDisplay;
							$this->button_text = $r->formButtonText;
							$this->button_symbol = $r->formButtonSymbol;
							$this->payment_provider = $r->formPaymentProvider;
							$this->payment_hmac = $r->formPaymentHmac;
							$this->terms_page = $r->formTermsPage;
							$this->payment_merchant = $r->formPaymentMerchant;
							$this->payment_password = $r->formPaymentPassword;
							$this->payment_currency = $r->formPaymentCurrency;
							$this->payment_cost = $r->formPaymentCost;
							$this->payment_amount = $r->formPaymentAmount;
							$this->payment_tax = $r->formPaymentTax;
							$this->payment_callback = $r->formPaymentCallback;
							$strFormCreated = $r->formCreated;
						}

						$this->name = $this->get_post_info(array('select' => "post_title"));
						$this->url = $this->get_post_info();
						$this->deadline = $this->meta(array('action' => 'get', 'key' => 'deadline'));
					}

					else
					{
						$error_text = __("I could not find the form you were looking for. If the problem persists, please contact an admin", 'lang_form');
					}
				}

				if(!isset($_POST['btnFormAdd']) && $this->form2type_id > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $this->form2type_id));

					if($wpdb->num_rows > 0)
					{
						foreach($result as $r)
						{
							$this->type_id = $r->formTypeID;
							$this->type_text = $r->formTypeText;
							$this->type_placeholder = $r->formTypePlaceholder;
							$this->check_id = $r->checkID;
							$this->type_tag = $r->formTypeTag;
							$this->type_class = $r->formTypeClass;
							$this->type_fetch_from = $r->formTypeFetchFrom;
							$this->type_connect_to = $r->formTypeConnectTo;
							$this->type_action_equals = $r->formTypeActionEquals;
							$this->type_action_show = $r->formTypeActionShow;

							switch($this->type_id)
							//switch($this->type_code)
							{
								case 2:
								//case 'range':
									list($this->type_text, $this->type_min, $this->type_max, $this->type_default) = explode("|", $this->type_text);
								break;

								case 10:
								//case 'select':
								case 11:
								//case 'select_multiple':
								case 16:
								//case 'checkbox_multiple':
								case 17:
								//case 'radio_multiple':
									if($this->form_option_exists)
									{
										$form2type_id_temp = $this->get_type_connect_to_root(array('connect_to' => $this->type_connect_to, 'field_id' => $this->form2type_id));

										$result = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionKey, formOptionValue, formOptionLimit, formOptionAction FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $form2type_id_temp));

										$this->arr_type_select_id = $this->arr_type_select_key = $this->arr_type_select_value = $this->arr_type_select_limit = $this->arr_type_select_action = array();

										foreach($result as $r)
										{
											$this->arr_type_select_id[] = $r->formOptionID;
											$this->arr_type_select_key[] = $r->formOptionKey;
											$this->arr_type_select_value[] = $r->formOptionValue;
											$this->arr_type_select_limit[] = $r->formOptionLimit;
											$this->arr_type_select_action[] = $r->formOptionAction;
										}
									}

									else
									{
										list($this->type_text, $strFormTypeSelect) = explode(":", $this->type_text);

										$this->arr_type_select_id = $this->arr_type_select_value = $this->arr_type_select_limit = array();

										foreach(explode(",", $strFormTypeSelect) as $option_temp)
										{
											list($option_id, $option_value, $option_limit) = explode("|", $option_temp);

											$this->arr_type_select_id[] = $option_id;
											$this->arr_type_select_value[] = $option_value;
											$this->arr_type_select_limit[] = $option_limit;
										}
									}
								break;
							}

							if(isset($_GET['btnFieldCopy']))
							{
								$this->form2type_id = "";

								$count_temp = count($this->arr_type_select_id);

								for($i = 0; $i < $count_temp; $i++)
								{
									$this->arr_type_select_id[$i] = '';
								}
							}
						}
					}

					else
					{
						do_log("No results from btnFormAdd (".$this->id.", ".$wpdb->last_query.")");
					}
				}
			break;

			default:
				if(isset($_GET['btnFormCopy']) && wp_verify_nonce($_REQUEST['_wpnonce_form_copy'], 'form_copy_'.$this->id))
				{
					if($this->clone_form(array('id' => $this->id)))
					{
						$done_text = __("The form was successfully copied", 'lang_form');
					}

					else
					{
						$error_text = __("Something went wrong. Contact your admin and add this URL as reference", 'lang_form');
					}
				}

				else if(isset($_POST['btnFormUpdate']))
				{
					$this->prefix = $this->get_post_info()."_";

					$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeCode, checkCode, formTypeRequired FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeID != '13' ORDER BY form2TypeOrder ASC", $this->id));

					foreach($result as $r)
					{
						$intForm2TypeID2 = $r->form2TypeID;
						$strFormTypeCode = $r->formTypeCode;
						$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";
						$intFormTypeRequired = $r->formTypeRequired;

						$strAnswerText = check_var($this->prefix.$intForm2TypeID2, $strCheckCode, true, '', true, 'post');

						if($strAnswerText != '')
						{
							$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $intForm2TypeID2));

							if($wpdb->num_rows > 0)
							{
								$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $this->answer_id, $intForm2TypeID2, $strAnswerText));

								if($wpdb->num_rows == 0)
								{
									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '%d'", $strAnswerText, $this->answer_id, $intForm2TypeID2));
								}
							}

							else
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s", $this->answer_id, $intForm2TypeID2, $strAnswerText));
							}
						}

						else if($strFormTypeCode == 'radio_button')
						{
							$strAnswerText_radio = (isset($_POST["radio_".$intForm2TypeID2]) ? check_var($_POST["radio_".$intForm2TypeID2], 'int', false) : '');

							if($strAnswerText_radio != '')
							{
								$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $strAnswerText_radio));

								if($wpdb->num_rows == 0)
								{
									$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = ''", $this->answer_id, $strAnswerText_radio));
								}
							}
						}

						else if($intFormTypeRequired == 0 && in_array($strFormTypeCode, array('range', 'input_field', 'textarea', 'text', 'datepicker')))
						{
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $this->answer_id, $intForm2TypeID2));
						}
					}

					if(!isset($error_text) || $error_text == '')
					{
						mf_redirect(admin_url("admin.php?page=mf_form/answer/index.php&intFormID=".$this->id));
					}
				}

				else if(isset($_GET['btnAnswerSpam']) && wp_verify_nonce($_REQUEST['_wpnonce_answer_spam'], 'answer_spam_'.$this->answer_id))
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2answer SET answerSpam = '1' WHERE answerID = '%d'", $this->answer_id));

					$done_text = __("I have marked the email as spam for you", 'lang_form');
				}

				else if(isset($_GET['btnAnswerApprove']) && wp_verify_nonce($_REQUEST['_wpnonce_answer_approve'], 'answer_approve_'.$this->answer_id))
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2answer SET answerSpam = '0' WHERE answerID = '%d'", $this->answer_id));

					$done_text = __("I have approved the answer for you", 'lang_form');
				}

				else if(isset($_GET['btnAnswerVerifyPayment']) && wp_verify_nonce($_REQUEST['_wpnonce_answer_verify_payment'], 'answer_verify_payment_'.$this->answer_id))
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT formPaymentAmount, formPaymentCallback FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $this->id));

					foreach($result as $r)
					{
						$intFormPaymentAmount = $r->formPaymentAmount;
						$strFormPaymentCallback = $r->formPaymentCallback;

						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText NOT LIKE %s", "116: ".__("Paid and Verified", 'lang_form'), $this->answer_id, '116:%'));

						if($wpdb->rows_affected > 0 && $intFormPaymentAmount > 0 && $strFormPaymentCallback != '')
						{
							$paid = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $this->answer_id, $intFormPaymentAmount));

							call_user_func($strFormPaymentCallback, array('paid' => $paid, 'answer_id' => $this->answer_id));

							$this->set_meta(array('id' => $this->answer_id, 'key' => 'payment_verified_by', 'value' => get_current_user_id()));

							$done_text = __("I have verified the payment for you", 'lang_form');
						}

						else
						{
							$error_text = __("I could not verify the payment for you", 'lang_form');
						}
					}
				}

				else if(isset($_GET['btnMessageResend']) && wp_verify_nonce($_REQUEST['_wpnonce_message_resend'], 'message_resend_'.$this->answer_id))
				{
					$resultAnswerEmail = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerType FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerSent = '0' AND answerType != ''", $this->answer_id));

					if($wpdb->num_rows > 0)
					{
						$this->form_name = $this->get_post_info(array('select' => "post_title"));
						$this->prefix = $this->get_post_info()."_";

						$this->answer_data = array();

						$this->arr_email_content = array(
							'fields' => array(),
						);

						$result = $wpdb->get_results($wpdb->prepare("SELECT ".$wpdb->base_prefix."form2type.form2TypeID, formTypeCode, formTypeText, checkCode, answerText FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) INNER JOIN ".$wpdb->base_prefix."form_answer ON ".$wpdb->base_prefix."form2type.form2TypeID = ".$wpdb->base_prefix."form_answer.form2TypeID WHERE answerID = '%d' ORDER BY form2TypeOrder ASC", $this->answer_id));

						foreach($result as $r)
						{
							$intForm2TypeID2 = $r->form2TypeID;
							$strFormTypeCode = $r->formTypeCode;
							$this->label = $r->formTypeText;
							$strCheckCode = $r->checkCode;
							$strAnswerText = $r->answerText;

							switch($strFormTypeCode)
							{
								case 'checkbox':
									$strAnswerText = "x";
								break;

								case 'range':
									$this->parse_range_label();
								break;

								case 'datepicker':
									$strAnswerText = format_date($strAnswerText);
								break;

								case 'radio_button':
									$strAnswerText = "x";
								break;

								case 'select':
								case 'radio_multiple':
									$strAnswerText = $this->parse_select_info($strAnswerText);
								break;

								case 'select_multiple':
								case 'checkbox_multiple':
									$strAnswerText = $this->parse_multiple_info($strAnswerText, true);
								break;

								default:
									switch($strCheckCode)
									{
										case 'zip':
											if(get_bloginfo('language') == "sv-SE")
											{
												include_once("class_zipcode.php");
												$obj_zipcode = new mf_zipcode();

												$city_name = $obj_zipcode->get_city($strAnswerText);

												if($city_name != '')
												{
													$strAnswerText .= ", ".$city_name;
												}
											}
										break;
									}
								break;
							}

							$this->arr_email_content['fields'][$intForm2TypeID2] = array(
								'type' => $strFormTypeCode,
								'label' => $this->label,
								'value' => $strAnswerText,
							);

							/*if($this->label != '')
							{
								$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;
							}

							if($strAnswerText != '')
							{
								$this->arr_email_content['fields'][$intForm2TypeID2]['value'] = $strAnswerText;
							}*/

							switch($strFormTypeCode)
							{
								case 'input_field':
									switch($strCheckCode)
									{
										case 'address':
										case 'city':
										case 'country':
										case 'email':
										case 'name':
										case 'telno':
										case 'zip':
											$this->answer_data[$strCheckCode] = $strAnswerText;
										break;
									}
								break;
							}
						}

						foreach($resultAnswerEmail as $r)
						{
							$strAnswerEmail = $r->answerEmail;
							$strAnswerType = $r->answerType;

							switch($strAnswerType)
							{
								/*case 'link_yes':	break;
								case 'link_no':		break;*/

								case 'replace_link':
									$this->send_to = $strAnswerEmail;
								break;

								/*case 'notify':	break;
								case 'confirm':		break;*/

								case 'product':
									$email_content_temp = apply_filters('filter_form_on_submit', array('obj_form' => $this));

									if(isset($email_content_temp['arr_mail_content']) && count($email_content_temp['arr_mail_content']) > 0)
									{
										$this->arr_email_content = $email_content_temp['arr_mail_content'];
									}
								break;
							}
						}

						$this->process_transactional_emails();
					}

					$done_text = __("I have resent the messages for you", 'lang_form');
				}

				else if(isset($_GET['btnFormExport']))
				{
					new mf_form_export();
				}

				else if(isset($_GET['btnFormAnswerExport']))
				{
					new mf_form_answer_export();
				}
			break;
		}

		return $out;
	}

	function get_amount($data = array())
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->posts." WHERE post_type = %s", $this->post_type));
	}

	function is_poll()
	{
		global $wpdb;

		if(!isset($data['id'])){	$data['id'] = $this->id;}

		/*if(!isset($this->accept_duplicates))
		{
			$this->accept_duplicates = $wpdb->get_var($wpdb->prepare("SELECT formAcceptDuplicates FROM ".$wpdb->base_prefix."form WHERE formID = '%d' LIMIT 0, 1", $data['id']));
		}*/

		$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' AND formTypeID IN('1', '8', '10', '11', '16', '17') LIMIT 0, 1", $data['id']));
		$rows_poll_fields = $wpdb->num_rows;

		$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' AND formTypeID IN('2', '3', '4', '7', '9', '12', '15') LIMIT 0, 1", $data['id']));
		$rows_input_fields = $wpdb->num_rows;

		//return ($this->accept_duplicates == 'no' || ($rows_poll_fields > 0 && $rows_input_fields == 0));
		return ($rows_poll_fields > 0 && $rows_input_fields == 0);
	}

	function is_duplicate()
	{
		global $wpdb;

		if(!isset($this->accept_duplicates))
		{
			$this->accept_duplicates = $wpdb->get_var($wpdb->prepare("SELECT formAcceptDuplicates FROM ".$wpdb->base_prefix."form WHERE formID = '%d' LIMIT 0, 1", $this->id));
		}

		if($this->accept_duplicates == 'no' || $this->is_poll())
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d' AND (answerIP = %s OR answerIP = %s OR answerIP = %s) LIMIT 0, 1", $this->id, get_current_visitor_ip(), md5(get_current_visitor_ip()), md5((defined('NONCE_SALT') ? NONCE_SALT : '').get_current_visitor_ip())));

			if($wpdb->num_rows > 0)
			{
				return true;
			}

			else if(is_user_logged_in())
			{
				$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer_meta USING (answerID) WHERE formID = '%d' AND metaKey = %s AND metaValue = %s LIMIT 0, 1", $this->id, 'user_id', get_current_user_id()));

				if($wpdb->num_rows > 0)
				{
					return true;
				}
			}
		}

		return false;
	}

	function is_correct_form($data)
	{
		if(isset($data['send_to']) && $data['send_to'] != '')
		{
			$email_encrypted = check_var('email_encrypted', 'char');

			if($email_encrypted != hash('sha512', $data['send_to']))
			{
				$log_text = shorten_text(array('string' => $email_encrypted, 'limit' => 10))." != ".shorten_text(array('string' => hash('sha512', $data['send_to']), 'limit' => 10))." (".$data['send_to'].(isset($_SERVER['HTTP_REFERER']) ? ", ".$_SERVER['HTTP_REFERER'] : "").", ".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].")";

				return false;
			}
		}

		return true;
	}

	function has_confirm_template()
	{
		global $wpdb;

		$query_where = "";

		if($this->id > 0)
		{
			$query_where .= " AND formID = '".$this->id."'";
		}

		$intFormID = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE blogID = '%d' AND (formEmailNotifyPage > 0 OR formEmailConfirmPage > 0)".$query_where, $wpdb->blogid));

		return ($intFormID > 0);
	}

	function check_if_has_payment()
	{
		global $wpdb;

		$this->has_payment = false;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formPaymentProvider, formPaymentCost, formPaymentAmount FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

		foreach($result as $r)
		{
			$this->payment_provider = $r->formPaymentProvider;
			$this->payment_cost = $r->formPaymentCost;
			$this->payment_amount = $r->formPaymentAmount;

			$this->has_payment = $this->payment_provider > 0 && ($this->payment_cost > 0 || $this->payment_amount > 0);
		}

		return $this->has_payment;
	}

	function get_form_status($data = array())
	{
		global $wpdb;

		if($this->post_status == '' || isset($data['form_id']) && $data['form_id'] != $this->id || isset($data['post_id']) && $data['post_id'] != $this->post_id)
		{
			if(isset($data['form_id']) && $data['form_id'] > 0){	$this->id = $data['form_id'];}
			if(isset($data['post_id']) && $data['post_id'] > 0){	$this->post_id = $data['post_id'];}

			if($this->id > 0)
			{
				$post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."form.postID = ".$wpdb->posts.".ID WHERE formID = '%d' AND formDeleted = '0'", $this->id));
			}

			else
			{
				$post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM ".$wpdb->posts." WHERE ID = '%d'", $this->post_id));
			}

			$this->post_status = $post_status;
		}

		return $this->post_status;
	}

	function get_for_select($data = array())
	{
		global $wpdb;

		if(!isset($data['local_only'])){		$data['local_only'] = false;}
		if(!isset($data['force_has_page'])){	$data['force_has_page'] = true;}
		if(!isset($data['is_payment'])){		$data['is_payment'] = false;}
		if(!isset($data['where'])){				$data['where'] = "";}

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_form')." --"
		);

		if($data['local_only'] == true)
		{
			$data['where'] .= " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)";
		}

		if($data['is_payment'] == true)
		{
			$data['where'] .= " AND formPaymentProvider > 0";
		}

		else
		{
			$data['where'] .= " AND (formPaymentProvider = 0 OR formPaymentProvider IS null OR formPaymentProvider = '')";
		}

		$result = $wpdb->get_results("SELECT formID, formName FROM ".$wpdb->base_prefix."form WHERE formDeleted = '0'".$data['where']." ORDER BY formCreated DESC");

		foreach($result as $r)
		{
			$intFormID = $r->formID;
			$strFormName = $r->formName;

			if($data['force_has_page'] == false || count(get_pages_from_shortcode("[mf_form id=".$intFormID."]")) > 0)
			{
				$arr_data[$intFormID] = $strFormName;
			}
		}

		return $arr_data;
	}

	function get_email_notify_from_for_select()
	{
		return array(
			'admin' => __("Admin", 'lang_form'),
			'visitor' => __("Visitor", 'lang_form'),
			'other' => __("Other", 'lang_form'),
		);
	}

	function get_form_name($id = 0)
	{
		if($id > 0)
		{
			$this->id = $id;
		}

		$form_name = $this->get_post_info(array('select' => 'post_title'));

		return ($form_name != '' ? $form_name: __("Unknown", 'lang_form')." (#".$id.")");
	}

	function get_form_id($id)
	{
		global $wpdb;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE postID = '%d'", $id));

		return $this->id;
	}

	function get_form_id_from_type($id)
	{
		global $wpdb;

		$this->form2type_id = $id;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $id));

		return $this->id;
	}

	function get_post_id($id = 0)
	{
		global $wpdb;

		if($id > 0){	$this->id = $id;}

		if(!($this->post_id > 0))
		{
			$this->post_id = $wpdb->get_var($wpdb->prepare("SELECT postID FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $this->id));
		}

		return $this->post_id;
	}

	function get_post_info($data = array())
	{
		global $wpdb;

		if(!isset($data['select'])){	$data['select'] = "post_name";}

		if(isset($data['form_id']) && $data['form_id'] > 0)
		{
			$this->id = $data['form_id'];
		}

		if($this->id > 0)
		{
			$post_name = $wpdb->get_var($wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."form.postID = ".$wpdb->posts.".ID WHERE formID = '%d' AND formDeleted = '0'", $this->id));
		}

		else
		{
			$post_name = $wpdb->get_var($wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->posts." WHERE ID = '%d'", $data['post_id']));
		}

		if($data['select'] == "post_name" && $post_name == '')
		{
			$post_name = "field";
		}

		return $post_name;
	}

	function pre_format_date($date)
	{
		if($date != '')
		{
			$date_formatted = date("Y-m-d", strtotime($date));

			if($date_formatted != $date && strlen($date_formatted) == 10 && $date_formatted > DEFAULT_DATE)
			{
				$date = $date_formatted;
			}
		}

		if(strlen($date) == 10)
		{
			$date = format_date($date);
		}

		return $date;
	}

	function get_form_id_from_post_content($post_id)
	{
		$post_content = mf_get_post_content($post_id);

		$form_id = get_match("/\[mf_form id=(.*?)\]/", $post_content, false);

		if($form_id > 0)
		{
			$this->id = $form_id;
		}
	}

	function get_form_id_from_answer($intAnswerID)
	{
		global $wpdb;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form2answer WHERE answerID = '%d'", $intAnswerID));
	}

	function get_form_email_field()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' AND checkID = '5'", $this->id));
	}

	function get_answer_email($intAnswerID)
	{
		global $wpdb;

		if(!($this->id > 0))
		{
			$this->get_form_id_from_answer($intAnswerID);
		}

		$intForm2TypeID = $this->get_form_email_field();

		return $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $intAnswerID, $intForm2TypeID));
	}

	function is_form_field_type_used($data)
	{
		global $wpdb;

		$query_join = $query_where = "";

		if(isset($data['query_type_id']))
		{
			if(is_array($data['query_type_id']))
			{
				$query_where .= " AND formTypeID IN ('".implode("','", $data['query_type_id'])."')";
			}

			else if($data['query_type_id'] > 0)
			{
				$query_where .= " AND formTypeID = '".$data['query_type_id']."'";
			}
		}

		if(isset($data['display']))
		{
			$query_where .= " AND formTypeDisplay = '".$data['display']."'";
		}

		if(isset($data['required']))
		{
			$query_where .= " AND formTypeRequired = '".$data['required']."'";
		}

		if(isset($data['autofocus']))
		{
			$query_where .= " AND formTypeAutofocus = '".$data['autofocus']."'";
		}

		if(isset($data['remember']))
		{
			$query_where .= " AND formTypeRemember = '".$data['remember']."'";
		}

		if(isset($data['check_code']) && $data['check_code'] != '')
		{
			$query_join .= " INNER JOIN ".$wpdb->base_prefix."form_check USING (checkID)";
			$query_where .= " AND checkCode = '".$data['check_code']."'";
		}

		$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type".$query_join." WHERE formID = '%d'".$query_where." LIMIT 0, 1", $this->id));

		return ($wpdb->num_rows > 0);
	}

	function set_meta($data)
	{
		global $wpdb;

		if(!isset($data['key'])){		$data['key'] = '';}
		if(!isset($data['value'])){		$data['value'] = '';}

		$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer_meta WHERE answerID = '%d' AND metaKey = %s", $data['id'], $data['key']));

		if($wpdb->num_rows > 0)
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer_meta SET metaValue = %s WHERE answerID = '%d' AND metaKey = %s", $data['value'], $data['id'], $data['key']));
		}

		else
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_meta SET answerID = '%d', metaKey = %s, metaValue = %s", $data['id'], $data['key'], $data['value']));
		}
	}

	function get_meta($data)
	{
		global $wpdb;

		if(!isset($data['meta_key'])){		$data['meta_key'] = '';}

		$query_where = '';

		if($data['meta_key'] != '')
		{
			$query_where .= " AND metaKey = '".esc_sql($data['meta_key'])."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT metaKey, metaValue FROM ".$wpdb->base_prefix."form_answer_meta WHERE answerID = '%d'".$query_where, $data['id']));

		if($data['meta_key'] != '')
		{
			foreach($result as $r)
			{
				if($data['meta_key'] == $r->metaKey)
				{
					return $r->metaValue;
				}
			}
		}

		else
		{
			return $result;
		}
	}

	function get_answer_amount($data)
	{
		global $wpdb;

		if(!isset($data['is_spam'])){		$data['is_spam'] = 0;}
		if(!isset($data['meta_key'])){		$data['meta_key'] = '';}
		if(!isset($data['meta_value'])){	$data['meta_value'] = '';}

		$query_join = $query_where = "";

		if($data['meta_key'] != '')
		{
			$query_join .= " INNER JOIN ".$wpdb->base_prefix."form_answer_meta USING (answerID)";
			$query_where .= " AND metaKey = '".esc_sql($data['meta_key'])."' AND metaValue = '".esc_sql($data['meta_value'])."'";
		}

		$wpdb->get_results($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID)".$query_join." WHERE formID = '%d' AND answerSpam = '%d'".$query_where." GROUP BY answerID", $data['form_id'], $data['is_spam']));

		return $wpdb->num_rows;
	}

	function get_type_name($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formTypeName FROM ".$wpdb->base_prefix."form_type WHERE formTypeID = '%d'", $id));
	}

	function get_form_type_info($data = array())
	{
		global $wpdb;

		if(!isset($data['form_id'])){			$data['form_id'] = 0;}
		if(!isset($data['query_type_id'])){		$data['query_type_id'] = array();}
		if(!isset($data['query_type_code'])){	$data['query_type_code'] = array();}
		if(!isset($data['query_exclude_id'])){	$data['query_exclude_id'] = 0;}

		if($data['form_id'] > 0)
		{
			$this->id = $data['form_id'];
		}

		$query_where = "";

		if(count($data['query_type_id']) > 0)
		{
			$query_where .= " AND formTypeID IN ('".implode("','", $data['query_type_id'])."')";
		}

		if(count($data['query_type_code']) > 0)
		{
			$query_where .= " AND formTypeCode IN ('".implode("','", $data['query_type_code'])."')";
		}

		if($data['query_exclude_id'] > 0)
		{
			$query_where .= " AND form2TypeID != '".$data['query_exclude_id']."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText, form2TypeOrder FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d'".$query_where." ORDER BY form2TypeOrder ASC", $this->id));
		$rows = $wpdb->num_rows;

		return array($result, $rows);
	}

	function preg_replace_label($matches)
	{
		$intForm2TypeID = $matches[1];

		if(isset($this->page_content_data['content']['fields'][$intForm2TypeID]['value']))
		{
			return $this->page_content_data['content']['fields'][$intForm2TypeID]['label'];
		}

		else
		{
			do_log("Field did not exist when checking in preg_replace_label(): ".var_export($matches, true)." -> ".$intForm2TypeID." -> ".var_export($this->page_content_data['content']['fields'], true));
		}
	}

	function preg_replace_answer($matches)
	{
		$intForm2TypeID = $matches[1];

		if(isset($this->page_content_data['content']['fields'][$intForm2TypeID]['value']))
		{
			return $this->page_content_data['content']['fields'][$intForm2TypeID]['value'];
		}

		else
		{
			do_log("Field did not exist when checking in preg_replace_answer(): ".var_export($matches, true)." -> ".$intForm2TypeID." -> ".var_export($this->page_content_data['content']['fields'], true));
		}
	}

	function render_mail_subject()
	{
		$arr_shortcodes = $arr_values = array();

		$arr_shortcodes[] = "[answer_id]";		$arr_values[] = $this->answer_id;

		$this->mail_data['subject'] = str_replace($arr_shortcodes, $arr_values, $this->mail_data['subject']);
		$this->mail_data['subject'] = preg_replace_callback("/\[label_(.*?)\]/", array($this, 'preg_replace_label'), $this->mail_data['subject']);
		$this->mail_data['subject'] = preg_replace_callback("/\[answer_(.*?)\]/", array($this, 'preg_replace_answer'), $this->mail_data['subject']);
	}

	function render_mail_content($data = array())
	{
		if(!isset($data['template'])){	$data['template'] = false;}

		$out_fields = $out_doc_types = $out_products = $intProductID = $strProductName = "";

		foreach($this->page_content_data['content'] as $key => $arr_types)
		{
			switch($key)
			{
				case 'fields':
					foreach($arr_types as $key => $arr_value)
					{
						switch($arr_value['type'])
						{
							case 'hidden_field':
								// Do not display in e-mail
							break;

							case 'text':
								$out_fields .= "<p>".$arr_value['label']."</p>";
							break;

							case 'space':
								$out_fields .= "<p>&nbsp;</p>";
							break;

							default:
								$out_fields .= "<p>- ".$arr_value['label'];

								if(isset($arr_value['value']) && $arr_value['value'] != '')
								{
									if(substr($arr_value['label'], -1) != ":")
									{
										$out_fields .= ":";
									}

									$out_fields .= " <strong>"
										.$arr_value['value'];

										if(isset($arr_value['xtra']))
										{
											$out_fields .= $arr_value['xtra'];
										}

									$out_fields .= "</strong>";
								}

								$out_fields .= "</p>";
							break;
						}
					}
				break;

				case 'doc_types':
					foreach($arr_types as $key => $arr_value)
					{
						$out_doc_types .= "<p>"
							."- ".$arr_value['label'];

							if(substr($arr_value['label'], -1) != ":")
							{
								$out_doc_types .= ":";
							}

							if($arr_value['value'] != '')
							{
								$out_doc_types .= " ".$arr_value['value'];
							}

						$out_doc_types .= "</p>";
					}
				break;

				case 'products':
					foreach($arr_types as $product)
					{
						if($product['value'] != '')
						{
							$out_products .= "<p>- ".$product['value']."</p>";

							$intProductID = $product['id'];
							$strProductName = $product['value'];
						}
					}
				break;
			}
		}

		if($data['template'] == false)
		{
			$out = "";

			if($out_fields != '')
			{
				$out .= "<br>".$out_fields;
			}

			if($out_doc_types != '')
			{
				$out .= "<br>".$out_doc_types;
			}

			if($out_products != '')
			{
				$out .= "<br>".$out_products;
			}
		}

		else
		{
			$link_base_url = get_form_url($this->id)."?btnVar"
				."&answer_email=".$data['mail_to']
				."&answer_id=".$this->answer_id
				."&product_id=".$intProductID
				."&hash=".md5((defined('NONCE_SALT') ? NONCE_SALT : '')."_".$this->answer_id."_".$intProductID);

			$arr_exclude = array(
				"[answer_id]",
				"[form_fields]",
				"[doc_types]",
				"[products]",
				"[product]",
				"[link_yes]",
				"[link_no]",
			);

			$arr_include = array(
				$this->answer_id,
				$out_fields,
				$out_doc_types,
				$out_products,
				$strProductName,
				str_replace("btnVar", "btnFormLinkYes", $link_base_url),
				str_replace("btnVar", "btnFormLinkNo", $link_base_url),
			);

			$out = str_replace($arr_exclude, $arr_include, $data['template']);
		}

		return $out;
	}

	function get_page_content_for_email()
	{
		global $wpdb;

		if(!isset($this->page_content_data['page_id'])){	$this->page_content_data['page_id'] = 0;}

		$mail_content = "";

		if($this->page_content_data['page_id'] > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_content FROM ".$wpdb->posts." WHERE ID = '%d'", $this->page_content_data['page_id']));

			foreach($result as $r)
			{
				$this->mail_data['subject'] = $r->post_title;
				$mail_template = apply_filters('the_content', $r->post_content);

				$mail_content = $this->render_mail_content(array('mail_to' => $this->page_content_data['mail_to'], 'template' => $mail_template));
			}
		}

		if($this->mail_data['subject'] != '')
		{
			$this->render_mail_subject();
		}

		if($mail_content == '')
		{
			$mail_content = $this->render_mail_content();
		}

		return $mail_content;
	}

	function has_email_field()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(formTypeID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_check USING (checkID) WHERE formID = '%d' AND formTypeID = '3' AND checkCode = 'email'", $this->id));
	}

	function get_email_fields_for_select()
	{
		global $wpdb;

		$arr_data = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeText FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_check USING (checkID) WHERE formID = '%d' AND formTypeID = '3' AND checkCode = 'email'", $this->id));

		foreach($result as $r)
		{
			$arr_data[$r->form2TypeID] = $r->formTypeText;
		}

		return $arr_data;
	}

	function get_form_type_for_select($data)
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}

		$arr_data = array();

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";
		}

		foreach($data['result'] as $r)
		{
			if(in_array($r->formTypeID, array(10, 11, 16, 17)))
			//if(in_array($r->formTypeCode, array('select', 'select_multiple', 'checkbox_multiple', 'radio_multiple')))
			{
				@list($strFormTypeText, $str_select) = explode(":", $r->formTypeText);
			}

			else if(in_array($r->formTypeID, array(13)))
			//else if(in_array($r->formTypeCode, array('custom_tag')))
			{
				$strFormTypeText = "(".__("Custom Tag", 'lang_form').")";
			}

			else
			{
				$strFormTypeText = $r->formTypeText;
			}

			$arr_data[$r->form2TypeID] = $strFormTypeText;
		}

		return $arr_data;
	}

	function get_form_type_result()
	{
		global $wpdb;

		if($this->form2type_id > 0)
		{
			$query_where = "form2typeID = '%d'";
			$query_where_id = $this->form2type_id;
		}

		else
		{
			$query_where = "formID = '%d'";
			$query_where_id = $this->id;
		}

		return $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, checkCode, checkPattern, formTypeText, formTypePlaceholder, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeRemember, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE ".$query_where." GROUP BY ".$wpdb->base_prefix."form2type.form2TypeID ORDER BY form2TypeOrder ASC", $query_where_id));
	}

	function process_link_yes_no()
	{
		global $error_text;
		$out = "";

		$intAnswerID = check_var('answer_id', 'int');
		$intProductID = check_var('product_id', 'int');
		$strAnswerEmail = check_var('answer_email');
		$hash = check_var('hash');

		if($hash == md5((defined('NONCE_SALT') ? NONCE_SALT : '')."_".$intAnswerID."_".$intProductID))
		{
			if($intProductID > 0)
			{
				$obj_webshop = new mf_webshop();

				$mail_from_name = $obj_webshop->get_product_name(array('id' => $intProductID));
			}

			else
			{
				$mail_from_name = get_bloginfo('name');
			}

			$mail_from = $strAnswerEmail;
			$mail_to = $this->get_answer_email($intAnswerID);
			$mail_subject = $this->get_form_name();
			$mail_content = (isset($_GET['btnFormLinkYes']) ? get_option('setting_link_yes_text') : get_option('setting_link_no_text'));

			if($mail_content != '')
			{
				$mail_content = nl2br(str_replace("[product]", $mail_from_name, $mail_content));

				$this->mail_data = array(
					'headers' => "From: ".$mail_from_name." <".$mail_from.">\r\n",
					'to' => $mail_to,
					'subject' => $mail_subject,
					'content' => $mail_content,
					'type' => (isset($_GET['btnFormLinkYes']) ? "link_yes" : "link_no"),
				);

				$sent = $this->send_transactional_email();

				if($sent)
				{
					$setting_link_thanks_text = nl2br(get_option_or_default('setting_link_thanks_text', __("The message has been sent!", 'lang_form')));

					$out .= "<p>".$setting_link_thanks_text."</p>
					<p class='grey'>".$mail_content."</p>";
				}

				else
				{
					$out .= "<p>".__("I am sorry, but I could not send the message that was requested")."</p>";
				}
			}

			else
			{
				if(isset($_GET['btnFormLinkYes']))
				{
					$error_text = sprintf(__("There was no content to send. You have to enter text into the field Text to send as positive response in %sMy Settings%s", 'lang_form'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_form")."'>", "</a>");
				}

				else
				{
					$error_text = sprintf(__("There was no content to send. You have to enter text into the field Text to send as negative response in %sMy Settings%s", 'lang_form'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_form")."'>", "</a>");
				}
			}
		}

		else
		{
			$error_text = __("Oops! You do not seam to have the correct link or it has expired", 'lang_form');
		}

		return $out;
	}

	function check_if_spam($data)
	{
		if($this->is_spam == false && $data['text'] != '' && $data['rule'] != '')
		{
			if(function_exists($data['rule']))
			{
				$string_decoded = htmlspecialchars_decode($data['text']);

				if($data['text'] != strip_tags($data['text']) || $string_decoded != strip_tags($string_decoded))
				{
					$this->is_spam = true;
					$this->is_spam_id = $data['id'];
				}
			}

			else
			{
				$arr_exclude = array("[qm]", "[bs]");
				$arr_include = array("\?", "\\");

				$reg_exp = str_replace($arr_exclude, $arr_include, $data['rule']);

				if($data['text'] != '')
				{
					if(preg_match($reg_exp, $data['text']) || esc_sql($data['text']) != '' && preg_match($reg_exp, esc_sql($data['text'])))
					{
						$this->is_spam = true;
						$this->is_spam_id = $data['id'];
					}
				}
			}
		}
	}

	function contains_urls($string)
	{
		return preg_match('/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,6}/', $string);
	}

	function contains_emails($string)
	{
		return preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}/', $string);
	}

	function contains_page_title($string)
	{
		if(class_exists('mf_theme_core'))
		{
			global $obj_theme_core;

			if(!isset($obj_theme_core))
			{
				$obj_theme_core = new mf_theme_core();
			}

			if($obj_theme_core->is_theme_active())
			{
				return preg_match("/(".$obj_theme_core->get_wp_title().")/", $string);
			}
		}

		return false;

		/*$string = str_replace(array(" ", "(", ")", "-", ".", ","), "", $string);

		return preg_match('/\d{8,}/', $string); // (^(([\+]\d{1,3})?[ \.-]?[\(]?\d{3}[\)]?)?[ \.-]?\d{3}[ \.-]?\d{4}$) or ([0-9]{3}\s*\([0-9]{3}\)\s*[0-9]{4}) or (\[[0-9]{2}\.[0-9]{1},[0-9]{2}-[0-9]{1}-[0-9]{2}\.[0-9]{2}\])*/
	}

	function contains_phone_numbers($string)
	{
		$string = str_replace(array(" ", "(", ")", "-", ".", ","), "", $string);

		return preg_match('/\d{8,}/', $string); // (^(([\+]\d{1,3})?[ \.-]?[\(]?\d{3}[\)]?)?[ \.-]?\d{3}[ \.-]?\d{4}$) or ([0-9]{3}\s*\([0-9]{3}\)\s*[0-9]{4}) or (\[[0-9]{2}\.[0-9]{1},[0-9]{2}-[0-9]{1}-[0-9]{2}\.[0-9]{2}\])
	}

	function get_spam_rules($data = array())
	{
		global $post;

		if(!isset($data['id'])){		$data['id'] = 0;}
		if(!isset($data['exclude'])){	$data['exclude'] = '';}
		if(!isset($data['type'])){		$data['type'] = '';}

		$arr_data = array(
			1 => array('exclude' => 'select_multiple',	'text' => 'contains_html',					'explain' => sprintf(__("Contains %s", 'lang_form'), "HTML")),
			2 => array('exclude' => 'referer_url',		'text' => "/(http|https|ftp|ftps)\:/i",		'explain' => sprintf(__("Link including %s", 'lang_form'), "http")),
			3 => array('exclude' => '',					'text' => "/([qm]){5}/",					'explain' => __("Question Marks", 'lang_form')),
			4 => array('exclude' => '',					'text' => "/(bit\.ly)/",					'explain' => __("Shortening Links", 'lang_form')),
			5 => array('exclude' => '',					'text' => "/([bs][url[bs]=)/",				'explain' => __("URL Shortcodes", 'lang_form')),
			6 => array('exclude' => '',					'text' => "",								'explain' => __("Recurring E-mail", 'lang_form')),
			7 => array('exclude' => '',					'text' => "",								'explain' => __("Honeypot", 'lang_form')),
		);

		if($data['type'] == 'explain')
		{
			$arr_data[8] = array('exclude' => '',		'text' => array($this, 'contains_urls'),			'explain' => __("Contains URLs", 'lang_form'));
			$arr_data[9] = array('exclude' => '',		'text' => array($this, 'contains_emails'),			'explain' => __("Contains E-mails", 'lang_form'));
			$arr_data[10] = array('exclude' => '',		'text' => array($this, 'contains_page_title'),		'explain' => __("Contains Page Title", 'lang_form'));
			$arr_data[11] = array('exclude' => '',		'text' => array($this, 'contains_phone_numbers'),	'explain' => __("Contains Phone Numbers", 'lang_form'));
		}

		if($data['exclude'] != '')
		{
			foreach($arr_data as $key => $value)
			{
				if($value['exclude'] == $data['exclude'])
				{
					$arr_data[$key] = array();
					unset($arr_data[$key]);
				}
			}
		}

		if($data['type'] != '')
		{
			foreach($arr_data as $key => $value)
			{
				$arr_data[$key] = $value[$data['type']];
			}
		}

		if($data['id'] > 0)
		{
			$arr_data = $arr_data[$data['id']];
		}

		return $arr_data;
	}

	function check_spam_rules($data)
	{
		$result = $this->get_spam_rules(array('type' => 'text', 'exclude' => $data['code']));

		foreach($result as $key => $value)
		{
			$intSpamID = $key;
			$strSpamText = $value;

			if($strSpamText != '')
			{
				if(is_array($data['text']))
				{
					foreach($data['text'] as $text)
					{
						$this->check_if_spam(array('id' => $intSpamID, 'rule' => $strSpamText, 'text' => $text));
					}
				}

				else
				{
					$this->check_if_spam(array('id' => $intSpamID, 'rule' => $strSpamText, 'text' => $data['text']));
				}

				if($this->is_spam == true)
				{
					break;
				}
			}
		}
	}

	function check_spam_email($data)
	{
		global $wpdb;

		if($this->is_spam == false)
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE answerSpam = '1' AND answerText = %s LIMIT 0, 1", $data['text']));

			if($wpdb->num_rows > 0)
			{
				$this->is_spam = true;
				$this->is_spam_id = 6;
			}
		}
	}

	function get_email_confirm_id()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formEmailConfirmID FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
	}

	function insert_answer()
	{
		global $wpdb;

		$out = true;

		if($this->answer_id > 0)
		{
			foreach($this->arr_answer_queries as $query)
			{
				$wpdb->query(str_replace("[answer_id]", $this->answer_id, $query));

				if($wpdb->rows_affected == 0)
				{
					$out = false;
				}
			}
		}

		else
		{
			$out = false;
		}

		return $out;
	}

	function get_option_key_from_id($id)
	{
		global $wpdb;

		$key = false;

		if($this->form_option_exists)
		{
			$key = $wpdb->get_var($wpdb->prepare("SELECT formOptionKey FROM ".$wpdb->base_prefix."form_option WHERE formOptionID = '%d'", $id));
		}

		return $key;
	}

	function get_option_id_from_key($key)
	{
		global $wpdb;

		$id = false;

		if($this->form_option_exists)
		{
			$id = $wpdb->get_var($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->base_prefix."form_option WHERE formOptionKey = '%d'", $key));
		}

		return $id;
	}

	function process_transactional_emails()
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmFromEmail, formEmailConfirmFromEmailName, formEmailConfirmID, formEmailConfirmPage, formEmail, formFromName, formEmailConditions, formEmailNotify, formEmailNotifyFromEmail, formEmailNotifyFromEmailName, formEmailNotifyFrom, formEmailNotifyPage, formEmailName FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

		foreach($result as $r)
		{
			$this->email_confirm = $r->formEmailConfirm;
			$this->email_confirm_from_email = $r->formEmailConfirmFromEmail;
			$this->email_confirm_from_email_name = $r->formEmailConfirmFromEmailName;
			$this->email_confirm_id = $r->formEmailConfirmID;
			$this->email_confirm_page = $r->formEmailConfirmPage;
			$this->email_admin = $r->formEmail;
			$this->email_admin_name = $r->formFromName;
			$this->email_conditions = $r->formEmailConditions;
			$this->email_notify = $r->formEmailNotify;
			$this->email_notify_from_email = $r->formEmailNotifyFromEmail;
			$this->email_notify_from_email_name = $r->formEmailNotifyFromEmailName;
			$this->email_notify_from = $r->formEmailNotifyFrom;
			$this->email_notify_page = $r->formEmailNotifyPage;
			$this->email_subject = ($r->formEmailName != '' ? $r->formEmailName : $this->form_name);
		}

		$this->page_content_data = array(
			'subject' => $this->email_subject,
			'content' => $this->arr_email_content,
		);

		if($this->email_conditions != '')
		{
			foreach(explode("\n", $this->email_conditions) as $str_condition)
			{
				@list($field_id, $option_id, $email) = explode("|", $str_condition, 3);

				if($field_id != '' && $option_id != '' && $email != '')
				{
					if(substr($field_id, 0, strlen($this->prefix)) == $this->prefix)
					{
						$field_id = str_replace($this->prefix, "", $field_id);
					}

					if(isset($_REQUEST[$this->prefix.$field_id]) && check_var($this->prefix.$field_id) == $option_id)
					{
						$this->email_admin = $email;

						break;
					}
				}

				else
				{
					do_log("Condition was not correctly formated (FormID: ".$this->id." -> ".$str_condition.")");
				}
			}
		}

		$email_from_visitor_address = $email_from_visitor = $email_from_admin_address = $email_from_admin = $email_from_other_address = $email_from_other = "";

		// From Visitor
		###################
		if(isset($this->answer_data['email']) && $this->answer_data['email'] != '')
		{
			if(isset($this->answer_data['name']) && $this->answer_data['name'] != '')
			{
				$name_temp = $this->answer_data['name'];
			}

			else
			{
				$name_temp = $this->answer_data['email'];
			}

			$email_from_visitor_address = $this->answer_data['email'];
			$email_from_visitor = "From: ".$name_temp." <".$this->answer_data['email'].">\r\n";
		}
		###################

		// From admin
		###################
		if($this->email_admin != '')
		{
			if(strpos($this->email_admin, "<"))
			{
				$email_from_admin_address = $this->email_admin;
				$email_from_admin = "From: ".$this->email_admin."\r\n";
			}

			else if(strpos($this->email_admin, ","))
			{
				$arr_email_admin = explode(",", $this->email_admin);
				$email_admin = trim($arr_email_admin[0]);

				$email_from_admin_address = $email_admin;
				$email_from_admin = "From: ".$email_admin." <".$email_admin.">\r\n";
			}

			else
			{
				$email_from_admin_address = $this->email_admin;
				$email_from_admin = "From: ".($this->email_admin_name != '' ? $this->email_admin_name : $this->email_admin)." <".$this->email_admin.">\r\n";
			}
		}
		###################

		// From other
		###################
		if($this->email_notify_from_email != '')
		{
			$email_from_other_address = $this->email_notify_from_email;
			$email_from_other = "From: ".($this->email_notify_from_email_name != '' ? $this->email_notify_from_email_name : $this->email_notify_from_email)." <".$this->email_notify_from_email.">\r\n";
		}
		###################

		if(isset($this->send_to) && $this->send_to != '')
		{
			$this->mail_data = array(
				'type' => 'replace_link',
				'to' => $this->send_to,
				'subject' => $this->page_content_data['subject'],
				'content' => '',
			);

			if($email_from_visitor != '')
			{
				$this->mail_data['from'] = $email_from_visitor_address;
				$this->mail_data['headers'] = $email_from_visitor;
			}

			$this->mail_data['content'] = $this->get_page_content_for_email();

			$this->send_transactional_email();
		}

		if($this->email_notify == 1)
		{
			$this->mail_data = array(
				'type' => 'notify',
				'subject' => $this->page_content_data['subject'],
				'content' => '',
			);

			if($this->email_admin != '')
			{
				if(strpos($this->email_admin, "<"))
				{
					$this->mail_data['to'] = get_match("/\<(.*)\>/", $this->email_admin);
				}

				else
				{
					$this->mail_data['to'] = $this->email_admin;
				}
			}

			else
			{
				$this->mail_data['to'] = get_bloginfo('admin_email');
			}

			if(isset($this->answer_data['email']) && $this->answer_data['email'] != '')
			{
				switch($this->email_notify_from)
				{
					default:
					case 'visitor':
						$this->mail_data['from'] = $email_from_visitor_address;
						$this->mail_data['headers'] = $email_from_visitor;
					break;

					case 'admin':
						$this->mail_data['from'] = $email_from_admin_address;
						$this->mail_data['headers'] = $email_from_admin;
					break;

					case 'other':
						$this->mail_data['from'] = ($email_from_other_address != '' ? $email_from_other_address : $email_from_admin_address);
						$this->mail_data['headers'] = ($email_from_other != '' ? $email_from_other : $email_from_admin);
					break;
				}
			}

			$this->page_content_data['mail_to'] = $this->mail_data['to'];
			$this->page_content_data['page_id'] = $this->email_notify_page;

			$this->mail_data['content'] = $this->get_page_content_for_email();

			$this->send_transactional_email();
		}

		if($this->email_confirm == 1 && isset($this->answer_data['email']) && $this->answer_data['email'] != '')
		{
			$this->mail_data = array(
				'type' => 'confirm',
				'to' => $this->answer_data['email'],
				'subject' => $this->page_content_data['subject'],
				'content' => '',
			);

			if($this->email_confirm_from_email != '')
			{
				$this->mail_data['from'] = $this->email_notify_from_email;
				$this->mail_data['headers'] = "From: ".($this->email_notify_from_email_name != '' ? $this->email_notify_from_email_name : $this->email_notify_from_email)." <".$this->email_notify_from_email.">\r\n";
			}

			else if($email_from_admin != '')
			{
				$this->mail_data['from'] = $email_from_admin_address;
				$this->mail_data['headers'] = $email_from_admin;
			}

			$this->page_content_data['mail_to'] = $this->mail_data['to'];
			$this->page_content_data['page_id'] = $this->email_confirm_page;

			$this->mail_data['content'] = $this->get_page_content_for_email();

			$this->send_transactional_email();
		}
	}

	function send_transactional_email()
	{
		global $wpdb;

		if(!isset($this->is_spam) || $this->is_spam == false)
		{
			$sent = send_email($this->mail_data);
		}

		else
		{
			$sent = false;
		}

		if(isset($this->answer_id) && $this->answer_id > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerEmail = %s AND answerType = %s LIMIT 0, 1", $this->answer_id, $this->mail_data['to'], $this->mail_data['type']));

			if($wpdb->num_rows > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer_email SET answerSent = '%d' WHERE answerID = '%d' AND answerEmail = %s AND answerType = %s", $sent, $this->answer_id, $this->mail_data['to'], $this->mail_data['type']));
			}

			else
			{
				if(!isset($this->mail_data['from']) || $this->mail_data['from'] == '')
				{
					$this->mail_data['from'] = get_bloginfo('admin_email');
				}

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_email SET answerID = '%d', answerEmailFrom = %s, answerEmail = %s, answerType = %s, answerSent = '%d'", $this->answer_id, $this->mail_data['from'], $this->mail_data['to'], $this->mail_data['type'], $sent));
			}
		}

		return $sent;
	}

	function check_limit($data)
	{
		global $wpdb, $error_text;

		$arr_data = array();

		@list($str_label, $str_select) = explode(":", $data['string']);
		$arr_options = explode(",", $str_select);

		foreach($arr_options as $str_option)
		{
			$arr_option = explode("|", $str_option);

			if($arr_option[0] == $data['value'])
			{
				if(isset($arr_option[2]) && $arr_option[2] > 0)
				{
					$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE formID = '%d' AND form2TypeID = '%d' AND answerText = %s AND answerSpam = '0' GROUP BY answerID", $this->id, $data['form2TypeID'], $arr_option[0]));
					$answer_rows = $wpdb->num_rows;

					if($answer_rows >= $arr_option[2])
					{
						$error_text = __("It is already full. Try with another alternative", 'lang_form');
					}
				}

				break;
			}
		}
	}

	function allow_save_ip()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formSaveIP FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
	}

	function get_mandatory_text()
	{
		global $wpdb;

		$out = $wpdb->get_var($wpdb->prepare("SELECT formMandatoryText FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

		if($out == '')
		{
			$out = __("Please, enter all required fields", 'lang_form');
		}

		return $out;
	}

	function get_field_name($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formTypeText FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $id));
	}

	function process_submit()
	{
		global $wpdb, $error_text, $done_text;

		$out = $error_text = "";
		$this->answer_data = $this->arr_answer_queries = array();

		$this->arr_email_content = array(
			'fields' => array(),
		);

		$setting_form_spam = get_option_or_default('setting_form_spam', array('email', 'filter', 'honeypot'));

		$this->form_name = $this->get_post_info(array('select' => "post_title"));
		$this->prefix = $this->get_post_info()."_";

		$dblQueryPaymentAmount_value = 0;

		if($this->is_duplicate())
		{
			$this->is_sent = true;
		}

		else
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeCode, formTypeText, checkCode, formTypeRequired FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeDisplay = '1' AND formTypeCode NOT IN('custom_tag', 'custom_tag_end') ORDER BY form2TypeOrder ASC", $this->id)); // AND formTypeResult = '1'

			foreach($result as $r)
			{
				$intForm2TypeID2 = $r->form2TypeID;
				$strFormTypeCode = $r->formTypeCode;
				$this->label = $r->formTypeText;
				$strCheckCode = $r->checkCode != '' ? $r->checkCode : 'char';
				$intFormTypeRequired = $r->formTypeRequired;

				if(!isset($this->arr_email_content['fields'][$intForm2TypeID2]))
				{
					$this->arr_email_content['fields'][$intForm2TypeID2] = array(
						'type' => $strFormTypeCode,
					);
				}

				$handle2fetch = $this->prefix.$intForm2TypeID2;

				$strAnswerText = $strAnswerText_send = check_var($handle2fetch, $strCheckCode, true, '', true, 'post');

				if($this->is_spam == false && $strAnswerText != '')
				{
					switch($strCheckCode)
					{
						case 'char':
							if($this->is_spam == false && in_array('filter', $setting_form_spam))
							{
								$this->check_spam_rules(array('code' => $strFormTypeCode, 'text' => $strAnswerText));
							}

							if($this->is_spam == false && $strFormTypeCode != 'hidden_field') // Hidden Fields can contain [user_email] which should be allowed
							{
								if(in_array('contains_emails', $setting_form_spam) && $this->contains_emails($strAnswerText))
								{
									$this->is_spam = true;
									$this->is_spam_id = 9;
								}

								else if(in_array('contains_urls', $setting_form_spam) && $this->contains_urls($strAnswerText))
								{
									$this->is_spam = true;
									$this->is_spam_id = 8;
								}
							}

							if($this->is_spam == false && in_array('contains_page_title', $setting_form_spam) && $this->contains_page_title($strAnswerText))
							{
								$this->is_spam = true;
								$this->is_spam_id = 10;
							}

							if($this->is_spam == false && in_array('contains_phone_numbers', $setting_form_spam) && $this->contains_phone_numbers($strAnswerText))
							{
								$this->is_spam = true;
								$this->is_spam_id = 11;
							}
						break;

						case 'email':
							if($this->is_spam == false && in_array('email', $setting_form_spam))
							{
								$this->check_spam_email(array('text' => $strAnswerText));
							}

							$intEmailConfirmID = $this->get_email_confirm_id();

							if($strFormTypeCode == 'input_field' && (!($intEmailConfirmID > 0) || $intEmailConfirmID == $intForm2TypeID2))
							{
								$this->answer_data['email'] = $strAnswerText;
							}
						break;

						case 'address':
						case 'city':
						case 'country':
						case 'name':
						case 'telno':
						case 'zip':
							if($strFormTypeCode == 'input_field')
							{
								$this->answer_data[$strCheckCode] = $strAnswerText;
							}
						break;
					}
				}

				switch($strFormTypeCode)
				{
					case 'checkbox':
						$strAnswerText_send = "x";
					break;

					case 'range':
						$this->parse_range_label();
					break;

					case 'datepicker':
						$strAnswerText_send = format_date($strAnswerText);
					break;

					case 'hidden_field':
						$strAnswerText_send = '';
					break;

					case 'select':
					case 'radio_multiple':
						$this->check_limit(array('string' => $this->label, 'value' => $strAnswerText, 'form2TypeID' => $intForm2TypeID2));

						$strAnswerText_send = $this->parse_select_info($strAnswerText);
					break;

					case 'select_multiple':
					case 'checkbox_multiple':
						$strAnswerText = "";

						if(isset($_POST[$handle2fetch]) && is_array($_POST[$handle2fetch]))
						{
							foreach($_POST[$handle2fetch] as $value)
							{
								$strAnswerText_temp = check_var($this->prefix.$value, $strCheckCode, false);

								$this->check_limit(array('string' => $this->label, 'value' => $strAnswerText_temp, 'form2TypeID' => $intForm2TypeID2));

								$strAnswerText .= ($strAnswerText != '' ? "," : "").$strAnswerText_temp;
							}
						}

						$strAnswerText_send = $this->parse_multiple_info($strAnswerText, true);
						$strAnswerText = $this->parse_multiple_info($strAnswerText, false);
					break;

					case 'file':
						if(isset($_FILES[$handle2fetch]))
						{
							$file_name = $_FILES[$handle2fetch]['name'];
							$file_location = $_FILES[$handle2fetch]['tmp_name'];
							$file_mime = $_FILES[$handle2fetch]['type'];

							if($file_name == '')
							{
								if($intFormTypeRequired == true)
								{
									$error_text = __("You have to submit a file", 'lang_form');
								}
							}

							else if(!is_uploaded_file($file_location))
							{
								if($intFormTypeRequired == true)
								{
									$error_text = __("The file was not uploaded", 'lang_form');
								}
							}

							else
							{
								$file_content = get_file_content(array('file' => $file_location));
								$file_mime = "";

								$intFileID = insert_attachment(array(
									'content' => $file_content,
									'mime' => $file_mime,
									'name' => $file_name,
								));

								if($intFileID > 0)
								{
									$strAnswerText = $intFileID;
								}
							}
						}
					break;

					default:
						switch($strCheckCode)
						{
							case 'zip':
								if(get_bloginfo('language') == "sv-SE")
								{
									include_once("class_zipcode.php");
									$obj_zipcode = new mf_zipcode();

									$city_name = $obj_zipcode->get_city($strAnswerText);

									if($city_name != '')
									{
										$this->arr_email_content['fields'][$intForm2TypeID2]['xtra'] = ", ".$city_name;
									}
								}
							break;
						}
					break;
				}

				$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;

				/*if($this->label != '')
				{
					switch($strFormTypeCode)
					{
						case 'hidden_field':
							unset($this->arr_email_content['fields'][$intForm2TypeID2]);
						break;

						default:
							$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;
						break;
					}
				}*/

				if($strAnswerText != '')
				{
					if($this->check_if_has_payment() && $this->payment_amount == $intForm2TypeID2)
					{
						$dblQueryPaymentAmount_value = $this->get_option_key_from_id($strAnswerText);
					}

					$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = %s", $intForm2TypeID2, $strAnswerText);

					if($strAnswerText_send != '')
					{
						$this->arr_email_content['fields'][$intForm2TypeID2]['value'] = $strAnswerText_send;
					}
				}

				else if($strFormTypeCode == 'radio_button')
				{
					$strAnswerText_radio = (isset($_POST["radio_".$intForm2TypeID2]) ? check_var($_POST["radio_".$intForm2TypeID2], 'int', false) : '');

					if($strAnswerText_radio != '')
					{
						$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = ''", $strAnswerText_radio);

						//$strFormTypeText_temp = $wpdb->get_var($wpdb->prepare("SELECT formTypeText FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $strAnswerText_radio)); // $this->get_field_name($strAnswerText_radio)

						if(!isset($this->arr_email_content['fields'][$strAnswerText_radio]))
						{
							$this->arr_email_content['fields'][$strAnswerText_radio] = array(
								'type' => $strFormTypeCode,
							);
						}

						$this->arr_email_content['fields'][$strAnswerText_radio]['value'] = "x";
					}
				}

				else if($intFormTypeRequired == true && !in_array($strFormTypeCode, array('text', 'space', 'referer_url')) && $error_text == '')
				{
					$error_text = $this->get_mandatory_text()." (".$this->label.")";
				}
			}
		}

		if($error_text == '' && $this->is_sent == false && count($this->arr_answer_queries) > 0)
		{
			$honeypot_check = check_var($this->prefix.'check');

			if(in_array('honeypot', $setting_form_spam) && $honeypot_check != '')
			{
				$this->is_spam = true;
				$this->is_spam_id = 7;

				// Honeypot should always be considered as spam if URL/Shortcode
				if(filter_var($honeypot_check, FILTER_VALIDATE_URL) === false || strpos($honeypot_check, "[/"))
				{
					//do_log("Honeypot value: ".$honeypot_check);
				}
			}

			if($this->allow_save_ip() == 'yes')
			{
				if(apply_filters('get_allow_cookies', true) == true)
				{
					$current_visitor_ip = get_current_visitor_ip();
				}

				else
				{
					$current_visitor_ip = md5((defined('NONCE_SALT') ? NONCE_SALT : '').get_current_visitor_ip());
				}
			}

			else
			{
				$current_visitor_ip = '';
			}

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2answer SET formID = '%d', answerIP = %s, answerSpam = '%d', spamID = '%d', answerCreated = NOW()", $this->id, $current_visitor_ip, $this->is_spam, $this->is_spam_id));
			$this->answer_id = $wpdb->insert_id;

			$email_content_temp = apply_filters('filter_form_on_submit', array('obj_form' => $this));

			if($error_text == '')
			{
				if(isset($email_content_temp['arr_mail_content']) && count($email_content_temp['arr_mail_content']) > 0)
				{
					$this->arr_email_content = $email_content_temp['arr_mail_content'];
				}

				if($this->insert_answer())
				{
					if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '')
					{
						$this->arr_email_content['fields'][] = array(
							'type' => 'http_referer',
							'label' => __("Sent From", 'lang_form'),
							'value' => remove_protocol(array('url' => $_SERVER['HTTP_REFERER'], 'clean' => true, 'trim' => true))
						);
					}

					$this->process_transactional_emails();

					if(is_user_logged_in())
					{
						$this->set_meta(array('id' => $this->answer_id, 'key' => 'user_id', 'value' => get_current_user_id()));
					}

					$this->set_meta(array('id' => $this->answer_id, 'key' => 'user_agent', 'value' => $_SERVER['HTTP_USER_AGENT']));

					if($this->check_if_has_payment())
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '0', answerText = %s", $this->answer_id, "101: ".__("Sent to processing", 'lang_form')));

						$test_payment = (isset($_POST[$this->prefix.'test_payment']) && (IS_ADMIN || isset($_GET['make_test_payment'])));

						if($test_payment == true)
						{
							$this->set_meta(array('id' => $this->answer_id, 'key' => 'test_payment', 'value' => get_current_user_id()));
						}

						$obj_payment = new mf_form_payment($this->id);
						$out .= $obj_payment->process_passthru(array('cost' => $this->payment_cost, 'amount' => $dblQueryPaymentAmount_value, 'orderid' => $this->answer_id, 'test' => $test_payment, 'answer_data' => $this->answer_data));
					}

					else
					{
						$this->is_sent = true;
					}
				}
			}
		}

		return $out;
	}

	function get_poll_results($data)
	{
		global $wpdb;

		$out = "";

		list($result, $rows) = $this->get_form_type_info(array('query_type_code' => array('text', 'radio_button', 'radio_multiple')));

		foreach($result as $r)
		{
			$intForm2TypeID = $r->form2TypeID;
			$intFormTypeID = $r->formTypeID;
			$strFormTypeCode = $r->formTypeCode;
			$strFormTypeText = $r->formTypeText;

			$intAnswerCount = 0;

			switch($strFormTypeCode)
			{
				//case 'checkbox':
				case 'radio_button':
					$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND formTypeID = '%d' AND form2TypeID = '%d'", $this->id, $intFormTypeID, $intForm2TypeID));

					$intAnswerPercent = round($intAnswerCount / $data['total_answers'] * 100);

					$out .= "<div class='form_radio'>";

						if($intAnswerPercent > 0)
						{
							$out .= "<div style='width: ".$intAnswerPercent."%'>&nbsp;</div>";
						}

						$out .= "<p>"
							.$strFormTypeText
							."<span>".$intAnswerPercent."%</span>
						</p>
					</div>";
				break;

				//case 'select':
				case 'radio_multiple':
					@list($strFormTypeText, $strFormTypeSelect) = explode(":", $strFormTypeText);

					$out .= "<h4>".$strFormTypeText."</h4>";

					if($this->form_option_exists)
					{
						$result = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionValue FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $intForm2TypeID));

						foreach($result as $r)
						{
							$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d 'AND formTypeID = '%d' AND form2TypeID = '%d' AND answerText = %s", $this->id, $intFormTypeID, $intForm2TypeID, $r->formOptionID));

							$intAnswerPercent = round($intAnswerCount / $data['total_answers'] * 100);

							$out .= "<div class='form_radio'>";

								if($intAnswerPercent > 0)
								{
									$out .= "<div style='width: ".$intAnswerPercent."%'>&nbsp;</div>";
								}

								$out .= "<p>"
									.$r->formOptionValue
									."<span>".$intAnswerPercent."%</span>
								</p>
							</div>";
						}
					}

					else
					{
						$arr_options = explode(",", $strFormTypeSelect);

						foreach($arr_options as $str_option)
						{
							$arr_option = explode("|", $str_option);

							if($arr_option[0] > 0 && $arr_option[1] != '')
							{
								$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d 'AND formTypeID = '%d' AND form2TypeID = '%d' AND answerText = %s", $this->id, $intFormTypeID, $intForm2TypeID, $arr_option[0]));

								$intAnswerPercent = round($intAnswerCount / $data['total_answers'] * 100);

								$out .= "<div class='form_radio'>";

									if($intAnswerPercent > 0)
									{
										$out .= "<div style='width: ".$intAnswerPercent."%'>&nbsp;</div>";
									}

									$out .= "<p>"
										.$arr_option[1]
										."<span>".$intAnswerPercent."%</span>
									</p>
								</div>";
							}
						}
					}
				break;

				default:
					$out .= "<div>
						<p>"
							.$strFormTypeText
						."</p>
					</div>";
				break;
			}

			$out .= "</div>";
		}

		return $out;
	}

	function create_nonce()
	{
		global $wpdb;

		$nonce_key = md5((defined('NONCE_SALT') ? NONCE_SALT : '').get_current_visitor_ip().date("YmdHis"));

		$wpdb->get_var($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_nonce SET nonceKey = %s, nonceCreated = NOW()", $nonce_key));

		return $nonce_key;
	}

	function check_nonce($nonce_key)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_nonce WHERE nonceKey = %s", $nonce_key));

		return ($wpdb->rows_affected == 1);
	}

	function get_form($data = array())
	{
		global $wpdb, $wp_query, $done_text, $error_text, $obj_font_icons;

		if(!isset($data['do_redirect'])){	$data['do_redirect'] = true;}

		$out = "";

		if(!isset($obj_font_icons))
		{
			$obj_font_icons = new mf_font_icons();
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT formAcceptDuplicates, formShowAnswers, formAnswerURL, formButtonDisplay, formButtonText, formButtonSymbol, formPaymentProvider FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND blogID = '%d' AND formDeleted = '0'", $this->id, $wpdb->blogid));

		foreach($result as $r)
		{
			$this->accept_duplicates = $r->formAcceptDuplicates;
			$strFormShowAnswers = $r->formShowAnswers;
			$strFormAnswerURL = $r->formAnswerURL;
			$intFormButtonDisplay = $r->formButtonDisplay;
			$strFormButtonText = ($r->formButtonText != '' ? $r->formButtonText : __("Submit", 'lang_form'));
			$strFormButtonSymbol = $obj_font_icons->get_symbol_tag(array('symbol' => $r->formButtonSymbol));
			$this->provider = $intFormPaymentProvider = $r->formPaymentProvider;

			if($strFormAnswerURL != '' && preg_match("/_/", $strFormAnswerURL))
			{
				list($blog_id, $intFormAnswerURL) = explode("_", $strFormAnswerURL);
			}

			else
			{
				$blog_id = 0;
				$intFormAnswerURL = $strFormAnswerURL;
			}

			$dteFormDeadline = $this->meta(array('action' => 'get', 'key' => 'deadline'));

			if($this->edit_mode == false && ($this->is_sent == true || $this->is_duplicate() && $this->accept_duplicates == 'no'))
			{
				$data['total_answers'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) WHERE formID = '%d' AND formTypeID IN('5', '8', '17')", $this->id));

				$out .= "<div class='mf_form mf_form_results'>";

					if($strFormShowAnswers == 'yes' && $data['total_answers'] > 0)
					{
						$out .= $this->get_poll_results($data);
					}

					else if($intFormAnswerURL > 0)
					{
						if($blog_id > 0)
						{
							switch_to_blog($blog_id);
						}

						if(isset($wp_query->post->ID) && $intFormAnswerURL != $wp_query->post->ID || !isset($wp_query->post->ID))
						{
							$out .= "<i class='fa fa-spinner fa-spin fa-3x'></i>";

							$this->redirect_url = get_permalink($intFormAnswerURL);

							if($data['do_redirect'] == true)
							{
								echo $out;

								mf_redirect($this->redirect_url);
							}
						}

						if($blog_id > 0)
						{
							restore_current_blog();
						}
					}

					else
					{
						$done_text = __("Thank You!", 'lang_form');

						$out .= get_notification();
					}

				$out .= "</div>";
			}

			else if($this->edit_mode == false && $dteFormDeadline > DEFAULT_DATE && $dteFormDeadline < date("Y-m-d"))
			{
				$error_text = __("This form is not open for submissions anymore", 'lang_form');

				$out .= get_notification();
			}

			else if($out == '')
			{
				$result = $this->get_form_type_result();

				if($wpdb->num_rows > 0)
				{
					$out .= "<form method='post' action='' id='form_".$this->id."' class='mf_form mf_form_submit".($this->edit_mode == true ? " mf_sortable" : "").apply_filters('filter_form_class', '', $this)."' enctype='multipart/form-data'>";

						if($this->edit_mode == false)
						{
							$out .= get_notification();
						}

						$intFormTypeID2_temp = $intForm2TypeID2_temp = "";

						foreach($result as $r)
						{
							$r->formTypeText = stripslashes($r->formTypeText);

							$obj_form_output = new mf_form_output(array('id' => $this->id, 'answer_id' => $this->answer_id, 'result' => $r, 'in_edit_mode' => $this->edit_mode, 'query_prefix' => $this->prefix));

							$obj_form_output->calculate_value(); //$this->answer_id
							$obj_form_output->get_form_fields();

							$out .= $obj_form_output->get_output($data);
						}

						if($this->answer_id > 0)
						{
							$out .= "<div class='form_button'>"
								.show_button(array('name' => 'btnFormUpdate', 'text' => __("Update", 'lang_form')))
								.input_hidden(array('name' => 'intFormID', 'value' => $this->id))
								.input_hidden(array('name' => 'intAnswerID', 'value' => $this->answer_id))
							."</div>";
						}

						else if($this->edit_mode == false)
						{
							$setting_form_spam = get_option_or_default('setting_form_spam', array('email', 'filter', 'honeypot'));

							if(in_array('honeypot', $setting_form_spam))
							{
								$out .= show_textfield(array('name' => $this->prefix.'check', 'text' => __("This field should not be visible", 'lang_form'), 'xtra_class' => "form_check", 'xtra' => " autocomplete='off'"));
							}

							$out .= apply_filters('filter_form_after_fields', '');

							if($intFormButtonDisplay != 0)
							{
								$out .= "<div class='form_button'>"
									.show_button(array('name' => $this->prefix.'btnFormSubmit', 'text' => $strFormButtonSymbol.$strFormButtonText))
									.show_button(array('type' => 'button', 'name' => 'btnFormClear', 'text' => __("Clear", 'lang_form'), 'class' => "button-secondary hide"));

									if(does_table_exist($wpdb->base_prefix."form_nonce"))
									{
										$out .= input_hidden(array('name' => 'form_submit_'.$this->id, 'value' => $this->create_nonce()));
									}

									//$out .= wp_nonce_field('form_submit_'.$this->id, '_wpnonce_form_submit', true, false);

									if($this->check_if_has_payment() && (IS_ADMIN || isset($_GET['make_test_payment'])))
									{
										$out .= show_checkbox(array('name' => $this->prefix.'test_payment', 'text' => __("Perform test payment", 'lang_form'), 'value' => 1))
										.apply_filters('filter_form_test_payment', '');
									}

									if(isset($this->send_to) && $this->send_to != '')
									{
										$out .= input_hidden(array('name' => 'email_encrypted', 'value' => hash('sha512', $this->send_to)));
									}

									$out .= input_hidden(array('name' => 'intFormID', 'value' => $this->id));

									if(isset($this->form_atts) && is_array($this->form_atts))
									{
										foreach($this->form_atts as $key => $value)
										{
											$out .= input_hidden(array('name' => $key, 'value' => $value));
										}
									}

								$out .= "</div>";
							}
						}

					$out .= "</form>";
				}
			}
		}

		return $out;
	}

	function process_form($data = array())
	{
		global $wpdb, $error_text;

		$out = "";

		if(!isset($data['form2type_id'])){	$data['form2type_id'] = 0;}

		$this->edit_mode = (isset($data['edit']) ? $data['edit'] : false);
		$this->send_to = (isset($data['send_to']) ? $data['send_to'] : "");
		$this->answer_id = (isset($data['answer_id']) ? $data['answer_id'] : "");

		if(isset($_GET['accept']) || isset($_GET['callback']) || isset($_GET['cancel']))
		{
			$obj_payment = new mf_form_payment($this->id);
			$out .= $obj_payment->process_callback();
		}

		else if(isset($_GET['btnFormLinkYes']) || isset($_GET['btnFormLinkNo']))
		{
			$out .= $this->process_link_yes_no();
		}

		else
		{
			$this->prefix = $this->get_post_info()."_";

			if(isset($_POST[$this->prefix.'btnFormSubmit']) && $this->is_correct_form($data) && (!does_table_exist($wpdb->base_prefix."form_nonce") || (isset($_POST['form_submit_'.$this->id]) && $this->check_nonce($_POST['form_submit_'.$this->id])))) // && wp_verify_nonce($_POST['_wpnonce_form_submit'], 'form_submit_'.$this->id)
			{
				$out .= $this->process_submit();
			}

			$out .= $this->get_form($data);
		}

		$out .= get_notification();

		return $out;
	}

	function get_pie_chart($data = array())
	{
		global $wpdb;

		if(!isset($data['heading_size'])){		$data['heading_size'] = 3;}

		$out = "";

		if(!isset($_GET['answerSpam']) || $_GET['answerSpam'] == 0)
		{
			list($result, $rows) = $this->get_form_type_info(array('query_type_code' => array('checkbox', 'radio_button', 'select', 'radio_multiple')));

			if($rows > 0)
			{
				mf_enqueue_script('jquery-flot', plugins_url()."/mf_base/include/jquery.flot.min.0.7.js", '0.7'); //Should be placed in admin_init
				mf_enqueue_script('jquery-flot-pie', plugins_url()."/mf_base/include/jquery.flot.pie.min.js", '1.1');

				$js_out = "";
				$arr_data_pie = array();

				$i = 0;

				foreach($result as $r)
				{
					$intForm2TypeID = $r->form2TypeID;
					$intFormTypeID = $r->formTypeID;
					$strFormTypeCode = $r->formTypeCode;
					$strFormTypeText = $r->formTypeText;

					if(!isset($arr_data_pie[$intForm2TypeID]))
					{
						$arr_data_pie[$intForm2TypeID] = array(
							'data' => '',
						);
					}

					switch($strFormTypeCode)
					{
						case 'checkbox':
						case 'radio_button':
							$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND formTypeID = '%d' AND form2TypeID = '%d'", $this->id, $intFormTypeID, $intForm2TypeID));

							$arr_data_pie[$intForm2TypeID]['data'] .= ($arr_data_pie[$intForm2TypeID]['data'] != '' ? "," : "")."{label: '".shorten_text(array('string' => $strFormTypeText, 'limit' => 20))."', data: ".$intAnswerCount."}";
						break;

						case 'select':
						case 'radio_multiple':
							if($this->form_option_exists)
							{
								$result_options = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionValue FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $intForm2TypeID));

								foreach($result_options as $r)
								{
									$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d 'AND formTypeID = '%d' AND form2TypeID = '%d' AND answerText = %s", $this->id, $intFormTypeID, $intForm2TypeID, $r->formOptionID));

									if($intAnswerCount > 0)
									{
										$arr_data_pie[$intForm2TypeID]['data'] .= ($arr_data_pie[$intForm2TypeID]['data'] != '' ? "," : "")."{label: '".shorten_text(array('string' => $r->formOptionValue, 'limit' => 20))."', data: ".$intAnswerCount."}";
									}
								}
							}

							else
							{
								list($strFormTypeText, $strFormTypeSelect) = explode(":", $strFormTypeText);
								$arr_options = explode(",", $strFormTypeSelect);

								foreach($arr_options as $str_option)
								{
									$arr_option = explode("|", $str_option);

									if($arr_option[0] > 0 && $arr_option[1] != '')
									{
										$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d 'AND formTypeID = '%d' AND form2TypeID = '%d' AND answerText = %s", $this->id, $intFormTypeID, $intForm2TypeID, $arr_option[0]));

										if($intAnswerCount > 0)
										{
											$arr_data_pie[$intForm2TypeID]['data'] .= ($arr_data_pie[$intForm2TypeID]['data'] != '' ? "," : "")."{label: '".shorten_text(array('string' => $arr_option[1], 'limit' => 20))."', data: ".$intAnswerCount."}";
										}
									}
								}
							}
						break;
					}

					$arr_data_pie[$intForm2TypeID]['label'] = $strFormTypeText;
				}

				$out .= "<div class='flot_wrapper'>";

					foreach($arr_data_pie as $key => $arr_value)
					{
						$out .= "<div>"
							."<h".$data['heading_size'].">".$arr_value['label']."</h".$data['heading_size'].">"
							."<div id='flot_pie_".$key."' class='flot_pie'></div>
						</div>";

						$js_out .= "$.plot($('#flot_pie_".$key."'), [".$arr_value['data']."],
						{
							series:
							{
								pie:
								{
									innerRadius: 0.3,
									show: true,
									radius: 1,
									label:
									{
										show: true,
										radius: 3/5,
										formatter: function(label, series)
										{
											return series.data[0][1];
										},
										background:
										{
											opacity: 0.5
										}
									}
								}
							},
							legend: {
								show: true
							}
						});";
					}

				$out .= "</div>
				<script defer>
					jQuery(function($)
					{"
						.$js_out
					."});
				</script>";
			}
		}

		return $out;
	}
}

class mf_form_payment
{
	function __construct($id = 0)
	{
		global $wpdb, $obj_form;

		$site_url = get_home_url();
		$site_url_clean = remove_protocol(array('url' => $site_url, 'clean' => true, 'trim' => true));

		$is_subfolder = substr_count($site_url_clean, '/') > 0;

		if($is_subfolder)
		{
			$arr_domain_parts = explode("/", $site_url_clean);

			$site_url = str_replace("/".$arr_domain_parts[1], "", $site_url);
		}

		$this->form_id = $id;
		$this->base_form_url = $site_url.$_SERVER['REQUEST_URI'].(preg_match("/\?/", $_SERVER['REQUEST_URI']) ? "&" : "?");
		$this->base_callback_url = $site_url.$_SERVER['REQUEST_URI'].(preg_match("/\?/", $_SERVER['REQUEST_URI']) ? "&" : "?");

		if($this->form_id > 0)
		{
			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$result = $wpdb->get_results($wpdb->prepare("SELECT formName, formPaymentProvider, formPaymentHmac, formTermsPage, formPaymentMerchant, formPaymentPassword, formPaymentCurrency, formAnswerURL, formPaymentCost, formPaymentAmount, formPaymentTax, formPaymentCallback FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $this->form_id));

			foreach($result as $r)
			{
				$this->name = $r->formName;
				$this->provider = $r->formPaymentProvider;
				$this->hmac = $r->formPaymentHmac;
				$this->terms_page = $r->formTermsPage;
				$this->merchant = $r->formPaymentMerchant;
				$this->password = $r->formPaymentPassword;
				$this->currency = $r->formPaymentCurrency;
				$this->answer_url = $r->formAnswerURL;
				$this->payment_cost = $r->formPaymentCost;
				$this->payment_amount = $r->formPaymentAmount;
				$this->payment_tax_rate = $r->formPaymentTax != '' ? $r->formPaymentTax : 25;
				$this->payment_callback = $r->formPaymentCallback;

				$obj_form->id = $this->form_id;

				$this->prefix = $obj_form->get_post_info()."_";

				//The callback must have a public URL
				if(is_admin())
				{
					$this->base_callback_url = get_permalink($obj_form->post_id)."?";
				}
			}
		}
	}

	function get_site_language($data) //sv_SE, en_US etc.
	{
		if(!isset($data['language'])){			$data['language'] = get_bloginfo('language');}
		if(!isset($data['type'])){				$data['type'] = '';}
		if(!isset($data['uc'])){				$data['uc'] = true;}
		if(!isset($data['return_separator'])){	$data['return_separator'] = "_";}

		if(preg_match("/\_/", $data['language']))
		{
			$arr_language = explode("_", $data['language']);
		}

		else
		{
			$arr_language = explode("-", $data['language']);
		}

		$out = "";

		switch($data['type'])
		{
			case 'first': //sv, en etc.
				if(isset($arr_language[0]))
				{
					$out = $arr_language[0];

					if($data['uc'] == true)
					{
						$out = strtoupper($out);
					}
				}

				else
				{
					do_log("Wrong lang[0]: ".var_export($data, true));
				}
			break;

			case 'last': //SE, US etc.
				if(isset($arr_language[1]))
				{
					$out = $arr_language[1];

					if($data['uc'] == true)
					{
						$out = strtoupper($out);
					}
				}

				else
				{
					do_log("Wrong lang[1]: ".var_export($data, true));
				}
			break;

			default:
				//$out = $data['language'];
				$out = $arr_language[0].$data['return_separator'].$arr_language[1];
			break;
		}

		return $out;
	}

	function process_passthru($data)
	{
		$this->cost = $data['cost'];
		$this->amount = intval($data['amount']) > 0 ? $data['amount'] : 1;

		$this->orderid = $data['orderid'];
		$this->test = $data['test'];
		$this->answer_data = $data['answer_data'];

		if($this->cost > 0)
		{
			$this->cost_total = $this->cost * $this->amount;
		}

		else
		{
			$this->cost_total = $this->cost = $this->amount;
			$this->amount = 1;
		}

		if($this->payment_tax_rate > 0)
		{
			$this->tax = ($this->cost * ($this->payment_tax_rate / 100));
			$this->tax_total = ($this->cost_total * ($this->payment_tax_rate / 100));
		}

		else
		{
			$this->tax = $this->tax_total = 0;
		}

		$out = apply_filters('form_process_passthru', '', $this);

		if($this->provider > 0 && $out == '')
		{
			do_log(sprintf("A provider was set (%s) to passthru but there seams to be no provider extensions installed", $this->provider));
		}

		return $out;
		exit;
	}

	function run_confirm_callback()
	{
		global $wpdb;

		if($this->payment_callback != '')
		{
			if(function_exists($this->payment_callback))
			{
				if(is_callable($this->payment_callback))
				{
					if($this->payment_amount > 0)
					{
						$paid = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $this->answer_id, $this->payment_amount));

						call_user_func($this->payment_callback, array('paid' => $paid, 'answer_id' => $this->answer_id));
					}

					else
					{
						do_log(sprintf("The Field for Payment Amount was not set on the form with ID %d so the function %s could not be triggered", $this->form_id, $this->payment_callback));
					}
				}

				else
				{
					do_log(sprintf("Function %s not callable", $this->payment_callback));
				}
			}

			else
			{
				do_log(sprintf("Function %s does not exist", $this->payment_callback));
			}
		}
	}

	function confirm_cancel()
	{
		global $wpdb, $error_text, $obj_form;

		$out = "";

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText LIKE %s", "103: ".__("User canceled", 'lang_form'), $this->answer_id, '10%'));

		$error_text = __("Your payment was cancelled", 'lang_form');

		$out .= get_notification();

		return $out;
	}

	function confirm_accept($is_verified = false)
	{
		global $wpdb, $wp_query, $obj_form, $done_text;

		$out = "";

		if($this->answer_id > 0)
		{
			if($is_verified)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText NOT LIKE %s", "116: ".__("Paid and Verified", 'lang_form'), $this->answer_id, '116:%'));

				if($wpdb->rows_affected > 0)
				{
					$this->run_confirm_callback();
				}
			}

			else
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText LIKE %s", "104: ".__("User has paid. Waiting for confirmation...", 'lang_form'), $this->answer_id, '10%'));
			}

			if($this->answer_url != '' && preg_match("/_/", $this->answer_url))
			{
				list($blog_id, $intFormAnswerURL) = explode("_", $this->answer_url);
			}

			else
			{
				$blog_id = 0;
				$intFormAnswerURL = $this->answer_url;
			}

			if($intFormAnswerURL > 0)
			{
				if($blog_id > 0)
				{
					switch_to_blog($blog_id);
				}

				if(isset($wp_query->post->ID) && $intFormAnswerURL != $wp_query->post->ID || !isset($wp_query->post->ID))
				{
					echo "<i class='fa fa-spinner fa-spin fa-3x'></i>"; // rel='test3_".$intFormAnswerURL."'

					$strFormAnswerURL = get_permalink($intFormAnswerURL);

					$strFormAnswerURL .= (preg_match("/\?/", $strFormAnswerURL) ? "&" : "?")."answer_id=".$this->answer_id;

					mf_redirect($strFormAnswerURL);
				}

				/*else
				{
					do_log("Redirect not verified");

					if(!headers_sent())
					{
						//header("Status: 400 Bad Request");
					}
				}*/

				if($blog_id > 0)
				{
					restore_current_blog();
				}
			}

			else
			{
				$done_text = __("Thank You!", 'lang_form');

				$out .= get_notification();
			}
		}

		else
		{
			if(!headers_sent())
			{
				header("Status: 400 Bad Request");
			}
		}

		return $out;
	}

	function confirm_paid($message)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText NOT LIKE %s", "116: ".$message, $this->answer_id, '116:%'));

		if($wpdb->rows_affected > 0)
		{
			$this->run_confirm_callback();
		}

		if(!headers_sent())
		{
			header("Status: 200 OK");
		}
	}

	function confirm_error($message)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0'", "115: ".$message, $this->answer_id));

		if(!headers_sent())
		{
			header("Status: 400 Bad Request");
		}
	}

	function process_callback()
	{
		global $obj_form;

		$request_type = substr($_SERVER['REQUEST_URI'], 15);

		$this->is_accept = isset($_GET['accept']) || $request_type == "accept";
		$this->is_callback = isset($_GET['callback']) || $request_type == "callback";
		$this->is_cancel = isset($_GET['cancel']) || $request_type == "cancel";

		//Debug
		##################
		/*$file_suffix = "unknown";

		if($this->is_accept){			$file_suffix = "accept";}
		else if($this->is_callback){	$file_suffix = "callback";}
		else if($this->is_cancel){		$file_suffix = "cancel";}

		$file = prepare_file_name($file_suffix);
		$debug = "URI: ".$_SERVER['REQUEST_URI']."\n\n"
			."GET: ".var_export($_GET, true)."\n\n"
			."POST: ".var_export($_POST, true)."\n\n"
			."THIS: ".var_export($this, true)."\n\n";

		list($upload_path, $upload_url) = get_uploads_folder($obj_form->post_type);

		$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => trim($debug)));*/
		##################

		$this->amount = check_var('amount', 'int');

		$out = apply_filters('form_process_callback', "<p>".__("Processing", 'lang_form')."&hellip;</p>", $this);

		return $out;
	}
}

if(class_exists('mf_export'))
{
	class mf_form_export extends mf_export
	{
		function get_defaults()
		{
			$this->plugin = "mf_form";
		}

		function get_export_data()
		{
			global $wpdb, $obj_form;

			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$obj_form->id = $this->type;
			$this->name = $obj_form->get_post_info(array('select' => 'post_title'));

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeConnectTo, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeRemember, form2TypeOrder FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' ORDER BY form2TypeOrder ASC", $this->type));

			foreach($result as $r)
			{
				switch($r->formTypeID)
				//switch($r->formTypeCode)
				{
					case 10:
					case 11:
					case 16:
					case 17:
						$i = 0;

						$result2 = $wpdb->get_results($wpdb->prepare("SELECT formOptionKey, formOptionValue, formOptionLimit, formOptionAction FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $r->form2TypeID));

						foreach($result2 as $r2)
						{
							if($i == 0)
							{
								$r->formTypeText .= ":";
							}

							else
							{
								$r->formTypeText .= ";";
							}

							$r->formTypeText .= $r2->formOptionKey."|".$r2->formOptionValue."|".$r2->formOptionLimit."|".$r2->formOptionAction;

							$i++;
						}
					break;
				}

				$this->data[] = array(
					$r->formTypeID,
					//$r->formTypeCode,
					$r->formTypeText,
					$r->formTypePlaceholder,
					$r->checkID,
					$r->formTypeTag,
					$r->formTypeClass,
					$r->formTypeFetchFrom,
					$r->formTypeConnectTo,
					$r->formTypeDisplay,
					$r->formTypeRequired,
					$r->formTypeAutofocus,
					$r->formTypeRemember,
					$r->form2TypeOrder,
				);
			}
		}
	}

	class mf_form_answer_export extends mf_export
	{
		function get_defaults()
		{
			$this->plugin = "mf_form";
		}

		function get_export_data()
		{
			global $wpdb, $obj_form;

			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$obj_form->id = $this->type;
			$this->name = $obj_form->get_post_info(array('select' => 'post_title'));

			$search = check_var('s');

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeCode NOT IN('text', 'space', 'custom_tag', 'custom_tag_end') ORDER BY form2TypeOrder ASC", $this->type)); // AND formTypeResult = '1'

			$this_row = array();

			foreach($result as $r)
			{
				$intForm2TypeID = $r->form2TypeID;
				$intFormTypeID = $r->formTypeID;
				$strFormTypeCode = $r->formTypeCode;
				$obj_form->label = $r->formTypeText;

				switch($strFormTypeCode)
				{
					case 'range':
						$obj_form->parse_range_label();
					break;

					case 'select':
					case 'select_multiple':
					case 'checkbox_multiple':
					case 'radio_multiple':
						@list($obj_form->label, $str_select) = explode(":", $obj_form->label);
					break;
				}

				$this_row[] = stripslashes(strip_tags($obj_form->label));
			}

			if($obj_form->check_if_has_payment())
			{
				$this_row[] = __("Payment", 'lang_form');
			}

			$this_row[] = __("Created", 'lang_form');

			$this->data[] = $this_row;

			$query_join = " INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID)";
			$query_where = "";

			// (Almost) Same as in mf_answer_table()
			if($search != '')
			{
				$query_join .= " LEFT JOIN ".$wpdb->base_prefix."form_answer_email USING (answerID)";
				$query_join .= " LEFT JOIN ".$wpdb->base_prefix."form_option ON ".$wpdb->base_prefix."form_answer.answerText = ".$wpdb->base_prefix."form_option.formOptionID";

				$query_where .= " AND (answerText LIKE '%".$search."%' OR answerEmail LIKE '%".$search."%' OR answerCreated LIKE '%".$search."%'";
					$query_where .= " OR formOptionValue LIKE '%".$search."%'";

					if(preg_match('/[a-zA-Z]/', $search))
					{
						$query_where .= " OR SOUNDEX(answerText) = SOUNDEX('".$search."') OR SOUNDEX(answerEmail) = SOUNDEX('".$search."')";
						$query_where .= " OR SOUNDEX(formOptionValue) = SOUNDEX('".$search."')";
					}

				$query_where .= ")";	
			}
			
			$result = $wpdb->get_results("SELECT answerID, formID, answerCreated FROM ".$wpdb->base_prefix."form2answer".$query_join." WHERE formID = '".esc_sql($this->type)."' AND answerSpam = '0'".$query_where." GROUP BY answerID ORDER BY answerCreated DESC");

			foreach($result as $r)
			{
				$intAnswerID = $r->answerID;
				$intFormID = $r->formID;
				$strAnswerCreated = $r->answerCreated;

				$this_row = array();

				$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeCode NOT IN('text', 'space', 'custom_tag', 'custom_tag_end') ORDER BY form2TypeOrder ASC", $intFormID)); // AND formTypeResult = '1'

				foreach($resultText as $r)
				{
					$intForm2TypeID = $r->form2TypeID;
					$intFormTypeID = $r->formTypeID;
					$strFormTypeCode = $r->formTypeCode;
					$obj_form->label = $r->formTypeText;

					$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d'", $intForm2TypeID, $intAnswerID));
					$rowsAnswer = $wpdb->num_rows;

					if($rowsAnswer > 0)
					{
						$r = $resultAnswer[0];
						$strAnswerText = $r->answerText;

						switch($strFormTypeCode)
						{
							case 'radio_button':
								$strAnswerText = 1;
							break;

							case 'datepicker':
								$strAnswerText = format_date($strAnswerText);
							break;

							case 'select':
							case 'radio_multiple':
								$strAnswerText = $obj_form->parse_select_info($strAnswerText);
							break;

							case 'select_multiple':
							case 'checkbox_multiple':
								$obj_form->prefix = $obj_form->get_post_info()."_";

								$strAnswerText = $obj_form->parse_multiple_info($strAnswerText, true);
							break;

							case 'file':
								$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

								foreach($result as $r)
								{
									$strAnswerText = "<a href='".$r->guid."'>".$r->post_title."</a>";
								}
							break;
						}

						$this_row[] = $strAnswerText;
					}

					else
					{
						$this_row[] = "";
					}
				}

				if($obj_form->check_if_has_payment())
				{
					$strAnswerText_temp = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '0'", $intAnswerID));

					$this_row[] = $strAnswerText_temp;
				}

				$this_row[] = $strAnswerCreated;

				$this->data[] = $this_row;
			}
		}
	}
}

if(class_exists('mf_list_table'))
{
	class mf_form_table extends mf_list_table
	{
		function set_default()
		{
			global $obj_form;

			$this->post_type = $obj_form->post_type;

			$this->orderby_default = "post_modified";
			$this->orderby_default_order = "DESC";
		}

		function init_fetch()
		{
			global $wpdb, $obj_form;

			$this->query_join .= " INNER JOIN ".$wpdb->base_prefix."form ON ".$wpdb->posts.".ID = ".$wpdb->base_prefix."form.postID";
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."blogID = '".$wpdb->blogid."'";

			$this->query_join .= " LEFT JOIN ".$wpdb->base_prefix."form2answer USING (formID)";

			if($this->search != '')
			{
				$this->query_where .= ($this->query_where != '' ? " AND " : "").get_form_xtra("", $this->search, "", "post_title");
			}

			$this->set_views(array(
				'db_field' => 'post_status',
				'types' => array(
					'all' => __("All", 'lang_form'),
					'publish' => __("Public", 'lang_form'),
					'draft' => __("Draft"),
					'trash' => __("Trash", 'lang_form'),
				),
			));

			$this->set_columns(array(
				'post_title' => __("Name", 'lang_form'),
				'content' => __("Content", 'lang_form'),
				'answers' => __("Answers", 'lang_form'),
				'spam' => __("Spam", 'lang_form'),
				'answerCreated' => __("Latest Answer", 'lang_form'),
				'post_date' => __("Created", 'lang_form'),
				'post_modified' => __("Modified", 'lang_form'),
			));

			$this->set_sortable_columns(array(
				'post_title',
				'answerCreated',
				'post_date',
				'post_modified',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb, $obj_form;

			if(!isset($obj_form))
			{
				$obj_form = new mf_form();
			}

			$out = "";

			$post_id = $item['ID'];
			$post_status = $item['post_status'];

			$obj_form->get_form_id($post_id);

			switch($column_name)
			{
				case 'post_title':
					$strFormName = $item['post_title'];

					if($obj_form->check_allow_edit())
					{
						$post_edit_url = admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id);
					}

					$actions = array();

					if($post_status != 'trash')
					{
						if($obj_form->check_allow_edit())
						{
							$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_form')."</a>";

							$query_answers = $obj_form->get_answer_amount(array('form_id' => $obj_form->id));

							if($query_answers == 0)
							{
								$actions['delete'] = "<a href='#delete/form/".$obj_form->id."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
							}
						}

						$actions['copy'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnFormCopy&intFormID=".$obj_form->id), 'form_copy_'.$obj_form->id, '_wpnonce_form_copy')."'>".__("Copy", 'lang_form')."</a>";

						$actions['export'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnFormExport&intFormID=".$obj_form->id."&btnExportRun&intExportType=".$obj_form->id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."'>".__("Export", 'lang_form')."</a>";

						if($post_status == 'publish' && $obj_form->id > 0)
						{
							$shortcode = "[mf_form id=".$obj_form->id."]";

							$result = get_pages_from_shortcode($shortcode);

							if(count($result) > 0)
							{
								foreach($result as $post_id_temp)
								{
									if($obj_form->check_allow_edit())
									{
										$actions['edit_page'] = "<a href='".admin_url("post.php?post=".$post_id_temp."&action=edit")."'>".__("Edit Page", 'lang_form')."</a>";
									}

									$actions['view_page'] = "<a href='".get_permalink($post_id_temp)."'>".__("View", 'lang_form')."</a>";
								}
							}

							else
							{
								if($obj_form->get_form_status() == 'publish')
								{
									$post_url = get_permalink($post_id);

									if($post_url != '')
									{
										$actions['view'] = "<a href='".$post_url."'>".__("View", 'lang_form')."</a>";
									}
								}

								$actions['add_page'] = "<a href='".admin_url("post-new.php?post_type=page&post_title=".$strFormName."&content=".$shortcode)."'>".__("Add New Page", 'lang_form')."</a>";
							}
						}
					}

					else if($obj_form->check_allow_edit())
					{
						$actions['recover'] = "<a href='".$post_edit_url."&recover'>".__("Recover", 'lang_form')."</a>";
					}

					if($obj_form->check_allow_edit())
					{
						$out .= "<a href='".$post_edit_url."'>".$strFormName."</a>";
					}

					else
					{
						$out .= $strFormName;
					}

					$out .= $this->row_actions($actions);
				break;

				case 'content':
					if($post_status == 'publish')
					{
						$out .= "<i class='fa fa-link fa-lg grey' title='".__("Public", 'lang_form')."'></i> ";
					}

					$result = $wpdb->get_results($wpdb->prepare("SELECT formEmail, formEmailConditions, formEmailNotifyPage, formEmailConfirm, formEmailConfirmPage, formPaymentProvider FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $obj_form->id));

					foreach($result as $r)
					{
						$strFormEmail = ($r->formEmail != '' ? $r->formEmail : get_bloginfo('admin_email'));
						$strFormEmailConditions = $r->formEmailConditions;
						$intFormEmailNotifyPage = $r->formEmailNotifyPage;
						$intFormEmailConfirm = $r->formEmailConfirm;
						$intFormEmailConfirmPage = $r->formEmailConfirmPage;
						$intFormPaymentProvider = $r->formPaymentProvider;

						if($intFormEmailNotifyPage > 0)
						{
							$out .= "<i class='fa fa-paper-plane fa-lg grey' title='".sprintf(__("A notification email based on a template will be sent to %s", 'lang_form'), $strFormEmail)."'></i> ";
						}

						else
						{
							$out .= "<i class='fa fa-paper-plane fa-lg grey' title='".sprintf(__("E-mails will be sent to %s on every answer", 'lang_form'), $strFormEmail)."'></i> ";
						}

						if($strFormEmailConditions != '')
						{
							$out .= "<i class='fa fa-paper-plane fa-lg grey' title='".__("Message will be sent to different e-mails because there are conditions", 'lang_form')."'></i> ";
						}

						if($intFormEmailConfirm > 0)
						{
							if($intFormEmailConfirmPage > 0)
							{
								$out .= "<i class='fa fa-paper-plane fa-lg grey' title='".__("A confirmation email based on a template will be sent to the visitor", 'lang_form')."'></i> ";
							}

							else
							{
								$out .= "<i class='fa fa-paper-plane fa-lg grey' title='".__("A confirmation email will be sent to the visitor", 'lang_form')."'></i> ";
							}
						}

						if($intFormPaymentProvider > 0)
						{
							switch($intFormPaymentProvider)
							{
								case 3:
									$icon = "fab fa-paypal";
								break;

								default:
									$icon = "fa fa-shopping-cart";
								break;
							}

							$out .= "<i class='".$icon." fa-lg grey' title='".__("Provider", 'lang_form')."'></i> ";
						}
					}

					if($obj_form->is_form_field_type_used(array('display' => '0')))
					{
						$out .= "<i class='fa fa-eye-slash fa-lg grey' title='".__("There are hidden fields", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('required' => true)))
					{
						$out .= "<i class='fa fa-asterisk fa-lg grey' title='".__("There are required fields", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('autofocus' => true)))
					{
						$out .= "<i class='fa fa-i-cursor fa-lg grey' title='".__("There are autofocus fields", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('remember' => true)))
					{
						$out .= "<i class='fa fa-sync fa-lg grey' title='".__("There are remembered fields", 'lang_form')."'></i> ";
					}

					$out .= "<br>";

					if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'check_code' => 'email')))
					{
						$out .= "<i class='fa fa-at fa-lg grey' title='".__("There is a field for entering email adress", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('query_type_id' => array(10, 11))))
					{
						$out .= "<i class='fa fa-list-alt fa-lg grey' title='".__("Dropdown", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('query_type_id' => array(1, 16))))
					{
						$out .= "<i class='fa fa-check-square fa-lg grey' title='".__("Checkbox", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('query_type_id' => 2)))
					{
						$out .= "<i class='fa fa-sliders-h fa-lg grey' title='".__("Range", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('query_type_id' => 7)))
					{
						$out .= "<i class='fa fa-calendar-alt fa-lg grey' title='".__("Datepicker", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('query_type_id' => array(8, 17))))
					{
						$out .= "<i class='far fa-circle fa-lg grey' title='".__("Radio button", 'lang_form')."'></i> ";
					}

					if($obj_form->is_form_field_type_used(array('query_type_id' => 15)))
					{
						$out .= "<i class='fa fa-file fa-lg grey' title='".__("File", 'lang_form')."'></i> ";
					}
				break;

				case 'answers':
					if($post_status != 'trash')
					{
						$query_answers = $obj_form->get_answer_amount(array('form_id' => $obj_form->id));

						if($query_answers > 0)
						{
							$count_message = $obj_form->get_count_answer_message(array('form_id' => $obj_form->id));

							$actions = array(
								'show_answers' => "<a href='".admin_url("admin.php?page=mf_form/answer/index.php&intFormID=".$obj_form->id)."'>".__("View", 'lang_form')."</a>",
								'export_csv' => "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnFormAnswerExport&intFormID=".$obj_form->id."&btnExportRun&intExportType=".$obj_form->id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."'>CSV</a>",
							);

							if(is_plugin_active("mf_phpexcel/index.php"))
							{
								$actions['export_xls'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnFormAnswerExport&intFormID=".$obj_form->id."&btnExportRun&intExportType=".$obj_form->id."&strExportFormat=xls"), 'export_run', '_wpnonce_export_run')."'>XLS</a>";
							}

							$out .= $query_answers.$count_message
							.$this->row_actions($actions);
						}
					}
				break;

				case 'spam':
					if($post_status != 'trash')
					{
						$query_spam = $obj_form->get_answer_amount(array('form_id' => $obj_form->id, 'is_spam' => 1));

						if($query_spam > 0)
						{
							$out .= $query_spam;
						}
					}
				break;

				case 'answerCreated':
					$dteAnswerCreated = $wpdb->get_var($wpdb->prepare("SELECT answerCreated FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d' AND answerSpam = '%d' ORDER BY answerCreated DESC", $obj_form->id, '0'));
					$dteAnswerCreated_spam = $wpdb->get_var($wpdb->prepare("SELECT answerCreated FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d' AND answerSpam = '%d' ORDER BY answerCreated DESC", $obj_form->id, '1'));

					if($dteAnswerCreated > DEFAULT_DATE)
					{
						$actions = array();

						if($dteAnswerCreated_spam > DEFAULT_DATE)
						{
							$actions['spam'] = __("Spam", 'lang_form').": ".format_date($dteAnswerCreated_spam);
						}

						$out .= format_date($dteAnswerCreated)
						.$this->row_actions($actions);
					}
				break;

				case 'post_date':
					$actions = array();

					if($item['userID'] > 0)
					{
						$actions['user'] = get_user_info(array('id' => $item['userID']));
					}

					$out .= format_date($item['post_date'])
					.$this->row_actions($actions);
				break;

				case 'post_modified':
					$actions = array();

					if($item['post_author'] > 0 && $item['post_author'] != $item['userID'])
					{
						$actions['user'] = get_user_info(array('id' => $item['post_author']));
					}

					$out .= format_date($item['post_modified'])
					.$this->row_actions($actions);
				break;

				default:
					if(isset($item[$column_name]))
					{
						$out .= $item[$column_name];
					}
				break;
			}

			return $out;
		}
	}

	class mf_answer_table extends mf_list_table
	{
		function set_default()
		{
			global $wpdb, $obj_form;

			$this->arr_settings['query_from'] = $wpdb->base_prefix."form2answer";

			$this->arr_settings['query_select_id'] = "answerID";
			$this->arr_settings['query_all_id'] = "0";
			$this->arr_settings['query_trash_id'] = "1";
			$this->orderby_default = "answerCreated";
			$this->orderby_default_order = "DESC";

			$this->arr_settings['page_vars'] = array('intFormID' => $obj_form->id);
		}

		function init_fetch()
		{
			global $wpdb, $obj_form;

			$this->query_where .= ($this->query_where != '' ? " AND " : "")."formID = '".$obj_form->id."'";

			// Same as in mf_form_answer_export()
			if($this->search != '')
			{
				$this->query_join .= " LEFT JOIN ".$wpdb->base_prefix."form_answer USING (answerID) LEFT JOIN ".$wpdb->base_prefix."form_answer_email USING (answerID)";
				$this->query_join .= " LEFT JOIN ".$wpdb->base_prefix."form_option ON ".$wpdb->base_prefix."form_answer.answerText = ".$wpdb->base_prefix."form_option.formOptionID";

				$this->query_where .= " AND (answerText LIKE '%".$this->search."%' OR answerEmail LIKE '%".$this->search."%' OR answerCreated LIKE '%".$this->search."%'";
					$this->query_where .= " OR formOptionValue LIKE '%".$this->search."%'";

					if(preg_match('/[a-zA-Z]/', $this->search))
					{
						$this->query_where .= " OR SOUNDEX(answerText) = SOUNDEX('".$this->search."') OR SOUNDEX(answerEmail) = SOUNDEX('".$this->search."')";
						$this->query_where .= " OR SOUNDEX(formOptionValue) = SOUNDEX('".$this->search."')";
					}

				$this->query_where .= ")";
			}

			$this->set_views(array(
				'db_field' => 'answerSpam',
				'types' => array(
					'0' => __("All", 'lang_form'),
					'1' => __("Spam", 'lang_form')
				),
			));

			$arr_columns = array();

			if(isset($_GET['answerSpam']) && $_GET['answerSpam'] == 1)
			{
				$arr_columns['answerSpam'] = __("Spam", 'lang_form');
			}

			$obj_form->answer_column = 0;

			$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeCode, formTypeText, form2TypeID FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeCode NOT IN('text', 'space', 'custom_tag', 'custom_tag_end') ORDER BY form2TypeOrder ASC", $obj_form->id)); // AND formTypeResult = '1'

			foreach($result as $r)
			{
				$strFormTypeCode = $r->formTypeCode;
				$obj_form->label = $r->formTypeText;
				$intForm2TypeID2 = $r->form2TypeID;

				switch($strFormTypeCode)
				{
					case 'checkbox':
					case 'radio_button':
						$label_limit = 10;
					break;

					case 'range':
						$obj_form->parse_range_label();

						$label_limit = 10;
					break;

					case 'datepicker':
						$label_limit = 15;
					break;

					case 'select':
					case 'select_multiple':
					case 'checkbox_multiple':
					case 'radio_multiple':
						@list($obj_form->label, $str_select) = explode(":", $obj_form->label);

						$label_limit = 10;
					break;

					default:
						$label_limit = 20;
					break;
				}

				$arr_columns[$intForm2TypeID2] = shorten_text(array('string' => trim($obj_form->label, ":"), 'limit' => $label_limit));
			}

			if($obj_form->check_if_has_payment())
			{
				$arr_columns['payment'] = __("Payment", 'lang_form');
			}

			$arr_columns['answerCreated'] = __("Created", 'lang_form');
			$arr_columns['sent'] = __("Sent", 'lang_form');

			$this->set_columns($arr_columns);

			$this->set_sortable_columns(array(
				'answerCreated',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb, $obj_form;

			$out = "";

			$intAnswerID = $item['answerID'];

			switch($column_name)
			{
				case 'answerSpam':
					$actions = array();

					if($item['answerSpam'] == true)
					{
						$out .= "<i class='fa fa-times fa-lg red'></i>";

						if($item['spamID'] > 0)
						{
							$strSpamExplain = $obj_form->get_spam_rules(array('id' => $item['spamID'], 'type' => 'explain'));

							if($strSpamExplain != '')
							{
								$out .= "&nbsp;".$strSpamExplain;
							}
						}

						$actions['unspam'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerApprove&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_approve_'.$intAnswerID, '_wpnonce_answer_approve')."' rel='confirm'>".__("Approve", 'lang_form')."</a>";
					}

					$out .= $this->row_actions($actions);
				break;

				case 'payment':
					$actions = array();

					$strAnswerText_temp = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '0'", $intAnswerID));

					$test_payment_value = $obj_form->get_meta(array('id' => $intAnswerID, 'meta_key' => 'test_payment'));

					if($test_payment_value > 0)
					{
						$actions['status'] = $strAnswerText_temp;

						$out .= __("Test Payment", 'lang_form');
					}

					else
					{
						$out .= $strAnswerText_temp;
					}

					if($strAnswerText_temp != '')
					{
						list($payment_number, $rest) = explode(":", $strAnswerText_temp);

						switch($payment_number)
						{
							case 101:
							case 102:
								$strFormPaymentCallback = $wpdb->get_var($wpdb->prepare("SELECT formPaymentCallback FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $obj_form->id));

								if($strFormPaymentCallback != '')
								{
									$actions['verify'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerVerifyPayment&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_verify_payment_'.$intAnswerID, '_wpnonce_answer_verify_payment')."' rel='confirm'>".__("Verify", 'lang_form')."</a>";
								}

								$out .= "<i class='set_tr_color' rel='yellow'></i>";
							break;

							case 103:
							case 115:
								$out .= "<i class='set_tr_color' rel='red'></i>";
							break;

							case 104:
							case 105:
							case 116:
								$out .= "<i class='set_tr_color' rel='green'></i>";
							break;
						}
					}

					$out .= $this->row_actions($actions);
				break;

				case 'answerCreated':
					$obj_form->answer_column = 0;

					$actions = array(
						'id' => __("ID", 'lang_form').": ".$intAnswerID,
					);

					if($item['answerIP'] != '')
					{
						$actions['ip'] = __("IP", 'lang_form').": ".$item['answerIP'];
					}

					if($item['answerToken'] != '')
					{
						$actions['token'] = __("Token", 'lang_form').": ".$item['answerToken'];
					}

					$result = $obj_form->get_meta(array('id' => $intAnswerID));

					if($wpdb->num_rows > 0)
					{
						$meta_data_title = "";

						foreach($result as $r)
						{
							$meta_data_title .= ($meta_data_title != '' ? "\n" : "");

							switch($r->metaKey)
							{
								case 'payment_verified_by':
									$meta_data_title .= __("Verified by", 'lang_form').": ".get_user_info(array('id' => $r->metaValue));
								break;

								case 'test_payment':
									$meta_data_title .= __("Test Payment", 'lang_form').": ".get_user_info(array('id' => $r->metaValue));
								break;

								case 'user_agent':
									$meta_data_title .= __("Browser", 'lang_form').": ".$r->metaValue;
								break;

								case 'user_id':
									$meta_data_title .= __("User", 'lang_form').": ".get_user_info(array('id' => $r->metaValue));
								break;

								default:
									$meta_data_title .= $r->metaKey.": ".$r->metaValue;
								break;
							}
						}

						$actions['meta_data'] = "<span title='".$meta_data_title."'>".__("Meta Data", 'lang_form')." (".count($result).")</span>";
					}

					if($obj_form->check_if_has_payment() == false)
					{
						$strSentTo = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '0'", $intAnswerID));

						$strSentTo = trim(trim($strSentTo), ', ');
						$strSentTo = str_replace(", ", "<br>", $strSentTo);

						if($strSentTo != '' && strlen($strSentTo) > 4)
						{
							$actions['sent_to'] = "<br><strong>".__("Sent To", 'lang_form')."</strong><br>".$strSentTo;
						}
					}

					$out .= format_date($item['answerCreated'])
					.$this->row_actions($actions);
				break;

				case 'sent':
					$result_emails = $wpdb->get_results($wpdb->prepare("SELECT answerEmailFrom, answerEmail, answerSent, answerType FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerEmail != ''", $intAnswerID));
					$count_temp = $wpdb->num_rows;

					if($count_temp > 0)
					{
						$li_out = $strAnswerEmail_temp = "";
						$sent_successfully = $sent_failed = $sent_failed_w_type = 0;

						foreach($result_emails as $r)
						{
							$strAnswerEmailFrom = $r->answerEmailFrom;
							$strAnswerEmail = $r->answerEmail;
							$intAnswerSent = $r->answerSent;
							$strAnswerType = $r->answerType;

							if($intAnswerSent == 1)
							{
								$fa_class = "fa fa-check green";

								$sent_successfully++;
							}

							else
							{
								$fa_class = "fa fa-times red";

								$sent_failed++;

								if($strAnswerType != '')
								{
									$sent_failed_w_type++;
								}
							}

							if($strAnswerEmail != $strAnswerEmail_temp)
							{
								$li_out .= "<li>
									<i class='".$fa_class."'></i> ".($strAnswerEmailFrom != '' ? $strAnswerEmailFrom." -> " : "").$strAnswerEmail
								."</li>";

								$strAnswerEmail_temp = $strAnswerEmail;
							}
						}

						$out .= ($sent_failed > 0 ? $sent_successfully."/" : "").$count_temp
						."<div class='row-actions'>
							<ul>".$li_out."</ul>";

							if($sent_failed_w_type > 0)
							{
								$out .= "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnMessageResend&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'message_resend_'.$intAnswerID, '_wpnonce_message_resend')."' rel='confirm'>".__("Resend", 'lang_form')."</a>";
							}

						$out .= "</div>";
					}
				break;

				default:
					if(isset($item[$column_name]))
					{
						$out .= $item[$column_name];
					}

					else
					{
						$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

						$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeCode, formTypeText, checkCode FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeCode NOT IN('text', 'space', 'custom_tag', 'custom_tag_end') AND form2TypeID = '%d' LIMIT 0, 1", $obj_form->id, $column_name)); // AND formTypeResult = '1'

						foreach($resultText as $r)
						{
							$intForm2TypeID = $r->form2TypeID;
							$strFormTypeCode = $r->formTypeCode;
							$obj_form->label = $r->formTypeText;
							$strCheckCode = $r->checkCode;

							$strAnswerText = "";
							$actions = array();

							$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d'", $intForm2TypeID, $intAnswerID));
							$rowsAnswer = $wpdb->num_rows;

							if($rowsAnswer > 0)
							{
								$r = $resultAnswer[0];
								$strAnswerText = $r->answerText;

								switch($strFormTypeCode)
								{
									case 'radio_button':
										$strAnswerText = 1;
									break;

									case 'datepicker':
										$strAnswerText = $obj_form->pre_format_date($strAnswerText);
									break;

									case 'select':
									case 'radio_multiple':
										$strAnswerText = $obj_form->parse_select_info($strAnswerText);
									break;

									case 'select_multiple':
									case 'checkbox_multiple':
										$obj_form->prefix = $obj_form->get_post_info()."_";

										$strAnswerText = $obj_form->parse_multiple_info($strAnswerText, true);
									break;

									case 'file':
										$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

										foreach($result as $r)
										{
											$strAnswerText = "<a href='".$r->guid."'>".$r->post_title."</a>";
										}
									break;

									default:
										if($strCheckCode != '')
										{
											switch($strCheckCode)
											{
												case 'url':
													$strAnswerText = "<a href='".$strAnswerText."'>".$strAnswerText."</a>";
												break;

												case 'email':
													$strAnswerText = "<a href='mailto:".$strAnswerText."?subject=".__("Re", 'lang_form').": ".$strFormName."'>".$strAnswerText."</a>";

													if($item['answerSpam'] == false)
													{
														$actions['spam'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerSpam&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_spam_'.$intAnswerID, '_wpnonce_answer_spam')."' rel='confirm'>".__("Mark as Spam", 'lang_form')."</a>";
													}
												break;

												case 'zip':
													if(get_bloginfo('language') == "sv-SE")
													{
														include_once("class_zipcode.php");
														$obj_zipcode = new mf_zipcode();

														$actions['zip'] = $obj_zipcode->get_city($strAnswerText);
													}
												break;
											}
										}
									break;
								}
							}

							if($strAnswerText != '')
							{
								$strAnswerText = stripslashes(stripslashes($strAnswerText));

								if(substr($strAnswerText, 0, 2) == "--")
								{
									$strAnswerText = "<span class='grey nowrap'>".$strAnswerText."</span>";
								}

								switch($strFormTypeCode)
								{
									case 'checkbox':
									case 'radio_button':
									//case 'checkbox_multiple':
									//case 'radio_multiple':
										if($strAnswerText == 1)
										{
											$out .= "<i class='fa fa-check green'></i>";
										}

										else if($strAnswerText == 0)
										{
											$out .= "<i class='fa fa-times red'></i>";
										}

										else
										{
											$out .= $strAnswerText;
										}
									break;

									default:
										$out .= $strAnswerText;
									break;
								}
							}

							if($obj_form->answer_column == 0)
							{
								$actions['edit'] = "<a href='".admin_url("admin.php?page=mf_form/view/index.php&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID)."'>".__("Edit", 'lang_form')."</a>";
								$actions['delete'] = "<a href='#delete/answer/".$intAnswerID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";

								$obj_form->answer_column++;
							}

							$out .= $this->row_actions($actions);
						}
					}
				break;
			}

			return $out;
		}
	}
}

class mf_form_output
{
	function __construct($data)
	{
		$this->id = isset($data['id']) ? $data['id'] : 0;
		$this->answer_id = isset($data['answer_id']) ? $data['answer_id'] : 0;

		$this->row = $data['result'];
		$this->query_prefix = $data['query_prefix'];
		$this->output = $this->answer_text = "";
		$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = $this->show_template_info = false;
		$this->in_edit_mode = $data['in_edit_mode'];
	}

	function calculate_value()
	{
		global $wpdb, $obj_form;

		if($this->answer_id > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d' LIMIT 0, 1", $this->row->form2TypeID, $this->answer_id));

			foreach($result as $r)
			{
				switch($this->row->formTypeCode)
				{
					case 'radio_button':
						$this->answer_text = $this->row->form2TypeID;
					break;

					case 'datepicker':
						if(!isset($obj_form))
						{
							$obj_form = new mf_form();
						}

						$this->answer_text = $obj_form->pre_format_date($r->answerText);
					break;

					default:
						$this->answer_text = stripslashes(stripslashes($r->answerText));
					break;
				}
			}
		}

		if($this->answer_text == '')
		{
			$this->answer_text = check_var($this->query_prefix.$this->row->form2TypeID, 'char');
		}
	}

	function filter_form_fields(&$field_data)
	{
		if($this->row->formTypeFetchFrom != '' && (!isset($field_data['value']) || $field_data['value'] == ''))
		{
			if(substr($this->row->formTypeFetchFrom, 0, 1) == "[" && is_user_logged_in())
			{
				$user_data = get_userdata(get_current_user_id());

				switch($this->row->formTypeFetchFrom)
				{
					case '[user_display_name]':
						$field_data['value'] = $user_data->display_name;
					break;

					case '[user_email]':
						$field_data['value'] = $user_data->user_email;
					break;

					case '[user_address]':
						$profile_address_street = get_the_author_meta('profile_address_street', $user_data->ID);
						$profile_address_zipcode = get_the_author_meta('profile_address_zipcode', $user_data->ID);
						$profile_address_city = get_the_author_meta('profile_address_city', $user_data->ID);

						$field_data['value'] = $profile_address_street.", ".$profile_address_zipcode." ".$profile_address_city;
					break;
				}
			}

			else
			{
				if(isset($_GET[$this->row->formTypeFetchFrom]) && $_GET[$this->row->formTypeFetchFrom] != '')
				{
					$field_data['value'] = check_var($this->row->formTypeFetchFrom);
				}

				else
				{
					$field_data['value'] = $this->row->formTypeFetchFrom;
				}
			}
		}
	}

	function check_limit($data)
	{
		global $wpdb, $obj_form;

		if(isset($data['array'][2]) && $data['array'][2] > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE formID = '%d' AND form2TypeID = '%d' AND answerText = %s AND answerSpam = '0' GROUP BY answerID", $this->id, $data['form2TypeID'], $data['array'][0]));
			$answer_rows = $wpdb->num_rows;

			if($answer_rows >= $data['array'][2])
			{
				$data['array'][0] = "disabled_".$data['array'][0];
				$data['array'][1] .= " (".__("Full", 'lang_form').")";
			}

			else
			{
				$data['array'][1] .= " (".($data['array'][2] - $answer_rows)." / ".$data['array'][2]." ".__("left", 'lang_form').")";
			}
		}

		return $data['array'];
	}

	function get_options_for_select($string)
	{
		global $wpdb, $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		if($obj_form->form_option_exists)
		{
			@list($this->label, $str_select) = explode(":", $string);

			$form2type_id_temp = $obj_form->get_type_connect_to_root(array('connect_to' => $this->row->formTypeConnectTo, 'field_id' => $this->row->form2TypeID));

			$result = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionKey, formOptionValue, formOptionLimit, formOptionAction FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $form2type_id_temp));

			$arr_data = array();

			foreach($result as $r)
			{
				$arr_option = $this->check_limit(array('array' => array($r->formOptionID, $r->formOptionValue, $r->formOptionLimit), 'form2TypeID' => $form2type_id_temp));

				if($r->formOptionAction > 0)
				{
					$arr_option[1] = array(
						'name' => $arr_option[1],
						'attributes' => array(
							'data-action' => $this->query_prefix.$r->formOptionAction,
						),
					);

					$this->row->has_action = true;
				}

				$arr_data[$arr_option[0]] = $arr_option[1];
			}
		}

		else
		{
			list($this->label, $str_select) = explode(":", $string);
			$arr_options = explode(",", $str_select);

			$arr_data = array();

			foreach($arr_options as $str_option)
			{
				$arr_option = explode("|", $str_option);
				$arr_option = $this->check_limit(array('array' => $arr_option, 'form2TypeID' => $this->row->form2TypeID));

				$arr_data[$arr_option[0]] = $arr_option[1];
			}
		}

		return $arr_data;
	}

	function get_form_fields()
	{
		global $intFormTypeID2_temp, $intForm2TypeID2_temp, $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		/*if(!isset($data['show_label'])){		$data['show_label'] = true;}
		if(!isset($data['ignore_required'])){	$data['ignore_required'] = false;}

		if($data['ignore_required'] == true)
		{
			$this->row->formTypeRequired = false;
		}*/

		$field_data = array(
			'name' => $this->query_prefix.$this->row->form2TypeID,
		);

		$class_output = $this->row->formTypeClass != '' ? " class='".$this->row->formTypeClass."'" : "";
		$class_output_small = ($this->row->formTypeClass != '' ? " ".$this->row->formTypeClass : "");

		switch($this->row->formTypeCode)
		{
			case 'checkbox':
				$is_first_checkbox = false;

				if($this->row->formTypeID != $intFormTypeID2_temp)
				{
					$intForm2TypeID2_temp = $this->row->form2TypeID;

					$is_first_checkbox = true;
				}

				if($this->row->formTypeActionShow > 0)
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_display";
					$field_data['xtra'] = "data-equals='".$this->row->formTypeActionEquals."' data-display='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				else if(isset($this->row->has_action) && $this->row->has_action)
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_action";
				}

				$field_data['text'] = $this->row->formTypeText;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['value'] = 1;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->formTypeClass.($is_first_checkbox ? ($this->row->formTypeClass != '' ? " " : "")."clear" : "");

				$this->output .= show_checkbox($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			case 'range':
				$arr_content = explode("|", $this->row->formTypeText);

				if($this->answer_text == '' && isset($arr_content[3]))
				{
					$this->answer_text = $arr_content[3];
				}

				$field_data['text'] = $arr_content[0]." (<span>".$this->answer_text."</span>)";
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = "min='".$arr_content[1]."' max='".$arr_content[2]."'".($this->row->formTypeAutofocus ? " autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['type'] = "range";

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = true;
			break;

			case 'datepicker':
				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['type'] = "date";
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			case 'radio_button':
				$is_first_radio = false;

				if($this->row->formTypeID != $intFormTypeID2_temp)
				{
					$intForm2TypeID2_temp = $this->row->form2TypeID;

					$is_first_radio = true;
				}

				if(isset($_POST["radio_".$intForm2TypeID2_temp]))
				{
					$this->answer_text = check_var($_POST["radio_".$intForm2TypeID2_temp], 'int', false);
				}

				else if($this->answer_text == '' && $this->row->formTypeRequired == 1)
				{
					$this->answer_text = $this->row->form2TypeID;
				}

				$field_data['name'] = "radio_".$intForm2TypeID2_temp;
				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->row->form2TypeID;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->formTypeClass.($is_first_radio ? ($this->row->formTypeClass != '' ? " " : "")."clear" : "");

				$this->output .= show_radio_input($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			case 'radio_multiple':
				if($this->row->formTypeActionShow > 0)
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_display";
					$field_data['xtra'] = "data-equals='".$this->row->formTypeActionEquals."' data-display='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				else if(isset($this->row->has_action) && $this->row->has_action)
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_action";
				}

				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);
				$field_data['text'] = $this->label;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_form_alternatives($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			case 'select':
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);
				$field_data['text'] = $this->label;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['xtra'] = "";

				if($this->row->formTypeConnectTo > 0)
				{
					$field_data['class'] .= " form_connect_to";
					$field_data['xtra'] .= ($field_data['xtra'] != '' ? " " : "")."data-connect_to='".$this->query_prefix.$this->row->formTypeConnectTo."'";
				}

				if($this->row->formTypeActionShow > 0)
				{
					$field_data['class'] .= ($field_data['class'] != '' ? " " : "")."form_display";
					$field_data['xtra'] = ($field_data['xtra'] != '' ? " " : "")."data-equals='".$this->row->formTypeActionEquals."' data-display='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				else if(isset($this->row->has_action) && $this->row->has_action)
				{
					$field_data['class'] .= ($field_data['class'] != '' ? " " : "")."form_action";
				}

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			case 'select_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);
				$field_data['text'] = $this->label;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;
				$field_data['xtra'] = "class='multiselect'";

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			case 'checkbox_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);
				$field_data['text'] = $this->label;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_form_alternatives($field_data);

				$this->show_required = true;
			break;

			case 'input_field':
				if($this->row->checkCode == "zip")
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_zipcode";
				}

				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->answer_text;
				$field_data['maxlength'] = 200;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['type'] = $this->row->checkCode;
				$field_data['placeholder'] = $this->row->formTypePlaceholder;
				$field_data['pattern'] = $this->row->checkPattern;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			case 'textarea':
				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			case 'text':
				if($this->row->formTypeTag != '')
				{
					$this->output .= "<".$this->row->formTypeTag.$class_output." id='".$field_data['name']."'>"
						.$this->row->formTypeText
					."</".$this->row->formTypeTag.">";
				}

				else
				{
					$this->output .= "<div".$class_output." id='".$field_data['name']."'>
						<p>".$this->row->formTypeText."</p>
					</div>";
				}
			break;

			case 'space':
				$this->output .= $this->in_edit_mode == true ? "<p class='grey".$class_output_small."'>(".__("Space", 'lang_form').")</p>" : "<p".$class_output.">&nbsp;</p>";
			break;

			case 'referer_url':
				$referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey".$class_output_small."'>".__("Hidden", 'lang_form')." (".$this->row->formTypeText.": '".$referer_url."')</p>";
				}

				else
				{
					$field_data['value'] = $referer_url;

					$this->output .= input_hidden($field_data);
				}
			break;

			case 'hidden_field':
				$field_data['value'] = ($this->answer_id > 0 ? $this->answer_text : '');

				$this->filter_form_fields($field_data);

				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey".$class_output_small."'>".__("Hidden", 'lang_form')." (".$this->query_prefix.$this->row->form2TypeID.": ".$field_data['value'].")</p>";
				}

				else
				{
					$this->output .= input_hidden($field_data);

					$this->show_copy = $this->show_template_info = true;
				}
			break;

			case 'custom_tag':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;".$this->row->formTypeText.$class_output."&gt;</p>";

					if($this->row->formTypeText == 'fieldset')
					{
						$this->output .= "<p class='grey'>&lt;legend&gt; (".$this->row->formTypePlaceholder.")</p>";
					}
				}

				else
				{
					$this->output .= "<".$this->row->formTypeText.$class_output." id='".$field_data['name']."'>";

					if($this->row->formTypeText == 'fieldset')
					{
						$this->output .= "<legend>".$this->row->formTypePlaceholder."</legend>";
					}
				}
			break;

			case 'custom_tag_end':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;/".$this->row->formTypeText."&gt;</p>";
				}

				else
				{
					$this->output .= "</".$this->row->formTypeText.">";
				}
			break;

			case 'file':
				$field_data['text'] = $this->row->formTypeText;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->output .= show_file_field($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			default:
				do_log("There was no code for this type (".$this->row->formTypeCode.")");
			break;
		}

		$intFormTypeID2_temp = $this->row->formTypeID;
	}

	function get_output($data = array())
	{
		global $wpdb, $obj_form;

		$out = "";

		if(!isset($this->in_edit_mode)){	$this->in_edit_mode = false;}

		if($this->in_edit_mode == true)
		{
			$row_settings = show_checkbox(array('name' => 'display_'.$this->row->form2TypeID, 'text' => __("Display", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeDisplay, 'xtra' => "class='ajax_checkbox' rel='display/type/".$this->row->form2TypeID."'"));

			if($this->show_required == true)
			{
				$row_settings .= show_checkbox(array('name' => 'require_'.$this->row->form2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRequired, 'xtra' => "class='ajax_checkbox' rel='require/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_autofocus == true)
			{
				$row_settings .= show_checkbox(array('name' => 'autofocus_'.$this->row->form2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeAutofocus, 'xtra' => "class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_remember == true)
			{
				$row_settings .= show_checkbox(array('name' => 'remember_'.$this->row->form2TypeID, 'text' => __("Remember Answer", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRemember, 'xtra' => "class='ajax_checkbox remember' rel='remember/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_copy == true)
			{
				$row_settings .= "<a href='".admin_url("admin.php?page=mf_form/create/index.php&btnFieldCopy&intFormID=".$this->id."&intForm2TypeID=".$this->row->form2TypeID)."'>".__("Copy", 'lang_form')."</a>";
			}

			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' LIMIT 0, 1", $this->row->form2TypeID));

			if($wpdb->num_rows == 0)
			{
				$row_settings .= ($this->show_copy == true ? " | " : "")."<a href='#delete/type/".$this->row->form2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
			}

			if($this->show_template_info == true)
			{
				$row_settings .= "<p class='add2condition' rel='".$this->row->form2TypeID."'>".sprintf(__("For use in templates this field has got %s and %s", 'lang_form'), "<a href='#'>[label_".$this->row->form2TypeID."]</a>", "<a href='#'>[answer_".$this->row->form2TypeID."]</a>")."</p>";
			}

			$row_class = "flex_flow";

			if($data['form2type_id'] == $this->row->form2TypeID)
			{
				$row_class .= " active";
			}

			$out .= "<mf-form-row id='type_".$this->row->form2TypeID."' class='".$row_class."'>"
				.$this->output;

				$out .= "<i class='fa fa-eye-slash field_hidden_by_rule' title='".__("The field is hidden by a rule in the form", 'lang_form')."'></i>";

				/*if($this->row->formTypeID != 14)
				//if($this->row->formTypeCode != 'custom_tag_end')
				{*/
					$out .= "<div class='row_icons'>";

						if($row_settings != '')
						{
							$out .= "<i class='fa fa-info-circle blue'></i>";
						}

						$out .= "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id."&intForm2TypeID=".$this->row->form2TypeID)."' title='".__("Edit", 'lang_form')."'><i class='far fa-edit'></i></a>
					</div>";

					if($row_settings != '')
					{
						$out .= "<div class='row_settings'>".$row_settings."</div>";
					}
				//}

				if($this->row->formTypeDisplay == 0)
				{
					$out .= "<i class='fa fa-eye-slash field_hidden_by_creator' title='".__("The field is hidden by the creator of the form", 'lang_form')."'></i>";
				}

				if($this->row->formTypeConnectTo > 0)
				{
					$out .= "<i class='fas fa-link field_connected_to'></i>";
				}

			$out .= "</mf-form-row>";
		}

		else if($this->row->formTypeDisplay == 1)
		{
			$out .= $this->output;
		}

		return $out;
	}
}

class widget_form extends WP_Widget
{
	function __construct()
	{
		$this->obj_form = new mf_form();

		$this->widget_ops = array(
			'classname' => 'form',
			'description' => __("Display a form that you have previously created", 'lang_form'),
		);

		$this->arr_default = array(
			'form_heading' => "",
			'form_id' => "",
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Form", 'lang_form'), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		global $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['form_id'] > 0)
		{
			echo apply_filters('filter_before_widget', $before_widget);

				if($instance['form_heading'] != '')
				{
					$instance['form_heading'] = apply_filters('widget_title', $instance['form_heading'], $instance, $this->id_base);

					echo $before_title
						.$instance['form_heading']
					.$after_title;
				}

				$obj_form->id = $instance['form_id'];

				echo $obj_form->process_form()
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['form_heading'] = sanitize_text_field($new_instance['form_heading']);
		$instance['form_id'] = sanitize_text_field($new_instance['form_id']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('form_heading'), 'text' => __("Heading", 'lang_form'), 'value' => $instance['form_heading'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => $this->obj_form->get_for_select(array('local_only' => true, 'force_has_page' => false)), 'name' => $this->get_field_name('form_id'), 'value' => $instance['form_id']))
		."</div>";
	}
}