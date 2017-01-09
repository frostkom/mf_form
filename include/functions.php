<?php

function count_shortcode_button_form($count)
{
	$obj_form = new mf_form();

	if($obj_form->count_forms(array('post_status' => 'publish')) > 0)
	{
		$count++;
	}

	return $count;
}

function get_shortcode_output_form($out)
{
	$templates = get_posts(array( 
		'post_type' 		=> 'mf_form', 
		'posts_per_page'	=> -1,
		'post_status' 		=> 'publish',
		'order'				=> 'ASC',
		'orderby'			=> 'title'
	));

	if(count($templates) > 0)
	{
		$obj_form = new mf_form();

		$out .= "<h3>".__("Choose a Form", 'lang_form')."</h3>";

		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

		foreach($templates as $template)
		{
			$arr_data[$obj_form->get_form_id($template->ID)] = $template->post_title;
		}

		$out .= show_select(array('data' => $arr_data, 'name' => 'select_form_id', 'xtra' => " rel='mf_form'"));
	}

	return $out;
}

function deleted_user_form($user_id)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
}

function init_form()
{
	mf_enqueue_script('script_forms', plugin_dir_url(__FILE__)."script.js", array('plugins_url' => plugins_url(), 'plugin_url' => plugin_dir_url(__FILE__)));

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

	if(get_option('mf_form_setting_replacement_form') > 0)
	{
		add_filter('the_content', 'my_replace_content');
	}
}

function delete_form($post_id)
{
	global $post_type;

	if($post_type == 'mf_form')
	{
		$mail_to = "martin.fors@frostkom.se";
		$mail_headers = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";
		$mail_content = $mail_subject = "Delete postID (#".$post_id.") from ".$wpdb->base_prefix."query";

		send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content, 'headers' => $mail_headers));

		/*$obj_form = new mf_form();
		$intQueryID = $obj_form->get_form_id($post_id);

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d'", $intQueryID));

		$intAnswerID = $wpdb->get_var($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer WHERE queryID = '%d'", $intQueryID));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d'", $intAnswerID));

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query2answer WHERE queryID = '%d'", $intQueryID));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $intQueryID));*/
	}
}

function get_page_from_form($id)
{
	global $wpdb;

	$arr_out = array();

	$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type != 'revision' AND post_status = 'publish' AND (post_content LIKE '%".addslashes("[mf_form id=".esc_sql($id)."]")."%' OR post_content LIKE '%".addslashes("[form_shortcode id='".esc_sql($id)."']")."%')");

	if($wpdb->num_rows > 0)
	{
		foreach($result as $r)
		{
			$arr_out[] = array(
				'post_id' => $r->ID,
				//'post_url' => get_permalink($r->ID)
			);
		}
	}

	return $arr_out;
}

function get_form_url($form_id)
{
	$out = "#";

	if($form_id > 0)
	{
		$result = get_page_from_form($form_id);

		foreach($result as $r)
		{
			$post_id = $r['post_id'];

			$out = get_permalink($post_id);
		}
	}

	return $out;
}

function preg_email_concat($matches)
{
	$replacement_form = get_option('mf_form_setting_replacement_form');
	$email = $matches[1];

	/*list($before_at, $after_at) = explode("@", $email);
	$before_at = substr($before_at, 0, 1);*/

	//$before_at."***@".$after_at
	return "<a href='#' class='mf_form_link'>".__("Click here to send e-mail", 'lang_form')."</a>
	<div class='mf_form_inline'>"
		.show_query_form(array('query_id' => $replacement_form, 'send_to' => $email))
	."</div>";
}

