<?php

function get_site_language($data) //sv_SE, en_US etc.
{
	if(!isset($data['type'])){	$data['type'] = "";}
	if(!isset($data['uc'])){	$data['uc'] = true;}

	if(preg_match("/\_/", $data['language']))
	{
		$arr_language = explode("_", $data['language']);
	}

	else
	{
		$arr_language = explode("-", $data['language']);
	}

	$out = "";

	if($data['type'] == "first")
	{
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
	}

	else if($data['type'] == "last")
	{
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
	}

	else
	{
		$out = $data['language'];
	}

	return $out;
}

function hextostr($hex)
{
	$string = "";

	foreach(explode("\n", trim(chunk_split($hex, 2))) as $h)
	{
		$string .= chr(hexdec($h));
	}

	return $string;
}

function get_hmac_prepared_string($array)
{
	$string = "";

	ksort($array);

	foreach($array as $key => $value)
	{
		if($key != "MAC")
		{
			if(strlen($string) > 1)
			{
				$string .= "&";
			}

			$string .= $key."=".$value;
		}
	}

	return $string;
}

function cron_form()
{
	global $wpdb;

	$setting_form_clear_spam = get_option_or_default('setting_form_clear_spam', 6);

	$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE answerSpam = '1' AND answerCreated < DATE_SUB(NOW(), INTERVAL %d MONTH)", $setting_form_clear_spam));

	if($wpdb->num_rows > 0)
	{
		foreach($result as $r)
		{
			$intAnswerID = $r->answerID;

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = %d", $intAnswerID));
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE answerID = %d", $intAnswerID));
		}
	}
}

function count_shortcode_button_form($count)
{
	if($count == 0)
	{
		$obj_form = new mf_form();

		if($obj_form->count_forms(array('post_status' => 'publish')) > 0)
		{
			$count++;
		}
	}

	return $count;
}

function get_shortcode_output_form($out)
{
	/*$templates = get_posts(array(
		'post_type' => 'mf_form',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'order' => 'ASC',
		'orderby' => 'title'
	));*/

	$tbl_group = new mf_form_table();

	$tbl_group->select_data(array(
		//'select' => "*",
		//'debug' => true,
	));

	//if(count($templates) > 0)
	if(count($tbl_group->data) > 0)
	{
		$obj_form = new mf_form();

		$out .= "<h3>".__("Choose a Form", 'lang_form')."</h3>";

		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

		/*foreach($templates as $template)
		{
			$arr_data[$obj_form->get_form_id($template->ID)] = $template->post_title;
		}*/

		foreach($tbl_group->data as $r)
		{
			$arr_data[$obj_form->get_form_id($r['ID'])] = $r['post_title'];
		}

		$out .= show_select(array('data' => $arr_data, 'xtra' => "rel='mf_form'"));
	}

	return $out;
}

function get_shortcode_list_form($data)
{
	$post_id = $data[0];
	$content_list = $data[1];

	if($post_id > 0)
	{
		$obj_form = new mf_form();
		$obj_form->get_form_id_from_post_content($post_id);

		if($obj_form->id > 0)
		{
			$content_list .= "<li><a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'>".$obj_form->get_form_name()."</a> <span class='grey'>[mf_form id=".$obj_form->id."]</span></li>";
		}
	}

	return array($post_id, $content_list);
}

function deleted_user_form($user_id)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
}

