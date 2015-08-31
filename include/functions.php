<?php

function init_form()
{
	wp_enqueue_style('font-awesome', "//netdna.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.css");
	wp_enqueue_style('style_forms', plugins_url()."/mf_form/include/style.css");

	mf_enqueue_script('script_forms', plugins_url()."/mf_form/include/script.js", array('plugins_url' => plugins_url()));

	$labels = array(
		'name' => _x(__("Forms", 'lang_forms'), 'post type general name'),
		'singular_name' => _x(__("Form", 'lang_forms'), 'post type singular name'),
		'menu_name' => __("Forms", 'lang_forms')
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
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

function get_page_from_form($id)
{
	global $wpdb;

	$arr_out = array();

	$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type != 'revision' AND post_status = 'publish' AND (post_content LIKE '%".addslashes("[mf_form id=".$id."]")."%' OR post_content LIKE '%".addslashes("[form_shortcode id='".$id."']")."%')");

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
	return "<a href='#' class='mf_form_link'>".__("Click here to send e-mail", 'lang_forms')."</a>
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
	$options_page = "settings_mf_base";
	$options_area = "mf_form_setting";

	add_settings_section(
		$options_area,
		__("Forms", 'lang_forms'),
		$options_area."_callback",
		$options_page
	);

	$arr_settings = array(
		"setting_form_permission" => __("Lowest user permission", 'lang_forms'),
		"setting_form_permission_see_all" => __("Lowest user permission to see all forms", 'lang_forms'),
		"mf_form_setting_replacement_form" => __("Form to replace all e-mail links", 'lang_forms'),
	);

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", $options_page, $options_area);

		register_setting($options_page, $handle);
	}
}

function mf_form_setting_callback(){}

function setting_form_permission_callback()
{
	global $wpdb;

	$option = get_option('setting_form_permission');
	$roles = get_all_roles();

	echo "<label>
		<select name='setting_form_permission'>
			<option value=''>-- ".__("Choose here", 'lang_forms')." --</option>";

			foreach($roles as $key => $value)
			{
				$key = get_role_first_capability($key);

				echo "<option value='".$key."'".($key == $option ? " selected" : "").">".__($value)."</option>";
			}

		echo "</select>
	</label>";
}

function setting_form_permission_see_all_callback()
{
	global $wpdb;

	$option = get_option('setting_form_permission_see_all');
	$roles = get_all_roles();

	echo "<label>
		<select name='setting_form_permission_see_all'>
			<option value=''>-- ".__("Choose here", 'lang_forms')." --</option>";

			foreach($roles as $key => $value)
			{
				$key = get_role_first_capability($key);

				echo "<option value='".$key."'".($key == $option ? " selected" : "").">".__($value)."</option>";
			}

		echo "</select>
	</label>";
}

function mf_form_setting_replacement_form_callback()
{
	global $wpdb;

	$is_super_admin = current_user_can('install_plugins');

	$option = get_option('mf_form_setting_replacement_form');

	echo "<label>
		<select name='mf_form_setting_replacement_form'>
			<option value=''>-- ".__("Choose here", 'lang_forms')." --</option>";

			$result = $wpdb->get_results("SELECT queryID, queryName FROM ".$wpdb->base_prefix."query WHERE queryDeleted = '0'".($is_super_admin ? "" : " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)")." ORDER BY queryCreated DESC");

			foreach($result as $r)
			{
				echo "<option value='".$r->queryID."'".($option == $r->queryID ? " selected" : "").">".$r->queryName."</option>";
			}

		echo "</select><br>
		<span class='description'>".__("If you would like all e-mail links in text to be replaced by a form, choose one here", 'lang_forms')."</span>
	</label>";
}

function widgets_form()
{
	register_widget('widget_form');
}

function get_form_xtra($query_xtra = "", $search = "")
{
	global $wpdb;

	$is_admin = current_user_can('install_plugins');

	$setting_form_permission_see_all = get_option('setting_form_permission_see_all');
	$is_allowed_to_see_all_forms = $setting_form_permission_see_all != '' ? current_user_can($setting_form_permission_see_all) : true;

	if(!$is_admin)
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : " WHERE")." (blogID = '".$wpdb->blogid."' OR blogID IS null)";
	}

	if(!$is_allowed_to_see_all_forms)
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : " WHERE")." ".$wpdb->base_prefix."query.userID = '".get_current_user_id()."'";
	}

	if($search != '')
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : " WHERE")." queryName LIKE '%".$search."%'";
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
		$count_message = "&nbsp;<span class='update-plugins' title='".__("Unread answers", 'lang_forms')."'>
			<span>".$rows."</span>
		</span>";
	}

	return $count_message;
}

