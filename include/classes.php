<?php

class mf_form
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : check_var('intFormID');

		$this->post_status = "";
		$this->form2type_id = $this->post_id = 0;

		$this->meta_prefix = "mf_form_";

		$this->answer_ip = $_SERVER['REMOTE_ADDR'];

		$this->edit_mode = $this->is_spam = $this->is_spam_id = $this->is_sent = false;

		if($this->id > 0)
		{
			$this->get_post_id();
		}
	}

	/*function post_updated($post_id, $post_after, $post_before)
	{
		$arr_include = array('mf_form');

		if(isset($post_after) && in_array($post_after->post_type, $arr_include) && ($post_after->post_status == 'publish' || $post_before->post_status == 'publish') && class_exists('mf_cache')) // && $post_after != $post_before //post_modified is different so point in checking for changes this way
		{
			$shortcode = "[mf_form id=".$post_id."]";

			$obj_cache = new mf_cache();
			$obj_cache->shortcode_updated($shortcode);
		}
	}*/

	/*function get_user_reminders($array)
	{
		$user_id = $array['user_id'];
		$reminder_cutoff = $array['cutoff'];

		do_log("obj_form->get_user_reminder was run for ".$user_id." (".$reminder_cutoff.")");

		$update_form = get_count_answer_message(array('form_id' => $obj_form->id));

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

	function fetch_request()
	{
		$this->answer_id = check_var('intAnswerID');
	}

	function parse_range_label()
	{
		list($this->label, $rest) = explode("|", $this->label);
	}

	function parse_select_info($strAnswerText)
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

		return $strAnswerText;
	}

	function parse_multiple_info($strAnswerText)
	{
		$arr_answer_text = explode(",", str_replace($this->prefix, "", $strAnswerText));
		$strAnswerText = "";

		list($this->label, $str_select) = explode(":", $this->label);
		$arr_options = explode(",", $str_select);

		foreach($arr_options as $str_option)
		{
			$arr_option = explode("|", $str_option);

			if(in_array($arr_option[0], $arr_answer_text))
			{
				$strAnswerText .= ($strAnswerText != '' ? ", " : "").$arr_option[1];
			}
		}

		if($strAnswerText == '')
		{
			$strAnswerText = implode(",", $arr_answer_text);
		}

		return $strAnswerText;
	}

	function get_form_types_for_select()
	{
		global $wpdb, $intFormTypeID;

		$arr_data = array();

		$result = $wpdb->get_results("SELECT formTypeID, formTypeCode, formTypeName, COUNT(formTypeID) AS formType_amount FROM ".$wpdb->base_prefix."form_type LEFT JOIN ".$wpdb->base_prefix."form2type USING (formTypeID) WHERE formTypePublic = 'yes' GROUP BY formTypeID ORDER BY formType_amount DESC, formTypeName ASC");

		if($wpdb->num_rows > 0)
		{
			$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

			foreach($result as $r)
			{
				if($intFormTypeID > 0 || $r->formTypeCode != 'custom_tag') // || $r->formTypeID != 13
				{
					$arr_data[$r->formTypeID] = $r->formTypeName;
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
			$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

			foreach($result as $r)
			{
				$arr_data[$r->checkID] = __($r->checkName, 'lang_form');
			}
		}

		return $arr_data;
	}

	function get_tags_for_select()
	{
		return array(
			'' => "-- ".__("Choose here", 'lang_form')." --",
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
		return array(
			'' => "-- ".__("Choose here", 'lang_form')." --",
			4 => __("Billmate", 'lang_form'),
			1 => __("DIBS", 'lang_form'),
			3 => __("Paypal", 'lang_form'),
			2 => __("Skrill", 'lang_form'),
		);
	}

	function get_payment_currency_for_select($intFormPaymentProvider)
	{
		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

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
				$arr_data["DKK"] = __("Danish Krone", 'lang_form')." (DKK)";
				$arr_data["EUR"] = __("Euro", 'lang_form')." (EUR)";
				$arr_data["USD"] = __("US Dollar", 'lang_form')." (USD)";
				$arr_data["GBP"] = __("English Pound", 'lang_form')." (GBP)";
				$arr_data["SEK"] = __("Swedish Krona", 'lang_form')." (SEK)";
				$arr_data["AUD"] = __("Australian Dollar", 'lang_form')." (AUD)";
				$arr_data["CAD"] = __("Canadian Dollar", 'lang_form')." (CAD)";
				$arr_data["ISK"] = __("Icelandic Krona", 'lang_form')." (ISK)";
				$arr_data["JPY"] = __("Japanese Yen", 'lang_form')." (JPY)";
				$arr_data["NZD"] = __("New Zealand Dollar", 'lang_form')." (NZD)";
				$arr_data["NOK"] = __("Norwegian Krone", 'lang_form')." (NOK)";
				$arr_data["CHF"] = __("Swiss Franc", 'lang_form')." (CHF)";
				$arr_data["TRY"] = __("Turkish Lira", 'lang_form')." (TRY)";
			break;
		}

		$arr_data = array_sort(array('array' => $arr_data, 'on' => 1, 'keep_index' => true));

		return $arr_data;
	}

	function get_payment_amount_for_select()
	{
		list($result, $rows) = $this->get_form_type_info(array('query_type_id' => array(10, 12))); //'select', 'hidden_field'
		return $this->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		if(isset($_GET['btnFormCopy']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_copy_'.$this->id))
		{
			$inserted = true;

			$result_temp = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$copy_fields = ", blogID, formAnswerURL, formEmail, formEmailNotify, formEmailNotifyPage, formEmailName, formEmailConfirm, formEmailConfirmPage, formShowAnswers, formMandatoryText, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formPaymentMerchant, formPaymentCurrency, formPaymentCheck, formPaymentAmount";

				$strFormName = $this->get_form_name($this->id);

				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strFormName,
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form (formName, postID".$copy_fields.", formCreated, userID) (SELECT CONCAT(formName, ' (".__("copy", 'lang_form').")'), '%d'".$copy_fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0')", $intPostID, $this->id));
				$intFormID_new = $wpdb->insert_id;

				if($intFormID_new > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' ORDER BY form2TypeID DESC", $this->id));

					foreach($result as $r)
					{
						$intForm2TypeID = $r->form2TypeID;

						$copy_fields = "formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow, formTypeRequired, formTypeAutofocus, formTypeRemember, form2TypeOrder";

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type (formID, ".$copy_fields.", form2TypeCreated, userID) (SELECT %d, ".$copy_fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d')", $intFormID_new, $intForm2TypeID));

						if(!($wpdb->insert_id > 0))
						{
							$inserted = false;
						}
					}
				}

				else
				{
					$inserted = false;
				}
			}

			if($inserted == false)
			{
				$error_text = __("Something went wrong. Contact your admin and add this URL as reference", 'lang_form');
			}

			else
			{
				$done_text = __("Wow! The form was copied successfully!", 'lang_form');
			}
		}

		else if(isset($_POST['btnFormUpdate']))
		{
			$strFormPrefix = $this->get_post_info()."_";

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, checkCode FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeID != '13' ORDER BY form2TypeOrder ASC", $this->id));

			foreach($result as $r)
			{
				$intForm2TypeID2 = $r->form2TypeID;
				$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";

				$var = check_var($strFormPrefix.$intForm2TypeID2, $strCheckCode, true, '', true, 'post');

				if($var != '')
				{
					$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $intForm2TypeID2));
					$rowsCheck = $wpdb->num_rows;

					if($rowsCheck > 0)
					{
						$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $this->answer_id, $intForm2TypeID2, $var));
						$rowsCheck = $wpdb->num_rows;

						if($rowsCheck == 0)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '%d'", $var, $this->answer_id, $intForm2TypeID2));
						}
					}

					else
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = %s", $this->answer_id, $intForm2TypeID2, $var));
					}
				}
			}

			if(!isset($error_text) || $error_text == '')
			{
				mf_redirect("?page=mf_form/answer/index.php&intFormID=".$this->id);
			}
		}

		else if(isset($_GET['btnAnswerSpam']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'answer_spam_'.$this->answer_id))
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2answer SET answerSpam = '1' WHERE answerID = '%d'", $this->answer_id));

			$done_text = __("I have marked the email as spam for you", 'lang_form');
		}

		else if(isset($_GET['btnAnswerApprove']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'answer_approve_'.$this->answer_id))
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2answer SET answerSpam = '0' WHERE answerID = '%d'", $this->answer_id));

			$done_text = __("I have approved the answer for you", 'lang_form');
		}

		else if(isset($_GET['btnMessageResend']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'message_resend_'.$this->answer_id))
		{
			$resultAnswerEmail = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerType FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerSent = '0' AND answerType != ''", $this->answer_id));

			if($wpdb->num_rows > 0)
			{
				$this->email_visitor = "";

				$strFormName = $this->get_post_info(array('select' => "post_title"));
				$this->prefix = $this->get_post_info()."_";

				$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmPage, formEmail, formEmailNotify, formEmailNotifyPage, formEmailName, formMandatoryText, formPaymentProvider, formPaymentAmount FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
				$r = $result[0];
				$this->email_confirm = $r->formEmailConfirm;
				$this->email_confirm_page = $r->formEmailConfirmPage;
				$this->email_admin = $r->formEmail;
				$this->email_notify = $r->formEmailNotify;
				$this->email_notify_page = $r->formEmailNotifyPage;
				$this->email_subject = ($r->formEmailName != "" ? $r->formEmailName : $strFormName);

				$this->arr_email_content = array(
					'fields' => array(),
				);

				$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeText, checkCode, answerText FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) WHERE answerID = '%d' = '1' ORDER BY form2TypeOrder ASC", $this->answer_id)); // AND formTypeResult

				foreach($result as $r)
				{
					$intForm2TypeID2 = $r->form2TypeID;
					$intFormTypeID2 = $r->formTypeID;
					$this->label = $r->formTypeText;
					$strCheckCode = $r->checkCode;
					$strAnswerText = $r->answerText;

					$this->arr_email_content['fields'][$intForm2TypeID2] = array();

					switch($intFormTypeID2)
					{
						case 1:
						//case 'checkbox':
							$strAnswerText = "x";
						break;

						case 2:
						//case 'range':
							$this->parse_range_label();
						break;

						case 7:
						//case 'datepicker':
							$strAnswerText = format_date($strAnswerText);
						break;

						case 8:
						//case 'radio_button':
							$strAnswerText = "x";
						break;

						case 10:
						//case 'select':
							$strAnswerText = $this->parse_select_info($strAnswerText);
						break;

						case 11:
						//case 'select_multiple':
						case 16:
						//case 'checkbox_multiple':
							$strAnswerText = $this->parse_multiple_info($strAnswerText);
						break;

						default:
							if($strCheckCode != '')
							{
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
							}
						break;
					}

					if($this->label != '')
					{
						$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;
					}

					if($strAnswerText != '')
					{
						$this->arr_email_content['fields'][$intForm2TypeID2]['value'] = $strAnswerText;
					}

					switch($strCheckCode)
					{
						case 'email':
							if($intFormTypeID2 == 3)
							//if($strFormTypeCode == 'input_field')
							{
								$this->email_visitor = $strAnswerText;
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
						/*case 'link_yes':

						break;

						case 'link_no':

						break;*/

						case 'replace_link':
							$this->send_to = $strAnswerEmail;
						break;

						/*case 'notify':

						break;

						case 'confirm':

						break;*/

						case 'product':
							$email_content_temp = apply_filters('filter_form_on_submit', array('answer_id' => $this->answer_id, 'mail_from' => $this->email_visitor, 'mail_admin' => $this->email_admin, 'mail_subject' => $this->email_subject, 'notify_page' => $this->email_notify_page, 'arr_mail_content' => $this->arr_email_content));

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

		$obj_export = new mf_form_export();

		return $out;
	}

	function count_forms($data = array())
	{
		global $wpdb;

		return $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->posts." WHERE post_type = 'mf_form'");

		/*$tbl_group = new mf_form_table();

		$tbl_group->select_data(array(
			//'select' => "*",
			//'where' => ,
			'debug' => true,
		));

		return count($tbl_group->data);*/
	}

	function is_poll()
	{
		global $wpdb;

		$not_poll_content_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(form2TypeID) FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' AND formTypeID != '5' AND formTypeID != '8' LIMIT 0, 1", $this->id));

		return ($not_poll_content_amount == 0 ? true : false);
	}

	function check_if_duplicate()
	{
		global $wpdb;

		$dup_ip = false;

		if($this->is_poll())
		{
			$rowsIP = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d' AND answerIP = %s LIMIT 0, 1", $this->id, $this->answer_ip));

			if($rowsIP > 0)
			{
				$dup_ip = true;
			}
		}

		return $dup_ip;
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

	function has_template()
	{
		global $wpdb;

		$query_where = "";

		if($this->id > 0)
		{
			$query_where .= " AND formID = '".$this->id."'";
		}

		$intFormID = $wpdb->get_var("SELECT formID FROM ".$wpdb->base_prefix."form WHERE (formEmailNotifyPage > 0 OR formEmailConfirmPage > 0)".$query_where);

		return ($intFormID > 0 ? true : false);
	}

	function check_if_has_payment()
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formPaymentProvider, formPaymentAmount FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id)); //formShowAnswers,

		foreach($result as $r)
		{
			//$intFormShowAnswers = $r->formShowAnswers;
			$intFormPaymentProvider = $r->formPaymentProvider;
			$intFormPaymentAmount = $r->formPaymentAmount;

			$this->has_payment = $intFormPaymentProvider > 0 && $intFormPaymentAmount > 0;
		}
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

	function get_form_array($data = array())
	{
		global $wpdb;

		if(!isset($data['local_only'])){		$data['local_only'] = false;}
		if(!isset($data['force_has_page'])){	$data['force_has_page'] = true;}

		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

		$result = $wpdb->get_results("SELECT formID, formName FROM ".$wpdb->base_prefix."form WHERE formDeleted = '0'".(IS_ADMIN && $data['local_only'] == false ? "" : " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)")." ORDER BY formCreated DESC");

		foreach($result as $r)
		{
			$intFormID = $r->formID;
			$strFormName = $r->formName;

			$result2 = get_pages_from_shortcode("[mf_form id=".$intFormID."]");

			if(count($result2) > 0 || $data['force_has_page'] == false)
			{
				$arr_data[$intFormID] = $strFormName;
			}
		}

		return $arr_data;
	}

	function get_form_name($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		return $this->get_post_info(array('select' => 'post_title'));
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

	function get_form_id_from_post_content($post_id)
	{
		global $wpdb;

		$post_content = mf_get_post_content($post_id);

		$form_id = get_match("/\[mf_form id=(.*?)\]/", $post_content, false);

		if($form_id > 0)
		{
			$this->id = $form_id;
		}
	}

	function get_form_email_field()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' AND checkID = '5'", $this->id));
	}

	function get_answer_email($intAnswerID)
	{
		global $wpdb;

		$intForm2TypeID = $this->get_form_email_field();

		return $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $intAnswerID, $intForm2TypeID));
	}

	function is_form_field_type_used($data)
	{
		global $wpdb;

		$query_join = $query_where = "";

		if(isset($data['query_type_id']) && $data['query_type_id'] > 0)
		{
			$query_where .= " AND formTypeID = '".$data['query_type_id']."'";
		}

		if(isset($data['required']) && $data['required'] != '')
		{
			$query_where .= " AND formTypeRequired = '".$data['required']."'";
		}

		if(isset($data['autofocus']) && $data['autofocus'] != '')
		{
			$query_where .= " AND formTypeAutofocus = '".$data['autofocus']."'";
		}

		if(isset($data['remember']) && $data['remember'] != '')
		{
			$query_where .= " AND formTypeRemember = '".$data['remember']."'";
		}

		if(isset($data['']) && $data[''] != '')
		{
			$query_where .= " AND formTypeAutofocus = '".$data['']."'";
		}

		if(isset($data['check_code']) && $data['check_code'] != '')
		{
			$query_join .= " INNER JOIN ".$wpdb->base_prefix."form_check USING (checkID)";
			$query_where .= " AND checkCode = '".$data['check_code']."'";
		}

		$intForm2TypeID = $wpdb->get_var($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type".$query_join." WHERE formID = '%d'".$query_where, $this->id));

		return $intForm2TypeID > 0 ? true : false;
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
		if(!isset($data['query_exclude_id'])){	$data['query_exclude_id'] = 0;}

		if($data['form_id'] > 0)
		{
			$this->id = $data['form_id'];
		}

		$query_where = "";

		if(count($data['query_type_id']) > 0)
		{
			$i = 0;

			$query_where .= " AND (";

				foreach($data['query_type_id'] as $query_type_id)
				{
					$query_where .= ($i > 0 ? " OR " : "")."formTypeID = '".$query_type_id."'";

					$i++;
				}

			$query_where .= ")";
		}

		if($data['query_exclude_id'] > 0)
		{
			$query_where .= " AND form2TypeID != '".$data['query_exclude_id']."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeText, form2TypeOrder FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d'".$query_where." ORDER BY form2TypeOrder ASC", $this->id));
		$rows = $wpdb->num_rows;

		return array($result, $rows);
	}

	function render_mail_subject($data)
	{
		return str_replace("[answer_id]", $this->answer_id, $data['subject']);
	}

	function render_mail_content($data)
	{
		if(!isset($data['template'])){	$data['template'] = false;}

		$out_fields = $out_doc_types = $out_products = $intProductID = $strProductName = "";

		foreach($data['array'] as $key => $arr_types)
		{
			switch($key)
			{
				case 'fields':
					foreach($arr_types as $key => $arr_value)
					{
						$out_fields .= "- ".$arr_value['label'];

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

						$out_fields .= "<br>";
					}
				break;

				case 'doc_types':
					foreach($arr_types as $key => $arr_value)
					{
						$out_doc_types .= "- ".$arr_value['label'];

						if(substr($arr_value['label'], -1) != ":")
						{
							$out_doc_types .= ":";
						}

						$out_doc_types .= " ".$arr_value['value']."<br>";
					}
				break;

				case 'products':
					foreach($arr_types as $product)
					{
						//$out_products .= "- ".$product['label'];

						if($product['value'] != '')
						{
							$out_products .= "- ".$product['value']."<br>";

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
				."&hash=".md5(NONCE_SALT."_".$this->answer_id."_".$intProductID);

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

	function get_page_content_for_email($data)
	{
		global $wpdb;

		$mail_content = "";

		if(isset($data['page_id']) && $data['page_id'] > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_content FROM ".$wpdb->posts." WHERE ID = '%d'", $data['page_id']));

			foreach($result as $r)
			{
				$data['subject'] = $r->post_title;
				$mail_template = apply_filters('the_content', $r->post_content);

				$mail_content = $this->render_mail_content(array('mail_to' => $data['mail_to'], 'array' => $data['content'], 'template' => $mail_template));
			}
		}

		if($data['subject'] != '')
		{
			$data['subject'] = $this->render_mail_subject(array('subject' => $data['subject']));
		}

		if($mail_content == '')
		{
			$mail_content = $this->render_mail_content(array('array' => $data['content']));
		}

		return array($data['subject'], $mail_content);
	}

	function has_email_field()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(formTypeID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_check USING (checkID) WHERE formID = '%d' AND formTypeID = '3' AND checkCode = 'email'", $this->id));
	}

	function get_icons_for_select()
	{
		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

		$obj_font_icons = new mf_font_icons();
		$arr_icons = $obj_font_icons->get_array();

		foreach($arr_icons as $key => $value)
		{
			$arr_data[$key] = $value;
		}

		return $arr_data;
	}

	function get_form_type_for_select($data)
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}

		$arr_data = array();

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";
		}

		foreach($data['result'] as $r)
		{
			if(in_array($r->formTypeID, array(10, 11, 16))) //'select', 'select_multiple', 'checkbox_multiple'
			{
				list($strFormTypeText, $str_select) = explode(":", $r->formTypeText);
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

		return $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, checkCode, checkPattern, formTypeText, formTypePlaceholder, formTypeRequired, formTypeAutofocus, formTypeRemember, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE ".$query_where." GROUP BY ".$wpdb->base_prefix."form2type.form2TypeID ORDER BY form2TypeOrder ASC", $query_where_id));
	}

	function process_link_yes_no()
	{
		global $error_text;
		$out = "";

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
			$mail_to = $this->get_answer_email($intAnswerID);
			$mail_subject = $this->get_form_name();
			$mail_content = (isset($_GET['btnFormLinkYes']) ? get_option('setting_link_yes_text') : get_option('setting_link_no_text'));

			if($mail_content != '')
			{
				$mail_content = nl2br(str_replace("[product]", $mail_from_name, $mail_content));

				$mail_data = array(
					'headers' => "From: ".$mail_from_name." <".$mail_from.">\r\n",
					'to' => $mail_to,
					'subject' => $mail_subject,
					'content' => $mail_content,
					'type' => (isset($_GET['btnFormLinkYes']) ? "link_yes" : "link_no"),
				);

				$sent = $this->send_transactional_email($mail_data);

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

		return $out;
	}

	function check_if_spam($data)
	{
		global $wpdb;

		if($data['text'] != '' && $data['rule'] != '')
		{
			if(function_exists($data['rule']))
			{
				$string_decoded = htmlspecialchars_decode($data['text']);

				if($data['text'] != strip_tags($data['text']) || $string_decoded != strip_tags($string_decoded))
				{
					//do_log("Is spam (".$data['rule']."): ".var_export($data, true));

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
						//do_log("Is spam (".$data['rule']."): ".var_export($data, true));

						$this->is_spam = true;
						$this->is_spam_id = $data['id'];
					}
				}
			}
		}
	}

	function get_spam_rules($data = array())
	{
		if(!isset($data['id'])){		$data['id'] = 0;}
		if(!isset($data['exclude'])){	$data['exclude'] = '';}
		if(!isset($data['type'])){		$data['type'] = '';}

		$arr_data = array(
			1 => array('exclude' => "select_multiple",	'text' => "contains_html",					'explain' => __("Contains HTML", 'lang_form')),
			2 => array('exclude' => "referer_url",		'text' => "/(http|https|ftp|ftps)\:/i",		'explain' => __("Link including http", 'lang_form')),
			3 => array('exclude' => '',					'text' => "/([qm]){5}/",					'explain' => __("Question marks", 'lang_form')),
			4 => array('exclude' => '',					'text' => "/(bit\.ly)/",					'explain' => __("bit.ly", 'lang_form')),
			5 => array('exclude' => '',					'text' => "/([bs][url[bs]=)/",				'explain' => __("[url]", 'lang_form')),
			6 => array('exclude' => '',					'text' => "",								'explain' => __("Recurring E-mail", 'lang_form')),
			7 => array('exclude' => '',					'text' => "",								'explain' => __("Honeypot", 'lang_form')),
		);

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
		global $wpdb;

		//$result = $wpdb->get_results($wpdb->prepare("SELECT spamID, spamText FROM ".$wpdb->base_prefix."form_spam WHERE (spamInclude = '' OR spamInclude = %s) AND (spamExclude = '' OR spamExclude != %s)", $data['code'], $data['code']));

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
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE answerSpam = '1' AND answerText = %s LIMIT 0, 1", $data['text'])); // AND form2TypeID = '%d', $data['id']

			if($wpdb->num_rows > 0)
			{
				//do_log(sprintf(__("The email %s has previously been marked as spam at least once (%s)", 'lang_form'), $data['text'], $wpdb->last_query));

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

	function process_transactional_emails()
	{
		$page_content_data = array(
			'subject' => $this->email_subject,
			'content' => $this->arr_email_content,
		);

		if(isset($this->send_to) && $this->send_to != '')
		{
			$mail_data = array(
				'type' => 'replace_link',
				'to' => $this->send_to,
			);

			if($this->email_visitor != '')
			{
				$mail_data['headers'] = "From: ".$this->email_visitor." <".$this->email_visitor.">\r\n";
			}

			list($mail_data['subject'], $mail_data['content']) = $this->get_page_content_for_email($page_content_data);

			$this->send_transactional_email($mail_data);
		}

		if($this->email_notify == 1)
		{
			$mail_data = array(
				'type' => 'notify',
			);

			if($this->email_admin != '')
			{
				if(strpos($this->email_admin, "<"))
				{
					$mail_data['to'] = get_match("/\<(.*)\>/", $this->email_admin);
				}

				else
				{
					$mail_data['to'] = $this->email_admin;
				}
			}

			else
			{
				$mail_data['to'] = get_bloginfo('admin_email');
			}

			if($this->email_visitor != '')
			{
				$mail_data['headers'] = "From: ".$this->email_visitor." <".$this->email_visitor.">\r\n";
			}

			$page_content_data['mail_to'] = $mail_data['to'];
			$page_content_data['page_id'] = $this->email_notify_page;

			list($mail_data['subject'], $mail_data['content']) = $this->get_page_content_for_email($page_content_data);

			$this->send_transactional_email($mail_data);
		}

		if($this->email_confirm == 1 && isset($this->email_visitor) && $this->email_visitor != '')
		{
			$mail_data = array(
				'type' => 'confirm',
				'to' => $this->email_visitor,
			);

			if($this->email_admin != '')
			{
				if(strpos($this->email_admin, "<"))
				{
					$mail_data['headers'] = "From: ".$this->email_admin."\r\n";
				}

				else if(strpos($this->email_admin, ","))
				{
					$arr_email_admin = explode(",", $this->email_admin);

					$email_admin = trim($arr_email_admin[0]);

					$mail_data['headers'] = "From: ".$email_admin." <".$email_admin.">\r\n";
				}

				else
				{
					$mail_data['headers'] = "From: ".$this->email_admin." <".$this->email_admin.">\r\n";
				}
			}

			$page_content_data['mail_to'] = $mail_data['to'];
			$page_content_data['page_id'] = $this->email_confirm_page;

			list($mail_data['subject'], $mail_data['content']) = $this->get_page_content_for_email($page_content_data);

			$this->send_transactional_email($mail_data);
		}
	}

	function send_transactional_email($data)
	{
		global $wpdb;

		if(!isset($this->is_spam) || $this->is_spam == false)
		{
			$sent = send_email($data);
		}

		else
		{
			$sent = false;
		}

		if(isset($this->answer_id) && $this->answer_id > 0)
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerEmail = %s AND answerType = %s", $this->answer_id, $data['to'], $data['type']));

			if($wpdb->num_rows > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer_email SET answerSent = '%d' WHERE answerID = '%d' AND answerEmail = %s AND answerType = %s", $sent, $this->answer_id, $data['to'], $data['type']));
			}

			else
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_email SET answerID = '%d', answerEmail = %s, answerType = %s, answerSent = '%d'", $this->answer_id, $data['to'], $data['type'], $sent));
			}
		}

		return $sent;
	}

	function check_limit($data)
	{
		global $wpdb, $error_text;

		$arr_data = array();

		list($str_label, $str_select) = explode(":", $data['string']);
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

	function process_submit()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		$this->email_visitor = $error_text = "";
		$this->arr_answer_queries = array();
		$this->arr_email_content = array(
			'fields' => array(),
		);

		$setting_form_spam = get_option('setting_form_spam', array('email', 'filter', 'honeypot'));

		$strFormName = $this->get_post_info(array('select' => "post_title"));
		$this->prefix = $this->get_post_info()."_";

		$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmPage, formEmail, formEmailNotify, formEmailNotifyPage, formEmailName, formMandatoryText, formPaymentProvider, formPaymentAmount FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
		$r = $result[0];
		$this->email_confirm = $r->formEmailConfirm;
		$this->email_confirm_page = $r->formEmailConfirmPage;
		$this->email_admin = $r->formEmail;
		$this->email_notify = $r->formEmailNotify;
		$this->email_notify_page = $r->formEmailNotifyPage;
		$this->email_subject = ($r->formEmailName != "" ? $r->formEmailName : $strFormName);
		$strFormMandatoryText = $r->formMandatoryText;
		$intFormPaymentProvider = $r->formPaymentProvider;
		$intFormPaymentAmount = $r->formPaymentAmount;

		$dblQueryPaymentAmount_value = 0;

		if($this->dup_ip == true)
		{
			$this->is_sent = true;
		}

		else
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText, checkCode, formTypeRequired FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' ORDER BY form2TypeOrder ASC", $this->id));

			foreach($result as $r)
			{
				$intForm2TypeID2 = $r->form2TypeID;
				$intFormTypeID2 = $r->formTypeID;
				$strFormTypeCode = $r->formTypeCode;
				$this->label = $r->formTypeText;
				$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";
				$intFormTypeRequired = $r->formTypeRequired;

				if(!isset($this->arr_email_content['fields'][$intForm2TypeID2]))
				{
					$this->arr_email_content['fields'][$intForm2TypeID2] = array();
				}

				$handle2fetch = $this->prefix.$intForm2TypeID2;

				$strAnswerText = $strAnswerText_send = check_var($handle2fetch, $strCheckCode, true, '', true, 'post');

				if($strAnswerText != '')
				{
					switch($strCheckCode)
					{
						case 'char':
							if(in_array('filter', $setting_form_spam))
							{
								$this->check_spam_rules(array('code' => $strFormTypeCode, 'text' => $strAnswerText));
							}
						break;

						case 'email':
							if(in_array('email', $setting_form_spam))
							{
								$this->check_spam_email(array('text' => $strAnswerText)); //'id' => $intForm2TypeID2,

								if($intFormTypeID2 == 3)
								{
									$this->email_visitor = $strAnswerText;
								}
							}
						break;
					}
				}

				switch($strFormTypeCode)
				{
					//case 1:
					case 'checkbox':
						$strAnswerText_send = "x";
					break;

					//case 2:
					case 'range':
						$this->parse_range_label();
					break;

					//case 7:
					case 'datepicker':
						$strAnswerText_send = format_date($strAnswerText);
					break;

					//case 10:
					case 'select':
						$this->check_limit(array('string' => $this->label, 'value' => $strAnswerText, 'form2TypeID' => $intForm2TypeID2));

						$strAnswerText_send = $this->parse_select_info($strAnswerText);
					break;

					//case 11:
					case 'select_multiple':
					//case 16:
					case 'checkbox_multiple':
						$strAnswerText = "";

						if(is_array($_POST[$handle2fetch]))
						{
							foreach($_POST[$handle2fetch] as $value)
							{
								$strAnswerText_temp = check_var($this->prefix.$value, $strCheckCode, false);

								$this->check_limit(array('string' => $this->label, 'value' => $strAnswerText_temp, 'form2TypeID' => $intForm2TypeID2));

								$strAnswerText .= ($strAnswerText != '' ? "," : "").$strAnswerText_temp;
							}
						}

						$strAnswerText_send = $this->parse_multiple_info($strAnswerText);
					break;

					//case 15:
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
						if($strCheckCode != '')
						{
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
						}
					break;
				}

				if($this->label != '')
				{
					$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;
				}

				if($strAnswerText != '')
				{
					if($intFormPaymentAmount == $intForm2TypeID2)
					{
						$dblQueryPaymentAmount_value = $strAnswerText;
					}

					$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = %s", $intForm2TypeID2, $strAnswerText);

					if($strAnswerText_send != '')
					{
						$this->arr_email_content['fields'][$intForm2TypeID2]['value'] = $strAnswerText_send;
					}
				}

				//else if($intFormTypeID2 == 8)
				else if($strFormTypeCode == 'radio_button')
				{
					$strAnswerText_radio = isset($_POST["radio_".$intForm2TypeID2]) ? check_var($_POST["radio_".$intForm2TypeID2], 'int', false) : '';

					if($strAnswerText_radio != '')
					{
						$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '[answer_id]', form2TypeID = %s, answerText = ''", $strAnswerText_radio);

						$strFormTypeText_temp = $wpdb->get_var($wpdb->prepare("SELECT formTypeText FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $strAnswerText_radio));

						if(!isset($this->arr_email_content['fields'][$strAnswerText_radio]))
						{
							$this->arr_email_content['fields'][$strAnswerText_radio] = array();
						}

						$this->arr_email_content['fields'][$strAnswerText_radio]['value'] = "x";
					}
				}

				//else if($intFormTypeRequired == true && !in_array($intFormTypeID2, array(5, 6, 9)) && $error_text == '')
				else if($intFormTypeRequired == true && !in_array($strFormTypeCode, array('text', 'space', 'referer_url')) && $error_text == '')
				{
					$error_text = ($strFormMandatoryText != '' ? $strFormMandatoryText : __("Please, enter all required fields", 'lang_form'))." (".$this->label.")";
				}
			}
		}

		if($error_text == '' && $this->is_sent == false && count($this->arr_answer_queries) > 0)
		{
			if(check_var($this->prefix.'check') != '' && in_array('honeypot', $setting_form_spam))
			{
				//do_log("Is Honeypot");

				$this->is_spam = true;
				$this->is_spam_id = 7;
			}

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2answer SET formID = '%d', answerIP = %s, answerSpam = '%d', spamID = '%d', answerCreated = NOW()", $this->id, $this->answer_ip, $this->is_spam, $this->is_spam_id));
			$this->answer_id = $wpdb->insert_id;

			$email_content_temp = apply_filters('filter_form_on_submit', array('answer_id' => $this->answer_id, 'mail_from' => $this->email_visitor, 'mail_admin' => $this->email_admin, 'mail_subject' => $this->email_subject, 'notify_page' => $this->email_notify_page, 'arr_mail_content' => $this->arr_email_content));

			if($error_text == '')
			{
				if(isset($email_content_temp['arr_mail_content']) && count($email_content_temp['arr_mail_content']) > 0)
				{
					$this->arr_email_content = $email_content_temp['arr_mail_content'];
				}

				if($this->insert_answer())
				{
					$this->arr_email_content['fields'][] = array(
						'label' => __("Sent From"),
						'value' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "")
					);

					$this->process_transactional_emails();

					if($intFormPaymentProvider > 0 && $dblQueryPaymentAmount_value > 0)
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '0', answerText = %s", $this->answer_id, "101: ".__("Sent to processing")));

						$intFormPaymentTest = isset($_POST['intFormPaymentTest']) && is_user_logged_in() && IS_ADMIN ? 1 : 0;

						$obj_payment = new mf_form_payment($this->id);

						$out .= $obj_payment->process_passthru(array('amount' => $dblQueryPaymentAmount_value, 'orderid' => $this->answer_id, 'test' => $intFormPaymentTest));
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

	/*function process_submit_fallback()
	{
		do_log("Submit Fallback: ".isset($_POST['btnFormSubmit'])." || ".isset($_POST['_wpnonce'])." && ".wp_verify_nonce($_POST['_wpnonce'], 'form_submit_'.$this->id)." (".var_export($this, true).", ".var_export($_REQUEST, true).")");
	}*/

	function get_form($data = array())
	{
		global $wpdb, $wp_query;

		if(!isset($data['do_redirect'])){	$data['do_redirect'] = true;}

		$out = "";

		$obj_font_icons = new mf_font_icons();

		$result = $wpdb->get_results($wpdb->prepare("SELECT formShowAnswers, formAnswerURL, formButtonText, formButtonSymbol, formPaymentProvider FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

		foreach($result as $r)
		{
			$intFormShowAnswers = $r->formShowAnswers;
			$strFormAnswerURL = $r->formAnswerURL;
			$strFormButtonText = $r->formButtonText != '' ? $r->formButtonText : __("Submit", 'lang_form');
			$strFormButtonSymbol = $obj_font_icons->get_symbol_tag($r->formButtonSymbol);
			$intFormPaymentProvider = $r->formPaymentProvider;

			$strFormPrefix = $this->get_post_info()."_";

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

			if($this->edit_mode == false && ($this->is_sent == true || $this->dup_ip == true))
			{
				$out .= "<div class='mf_form mf_form_results'>";

					$data['total_answers'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) WHERE formID = '%d' AND formTypeID = '8'", $this->id));

					if($intFormShowAnswers == 1 && $data['total_answers'] > 0)
					{
						$out .= get_poll_results($data);
					}

					else if($intFormAnswerURL > 0)
					{
						//Switch to temp site
						####################
						$wpdbobj = clone $wpdb;
						$wpdb->blogid = $blog_id;
						$wpdb->set_prefix($wpdb->base_prefix);
						####################

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
				$result = $this->get_form_type_result();
				$intTotalRows = $wpdb->num_rows;

				if($intTotalRows > 0)
				{
					$out .= "<form method='post' action='' id='form_".$this->id."' class='mf_form mf_form_submit".($this->edit_mode == true ? " mf_sortable" : "")."' enctype='multipart/form-data'>";

						if($this->edit_mode == false)
						{
							$out .= get_notification();
						}

						$i = 1;

						$intFormTypeID2_temp = $intForm2TypeID2_temp = "";

						foreach($result as $r)
						{
							$r->formTypeText = stripslashes($r->formTypeText);

							$obj_form_output = new mf_form_output(array('id' => $this->id, 'result' => $r, 'in_edit_mode' => $this->edit_mode, 'query_prefix' => $strFormPrefix));

							$obj_form_output->calculate_value($this->answer_id);
							$obj_form_output->get_form_fields();

							$out .= $obj_form_output->get_output($data);

							$i++;
						}

						if($this->answer_id > 0)
						{
							$out .= show_button(array('name' => "btnFormUpdate", 'text' => __("Update", 'lang_form')))
							.input_hidden(array('name' => 'intFormID', 'value' => $this->id))
							.input_hidden(array('name' => 'intAnswerID', 'value' => $this->answer_id));
						}

						else if($this->edit_mode == false)
						{
							$out .= show_textfield(array('name' => $strFormPrefix.'check', 'text' => __("This field should not visible", 'lang_form'), 'xtra_class' => "form_check", 'xtra' => " autocomplete='off'"));

							$out .= apply_filters('filter_form_after_fields', '')
							."<div class='form_button_container'>
								<div class='form_button'>"
									.show_button(array('name' => "btnFormSubmit", 'text' => $strFormButtonSymbol.$strFormButtonText))
									.show_button(array('type' => "button", 'name' => "btnFormClear", 'text' => __("Clear", 'lang_form'), 'class' => "button-secondary hide"));

									if($intFormPaymentProvider > 0 && is_user_logged_in() && IS_ADMIN)
									{
										$out .= show_checkbox(array('name' => "intFormPaymentTest", 'text' => __("Perform test payment", 'lang_form'), 'value' => 1));
									}

									if(isset($this->send_to) && $this->send_to != '')
									{
										$out .= input_hidden(array('name' => 'email_encrypted', 'value' => hash('sha512', $this->send_to)));
									}

								$out .= "</div>"
								.input_hidden(array('name' => 'intFormID', 'value' => $this->id))
								//.wp_nonce_field('form_submit_'.$this->id, '_wpnonce', true, false)
							."</div>";
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

		$this->edit_mode = isset($data['edit']) ? $data['edit'] : false;
		$this->send_to = isset($data['send_to']) ? $data['send_to'] : "";
		$this->answer_id = isset($data['answer_id']) ? $data['answer_id'] : "";

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
			$this->dup_ip = $this->check_if_duplicate();

			if(isset($_POST['btnFormSubmit']) && $this->is_correct_form($data)) // && wp_verify_nonce($_POST['_wpnonce'], 'form_submit_'.$this->id)
			{
				$out .= $this->process_submit();
			}

			/*else if(isset($_POST['btnFormSubmit']) || isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'form_submit_'.$this->id))
			{
				$this->process_submit_fallback();

				$error_text = __("I could not validate the form submission correctly. If the problem persists, contact an admin", 'lang_form');
			}*/

			$out .= $this->get_form($data);
		}

		$out .= get_notification();

		return $out;
	}

	function get_pie_chart()
	{
		global $wpdb;

		$out = "";

		if(!isset($_GET['answerSpam']) || $_GET['answerSpam'] == 0)
		{
			$query_answers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND (formTypeID = '1' OR formTypeID = '8' OR formTypeID = '10')", $this->id));

			if($query_answers > 1)
			{
				list($resultPie, $rowsPie) = $this->get_form_type_info(array('query_type_id' => array(1, 8, 10))); //'checkbox', 'radio_button', 'select'

				if($rowsPie > 0)
				{
					mf_enqueue_script('jquery-flot', plugins_url()."/mf_base/include/jquery.flot.min.0.7.js", '0.7');
					mf_enqueue_script('jquery-flot-pie', plugins_url()."/mf_base/include/jquery.flot.pie.min.js", '1.1');

					$js_out = $order_temp = "";
					$data = array();

					$i = 0;

					foreach($resultPie as $r)
					{
						$intForm2TypeID2 = $r->form2TypeID;
						$intFormTypeID = $r->formTypeID;
						$strFormTypeText2 = $r->formTypeText;
						$strForm2TypeOrder2 = $r->form2TypeOrder;

						switch($intFormTypeID)
						{
							case 1:
							//case 'checkbox':
								if($order_temp != '' && $strForm2TypeOrder2 != ($order_temp + 1))
								{
									$i++;
								}
							break;

							case 8:
							//case 'radio_button':
								if($order_temp != '' && $strForm2TypeOrder2 != ($order_temp + 1))
								{
									$i++;
								}
							break;

							case 10:
							//case 'select':
								$i++;
							break;
						}

						if(!isset($data[$i])){	$data[$i] = "";}

						$order_temp = $strForm2TypeOrder2;

						switch($intFormTypeID)
						{
							case 1:
							//case 'checkbox':
								$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND formTypeID = '%d' AND form2TypeID = '%d'", $this->id, $intFormTypeID, $intForm2TypeID2));

								$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".shorten_text(array('string' => $strFormTypeText2, 'limit' => 20))."', data: ".$intAnswerCount."}";
							break;

							case 8:
							//case 'radio_button':
								$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND formTypeID = '%d' AND form2TypeID = '%d'", $this->id, $intFormTypeID, $intForm2TypeID2));

								$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".shorten_text(array('string' => $strFormTypeText2, 'limit' => 20))."', data: ".$intAnswerCount."}";
							break;

							case 10:
							//case 'select':
								list($strFormTypeText2, $strFormTypeSelect) = explode(":", $strFormTypeText2);
								$arr_options = explode(",", $strFormTypeSelect);

								foreach($arr_options as $str_option)
								{
									$arr_option = explode("|", $str_option);

									if($arr_option[0] > 0 && $arr_option[1] != '')
									{
										$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d 'AND formTypeID = '%d' AND form2TypeID = '%d' AND answerText = %s", $this->id, $intFormTypeID, $intForm2TypeID2, $arr_option[0]));

										if($intAnswerCount > 0)
										{
											$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".shorten_text(array('string' => $arr_option[1], 'limit' => 20))."', data: ".$intAnswerCount."}";
										}
									}
								}
							break;
						}
					}

					$out .= "<div class='flot_wrapper'>";

						foreach($data as $key => $value)
						{
							$out .= "<div id='flot_pie_".$key."' class='flot_pie'></div>";
							$js_out .= "$.plot($('#flot_pie_".$key."'), [".$value."],
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
					<script>
						jQuery(function($)
						{"
							.$js_out
						."});
					</script>";
				}
			}
		}

		return $out;
	}
}

class mf_form_payment
{
	function __construct($id)
	{
		global $wpdb;

		$this->form_id = $id;
		$this->base_callback_url = get_site_url().$_SERVER['REQUEST_URI'];

		$result = $wpdb->get_results($wpdb->prepare("SELECT formName, formPaymentProvider, formPaymentHmac, formPaymentMerchant, formPaymentPassword, formPaymentCurrency, formAnswerURL FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $this->form_id));

		foreach($result as $r)
		{
			$this->name = $r->formName;
			$this->provider = $r->formPaymentProvider;
			$this->hmac = $r->formPaymentHmac;
			$this->merchant = $r->formPaymentMerchant;
			$this->password = $r->formPaymentPassword;
			$this->currency = $r->formPaymentCurrency;
			$this->answer_url = $r->formAnswerURL;

			$obj_form = new mf_form($this->form_id);

			$this->prefix = $obj_form->get_post_info()."_";
		}
	}

	function process_passthru($data)
	{
		global $wpdb;

		$out = "";

		$this->amount = $data['amount'];
		$this->orderid = $data['orderid'];
		$this->test = $data['test'];

		switch($this->provider)
		{
			case 1:
				$out .= $this->process_passthru_dibs();
			break;

			case 2:
				$out .= $this->process_passthru_skrill();
			break;

			case 3:
				$out .= $this->process_passthru_paypal();
			break;

			case 4:
				$out .= $this->process_passthru_billmate();
			break;
		}

		return $out;

		exit;
	}

	function process_passthru_dibs()
	{
		global $wpdb;

		$out = "";

		if(!($this->currency > 0)){	$this->currency = 752;}

		$instance = array();

		$instance['amount'] = $this->amount * 100;
		$instance['orderid'] = $this->orderid;
		$instance['test'] = $this->test;

		$hmac = $this->hmac;
		$instance['merchant'] = $this->merchant;

		$instance['currency'] = $this->currency;
		$instance['paytype'] = "MC,VISA,MTRO,DIN,AMEX,DK,V-DK,ELEC"; //FFK,JCB
		$instance['language'] = get_bloginfo('language');

		$instance['acceptreturnurl'] = $this->base_callback_url."?accept";
		$instance['callbackurl'] = $this->base_callback_url."?callback";
		$instance['cancelreturnurl'] = $this->base_callback_url."?cancel";

		$instance['capturenow'] = 1;
		$dibs_action = "https://sat1.dibspayment.com/dibspaymentwindow/entrypoint";

		$out .= "<form name='form_payment' method='post' action='".$dibs_action."'>
			<input type='hidden' name='acceptreturnurl' value='".$instance['acceptreturnurl']."'>
			<input type='hidden' name='amount' value='".$instance['amount']."'>
			<input type='hidden' name='callbackurl' value='".$instance['callbackurl']."'>
			<input type='hidden' name='cancelreturnurl' value='".$instance['cancelreturnurl']."'>
			<input type='hidden' name='currency' value='".$instance['currency']."'>
			<input type='hidden' name='language' value='".$instance['language']."'>
			<input type='hidden' name='merchant' value='".$instance['merchant']."'>
			<input type='hidden' name='orderid' value='".$instance['orderid']."'>
			<input type='hidden' name='paytype' value='".$instance['paytype']."'>";

			if($instance['test'] == 1)
			{
				$out .= "<input type='hidden' name='test' value='".$instance['test']."'>";
			}

			else
			{
				unset($instance['test']);
			}

			if($instance['capturenow'] == 1)
			{
				$out .= "<input type='hidden' name='capturenow' value='".$instance['capturenow']."'>";
			}

			else
			{
				unset($instance['capturenow']);
			}

			//Calculate HMAC
			########
			$k = hextostr($hmac);

			$string = get_hmac_prepared_string($instance);

			$instance['mac'] = hash_hmac("sha256", $string, $k);

			$out .= "<input type='hidden' name='MAC' value='".$instance['mac']."' rel='".$string."'>";
			########

		$out .= "</form>";

		if(isset($instance['test']) && $instance['test'] == 1)
		{
			$out .= "<a href='http://tech.dibspayment.com/toolbox/test_information_cards'>".__("See DIBS test info", 'lang_form')."</a><br>
			<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_form')."</button>";
		}

		else
		{
			$out .= "<script>document.form_payment.submit();</script>";
		}

		$wpdb->query("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = '"."102: ".__("Sent to payment", 'lang_form')."' WHERE answerID = '".$this->orderid."' AND form2TypeID = '0' AND answerText LIKE '10%'");

		return $out;
	}

	function save_token_with_answer_id()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2answer SET answerToken = %s WHERE answerID = '%d'", urldecode($this->token), $this->orderid));
	}

	// developer.paypal.com/webapps/developer/docs/classic/express-checkout/integration-guide/ECCustomizing/
	// developer.paypal.com/docs/classic/api/merchant/SetExpressCheckout_API_Operation_NVP/
	function process_passthru_paypal()
	{
		global $wpdb;

		include_once("lib/PayPal.php");

		$out = "";

		//$PayPalMode = $this->test == 1 ? 'sandbox' : 'live';
		$paypalmode = $this->test == 1 ? '.sandbox' : '';

		$PayPalReturnURL = $this->base_callback_url."?accept";
		$PayPalCancelURL = $this->base_callback_url."?cancel";

		$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "last"));

		//Parameters for SetExpressCheckout, which will be sent to PayPal
		$padata = '&METHOD=SetExpressCheckout'
			.'&RETURNURL='.urlencode($PayPalReturnURL)
			.'&CANCELURL='.urlencode($PayPalCancelURL)
			.'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE")
			//.'&L_PAYMENTREQUEST_0_AMT0='.urlencode($this->amount)
			.'&NOSHIPPING=0' //set 1 to hide buyer's shipping address, in-case products that does not require shipping
			//.'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this->amount)
			.'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount)
			//.'&L_PAYMENTREQUEST_0_QTY0=1'
			.'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency)
			//."&L_PAYMENTREQUEST_0_NAME=".urlencode($this->name)
			."&PAYMENTREQUEST_0_DESC=".urlencode($this->name)
			."&LANDINGPAGE=Billing" //Billing / Login
			.'&LOCALECODE='.$this->language; //PayPal pages to match the language on your website.
			//.'&LOGOIMG='."http://". //site logo
			//.'&CARTBORDERCOLOR=FFFFFF'. //border color of cart
			//.'&ALLOWNOTE=1';

		//We need to execute the "SetExpressCheckOut" method to obtain paypal token
		$httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $padata, $this->merchant, $this->password, $this->hmac); //, $PayPalMode

		//Respond according to message we receive from Paypal
		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
		{
			echo "<i class='fa fa-spinner fa-spin fa-3x'></i>";

			$this->token = $httpParsedResponseAr["TOKEN"];

			$this->action = "https://www".$paypalmode.".paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=".$this->token;

			$this->save_token_with_answer_id();

			mf_redirect($this->action);
		}

		else
		{
			$out .= "<div class='error'>
				<p>Passthru: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
				<p>".$padata."</p>
				<p>".var_export($httpParsedResponseAr, true)."</p>
			</div>";
		}

		return $out;
	}

	function process_passthru_skrill()
	{
		$out = "";

		if($this->currency == ''){	$this->currency = "USD";}

		$instance = array();

		$this->action = "https://pay.skrill.com";
		$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "first"));

		$this->sid = get_url_content($this->action."/?pay_to_email=".$this->merchant."&amount=".$this->amount."&currency=".$this->currency."&language=".$this->language."&prepare_only=1");

		$transaction_id = $this->prefix.$this->orderid;

		$out .= "<form name='form_payment' action='".$this->action."' method='post'>
			<input type='hidden' name='session_ID' value='".$this->sid."'>
			<input type='hidden' name='pay_to_email' value='".$this->merchant."'>
			<input type='hidden' name='recipient_description' value='".get_bloginfo('name')."'>
			<input type='hidden' name='transaction_id' value='".$transaction_id."'>
			<input type='hidden' name='return_url' value='".$this->base_callback_url."?accept'>
			<input type='hidden' name='cancel_url' value='".$this->base_callback_url."?cancel&transaction_id=".$transaction_id."'>
			<input type='hidden' name='status_url' value='".$this->base_callback_url."?callback'>
			<input type='hidden' name='language' value='".$this->language."'>
			<input type='hidden' name='amount' value='".$this->amount."'>
			<input type='hidden' name='currency' value='".$this->currency."'>
		</form>";

		if(isset($this->test) && $this->test == 1)
		{
			$out .= "<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_form')."</button>";
		}

		else
		{
			$out .= "<script>document.form_payment.submit();</script>";
		}

		return $out;
	}

	// developer.billmate.se/api-integration
	function process_passthru_billmate()
	{
		global $error_text;

		include_once("lib/Billmate.php");

		$out = "";

		if($this->currency == ''){	$this->currency = "USD";}

		$transaction_id = str_replace("_", "", $this->prefix).$this->orderid; //Billmate cannot handle anything but letters and numbers

		$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "first"));
		$this->country = get_site_language(array('language' => get_bloginfo('language'), 'type' => "last"));

		define("BILLMATE_SERVER", "2.1.6");
		define("BILLMATE_CLIENT", "Pluginname:BillMate:1.0");
		define("BILLMATE_LANGUAGE", $this->language);

		$bm = new BillMate($this->merchant, $this->hmac, true, $this->test, false);
		$values = array();

		$values["PaymentData"] = array(
			"method" => 8, //1=Invoice Factoring, 2=Invoice Service, 4=Invoice Part Payment, 8=Card, 16=Bank and 32=Cash (Receipt)
			//"paymentplanid" => "",
			"currency" => $this->currency,
			"language" => $this->language,
			"country" => $this->country,
			"autoactivate" => 0,
			"orderid" => $transaction_id,
			//"logo" => "Logo2.jpg",
		);

		$values["Card"] = array(
			//"promptname" => "",
			//"3dsecure" => "",
			//"recurring" => "",
			//"recurringnr" => "",
			"accepturl" => $this->base_callback_url."?accept",
			"cancelurl" => $this->base_callback_url."?cancel&transaction_id=".$transaction_id,
			//"returnmethod" => "", //POST/GET
			"callbackurl" => $this->base_callback_url."?callback",
		);

		$amount_incl_tax = $this->amount * 100;
		$amount_excl_tax = $amount_incl_tax * .8;
		$amount_tax = $amount_incl_tax * .2;

		$values["Articles"][0] = array(
				"artnr" => $transaction_id,
				"title" => $this->name,
				"quantity" => 1,
				"aprice" => $amount_excl_tax,
				"taxrate" => 25,
				"discount" => 0,
				"withouttax" => $amount_excl_tax,
		);

		$values["Cart"] = array(
			/*"Handling" => array(
				"withouttax" => "1000",
				"taxrate" => "25"
			),*/
			/*"Shipping" => array(
				"withouttax" => "3000",
				"taxrate" => "25"
			),*/
			"Total" => array(
				"withouttax" => $amount_excl_tax,
				"tax" => $amount_tax,
				"rounding" => 0,
				"withtax" => $amount_incl_tax,
			)
		);

		$result = $bm->addPayment($values);

		if(isset($result['message']) && $result['message'] != '')
		{
			$error_text = $result['message'];

			$out .= get_notification();
		}

		else if(isset($result['url']) && $result['url'] != '')
		{
			echo "<i class='fa fa-spinner fa-spin fa-3x'></i>";

			$this->action = $result['url'];

			mf_redirect($this->action, array(), 'get');
		}

		else
		{
			$out .= "<br><br>Billmate->addPayment() returned: ".var_export($bm, true)."<br><br>".var_export($result, true)."<br><br>---------------";
		}

		return $out;
	}

	function confirm_cancel()
	{
		global $wpdb;

		echo "<i class='fa fa-spinner fa-spin fa-3x'></i>";

		$wpdb->query("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = '"."103: ".__("User canceled", 'lang_form')."' WHERE answerID = '".$this->answer_id."' AND form2TypeID = '0' AND answerText LIKE '10%'");

		mf_redirect(get_site_url());
	}

	function confirm_accept()
	{
		global $wpdb, $wp_query;

		$out = "";

		if($this->answer_id > 0)
		{
			$wpdb->query("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = '"."104: ".__("User has paid. Waiting for confirmation...", 'lang_form')."' WHERE answerID = '".$this->answer_id."' AND form2TypeID = '0' AND answerText LIKE '10%'");

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
				//Switch to temp site
				####################
				$wpdbobj = clone $wpdb;
				$wpdb->blogid = $blog_id;
				$wpdb->set_prefix($wpdb->base_prefix);
				####################

				if(isset($wp_query->post->ID) && $intFormAnswerURL != $wp_query->post->ID || !isset($wp_query->post->ID))
				{
					echo "<i class='fa fa-spinner fa-spin fa-3x'></i>";

					$wpdb->query("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = '"."105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_form')."' WHERE answerID = '".$this->answer_id."' AND form2TypeID = '0' AND answerText LIKE '10%'");

					$strFormAnswerURL = get_permalink($intFormAnswerURL);

					mf_redirect($strFormAnswerURL);
				}

				else
				{
					do_log("Redirect not verified");
					//header("Status: 400 Bad Request");
				}

				//Switch back to orig site
				###################
				$wpdb = clone $wpdbobj;
				###################
			}

			else
			{
				$out .= "<p>".__("Thank you!", 'lang_form')."</p>";
			}
		}

		else
		{
			header("Status: 400 Bad Request");
		}

		return $out;
	}

	function confirm_paid($message)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0'", "116: ".$message, $this->answer_id));

		header("Status: 200 OK");
	}

	function confirm_error($message)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0'", "115: ".$message, $this->answer_id));

		header("Status: 400 Bad Request");
	}

	function process_callback()
	{
		global $wpdb;

		$out = "";

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

		$file = date("YmdHis")."_".$file_suffix;
		$debug = "URI: ".$_SERVER['REQUEST_URI']."\n\n"
			."GET: ".var_export($_GET, true)."\n\n"
			."POST: ".var_export($_POST, true)."\n\n"
			."THIS: ".var_export($this, true)."\n\n";

		list($upload_path, $upload_url) = get_uploads_folder('mf_form');

		$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => trim($debug)));*/
		##################

		$this->amount = check_var('amount', 'int');

		$out .= "<p>".__("Processing", 'lang_form')."&hellip;</p>";

		switch($this->provider)
		{
			case 1:
				$out .= $this->process_callback_dibs();
			break;

			case 2:
				$out .= $this->process_callback_skrill();
			break;

			case 3:
				$out .= $this->process_callback_paypal();
			break;

			case 4:
				$out .= $this->process_callback_billmate();
			break;
		}

		return $out;
	}

	function process_callback_dibs()
	{
		global $wpdb;

		$out = "";

		$this->answer_id = check_var('orderid', 'char');

		$hmac = $this->hmac;
		$instance['merchant'] = $this->merchant;

		if($this->is_accept)
		{
			$out .= $this->confirm_accept();
		}

		else if($this->is_callback)
		{
			$k = hextostr($hmac);

			if(isset($_POST['mobilelib']) && $_POST['mobilelib'] == "android")
			{
				$arr_from_post = array('lang', 'orderid', 'merchantid');

				$post_selection = array();

				foreach($arr_from_post as $str_from_post)
				{
					$post_selection[$str_from_post] = $_POST[$str_from_post];
				}

				$string = get_hmac_prepared_string($post_selection);
			}

			else
			{
				$string = get_hmac_prepared_string($_POST);
			}

			$mac = hash_hmac("sha256", $string, $k);
			$is_valid_mac = isset($_POST['MAC']) && $_POST['MAC'] == $mac;

			$arr_confirm_type = explode("_", $this->answer_id);

			$strConfirmType = $arr_confirm_type[0];
			$strConfirmTypeID = $arr_confirm_type[1];

			if($is_valid_mac)
			{
				$this->confirm_paid(__("Payment done", 'lang_form')." (".($this->amount / 100).")");
			}

			else
			{
				$this->confirm_error(__("Payment done", 'lang_form')." (".__("But could not verify", 'lang_form').", ".$mac." != ".$_POST['MAC'].")");
			}
		}

		else if($this->is_cancel)
		{
			$this->confirm_cancel();
		}

		return $out;
	}

	function get_info_from_token()
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, formPaymentAmount FROM ".$wpdb->base_prefix."form INNER JOIN ".$wpdb->base_prefix."form2answer USING (formID) WHERE answerToken = %s", $this->token));
		$r = $result[0];
		$this->answer_id = $r->answerID;
		$intFormPaymentAmount = $r->formPaymentAmount;

		$this->amount = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $this->answer_id, $intFormPaymentAmount));
	}

	function process_callback_paypal()
	{
		global $wpdb;

		include_once("lib/PayPal.php");

		$out = "";

		$this->token = check_var('token', 'char');
		$payer_id = check_var('PayerID', 'char');

		$this->get_info_from_token();

		if($this->is_cancel)
		{
			$this->confirm_cancel();
		}

		else if($this->is_accept)
		{
			$padata = '&TOKEN='.urlencode($this->token).
				'&PAYERID='.urlencode($payer_id).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				//'&L_PAYMENTREQUEST_0_AMT0='.urlencode($this->amount).
				//'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this->amount).
				'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount).
				//'&L_PAYMENTREQUEST_0_QTY0=1'.
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency);

			//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
			$httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $padata, $this->merchant, $this->password, $this->hmac); //, $PayPalMode

			//Check if everything went ok..
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
			{
				$out .= $this->confirm_accept();

				/*if('Completed' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
				{
					$out .= "<div class='updated'><p>Payment Received! Your product will be sent to you very soon!</p></div>";
				}

				else if('Pending' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
				{
					$out .= "<div class='error'>
						<p>Transaction Complete, but payment is still pending!</p>
						<p>You need to manually authorize this payment in your <a target='_new' href='//paypal.com'>Paypal Account</a></p>
					</div>";
				}*/

				// GetTransactionDetails requires a Transaction ID, and GetExpressCheckoutDetails requires Token returned by SetExpressCheckOut
				$padata = '&TOKEN='.urlencode($this->token);

				$httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $padata, $this->merchant, $this->password, $this->hmac); //, $PayPalMode

				if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
				{
					$this->confirm_paid(__("Payment done", 'lang_form')." (".$this->amount.")");
				}

				else
				{
					$this->confirm_error(__("Payment done", 'lang_form')." (".__("But could not verify", 'lang_form').", Success - ".$this->token.")");

					/*$out .= "<div class='error'>
						<p>GetTransactionDetails failed: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
						<p>".var_export($httpParsedResponseAr, true)."</p>
					</div>";*/

				}
			}

			else
			{
				$this->confirm_error(__("Payment done", 'lang_form')." (".__("But could not verify", 'lang_form').", ".$this->token.")");

				/*$out .= "<div class='error'>
					<p>Callback: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
					<p>".$padata."</p>
					<p>".var_export($httpParsedResponseAr, true)."</p>
				</div>";*/
			}
		}

		return $out;
	}

	function process_callback_skrill()
	{
		global $wpdb;

		$out = "";

		$transaction_id = check_var('transaction_id', 'char');
		$this->answer_id = str_replace($this->prefix, "", $transaction_id);

		if($this->is_accept)
		{
			$out .= $this->confirm_accept();
		}

		else if($this->is_callback)
		{
			//pay_to_email, pay_from_email, amount

			$md5sig = check_var('md5sig', 'char');
			$currency = check_var('currency', 'char');

			$merchant_id = check_var('merchant_id', 'char');
			$mb_amount = check_var('mb_amount', 'char');
			$mb_currency = check_var('mb_currency', 'char');
			$status = check_var('status', 'char');

			$md5calc = strtoupper(md5($merchant_id.$transaction_id.strtoupper(md5($this->hmac)).$mb_amount.$mb_currency.$status));

			$is_valid_mac = $md5sig == $md5calc;

			$payment_status_text = "";

			switch($status)
			{
				case -2:		$payment_status_text = __("Failed", 'lang_form');			break;
				case 2:			$payment_status_text = __("Processed", 'lang_form');		break;
				case 0:			$payment_status_text = __("Pending", 'lang_form');			break;
				case -1:		$payment_status_text = __("Cancelled", 'lang_form');		break;
			}

			if($is_valid_mac)
			{
				$this->confirm_paid($status.": ".$payment_status_text." (".$this->amount." ".$currency.")");
			}

			else
			{
				$this->confirm_error($status.": ".$payment_status_text." (".__("But could not verify", 'lang_form').", ".$md5sig." != ".$md5calc.") (".$this->amount." ".$currency.")");
			}
		}

		else if($this->is_cancel)
		{
			$this->confirm_cancel();
		}

		return $out;
	}

	function process_callback_billmate()
	{
		global $wpdb;

		$out = "";

		/*"credentials": {
			"hash": "26042c7bc65e106d1d0276a72edc4b1d00e8237155f0de52b4a3b2391c7f6a6bad828ecbb0533b04782e816341ff376943719d6de7519e3384379292ed2d9d22"
		},
		"data": {
			"number": "1043",
			"status": "Paid",
			"url": "https:\/\/invoice.billmate.se\/20170914150722041959bac84c65edf",
			"orderid": "176",
			"carddata": {
				"maskedcardno": "xxxxxxxxxxxx5187",
				"cardholdername": "",
				"expirymonth": "04",
				"expiryyear": "20"
			}
		}*/

		$result_credentials = isset($_POST['credentials']) ? json_decode(stripslashes($_POST['credentials']), true) : array();
		$result_data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : array();

		/*echo "POST: ".var_export($_POST, true)."<br>"
		."Data: ".var_export($result_data, true)."<br>"
		."Echo: ".$result_data."<br>"
		."Decode: ".var_export(json_decode($result_data, true), true)."<br>"
		."Order ID: ".$result_data['orderid'];*/

		$hash = $result_credentials['hash'];
		$status = $result_data['status'];
		$orderid = $result_data['orderid'];

		$this->answer_id = str_replace(str_replace("_", "", $this->prefix), "", $orderid);

		$is_verified = hash_hmac('sha512', json_encode($result_data), $this->hmac) == $hash;

		//do_log("Verified: ".$is_verified." (".hash_hmac('sha512', json_encode($result_data), $this->hmac)." == ".$hash.")");

		if($is_verified)
		{
			if($this->is_accept)
			{
				$out .= $this->confirm_accept();
			}

			else if($this->is_callback)
			{
				do_log("Billmate callback: ".var_export($_REQUEST, true));

				$payment_status_text = "";

				switch($status)
				{
					case 'Paid':		$payment_status_text = __("Paid", 'lang_form');			break;
					case 'Created':		$payment_status_text = __("Created", 'lang_form');		break;
					case 'Pending':		$payment_status_text = __("Pending", 'lang_form');		break;
					case 'Failed':		$payment_status_text = __("Failed", 'lang_form');		break;
					case 'Cancelled':	$payment_status_text = __("Cancelled", 'lang_form');	break;
				}

				if($is_verified)
				{
					switch($status)
					{
						case 'Paid':
						case 'Created':
							//Do nothing here
						break;

						case 'Pending':
						case 'Failed':
						case 'Cancelled':
							do_log("What should I do? I was given the callback but it sent me (".var_export($result, true).")");
						break;
					}

					$this->confirm_paid($status." (".$orderid.")");
				}

				else
				{
					$this->confirm_error($status." (".__("But could not verify", 'lang_form').", ".$md5sig." != ".$md5calc.") (".$orderid.")");
				}
			}

			else if($this->is_cancel)
			{
				$this->confirm_cancel();
			}
		}

		else
		{
			do_log(__("Not verified correctly", 'lang_form')." (".var_export($_REQUEST, true).")");
		}

		return $out;
	}
}

