<?php

class mf_form
{
	var $id;
	var $form2type_id = 0;
	var $post_id = 0;
	var $post_type = __CLASS__;
	var $meta_prefix;
	var $edit_mode = false;
	var $is_spam = false;
	var $is_spam_id = false;
	var $type;
	var $answer_id = '';
	var $prefix = '';
	var $form_atts = [];
	var $answer_column = 0;
	var $label = "";
	var $answer_data = [];
	var $arr_answer_queries = [];
	var $arr_email_content = [];
	var $form_name = "";
	var $form2type_order = "";
	var $email_admin = "";
	var $email_name = "";
	var $type_id = "";
	var $type_text = "";
	var $type_text2 = "";
	var $check_id = "";
	var $type_placeholder = "";
	var $type_tag = "";
	var $type_class = "";
	var $type_length;
	var $type_fetch_from = "";
	var $type_connect_to = "";
	var $type_action_equals = "";
	var $type_action_show = "";
	var $arr_type_select_id = [];
	var $arr_type_select_key = [];
	var $arr_type_select_value = [];
	var $arr_type_select_limit = [];
	var $arr_type_select_action = [];
	var $type_min = "";
	var $type_max = "";
	var $type_default = "";
	var $page_content_data = [];
	var $mail_data = [];
	var $form_nonce_hash;
	var $arr_form_types;
	var $ignore_tags_on_output = array('text', 'space', 'custom_tag', 'custom_tag_end');
	var $arr_form_check;

	function __construct($data = [])
	{
		global $wpdb;

		$this->id = (isset($data['id']) && $data['id'] > 0 ? $data['id'] : check_var('intFormID'));

		$this->meta_prefix = $this->post_type.'_';

		if($this->id > 0)
		{
			$this->get_post_id();
		}

		$this->type = (isset($data['type']) ? $data['type'] : '');

		$this->form_nonce_hash = md5((defined('NONCE_SALT') ? NONCE_SALT : '').'form_nonce_hash_'.apply_filters('get_current_visitor_ip', "").'_'.date("Ymd"));
	}

	function check_allow_edit()
	{
		global $wpdb;

		$out = current_user_can('edit_pages');

		if($out == false && $this->id > 0)
		{
			$this->post_id = $this->get_post_id($this->id);
			$intUserID = $this->get_post_info(array('select' => 'post_author'));

			if($intUserID == get_current_user_id())
			{
				$out = true;
			}
		}

		return $out;
	}