function my_replace_content($html)
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
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	$arr_settings['setting_redirect_emails'] = __("Redirect all e-mails", 'lang_form');

	if(get_option('setting_redirect_emails') != 'yes')
	{
		$arr_settings['setting_form_test_emails'] = __("Redirect test e-mails", 'lang_form');
	}

	$arr_settings['setting_form_permission'] = __("Role to see forms", 'lang_form');

	if(get_option('setting_form_permission') != '')
	{
		$arr_settings['setting_form_permission_see_all'] = __("Role to see all", 'lang_form');
	}

	$arr_settings['mf_form_setting_replacement_form'] = __("Form to replace all e-mail links", 'lang_form');

	$obj_form = new mf_form();

	if($obj_form->has_template())
	{
		$arr_settings['setting_link_yes_text'] = __("Text to send as positive response", 'lang_form');
		$arr_settings['setting_link_no_text'] = __("Text to send as negative response", 'lang_form');
		$arr_settings['setting_link_thanks_text'] = __("Thank you message after sending response", 'lang_form');
	}

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $options_area);

		register_setting(BASE_OPTIONS_PAGE, $handle);
	}
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

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("When a visitor sends an e-mail through the site it is redirected to the admins address", 'lang_form')));
}

function setting_form_test_emails_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("When an admin is logged in and testing to send e-mails all outgoing e-mails are redirected to the admins address", 'lang_form')));
}

function setting_form_permission_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = get_roles_for_select(array('add_choose_here' => true));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
}

function setting_form_permission_see_all_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = get_roles_for_select(array('add_choose_here' => true));

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
}

function mf_form_setting_replacement_form_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$obj_form = new mf_form();
	$arr_data = $obj_form->get_form_array(false);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("admin.php?page=mf_form/create/index.php")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("If you would like all e-mail links in text to be replaced by a form, choose one here", 'lang_form')));
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

function get_form_xtra($query_xtra = "", $search = "", $prefix = " WHERE", $field_name = "queryName")
{
	global $wpdb;

	$setting_form_permission_see_all = get_option('setting_form_permission_see_all');
	$is_allowed_to_see_all_forms = $setting_form_permission_see_all != '' ? current_user_can($setting_form_permission_see_all) : true;

	if(!$is_allowed_to_see_all_forms)
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : $prefix)." ".$wpdb->base_prefix."query.userID = '".get_current_user_id()."'";
	}

	if($search != '')
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : $prefix)." ".$field_name." LIKE '%".$search."%'";
	}

	return $query_xtra;
}

function get_count_message($id = 0)
{
	global $wpdb;

	$count_message = "";

	$last_viewed = get_user_meta(get_current_user_id(), 'mf_forms_viewed', true);
	$query_xtra = get_form_xtra(" WHERE answerCreated > %s".($id > 0 ? " AND queryID = '".$id."'" : ""));

	$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->base_prefix."query2answer USING (queryID)".$query_xtra, $last_viewed));
	$rows = $wpdb->num_rows;

	if($rows > 0)
	{
		$count_message = "&nbsp;<span class='update-plugins' title='".__("Unread answers", 'lang_form')."'>
			<span>".$rows."</span>
		</span>";
	}

	return $count_message;
}

function menu_form()
{
	global $wpdb;

	$obj_form = new mf_form();

	$count_forms = $obj_form->count_forms();

	$menu_root = 'mf_form/';
	$menu_start = $count_forms > 0 ? $menu_root."list/index.php" : $menu_root."create/index.php";

	$menu_capability = get_option_or_default('setting_form_permission', 'edit_pages');

	$count_message = get_count_message();

	$menu_title = __("Forms", 'lang_form');
	add_menu_page($menu_title, $menu_title.$count_message, $menu_capability, $menu_start, '', 'dashicons-forms');

	if($count_forms > 0)
	{
		$menu_title = __("Add New", 'lang_form');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root.'create/index.php');

		$menu_title = __("Last Answers", 'lang_form');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'answer/index.php');

		$menu_title = __("Edit Last Answer", 'lang_form');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'view/index.php');
	}
}