class mf_form_export extends mf_export
{
	function get_defaults()
	{
		$this->plugin = "mf_form";
	}

	function get_export_data()
	{
		global $wpdb;

		$obj_form = new mf_form($this->type);
		$this->name = $obj_form->get_post_info(array('select' => 'post_title'));

		$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' ORDER BY form2TypeOrder ASC", $this->type));

		$this_row = array();

		foreach($result as $r)
		{
			$intForm2TypeID = $r->form2TypeID;
			$intFormTypeID = $r->formTypeID;
			$strFormTypeCode = $r->formTypeCode;
			$obj_form->label = $r->formTypeText;

			switch($strFormTypeCode)
			{
				//case 2:
				case 'range':
					$obj_form->parse_range_label();
				break;

				//case 10:
				case 'select':
				//case 11:
				case 'select_multiple':
				//case 16:
				case 'checkbox_multiple':
					list($obj_form->label, $str_select) = explode(":", $obj_form->label);
				break;
			}

			$this_row[] = stripslashes(strip_tags($obj_form->label));
		}

		$this_row[] = __("Created", 'lang_form');

		$this->data[] = $this_row;

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, formID, answerCreated, answerIP FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE formID = '%d' AND answerSpam = '0' GROUP BY answerID ORDER BY answerCreated DESC", $this->type));

		foreach($result as $r)
		{
			$intAnswerID = $r->answerID;
			$intFormID = $r->formID;
			$strAnswerCreated = $r->answerCreated;
			$strAnswerIP = $r->answerIP;

			$this_row = array();

			$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' ORDER BY form2TypeOrder ASC", $intFormID));

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
						//case 8:
						case 'radio_button':
							$strAnswerText = 1;
						break;

						//case 7:
						case 'datepicker':
							$strAnswerText = format_date($strAnswerText);
						break;

						//case 10:
						case 'select':
							$strAnswerText = $obj_form->parse_select_info($strAnswerText);
						break;

						//case 11:
						case 'select_multiple':
						//case 16:
						case 'checkbox_multiple':
							$strAnswerText = $obj_form->parse_multiple_info($strAnswerText);
						break;

						//case 15:
						case 'file':
							$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

							foreach($result as $r)
							{
								$strAnswerText = "<a href='".$r->guid."' rel='external'>".$r->post_title."</a>";
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

			$this_row[] = $strAnswerCreated;

			$this->data[] = $this_row;
		}
	}
}

class mf_form_table extends mf_list_table
{
	function set_default()
	{
		global $wpdb;

		$this->post_type = "mf_form";

		$this->orderby_default = "post_modified";
		$this->orderby_default_order = "desc";

		/*$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_form';*/

		$this->query_join .= " INNER JOIN ".$wpdb->base_prefix."form ON ".$wpdb->posts.".ID = ".$wpdb->base_prefix."form.postID";
		$this->query_where .= ($this->query_where != '' ? " AND " : "")."blogID = '".$wpdb->blogid."'";

		if($this->search != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "").get_form_xtra("", $this->search, "", "post_title");
		}