	function delete_form($form_id)
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer WHERE formID = '%d'", $form_id));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$this->delete_answer($r->answerID);
			}
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d'", $form_id));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d'", $r->form2TypeID));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $r->form2TypeID));
			}
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form WHERE formID = '%d'", $form_id));
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			replace_option(array('old' => 'setting_redirect_emails', 'new' => 'setting_form_redirect_emails'));

			// Convert wp_form to wp_posts
			#################################
			if(apply_filters('does_table_exist', false, $wpdb->base_prefix."form") && does_column_exist($wpdb->base_prefix."form", "blogID"))
			{
				$arr_fields_db = $arr_fields_meta = [];

				$arr_fields_db[] = 'formButtonSymbol';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'button_symbol';
				$arr_fields_db[] = 'formButtonText';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'button_text';
				$arr_fields_db[] = 'formMandatoryText';				$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'mandatory_text';
				$arr_fields_db[] = 'formFromName';					$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_admin_name';
				$arr_fields_db[] = 'formEmailName';					$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_name';
				$arr_fields_db[] = 'formEmailNotify';				$arr_fields_db_bool[] = true;		$arr_fields_meta[] = 'email_notify';
				$arr_fields_db[] = 'formEmail';						$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_admin';
				$arr_fields_db[] = 'formEmailNotifyPage';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_notify_page';
				$arr_fields_db[] = 'formEmailConfirm';				$arr_fields_db_bool[] = true;		$arr_fields_meta[] = 'email_confirm';
				$arr_fields_db[] = 'formEmailConfirmPage';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_confirm_page';
				$arr_fields_db[] = 'formEmailConditions';			$arr_fields_db_bool[] = false;		$arr_fields_meta[] = 'email_conditions';

				foreach($arr_fields_db as $key => $value)
				{
					if(does_column_exist($wpdb->base_prefix."form", $value) == false)
					{
						unset($arr_fields_db[$key]);
						unset($arr_fields_db_bool[$key]);
						unset($arr_fields_meta[$key]);
					}
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT formID, postID".(count($arr_fields_db) > 0 ? ", ".implode(", ", $arr_fields_db) : "")." FROM ".$wpdb->base_prefix."form WHERE (blogID = '0' OR blogID = '%d')", $wpdb->blogid), ARRAY_A);

				foreach($result as $r)
				{
					$form_id = $r['formID'];
					$post_id = $r['postID'];

					foreach($arr_fields_db as $key => $value)
					{
						if(isset($r[$arr_fields_db[$key]]) && $r[$arr_fields_db[$key]] != '')
						{
							if(get_post_meta($post_id, $this->meta_prefix.$arr_fields_meta[$key], true) == '')
							{
								$default_value = '';

								if($arr_fields_db_bool[$key] == true)
								{
									$default_value = ($r[$arr_fields_db[$key]] == 1 ? 'yes' : 'no');
								}

								if($default_value != '')
								{
									update_post_meta($post_id, $this->meta_prefix.$arr_fields_meta[$key], $default_value);
								}
							}
						}

						// Correct a previous bug
						if($arr_fields_db_bool[$key] == false)
						{
							if(get_post_meta($post_id, $this->meta_prefix.$arr_fields_meta[$key], true) == 'no')
							{
								update_post_meta($post_id, $this->meta_prefix.$arr_fields_meta[$key], "");
							}
						}

						$wpdb->query("ALTER TABLE ".$wpdb->base_prefix."form DROP COLUMN ".$arr_fields_db[$key]);
					}

					update_post_meta($post_id, $this->meta_prefix.'form_id', $form_id);
				}
			}
			#################################

			// Convert answerIP to MD5
			#################################
			if(apply_filters('does_table_exist', false, $wpdb->prefix."form2answer"))
			{
				$result = $wpdb->get_results("SELECT answerID, answerIP FROM ".$wpdb->prefix."form2answer WHERE answerIP != '' AND CHAR_LENGTH(answerIP) < 32");

				foreach($result as $r)
				{
					$wpdb->get_results($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerIP = %s WHERE answerID = '%d'", md5((defined('NONCE_SALT') ? NONCE_SALT : '').$r->answerIP), $r->answerID));
				}
			}
			#################################

			mf_uninstall_plugin(array(
				'options' => array('setting_form_permission', 'setting_form_reload', 'setting_form_replacement', 'setting_form_replacement_text', 'setting_form_clear_spam', 'setting_form_permission_edit_all', 'setting_form_permission_see_all', 'setting_link_yes_text', 'setting_link_no_text', 'setting_link_thanks_text'),
				'user_meta' => array('meta_answer_viewed'),
				'tables' => array('form_check', 'form_nonce', 'form_spam', 'form_zipcode', 'form_answer_meta'),
			));

			// Delete orphan forms
			#######################
			if($wpdb->base_prefix == $wpdb->prefix && does_column_exist($wpdb->base_prefix."form", "blogID"))
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form LEFT JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."form.postID = ".$wpdb->posts.".ID AND post_type = %s WHERE blogID = '%d' AND ID IS null", $this->post_type, $wpdb->blogid));

				foreach($result as $r)
				{
					$this->delete_form($r->formID);
				}
			}

			else if(apply_filters('does_table_exist', false, $wpdb->prefix."form"))
			{
				$query = $wpdb->prepare("SELECT formID FROM ".$wpdb->prefix."form LEFT JOIN ".$wpdb->posts." ON ".$wpdb->prefix."form.postID = ".$wpdb->posts.".ID AND post_type = %s WHERE ID IS null", $this->post_type);
				$result = $wpdb->get_results($query);

				foreach($result as $r)
				{
					$this->delete_form($r->formID);
				}
			}
			#######################

			// Delete orphan data
			#######################
			if(apply_filters('does_table_exist', false, $wpdb->prefix."form"))
			{
				$result = $wpdb->get_results("SELECT ".$wpdb->prefix."form2type.formID FROM ".$wpdb->prefix."form2type LEFT JOIN ".$wpdb->prefix."form USING (formID) WHERE ".$wpdb->prefix."form.formID IS null");

				if($wpdb->num_rows > 0)
				{
					do_log(__FUNCTION__." - Dead form2type: ".$wpdb->last_query);

					foreach($result as $r)
					{
						//$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2type WHERE formID = '%d'", $r->formID));
					}
				}

				$result = $wpdb->get_results("SELECT ".$wpdb->prefix."form_option.form2TypeID FROM ".$wpdb->prefix."form_option LEFT JOIN ".$wpdb->prefix."form2type USING (form2TypeID) WHERE ".$wpdb->prefix."form2type.form2TypeID IS null");

				if($wpdb->num_rows > 0)
				{
					do_log(__FUNCTION__." - Dead form_option: ".$wpdb->last_query);

					foreach($result as $r)
					{
						//$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d'", $r->form2TypeID));
					}
				}

				$result = $wpdb->get_results("SELECT ".$wpdb->prefix."form_answer.answerID FROM ".$wpdb->prefix."form_answer LEFT JOIN ".$wpdb->prefix."form2answer USING (answerID) WHERE ".$wpdb->prefix."form2answer.answerID IS null");

				foreach($result as $r)
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d'", $r->answerID));
				}

				$result = $wpdb->get_results("SELECT ".$wpdb->prefix."form_answer_email.answerID FROM ".$wpdb->prefix."form_answer_email LEFT JOIN ".$wpdb->prefix."form2answer USING (answerID) WHERE ".$wpdb->prefix."form2answer.answerID IS null");

				foreach($result as $r)
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d'", $r->answerID));
				}

				$result = $wpdb->get_results("SELECT ".$wpdb->prefix."form2answer.formID FROM ".$wpdb->prefix."form2answer LEFT JOIN ".$wpdb->prefix."form USING (formID) WHERE ".$wpdb->prefix."form.formID IS null");

				if($wpdb->num_rows > 0)
				{
					do_log(__FUNCTION__." - Dead form2answer: ".$wpdb->last_query);

					foreach($result as $r)
					{
						//$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2answer WHERE formID = '%d'", $r->formID));
					}
				}
			}
			#######################

			// Look for empty answers
			#######################
			if(apply_filters('does_table_exist', false, $wpdb->prefix."form2answer"))
			{
				$result = $wpdb->get_results("SELECT answerID FROM ".$wpdb->prefix."form2answer LEFT JOIN ".$wpdb->prefix."form_answer USING (answerID) WHERE ".$wpdb->prefix."form_answer.answerID IS null");

				foreach($result as $r)
				{
					$has_data = false;

					$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d'", $r->answerID));

					if($wpdb->num_rows > 0)
					{
						$has_data = true;
					}

					$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d'", $r->answerID));

					if($wpdb->num_rows > 0)
					{
						$has_data = true;
					}

					if($has_data == false)
					{
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d'", $r->answerID));
					}
				}
			}
			#######################

			// Delete old spam answers
			#######################
			$result = $wpdb->get_results("SELECT answerID FROM ".$wpdb->prefix."form2answer WHERE answerSpam = '1' AND answerCreated < DATE_SUB(NOW(), INTERVAL 6 MONTH)");

			foreach($result as $r)
			{
				$intAnswerID = $r->answerID;

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d'", $intAnswerID));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d'", $intAnswerID));
			}
			#######################

			// Delete old uploads
			#######################
			list($upload_path, $upload_url) = get_uploads_folder($this->post_type, true, false);

			if($upload_path != '')
			{
				get_file_info(array('path' => $upload_path, 'callback' => 'delete_files_callback', 'time_limit' => WEEK_IN_SECONDS));
				get_file_info(array('path' => $upload_path, 'folder_callback' => 'delete_empty_folder_callback'));
			}
			#######################
		}

		$obj_cron->end();
	}

	function process_submit()
	{
		global $wpdb, $error_text, $notice_text, $done_text;

		$out = $error_text = "";

		$this->arr_email_content = array(
			'fields' => [],
		);

		$setting_form_spam = get_option_or_default('setting_form_spam', array('email', 'filter', 'honeypot'));

		$this->form_name = $this->get_form_name();

		$arr_email_fields = $this->get_email_fields();
		$email_confirm_id = (isset($arr_email_fields[0]) ? $arr_email_fields[0] : 0);

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeText, formTypeID, checkID, formTypeRequired, formTypeEncrypt FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND formTypeDisplay = '1' ORDER BY form2TypeOrder ASC", $this->id));

		foreach($result as $r)
		{
			$intForm2TypeID2 = $r->form2TypeID;
			$strFormTypeCode = $this->arr_form_types[$r->formTypeID]['code'];
			$this->label = $r->formTypeText;
			$strCheckCode = ($r->checkID > 0 && isset($this->arr_form_check[$r->checkID]) ? $this->arr_form_check[$r->checkID]['code'] : 'char');
			$intFormTypeRequired = $r->formTypeRequired;
			$strFormTypeEncrypt = $r->formTypeEncrypt;

			if(!in_array($strFormTypeCode, array('custom_tag', 'custom_tag_end')))
			{
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

							if($strFormTypeCode == 'input_field' && (!($email_confirm_id > 0) || $email_confirm_id == $intForm2TypeID2))
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

									$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

									foreach($result as $r)
									{
										$strAnswerText_send = "<a href='".$r->guid."'>".$r->post_title."</a>";
									}
								}
							}
						}
					break;
				}

				$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;

				if($strAnswerText != '')
				{
					if($strFormTypeEncrypt == 'yes')
					{
						$obj_encryption = new mf_encryption(__CLASS__);
						$strAnswerText = $obj_encryption->encrypt($strAnswerText, md5(AUTH_KEY));
					}

					$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = %s, answerUpdated = NOW()", $intForm2TypeID2, $strAnswerText);

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
						$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = '', answerUpdated = NOW()", $strAnswerText_radio);

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
					$this->get_post_id();

					$error_text = get_post_meta_or_default($this->post_id, $this->meta_prefix.'mandatory_text', true, __("Please, enter all required fields", 'lang_form'))." (".$this->label.")";
				}
			}
		}

		if($error_text == '' && count($this->arr_answer_queries) > 0)
		{
			$honeypot_check = check_var($this->prefix.'check');

			if(in_array('honeypot', $setting_form_spam) && $honeypot_check != '')
			{
				$this->is_spam = true;
				$this->is_spam_id = 7;
			}

			$current_visitor_ip = md5((defined('NONCE_SALT') ? NONCE_SALT : '').apply_filters('get_current_visitor_ip', ""));
			$answer_fingerprint = apply_filters('get_visitor_fingerprint', "");

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2answer SET formID = '%d', answerIP = %s, answerFingerprint = %s, answerSpam = '%d', spamID = '%d', answerCreated = NOW()", $this->id, $current_visitor_ip, $answer_fingerprint, $this->is_spam, $this->is_spam_id));
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

					$post_meta_answer_url = get_post_meta($this->post_id, $this->meta_prefix.'answer_url', true, $this->post_id);
					$post_url = get_permalink($post_meta_answer_url);

					mf_redirect($post_url.(strpos($post_url, "?") ? "&" : "?")."success");
				}
			}
		}

		return $out;
	}

	function check_if_already_answered($data)
	{
		global $wpdb, $obj_encryption, $notice_text;

		$post_multiple_answers = get_post_meta($this->post_id, $this->meta_prefix.'multiple_answers', true);

		if($data['edit_mode'] == false && !($this->answer_id > 0) && $post_multiple_answers == 'no')
		{
			$answer_fingerprint = apply_filters('get_visitor_fingerprint', "");

			$answer_id = $wpdb->get_var($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer WHERE formID = '%d' AND answerFingerprint = %s ORDER BY answerCreated ASC LIMIT 0, 1", $this->id, $answer_fingerprint));

			if($answer_id > 0)
			{
				$data['display_form'] = false;

				$post_editable_answers = get_post_meta($this->post_id, $this->meta_prefix.'editable_answers', true);

				if($post_editable_answers == 'yes')
				{
					$answer_id_encrypted = $obj_encryption->encrypt($answer_id, md5(AUTH_KEY));

					$post_url = get_permalink($this->post_id);
					$post_url .= (strpos($post_url, "?") ? "&" : "?")."answer_id_encrypted=".$answer_id_encrypted;

					$notice_text = sprintf(__("You have already answered this form and you can %sedit your answer%s", 'lang_form'), "<a href='".$post_url."'>", "</a>");
				}

				else
				{
					$notice_text = __("You have already answered this form", 'lang_form');
				}
			}
		}

		return $data;
	}

	function process_form($data = [])
	{
		global $wpdb, $wp_query, $done_text, $notice_text, $error_text, $obj_form;

		if(!isset($obj_form))
		{
			$obj_form = new mf_form();
		}

		$obj_form->init();

		$obj_encryption = new mf_encryption(__CLASS__);

		$plugin_include_url = plugin_dir_url(__FILE__);

		$out = "";

		if(!isset($data['edit_mode'])){		$data['edit_mode'] = false;}
		if(!isset($data['form2type_id'])){	$data['form2type_id'] = 0;}

		$this->prefix = $this->get_post_info(array('select' => 'post_name'))."_";
		$this->get_post_id();

		if($data['edit_mode'] == false)
		{
			$this->fetch_request();
			$out .= $this->save_data();
		}

		$data['display_form'] = true;

		if(isset($_GET['success']))
		{
			$data['display_form'] = false;
			$done_text = __("Thank You!", 'lang_form');
		}

		else
		{
			$post_meta_deadline = get_post_meta($this->post_id, $this->meta_prefix.'deadline', true);

			if($data['edit_mode'] == false && $post_meta_deadline > DEFAULT_DATE && $post_meta_deadline < date("Y-m-d"))
			{
				$data['display_form'] = false;
				$error_text = __("This form is not open for submissions anymore", 'lang_form');
			}

			else
			{
				$data = $this->check_if_already_answered($data);
			}
		}

		if($data['display_form'] == false)
		{
			$out .= get_notification(array('add_container' => true));
		}

		else if(isset($_POST[$this->prefix.'btnFormSubmit']))
		{
			if(!isset($_POST['form_submit_'.$this->id]) || $_POST['form_submit_'.$this->id] != $this->form_nonce_hash)
			{
				$error_text = __("The form was not processed properly. Try again. If the problem persists, contact an admin to report this.", 'lang_form');
			}

			else
			{
				$out .= $this->process_submit();
			}

			$out .= get_notification(array('add_container' => true));
		}

		else
		{
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

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, checkID, formTypeText, formTypePlaceholder, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeEncrypt, formTypeRemember, formTypeTag, formTypeClass, formTypeLength, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->prefix."form2type WHERE ".$query_where." GROUP BY ".$wpdb->prefix."form2type.form2TypeID ORDER BY form2TypeOrder ASC", $query_where_id));

			if($wpdb->num_rows > 0)
			{
				$data_temp = [];

				if($data['edit_mode'] == true)
				{
					$data_temp['class'][] = "mf_sortable";
				}

				$out .= "<form".apply_filters('get_form_attr', " id='form_".$this->id."' enctype='multipart/form-data'", $data_temp).">";

					/*if($data['edit_mode'] == false)
					{*/
						$out .= get_notification(array('add_container' => true));
					//}

					$intFormTypeID2_temp = $intForm2TypeID2_temp = "";

					foreach($result as $r)
					{
						$r->formTypeText = stripslashes($r->formTypeText);

						$obj_form_output = new mf_form_output(array('id' => $this->id, 'answer_id' => $this->answer_id, 'result' => $r, 'edit_mode' => $data['edit_mode'], 'query_prefix' => $this->prefix));

						$obj_form_output->calculate_value();
						$obj_form_output->get_form_fields();

						$out .= $obj_form_output->get_output($data);
					}

					if($this->answer_id > 0)
					{
						$answer_id_encrypted = $obj_encryption->encrypt($this->answer_id, md5(AUTH_KEY));

						$out .= "<div".get_form_button_classes().">"
							.show_button(array('name' => 'btnAnswerUpdate', 'text' => __("Update", 'lang_form')))
							.input_hidden(array('name' => 'answer_id_encrypted', 'value' => $answer_id_encrypted))
						."</div>";
					}

					else if($data['edit_mode'] == false)
					{
						$setting_form_spam = get_option_or_default('setting_form_spam', array('email', 'filter', 'honeypot'));

						if(in_array('honeypot', $setting_form_spam))
						{
							mf_enqueue_style('style_form_check', $plugin_include_url."style_check.css");

							$out .= show_textfield(array('name' => $this->prefix.'check', 'text' => __("This field should not be visible", 'lang_form'), 'xtra_class' => "form_check hide", 'xtra' => " autocomplete='off'"));
						}

						$out .= apply_filters('filter_form_after_fields', '');

						mf_enqueue_script('script_form_submit', $plugin_include_url."script_submit.js", array(
							'ajax_url' => admin_url('admin-ajax.php'),
						));

						$post_button_symbol = get_post_meta($this->post_id, $this->meta_prefix.'button_symbol', true);

						if($post_button_symbol != '')
						{
							global $obj_font_icons;

							if(!isset($obj_font_icons))
							{
								$obj_font_icons = new mf_font_icons();
							}

							$post_button_symbol = $obj_font_icons->get_symbol_tag(array('symbol' => $post_button_symbol))."&nbsp;";
						}

						$post_button_text = get_post_meta_or_default($this->post_id, $this->meta_prefix.'button_text', true, __("Submit", 'lang_form'));

						$out .= "<div".get_form_button_classes().">"
							.show_button(array('name' => $this->prefix.'btnFormSubmit', 'text' => $post_button_symbol.$post_button_text, 'xtra' => "disabled"))
							."<div class='api_form_nonce'></div>"
							.input_hidden(array('name' => 'intFormID', 'value' => $this->id));

							if(isset($this->form_atts) && is_array($this->form_atts))
							{
								foreach($this->form_atts as $key => $value)
								{
									$out .= input_hidden(array('name' => $key, 'value' => $value));
								}
							}

						$out .= "</div>";
					}

				$out .= "</form>";
			}

			else if(IS_SUPER_ADMIN && $data['edit_mode'] == false)
			{
				$out .= "<em>".sprintf(__("There are no fields in this form so far. %sAdd a few%s and they will display here.", 'lang_form'), "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id)."'>", "</a>")."</em>";
			}
		}

		//$out .= get_notification(array('add_container' => true));

		return $out;
	}

	function block_render_callback($attributes)
	{
		if(!isset($attributes['form_id'])){			$attributes['form_id'] = 0;}

		if($attributes['form_id'] == 0)
		{
			global $post;

			if(isset($post->ID) && $post->ID > 0)
			{
				$attributes['form_id'] = $this->get_form_id($post->ID);
			}
		}

		$out = "";

		if($attributes['form_id'] > 0)
		{
			$this->id = $attributes['form_id'];

			$out = "<div".parse_block_attributes(array('class' => "widget form", 'attributes' => $attributes)).">"
				.$this->process_form()
			."</div>";
		}

		return $out;
	}

	function get_for_select($data = [])
	{
		global $wpdb;

		if(!isset($data['force_has_page'])){	$data['force_has_page'] = true;}

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_form')." --"
		);

		$arr_data_posts = [];
		get_post_children(array('add_choose_here' => false, 'post_type' => $this->post_type), $arr_data_posts);

		foreach($arr_data_posts as $post_id => $post_title)
		{
			$form_id = get_post_meta($post_id, $this->meta_prefix.'form_id', true);

			$allow_form = false;

			if($data['force_has_page'] == true)
			{
				$arr_ids = apply_filters('get_page_from_block_code', [], '<!-- wp:mf/form {"form_id":"'.$form_id.'"} /-->');

				if(count($arr_ids) > 0)
				{
					$allow_form = true;
				}
			}

			else
			{
				$allow_form = true;
			}

			if($form_id > 0 && $allow_form == true)
			{
				$arr_data[$form_id] = $post_title;
			}
		}

		return $arr_data;
	}

	function enqueue_block_editor_assets()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_script('script_form_block_wp', $plugin_include_url."block/script_wp.js", array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'), $plugin_version, true);

		wp_localize_script('script_form_block_wp', 'script_form_block_wp', array(
			'block_title' => __("Form", 'lang_form'),
			'block_description' => __("Display a Form", 'lang_form'),
			'form_id_label' => __("Select", 'lang_form'),
			'form_id' => $this->get_for_select(array('force_has_page' => false)),
		));
	}

	function init()
	{
		load_plugin_textdomain('lang_form', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		$this->arr_form_types = array(
			1 => array('code' => 'checkbox',			'name' => "&#xf14a; ".__("Checkbox", 'lang_form'),					'desc' => __("To choose one or many alternatives", 'lang_form')),
			2 => array('code' => 'range',				'name' => "&#xf258; ".__("Range", 'lang_form'),						'desc' => __("To choose a min and max value and create a slider for that", 'lang_form')),
			3 => array('code' => 'input_field',			'name' => "&#xf044; ".__("Input Field", 'lang_form'),				'desc' => __("To enter a short text", 'lang_form')),
			4 => array('code' => 'textarea',			'name' => "&#xf044; ".__("Textarea", 'lang_form'),					'desc' => __("To enter a longer text on multiple rows", 'lang_form')),
			5 => array('code' => 'text',				'name' => "&#xf27a; ".__("Text", 'lang_form'),						'desc' => __("To present information to the visitor", 'lang_form')),
			6 => array('code' => 'space',				'name' => "&#xf2d1; ".__("Space", 'lang_form'),						'desc' => __("To separate fields in the form with empty space", 'lang_form')),
			7 => array('code' => 'datepicker',			'name' => "&#xf073; ".__("Datepicker", 'lang_form'),				'desc' => __("To choose a date", 'lang_form')),
			8 => array('code' => 'radio_button',		'name' => "&#xf192; ".__("Radio Button", 'lang_form'),				'desc' => __("To choose one alternative", 'lang_form')),
			9 => array('code' => 'referer_url',			'name' => "&#xf164; ".__("Referer URL", 'lang_form'),				'desc' => __("To get which URL the visitor came from", 'lang_form')),
			10 => array('code' => 'select',				'name' => "&#xf022; ".__("Dropdown", 'lang_form'),					'desc' => __("To choose one alternative", 'lang_form')),
			11 => array('code' => 'select_multiple',	'name' => "&#xf022; ".__("Multiple Selection", 'lang_form'),		'desc' => __("To choose one or many alternatives", 'lang_form')),
			12 => array('code' => 'hidden_field',		'name' => "&#xf070; ".__("Hidden Field", 'lang_form'),				'desc' => __("To add hidden data to the form", 'lang_form')),
			13 => array('code' => 'custom_tag',			'name' => "&#xf070; ".__("Custom Tag", 'lang_form'),				'desc' => __("To add a custom tag", 'lang_form')),
			14 => array('code' => 'custom_tag_end',		'name' => "&#xf070; ".__("Custom Tag (end)", 'lang_form'),			'desc' => __("To add a custom end tag", 'lang_form')),
			15 => array('code' => 'file',				'name' => "&#xf07c; ".__("File", 'lang_form'),						'desc' => __("To add a file upload to the form", 'lang_form')),
			16 => array('code' => 'checkbox_multiple',	'name' => "&#xf14a; ".__("Multiple Checkboxes", 'lang_form'),		'desc' => __("To choose one or many alternatives", 'lang_form')),
			17 => array('code' => 'radio_multiple',		'name' => "&#xf192; ".__("Multiple Radio Buttons", 'lang_form'),	'desc' => __("To choose one alternative", 'lang_form')),
		);

		$this->arr_form_check = array(
			1 => array('name' => __("Number", 'lang_form'),				'code' => 'int'),
			2 => array('name' => __("Zip Code", 'lang_form'),			'code' => 'zip'),
			5 => array('name' => __("E-mail", 'lang_form'),				'code' => 'email'),
			6 => array('name' => __("Phone no", 'lang_form'),			'code' => 'telno'),
			7 => array('name' => __("Decimal number", 'lang_form'),		'code' => 'float'),
			8 => array('name' => __("URL", 'lang_form'),				'code' => 'url'),
			9 => array('name' => __("Name", 'lang_form'),				'code' => 'name'),
			10 => array('name' => __("Street Address", 'lang_form'),	'code' => 'address'),
			11 => array('name' => __("City", 'lang_form'),				'code' => 'city'),
			11 => array('name' => __("Country", 'lang_form'),			'code' => 'country'),
		);

		if(get_bloginfo('language') == "sv-SE")
		{
			$this->arr_form_check[3] = array('name' => __("Social security no", 'lang_form')." (8208041234)",		'code' => 'soc');
			$this->arr_form_check[4] = array('name' => __("Social security no", 'lang_form')." (198208041234)",		'code' => 'soc2');
		}

		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => __("Forms", 'lang_form'),
				'singular_name' => __("Form", 'lang_form'),
				'menu_name' => __("Forms", 'lang_form'),
				'all_items' => __("List", 'lang_form'),
				'edit_item' => __("Edit", 'lang_form'),
				'view_item' => __("View", 'lang_form'),
				'add_new_item' => __("Add New", 'lang_form'),
				//'search_items' => __("Search Forms", 'lang_form'),
				//'not_found' => __("No Forms Found", 'lang_form'),
			),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'exclude_from_search' => true,
			'menu_position' => 21,
			'menu_icon' => 'dashicons-forms',
			'supports' => array('title'),
			'rewrite' => array(
				'slug' => 'form',
			),
		));

		remove_post_type_support($this->post_type, 'comments');
		remove_post_type_support($this->post_type, 'trackbacks');

		if(!WP_Block_Type_Registry::get_instance()->is_registered('mf/form'))
		{
			register_block_type('mf/form', array(
				'editor_script' => 'script_form_block_wp',
				'editor_style' => 'style_base_block_wp',
				'render_callback' => array($this, 'block_render_callback'),
			));
		}
	}

	function has_template()
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s LIMIT 0, 1", 'wp_template', 'single-'.$this->post_type));

		return ($wpdb->num_rows > 0);
	}

	function admin_notices()
	{
		global $wpdb, $done_text, $notice_text, $error_text;

		if(IS_ADMINISTRATOR && $done_text == '' && $notice_text == '' && $error_text == '' && wp_is_block_theme() == true && $this->has_template() == false)
		{
			$notice_text = sprintf(__("You need to %screate a single template%s for Forms", 'lang_form'), "<a href='".admin_url("site-editor.php?p=/template")."'>", "</a>");

			echo get_notification();
		}
	}

	function settings_form()
	{
		global $wpdb;

		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_form_redirect_emails' => __("Redirect all e-mails", 'lang_form'),
			'setting_form_test_emails' => __("Redirect test e-mails", 'lang_form'),
			'setting_form_spam' => __("Spam Filter", 'lang_form'),
		);

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_form_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Forms", 'lang_form'));
	}

	function setting_form_redirect_emails_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		list($option, $description) = setting_time_limit(array('key' => $setting_key, 'value' => $option, 'return' => 'array'));

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("When a visitor sends an e-mail through the site it is redirected to the admins address", 'lang_form'), 'description' => $description));
	}

	function setting_form_test_emails_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'no');

		list($option, $description) = setting_time_limit(array('key' => $setting_key, 'value' => $option, 'return' => 'array'));

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("When an admin is logged in and testing to send e-mails all outgoing e-mails are redirected to the admins address", 'lang_form'), 'description' => $description));
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
		do_action('load_font_awesome');

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

	function the_content($html)
	{
		global $wpdb, $post;

		$intAnswerID = check_var('answer_id', 'int');

		if($intAnswerID > 0)
		{
			$value = $wpdb->get_var($wpdb->prepare("SELECT SUM(formOptionKey) FROM ".$wpdb->prefix."form2type INNER JOIN ".$wpdb->prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->prefix."form_option ON ".$wpdb->prefix."form_answer.answerText = ".$wpdb->prefix."form_option.formOptionID WHERE answerID = '%d' AND formTypeID IN('8', '17')", $intAnswerID)); //radio_button, radio_multiple

			$if_statement = get_match("/\[if (.*?)\]/i", $html, false);

			if($if_statement != '')
			{
				$if_parts = explode(" ", $if_statement);

				$log_message = $post->ID." -> <a href='".get_permalink($post->ID)."'>".get_the_title($post->ID)."</a> -> ".$if_statement;

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

		return $html;
	}

	/*function get_query_permission()
	{
		global $wpdb;

		$query_where = "";

		if(!current_user_can('edit_pages'))
		{
			$query_where .= " AND ".$wpdb->prefix."form.userID = '".get_current_user_id()."'";
		}

		return $query_where;
	}*/

	function get_count_answer_message($data = [])
	{
		global $wpdb;

		if(!isset($data['form_id'])){		$data['form_id'] = 0;}
		if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}
		if(!isset($data['return_type'])){	$data['return_type'] = 'html';}

		$out = "";

		$last_viewed = get_user_meta($data['user_id'], 'meta_forms_viewed', true);

		$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form INNER JOIN ".$wpdb->prefix."form2answer USING (formID) INNER JOIN ".$wpdb->prefix."form_answer_email USING (answerID) WHERE (answerCreated > %s OR answerCreated > DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND answerSpam = '0' AND answerSent = '0'".($data['form_id'] > 0 ? " AND formID = '".$data['form_id']."'" : ""), ($last_viewed > DEFAULT_DATE ? $last_viewed : date("Y-m-d H:i:s")))); //.$this->get_query_permission()
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			switch($data['return_type'])
			{
				default:
				case 'html':
					do_action('load_font_awesome');

					$out .= "&nbsp;<i class='fa fa-exclamation-triangle yellow' title='".($rows > 1 ? sprintf(__("There are %d unsent messages", 'lang_form'), $rows) : __("There is one unset message", 'lang_form'))."'></i>";
				break;

				case 'array':
					$out = array(
						'title' => ($rows > 1 ? sprintf(__("There are %d unsent messages", 'lang_form'), $rows) : __("There is one unset message", 'lang_form')),
						'tag' => 'form',
						'link' => admin_url("edit.php?post_type=".$this->post_type),
					);
				break;
			}

			return $out;
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form INNER JOIN ".$wpdb->prefix."form2answer USING (formID) WHERE answerCreated > %s AND answerSpam = '0'".($data['form_id'] > 0 ? " AND formID = '".$data['form_id']."'" : ""), ($last_viewed > DEFAULT_DATE ? $last_viewed : date("Y-m-d H:i:s")))); //.$this->get_query_permission()
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			$title = ($rows > 1 ? sprintf(__("There are %d new answers", 'lang_form'), $rows) : __("There is one new answer", 'lang_form'));

			switch($data['return_type'])
			{
				default:
				case 'html':
					$out .= "&nbsp;<span class='update-plugins' title='".$title."'><span>".$rows."</span></span>";
				break;

				case 'array':
					$out = array(
						'title' => $title,
						'tag' => 'form',
						'link' => admin_url("edit.php?post_type=".$this->post_type),
					);
				break;
			}

			return $out;
		}

		return $out;
	}

	function admin_init()
	{
		global $pagenow, $menu, $done_text, $error_text;

		$count_message = $this->get_count_answer_message();

		if($count_message != '' && is_array($menu))
		{
			foreach($menu as $key => $menu_item)
			{
				if(isset($menu_item[2]) && $menu_item[2] == 'edit.php?post_type='.$this->post_type)
				{
					if(!preg_match("/update-plugins/i", $menu[$key][0]))
					{
						$menu[$key][0] .= $count_message;
						break;
					}
				}
			}
		}

		switch($pagenow)
		{
			case 'admin.php':
				$page = check_var('page');

				$plugin_include_url = plugin_dir_url(__FILE__);

				if($page == 'mf_form/create/index.php')
				{
					$plugin_base_include_url = plugins_url()."/mf_base/include/";

					wp_enqueue_script('jquery-ui-sortable');
					mf_enqueue_script('script_touch', $plugin_base_include_url."jquery.ui.touch-punch.min.js");
				}

				if($page == 'mf_form/create/index.php' || $page == 'mf_form/answer/index.php')
				{
					mf_enqueue_style('style_form_wp', $plugin_include_url."style_wp.css");
					mf_enqueue_script('script_form_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')));
				}
			break;

			case 'edit.php':
				if(isset($_GET['btnFormCopy']) && wp_verify_nonce($_REQUEST['_wpnonce_form_copy'], 'form_copy_'.$this->id))
				{
					if($this->clone_form(array('id' => $this->id, 'create_new_page' => true, 'include_answers' => false)))
					{
						$done_text = __("The form was successfully copied", 'lang_form')." (<a href='".admin_url("edit.php?post_type=".$this->post_type)."'>".__("Edit", 'lang_form')."</a>)";
					}

					else
					{
						$error_text = __("Something went wrong. Contact your admin and add this URL as reference", 'lang_form');
					}

					echo get_notification();
				}

				/*else if(isset($_GET['btnFormExport']))
				{
					new mf_form_export();
				}*/

				else if(isset($_GET['btnFormAnswerExport']))
				{
					new mf_form_answer_export();
				}
			break;
		}

		if(function_exists('wp_add_privacy_policy_content') && $this->get_amount() > 0)
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

	function filter_sites_table_settings($arr_settings)
	{
		$arr_settings['settings_form'] = array(
			'setting_form_redirect_emails' => array(
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
		);

		return $arr_settings;
	}

	function filter_sites_table_pages($arr_pages)
	{
		$arr_pages[$this->post_type] = array(
			'icon' => "fab fa-wpforms",
			'title' => __("Forms", 'lang_form'),
		);

		return $arr_pages;
	}

	function return_row_actions($arr_actions)
	{
		$out = "<div class='row-actions'>";

			$i = 0;

			foreach($arr_actions as $key => $value)
			{
				if($i > 0)
				{
					$out .= " | ";
				}

				$out .= "<span class='".$key."'>".$value."</span>";

				$i++;
			}

		$out .= "</div>";

		return $out;
	}

	function column_header($columns)
	{
		unset($columns['date']);

		do_action('load_font_awesome');

		$columns['content'] = __("Content", 'lang_form');
		$columns['answers'] = __("Answers", 'lang_form');
		$columns['spam'] = __("Spam", 'lang_form');
		$columns['post_modified'] = __("Modified", 'lang_form');

		return $columns;
	}

	function column_cell($column, $post_id)
	{
		global $wpdb, $post;

		switch($post->post_type)
		{
			case $this->post_type:
				$this->post_id = $post_id;
				$this->get_form_id($post_id);

				switch($column)
				{
					case 'content':
						$strFormEmail = get_post_meta($this->post_id, $this->meta_prefix.'email_admin', true);
						$intFormEmailNotifyPage = get_post_meta($this->post_id, $this->meta_prefix.'email_notify_page', true);
						$strFormEmailConfirm = get_post_meta($this->post_id, $this->meta_prefix.'email_confirm', true);
						$intFormEmailConfirmPage = get_post_meta($this->post_id, $this->meta_prefix.'email_confirm_page', true);
						$email_conditions = get_post_meta($this->post_id, $this->meta_prefix.'email_conditions', true);

						if($strFormEmail == '')
						{
							$strFormEmail = get_bloginfo('admin_email');
						}

						if($intFormEmailNotifyPage > 0)
						{
							echo "<i class='fa fa-paper-plane fa-lg grey' title='".sprintf(__("A notification email based on a template will be sent to %s", 'lang_form'), $strFormEmail)."'></i> ";
						}

						else
						{
							echo "<i class='fa fa-paper-plane fa-lg grey' title='".sprintf(__("E-mails will be sent to %s on every answer", 'lang_form'), $strFormEmail)."'></i> ";
						}

						if($email_conditions != '')
						{
							echo "<i class='fa fa-paper-plane fa-lg grey' title='".__("Message will be sent to different e-mails because there are conditions", 'lang_form')."'></i> ";
						}

						if($strFormEmailConfirm == 'yes')
						{
							if($intFormEmailConfirmPage > 0)
							{
								echo "<i class='fa fa-paper-plane fa-lg grey' title='".__("A confirmation email based on a template will be sent to the visitor", 'lang_form')."'></i> ";
							}

							else
							{
								echo "<i class='fa fa-paper-plane fa-lg grey' title='".__("A confirmation email will be sent to the visitor", 'lang_form')."'></i> ";
							}
						}

						/*if($this->is_form_field_type_used(array('display' => '0')))
						{
							echo "<i class='fa fa-eye-slash fa-lg grey' title='".__("There are hidden fields", 'lang_form')."'></i> ";
						}*/

						if($this->is_form_field_type_used(array('required' => true)))
						{
							echo "<i class='fa fa-asterisk fa-lg grey' title='".__("There are required fields", 'lang_form')."'></i> ";
						}

						/*if($this->is_form_field_type_used(array('autofocus' => true)))
						{
							echo "<i class='fa fa-i-cursor fa-lg grey' title='".__("There are autofocus fields", 'lang_form')."'></i> ";
						}*/

						if($this->is_form_field_type_used(array('remember' => true)))
						{
							echo "<i class='fa fa-sync fa-lg grey' title='".__("There are remembered fields", 'lang_form')."'></i> ";
						}

						//echo "<br>";

						if($this->is_form_field_type_used(array('query_type_id' => 3, 'check_code' => 'email')))
						{
							echo "<i class='fa fa-at fa-lg grey' title='".__("There is a field for entering email adress", 'lang_form')."'></i> ";
						}

						/*if($this->is_form_field_type_used(array('query_type_id' => array(10, 11))))
						{
							echo "<i class='fa fa-list-alt fa-lg grey' title='".__("Dropdown", 'lang_form')."'></i> ";
						}

						if($this->is_form_field_type_used(array('query_type_id' => array(1, 16))))
						{
							echo "<i class='fa fa-check-square fa-lg grey' title='".__("Checkbox", 'lang_form')."'></i> ";
						}

						if($this->is_form_field_type_used(array('query_type_id' => 2)))
						{
							echo "<i class='fa fa-sliders-h fa-lg grey' title='".__("Range", 'lang_form')."'></i> ";
						}

						if($this->is_form_field_type_used(array('query_type_id' => 7)))
						{
							echo "<i class='fa fa-calendar-alt fa-lg grey' title='".__("Datepicker", 'lang_form')."'></i> ";
						}

						if($this->is_form_field_type_used(array('query_type_id' => array(8, 17))))
						{
							echo "<i class='far fa-circle fa-lg grey' title='".__("Radio button", 'lang_form')."'></i> ";
						}

						if($this->is_form_field_type_used(array('query_type_id' => 15)))
						{
							echo "<i class='fa fa-file fa-lg grey' title='".__("File", 'lang_form')."'></i> ";
						}*/
					break;

					case 'answers':
						if(get_post_status($post_id) != 'trash')
						{
							$query_answers = $this->get_answer_amount(array('form_id' => $this->id));

							if($query_answers > 0)
							{
								$count_message = $this->get_count_answer_message(array('form_id' => $this->id));
								$dteAnswerCreated = $wpdb->get_var($wpdb->prepare("SELECT answerCreated FROM ".$wpdb->prefix."form2answer WHERE formID = '%d' AND answerSpam = '%d' ORDER BY answerCreated DESC", $this->id, '0'));

								$export_url = "edit.php?post_type=".$this->post_type."&intFormID=".$this->id;

								$arr_actions = array(
									'show_answers' => "<a href='".admin_url("admin.php?page=mf_form/answer/index.php&intFormID=".$this->id)."'>".__("View", 'lang_form')."</a>",
									'export_csv' => "<a href='".wp_nonce_url(admin_url($export_url."&btnFormAnswerExport&btnExportRun&intExportType=".$this->id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."'>CSV</a>",
								);

								if(is_plugin_active("mf_phpexcel/index.php"))
								{
									$arr_actions['export_xls'] = "<a href='".wp_nonce_url(admin_url($export_url."&btnFormAnswerExport&btnExportRun&intExportType=".$this->id."&strExportFormat=xls"), 'export_run', '_wpnonce_export_run')."'>XLS</a>";
								}

								echo $query_answers.$count_message." <span class='grey'>(".format_date($dteAnswerCreated).")</span>"
								.$this->return_row_actions($arr_actions);
							}
						}
					break;

					case 'spam':
						if(get_post_status($post_id) != 'trash')
						{
							$query_spam = $this->get_answer_amount(array('form_id' => $this->id, 'is_spam' => 1));

							if($query_spam > 0)
							{
								echo $query_spam;
							}
						}
					break;

					case 'post_modified':
						$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d'", $this->post_type, $post_id));

						echo format_date($post_modified);
					break;

					/*default:
						if(isset($item[$column_name]))
						{
							echo $item[$column_name];
						}
					break;*/
				}
			break;
		}
	}

	function post_row_actions($arr_actions, $post)
	{
		if($post->post_type == $this->post_type && get_post_status($post->ID) != 'trash')
		{
			$this->get_form_id($post->ID);

			$arr_actions['edit_content'] = "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id)."'>".__("Edit Content", 'lang_form')."</a>";

			$arr_ids = apply_filters('get_page_from_block_code', [], '<!-- wp:mf/form {"form_id":"'.$this->id.'"} /-->');

			if(count($arr_ids) > 0)
			{
				unset($arr_actions['view']);

				foreach($arr_ids as $post_id)
				{
					if(count($arr_ids) > 1)
					{
						$post_title = sprintf(__("View %s", 'lang_form'), "'".get_the_title($post_id)."'");
					}

					else
					{
						$post_title = __("View", 'lang_form');
					}

					$arr_actions['view_'.$post_id] = "<a href='".get_permalink($post_id)."'>".$post_title."</a>";
				}
			}

			$export_url = "edit.php?post_type=".$this->post_type."&intFormID=".$this->id;
			$arr_actions['copy'] = "<a href='".wp_nonce_url(admin_url($export_url."&btnFormCopy"), 'form_copy_'.$this->id, '_wpnonce_form_copy')."'>".__("Copy", 'lang_form')."</a>";
			//$arr_actions['export'] = "<a href='".wp_nonce_url(admin_url($export_url."&btnFormExport&btnExportRun&intExportType=".$this->id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."'>".__("Export", 'lang_form')."</a>";
		}

		return $arr_actions;
	}

	function get_email_fields()
	{
		global $wpdb;

		$arr_out = [];

		if(apply_filters('does_table_exist', false, $wpdb->prefix."form2type"))
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeText, checkID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND formTypeID = '%d'", $this->id, 3));

			foreach($result as $r)
			{
				if(isset($this->arr_form_check[$r->checkID]) && $this->arr_form_check[$r->checkID]['code'] == 'email')
				{
					$arr_out[$r->form2TypeID] = $r->formTypeText;
				}
			}
		}

		return $arr_out;
	}

	function get_email_confirm_for_select()
	{
		return array(
			'yes' => __("Yes", 'lang_form')." (".__("If there is an e-mail field", 'lang_form').")",
			'no' => __("No", 'lang_form'),
		);
	}

	function meta_page_information()
	{
		global $post;

		$out = "";

		$post_id = $post->ID;

		if($post_id > 0)
		{
			$this->get_form_id($post_id);

			$out .= "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id)."'>".__("Edit Content", 'lang_form')."</a> ";

			$arr_ids = apply_filters('get_page_from_block_code', [], '<!-- wp:mf/form {"form_id":"'.$this->id.'"} /-->');

			if(count($arr_ids) > 0)
			{
				foreach($arr_ids as $post_id)
				{
					$out .= "<a href='".get_permalink($post_id)."'>".__("View", 'lang_form')."</a> ";
				}
			}
		}

		return $out;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		$arr_data_pages = [];
		get_post_children(array('add_choose_here' => true), $arr_data_pages);

		$post_id = check_var('post');

		if($post_id > 0)
		{
			$this->get_form_id($post_id);
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'information',
			'title' => __("Information", 'lang_form'),
			'post_types' => array($this->post_type),
			'context' => 'side',
			'priority' => 'default',
			'fields' => array(
				array(
					'id' => $this->meta_prefix.'information',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_page_information'),
				),
			),
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'button',
			'title' => __("Button", 'lang_form'),
			'post_types' => array($this->post_type),
			'context' => 'side',
			'priority' => 'default',
			'fields' => array(
				array(
					'name' => __("Symbol", 'lang_form'),
					'id' => $this->meta_prefix.'button_symbol',
					'type' => 'select',
					'options' => $obj_base->get_icons_for_select(),
				),
				array(
					'name' => __("Text", 'lang_form'),
					'id' => $this->meta_prefix.'button_text',
					'type' => 'text',
					'placeholder' => __("Submit", 'lang_form')."...",
					'attributes' => array(
						'maxlength' => 100,
					),
				),
				array(
					'name' => __("Deadline", 'lang_form'),
					'id' => $this->meta_prefix.'deadline',
					'type' => 'date',
				),
			),
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_form'),
			'post_types' => array($this->post_type),
			'context' => 'normal',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Confirmation Page", 'lang_form'),
					'id' => $this->meta_prefix.'answer_url',
					'type' => 'select',
					'options' => $arr_data_pages,
				),
				array(
					'name' => __("Text regarding mandatory fields", 'lang_form'),
					'id' => $this->meta_prefix.'mandatory_text',
					'type' => 'text',
					'placeholder' => __("Please enter all required fields", 'lang_form')."...",
					'attributes' => array(
						'maxlength' => 100,
					),
				),
				array(
					'name' => __("Allow Multiple Answers", 'lang_form'),
					'id' => $this->meta_prefix.'multiple_answers',
					'type' => 'select',
					'options' => get_yes_no_for_select(),
					'std' => 'yes',
				),
				array(
					'name' => __("Allow Editing of Answers", 'lang_form'),
					'id' => $this->meta_prefix.'editable_answers',
					'type' => 'select',
					'options' => get_yes_no_for_select(),
					'std' => 'no',
				),
			),
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'notification',
			'title' => __("Notification", 'lang_form'),
			'post_types' => array($this->post_type),
			'context' => 'normal',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Send to Admin", 'lang_form'),
					'id' => $this->meta_prefix.'email_notify',
					'type' => 'select',
					'options' => get_yes_no_for_select(),
					'std' => 'no',
				),
				array(
					'name' => __("To", 'lang_form'),
					'id' => $this->meta_prefix.'email_admin',
					'type' => 'text',
					'attributes' => array(
						'placeholder' => get_bloginfo('admin_email'),
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'email_notify',
						'condition_value' => 'yes',
					),
				),
				array(
					'name' => " - ".__("Template", 'lang_form'),
					'id' => $this->meta_prefix.'email_notify_page',
					'type' => 'select',
					'options' => $arr_data_pages,
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'email_notify',
						'condition_value' => 'yes',
					),
				),
			),
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'confirmation',
			'title' => __("Confirmation", 'lang_form'),
			'post_types' => array($this->post_type),
			'context' => 'normal',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Send to Visitor", 'lang_form'),
					'id' => $this->meta_prefix.'email_confirm',
					'type' => 'select',
					'options' => $this->get_email_confirm_for_select(),
					'std' => 'no',
				),
				array(
					'name' => __("Template", 'lang_form'),
					'id' => $this->meta_prefix.'email_confirm_page',
					'type' => 'select',
					'options' => $arr_data_pages,
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'email_confirm',
						'condition_value' => 'yes',
					),
				),
				array(
					'name' => __("Conditions", 'lang_form'),
					'id' => $this->meta_prefix.'email_conditions',
					'type' => 'text',
					'attributes' => array(
						'placeholder' => "[field_id]|[field_value]|".get_bloginfo('admin_email'),
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'email_confirm',
						'condition_value' => 'yes',
					),
				),
			),
		);

		return $meta_boxes;
	}

	function admin_menu()
	{
		$menu_root = 'mf_form/';

		if(IS_EDITOR)
		{
			$menu_start = "edit.php?post_type=".$this->post_type;
			$menu_capability = 'edit_posts';

			$menu_title = __("Settings", 'lang_form');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_form"));

			$menu_title = __("Add New", 'lang_form');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'create/index.php');

			$menu_title = __("Answers", 'lang_form');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'answer/index.php');

			$menu_title = __("Edit Answer", 'lang_form');
			add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, $menu_root.'view/index.php');
		}

		remove_meta_box('commentsdiv', $this->post_type, 'normal');
		remove_meta_box('commentstatusdiv', $this->post_type, 'normal');
	}

	function wp_delete_post($post_id)
	{
		global $wpdb;

		if(get_post_type($post_id) == $this->post_type)
		{
			$this->get_form_id($post_id);

			$this->delete_form($this->id);
		}
	}

	function deleted_user($user_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
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

		$form_id = 0;

		if(apply_filters('does_table_exist', false, $wpdb->prefix."form2type"))
		{
			$form_id = $wpdb->get_var("SELECT formID FROM ".$wpdb->prefix."form2type WHERE formTypeRemember = '1'");
		}

		return ($form_id > 0);
	}

	function export_personal_data($email_address, $page = 1)
	{
		$number = 200;
		$page = (int)$page;

		$group_id = $this->meta_prefix;
		$group_label = __("Forms", 'lang_form');

		$export_items = [];

		$tbl_group = new mf_answer_table(array('search' => $email_address));

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated",
			'limit' => (($page - 1) * $number),
			'amount' => $number,
			'debug' => true,
			'debug_type' => 'log',
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
			'debug' => true,
			'debug_type' => 'log',
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
			'messages' => [], // no messages in this example
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

	function delete_answer($answer_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d'", $answer_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d'", $answer_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d'", $answer_id));

		return $wpdb->rows_affected;
	}

	function wp_sitemaps_post_types($post_types)
	{
		unset($post_types[$this->post_type]);

		return $post_types;
	}

	function phpmailer_init($phpmailer)
	{
		if(is_user_logged_in() && defined('IS_ADMINISTRATOR') && IS_ADMINISTRATOR && get_option('setting_form_test_emails') == 'yes')
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

		else if(get_option('setting_form_redirect_emails') == 'yes')
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

	function api_form_fetch_info()
	{
		$arr_fields = check_var('arr_fields');

		$out = [];

		foreach($arr_fields as $key => $arr_value)
		{
			$value_temp = "";

			switch($arr_value[1])
			{
				case 'address':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_address_street', get_current_user_id());
					}
				break;

				case 'city':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_address_city', get_current_user_id());
					}
				break;

				case 'country':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_country', get_current_user_id());

						if($value_temp > 0 && is_plugin_active("mf_address/index.php"))
						{
							global $obj_address;

							$value_temp = $obj_address->get_countries_for_select()[$value_temp];
						}
					}
				break;

				case 'email':
					if(is_user_logged_in())
					{
						$user_data = get_userdata(get_current_user_id());

						$value_temp = $user_data->user_email;
					}
				break;

				case 'name':
					if(is_user_logged_in())
					{
						$user_data = get_userdata(get_current_user_id());

						$value_temp = $user_data->display_name;
					}
				break;

				//case 'tel':
				case 'telno':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_phone', get_current_user_id());
					}
				break;

				case 'zip':
					if(is_user_logged_in())
					{
						$value_temp = get_the_author_meta('profile_address_zipcode', get_current_user_id());
					}
				break;
			}

			if($value_temp == '')
			{
				$value_temp = apply_filters('filter_visitor_'.$arr_value[1], '');
			}

			if($value_temp != '')
			{
				$out[] = array('id' => $arr_value[0], 'value' => $value_temp);
			}
		}

		$json_output = array(
			'success' => true,
			'response_fields' => $out,
		);

		header("Content-Type: application/json");
		echo json_encode($json_output);
		die();
	}

	function api_form_nonce()
	{
		$form_id = check_var('form_id', 'int');

		$json_output = array(
			'success' => true,
			'html' => input_hidden(array('name' => 'form_submit_'.$form_id, 'value' => $this->form_nonce_hash)),
		);

		header("Content-Type: application/json");
		echo json_encode($json_output);
		die();
	}

	function save_options($intForm2TypeID, $arrFormTypeSelect_id, $arrFormTypeSelect_key, $arrFormTypeSelect_value, $arrFormTypeSelect_limit, $arrFormTypeSelect_action)
	{
		global $wpdb;

		$count_temp = count($arrFormTypeSelect_value);

		for($i = 0; $i < $count_temp; $i++)
		{
			if(isset($arrFormTypeSelect_id[$i]) && $arrFormTypeSelect_id[$i] > 0)
			{
				$wpdb->get_results($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d' AND formOptionID = '%d'", $intForm2TypeID, $arrFormTypeSelect_id[$i]));
				$rows = $wpdb->num_rows;

				if($rows == 1)
				{
					if($arrFormTypeSelect_value[$i] != '')
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form_option SET formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionAction = '%d', formOptionOrder = '%d' WHERE form2TypeID = '%d' AND formOptionID = '%d'", $arrFormTypeSelect_key[$i], $arrFormTypeSelect_value[$i], $arrFormTypeSelect_limit[$i], $arrFormTypeSelect_action[$i], $i, $intForm2TypeID, $arrFormTypeSelect_id[$i]));

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
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d' AND formOptionID = '%d'", $intForm2TypeID, $arrFormTypeSelect_id[$i]));

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
				if(isset($arrFormTypeSelect_value[$i]) && $arrFormTypeSelect_value[$i] != '')
				{
					$intFormOptionOrder_temp = $wpdb->get_var($wpdb->prepare("SELECT formOptionOrder FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder DESC LIMIT 0, 1", $intForm2TypeID));

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_option SET form2TypeID = '%d', formOptionKey = %s, formOptionValue = %s, formOptionLimit = '%d', formOptionAction = '%d', formOptionOrder = '%d'",
						$intForm2TypeID,
						$arrFormTypeSelect_key[$i],
						$arrFormTypeSelect_value[$i],
						$arrFormTypeSelect_limit[$i],
						$arrFormTypeSelect_action[$i],
						($intFormOptionOrder_temp + 1)
					));

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

		if($strAnswerText != '')
		{
			$strAnswerText = $wpdb->get_var($wpdb->prepare("SELECT formOptionValue FROM ".$wpdb->prefix."form_option WHERE formOptionID = '%d'", $strAnswerText));
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
			$result = $wpdb->get_results("SELECT formOptionValue FROM ".$wpdb->prefix."form_option WHERE formOptionID IN ('".implode("','", $arr_answer_text)."') AND formOptionKey != '0'");

			foreach($result as $r)
			{
				$strAnswerText .= ($strAnswerText != '' ? ", " : "").$r->formOptionValue;
			}
		}

		if($strAnswerText == '')
		{
			$strAnswerText = implode(",", $arr_answer_text);
		}

		return $strAnswerText;
	}

	function get_form_types_for_select($data = [])
	{
		global $wpdb;

		if(!isset($data['form_type_id'])){		$data['form_type_id'] = 0;}

		$arr_data = [];
		$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";

		foreach($this->arr_form_types as $key => $arr_value)
		{
			if($data['form_type_id'] != $key && in_array($arr_value['code'], array('radio_button')))
			{
				//Don't let new fields be old style radio button
			}

			else if(in_array($data['form_type_id'], array(10, 11, 16, 17)) && !in_array($arr_value['code'], array('select', 'select_multiple', 'checkbox_multiple', 'radio_multiple')))
			{
				//Don't let the user change from any of these fields to one that does not have the same structure
			}

			else if(in_array($data['form_type_id'], array(2, 3, 4, 5, 6, 7, 9, 12, 15)) && !in_array($arr_value['code'], array('range', 'input_field', 'textarea', 'text', 'space', 'datepicker', 'referer_url', 'hidden_field', 'file')))
			{
				//Don't let the user change from any of these fields to one that does not have the same structure
			}

			else if($data['form_type_id'] == 13 && $arr_value['code'] != 'custom_tag')
			{
				//Don't let the use change from Custom Tag
			}

			else
			{
				$arr_data[$key] = array(
					'name' => $arr_value['name'],
					'desc' => $arr_value['desc'],
				);
			}
		}

		return $arr_data;
	}

	function get_form_checks_for_select()
	{
		global $wpdb;

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_form')." --",
		);

		foreach($this->arr_form_check as $key => $arr_value)
		{
			$arr_data[$key] = $arr_value['name'];
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

	function is_select_value_used($data)
	{
		global $wpdb;

		$out = false;

		if($data['form2type_id'] > 0 && $data['option_id'] > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer WHERE form2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $data['form2type_id'], $data['option_id']));

			$out = ($wpdb->num_rows > 0);
		}

		return $out;
	}

	function fetch_request()
	{
		switch($this->type)
		{
			case 'create':
				$this->form2type_id = check_var('intForm2TypeID');
				$this->form2type_order = check_var('intForm2TypeOrder');

				$this->type_id = check_var('intFormTypeID');
				$this->type_text = (isset($_POST['strFormTypeText']) ? $_POST['strFormTypeText'] : ""); //Allow HTML here
				$this->type_text2 = check_var('strFormTypeText2');
				$this->check_id = check_var('intCheckID');
				$this->type_placeholder = check_var('strFormTypePlaceholder');
				$this->type_tag = check_var('strFormTypeTag');
				$this->type_class = check_var('strFormTypeClass');
				$this->type_length = check_var('intFormTypeLength');
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

				// Range
				$this->type_min = check_var('strFormTypeMin', '', true, "0");
				$this->type_max = check_var('strFormTypeMax', '', true, 100);
				$this->type_default = check_var('strFormTypeDefault', '', true, 1);
			break;

			default:
				$this->answer_id = 0;
				$answer_id_encrypted = check_var('answer_id_encrypted');

				if($answer_id_encrypted != '')
				{
					$obj_encryption = new mf_encryption(__CLASS__);
					$this->answer_id = $obj_encryption->decrypt($answer_id_encrypted, md5(AUTH_KEY));
				}
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
			$intFormTypeConnectTo = $wpdb->get_var($wpdb->prepare("SELECT formTypeConnectTo FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $type_connect_to_temp));

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

		$success = true;
		$arr_form2type_id = [];

		$form_id_old = $data['id'];
		$post_id_old = $this->get_post_id($form_id_old);

		$post_id_new = wp_insert_post(array(
			'post_type' => $this->post_type,
			'post_status' => 'publish',
			'post_title' => $this->get_form_name($form_id_old)." (".__("copy", 'lang_form').")",
		));

		$arr_post_meta = get_post_meta($post_id_old);

		if(is_array($arr_post_meta))
		{
			foreach($arr_post_meta as $meta_key => $meta_values)
			{
				foreach($meta_values as $meta_value)
				{
					add_post_meta($post_id_new, $meta_key, $meta_value);
				}
			}
		}

		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form (postID) (SELECT %d FROM ".$wpdb->prefix."form WHERE formID = '%d')", $post_id_new, $form_id_old));
		$form_id_new = $wpdb->insert_id;

		if($form_id_new > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' ORDER BY form2TypeID DESC", $form_id_old));

			foreach($result as $r)
			{
				$intForm2TypeID = $r->form2TypeID;

				$copy_fields = "formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeLength, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeEncrypt, formTypeRemember, form2TypeOrder";

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2type (formID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d')", $form_id_new, $intForm2TypeID));
				$intForm2TypeID_new = $wpdb->insert_id;

				if($intForm2TypeID_new > 0)
				{
					$arr_form2type_id[$intForm2TypeID] = $intForm2TypeID_new;

					$copy_fields = "formOptionKey, formOptionValue, formOptionLimit, formOptionOrder, formOptionAction";

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_option (form2TypeID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d')", $intForm2TypeID_new, $intForm2TypeID));
				}

				else
				{
					$success = false;
				}

				if($data['include_answers'] == true)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer WHERE formID = '%d'", $form_id_old));

					foreach($result as $r)
					{
						$intAnswerID = $r->answerID;

						$copy_fields = "answerIP, answerFingerprint, answerSpam, spamID, answerCreated";

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2answer (formID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d')", $form_id_new, $intAnswerID));

						$intAnswerID_new = $wpdb->insert_id;

						if($intAnswerID_new > 0)
						{
							$result_answer = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, answerText FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d'", $intAnswerID));

							foreach($result_answer as $r)
							{
								$intForm2TypeID = $r->form2TypeID;
								$strAnswerText = $r->answerText;

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s, answerUpdated = NOW()", $intAnswerID_new, $arr_form2type_id[$intForm2TypeID], $strAnswerText));
							}

							$copy_fields = "answerEmailFrom, answerEmail, answerType, answerSent";

							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer_email (answerID, ".$copy_fields.") (SELECT %d, ".$copy_fields." FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d')", $intAnswerID_new, $intAnswerID));
						}
					}
				}
			}

			update_post_meta($post_id_new, $this->meta_prefix.'form_id', $form_id_new);
		}

		else
		{
			$success = false;
		}

		return $success;
	}

	function create_form($post_id = 0)
	{
		global $wpdb;

		if($post_id > 0)
		{
			$this->post_id = $post_id;
		}

		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form SET postID = '%d'", $this->post_id));
		$this->id = $wpdb->insert_id;

		update_post_meta($this->post_id, $this->meta_prefix.'form_id', $this->id);

		return $this->id;
	}

	function save_data()
	{
		global $wpdb, $error_text, $notice_text, $done_text;

		$out = "";

		switch($this->type)
		{
			case 'create':
				if(isset($_POST['btnFormAdd']) && wp_verify_nonce($_POST['_wpnonce_form_add'], 'form_add_'.$this->id))
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
								if($this->form2type_id > 0)
								{
									$this->save_options($this->form2type_id, $this->arr_type_select_id, $this->arr_type_select_key, $this->arr_type_select_value, $this->arr_type_select_limit, $this->arr_type_select_action);
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
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeLength = %s, formTypeFetchFrom = %s, formTypeConnectTo = '%d', formTypeActionEquals = %s, formTypeActionShow = %s WHERE form2TypeID = '%d'", $this->type_id, $this->type_text, $this->type_placeholder, $this->check_id, $this->type_tag, $this->type_class, $this->type_length, $this->type_fetch_from, $this->type_connect_to, $this->type_action_equals, $this->type_action_show, $this->form2type_id));

								switch($this->type_id)
								{
									case 13:
									//case 'custom_tag':
										$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET formTypeText = %s, userID = '%d' WHERE form2TypeID2 = '%d'", $this->type_text, get_current_user_id(), $this->form2type_id));
									break;
								}

								$this->form2type_id = $this->type_id = $this->type_text = $this->type_placeholder = $this->check_id = $this->type_tag = $this->type_class = $this->type_length = $this->type_fetch_from = $this->type_action_equals = $this->type_action_show = "";
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
								$this->form2type_order = $wpdb->get_var($wpdb->prepare("SELECT (form2TypeOrder + 1) FROM ".$wpdb->prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder DESC", $this->id));

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2type SET formID = '%d', formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeLength = %s, formTypeFetchFrom = %s, formTypeConnectTo = '%d', formTypeActionEquals = %s, formTypeActionShow = %s, form2TypeOrder = '%d'", $this->id, $this->type_id, $this->type_text, $this->type_placeholder, $this->check_id, $this->type_tag, $this->type_class, $this->type_length, $this->type_fetch_from, $this->type_connect_to, $this->type_action_equals, $this->type_action_show, $this->form2type_order));

								$this->form2type_id = $wpdb->insert_id;

								switch($this->type_id)
								{
									case 13:
									//case 'custom_tag':
										$this->type_id = 14;
										$this->form2type_order++;

										$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form2type SET form2TypeID2 = '%d', formID = '%d', formTypeID = '%d', formTypeText = %s, form2TypeOrder = '%d'", $this->form2type_id, $this->id, $this->type_id, $this->type_text, $this->form2type_order));
									break;

									case 10:
									//case 'select':
									case 11:
									//case 'select_multiple':
									case 16:
									//case 'checkbox_multiple':
									case 17:
									//case 'radio_multiple':
										$this->save_options($this->form2type_id, $this->arr_type_select_id, $this->arr_type_select_key, $this->arr_type_select_value, $this->arr_type_select_limit, $this->arr_type_select_action);
									break;
								}

								if($wpdb->rows_affected > 0)
								{
									$this->form2type_id = $this->type_id = $this->type_text = $this->type_placeholder = $this->check_id = $this->type_tag = $this->type_class = $this->type_length = $this->type_fetch_from = $this->type_action_equals = $this->type_action_show = "";
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
					}
				}

				if(isset($_GET['btnPageCreate']) && wp_verify_nonce($_GET['_wpnonce_page_create'], 'page_create_'.$this->id))
				{
					$post_id = wp_insert_post(array(
						'post_type' => 'page',
						'post_status' => 'draft',
						'post_title' => $this->form_name,
						'post_content' => '<!-- wp:mf/form {"form_id":"'.$this->id.'"} /-->',
					));

					mf_redirect(admin_url("post.php?post=".$post_id."&action=edit"));
				}

				if(!isset($_POST['btnFormAdd']) && $this->form2type_id > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeLength, formTypeFetchFrom, formTypeConnectTo, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $this->form2type_id));

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
							$this->type_length = $r->formTypeLength;
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
									$form2type_id_temp = $this->get_type_connect_to_root(array('connect_to' => $this->type_connect_to, 'field_id' => $this->form2type_id));

									$result = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionKey, formOptionValue, formOptionLimit, formOptionAction FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $form2type_id_temp));

									$this->arr_type_select_id = $this->arr_type_select_key = $this->arr_type_select_value = $this->arr_type_select_limit = $this->arr_type_select_action = [];

									foreach($result as $r)
									{
										$this->arr_type_select_id[] = $r->formOptionID;
										$this->arr_type_select_key[] = $r->formOptionKey;
										$this->arr_type_select_value[] = $r->formOptionValue;
										$this->arr_type_select_limit[] = $r->formOptionLimit;
										$this->arr_type_select_action[] = $r->formOptionAction;
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
				}
			break;

			default:
				if(isset($_POST['btnAnswerUpdate']))
				{
					$this->prefix = $this->get_post_info(array('select' => 'post_name'))."_";

					$answer_id_encrypted = check_var('answer_id_encrypted');

					$obj_encryption = new mf_encryption(__CLASS__);
					$this->answer_id = $obj_encryption->decrypt($answer_id_encrypted, md5(AUTH_KEY));

					$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, checkID, formTypeRequired FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND formTypeID != '%d' ORDER BY form2TypeOrder ASC", $this->id, 13));

					foreach($result as $r)
					{
						$intForm2TypeID2 = $r->form2TypeID;
						$strFormTypeCode = $this->arr_form_types[$r->formTypeID]['code'];
						$strCheckCode = ($r->checkID > 0 && isset($this->arr_form_check[$r->checkID]) ? $this->arr_form_check[$r->checkID]['code'] : 'char');
						$intFormTypeRequired = $r->formTypeRequired;

						$strAnswerText = check_var($this->prefix.$intForm2TypeID2, $strCheckCode, true, '', true, 'post');

						if($strAnswerText != '')
						{
							$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $intForm2TypeID2));

							if($wpdb->num_rows > 0)
							{
								$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $this->answer_id, $intForm2TypeID2, $strAnswerText));

								if($wpdb->num_rows == 0)
								{
									$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s, answerUpdated = NOW()", $this->answer_id, $intForm2TypeID2, $strAnswerText));
								}
							}

							else
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s, answerUpdated = NOW()", $this->answer_id, $intForm2TypeID2, $strAnswerText));
							}
						}

						else if($strFormTypeCode == 'radio_button')
						{
							$strAnswerText_radio = (isset($_POST["radio_".$intForm2TypeID2]) ? check_var($_POST["radio_".$intForm2TypeID2], 'int', false) : '');

							if($strAnswerText_radio != '')
							{
								$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $strAnswerText_radio));

								if($wpdb->num_rows == 0)
								{
									$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = '', answerUpdated = NOW()", $this->answer_id, $strAnswerText_radio));
								}
							}
						}

						else if($intFormTypeRequired == 0 && in_array($strFormTypeCode, array('range', 'input_field', 'textarea', 'text', 'datepicker')))
						{
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s, answerUpdated = NOW()", $this->answer_id, $intForm2TypeID2, $strAnswerText));
						}
					}

					if(!isset($error_text) || $error_text == '')
					{
						//mf_redirect(admin_url("admin.php?page=mf_form/answer/index.php&intFormID=".$this->id));
						$done_text = __("I have updated the answer for you", 'lang_form')." (".$this->id.", ".$this->answer_id.")";
					}
				}

				else if(isset($_GET['btnAnswerSpam']))
				{
					$intAnswerID = check_var('intAnswerID');

					if(wp_verify_nonce($_REQUEST['_wpnonce_answer_spam'], 'answer_spam_'.$intAnswerID))
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerSpam = '%d' WHERE answerID = '%d'", 1, $intAnswerID));
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerStatus = %s WHERE answerID = '%d'", 'spam', $intAnswerID));

						$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, answerIP, answerFingerprint FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d'", $intAnswerID));

						foreach($result as $r)
						{
							$intAnswerID = $r->answerID;
							$strAnswerIP = $r->answerIP;
							$strAnswerFingerprint = $r->answerFingerprint;

							$strAnswerEmail = $this->get_answer_email($intAnswerID);

							if($strAnswerIP != '')
							{
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerSpam = '%d' WHERE answerID != '%d' AND answerIP = %s AND answerSpam = '0'", 1, $intAnswerID, $strAnswerIP));
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerStatus = %s WHERE answerID = '%d'", 'spam_ip', $intAnswerID));
							}

							if($strAnswerFingerprint != '')
							{
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerSpam = '%d' WHERE answerID != '%d' AND answerFingerprint = %s AND answerSpam = '0'", 1, $intAnswerID, $strAnswerFingerprint));
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerStatus = %s WHERE answerID = '%d'", 'spam_fingerprint', $intAnswerID));
							}

							if($strAnswerEmail != '')
							{
								$intForm2TypeID = $this->get_form_email_field();

								$resultEmail = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer INNER JOIN ".$wpdb->prefix."form2answer USING (answerID) WHERE answerID != '%d' AND form2TypeID = '%d' AND answerText = %s", $intAnswerID, $intForm2TypeID, $strAnswerEmail));

								foreach($resultEmail as $r)
								{
									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerSpam = '%d' WHERE answerID = '%d' AND answerSpam = '%d'", 1, $r->answerID, 0));
									$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerStatus = %s WHERE answerID = '%d'", 'spam_email', $r->answerID));
								}
							}
						}

						$done_text = __("I have marked the email as spam for you", 'lang_form');
					}
				}

				else if(isset($_GET['btnAnswerApprove']))
				{
					$intAnswerID = check_var('intAnswerID');

					if(wp_verify_nonce($_REQUEST['_wpnonce_answer_approve'], 'answer_approve_'.$intAnswerID))
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerSpam = '%d' WHERE answerID = '%d'", 0, $intAnswerID));
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerStatus = %s WHERE answerID = '%d'", 'not_spam', $intAnswerID));

						$done_text = __("I have approved the answer for you", 'lang_form');
					}
				}

				else if(isset($_GET['btnAnswerStatus']))
				{
					$intAnswerID = check_var('intAnswerID');

					if(wp_verify_nonce($_REQUEST['_wpnonce_answer_status'], 'answer_status_'.$intAnswerID))
					{
						$strAnswerStatus = check_var('strAnswerStatus');

						if($strAnswerStatus != '')
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2answer SET answerStatus = %s WHERE answerID = '%d'", $strAnswerStatus, $intAnswerID));

							$done_text = __("I marked the answer as done for you", 'lang_form');
						}

						else
						{
							$error_text = __("I could NOT change the status because what you sent was incorrect", 'lang_form');
						}
					}

					else
					{
						$error_text = __("I could NOT change the status because the validation was wrong", 'lang_form');

						if(IS_SUPER_ADMIN)
						{
							$error_text .= " (".$intAnswerID." -> ".$_REQUEST['_wpnonce_answer_status'].")";
						}
					}
				}

				else if(isset($_GET['btnMessageResend']))
				{
					$intAnswerID = check_var('intAnswerID');

					if(wp_verify_nonce($_REQUEST['_wpnonce_message_resend'], 'message_resend_'.$intAnswerID))
					{
						$this->form_name = $this->get_form_name();
						$this->prefix = $this->get_post_info(array('select' => 'post_name'))."_";

						$this->answer_data = [];

						$this->arr_email_content = array(
							'fields' => [],
						);

						$result = $wpdb->get_results($wpdb->prepare("SELECT ".$wpdb->prefix."form2type.form2TypeID, formTypeID, formTypeText, checkID, answerText FROM ".$wpdb->prefix."form2type LEFT JOIN ".$wpdb->prefix."form_answer ON ".$wpdb->prefix."form2type.form2TypeID = ".$wpdb->prefix."form_answer.form2TypeID WHERE formID = '%d' AND (answerID = '%d' OR answerID IS null) ORDER BY form2TypeOrder ASC", $this->id, $intAnswerID));

						foreach($result as $r)
						{
							$intForm2TypeID2 = $r->form2TypeID;
							$strFormTypeCode = $this->arr_form_types[$r->formTypeID]['code'];
							$this->label = $r->formTypeText;
							$strCheckCode = ($r->checkID > 0 && isset($this->arr_form_check[$r->checkID]) ? $this->arr_form_check[$r->checkID]['code'] : 'char');
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

								case 'file':
									$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

									foreach($result as $r)
									{
										$strAnswerText = "<a href='".$r->guid."'>".$r->post_title."</a>";
									}
								break;
							}

							$this->arr_email_content['fields'][$intForm2TypeID2] = array(
								'type' => $strFormTypeCode,
								'label' => $this->label,
								'value' => $strAnswerText,
							);

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

						$resultAnswerEmail = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerType FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d' AND answerType != ''", $intAnswerID));

						foreach($resultAnswerEmail as $r)
						{
							$strAnswerEmail = $r->answerEmail;
							$strAnswerType = $r->answerType;

							switch($strAnswerType)
							{
								/*case 'replace_link':
									$this->send_to = $strAnswerEmail;
								break;*/

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

						$done_text = __("I have resent the messages for you", 'lang_form');
					}
				}
			break;
		}

		return $out;
	}

	function get_amount($data = [])
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->posts." WHERE post_type = %s", $this->post_type));
	}

	function is_poll()
	{
		global $wpdb;

		if(!isset($data['id'])){	$data['id'] = $this->id;}

		$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND formTypeID IN('1', '8', '10', '11', '16', '17') LIMIT 0, 1", $data['id']));
		$rows_poll_fields = $wpdb->num_rows;

		$wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND formTypeID IN('2', '3', '4', '7', '9', '12', '15') LIMIT 0, 1", $data['id']));
		$rows_input_fields = $wpdb->num_rows;

		return ($rows_poll_fields > 0 && $rows_input_fields == 0);
	}

	/*function is_correct_form($data)
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
	}*/

	function get_form_name($id = 0)
	{
		if($id > 0)
		{
			$this->id = $id;
		}

		$form_name = $this->get_post_info(array('select' => 'post_title'));

		return ($form_name != '' ? $form_name : __("Unknown", 'lang_form')." (#".$id.")");
	}

	function get_form_id($post_id)
	{
		global $wpdb;

		$this->id = get_post_meta($post_id, $this->meta_prefix.'form_id', true);

		if(!($this->id > 0))
		{
			$this->id = $this->create_form($post_id);
		}

		return $this->id;
	}

	function get_post_id($id = 0)
	{
		global $wpdb;

		if($id > 0){	$this->id = $id;}

		if(!($this->post_id > 0))
		{
			$this->post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value = '%d' GROUP BY ID LIMIT 0, 1", $this->post_type, $this->meta_prefix.'form_id', $this->id));
		}

		return $this->post_id;
	}

	function get_post_info($data)
	{
		global $wpdb;

		$out = "";

		if(isset($data['form_id']) && $data['form_id'] > 0)
		{
			$this->id = $data['form_id'];
		}

		if(isset($data['post_id']) && $data['post_id'] > 0)
		{
			$post_id = $data['post_id'];
		}

		else if($this->id > 0)
		{
			$post_id = $this->get_post_id($this->id);
		}

		if($post_id > 0)
		{
			$out = $wpdb->get_var($wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->posts." WHERE ID = '%d'", $post_id));
		}

		if($data['select'] == "post_name" && $out == '')
		{
			$out = "field";
		}

		return $out;
	}

	function get_form_email_field()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND checkID = '5'", $this->id));
	}

	function get_answer_email($intAnswerID)
	{
		global $wpdb;

		if(!($this->id > 0))
		{
			$this->id = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->prefix."form2answer WHERE answerID = '%d'", $intAnswerID));
		}

		$intForm2TypeID = $this->get_form_email_field();

		return $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $intAnswerID, $intForm2TypeID));
	}

	function is_form_field_type_used($data)
	{
		global $wpdb;

		$out = 0;

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

		if(isset($data['encrypt']))
		{
			$query_where .= " AND formTypeEncrypt = '".$data['encrypt']."'";
		}

		if(isset($data['remember']))
		{
			$query_where .= " AND formTypeRemember = '".$data['remember']."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, checkID FROM ".$wpdb->prefix."form2type".$query_join." WHERE formID = '%d'".$query_where." LIMIT 0, 1", $this->id));

		foreach($result as $r)
		{
			if(isset($data['check_code']) && $data['check_code'] != '')
			{
				if($r->checkID > 0 && $this->arr_form_check[$r->checkID]['code'] == $data['check_code'])
				{
					$out++;
				}
			}

			else
			{
				$out++;
			}
		}

		return $out;
	}

	function get_answer_amount($data)
	{
		global $wpdb;

		if(!isset($data['is_spam'])){		$data['is_spam'] = 0;}

		$wpdb->get_results($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->prefix."form2answer INNER JOIN ".$wpdb->prefix."form_answer USING (answerID) WHERE formID = '%d' AND answerSpam = '%d' GROUP BY answerID", $data['form_id'], $data['is_spam']));

		return $wpdb->num_rows;
	}

	function get_form_type_info($data = [])
	{
		global $wpdb;

		if(!isset($data['form_id'])){			$data['form_id'] = 0;}
		//if(!isset($data['query_type_id'])){	$data['query_type_id'] = [];}
		if(!isset($data['query_type_code'])){	$data['query_type_code'] = [];}
		if(!isset($data['query_exclude_id'])){	$data['query_exclude_id'] = 0;}

		if($data['form_id'] > 0)
		{
			$this->id = $data['form_id'];
		}

		$query_where = "";

		/*if(count($data['query_type_id']) > 0)
		{
			$query_where .= " AND formTypeID IN ('".implode("','", $data['query_type_id'])."')";
		}*/

		if(count($data['query_type_code']) > 0)
		{
			$data['query_type_id'] = [];

			foreach($this->arr_form_types as $key => $arr_value)
			{
				if(in_array($arr_value['code'], $data['query_type_code']))
				{
					$data['query_type_id'][] = $key;
				}
			}

			$query_where .= " AND formTypeID IN ('".implode("','", $data['query_type_id'])."')";
		}

		if($data['query_exclude_id'] > 0)
		{
			$query_where .= " AND form2TypeID != '".$data['query_exclude_id']."'";
		}

		return $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeText, form2TypeOrder FROM ".$wpdb->prefix."form2type WHERE formID = '%d'".$query_where." ORDER BY form2TypeOrder ASC", $this->id));
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
		$arr_shortcodes = $arr_values = [];
		$arr_shortcodes[] = "[answer_id]";		$arr_values[] = $this->answer_id;

		$this->mail_data['subject'] = str_replace($arr_shortcodes, $arr_values, $this->mail_data['subject']);
		$this->mail_data['subject'] = preg_replace_callback("/\[label_(.*?)\]/", array($this, 'preg_replace_label'), $this->mail_data['subject']);
		$this->mail_data['subject'] = preg_replace_callback("/\[answer_(.*?)\]/", array($this, 'preg_replace_answer'), $this->mail_data['subject']);
	}

	function get_form_url($form_id)
	{
		$out = "#";

		if($form_id > 0)
		{
			$arr_ids = apply_filters('get_page_from_block_code', [], '<!-- wp:mf/form {"form_id":"'.$form_id.'"} /-->');

			if(count($arr_ids) > 0)
			{
				foreach($arr_ids as $post_id_temp)
				{
					$out = get_permalink($post_id_temp);
				}
			}

			else
			{
				$out = get_permalink($this->get_post_id($form_id));
			}
		}

		return $out;
	}

	function render_mail_content($data = [])
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
							case 'custom_tag':
							case 'custom_tag_end':
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
			$link_base_url = $this->get_form_url($this->id)."?btnVar"
				."&answer_email=".$data['mail_to']
				."&answer_id=".$this->answer_id
				."&product_id=".$intProductID
				."&hash=".md5((defined('NONCE_SALT') ? NONCE_SALT : '')."_".$this->answer_id."_".$intProductID);

			$arr_exclude = $arr_include = [];
			$arr_exclude[] = "[heading]";		$arr_include[] = $this->mail_data['subject'];
			$arr_exclude[] = "[content]";		$arr_include[] = $out_fields;
			$arr_exclude[] = "[answer_id]";		$arr_include[] = $this->answer_id;
			$arr_exclude[] = "[form_fields]";	$arr_include[] = $out_fields;
			$arr_exclude[] = "[doc_types]";		$arr_include[] = $out_doc_types;
			$arr_exclude[] = "[products]";		$arr_include[] = $out_products;
			$arr_exclude[] = "[product]";		$arr_include[] = $strProductName;
			$arr_exclude[] = "[link_yes]";		$arr_include[] = str_replace("btnVar", "btnFormLinkYes", $link_base_url);
			$arr_exclude[] = "[link_no]";		$arr_include[] = str_replace("btnVar", "btnFormLinkNo", $link_base_url);

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
			$result = $wpdb->get_results($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE ID = '%d'", $this->page_content_data['page_id'])); //post_title,

			foreach($result as $r)
			{
				//$this->mail_data['subject'] = $r->post_title; // Only if we want the form name to be replaced by the template name
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

		$out = 0;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeID, checkID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND formTypeID = '%d'", $this->id, 3));

		foreach($result as $r)
		{
			if($r->checkID > 0 && $this->arr_form_check[$r->checkID]['code'] == 'email')
			{
				$out++;
			}
		}

		return $out;
	}

	function get_form_type_for_select($data)
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}

		$arr_data = [];

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";
		}

		foreach($data['result'] as $r)
		{
			if(in_array($this->arr_form_types[$r->formTypeID]['code'], array('select', 'select_multiple', 'checkbox_multiple', 'radio_multiple')))
			{
				@list($strFormTypeText, $str_select) = explode(":", $r->formTypeText);
			}

			else if(in_array($this->arr_form_types[$r->formTypeID]['code'], array('custom_tag')))
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
		if(apply_filters('is_theme_active', false))
		{
			return preg_match("/(".$obj_theme_core->get_wp_title().")/", $string);
		}

		return false;
	}

	function contains_phone_numbers($string)
	{
		$string = str_replace(array(" ", "(", ")", "-", ".", ","), "", $string);

		return preg_match('/\d{8,}/', $string); // (^(([\+]\d{1,3})?[ \.-]?[\(]?\d{3}[\)]?)?[ \.-]?\d{3}[ \.-]?\d{4}$) or ([0-9]{3}\s*\([0-9]{3}\)\s*[0-9]{4}) or (\[[0-9]{2}\.[0-9]{1},[0-9]{2}-[0-9]{1}-[0-9]{2}\.[0-9]{2}\])
	}

	function get_spam_rules($data = [])
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
					$arr_data[$key] = [];
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
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer INNER JOIN ".$wpdb->prefix."form_answer USING (answerID) WHERE answerSpam = '1' AND answerText = %s LIMIT 0, 1", $data['text']));

			if($wpdb->num_rows > 0)
			{
				$this->is_spam = true;
				$this->is_spam_id = 6;
			}
		}
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

		return $wpdb->get_var($wpdb->prepare("SELECT formOptionKey FROM ".$wpdb->prefix."form_option WHERE formOptionID = '%d'", $id));
	}

	function get_option_id_from_key($key)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formOptionID FROM ".$wpdb->prefix."form_option WHERE formOptionKey = '%d'", $key));
	}

	function process_transactional_emails()
	{
		global $wpdb;

		$this->get_post_id();

		$this->email_admin = get_post_meta($this->post_id, $this->meta_prefix.'email_admin', true);

		$email_notify = get_post_meta($this->post_id, $this->meta_prefix.'email_notify', true);
		$email_notify_page = get_post_meta($this->post_id, $this->meta_prefix.'email_notify_page', true);
		$email_confirm = get_post_meta($this->post_id, $this->meta_prefix.'email_confirm', true);
		$email_confirm_page = get_post_meta($this->post_id, $this->meta_prefix.'email_confirm_page', true);
		$email_conditions = get_post_meta($this->post_id, $this->meta_prefix.'email_conditions', true);

		$arr_email_fields = $this->get_email_fields();
		$email_confirm_id = (isset($arr_email_fields[0]) ? $arr_email_fields[0] : 0);

		$this->page_content_data = array(
			'subject' => $this->form_name,
			'content' => $this->arr_email_content,
		);

		if($email_conditions != '')
		{
			foreach(explode("\n", $email_conditions) as $str_condition)
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

		$email_from_visitor = $email_from_admin_address = $email_from_admin = ""; //$email_from_visitor_address = $email_from_other_address = $email_from_other =

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

			$email_from_visitor = "Reply-To: ".$name_temp." <".$this->answer_data['email'].">\r\n";
		}
		###################

		// From Admin
		###################
		$email_from_admin = "Reply-To: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";
		###################

		if($email_notify == 'yes')
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
				$this->mail_data['from'] = $email_from_admin_address;
				$this->mail_data['headers'] = $email_from_visitor;
			}

			$this->page_content_data['mail_to'] = $this->mail_data['to'];
			$this->page_content_data['page_id'] = $email_notify_page;

			$this->mail_data['content'] = $this->get_page_content_for_email();

			$this->send_transactional_email();
		}

		if($email_confirm == 'yes' && isset($this->answer_data['email']) && $this->answer_data['email'] != '')
		{
			$this->mail_data = array(
				'type' => 'confirm',
				'to' => $this->answer_data['email'],
				'subject' => $this->page_content_data['subject'],
				'content' => '',
			);

			if($email_from_admin != '')
			{
				$this->mail_data['from'] = $email_from_admin_address;
				$this->mail_data['headers'] = $email_from_admin;
			}

			$this->page_content_data['mail_to'] = $this->mail_data['to'];
			$this->page_content_data['page_id'] = $email_confirm_page;

			$this->mail_data['content'] = $this->get_page_content_for_email();

			$this->send_transactional_email();
		}
	}

	function send_transactional_email()
	{
		global $wpdb;

		if($this->is_spam == false)
		{
			$sent = send_email($this->mail_data);
		}

		else
		{
			$sent = false;
		}

		if($this->answer_id > 0)
		{
			if(!isset($this->mail_data['from']) || $this->mail_data['from'] == '')
			{
				$this->mail_data['from'] = get_bloginfo('admin_email');
			}

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."form_answer_email SET answerID = '%d', answerEmailFrom = %s, answerEmail = %s, answerType = %s, answerSent = '%d'", $this->answer_id, $this->mail_data['from'], $this->mail_data['to'], $this->mail_data['type'], $sent));
		}

		return $sent;
	}

	function check_limit($data)
	{
		global $wpdb, $error_text;

		$arr_data = [];

		@list($str_label, $str_select) = explode(":", $data['string']);

		if($str_select != '')
		{
			$arr_options = explode(",", $str_select);

			foreach($arr_options as $str_option)
			{
				$arr_option = explode("|", $str_option);

				if($arr_option[0] == $data['value'])
				{
					if(isset($arr_option[2]) && $arr_option[2] > 0)
					{
						$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer INNER JOIN ".$wpdb->prefix."form_answer USING (answerID) WHERE formID = '%d' AND form2TypeID = '%d' AND answerText = %s AND answerSpam = '0' GROUP BY answerID", $this->id, $data['form2TypeID'], $arr_option[0]));
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
	}

	function get_field_name($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formTypeText FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $id));
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

			$obj_form->init();

			$obj_form->id = $this->type;
			$this->name = $obj_form->get_form_name();

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeLength, formTypeFetchFrom, formTypeConnectTo, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeEncrypt, formTypeRemember, form2TypeOrder FROM ".$wpdb->prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder ASC", $this->type));

			foreach($result as $r)
			{
				switch($obj_form->arr_form_types[$r->formTypeID]['code'])
				{
					case 'select':
					case 'select_multiple':
					case 'checkbox_multiple':
					case 'radio_multiple':
						$i = 0;

						$result2 = $wpdb->get_results($wpdb->prepare("SELECT formOptionKey, formOptionValue, formOptionLimit, formOptionAction FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $r->form2TypeID));

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
					$r->formTypeEncrypt,
					$r->formTypeRemember,
					$r->form2TypeOrder,
					$r->formTypeLength,
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

			$obj_form->init();

			$obj_form->id = $this->type;
			$this->name = $obj_form->get_form_name();

			$search = check_var('s');

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeText, formTypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder ASC", $this->type));

			$this_row = [];

			foreach($result as $r)
			{
				$intForm2TypeID = $r->form2TypeID;
				$obj_form->label = $r->formTypeText;
				$strFormTypeCode = $obj_form->arr_form_types[$r->formTypeID]['code'];

				if(!in_array($strFormTypeCode, $obj_form->ignore_tags_on_output))
				{
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
			}

			$this_row[] = __("Created", 'lang_form');

			$this->data[] = $this_row;

			$query_join = " INNER JOIN ".$wpdb->prefix."form_answer USING (answerID)";
			$query_where = "";

			// (Almost) Same as in mf_answer_table()
			if($search != '')
			{
				$query_join .= " LEFT JOIN ".$wpdb->prefix."form_answer_email USING (answerID)";
				$query_join .= " LEFT JOIN ".$wpdb->prefix."form_option ON ".$wpdb->prefix."form_answer.answerText = ".$wpdb->prefix."form_option.formOptionID";

				$query_where .= " AND (answerText LIKE '%".$search."%' OR answerEmail LIKE '%".$search."%' OR answerCreated LIKE '%".$search."%'";
					$query_where .= " OR formOptionValue LIKE '%".$search."%'";

					if(preg_match('/[a-zA-Z]/', $search))
					{
						$query_where .= " OR SOUNDEX(answerText) = SOUNDEX('".$search."') OR SOUNDEX(answerEmail) = SOUNDEX('".$search."')";
						$query_where .= " OR SOUNDEX(formOptionValue) = SOUNDEX('".$search."')";
					}

				$query_where .= ")";
			}

			$result = $wpdb->get_results("SELECT answerID, formID, answerCreated FROM ".$wpdb->prefix."form2answer".$query_join." WHERE formID = '".esc_sql($this->type)."' AND answerSpam = '0'".$query_where." GROUP BY answerID ORDER BY answerCreated DESC");

			foreach($result as $r)
			{
				$intAnswerID = $r->answerID;
				$intFormID = $r->formID;
				$strAnswerCreated = $r->answerCreated;

				$this_row = [];

				$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeText, formTypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder ASC", $intFormID));

				foreach($resultText as $r)
				{
					$intForm2TypeID = $r->form2TypeID;
					$obj_form->label = $r->formTypeText;
					$strFormTypeCode = $obj_form->arr_form_types[$r->formTypeID]['code'];

					if(!in_array($strFormTypeCode, $obj_form->ignore_tags_on_output))
					{
						$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d'", $intForm2TypeID, $intAnswerID));
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
									$obj_form->prefix = $obj_form->get_post_info(array('select' => 'post_name'))."_";

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
				}

				$this_row[] = $strAnswerCreated;

				$this->data[] = $this_row;
			}
		}
	}
}

if(class_exists('mf_list_table'))
{
	class mf_answer_table extends mf_list_table
	{
		var $current_row = [];
		var $previous_row = [];

		function set_default()
		{
			global $wpdb, $obj_form;

			$this->arr_settings['query_from'] = $wpdb->prefix."form2answer";

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

			$obj_form->init();

			do_action('load_font_awesome');

			$this->query_join .= " INNER JOIN ".$wpdb->prefix."form_answer USING (answerID)";
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."formID = '".$obj_form->id."'";

			// Same as in mf_form_answer_export()
			if($this->search != '')
			{
				$this->query_join .= " LEFT JOIN ".$wpdb->prefix."form_answer_email USING (answerID)";
				$this->query_join .= " LEFT JOIN ".$wpdb->prefix."form_option ON ".$wpdb->prefix."form_answer.answerText = ".$wpdb->prefix."form_option.formOptionID";

				$this->query_where .= " AND (answerText LIKE '".$this->filter_search_before_like($this->search)."' OR answerEmail LIKE '".$this->filter_search_before_like($this->search)."' OR answerCreated LIKE '".$this->filter_search_before_like($this->search)."'";
					$this->query_where .= " OR formOptionValue LIKE '".$this->filter_search_before_like($this->search)."'";

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

			$arr_columns = [];
			$arr_columns['answerStatus'] = __("Status", 'lang_form');

			$obj_form->answer_column = 0;

			$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeText, form2TypeID, formTypeID FROM ".$wpdb->prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder ASC", $obj_form->id));

			foreach($result as $r)
			{
				$strFormTypeCode = $obj_form->arr_form_types[$r->formTypeID]['code'];
				$obj_form->label = $r->formTypeText;
				$intForm2TypeID2 = $r->form2TypeID;

				if(!in_array($strFormTypeCode, $obj_form->ignore_tags_on_output))
				{
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
			}

			$arr_columns['answerCreated'] = __("Created", 'lang_form');

			$this->set_columns($arr_columns);

			$this->set_sortable_columns(array(
				'answerCreated',
			));
		}

		function column_default($item, $column_name)
		{
			global $wpdb, $obj_form;

			$obj_form->init();

			$out = "";

			$intAnswerID = $item['answerID'];

			switch($column_name)
			{
				case 'answerStatus':
					$arr_actions = [];

					if($item['answerSpam'] == true)
					{
						$icon_title = "";

						if($item['spamID'] > 0)
						{
							$strSpamExplain = $obj_form->get_spam_rules(array('id' => $item['spamID'], 'type' => 'explain'));

							if($strSpamExplain != '')
							{
								$icon_title = $strSpamExplain;
							}
						}

						$out .= "<i class='fa fa-ban fa-lg red'".($icon_title != '' ? " title='".$icon_title."'" : "")."></i>";

						$arr_actions['unspam'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerApprove&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_approve_'.$intAnswerID, '_wpnonce_answer_approve')."'".make_link_confirm().">".__("Approve", 'lang_form')."</a>";
					}

					else
					{
						// Status
						########################
						switch($item[$column_name])
						{
							case '':
							case 'draft':
							case 'not_spam':
								$out .= "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerStatus&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID."&strAnswerStatus=done"), 'answer_status_'.$intAnswerID, '_wpnonce_answer_status')."'".make_link_confirm()."><i class='far fa-star' title='".__("No status so far. Click to mark as done.", 'lang_form')."'></i></a>";
							break;

							case 'spam':
							case 'spam_ip':
							case 'spam_fingerprint':
							case 'spam_email':
								$icon_title = "";

								if(strpos($url, "_") !== false)
								{
									list($rest, $icon_title) = explode("_", $item[$column_name], 2);
								}

								$out .= "<i class='fa fa-ban fa-lg red'".($icon_title != '' ? " title='".$icon_title."'" : "")."></i>";

								$arr_actions['unspam'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerApprove&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_approve_'.$intAnswerID, '_wpnonce_answer_approve')."'".make_link_confirm().">".__("Approve", 'lang_form')."</a>";
							break;

							case 'done':
								$out .= "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerStatus&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID."&strAnswerStatus=draft"), 'answer_status_'.$intAnswerID, '_wpnonce_answer_status')."'".make_link_confirm()."><i class='fas fa-star' title='".__("Done. Click to mark as NOT done.", 'lang_form')."'></i></a>";
							break;

							default:
								$out .= "<i class='fa fa-question-circle' title='".__("Unknown Status", 'lang_form')." (".$item[$column_name].")'></i>";
							break;
						}
						########################

						// Sent
						########################
						$result_emails = $wpdb->get_results($wpdb->prepare("SELECT answerEmailFrom, answerEmail, answerSent FROM ".$wpdb->prefix."form_answer_email WHERE answerID = '%d' AND answerEmail != ''", $intAnswerID));
						$sent_total = $wpdb->num_rows;

						if($sent_total > 0)
						{
							$sent_successfully = $sent_failed = 0;
							$arr_sent = [];

							foreach($result_emails as $r)
							{
								$strAnswerEmailFrom = $r->answerEmailFrom;
								$strAnswerEmail = $r->answerEmail;
								$intAnswerSent = $r->answerSent;

								$answer_md5 = md5($strAnswerEmailFrom.'-'.$strAnswerEmail);

								if($intAnswerSent == 1)
								{
									$sent_successfully++;

									if(isset($arr_sent[$answer_md5.'success']))
									{
										$arr_sent[$answer_md5.'success']['amount']++;
									}

									else
									{
										$arr_sent[$answer_md5.'success'] = array(
											'from' => $strAnswerEmailFrom,
											'to' => $strAnswerEmail,
											'type' => 'success',
											'amount' => 1,
										);
									}
								}

								else
								{
									$sent_failed++;

									if(isset($arr_sent[$answer_md5.'fail']))
									{
										$arr_sent[$answer_md5.'fail']['amount']++;
									}

									else
									{
										$arr_sent[$answer_md5.'fail'] = array(
											'from' => $strAnswerEmailFrom,
											'to' => $strAnswerEmail,
											'type' => 'fail',
											'amount' => 1,
										);
									}
								}
							}

							$out .= "<i class='fa fa-paper-plane ".($sent_failed > 0 ? "red" : "green")."' title='".($sent_failed > 0 ? $sent_successfully."/" : "").$sent_total."'></i>";

							foreach($arr_sent as $key => $arr_value)
							{
								$arr_actions[$key] = "<i class='".($arr_value['type'] == 'success' ? "fa fa-check green" : "fa fa-times red")."' title='".($arr_value['from'] != '' ? $arr_value['from']." -> " : "").$arr_value['to'].($arr_value['amount'] > 1 ? " (".$arr_value['amount'].")" : "")."'></i>";
							}

							$email_notify = get_post_meta($obj_form->id, $obj_form->meta_prefix.'email_notify', true);
							$email_confirm = get_post_meta($obj_form->id, $obj_form->meta_prefix.'email_confirm', true);

							if($email_notify == 'yes' || $email_confirm == 'yes')
							{
								$arr_actions['resend'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnMessageResend&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'message_resend_'.$intAnswerID, '_wpnonce_message_resend')."'".make_link_confirm()."><i class='fa fa-recycle' title='".__("Do you want to send the message again?", 'lang_form')."'></i></a>";
							}
						}

						// This will remove all answers that have the same field inputs, so it needs to be smarter before use in prod
						if(count($this->current_row) > 0 && $this->current_row == $this->previous_row)
						{
							$out .= "<i class='far fa-copy' title='".__("This is a duplicate", 'lang_form')."'></i>";

							if(IS_SUPER_ADMIN)
							{
								//$obj_form->delete_answer($intAnswerID);
							}
						}

						$this->previous_row = $this->current_row;
						########################
					}

					$out .= $this->row_actions($arr_actions);
				break;

				case 'answerCreated':
					$obj_form->answer_column = 0;

					$arr_actions = [];

					$strSentTo = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '0'", $intAnswerID));

					if($strSentTo != '')
					{
						$strSentTo = trim(trim($strSentTo), ', ');
						$strSentTo = str_replace(", ", "<br>", $strSentTo);
					}

					if($strSentTo != '' && strlen($strSentTo) > 4)
					{
						$arr_actions['sent_to'] = "<br><strong>".__("Sent To", 'lang_form')."</strong><br>".$strSentTo;
					}

					$out .= format_date($item['answerCreated'])
					.$this->row_actions($arr_actions);
				break;

				default:
					if(isset($item[$column_name]))
					{
						$out .= $item[$column_name];
					}

					else
					{
						$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeText, formTypeID, checkID, formTypeEncrypt FROM ".$wpdb->prefix."form2type WHERE formID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $obj_form->id, $column_name));

						foreach($resultText as $r)
						{
							$intForm2TypeID = $r->form2TypeID;
							$strFormTypeCode = $obj_form->arr_form_types[$r->formTypeID]['code'];
							$obj_form->label = $r->formTypeText;
							$strCheckCode = ($r->checkID > 0 ? $obj_form->arr_form_check[$r->checkID]['code'] : '');
							$strFormTypeEncrypt = $r->formTypeEncrypt;

							$strAnswerText = "";

							if(!in_array($strFormTypeCode, $obj_form->ignore_tags_on_output))
							{
								$arr_actions = [];

								$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText, answerUpdated FROM ".$wpdb->prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d' ORDER BY answerUpdated DESC", $intForm2TypeID, $intAnswerID));
								$rowsAnswer = $wpdb->num_rows;

								if($rowsAnswer > 0)
								{
									if($strFormTypeEncrypt == 'yes')
									{
										$obj_encryption = new mf_encryption("mf_form");
									}

									$strAnswerText = "";
									$i = 0;

									foreach($resultAnswer as $r2)
									{
										$strAnswerText_temp = $r2->answerText;

										if($strFormTypeEncrypt == 'yes')
										{
											$strAnswerText_temp = $obj_encryption->decrypt($strAnswerText_temp, md5(AUTH_KEY));
										}

										switch($strFormTypeCode)
										{
											case 'radio_button':
												//$strAnswerText_temp = 1;
												$strAnswerText_temp = "<i class='fa fa-check green'></i>";
											break;

											case 'select':
											case 'radio_multiple':
												$strAnswerText_temp = $obj_form->parse_select_info($strAnswerText_temp);
											break;

											case 'select_multiple':
											case 'checkbox_multiple':
												$obj_form->prefix = $obj_form->get_post_info(array('select' => 'post_name'))."_";

												$strAnswerText_temp = $obj_form->parse_multiple_info($strAnswerText_temp, true);
											break;

											case 'file':
												$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText_temp));

												foreach($result as $r)
												{
													$strAnswerText_temp = "<a href='".$r->guid."'>".$r->post_title."</a>";
												}
											break;

											default:
												switch($strCheckCode)
												{
													case 'url':
														$strAnswerText_temp = "<a href='".$strAnswerText_temp."'>".$strAnswerText_temp."</a>";
													break;

													case 'email':
														$strAnswerText_temp = "<a href='mailto:".$strAnswerText_temp."?subject=".__("Re", 'lang_form').": ".$obj_form->get_form_name()."'>".$strAnswerText_temp."</a>";

														if($item['answerSpam'] == false)
														{
															$arr_actions['spam'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerSpam&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_spam_'.$intAnswerID, '_wpnonce_answer_spam')."'".make_link_confirm().">".__("Mark as Spam", 'lang_form')."</a>";
														}
													break;
												}
											break;
										}

										$strAnswerText_temp = stripslashes(stripslashes($strAnswerText_temp));

										/*if(substr($strAnswerText, 0, 2) == "--")
										{
											$strAnswerText_temp = "<span class='grey nowrap'>".$strAnswerText_temp."</span>";
										}*/

										/*switch($strFormTypeCode)
										{
											case 'checkbox':
											case 'radio_button':
											//case 'checkbox_multiple':
											//case 'radio_multiple':
												if($strAnswerText == 1)
												{
													$strAnswerText_temp = "<i class='fa fa-check green'></i>";
												}

												else if($strAnswerText == 0)
												{
													$strAnswerText_temp = "<i class='fa fa-times red'></i>";
												}
											break;
										}*/

										if($i > 0)
										{
											$strAnswerText .= "<br>
											<span class='grey'>";
										}

											if(strpos($strAnswerText_temp, "<") !== false)
											{
												$strAnswerText .= $strAnswerText_temp;
											}

											else
											{
												$strAnswerText .= shorten_text(array('string' => $strAnswerText_temp, 'limit' => 20, 'add_title' => true));
											}

											if($rowsAnswer > 1)
											{
												if($r2->answerUpdated > DEFAULT_DATE)
												{
													$strAnswerText .= " <span class='grey'>(".format_date($r2->answerUpdated).")</span>";
												}

												else
												{
													$strAnswerText .= " <span class='grey'>(".format_date($item['answerCreated']).")</span>";
												}
											}

										if($i > 0)
										{
											$strAnswerText .= "</span>";
										}

										$i++;
									}
								}

								if($strAnswerText != '')
								{
									$out .= $strAnswerText;

									if($obj_form->answer_column == 0)
									{
										$obj_encryption = new mf_encryption("mf_form");
										$answer_id_encrypted = $obj_encryption->encrypt($intAnswerID, md5(AUTH_KEY));

										$arr_actions['edit'] = "<a href='".admin_url("admin.php?page=mf_form/view/index.php&intFormID=".$obj_form->id."&answer_id_encrypted=".$answer_id_encrypted)."'>".__("Edit", 'lang_form')."</a>";
										$arr_actions['delete'] = "<a href='#delete/answer/".$intAnswerID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";

										$obj_form->answer_column++;
									}

									$out .= $this->row_actions($arr_actions);
								}
							}

							if(in_array($strFormTypeCode, array('input_field', 'textarea')))
							{
								$this->current_row[$intForm2TypeID] = $strAnswerText;
							}
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
	var $id;
	var $answer_id;
	var $row = [];
	var $query_prefix = '';
	var $output = "";
	var $answer_text = "";
	var $show_required = false;
	var $show_autofocus = false;
	var $show_encrypt = false;
	var $show_remember = false;
	var $show_copy = false;
	var $show_template_info = false;
	var $edit_mode = false;

	function __construct($data)
	{
		$this->id = (isset($data['id']) ? $data['id'] : 0);
		$this->answer_id = (isset($data['answer_id']) ? $data['answer_id'] : 0);

		$this->row = $data['result'];
		$this->query_prefix = $data['query_prefix'];
		$this->edit_mode = $data['edit_mode'];
	}

	function calculate_value()
	{
		global $wpdb, $obj_form;

		if($this->answer_id > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d' ORDER BY answerUpdated DESC LIMIT 0, 1", $this->row->form2TypeID, $this->answer_id));

			foreach($result as $r)
			{
				switch($obj_form->arr_form_types[$this->row->formTypeID]['code'])
				{
					case 'radio_button':
						$this->answer_text = $this->row->form2TypeID;
					break;

					case 'datepicker':
						$this->answer_text = $r->answerText;
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
			if(isset($_GET[$this->row->formTypeFetchFrom]) && $_GET[$this->row->formTypeFetchFrom] != '')
			{
				$field_data['value'] = check_var($this->row->formTypeFetchFrom);
			}

			else
			{
				$field_data['value'] = $this->row->formTypeFetchFrom;
			}

			if(strpos($this->row->formTypeFetchFrom, "[") !== false)
			{
				$user_display_name = $user_email = $user_address = "";

				$user_id = get_current_user_id();

				if($user_id > 0)
				{
					$user_data = get_userdata($user_id);

					$user_display_name = $user_data->display_name;
					$user_email = $user_data->user_email;

					$profile_address_street = get_the_author_meta('profile_address_street', $user_data->ID);
					$profile_address_zipcode = get_the_author_meta('profile_address_zipcode', $user_data->ID);
					$profile_address_city = get_the_author_meta('profile_address_city', $user_data->ID);
					$user_address = $profile_address_street.", ".$profile_address_zipcode." ".$profile_address_city;
				}

				$arr_exclude = $arr_include = [];
				$arr_exclude[] = "[user_display_name]";		$arr_include[] = $user_display_name;
				$arr_exclude[] = "[user_email]";			$arr_include[] = $user_email;
				$arr_exclude[] = "[user_address]";			$arr_include[] = $user_address;

				$arr_request = get_match_all('/\[get\=(.*?)\]/is', $this->row->formTypeFetchFrom, false);

				if(isset($arr_request[0]) && count($arr_request[0]) > 0)
				{
					foreach($arr_request[0] as $str_request)
					{
						$value_temp = check_var($str_request);

						if($value_temp != '')
						{
							$arr_exclude[] = "[get=".$str_request."]";		$arr_include[] = $value_temp;
						}
					}
				}

				$field_data['value'] = str_replace($arr_exclude, $arr_include, $field_data['value']);
			}
		}
	}

	function check_limit($data)
	{
		global $wpdb, $obj_form;

		if(isset($data['array'][2]) && $data['array'][2] > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form2answer INNER JOIN ".$wpdb->prefix."form_answer USING (answerID) WHERE formID = '%d' AND form2TypeID = '%d' AND answerText = %s AND answerSpam = '0' GROUP BY answerID", $this->id, $data['form2TypeID'], $data['array'][0]));
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

		@list($this->label, $str_select) = explode(":", $string);

		$form2type_id_temp = $obj_form->get_type_connect_to_root(array('connect_to' => $this->row->formTypeConnectTo, 'field_id' => $this->row->form2TypeID));

		$result = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionKey, formOptionValue, formOptionLimit, formOptionAction FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $form2type_id_temp));

		$arr_data = [];

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

			$arr_data[($r->formOptionKey > 0 ? $arr_option[0] : '')] = $arr_option[1];
		}

		return $arr_data;
	}

	function get_form_fields()
	{
		global $intFormTypeID2_temp, $intForm2TypeID2_temp, $obj_form;

		$plugin_include_url = plugin_dir_url(__FILE__);

		$field_data = array(
			'name' => $this->query_prefix.$this->row->form2TypeID,
		);

		if($this->row->formTypeClass != '' && strpos($this->row->formTypeClass, "flex_flow") !== false)
		{
			apply_filters('get_flex_flow', "");
		}

		$class_output = ($this->row->formTypeClass != '' ? " class='".$this->row->formTypeClass."'" : "");
		$class_output_small = ($this->row->formTypeClass != '' ? " ".$this->row->formTypeClass : "");

		if(!($this->row->formTypeID > 0))
		{
			$this->row->formTypeID = 3;
		}

		switch($obj_form->arr_form_types[$this->row->formTypeID]['code'])
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
					mf_enqueue_script('script_form_display', $plugin_include_url."script_display.js");

					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_display";
					$field_data['xtra'] = "data-equals='".$this->row->formTypeActionEquals."' data-display='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				else if(isset($this->row->has_action) && $this->row->has_action)
				{
					mf_enqueue_script('script_form_connect_to', $plugin_include_url."script_action.js");

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
				mf_enqueue_script('script_form_range', $plugin_include_url."script_range.js");

				$arr_content = explode("|", $this->row->formTypeText);

				if($this->answer_text == '' && isset($arr_content[3]))
				{
					$this->answer_text = $arr_content[3];
				}

				$field_data['text'] = $arr_content[0]." (<span>".$this->answer_text."</span>)";
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = "min='".$arr_content[1]."' max='".$arr_content[2]."'".($this->row->formTypeAutofocus ? " autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass;
				$field_data['type'] = "range";

				if($this->row->formTypeRemember)
				{
					mf_enqueue_script('script_form_remember', $plugin_include_url."script_remember.js", array(
						'plugins_url' => plugins_url(),
					));

					$field_data['xtra_class'] .= " remember";
				}

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_encrypt = $this->show_remember = $this->show_copy = true;
			break;

			case 'datepicker':
				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass;
				$field_data['type'] = "date";
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				if($this->row->formTypeRemember)
				{
					mf_enqueue_script('script_form_remember', $plugin_include_url."script_remember.js", array(
						'plugins_url' => plugins_url(),
					));

					$field_data['xtra_class'] .= " remember";
				}

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_encrypt = $this->show_remember = $this->show_copy = $this->show_template_info = true;
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
					mf_enqueue_script('script_form_display', $plugin_include_url."script_display.js");

					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_display";
					$field_data['xtra'] = "data-equals='".$this->row->formTypeActionEquals."' data-display='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				else if(isset($this->row->has_action) && $this->row->has_action)
				{
					mf_enqueue_script('script_form_action', $plugin_include_url."script_action.js");

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
				$field_data['class'] = $this->row->formTypeClass;
				$field_data['xtra'] = "";

				if($this->row->formTypeRemember)
				{
					mf_enqueue_script('script_form_remember', $plugin_include_url."script_remember.js", array(
						'plugins_url' => plugins_url(),
					));

					$field_data['class'] .= " remember";
				}

				if($this->row->formTypeConnectTo > 0)
				{
					mf_enqueue_script('script_form_connect_to', $plugin_include_url."script_connect_to.js");

					$field_data['class'] .= " form_connect_to";
					$field_data['xtra'] .= ($field_data['xtra'] != '' ? " " : "")."data-connect_to='".$this->query_prefix.$this->row->formTypeConnectTo."'";
				}

				if($this->row->formTypeActionShow > 0)
				{
					mf_enqueue_script('script_form_display', $plugin_include_url."script_display.js");

					$field_data['class'] .= ($field_data['class'] != '' ? " " : "")."form_display";
					$field_data['xtra'] = ($field_data['xtra'] != '' ? " " : "")."data-equals='".$this->row->formTypeActionEquals."' data-display='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				else if(isset($this->row->has_action) && $this->row->has_action)
				{
					mf_enqueue_script('script_form_action', $plugin_include_url."script_action.js");

					$field_data['class'] .= ($field_data['class'] != '' ? " " : "")."form_action";
				}

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_encrypt = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			case 'select_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);
				$field_data['text'] = $this->label;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;
				$field_data['xtra'] = "class='mf_form_field multiselect'";

				do_action('init_multiselect');

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
				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->answer_text;
				$field_data['maxlength'] = 200;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass;
				$field_data['type'] = ($this->row->checkID > 0 ? $obj_form->arr_form_check[$this->row->checkID]['code'] : 'char');
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				$arr_form_input_type = array('address', 'city', 'country', 'email', 'name', 'telno', 'zip');

				if(in_array($field_data['type'], $arr_form_input_type))
				{
					$field_data['xtra'] .= " data-fetch_info='".$field_data['type']."'";

					mf_enqueue_script('script_form_fetch_info', $plugin_include_url."script_fetch_info.js", array(
						'ajax_url' => admin_url('admin-ajax.php'),
						'arr_form_input_type' => $arr_form_input_type,
					));
				}

				if($this->row->formTypeRemember)
				{
					mf_enqueue_script('script_form_remember', $plugin_include_url."script_remember.js", array(
						'plugins_url' => plugins_url(),
					));

					$field_data['xtra_class'] .= " remember";
				}

				if($this->row->formTypeLength > 0)
				{
					$field_data['maxlength'] = $this->row->formTypeLength;
				}

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_encrypt = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			case 'textarea':
				$field_data['text'] = $this->row->formTypeText;
				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->formTypeClass;
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				if($this->row->formTypeRemember)
				{
					mf_enqueue_script('script_form_remember', $plugin_include_url."script_remember.js", array(
						'plugins_url' => plugins_url(),
					));

					$field_data['class'] .= " remember";
				}

				if($this->row->formTypeLength > 0)
				{
					$field_data['xtra'] = " maxlength='".$this->row->formTypeLength."'";
				}

				$this->filter_form_fields($field_data);
				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = $this->show_encrypt = $this->show_remember = $this->show_copy = $this->show_template_info = true;
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
				$this->output .= ($this->edit_mode == true ? "<p class='grey".$class_output_small."'>(".__("Space", 'lang_form').")</p>" : "<p".$class_output.">&nbsp;</p>");
			break;

			case 'referer_url':
				$referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

				if($this->edit_mode == true)
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

				if($this->edit_mode == true)
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
				if($this->edit_mode == true)
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
				if($this->edit_mode == true)
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
				do_log(__FUNCTION__." - No code for this formTypeID (".$this->row->formTypeID." - ".var_export($obj_form->arr_form_types, true).")");
			break;
		}

		$intFormTypeID2_temp = $this->row->formTypeID;
	}

	function get_output($data = [])
	{
		global $wpdb, $obj_form;

		$out = "";

		if($this->edit_mode == true)
		{
			do_action('load_font_awesome');

			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->prefix."form_answer WHERE form2TypeID = '%d' LIMIT 0, 1", $this->row->form2TypeID));
			$row_answers = $wpdb->num_rows;

			$row_settings = show_checkbox(array('name' => 'display_'.$this->row->form2TypeID, 'text' => __("Display", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeDisplay, 'xtra' => "class='ajax_checkbox' rel='display/type/".$this->row->form2TypeID."'"));

			if($this->show_required == true)
			{
				$row_settings .= show_checkbox(array('name' => 'require_'.$this->row->form2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRequired, 'xtra' => "class='ajax_checkbox' rel='require/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_autofocus == true)
			{
				$row_settings .= show_checkbox(array('name' => 'autofocus_'.$this->row->form2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeAutofocus, 'xtra' => "class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_encrypt == true && $row_answers == 0)
			{
				$row_settings .= show_checkbox(array('name' => 'encrypt_'.$this->row->form2TypeID, 'text' => __("Encrypt Answer", 'lang_form'), 'value' => 'yes', 'compare' => $this->row->formTypeEncrypt, 'xtra' => "class='ajax_checkbox encrypt' rel='encrypt/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_remember == true)
			{
				$row_settings .= show_checkbox(array('name' => 'remember_'.$this->row->form2TypeID, 'text' => __("Remember Answer", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRemember, 'xtra' => "class='ajax_checkbox remember' rel='remember/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_copy == true)
			{
				$row_settings .= "<a href='".admin_url("admin.php?page=mf_form/create/index.php&btnFieldCopy&intFormID=".$this->id."&intForm2TypeID=".$this->row->form2TypeID)."'>".__("Copy", 'lang_form')."</a>";
			}

			if($row_answers == 0)
			{
				$row_settings .= ($this->show_copy == true ? " | " : "")."<a href='#delete/type/".$this->row->form2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
			}

			if($this->show_template_info == true)
			{
				$row_settings .= "<p class='add2condition' rel='".$this->row->form2TypeID."'>".sprintf(__("For use in templates this field has got %s and %s", 'lang_form'), "<a href='#'>[label_".$this->row->form2TypeID."]</a>", "<a href='#'>[answer_".$this->row->form2TypeID."]</a>")."</p>";
			}

			$row_class = "form_row";

			if($data['form2type_id'] == $this->row->form2TypeID)
			{
				$row_class .= ($row_class != '' ? " " : "")."active";
			}

			$out .= "<div id='type_".$this->row->form2TypeID."' class='".$row_class."'>"
				.$this->output
				."<i class='fa fa-eye-slash field_hidden_by_rule' title='".__("The field is hidden by a rule in the form", 'lang_form')."'></i>"
				."<div class='row_icons'>";

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

				if($this->row->formTypeDisplay == 0)
				{
					$out .= "<i class='fa fa-eye-slash field_hidden_by_creator' title='".__("The field is hidden by the creator of the form", 'lang_form')."'></i>";
				}

				if($this->row->formTypeConnectTo > 0)
				{
					$out .= "<i class='fas fa-link field_connected_to'></i>";
				}

			$out .= "</div>";
		}

		else if($this->row->formTypeDisplay == 1)
		{
			$out .= $this->output;
		}

		return $out;
	}
}