function init_form()
{
	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	//$setting_form_reload = get_option_or_default('setting_form_reload', 'yes');

	mf_enqueue_style('style_form', $plugin_include_url."style.css", $plugin_version);
	mf_enqueue_script('script_form', $plugin_include_url."script.js", array('ajax_url' => admin_url('admin-ajax.php'), 'plugins_url' => plugins_url(), 'plugin_url' => $plugin_include_url, 'please_wait' => __("Please wait", 'lang_form')), $plugin_version); //, 'reload' => $setting_form_reload

	$labels = array(
		'name' => _x(__("Forms", 'lang_form'), 'post type general name'),
		'singular_name' => _x(__("Form", 'lang_form'), 'post type singular name'),
		'menu_name' => __("Forms", 'lang_form')
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'exclude_from_search' => true,
		'show_in_menu' => false,
		'rewrite' => array(
			'slug' => "form",
		),
	);

	register_post_type('mf_form', $args);

	if(get_option('setting_replacement_form') > 0)
	{
		mf_enqueue_style('style_form_replacement', $plugin_include_url."style_replacement.css", $plugin_version);

		add_filter('the_content', 'the_content_form');
	}

	//Can be removed later
	global $wpdb;

	$wpdb->get_results("SHOW TABLES LIKE '".$wpdb->base_prefix."form'");

	if($wpdb->num_rows == 0)
	{
		activate_form();
	}
}

function shortcode_form($atts)
{
	extract(shortcode_atts(array(
		'id' => ''
	), $atts));

	$obj_form = new mf_form($id);

	return $obj_form->process_form();
}

function submit_form()
{
	global $error_text;

	$result = array();

	$obj_form = new mf_form();
	$obj_form->dup_ip = $obj_form->check_if_duplicate();

	do_log("This should not be used because btnFormSubmit is not in POST");

	if(isset($_POST['btnFormSubmit'])) // && wp_verify_nonce($_POST['_wpnonce'], 'form_submit_'.$obj_form->id)
	{
		$result['output'] = $obj_form->process_submit();

		if($error_text != '')
		{
			$result['output'] .= get_notification();
		}

		else
		{
			$result['output'] .= $obj_form->get_form(array('do_redirect' => false));

			if(isset($obj_form->redirect_url) && $obj_form->redirect_url != '')
			{
				$result['redirect'] = $obj_form->redirect_url;
			}

			$result['success'] = true;
		}
	}

	/*else
	{
		$obj_form->process_submit_fallback();

		$result['error'] = __("I could not validate the form submission correctly. If the problem persists, contact an admin", 'lang_form');
	}*/

	echo json_encode($result);
	die();
}

function delete_form($post_id)
{
	global $post_type;

	if($post_type == 'mf_form')
	{
		do_log("Delete postID (#".$post_id.") from ".$wpdb->base_prefix."form");

		/*$obj_form = new mf_form();
		$obj_form->get_form_id($post_id);

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d'", $obj_form->id));

		$intAnswerID = $wpdb->get_var($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d'", $obj_form->id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d'", $intAnswerID));

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d'", $obj_form->id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $obj_form->id));*/
	}
}

/*function get_page_from_form($id)
{
	global $wpdb;

	$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type != 'revision' AND post_status = 'publish' AND (post_content LIKE '%".addslashes("[mf_form id=".esc_sql($id)."]")."%' OR post_content LIKE '%".addslashes("[form_shortcode id='".esc_sql($id)."']")."%')");

	return $result;
}*/

function get_form_url($form_id)
{
	$out = "#";

	if($form_id > 0)
	{
		/*$result = get_page_from_form($form_id);

		foreach($result as $r)
		{
			$post_id = $r->ID;

			$out = get_permalink($post_id);
		}*/

		foreach(get_pages_from_shortcode("[mf_form id=".$form_id."]") as $post_id)
		{
			$out = get_permalink($post_id);
		}
	}

	return $out;
}

function preg_email_concat($matches)
{
	$setting_replacement_form = get_option('setting_replacement_form');
	$setting_replacement_form_text = get_option_or_default('setting_replacement_form_text', __("Click here to send e-mail", 'lang_form'));

	$email = $matches[1];

	$obj_form = new mf_form($setting_replacement_form);

	$form_md5 = md5("form_link_".$email."_".mt_rand(1, 1000));

	$out = "<a href='#' class='form_link' rel='".$form_md5."'>".$setting_replacement_form_text."</a>
	<div id='inline_form_".$form_md5."' class='form_inline hide'>"
		.$obj_form->process_form(array('send_to' => $email))
	."</div>";

	return $out;
}