		$this->set_views(array(
			'db_field' => 'post_status',
			'types' => array(
				'all' => __("All", 'lang_form'),
				'publish' => __("Public", 'lang_form'),
				'draft' => __("Draft", 'lang_form'),
				'trash' => __("Trash", 'lang_form')
			),
		));

		$this->set_columns(array(
			//'cb' => '<input type="checkbox">',
			'post_title' => __("Name", 'lang_form'),
			'content' => __("Content", 'lang_form'),
			'answers' => __("Answers", 'lang_form'),
			'latest_answer' => __("Latest Answer", 'lang_form'),
			'post_modified' => __("Modified", 'lang_form'),
		));

		$this->set_sortable_columns(array(
			'post_title',
			'post_modified',
		));
	}

	function column_default($item, $column_name)
	{
		global $wpdb;

		$out = "";

		$post_id = $item['ID'];
		$post_status = $item['post_status'];

		$obj_form = new mf_form();
		$obj_form->get_form_id($post_id);

		switch($column_name)
		{
			case 'post_title':
				$strFormName = $item[$column_name];

				$post_edit_url = IS_ADMIN ? "?page=mf_form/create/index.php&intFormID=".$obj_form->id : "#";

				$actions = array();

				if($post_status != 'trash')
				{
					if(IS_ADMIN)
					{
						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_form')."</a>";
						$actions['delete'] = "<a href='#delete/form/".$obj_form->id."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
					}

					$actions['copy'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnFormCopy&intFormID=".$obj_form->id, 'form_copy_'.$obj_form->id)."'>".__("Copy", 'lang_form')."</a>";

					if($post_status == 'publish' && $obj_form->id > 0)
					{
						$shortcode = "[mf_form id=".$obj_form->id."]";

						$result = get_pages_from_shortcode($shortcode);

						if(count($result) > 0)
						{
							/*foreach($result as $r)
							{
								$post_id_temp = $r->ID;

								$actions['edit_page'] = "<a href='".admin_url("post.php?post=".$post_id_temp."&action=edit")."'>".__("Edit Page", 'lang_form')."</a>";
								$actions['view_page'] = "<a href='".get_permalink($post_id_temp)."'>".__("View page", 'lang_form')."</a>";
							}*/

							foreach($result as $post_id_temp)
							{
								$actions['edit_page'] = "<a href='".admin_url("post.php?post=".$post_id_temp."&action=edit")."'>".__("Edit Page", 'lang_form')."</a>";
								$actions['view_page'] = "<a href='".get_permalink($post_id_temp)."'>".__("View page", 'lang_form')."</a>";
							}
						}

						else
						{
							if($obj_form->get_form_status() == "publish")
							{
								$post_url = get_permalink($post_id);

								if($post_url != '')
								{
									$actions['view'] = "<a href='".$post_url."'>".__("View form", 'lang_form')."</a>";
								}
							}

							$actions['add_post'] = "<a href='".admin_url("post-new.php?content=".$shortcode)."'>".__("Add New Post", 'lang_form')."</a>";
							$actions['add_page'] = "<a href='".admin_url("post-new.php?post_type=page&content=".$shortcode)."'>".__("Add New Page", 'lang_form')."</a>";
						}
					}
				}

				else if(IS_ADMIN)
				{
					$actions['recover'] = "<a href='".$post_edit_url."&recover'>".__("Recover", 'lang_form')."</a>";
				}

				$out .= "<a href='".$post_edit_url."'>"
					.$strFormName
				."</a>"
				.$this->row_actions($actions);
			break;

			case 'content':
				if($post_status == 'publish')
				{
					$out .= "<i class='fa fa-lg fa-link grey' title='".__("Public", 'lang_form')."'></i> ";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT formEmail, formEmailNotifyPage, formEmailConfirm, formEmailConfirmPage, formPaymentProvider FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $obj_form->id));

				foreach($result as $r)
				{
					$strFormEmail = $r->formEmail;
					$intFormEmailNotifyPage = $r->formEmailNotifyPage;
					$intFormEmailConfirm = $r->formEmailConfirm;
					$intFormEmailConfirmPage = $r->formEmailConfirmPage;
					$intFormPaymentProvider = $r->formPaymentProvider;

					if($strFormEmail != '')
					{
						if($intFormEmailNotifyPage > 0)
						{
							$out .= "<i class='fa fa-lg fa-send grey' title='".sprintf(__("A notification email based on a template will be sent to %s", 'lang_form'), $strFormEmail)."'></i> ";
						}

						else
						{
							$out .= "<i class='fa fa-lg fa-send grey' title='".sprintf(__("E-mails will be sent to %s on every answer", 'lang_form'), $strFormEmail)."'></i> ";
						}
					}

					if($intFormEmailConfirm > 0)
					{
						if($intFormEmailConfirmPage > 0)
						{
							$out .= "<i class='fa fa-lg fa-send-o grey' title='".__("A confirmation email based on a template will be sent to the visitor", 'lang_form')."'></i> ";
						}

						else
						{
							$out .= "<i class='fa fa-lg fa-send-o grey' title='".__("A confirmation email will be sent to the visitor", 'lang_form')."'></i> ";
						}
					}

					if($intFormPaymentProvider > 0)
					{
						switch($intFormPaymentProvider)
						{
							case 3:
								$icon = "fa-paypal";
							break;

							default:
								$icon = "fa-shopping-cart";
							break;
						}

						$out .= "<i class='fa fa-lg ".$icon." grey' title='".__("Provider", 'lang_form')."'></i> ";
					}
				}

				if($obj_form->is_form_field_type_used(array('required' => true)))
				{
					$out .= "<i class='fa fa-lg fa-asterisk grey' title='".__("There are required fields", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('autofocus' => true)))
				{
					$out .= "<i class='fa fa-lg fa-i-cursor grey' title='".__("There are autofocus fields", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('remember' => true)))
				{
					$out .= "<i class='fa fa-lg fa-refresh grey' title='".__("There are remembered fields", 'lang_form')."'></i> ";
				}

				$out .= "<br>";

				if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'check_code' => 'email')))
				{
					$out .= "<i class='fa fa-lg fa-at grey' title='".__("There is a field for entering email adress", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 1)))
				{
					$out .= "<i class='fa fa-lg fa-check-square-o grey' title='".__("Checkbox", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 2)))
				{
					$out .= "<i class='fa fa-lg fa-sliders grey' title='".__("Range", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 7)))
				{
					$out .= "<i class='fa fa-lg fa-calendar grey' title='".__("Datepicker", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 8)))
				{
					$out .= "<i class='fa fa-lg fa-circle-o grey' title='".__("Radio button", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 15)))
				{
					$out .= "<i class='fa fa-lg fa-file-o grey' title='".__("File", 'lang_form')."'></i> ";
				}
			break;

			case 'answers':
				if($post_status != 'trash')
				{
					$wpdb->query($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE formID = '%d' AND answerSpam = '0' GROUP BY answerID", $obj_form->id));
					$query_answers = $wpdb->num_rows;

					$wpdb->query($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE formID = '%d' AND answerSpam = '1' GROUP BY answerID", $obj_form->id));
					$query_spam = $wpdb->num_rows;

					if($query_answers > 0 || $query_spam > 0)
					{
						$count_message = get_count_answer_message(array('form_id' => $obj_form->id));

						$actions = array();

						$actions['show_answers'] = "<a href='?page=mf_form/answer/index.php&intFormID=".$obj_form->id."'>".__("Show", 'lang_form')."</a>";

						$actions['export_csv'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnExportRun&intExportType=".$obj_form->id."&strExportAction=csv", 'export_run')."'>".__("CSV", 'lang_form')."</a>";

						if(is_plugin_active("mf_phpexcel/index.php"))
						{
							$actions['export_xls'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnExportRun&intExportType=".$obj_form->id."&strExportAction=xls", 'export_run')."'>".__("XLS", 'lang_form')."</a>";
						}

						$out .= $query_answers.($query_spam > 0 ? " <span class='grey'>(".$query_spam.")</span>" : "")
						.$count_message
						.$this->row_actions($actions);
					}
				}
			break;

			case 'latest_answer':
				$latest_answer = $wpdb->get_var($wpdb->prepare("SELECT answerCreated FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d' ORDER BY answerCreated DESC", $obj_form->id));

				$out .= format_date($latest_answer);
			break;

			case 'post_modified':
				$out .= format_date($item[$column_name]);
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
		$this->post_type = "";

		$this->arr_settings['query_select_id'] = "answerID";
		$this->arr_settings['query_all_id'] = "0";
		$this->arr_settings['query_trash_id'] = "1";
		$this->orderby_default = "answerCreated";
		$this->orderby_default_order = "desc";

		$this->arr_settings['page_vars'] = array('intFormID' => $obj_form->id);

		$this->query_where .= ($this->query_where != '' ? " AND " : "")."formID = '".$obj_form->id."'";

		if($this->search != '')
		{
			$this->query_join .= " LEFT JOIN ".$wpdb->base_prefix."form_answer USING (answerID) LEFT JOIN ".$wpdb->base_prefix."form_answer_email USING (answerID)";
			$this->query_where .= " AND (answerText LIKE '%".$this->search."%' OR answerEmail LIKE '%".$this->search."%' OR answerCreated LIKE '%".$this->search."%')";
		}

		$this->set_views(array(
			'db_field' => 'answerSpam',
			'types' => array(
				'0' => __("All", 'lang_address'),
				'1' => __("Spam", 'lang_address')
			),
		));

		$arr_columns = array(
			//'cb' => '<input type="checkbox">',
		);

		if(isset($_GET['answerSpam']) && $_GET['answerSpam'] == 1)
		{
			$arr_columns['answerSpam'] = __("Spam", 'lang_form');
		}

		$obj_form->answer_column = 0;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeID, formTypeCode, formTypeText, form2TypeID FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' ORDER BY form2TypeOrder ASC", $obj_form->id));

		foreach($result as $r)
		{
			$intFormTypeID = $r->formTypeID;
			$strFormTypeCode = $r->formTypeCode;
			$obj_form->label = $r->formTypeText;
			$intForm2TypeID2 = $r->form2TypeID;

			switch($strFormTypeCode)
			{
				//case 1:
				case 'checkbox':
				//case 8:
				case 'radio_button':
					$label_limit = 10;
				break;

				//case 2:
				case 'range':
					$obj_form->parse_range_label();

					$label_limit = 10;
				break;

				//case 7:
				case 'datepicker':
					$label_limit = 15;
				break;

				//case 10:
				case 'select':
				//case 11:
				case 'select_multiple':
				//case 16:
				case 'checkbox_multiple':
					list($obj_form->label, $str_select) = explode(":", $obj_form->label);

					$label_limit = 10;
				break;

				default:
					$label_limit = 20;
				break;
			}

			$arr_columns[$intForm2TypeID2] = shorten_text(array('string' => $obj_form->label, 'limit' => $label_limit));
		}

		if($obj_form->has_payment)
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

				if($item[$column_name] == true)
				{
					$out .= "<i class='fa fa-lg fa-close red'></i>";

					if($item['spamID'] > 0)
					{
						$strSpamExplain = $obj_form->get_spam_rules(array('id' => $item['spamID'], 'type' => 'explain'));

						if($strSpamExplain != '')
						{
							$out .= "&nbsp;".$strSpamExplain;
						}
					}

					$actions['unspam'] = "<a href='".wp_nonce_url("?page=mf_form/answer/index.php&btnAnswerApprove&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID, 'answer_approve_'.$intAnswerID)."' rel='confirm'>".__("Approve", 'lang_form')."</a>";
				}

				/*else
				{
					$out .= "<i class='fa fa-lg fa-check green'></i>";
				}*/

				$out .= $this->row_actions($actions);
			break;

			case 'payment':
				$strAnswerText_temp = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '0'", $intAnswerID));

				$out .= $strAnswerText_temp;
			break;

			case 'answerCreated':
				$obj_form->answer_column = 0;

				$actions = array();

				$actions['id'] = __("ID", 'lang_form').": ".$intAnswerID;
				$actions['ip'] = __("IP", 'lang_form').": ".$item['answerIP'];

				if($item['answerToken'] != '')
				{
					$actions['token'] = __("Token", 'lang_form').": ".$item['answerToken'];
				}

				if($obj_form->has_payment == false)
				{
					$strSentTo = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '0'", $intAnswerID));

					$strSentTo = trim(trim($strSentTo), ', ');
					$strSentTo = str_replace(", ", "<br>", $strSentTo);

					if($strSentTo != '' && strlen($strSentTo) > 4)
					{
						$actions['sent_to'] = "<br><strong>".__("Sent to", 'lang_form')."</strong><br>".$strSentTo;
					}
				}

				$out .= format_date($item['answerCreated'])
				.$this->row_actions($actions);
			break;

			case 'sent':
				$result_emails = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerSent, answerType FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerEmail != ''", $intAnswerID));
				$count_temp = $wpdb->num_rows;

				if($count_temp > 0)
				{
					$li_out = $strAnswerEmail_temp = "";
					$sent_successfully = $sent_failed = $sent_failed_w_type = 0;

					foreach($result_emails as $r)
					{
						$strAnswerEmail = $r->answerEmail;
						$intAnswerSent = $r->answerSent;
						$strAnswerType = $r->answerType;

						if($intAnswerSent == 1)
						{
							$fa_class = "fa-check green";

							$sent_successfully++;
						}

						else
						{
							$fa_class = "fa-close red";

							$sent_failed++;

							if($strAnswerType != '')
							{
								$sent_failed_w_type++;
							}
						}

						if($strAnswerEmail != $strAnswerEmail_temp)
						{
							$li_out .= "<li>
								<i class='fa ".$fa_class."'></i> ".$strAnswerEmail
							."</li>";

							$strAnswerEmail_temp = $strAnswerEmail;
						}
					}

					$out .= ($sent_failed > 0 ? $sent_successfully."/" : "").$count_temp
					."<div class='row-actions'>
						<ul>".$li_out."</ul>";

						if($sent_failed_w_type > 0)
						{
							$out .= "<a href='".wp_nonce_url("?page=mf_form/answer/index.php&btnMessageResend&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID, 'message_resend_'.$intAnswerID)."' rel='confirm'>".__("Resend", 'lang_form')."</a>";
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
					$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText, checkCode FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' AND form2TypeID = '%d' LIMIT 0, 1", $obj_form->id, $column_name));

					foreach($resultText as $r)
					{
						$intForm2TypeID = $r->form2TypeID;
						$intFormTypeID = $r->formTypeID;
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
								//case 8:
								case 'radio_button':
									$strAnswerText = 1;
								break;

								//case 7:
								case 'datepicker':
									if(strlen($strAnswerText) > 10)
									{
										$strAnswerText = format_date($strAnswerText);
									}

									else
									{
										$strAnswerText = $strAnswerText;
									}
								break;

								//case 10:
								case 'select':
									$strAnswerText = $obj_form->parse_select_info($strAnswerText);
								break;

								//case 11:
								case 'select_multiple':
								//case 16:
								case 'checkbox_multiple':
									$obj_form->prefix = $obj_form->get_post_info()."_";

									$strAnswerText = $obj_form->parse_multiple_info($strAnswerText);
								break;

								//case 15:
								case 'file':
									$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

									foreach($result as $r)
									{
										$strAnswerText = "<a href='".$r->guid."' rel='external'>".$r->post_title."</a>";
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
												$strAnswerText = "<a href='mailto:".$strAnswerText."'>".$strAnswerText."</a>";

												if($item['answerSpam'] == false)
												{
													$actions['spam'] = "<a href='".wp_nonce_url("?page=mf_form/answer/index.php&btnAnswerSpam&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID, 'answer_spam_'.$intAnswerID)."' rel='confirm'>".__("Mark as Spam", 'lang_form')."</a>";
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
							$out .= stripslashes(stripslashes($strAnswerText));
						}

						if($obj_form->answer_column == 0)
						{
							$actions['edit'] = "<a href='?page=mf_form/view/index.php&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID."'>".__("Edit", 'lang_form')."</a>";
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

class mf_form_output
{
	function __construct($data)
	{
		$this->id = isset($data['id']) ? $data['id'] : 0;

		$this->row = $data['result'];
		$this->query_prefix = $data['query_prefix'];

		$this->output = "";

		$this->show_required = $this->show_autofocus = $this->show_remember = false;

		$this->answer_text = "";

		$this->in_edit_mode = $data['in_edit_mode'];
	}

	function calculate_value($intAnswerID)
	{
		global $wpdb;

		if($intAnswerID > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' AND answerID = '%d' LIMIT 0, 1", $this->row->form2TypeID, $intAnswerID));

			foreach($result as $r)
			{
				switch($this->row->formTypeCode)
				{
					default:
						$this->answer_text = stripslashes(stripslashes($r->answerText));
					break;

					//case 8:
					case 'radio_button':
						$this->answer_text = $this->row->form2TypeID;
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
		if($this->row->formTypeFetchFrom != '' && isset($field_data['value']) && $field_data['value'] == '')
		{
			$field_data['value'] = check_var($this->row->formTypeFetchFrom);
		}
	}

	function check_limit($data)
	{
		global $wpdb;

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
		list($this->label, $str_select) = explode(":", $string);
		$arr_options = explode(",", $str_select);

		$arr_data = array();

		foreach($arr_options as $str_option)
		{
			$arr_option = explode("|", $str_option);
			$arr_option = $this->check_limit(array('array' => $arr_option, 'form2TypeID' => $this->row->form2TypeID));

			$arr_data[$arr_option[0]] = $arr_option[1];
		}

		return $arr_data;
	}

	function get_form_fields($data = array())
	{
		global $intFormTypeID2_temp, $intForm2TypeID2_temp;

		if(!isset($data['show_label'])){		$data['show_label'] = true;}
		if(!isset($data['ignore_required'])){	$data['ignore_required'] = false;}

		if($data['ignore_required'] == true)
		{
			$this->row->formTypeRequired = false;
		}

		$field_data = array(
			'name' => $this->query_prefix.$this->row->form2TypeID,
		);

		$class_output = $this->row->formTypeClass != '' ? " class='".$this->row->formTypeClass."'" : "";
		$class_output_small = ($this->row->formTypeClass != '' ? " ".$this->row->formTypeClass : "");

		switch($this->row->formTypeCode)
		{
			//case 1:
			case 'checkbox':
				$is_first_checkbox = false;

				if($this->row->formTypeID != $intFormTypeID2_temp)
				{
					$intForm2TypeID2_temp = $this->row->form2TypeID;

					$is_first_checkbox = true;
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->formTypeText;
				}

				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['value'] = 1;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->formTypeClass.($is_first_checkbox ? " clear" : "");

				$this->output .= show_checkbox($field_data);

				$this->show_required = true;
			break;

			//case 2:
			case 'range':
				$arr_content = explode("|", $this->row->formTypeText);

				if($this->answer_text == '' && isset($arr_content[3]))
				{
					$this->answer_text = $arr_content[3];
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content[0]." (<span>".$this->answer_text."</span>)";
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = "min='".$arr_content[1]."' max='".$arr_content[2]."'".($this->row->formTypeAutofocus ? " autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['type'] = "range";

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 7:
			case 'datepicker':
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->formTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['type'] = "date";
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 8:
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

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->formTypeText;
				}

				$field_data['value'] = $this->row->form2TypeID;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->formTypeClass.($is_first_radio ? " clear" : "");

				$this->output .= show_radio_input($field_data);

				$this->show_required = true;
			break;

			//case 10:
			case 'select':
				if($this->row->formTypeActionShow > 0)
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_action";
					$field_data['xtra'] = "data-equals='".$this->row->formTypeActionEquals."' data-show='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->label;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_remember = true;
			break;

			//case 11:
			case 'select_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->label;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;
				$field_data['xtra'] = "class='multiselect'";

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = true;
			break;

			//case 16:
			case 'checkbox_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->label;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_checkboxes($field_data);

				$this->show_required = true;
			break;

			//case 3:
			case 'input_field':
				if($this->row->checkCode == "zip")
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_zipcode";
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->formTypeText;
				}

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

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 4:
			case 'textarea':
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->formTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 5:
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

			//case 6:
			case 'space':
				$this->output .= $this->in_edit_mode == true ? "<p class='grey".$class_output_small."'>(".__("Space", 'lang_form').")</p>" : "<p".$class_output.">&nbsp;</p>";
			break;

			//case 9:
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

			//case 12:
			case 'hidden_field':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey".$class_output_small."'>".__("Hidden", 'lang_form')." (".$this->query_prefix.$this->row->form2TypeID.": ".$this->row->formTypeText.")</p>";
				}

				else
				{
					$field_data['value'] = ($this->answer_text != '' ? $this->answer_text : $this->row->formTypeText);

					$this->filter_form_fields($field_data);
					$this->output .= input_hidden($field_data);
				}
			break;

			//case 13:
			case 'custom_tag':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;".$this->row->formTypeText.$class_output."&gt;</p>";
				}

				else
				{
					$this->output .= "<".$this->row->formTypeText.$class_output." id='".$field_data['name']."'>";
				}
			break;

			//case 14:
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

			//case 15:
			case 'file':
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->formTypeText;
				}

				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->output .= show_file_field($field_data);

				$this->show_required = true;
			break;

			default:
				do_log(__("There was no code for this type", 'lang_form')." (".$this->row->formTypeCode.")");
			break;
		}

		$intFormTypeID2_temp = $this->row->formTypeID;
	}

	function get_output($data = array())
	{
		global $wpdb;

		$out = "";

		if(!isset($this->in_edit_mode)){	$this->in_edit_mode = false;}

		if($this->in_edit_mode == true)
		{
			$out .= "<mf-form-row id='type_".$this->row->form2TypeID."' class='flex_flow".($data['form2type_id'] == $this->row->form2TypeID ? " active" : "")."'>"
				.$this->output
				."<div class='row_settings'>";

					if($this->show_required == true)
					{
						$out .= show_checkbox(array('name' => "require_".$this->row->form2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRequired, 'xtra' => "class='ajax_checkbox' rel='require/type/".$this->row->form2TypeID."'"));
					}

					if($this->show_autofocus == true)
					{
						$out .= show_checkbox(array('name' => "autofocus_".$this->row->form2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeAutofocus, 'xtra' => "class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->form2TypeID."'"));
					}

					if($this->show_remember == true)
					{
						$out .= show_checkbox(array('name' => "remember_".$this->row->form2TypeID, 'text' => __("Remember answer", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRemember, 'xtra' => "class='ajax_checkbox remember' rel='remember/type/".$this->row->form2TypeID."'"));
					}

					$out .= "<a href='?page=mf_form/create/index.php&intFormID=".$this->id."&intForm2TypeID=".$this->row->form2TypeID."'>".__("Edit", 'lang_form')."</a>";

					$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d'", $this->row->form2TypeID));

					if($wpdb->num_rows == 0)
					{
						$out .= " | <a href='#delete/type/".$this->row->form2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
					}

					$out .= " | <a href='?page=mf_form/create/index.php&btnFieldCopy&intFormID=".$this->id."&intForm2TypeID=".$this->row->form2TypeID."'>".__("Copy", 'lang_form')."</a>
				</div>";

			$out .= "</mf-form-row>";
		}

		else
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
		$widget_ops = array(
			'classname' => 'form',
			'description' => __("Display a form that you've previously created", 'lang_form')
		);

		$this->arr_default = array(
			'form_heading' => "",
			'form_id' => "",
		);

		parent::__construct('form-widget', __("Form", 'lang_form'), $widget_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['form_id'] > 0)
		{
			echo $before_widget;

				if($instance['form_heading'] != '')
				{
					echo $before_title
						.$instance['form_heading']
					.$after_title;
				}

				$obj_form = new mf_form($instance['form_id']);

				echo $obj_form->process_form()
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['form_heading'] = strip_tags($new_instance['form_heading']);
		$instance['form_id'] = strip_tags($new_instance['form_id']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$obj_form = new mf_form();
		$arr_data = $obj_form->get_form_array(array('force_has_page' => false));

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('form_heading'), 'text' => __("Heading", 'lang_form'), 'value' => $instance['form_heading']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('form_id'), 'value' => $instance['form_id']))
		."</div>";
	}
}