function notices_form()
{
	global $wpdb, $error_text;

	if(IS_ADMIN)
	{
		$answer_viewed = get_user_meta(get_current_user_id(), 'answer_viewed', true);

		$query_xtra = get_form_xtra(" WHERE answerCreated > %s AND answerSent = '0'");

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryID, COUNT(answerSent) AS answerSent FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->base_prefix."query2answer USING (queryID) INNER JOIN ".$wpdb->base_prefix."query_answer_email USING (answerID)".$query_xtra." GROUP BY queryID", $answer_viewed));

		if($wpdb->num_rows > 0)
		{
			$unsent_links = "";

			foreach($result as $r)
			{
				$intQueryID = $r->queryID;
				$intAnswerSent = $r->answerSent;

				$obj_form = new mf_form($intQueryID);
				$strFormName = $obj_form->get_post_info(array('select' => 'post_title'));

				$unsent_links .= ($unsent_links != '' ? ", " : "")."<a href='".admin_url("admin.php?page=mf_form/answer/index.php&intQueryID=".$intQueryID)."'>".$intAnswerSent." ".__("in", 'lang_form')." ".$strFormName."</a>";
			}

			$error_text = __("There were unsent messages", 'lang_form')." (".$unsent_links.")";

			echo get_notification();
		}
	}
}

function get_poll_results($data)
{
	global $wpdb;

	$out = "";

	$obj_form = new mf_form($data['query_id']);
	list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(5, 8)));

	foreach($result as $r)
	{
		$intQuery2TypeID2 = $r->query2TypeID;
		$intQueryTypeID2 = $r->queryTypeID;
		$strQueryTypeText2 = $r->queryTypeText;

		$out .= "<div".($intQueryTypeID2 == 8 ? " class='form_radio'" : "").">";

			if($intQueryTypeID2 == 8)
			{
				$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8' AND query2TypeID = '".$intQuery2TypeID2."'", $obj_form->id)); //$data['query_id']

				$intAnswerPercent = round($intAnswerCount / $data['total_answers'] * 100);

				$out .= "<div style='width: ".$intAnswerPercent."%'>&nbsp;</div>";
			}

			$out .= "<p>"
				.$strQueryTypeText2;

				if($intQueryTypeID2 == 8)
				{
					$out .= "<span>".$intAnswerPercent."%</span>";
				}

			$out .= "</p>
		</div>";
	}

	return $out;
}

function mf_form_mail($data)
{
	global $wpdb;

	//if(!isset($data['headers'])){	$data['headers'] = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";}

	$out = "";

	if(is_user_logged_in() && IS_ADMIN && get_option('setting_form_test_emails') == 'yes')
	{
		$user_data = get_userdata(get_current_user_id());

		$data['subject'] = __("Test", 'lang_form')." (".$data['to']."): ".$data['subject'];
		$data['to'] = $user_data->user_email;
	}

	else if(get_option('setting_redirect_emails') == 'yes')
	{
		$data['subject'] = __("Test", 'lang_form')." (".$data['to']."): ".$data['subject'];
		$data['to'] = get_bloginfo('admin_email');
	}

	$mail_sent = send_email($data);

	if(isset($data['answer_id']))
	{
		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer_email SET answerID = '%d', answerEmail = %s, answerSent = '%d'", $data['answer_id'], $data['to'], $mail_sent));
	}

	return $out;
}