function the_content_form($html)
{
	$char_before = "?<=^|\s|\(|\[";
	$chars = "[-A-Za-z\d_.]+[@][A-Za-z\d_-]+([.][A-Za-z\d_-]+)*[.][A-Za-z]{2,8}";
	$char_after = "?=\s|$|\)|\'|\!|(\?)|\.|\]|\<|\[|;";

	$html = preg_replace("/(".$char_before.")(".$chars.")(".$char_after.")/", "<a href='mailto:$1'>$1</a>", $html);
	$html = preg_replace_callback("/<a.*?href=['\"]mailto:(.*?)['\"]>.*?<\/a>/i", "preg_email_concat", $html);

	return $html;
}

function settings_form()
{
	global $wpdb;

	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_redirect_emails'] = __("Redirect all e-mails", 'lang_form');
	$arr_settings['setting_form_test_emails'] = __("Redirect test e-mails", 'lang_form');
	$arr_settings['setting_form_permission_see_all'] = __("Role to see all", 'lang_form');
	$arr_settings['setting_form_spam'] = __("Spam Filter", 'lang_form');

	//$arr_settings['setting_form_reload'] = __("Reload page on form submission", 'lang_form');

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

	$obj_form = new mf_form();

	if($obj_form->has_template())
	{
		$arr_settings['setting_link_yes_text'] = __("Text to send as positive response", 'lang_form');
		$arr_settings['setting_link_no_text'] = __("Text to send as negative response", 'lang_form');
		$arr_settings['setting_link_thanks_text'] = __("Thank you message after sending response", 'lang_form');
	}

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
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

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("When a visitor sends an e-mail through the site it is redirected to the admins address", 'lang_form')));
}

function setting_form_test_emails_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("When an admin is logged in and testing to send e-mails all outgoing e-mails are redirected to the admins address", 'lang_form')));
}

function setting_form_permission_see_all_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = get_roles_for_select(array('add_choose_here' => true));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
}

function setting_form_spam_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, array('email', 'filter', 'honeypot'));

	$arr_data = array(
		'email' => __("Recurring E-mail", 'lang_form'),
		'filter' => __("HTML & Links", 'lang_form'),
		'honeypot' => __("Honeypot", 'lang_form'),
	);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
}

function setting_replacement_form_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$obj_form = new mf_form();
	$arr_data = $obj_form->get_form_array(array('force_has_page' => false));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("If you would like all e-mail links in text to be replaced by a form, choose one here", 'lang_form')));
}

function setting_replacement_form_text_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Click here to send e-mail", 'lang_form')));
}

/*function setting_form_reload_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}*/

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
		'textarea_rows' => 5,
		'statusbar' => false,
	));
}

function setting_link_no_text_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
		'class' => "hide_media_button hide_tabs",
		'mini_toolbar' => true,
		'textarea_rows' => 5,
		'statusbar' => false,
	));
}

function setting_link_thanks_text_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_wp_editor(array('name' => $setting_key, 'value' => $option,
		'class' => "hide_media_button hide_tabs",
		'mini_toolbar' => true,
		'textarea_rows' => 5,
		'statusbar' => false,
	));
}

function widgets_form()
{
	register_widget('widget_form');
}

function get_form_xtra($query_xtra = "", $search = "", $prefix = " WHERE", $field_name = "formName")
{
	global $wpdb;

	$setting_form_permission_see_all = get_option('setting_form_permission_see_all');
	$is_allowed_to_see_all_forms = $setting_form_permission_see_all != '' ? current_user_can($setting_form_permission_see_all) : true;

	if(!$is_allowed_to_see_all_forms)
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : $prefix)." ".$wpdb->base_prefix."form.userID = '".get_current_user_id()."'";
	}

	if($search != '')
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : $prefix)." ".$field_name." LIKE '%".$search."%'";
	}

	return $query_xtra;
}