function menu_forms()
{
	global $wpdb;

	$menu_root = 'mf_form/';
	$menu_start = $menu_root.'list/index.php';
	//$menu_capability = "edit_pages";

	$menu_capability = get_option('setting_form_permission', 'edit_pages');

	$count_message = get_count_message();

	add_menu_page(__("Forms", 'lang_forms'), __("Forms", 'lang_forms').$count_message, $menu_capability, $menu_start, '', 'dashicons-forms');

	add_submenu_page($menu_start, __("Add New", 'lang_forms'), __("Add New", 'lang_forms'), $menu_capability, $menu_root.'create/index.php');
	add_submenu_page($menu_start, __("Last Answers", 'lang_forms'), "", $menu_capability, $menu_root.'answer/index.php');
	add_submenu_page($menu_start, __("Edit Last Answer", 'lang_forms'), "", $menu_capability, $menu_root.'view/index.php');
}

function check_if_duplicate($data)
{
	global $wpdb;

	$strAnswerIP = $_SERVER['REMOTE_ADDR'];

	$dup_ip = false;

	$is_poll = is_poll(array('query_id' => $data['query_id']));

	if($is_poll)
	{
		$rowsIP = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2answer WHERE queryID = '%d' AND answerIP = %s LIMIT 0, 1", $data['query_id'], $strAnswerIP));

		if($rowsIP > 0)
		{
			$dup_ip = true;
		}
	}

	else
	{
		$rowsIP = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2answer WHERE queryID = '%d' AND answerIP = %s AND answerCreated > DATE_SUB(NOW(), INTERVAL 30 SECOND) LIMIT 0, 1", $data['query_id'], $strAnswerIP));

		if($rowsIP > 0)
		{
			$dup_ip = true;
		}
	}

	return $dup_ip;
}