################################
function show_query_form($data)
{
	global $wpdb, $wp_query, $error_text, $done_text;

	$out = "";

	if(!isset($data['edit'])){			$data['edit'] = false;}
	if(!isset($data['sent'])){			$data['sent'] = false;}
	if(!isset($data['query2type_id'])){	$data['query2type_id'] = 0;}

	$intAnswerID = isset($data['answer_id']) ? $data['answer_id'] : "";

	$strAnswerIP = $_SERVER['REMOTE_ADDR'];

	$obj_form = new mf_form(isset($data['query_id']) && $data['query_id'] > 0 ? $data['query_id'] : 0);

	$payment = new mf_form_payment(array('query_id' => $obj_form->id));

	if(isset($_GET['accept']) || isset($_GET['callback']) || isset($_GET['cancel']))
	{
		$out .= $payment->process_callback();
	}

	else if(isset($_GET['btnFormLinkYes']) || isset($_GET['btnFormLinkNo']))
	{
		$intAnswerID = check_var('answer_id', 'int');
		$intProductID = check_var('product_id', 'int');
		$strAnswerEmail = check_var('answer_email');
		$hash = check_var('hash');

		if($hash == md5(NONCE_SALT."_".$intAnswerID."_".$intProductID))
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
			$mail_to = $obj_form->get_answer_email($intAnswerID);
			$mail_subject = $obj_form->get_form_name();
			$mail_content = (isset($_GET['btnFormLinkYes']) ? get_option('setting_link_yes_text') : get_option('setting_link_no_text'));

			if($mail_content != '')
			{
				$mail_content = nl2br(str_replace("[product]", $mail_from_name, $mail_content));

				$mail_data = array(
					'headers' => "From: ".$mail_from_name." <".$mail_from.">\r\n",
					'to' => $mail_to,
					'subject' => $mail_subject,
					'content' => $mail_content,
				);

				mf_form_mail($mail_data);

				$setting_link_thanks_text = nl2br(get_option_or_default('setting_link_thanks_text', __("The message has been sent!", 'lang_form')));

				$out .= "<p>".$setting_link_thanks_text."</p>
				<p class='grey'>".$mail_content."</p>";
			}

			else
			{
				if(isset($_GET['btnFormLinkYes']))
				{
					$error_text = sprintf(__("There was no content to send. You have to enter text into the field 'Text to send as positive response' in %sMy Settings%s", 'lang_form'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_form")."'>", "</a>");
				}

				else
				{
					$error_text = sprintf(__("There was no content to send. You have to enter text into the field 'Text to send as negative response' in %sMy Settings%s", 'lang_form'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_form")."'>", "</a>");
				}
			}
		}

		else
		{
			$error_text = __("Oops! You don't seam to have the correct link or it has expired", 'lang_form');
		}
	}

	else
	{
		$dup_ip = $obj_form->check_if_duplicate();

		if(isset($_POST['btnFormSubmit']) && wp_verify_nonce($_POST['_wpnonce'], 'form_submit'))
		{
			/*$email_encrypted = check_var('email_encrypted', 'char');

			$is_correct_form = true;
			$log_text = "";

			if($data['query_id'] != $obj_form->id)
			{
				$is_correct_form = false;

				$log_text = $data['query_id']." != ".$obj_form->id;
			}

			if(isset($data['send_to']) && $data['send_to'] != '' && $email_encrypted != hash('sha512', $data['send_to']))
			{
				$is_correct_form = false;

				$log_text = $email_encrypted." != ".hash('sha512', $data['send_to']);
			}*/

			if($obj_form->is_correct_form($data)) //$is_correct_form == true
			{
				$email_from = $error_text = "";
				$arr_email_content = array(
					'fields' => array(),
				);

				$result = $wpdb->get_results($wpdb->prepare("SELECT queryEmailConfirm, queryEmailConfirmPage, queryEmail, queryEmailNotify, queryEmailNotifyPage, queryEmailName, queryMandatoryText, queryPaymentProvider, queryPaymentAmount FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id));
				$r = $result[0];
				$intQueryEmailConfirm = $r->queryEmailConfirm;
				$intQueryEmailConfirmPage = $r->queryEmailConfirmPage;
				$strQueryEmail = $r->queryEmail;
				$intQueryEmailNotify = $r->queryEmailNotify;
				$intQueryEmailNotifyPage = $r->queryEmailNotifyPage;
				$strQueryEmailName = $r->queryEmailName;
				$strQueryMandatoryText = $r->queryMandatoryText;
				$intQueryPaymentProvider = $r->queryPaymentProvider;
				$intQueryPaymentAmount = $r->queryPaymentAmount;

				$strFormName = $obj_form->get_post_info(array('select' => "post_title"));
				$strFormPrefix = $obj_form->get_post_info()."_";

				$dblQueryPaymentAmount_value = 0;

				if($dup_ip == true)
				{
					$data['sent'] = true;
				}

				else
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, checkCode, queryTypeRequired FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $obj_form->id));

					foreach($result as $r)
					{
						$intQuery2TypeID2 = $r->query2TypeID;
						$intQueryTypeID2 = $r->queryTypeID;
						$strQueryTypeText = $r->queryTypeText;
						$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";
						$intQueryTypeRequired = $r->queryTypeRequired;

						if(!isset($arr_email_content['fields'][$intQuery2TypeID2]))
						{
							$arr_email_content['fields'][$intQuery2TypeID2] = array();
						}

						$handle2fetch = $strFormPrefix.$intQuery2TypeID2;

						$strAnswerText = $strAnswerText_send = check_var($handle2fetch, $strCheckCode, true, '', true, 'post');

						if($strAnswerText != '')
						{
							switch($strCheckCode)
							{
								//Add specific check here to prevent HTML in messages
								case 'char':
									if(contains_html($strAnswerText))
									{
										$error_text = __("You are not allowed to enter code in the text fields", 'lang_form');
									}

									else if($intQueryTypeID2 != 9 && contains_urls($strAnswerText))
									{
										do_log(__("The string contained links and was stopped", 'lang_form')." (".$strQueryTypeText.": ".$strAnswerText.")");
										$error_text = __("You are not allowed to enter links in the text fields", 'lang_form');
									}
								break;

								case 'email':
									if($intQueryTypeID2 == 3)
									{
										$email_from = $strAnswerText;
									}
								break;
							}
						}

						switch($intQueryTypeID2)
						{
							case 2:
								list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
							break;

							case 7:
								$strAnswerText_send = format_date($strAnswerText);
							break;

							case 10:
								$arr_content1 = explode(":", $strQueryTypeText);
								$arr_content2 = explode(",", $arr_content1[1]);

								foreach($arr_content2 as $str_content)
								{
									$arr_content3 = explode("|", $str_content);

									if($strAnswerText == $arr_content3[0])
									{
										$strAnswerText_send = $arr_content3[1];
									}
								}

								$strQueryTypeText = $arr_content1[0];
							break;

							case 11:
								$strAnswerText = "";

								if(is_array($_POST[$handle2fetch]))
								{
									foreach($_POST[$handle2fetch] as $value)
									{
										$strAnswerText .= ($strAnswerText != '' ? "," : "").check_var($strFormPrefix.$value, $strCheckCode, false);
									}
								}

								$arr_content1 = explode(":", $strQueryTypeText);
								$arr_content2 = explode(",", $arr_content1[1]);

								$arr_answer_text = explode(",", str_replace($strFormPrefix, "", $strAnswerText));

								$strAnswerText_send = "";

								foreach($arr_content2 as $str_content)
								{
									$arr_content3 = explode("|", $str_content);

									if(in_array($arr_content3[0], $arr_answer_text))
									{
										$strAnswerText_send .= ($strAnswerText_send != '' ? ", " : "").$arr_content3[1];
									}
								}

								$strQueryTypeText = $arr_content1[0];
							break;

							case 15:
								if(isset($_FILES[$handle2fetch]))
								{
									$file_name = $_FILES[$handle2fetch]['name'];
									$file_location = $_FILES[$handle2fetch]['tmp_name'];
									$file_mime = $_FILES[$handle2fetch]['type'];

									if($file_name == '')
									{
										if($intQueryTypeRequired == true)
										{
											$error_text = __("You have to submit a file", 'lang_form');
										}
									}

									else if(!is_uploaded_file($file_location))
									{
										if($intQueryTypeRequired == true)
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
								if($strCheckCode != '')
								{
									switch($strCheckCode)
									{
										case 'zip':
											$city_name = $obj_form->get_city_from_zip($strAnswerText);

											if($city_name != '')
											{
												$arr_email_content['fields'][$intQuery2TypeID2]['xtra'] = ", ".$city_name;
											}
										break;
									}
								}
							break;
						}

						if($strQueryTypeText != '')
						{
							$arr_email_content['fields'][$intQuery2TypeID2]['label'] = $strQueryTypeText;
						}

						if($strAnswerText != '')
						{
							if($intQueryPaymentAmount == $intQuery2TypeID2)
							{
								$dblQueryPaymentAmount_value = $strAnswerText;
							}

							$arr_query[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '[answer_id]', query2TypeID = '%d', answerText = %s", $intQuery2TypeID2, $strAnswerText);

							if($strAnswerText_send != '')
							{
								$arr_email_content['fields'][$intQuery2TypeID2]['value'] = $strAnswerText_send;
							}
						}

						else if($intQueryTypeID2 == 8)
						{
							$strAnswerText_radio = isset($_POST["radio_".$intQuery2TypeID2]) ? check_var($_POST["radio_".$intQuery2TypeID2], 'int', false) : '';

							if($strAnswerText_radio != '')
							{
								$arr_query[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '[answer_id]', query2TypeID = %s, answerText = ''", $strAnswerText_radio);

								$strQueryTypeText_temp = $wpdb->get_var($wpdb->prepare("SELECT queryTypeText FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $strAnswerText_radio));

								if(!isset($arr_email_content['fields'][$strAnswerText_radio]))
								{
									$arr_email_content['fields'][$strAnswerText_radio] = array();
								}

								$arr_email_content['fields'][$strAnswerText_radio]['value'] = "x";
							}
						}

						else if($intQueryTypeRequired == true && !in_array($intQueryTypeID2, array(5, 6, 9)) && $error_text == '')
						{
							$error_text = ($strQueryMandatoryText != '' ? $strQueryMandatoryText : __("Please, enter all required fields", 'lang_form'))." (".$strQueryTypeText.")";
						}
					}
				}

				if($error_text == '' && $data['sent'] == false && isset($arr_query))
				{
					$updated = true;

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2answer SET queryID = '%d', answerIP = %s, answerCreated = NOW()", $obj_form->id, $strAnswerIP));
					$intAnswerID = $wpdb->insert_id;

					$email_content_temp = apply_filters('filter_form_on_submit', array('answer_id' => $intAnswerID, 'mail_from' => $email_from, 'mail_subject' => ($strQueryEmailName != "" ? $strQueryEmailName : $strFormName), 'notify_page' => $intQueryEmailNotifyPage, 'arr_mail_content' => $arr_email_content));

					if($error_text == '')
					{
						if(isset($email_content_temp['arr_mail_content']) && count($email_content_temp['arr_mail_content']) > 0)
						{
							$arr_email_content = $email_content_temp['arr_mail_content'];
						}

						if($intAnswerID > 0)
						{
							foreach($arr_query as $query)
							{
								$wpdb->query(str_replace("[answer_id]", $intAnswerID, $query));

								if($wpdb->rows_affected == 0)
								{
									$updated = false;
								}
							}
						}

						else
						{
							$updated = false;
						}

						if($updated == true)
						{
							$answer_data = "";

							if(isset($data['send_to']) && $data['send_to'] != '')
							{
								$mail_subject = $strQueryEmailName != "" ? $strQueryEmailName : $strFormName;
								$mail_content = $obj_form->render_mail_content(array('array' => $arr_email_content));

								$mail_data = array(
									'to' => $data['send_to'],
									'subject' => $mail_subject,
									'content' => $mail_content,
									'answer_id' => $intAnswerID,
								);

								if($email_from != '')
								{
									$mail_data['headers'] = "From: ".$email_from." <".$email_from.">\r\n";
								}

								$answer_data .= ($answer_data != '' ? ", " : "").mf_form_mail($mail_data);
							}

							if($intQueryEmailNotify == 1 && $strQueryEmail != '')
							{
								$mail_subject = $strQueryEmailName != "" ? $strQueryEmailName : $strFormName;
								
								$page_content_data = array(
									'page_id' => $intQueryEmailNotifyPage,
									'answer_id' => $intAnswerID,
									'mail_to' => $strQueryEmail,
									'subject' => $mail_subject,
									'content' => $arr_email_content,
								);

								list($mail_subject, $mail_content) = $obj_form->get_page_content_for_email($page_content_data);

								$mail_data = array(
									'to' => $strQueryEmail,
									'subject' => $mail_subject,
									'content' => $mail_content,
									'answer_id' => $intAnswerID,
								);

								if($email_from != '')
								{
									$mail_data['headers'] = "From: ".$email_from." <".$email_from.">\r\n";
								}

								$answer_data .= ($answer_data != '' ? ", " : "").mf_form_mail($mail_data);
							}

							if($intQueryEmailConfirm == 1 && isset($email_from) && $email_from != '')
							{
								$mail_subject = $strQueryEmailName != "" ? $strQueryEmailName : $strFormName;

								$page_content_data = array(
									'page_id' => $intQueryEmailConfirmPage,
									'answer_id' => $intAnswerID,
									'mail_to' => $email_from,
									'subject' => $mail_subject,
									'content' => $arr_email_content,
								);

								list($mail_subject, $mail_content) = $obj_form->get_page_content_for_email($page_content_data);

								$mail_data = array(
									'to' => $email_from,
									'subject' => $mail_subject,
									'content' => $mail_content,
									'answer_id' => $intAnswerID,
								);

								if($strQueryEmail != '')
								{
									$mail_data['headers'] = "From: ".$strQueryEmail." <".$strQueryEmail.">\r\n";
								}

								$answer_data .= ($answer_data != '' ? ", " : "").mf_form_mail($mail_data);
							}

							if($answer_data != '')
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '0', answerText = %s", $intAnswerID, $answer_data));
							}

							if($intQueryPaymentProvider > 0 && $dblQueryPaymentAmount_value > 0)
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '0', answerText = %s", $intAnswerID, "101: ".__("Sent to processing")));

								$intQueryPaymentTest = isset($_POST['intQueryPaymentTest']) && is_user_logged_in() && IS_ADMIN ? 1 : 0;

								$out .= $payment->process_passthru(array('amount' => $dblQueryPaymentAmount_value, 'orderid' => $intAnswerID, 'test' => $intQueryPaymentTest));
							}

							else
							{
								$data['sent'] = true;
							}
						}
					}
				}
			}

			/*else if($obj_form->log_text != '')
			{
				do_log(__("The form wasn't sent correctly", 'lang_form'))." (".$obj_form->log_text.")";
			}*/
		}

		$obj_font_icons = new mf_font_icons();

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryShowAnswers, queryAnswerURL, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryEmailCheckConfirm FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id));

		foreach($result as $r)
		{
			$intQueryShowAnswers = $r->queryShowAnswers;
			$strQueryAnswerURL = $r->queryAnswerURL;
			$strQueryButtonText = $r->queryButtonText != '' ? $r->queryButtonText : __("Submit", 'lang_form');
			$strQueryButtonSymbol = $obj_font_icons->get_symbol_tag($r->queryButtonSymbol);
			$intQueryPaymentProvider = $r->queryPaymentProvider;
			$strQueryEmailCheckConfirm = $r->queryEmailCheckConfirm;

			$strFormPrefix = $obj_form->get_post_info()."_";

			if($strQueryAnswerURL != '' && preg_match("/_/", $strQueryAnswerURL))
			{
				list($blog_id, $intQueryAnswerURL) = explode("_", $strQueryAnswerURL);
			}

			else
			{
				$blog_id = 0;
				$intQueryAnswerURL = $strQueryAnswerURL;
			}

			$dteFormDeadline = $obj_form->meta(array('action' => 'get', 'key' => 'deadline'));

			if($data['edit'] == false && ($data['sent'] == true || $dup_ip == true))
			{
				$out .= "<div class='mf_form mf_form_results'>";

					$data['total_answers'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8'", $obj_form->id));

					if($intQueryShowAnswers == 1 && $data['total_answers'] > 0)
					{
						$out .= get_poll_results($data);
					}

					else if($intQueryAnswerURL > 0)
					{
						//Switch to temp site
						####################
						$wpdbobj = clone $wpdb;
						$wpdb->blogid = $blog_id;
						$wpdb->set_prefix($wpdb->base_prefix);
						####################

						if($intQueryAnswerURL != $wp_query->post->ID || !isset($wp_query->post->ID))
						{
							$strQueryAnswerURL = get_permalink($intQueryAnswerURL);

							mf_redirect($strQueryAnswerURL);
						}

						//Switch back to orig site
						###################
						$wpdb = clone $wpdbobj;
						###################
					}

					else
					{
						$out .= "<h2>".__("Thank you!", 'lang_form')."</h2>";
					}

				$out .= "</div>";
			}

			else if($dteFormDeadline > DEFAULT_DATE && $dteFormDeadline < date("Y-m-d"))
			{
				$out .= "<p>".__("This form is not open for submissions anymore", 'lang_form')."</p>";
			}

			else if($out == '')
			{
				$cols = $data['edit'] == true ? 5 : 2;

				$result = $obj_form->get_form_type_result();
				$intTotalRows = $wpdb->num_rows;

				if($intTotalRows > 0)
				{
					$out .= "<form method='post' action='' id='form_".$obj_form->id."' class='mf_form".($data['edit'] == true ? " mf_sortable" : "")."' enctype='multipart/form-data'>";

						if($data['edit'] == false)
						{
							$out .= get_notification();
						}

						$i = 1;

						$intQueryTypeID2_temp = $intQuery2TypeID2_temp = "";

						$has_required_email = false;

						foreach($result as $r)
						{
							$r->queryTypeText = stripslashes($r->queryTypeText);

							$obj_form_output = new mf_form_output(array('result' => $r, 'in_edit_mode' => $data['edit'], 'query_prefix' => $strFormPrefix, 'email_check_confirm' => $strQueryEmailCheckConfirm));

							$obj_form_output->calculate_value($intAnswerID);
							$obj_form_output->get_form_fields();

							$out .= $obj_form_output->get_output($data);

							$i++;
						}

						if($intAnswerID > 0)
						{
							$out .= show_button(array('name' => "btnFormUpdate", 'text' => __("Update", 'lang_form')))
							.input_hidden(array('name' => 'intQueryID', 'value' => $obj_form->id))
							.input_hidden(array('name' => 'intAnswerID', 'value' => $intAnswerID));
						}

						else if($data['edit'] == false)
						{
							$out .= apply_filters('filter_form_after_fields', '')
							."<div class='form_button_container'>
								<div class='form_button'>";

									if($has_required_email)
									{
										$out .= "<div class='updated hide'><p>".__("Does the e-mail address look right?", 'lang_form')." ".$strQueryButtonText." ".__("or", 'lang_form')." <a href='#' class='show_none_email'>".__("Change", 'lang_form')."</a></p></div>";
									}

									$out .= show_button(array('name' => "btnFormSubmit", 'text' => $strQueryButtonSymbol.$strQueryButtonText, 'class' => ($has_required_email ? "has_required_email" : "")))
									.show_button(array('type' => "button", 'name' => "btnFormClear", 'text' => __("Clear", 'lang_form'), 'class' => "button-secondary hide"));

									if(is_user_logged_in() && IS_ADMIN)
									{
										if($intQueryPaymentProvider > 0)
										{
											$out .= show_checkbox(array('name' => "intQueryPaymentTest", 'text' => __("Perform test payment", 'lang_form'), 'value' => 1));
										}

										//$out .= "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intQueryID=".$obj_form->id)."' class='button button-secondary'>".__("Edit this form", 'lang_form')."</a>";
									}

									if(isset($data['send_to']) && $data['send_to'] != '')
									{
										$out .= input_hidden(array('name' => 'email_encrypted', 'value' => hash('sha512', $data['send_to'])));
									}

								$out .= "</div>"
								.wp_nonce_field('form_submit', '_wpnonce', true, false)
								.input_hidden(array('name' => 'intQueryID', 'value' => $obj_form->id))
							."</div>";
						}

					$out .= "</form>";
				}
			}
		}
	}

	$out .= get_notification();

	return $out;
}
################################