function get_count_answer_message($data = array()) //$id = 0
{
	global $wpdb;

	if(!isset($data['form_id'])){		$data['form_id'] = 0;}
	if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}
	if(!isset($data['return_type'])){	$data['return_type'] = 'html';}

	$out = "";

	$last_viewed = get_user_meta($data['user_id'], 'mf_forms_viewed', true);

	$query_xtra = get_form_xtra(" WHERE answerCreated > %s".($data['form_id'] > 0 ? " AND formID = '".$data['form_id']."'" : ""))
		." AND (blogID = '".$wpdb->blogid."' OR blogID IS null)"
		." AND answerSpam = '0'";

	$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->base_prefix."form2answer USING (formID)".$query_xtra, $last_viewed));
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

	else if(!($data['form_id'] > 0) && IS_SUPER_ADMIN)
	{
		$result = $wpdb->get_results("SELECT answerCreated FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->base_prefix."form2answer USING (formID) ORDER BY answerCreated DESC LIMIT 0, 2"); //$wpdb->prepare( WHERE (blogID = '%d' OR blogID IS null), $wpdb->blogid)

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

			$date_diff_old = time_between_dates(array('start' => $dteAnswerOld, 'end' => $dteAnswerNew, 'type' => 'round', 'return' => 'minutes'));
			$date_diff_new = time_between_dates(array('start' => $dteAnswerNew, 'end' => date("Y-m-d H:i:s"), 'type' => 'round', 'return' => 'minutes'));

			if($date_diff_new > ($date_diff_old * 2) && $date_diff_new > (60 * 24 * 2))
			{
				$message_temp = sprintf(__("There are no answers since %s", 'lang_form'), format_date($dteAnswerNew));

				switch($data['return_type'])
				{
					default:
					case 'html':
						$out = "&nbsp;<span title='".$message_temp."'>
							<i class='fa fa-warning yellow'></i>
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

	return $out;
}

function menu_form()
{
	$obj_form = new mf_form();
	$count_forms = $obj_form->count_forms();

	$menu_root = 'mf_form/';
	$menu_start = $count_forms > 0 ? $menu_root."list/index.php" : $menu_root."create/index.php";

	$menu_capability = 'edit_pages';

	$count_message = $count_forms > 0 ? get_count_answer_message() : "";

	$menu_title = __("Forms", 'lang_form');
	add_menu_page("", $menu_title.$count_message, $menu_capability, $menu_start, '', 'dashicons-forms');

	$menu_title = __("List", 'lang_form');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

	if($count_forms > 0)
	{
		$menu_title = __("Add New", 'lang_form');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'create/index.php');

		$menu_title = __("Answers", 'lang_form');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'answer/index.php');

		$menu_title = __("Edit Answer", 'lang_form');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'view/index.php');
	}
}

function get_poll_results($data)
{
	global $wpdb;

	$out = "";

	$obj_form = new mf_form($data['form_id']);
	list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(5, 8))); //'text', 'radio_button'

	foreach($result as $r)
	{
		$intForm2TypeID2 = $r->form2TypeID;
		$intFormTypeID2 = $r->formTypeID;
		$strFormTypeText2 = $r->formTypeText;

		$out .= "<div".($intFormTypeID2 == 8 ? " class='form_radio'" : "").">";

			if($intFormTypeID2 == 8)
			{
				$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) WHERE formID = '%d' AND formTypeID = '8' AND form2TypeID = '".$intForm2TypeID2."'", $obj_form->id));

				$intAnswerPercent = round($intAnswerCount / $data['total_answers'] * 100);

				$out .= "<div style='width: ".$intAnswerPercent."%'>&nbsp;</div>";
			}

			$out .= "<p>"
				.$strFormTypeText2;

				if($intFormTypeID2 == 8)
				{
					$out .= "<span>".$intAnswerPercent."%</span>";
				}

			$out .= "</p>
		</div>";
	}

	return $out;
}

function phpmailer_init_form($phpmailer)
{
	if(is_user_logged_in() && IS_ADMIN && get_option('setting_form_test_emails') == 'yes')
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