function get_poll_results($data)
{
	global $wpdb;

	$out = "";

	$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND (queryTypeID = '5' OR queryTypeID = '8') ORDER BY query2TypeOrder ASC, query2TypeCreated ASC", $data['query_id']));
	$intTotalRows = $wpdb->num_rows;

	if($intTotalRows > 0)
	{
		foreach($result as $r)
		{
			$intQuery2TypeID2 = $r->query2TypeID;
			$intQueryTypeID2 = $r->queryTypeID;
			$strQueryTypeText2 = $r->queryTypeText;

			$out .= "<div".($intQueryTypeID2 == 8 ? " class='form_radio'" : "").">";

				if($intQueryTypeID2 == 8)
				{
					$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8' AND query2TypeID = '".$intQuery2TypeID2."'", $data['query_id']));

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
	}

	return $out;
}

function is_poll($data)
{
	global $wpdb;

	$not_poll_content_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(query2TypeID) FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND queryTypeID != '5' AND queryTypeID != '8' LIMIT 0, 1", $data['query_id']));

	return ($not_poll_content_amount == 0 ? true : false);
}

function mf_form_mail($data)
{
	global $wpdb;

	$out = "";

	add_filter('wp_mail_content_type', 'set_html_content_type');
	$data['content'] = nl2br($data['content']);

	$mail_sent = wp_mail($data['to'], $data['subject'], $data['content'], $data['headers']);

	$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer_email SET answerID = '%d', answerEmail = %s, answerSent = '%d'", $data['answer_id'], $data['to'], $mail_sent));

	return $out;
}

################################
function show_query_form($data)
{
	global $wpdb, $wp_query, $error_text, $done_text;

	$out = $intAnswerID = "";

	if(!isset($data['edit'])){			$data['edit'] = false;}
	if(!isset($data['sent'])){			$data['sent'] = false;}
	if(!isset($data['query2type_id'])){	$data['query2type_id'] = 0;}

	if(isset($data['answer_id']))
	{
		$intAnswerID = $data['answer_id'];
	}

	$strAnswerIP = $_SERVER['REMOTE_ADDR'];

	$intQueryID = check_var('intQueryID');

	$payment = new mf_form_payment(array('query_id' => $data['query_id']));

	if(isset($_GET['accept']) || isset($_GET['callback']) || isset($_GET['cancel']))
	{
		$out .= $payment->process_callback();
	}

	else
	{
		$dup_ip = check_if_duplicate(array('query_id' => $intQueryID));

		if(isset($_POST['btnFormSubmit']) && wp_verify_nonce($_POST['_wpnonce'], 'form_submit'))
		{
			$email_encrypted = check_var('email_encrypted', 'char');
			
			$is_correct_form = $data['query_id'] == $intQueryID ? true : false;

			if(isset($data['send_to']) && $data['send_to'] != '' && $email_encrypted != hash('sha512', $data['send_to']))
			{
				$is_correct_form = false;
			}

			if($is_correct_form == true)
			{
				$email_from = $email_content = $error_text = "";

				$result = $wpdb->get_results($wpdb->prepare("SELECT queryEmailConfirm, queryEmailConfirmPage, queryName, queryURL, queryEmail, queryEmailNotify, queryEmailName, queryMandatoryText, queryPaymentProvider, queryPaymentCheck, queryPaymentAmount FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryID));
				$r = $result[0];
				$intQueryEmailConfirm = $r->queryEmailConfirm;
				$strQueryEmailConfirmPage = $r->queryEmailConfirmPage;
				$strQueryName = $r->queryName;
				$strQueryPrefix = $r->queryURL != '' ? $r->queryURL."_" : "field_";
				$strQueryEmail = $r->queryEmail;
				$intQueryEmailNotify = $r->queryEmailNotify;
				$strQueryEmailName = $r->queryEmailName;
				$strQueryMandatoryText = $r->queryMandatoryText;
				$intQueryPaymentProvider = $r->queryPaymentProvider;
				$intQueryPaymentCheck = $r->queryPaymentCheck;
				$intQueryPaymentAmount = $r->queryPaymentAmount;

				$intQueryPaymentCheck_value = $dblQueryPaymentAmount_value = 0;

				if($dup_ip == true)
				{
					$data['sent'] = true;
				}

				else
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, checkCode, queryTypeRequired FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' ORDER BY query2TypeOrder ASC, query2TypeCreated ASC", $intQueryID));

					foreach($result as $r)
					{
						$intQuery2TypeID2 = $r->query2TypeID;
						$intQueryTypeID2 = $r->queryTypeID;
						$strQueryTypeText = $r->queryTypeText;
						$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";
						$intQueryTypeRequired = $r->queryTypeRequired;

						$var = $var_send = check_var($strQueryPrefix.$intQuery2TypeID2, $strCheckCode, true, '', true, 'post');

						if($var != '' && $intQueryTypeID2 == 3 && $strCheckCode == 'email')
						{
							$email_from = $var;
						}

						if($intQueryTypeID2 == 2)
						{
							list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
						}

						else if($intQueryTypeID2 == 7)
						{
							$var_send = wp_date_format(array('date' => $var));
						}

						else if($intQueryTypeID2 == 10)
						{
							$arr_content1 = explode(":", $strQueryTypeText);
							$arr_content2 = explode(",", $arr_content1[1]);

							foreach($arr_content2 as $str_content)
							{
								$arr_content3 = explode("|", $str_content);

								if($var == $arr_content3[0])
								{
									$var_send = $arr_content3[1];
								}
							}

							$strQueryTypeText = $arr_content1[0];
						}

						else if($intQueryTypeID2 == 11)
						{
							$var = "";

							if(is_array($_POST[$strQueryPrefix.$intQuery2TypeID2]))
							{
								foreach($_POST[$strQueryPrefix.$intQuery2TypeID2] as $value)
								{
									$var .= ($var != '' ? "," : "").check_var($strQueryPrefix.$value, $strCheckCode, false);
								}
							}

							$arr_content1 = explode(":", $strQueryTypeText);
							$arr_content2 = explode(",", $arr_content1[1]);

							$arr_answer_text = explode(",", str_replace($strQueryPrefix, "", $var));

							$var_send = "";

							foreach($arr_content2 as $str_content)
							{
								$arr_content3 = explode("|", $str_content);

								if(in_array($arr_content3[0], $arr_answer_text))
								{
									$var_send .= ($var_send != '' ? ", " : "").$arr_content3[1];
								}
							}

							$strQueryTypeText = $arr_content1[0];
						}

						$email_content .= "\n";

						if($strQueryTypeText != '')
						{
							$email_content .= $strQueryTypeText.(substr($strQueryTypeText, -1) == ":" ? "" : ":")." ";
						}

						if($var != '')
						{
							if($intQueryPaymentCheck == $intQuery2TypeID2)
							{
								$intQueryPaymentCheck_value = $var;
							}

							if($intQueryPaymentAmount == $intQuery2TypeID2)
							{
								$dblQueryPaymentAmount_value = $var;
							}

							$arr_query[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '[answer_id]', query2TypeID = '%d', answerText = %s", $intQuery2TypeID2, $var);

							if($var_send != '')
							{
								$email_content .= " ".$var_send;
							}
								
							$email_content .= "\n";
						}

						else if($intQueryTypeID2 == 8)
						{
							$var_radio = isset($_POST["radio_".$intQuery2TypeID2]) ? check_var($_POST["radio_".$intQuery2TypeID2], 'int', false) : '';

							if($var_radio != '')
							{
								$arr_query[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '[answer_id]', query2TypeID = %s, answerText = ''", $var_radio);
							}

							$strQueryTypeText_temp = $wpdb->get_var($wpdb->prepare("SELECT queryTypeText FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $var_radio));

							$email_content .= ($strQueryTypeText_temp == $strQueryTypeText ? " x" : "")."\n";
						}

						else if($intQueryTypeRequired == true && !in_array($intQueryTypeID2, array(5, 6, 9)) && $error_text == '')
						{
							$error_text = ($strQueryMandatoryText != '' ? $strQueryMandatoryText : __("Please, enter all required fields", 'lang_forms'))." (".$strQueryTypeText.")";

							$email_content .= "\n";
						}

						else
						{
							$email_content .= "\n";
						}
					}
				}

				if($error_text == '' && $data['sent'] == false && isset($arr_query))
				{
					$updated = true;

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2answer (queryID, answerIP, answerCreated) VALUES (%d, %s, NOW())", $intQueryID, $strAnswerIP));

					$intAnswerID = $wpdb->insert_id;

					//do_action('action_form_on_submit');
					apply_filters('filter_form_on_submit', array('answer_id' => $intAnswerID, 'mail_from' => $email_from, 'mail_subject' => ($strQueryEmailName != "" ? $strQueryEmailName : $strQueryName), 'mail_content' => $email_content));

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
							if($email_from != '')
							{
								$mail_headers = "From: ".$email_from." <".$email_from.">\r\n";
							}

							else
							{
								$mail_headers = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";
							}

							$mail_subject = $strQueryEmailName != "" ? $strQueryEmailName : $strQueryName;
							$mail_content = strip_tags($email_content);

							$answer_data .= ($answer_data != '' ? ", " : "").mf_form_mail(array('to' => $data['send_to'], 'subject' => $mail_subject, 'content' => $mail_content, 'headers' => $mail_headers, 'answer_id' => $intAnswerID));
						}

						if($intQueryEmailNotify == 1 && $strQueryEmail != '' && isset($email_content) && $email_content != '')
						{
							if($email_from != '')
							{
								$mail_headers = "From: ".$email_from." <".$email_from.">\r\n";
							}

							else
							{
								$mail_headers = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";
							}

							$mail_subject = $strQueryEmailName != "" ? $strQueryEmailName : $strQueryName;
							$mail_content = strip_tags($email_content);

							$answer_data .= ($answer_data != '' ? ", " : "").mf_form_mail(array('to' => $strQueryEmail, 'subject' => $mail_subject, 'content' => $mail_content, 'headers' => $mail_headers, 'answer_id' => $intAnswerID));
						}

						if($intQueryEmailConfirm == 1 && isset($email_from) && $email_from != '')
						{
							if($strQueryEmail != '')
							{
								$mail_headers = "From: ".$strQueryEmail." <".$strQueryEmail.">\r\n";
							}

							else
							{
								$mail_headers = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";
							}

							$mail_subject = $strQueryName;
							$mail_content = strip_tags($email_content);

							if($strQueryEmailConfirmPage > 0)
							{
								list($blog_id, $strQueryEmailConfirmPage) = explode("_", $strQueryEmailConfirmPage);

								//Switch to temp site
								####################
								$wpdbobj = clone $wpdb;
								$wpdb->blogid = $blog_id;
								$wpdb->set_prefix($wpdb->base_prefix);
								####################

								if($strQueryEmailConfirmPage != $wp_query->post->ID)
								{
									$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_content FROM ".$wpdb->posts." WHERE ID = '%d'", $strQueryEmailConfirmPage));

									foreach($result as $r)
									{
										$mail_subject = $r->post_title;
										$mail_content = apply_filters('the_content', $r->post_content);

										add_filter('wp_mail_content_type', 'set_html_content_type');
									}
								}

								//Switch back to orig site
								###################
								$wpdb = clone $wpdbobj;
								###################
							}

							$answer_data .= ($answer_data != '' ? ", " : "").mf_form_mail(array('to' => $email_from, 'subject' => $mail_subject, 'content' => $mail_content, 'headers' => $mail_headers, 'answer_id' => $intAnswerID));
						}

						if($answer_data != '')
						{
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '0', answerText = %s", $intAnswerID, $answer_data));
						}

						if($intQueryPaymentProvider > 0 && $intQueryPaymentCheck_value > 0 && $dblQueryPaymentAmount_value > 0)
						{
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '0', answerText = '101 (Sent to processing)'", $intAnswerID));

							$intQueryPaymentTest = isset($_POST['intQueryPaymentTest']) && is_user_logged_in() && current_user_can('manage_options') ? 1 : 0;

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

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryURL, queryShowAnswers, queryAnswerURL, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryImproveUX FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $data['query_id']));
		$r = $result[0];
		$strQueryPrefix = $r->queryURL != '' ? $r->queryURL."_" : "field_";
		$intQueryShowAnswers = $r->queryShowAnswers;
		$strQueryAnswerURL = $r->queryAnswerURL;
		$strQueryButtonText = $r->queryButtonText != '' ? $r->queryButtonText : __("Submit", 'lang_forms');
		$strQueryButtonSymbol = $r->queryButtonSymbol != '' ? "<i class='fa fa-".$r->queryButtonSymbol."'></i> " : "";
		$intQueryPaymentProvider = $r->queryPaymentProvider;
		$intQueryImproveUX = $r->queryImproveUX;

		if($strQueryAnswerURL != '' && preg_match("/_/", $strQueryAnswerURL))
		{
			list($blog_id, $intQueryAnswerURL) = explode("_", $strQueryAnswerURL);
		}

		else
		{
			$blog_id = 0;
			$intQueryAnswerURL = $strQueryAnswerURL;
		}

		if($data['edit'] == false && ($data['sent'] == true || $dup_ip == true))
		{
			$out .= "<div class='mf_form mf_form_results'>";

				$data['total_answers'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8'", $data['query_id']));

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

					if($intQueryAnswerURL != $wp_query->post->ID)
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
					$out .= "<h2>".__("Thank you!", 'lang_forms')."</h2>";
				}

			$out .= "</div>";
		}

		else if($out == '')
		{
			$cols = $data['edit'] == true ? 5 : 2;

			$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, checkCode, checkPattern, queryTypeText, queryTypePlaceholder, queryTypeRequired, queryTypeAutofocus, queryTypeClass, query2TypeOrder FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' GROUP BY ".$wpdb->base_prefix."query2type.query2TypeID ORDER BY query2TypeOrder ASC, query2TypeCreated ASC", $data['query_id']));
			$intTotalRows = $wpdb->num_rows;

			if($intTotalRows > 0)
			{
				$out .= "<form method='post' action='' id='form_".$data['query_id']."' class='mf_form".($data['edit'] == true ? " mf_sortable" : "").($intQueryImproveUX == 1 ? " mf_improve_ux" : "")."'>";

					if($data['edit'] == false)
					{
						$out .= get_notification();
					}

					$i = 1;

					$intQueryTypeID2_temp = $intQuery2TypeID2_temp = "";

					$has_required_email = false;

					foreach($result as $r)
					{
						$intQuery2TypeID2 = $r->query2TypeID;
						$intQueryTypeID2 = $r->queryTypeID;
						$strCheckCode = $r->checkCode;
						$strCheckPattern = $r->checkPattern;
						$strQueryTypeText2 = stripslashes($r->queryTypeText);
						$strQueryTypePlaceholder = $r->queryTypePlaceholder;
						$intQueryTypeRequired = $r->queryTypeRequired;
						$intQueryTypeAutofocus = $r->queryTypeAutofocus;
						$strQueryTypeClass = $r->queryTypeClass;
						$intQuery2TypeOrder = $r->query2TypeOrder;

						$this_is_required_email = $intQueryTypeID2 == 3 && $strCheckCode == 'email' && $intQueryTypeRequired == 1;

						if($this_is_required_email)
						{
							$has_required_email = true;
						}

						$strAnswerText = "";

						if($intAnswerID > 0)
						{
							$resultInfo = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d' LIMIT 0, 1", $intQuery2TypeID2, $intAnswerID));
							$rowsInfo = $wpdb->num_rows;

							if($rowsInfo > 0)
							{
								$r = $resultInfo[0];
								$strAnswerText = $r->answerText;
							}
						}

						if($strAnswerText == '')
						{
							$strAnswerText = check_var($strQueryPrefix.$intQuery2TypeID2, 'char');
						}

						if($data['edit'] == true)
						{
							$out .= "<div id='type_".$intQuery2TypeID2."' class='form_row".($data['query2type_id'] == $intQuery2TypeID2 ? " active" : "")."'>";
						}

							$show_required = $show_autofocus = false;

							switch($intQueryTypeID2)
							{
								//Checkbox
								case 1:
									$is_first_checkbox = false;

									if($intQueryTypeID2 != $intQueryTypeID2_temp)
									{
										$intQuery2TypeID2_temp = $intQuery2TypeID2;

										$is_first_checkbox = true;
									}

									$out .= show_checkbox(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'text' => $strQueryTypeText2, 'required' => $intQueryTypeRequired, 'value' => 1, 'compare' => $strAnswerText, 'xtra_class' => $strQueryTypeClass.($is_first_checkbox ? " clear" : "")));

									$show_required = true;
								break;

								//Input range
								case 2:
									$arr_content = explode("|", $strQueryTypeText2);

									if($strAnswerText == '' && isset($arr_content[3]))
									{
										$strAnswerText = $arr_content[3];
									}

									$out .= show_textfield(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'text' => $arr_content[0]." (<span>".$strAnswerText."</span>)", 'value' => $strAnswerText, 'required' => $intQueryTypeRequired, 'xtra' => "min='".$arr_content[1]."' max='".$arr_content[2]."'".($intQueryTypeAutofocus ? " autofocus" : ""), 'xtra_class' => $strQueryTypeClass, 'type' => "range"));

									$show_required = $show_autofocus = true;
								break;

								//Input date
								case 7:
									$out .= show_textfield(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'text' => $strQueryTypeText2, 'value' => $strAnswerText, 'required' => $intQueryTypeRequired, 'xtra' => ($intQueryTypeAutofocus ? "autofocus" : ""), 'xtra_class' => $strQueryTypeClass, 'type' => "date", 'placeholder' => $strQueryTypePlaceholder));

									$show_required = $show_autofocus = true;
								break;

								//Radio button
								case 8:
									$is_first_radio = false;

									if($intQueryTypeID2 != $intQueryTypeID2_temp)
									{
										$intQuery2TypeID2_temp = $intQuery2TypeID2;

										$is_first_radio = true;
									}

									if(isset($_POST["radio_".$intQuery2TypeID2_temp]))
									{
										$strAnswerText = check_var($_POST["radio_".$intQuery2TypeID2_temp], 'int', false);
									}

									else if($strAnswerText == '' && $intQueryTypeRequired == 1)
									{
										$strAnswerText = $intQuery2TypeID2;
									}

									$out .= show_radio_input(array('name' => "radio_".$intQuery2TypeID2_temp, 'label' => $strQueryTypeText2, 'value' => $intQuery2TypeID2, 'compare' => $strAnswerText, 'xtra_class' => $strQueryTypeClass.($is_first_radio ? " clear" : "")));

									$show_required = true;
								break;

								//Select
								case 10:
									$arr_content1 = explode(":", $strQueryTypeText2);
									$arr_content2 = explode(",", $arr_content1[1]);

									$arr_data = array();

									foreach($arr_content2 as $str_content)
									{
										$arr_content3 = explode("|", $str_content);

										$arr_data[] = array($arr_content3[0], $arr_content3[1]);
									}

									$out .= show_select(array('data' => $arr_data, 'name' => $strQueryPrefix.$intQuery2TypeID2, 'text' => $arr_content1[0], 'compare' => $strAnswerText, 'required' => $intQueryTypeRequired, 'class' => $strQueryTypeClass));

									$show_required = true;
								break;

								//Select (multiple)
								case 11:
									$arr_content1 = explode(":", $strQueryTypeText2);
									$arr_content2 = explode(",", $arr_content1[1]);

									$arr_data = array();

									foreach($arr_content2 as $str_content)
									{
										$arr_content3 = explode("|", $str_content);

										$arr_data[] = array($arr_content3[0], $arr_content3[1]);
									}

									$out .= show_select(array('data' => $arr_data, 'name' => $strQueryPrefix.$intQuery2TypeID2."[]", 'text' => $arr_content1[0], 'compare' => $strAnswerText, 'required' => $intQueryTypeRequired, 'class' => $strQueryTypeClass));

									$show_required = true;
								break;

								//Textfield
								case 3:
									$out .= show_textfield(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'text' => $strQueryTypeText2, 'value' => $strAnswerText, 'maxlength' => 200, 'required' => $intQueryTypeRequired, 'xtra' => ($intQueryTypeAutofocus ? "autofocus" : ""), 'xtra_class' => $strQueryTypeClass.($strCheckCode == "zip" ? " form_zipcode" : "").($this_is_required_email ? " this_is_required_email" : ""), 'type' => $strCheckCode, 'placeholder' => $strQueryTypePlaceholder, 'pattern' => $strCheckPattern));

									$show_required = $show_autofocus = true;
								break;

								//Textarea
								case 4:
									$out .= show_textarea(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'text' => $strQueryTypeText2, 'value' => $strAnswerText, 'required' => $intQueryTypeRequired, 'xtra' => ($intQueryTypeAutofocus ? "autofocus" : ""), 'class' => $strQueryTypeClass, 'placeholder' => $strQueryTypePlaceholder));

									$show_required = $show_autofocus = true;
								break;

								//Text
								case 5:
									$out .= "<div".($strQueryTypeClass != '' ? " class='".$strQueryTypeClass."'" : "")."><p>".$strQueryTypeText2."</p></div>";
								break;

								//Space
								case 6:
									$out .= $data['edit'] == true ? "<p class='grey".($strQueryTypeClass != '' ? " ".$strQueryTypeClass : "")."'>(".__("Space", 'lang_forms').")</p>" : "<p".($strQueryTypeClass != '' ? " class='".$strQueryTypeClass."'" : "").">&nbsp;</p>";
								break;

								//Referer URL
								case 9:
									$referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

									if($data['edit'] == true)
									{
										$out .= "<p class='grey".($strQueryTypeClass != '' ? " ".$strQueryTypeClass : "")."'>".__("Hidden", 'lang_forms')." (".$strQueryTypeText2.": '".$referer_url."')</p>";
									}

									else
									{
										$out .= input_hidden(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'value' => $referer_url));
									}
								break;

								//Hidden field
								case 12:
									if($data['edit'] == true)
									{
										$out .= "<p class='grey".($strQueryTypeClass != '' ? " ".$strQueryTypeClass : "")."'>".__("Hidden", 'lang_forms')." (".$strQueryPrefix.$intQuery2TypeID2.": ".$strQueryTypeText2.")</p>";
									}

									else
									{
										$out .= input_hidden(array('name' => $strQueryPrefix.$intQuery2TypeID2, 'value' => ($strAnswerText != '' ? $strAnswerText : $strQueryTypeText2)));
									}
								break;
							}

						if($data['edit'] == true)
						{
							$out .= "<div class='form_buttons'>";

									if($show_required == true)
									{
										$out .= show_checkbox(array('name' => "require_".$intQuery2TypeID2, 'text' => __("Required", 'lang_forms'), 'value' => 1, 'compare' => $intQueryTypeRequired, 'xtra' => " class='ajax_checkbox' rel='require/type/".$intQuery2TypeID2."'"));
									}

									if($show_autofocus == true)
									{
										$out .= show_checkbox(array('name' => "autofocus_".$intQuery2TypeID2, 'text' => __("Autofocus", 'lang_forms'), 'value' => 1, 'compare' => $intQueryTypeAutofocus, 'xtra' => " class='ajax_checkbox autofocus' rel='autofocus/type/".$intQuery2TypeID2."'"));
									}

									$out .= "<a href='?page=mf_form/create/index.php&btnFieldCopy&intQueryID=".$data['query_id']."&intQuery2TypeID=".$intQuery2TypeID2."'>".__("Copy", 'lang_forms')."</a> | 
									<a href='?page=mf_form/create/index.php&intQueryID=".$data['query_id']."&intQuery2TypeID=".$intQuery2TypeID2."'>".__("Edit", 'lang_forms')."</a> | 
									<a href='#delete/type/".$intQuery2TypeID2."' class='ajax_link confirm_link'>".__("Delete", 'lang_forms')."</a>
								</div>
							</div>";
						}

						$i++;

						//Set temp id to check on next row if it is connected radio buttons
						$intQueryTypeID2_temp = $intQueryTypeID2;
					}

					if($intAnswerID > 0)
					{
						$out .= show_submit(array('name' => "btnQueryUpdate", 'text' => __("Update", 'lang_forms')))
						.input_hidden(array('name' => 'intQueryID', 'value' => $data['query_id']))
						.input_hidden(array('name' => 'intAnswerID', 'value' => $intAnswerID));
					}

					else if($data['edit'] == false)
					{
						//do_action('action_form_after_fields');

						$out .= apply_filters('filter_form_after_fields', '')
						."<div class='form_button'>";

							if($has_required_email)
							{
								$out .= "<p class='hide'>".__("Does the e-mail address look right?", 'lang_forms')." ".$strQueryButtonText." ".__("or", 'lang_forms')." <a href='#' class='show_none_email'>".__("Change", 'lang_forms')."</a></p>";
							}

							$out .= show_submit(array('name' => "btnFormSubmit", 'text' => $strQueryButtonSymbol.$strQueryButtonText, 'class' => ($has_required_email ? "has_required_email" : "")))
							.wp_nonce_field('form_submit', '_wpnonce', true, false)
							.input_hidden(array('name' => 'intQueryID', 'value' => $data['query_id']));

							if(isset($data['send_to']) && $data['send_to'] != '')
							{
								$out .= input_hidden(array('name' => 'email_encrypted', 'value' => hash('sha512', $data['send_to'])));
							}

							if(is_user_logged_in() && current_user_can('manage_options'))
							{
								if($intQueryPaymentProvider > 0)
								{
									$out .= show_checkbox(array('name' => "intQueryPaymentTest", 'text' => __("Perform test payment", 'lang_forms'), 'value' => 1));
								}

								$out .= "<a href='".get_site_url()."/wp-admin/admin.php?page=mf_form/create/index.php&intQueryID=".$data['query_id']."'>".__("Edit this form", 'lang_forms')."</a>";
							}

						$out .= "</div>";
					}

				$out .= "</form>";
			}
		}
	}

	return $out;
}
################################

function delete_old_files($data)
{
	$time = time();

	$file = $data['file'];

	if($time - filemtime($file) >= 60 * 60 * 24 * 2) // 2 days
	{
		unlink($file);
	}
}