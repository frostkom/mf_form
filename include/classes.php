<?php

class mf_form
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : check_var('intFormID');

		$this->post_status = "";
		$this->form2type_id = $this->post_id = 0;

		$this->meta_prefix = "mf_form_";

		$this->edit_mode = $this->is_spam = $this->is_spam_id = $this->is_sent = false;

		if($this->id > 0)
		{
			$this->get_post_id();
		}
	}

	function combined_head($load_replacement = false)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_form', $plugin_include_url."style.css", $plugin_version);
		mf_enqueue_script('script_form', $plugin_include_url."script.js", array('ajax_url' => admin_url('admin-ajax.php'), 'plugins_url' => plugins_url(), 'plugin_url' => $plugin_include_url, 'please_wait' => __("Please wait", 'lang_form')), $plugin_version);

		if($load_replacement == true)
		{
			if(get_option('setting_replacement_form') > 0)
			{
				mf_enqueue_style('style_form_replacement', $plugin_include_url."style_replacement.css", $plugin_version);

				add_filter('the_content', 'the_content_form');
			}
		}
	}

	function admin_init()
	{
		$this->combined_head();

		global $pagenow;

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
					wp_enqueue_script('jquery-ui-sortable');
					mf_enqueue_script('script_touch', $plugin_include_url."jquery.ui.touch-punch.min.js", $plugin_version);
				}

				if($page == 'mf_form/create/index.php' || $page == 'mf_form/answer/index.php')
				{
					mf_enqueue_style('style_forms_wp', $plugin_include_url."style_wp.css", $plugin_version);
					mf_enqueue_script('script_forms_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')), $plugin_version);
				}
			}
		}
	}

	function login_init()
	{
		$this->combined_head(true);
	}

	function wp_head()
	{
		$this->combined_head(true);
	}

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

	function parse_multiple_info($strAnswerText, $return_value)
	{
		$strAnswerText_orig = $strAnswerText;

		$arr_answer_text = explode(",", str_replace($this->prefix, "", $strAnswerText));
		$strAnswerText = "";

		@list($this->label, $str_select) = explode(":", $this->label);

		if($return_value == true) // && $str_select != ''
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

		if($strAnswerText == '')
		{
			$strAnswerText = implode(",", $arr_answer_text);
		}

		return $strAnswerText;
	}

	function get_form_types()
	{
		return array(
			1 => array('code' => 'checkbox',			'name' => "&#xf046; ".__("Checkbox", 'lang_form'),					'desc' => __("To choose one or many alternatives", 'lang_form'),							'result' => 1),
			2 => array('code' => 'range',				'name' => "&#xf1de; ".__("Range", 'lang_form'),						'desc' => __("To choose a min and max value and create a slider for that", 'lang_form'),	'result' => 1),
			3 => array('code' => 'input_field',			'name' => "&#xf120; ".__("Input Field", 'lang_form'),				'desc' => __("To enter a short text", 'lang_form'),											'result' => 1),
			4 => array('code' => 'textarea',			'name' => "&#xf044; ".__("Textarea", 'lang_form'),					'desc' => __("To enter a longer text on multiple rows", 'lang_form'),						'result' => 1),
			5 => array('code' => 'text',				'name' => "&#xf1dd; ".__("Text", 'lang_form'),						'desc' => __("To present information to the visitor", 'lang_form'),							'result' => 0),
			6 => array('code' => 'space',				'name' => "&#xf141; ".__("Space", 'lang_form'),						'desc' => __("To separate fields in the form with empty space", 'lang_form'),				'result' => 0), //f07d
			7 => array('code' => 'datepicker',			'name' => "&#xf073; ".__("Datepicker", 'lang_form'),				'desc' => __("To choose a date", 'lang_form'),												'result' => 1),
			8 => array('code' => 'radio_button',		'name' => "&#xf192; ".__("Radio Button", 'lang_form'),				'desc' => __("To choose one alternative", 'lang_form'),										'result' => 1),
			9 => array('code' => 'referer_url',			'name' => "&#xf1e0; ".__("Referer URL", 'lang_form'),				'desc' => __("To get which URL the visitor came from", 'lang_form'),						'result' => 1),
			10 => array('code' => 'select',				'name' => "&#xf00b; ".__("Dropdown", 'lang_form'),					'desc' => __("To choose one alternative", 'lang_form'),										'result' => 1),
			11 => array('code' => 'select_multiple',	'name' => "&#xf022; ".__("Multiple Selection", 'lang_form'),		'desc' => __("To choose one or many alternatives", 'lang_form'),							'result' => 1),
			12 => array('code' => 'hidden_field',		'name' => "&#xf070; ".__("Hidden Field", 'lang_form'),				'desc' => __("To add hidden data to the form", 'lang_form'),								'result' => 1),
			13 => array('code' => 'custom_tag',			'name' => "&#xf121; ".__("Custom Tag", 'lang_form'),				'desc' => __("To add a custom tag", 'lang_form'),											'result' => 0),
			14 => array('code' => 'custom_tag_end',		'name' => "&#xf121; ".__("Custom Tag (end)", 'lang_form'),			'desc' => __("To add a custom end tag", 'lang_form'),										'result' => 0,		'public' => 'no'),
			15 => array('code' => 'file',				'name' => "&#xf115; ".__("File", 'lang_form'),						'desc' => __("To add a file upload to the form", 'lang_form'),								'result' => 1), //f03e
			16 => array('code' => 'checkbox_multiple',	'name' => "&#xf046; ".__("Multiple Checkboxes", 'lang_form'),		'desc' => __("To choose one or many alternatives", 'lang_form'),							'result' => 1),
			17 => array('code' => 'radio_multiple',		'name' => "&#xf192; ".__("Multiple Radio Buttons", 'lang_form'),	'desc' => __("To choose one alternative", 'lang_form'),										'result' => 1),
		);
	}

	function get_form_types_for_select($data = array())
	{
		global $wpdb;

		if(!isset($data['form_type_id'])){		$data['form_type_id'] = 0;}

		$arr_data = array();

		$result = $wpdb->get_results("SELECT formTypeID, formTypeCode, formTypeName, formTypeDesc, COUNT(formTypeID) AS formType_amount FROM ".$wpdb->base_prefix."form_type LEFT JOIN ".$wpdb->base_prefix."form2type USING (formTypeID) WHERE formTypePublic = 'yes' GROUP BY formTypeID ORDER BY formType_amount DESC, formTypeName ASC");

		if($wpdb->num_rows > 0)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";

			foreach($result as $r)
			{
				if($data['form_type_id'] != $r->formTypeID && in_array($r->formTypeCode, array('radio_button'))) //'checkbox', 1, 8
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

				else// if($data['form_type_id'] > 0 || $r->formTypeCode != 'custom_tag')
				{
					$arr_data[$r->formTypeID] = array($r->formTypeName, $r->formTypeDesc);
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
		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";

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

		$arr_data = array_sort(array('array' => $arr_data, 'on' => 1, 'keep_index' => true));

		return $arr_data;
	}

	function get_payment_amount_for_select()
	{
		list($result, $rows) = $this->get_form_type_info(array('query_type_id' => array(10, 12))); //'select', 'hidden_field'
		return $this->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));
	}

	function is_select_value_used($data)
	{
		global $wpdb;

		$out = false;

		if($data['form2type_id'] > 0 && $data['option_id'] != 0 && $data['option_id'] != '')
		{
			$wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $data['form2type_id'], $data['option_id']));

			$out = ($wpdb->num_rows > 0);
		}

		return $out;
	}

	function fetch_request()
	{
		$this->answer_id = check_var('intAnswerID');
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		if(isset($_GET['btnFormCopy']) && wp_verify_nonce($_REQUEST['_wpnonce_form_copy'], 'form_copy_'.$this->id))
		{
			$inserted = true;

			$result_temp = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$copy_fields = ", blogID, formAnswerURL, formEmail, formEmailNotify, formEmailNotifyPage, formEmailName, formEmailConfirm, formEmailConfirmPage, formShowAnswers, formMandatoryText, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formTermsPage, formPaymentMerchant, formPaymentCurrency, formPaymentCheck, formPaymentCost, formPaymentTax, formPaymentCallback"; //, formEmailConditions, formPaymentAmount (field IDs are not the same in this copied form)

				$strFormName = $this->get_form_name($this->id);

				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strFormName." (".__("copy", 'lang_form').")",
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form (formName, postID".$copy_fields.", formCreated, userID) (SELECT CONCAT(formName, ' (".__("copy", 'lang_form').")'), '%d'".$copy_fields.", NOW(), '%d' FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0')", $intPostID, get_current_user_id(), $this->id));
				$intFormID_new = $wpdb->insert_id;

				if($intFormID_new > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' ORDER BY form2TypeID DESC", $this->id));

					foreach($result as $r)
					{
						$intForm2TypeID = $r->form2TypeID;

						$copy_fields = "formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeRemember, form2TypeOrder";

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
			$this->prefix = $this->get_post_info()."_";

			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeCode, checkCode FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeID != '13' ORDER BY form2TypeOrder ASC", $this->id));

			foreach($result as $r)
			{
				$intForm2TypeID2 = $r->form2TypeID;
				$strFormTypeCode = $r->formTypeCode;
				$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";

				$strAnswerText = check_var($this->prefix.$intForm2TypeID2, $strCheckCode, true, '', true, 'post');

				if($strAnswerText != ''){}

				else if($strFormTypeCode == 'radio_button') //8
				{
					$strAnswerText_radio = isset($_POST["radio_".$intForm2TypeID2]) ? check_var($_POST["radio_".$intForm2TypeID2], 'int', false) : '';

					if($strAnswerText_radio != '')
					{
						$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $strAnswerText_radio));

						if($wpdb->num_rows == 0)
						{
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '%d', answerText = ''", $this->answer_id, $strAnswerText_radio));
						}
					}
				}

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

				/*else
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $this->answer_id, $intForm2TypeID2));
				}*/
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

		else if(isset($_GET['btnMessageResend']) && wp_verify_nonce($_REQUEST['_wpnonce_message_resend'], 'message_resend_'.$this->answer_id))
		{
			$resultAnswerEmail = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerType FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d' AND answerSent = '0' AND answerType != ''", $this->answer_id));

			if($wpdb->num_rows > 0)
			{
				$this->form_name = $this->get_post_info(array('select' => "post_title"));
				$this->prefix = $this->get_post_info()."_";

				$this->email_visitor = '';

				/*$result = $wpdb->get_results($wpdb->prepare("SELECT formEmail FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id)); //, formEmailConfirm, formEmailConfirmPage, formEmailConditions, formEmailNotify, formEmailNotifyPage, formEmailName

				foreach($result as $r)
				{
					//$this->email_confirm = $r->formEmailConfirm;
					//$this->email_confirm_page = $r->formEmailConfirmPage;
					$this->email_admin = $r->formEmail;
					//$this->email_conditions = $r->formEmailConditions;
					//$this->email_notify = $r->formEmailNotify;
					//$this->email_notify_page = $r->formEmailNotifyPage;
					//$this->email_subject = ($r->formEmailName != "" ? $r->formEmailName : $this->form_name);
				}*/

				$this->arr_email_content = array(
					'fields' => array(),
				);

				$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeText, checkCode, answerText FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) WHERE answerID = '%d' = '1' ORDER BY form2TypeOrder ASC", $this->answer_id));

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
						case 17:
						//case 'radio_multiple':
							$strAnswerText = $this->parse_select_info($strAnswerText);
						break;

						case 11:
						//case 'select_multiple':
						case 16:
						//case 'checkbox_multiple':
							$strAnswerText = $this->parse_multiple_info($strAnswerText, true);
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
							if($intFormTypeID2 == 3) //'input_field'
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
						/*case 'link_yes':	break;
						case 'link_no':		break;*/

						case 'replace_link':
							$this->send_to = $strAnswerEmail;
						break;

						/*case 'notify':	break;
						case 'confirm':		break;*/

						case 'product':
							$email_content_temp = apply_filters('filter_form_on_submit', array('obj_form' => $this)); //, 'answer_id' => $this->answer_id, 'mail_from' => $this->email_visitor, 'mail_admin' => $this->email_admin, 'mail_subject' => $this->email_subject, 'notify_page' => $this->email_notify_page, 'arr_mail_content' => $this->arr_email_content

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
	}

	function is_poll()
	{
		global $wpdb;

		$not_poll_content_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(form2TypeID) FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' AND formTypeID != '5' AND formTypeID != '8' LIMIT 0, 1", $this->id));

		return ($not_poll_content_amount == 0);
	}

	function check_if_duplicate()
	{
		global $wpdb;

		$dup_ip = false;

		if($this->is_poll())
		{
			$rowsIP = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2answer WHERE formID = '%d' AND answerIP = %s LIMIT 0, 1", $this->id, $_SERVER['REMOTE_ADDR']));

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

		$intFormID = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE blogID = '%d' AND (formEmailNotifyPage > 0 OR formEmailConfirmPage > 0)".$query_where, $wpdb->blogid));

		return ($intFormID > 0);
	}

	function check_if_has_payment()
	{
		global $wpdb;

		if(!isset($this->has_payment))
		{
			$this->has_payment = false;

			$result = $wpdb->get_results($wpdb->prepare("SELECT formPaymentProvider, formPaymentCost, formPaymentAmount FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

			foreach($result as $r)
			{
				$this->payment_provider = $r->formPaymentProvider;
				$this->payment_cost = $r->formPaymentCost;
				$this->payment_amount = $r->formPaymentAmount;

				$this->has_payment = $this->payment_provider > 0 && ($this->payment_cost > 0 || $this->payment_amount > 0);
			}
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

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_form')." --"
		);

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

		$intForm2TypeID = $wpdb->get_var($wpdb->prepare("SELECT form2TypeID FROM ".$wpdb->base_prefix."form2type".$query_join." WHERE formID = '%d'".$query_where, $this->id));

		return $intForm2TypeID > 0 ? true : false;
	}

	function set_meta($data)
	{
		global $wpdb;

		if(!isset($data['key'])){		$data['key'] = '';}
		if(!isset($data['value'])){		$data['value'] = '';}

		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_meta SET answerID = '%d', metaKey = %s, metaValue = %s", $data['id'], $data['key'], $data['value']));
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

	function preg_replace_label($matches)
	{
		$intForm2TypeID = $matches[1];

		return $this->page_content_data['content']['fields'][$intForm2TypeID]['label'];
	}

	function preg_replace_answer($matches)
	{
		$intForm2TypeID = $matches[1];

		return $this->page_content_data['content']['fields'][$intForm2TypeID]['value'];
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

				$mail_content = $this->render_mail_content(array('mail_to' => $this->page_content_data['mail_to'], 'template' => $mail_template)); //, 'array' => $this->page_content_data['content']
			}
		}

		if($this->mail_data['subject'] != '')
		{
			$this->render_mail_subject();
		}

		if($mail_content == '')
		{
			$mail_content = $this->render_mail_content(); //array('array' => $this->page_content_data['content'])
		}

		return $mail_content;
	}

	function has_email_field()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(formTypeID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_check USING (checkID) WHERE formID = '%d' AND formTypeID = '3' AND checkCode = 'email'", $this->id));
	}

	function get_icons_for_select()
	{
		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";

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
			$arr_data[''] = "-- ".__("Choose Here", 'lang_form')." --";
		}

		foreach($data['result'] as $r)
		{
			if(in_array($r->formTypeID, array(10, 11, 16, 17))) //'select', 'select_multiple', 'checkbox_multiple', 'radio_multiple'
			{
				list($strFormTypeText, $str_select) = explode(":", $r->formTypeText);
			}

			else if(in_array($r->formTypeID, array(13))) //'custom_tag'
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

		return $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, checkCode, checkPattern, formTypeText, formTypePlaceholder, formTypeDisplay, formTypeRequired, formTypeAutofocus, formTypeRemember, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE ".$query_where." GROUP BY ".$wpdb->base_prefix."form2type.form2TypeID ORDER BY form2TypeOrder ASC", $query_where_id));
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

	function get_spam_rules($data = array())
	{
		if(!isset($data['id'])){		$data['id'] = 0;}
		if(!isset($data['exclude'])){	$data['exclude'] = '';}
		if(!isset($data['type'])){		$data['type'] = '';}

		$arr_data = array(
			1 => array('exclude' => "select_multiple",	'text' => "contains_html",					'explain' => __("Contains HTML", 'lang_form')),
			2 => array('exclude' => "referer_url",		'text' => "/(http|https|ftp|ftps)\:/i",		'explain' => __("Link including http", 'lang_form')),
			3 => array('exclude' => '',					'text' => "/([qm]){5}/",					'explain' => __("Question marks", 'lang_form')),
			4 => array('exclude' => '',					'text' => "/(bit\.ly)/",					'explain' => __("Shortening links", 'lang_form')),
			5 => array('exclude' => '',					'text' => "/([bs][url[bs]=)/",				'explain' => __("URL shortcodes", 'lang_form')),
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
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmPage, formEmail, formEmailConditions, formEmailNotify, formEmailNotifyPage, formEmailName FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

		foreach($result as $r)
		{
			$this->email_confirm = $r->formEmailConfirm;
			$this->email_confirm_page = $r->formEmailConfirmPage;
			$this->email_admin = $r->formEmail;
			$this->email_conditions = $r->formEmailConditions;
			$this->email_notify = $r->formEmailNotify;
			$this->email_notify_page = $r->formEmailNotifyPage;
			$this->email_subject = ($r->formEmailName != '' ? $r->formEmailName : $this->form_name);
		}

		$this->page_content_data = array(
			'subject' => $this->email_subject,
			'content' => $this->arr_email_content,
		);

		if($this->email_conditions != '')
		{
			foreach(explode("\n", $this->email_conditions) as $arr_condition)
			{
				list($key, $value, $email) = explode("|", $arr_condition, 3);

				if(substr($key, 0, strlen($this->prefix)) == $this->prefix)
				{
					$key = str_replace($this->prefix, "", $key);
				}

				if(isset($_REQUEST[$this->prefix.$key]) && check_var($this->prefix.$key) == $value)
				//if($this->page_content_data['content']['fields'][$key]['value'] == $value)
				{
					$this->email_admin = $email;
				}
			}
		}

		if(isset($this->send_to) && $this->send_to != '')
		{
			$this->mail_data = array(
				'type' => 'replace_link',
				'to' => $this->send_to,
				'subject' => $this->page_content_data['subject'],
				'content' => '',
			);

			if($this->email_visitor != '')
			{
				$this->mail_data['headers'] = "From: ".$this->email_visitor." <".$this->email_visitor.">\r\n";
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

			if($this->email_visitor != '')
			{
				$this->mail_data['headers'] = "From: ".$this->email_visitor." <".$this->email_visitor.">\r\n";
			}

			$this->page_content_data['mail_to'] = $this->mail_data['to'];
			$this->page_content_data['page_id'] = $this->email_notify_page;

			$this->mail_data['content'] = $this->get_page_content_for_email();

			$this->send_transactional_email();
		}

		if($this->email_confirm == 1 && isset($this->email_visitor) && $this->email_visitor != '')
		{
			$this->mail_data = array(
				'type' => 'confirm',
				'to' => $this->email_visitor,
				'subject' => $this->page_content_data['subject'],
				'content' => '',
			);

			if($this->email_admin != '')
			{
				if(strpos($this->email_admin, "<"))
				{
					$this->mail_data['headers'] = "From: ".$this->email_admin."\r\n";
				}

				else if(strpos($this->email_admin, ","))
				{
					$arr_email_admin = explode(",", $this->email_admin);

					$email_admin = trim($arr_email_admin[0]);

					$this->mail_data['headers'] = "From: ".$email_admin." <".$email_admin.">\r\n";
				}

				else
				{
					$this->mail_data['headers'] = "From: ".$this->email_admin." <".$this->email_admin.">\r\n";
				}
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
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer_email SET answerID = '%d', answerEmail = %s, answerType = %s, answerSent = '%d'", $this->answer_id, $this->mail_data['to'], $this->mail_data['type'], $sent));
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

	function allow_save_ip()
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT formSaveIP FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));
	}

	function get_mandatory_text()
	{
		global $wpdb;

		$out = $wpdb->get_results($wpdb->prepare("SELECT formMandatoryText FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id));

		if($out == '')
		{
			$out = __("Please, enter all required fields", 'lang_form');
		}

		return $out;
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

		$this->form_name = $this->get_post_info(array('select' => "post_title"));
		$this->prefix = $this->get_post_info()."_";

		/*$result = $wpdb->get_results($wpdb->prepare("SELECT formPaymentAmount FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $this->id)); //formSaveIP, formMandatoryText, formEmailConfirm, formEmailConfirmPage, formEmail, formEmailConditions, formEmailNotify, formEmailNotifyPage, formEmailName, formPaymentProvider, formPaymentCost, 

		foreach($result as $r)
		{
			//$strFormSaveIP = $r->formSaveIP;
			//$this->email_confirm = $r->formEmailConfirm;
			//$this->email_confirm_page = $r->formEmailConfirmPage;
			//$this->email_admin = $r->formEmail;
			//$this->email_conditions = $r->formEmailConditions;
			//$this->email_notify = $r->formEmailNotify;
			//$this->email_notify_page = $r->formEmailNotifyPage;
			//$this->email_subject = ($r->formEmailName != "" ? $r->formEmailName : $this->form_name);
			//$strFormMandatoryText = $r->formMandatoryText;
			//$intFormPaymentProvider = $r->formPaymentProvider;
			//$intFormPaymentCost = $r->formPaymentCost;
			$intFormPaymentAmount = $r->formPaymentAmount;
		}*/

		$dblQueryPaymentAmount_value = 0;

		if($this->dup_ip == true)
		{
			$this->is_sent = true;
		}

		else
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeID, formTypeCode, formTypeText, checkCode, formTypeRequired FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeDisplay = '1' AND formTypeResult = '1' ORDER BY form2TypeOrder ASC", $this->id));

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

					//case 12:
					case 'hidden_field':
						$strAnswerText_send = '';
					break;

					//case 10:
					case 'select':
					//case 17:
					case 'radio_multiple':
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

						$strAnswerText_send = $this->parse_multiple_info($strAnswerText, true);
						$strAnswerText = $this->parse_multiple_info($strAnswerText, false);
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
					switch($strFormTypeCode)
					{
						//case 12:
						case 'hidden_field':
							unset($this->arr_email_content['fields'][$intForm2TypeID2]);
						break;

						default:
							$this->arr_email_content['fields'][$intForm2TypeID2]['label'] = $this->label;
						break;
					}
				}

				if($strAnswerText != '')
				{
					if($this->check_if_has_payment() && $this->payment_amount == $intForm2TypeID2)
					{
						$dblQueryPaymentAmount_value = $strAnswerText;
					}

					$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = %s", $intForm2TypeID2, $strAnswerText);

					if($strAnswerText_send != '')
					{
						$this->arr_email_content['fields'][$intForm2TypeID2]['value'] = $strAnswerText_send;
					}
				}

				else if($strFormTypeCode == 'radio_button') //8
				{
					$strAnswerText_radio = isset($_POST["radio_".$intForm2TypeID2]) ? check_var($_POST["radio_".$intForm2TypeID2], 'int', false) : '';

					if($strAnswerText_radio != '')
					{
						$this->arr_answer_queries[] = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '[answer_id]', form2TypeID = '%d', answerText = ''", $strAnswerText_radio);

						$strFormTypeText_temp = $wpdb->get_var($wpdb->prepare("SELECT formTypeText FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $strAnswerText_radio));

						if(!isset($this->arr_email_content['fields'][$strAnswerText_radio]))
						{
							$this->arr_email_content['fields'][$strAnswerText_radio] = array();
						}

						$this->arr_email_content['fields'][$strAnswerText_radio]['value'] = "x";
					}
				}

				else if($intFormTypeRequired == true && !in_array($strFormTypeCode, array('text', 'space', 'referer_url')) && $error_text == '') //5, 6, 9
				{
					$error_text = $this->get_mandatory_text()." (".$this->label.")";
				}
			}
		}

		if($error_text == '' && $this->is_sent == false && count($this->arr_answer_queries) > 0)
		{
			if(check_var($this->prefix.'check') != '' && in_array('honeypot', $setting_form_spam))
			{
				$this->is_spam = true;
				$this->is_spam_id = 7;
			}

			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2answer SET formID = '%d', answerIP = %s, answerSpam = '%d', spamID = '%d', answerCreated = NOW()", $this->id, ($this->allow_save_ip() == 'yes' ? $_SERVER['REMOTE_ADDR'] : ''), $this->is_spam, $this->is_spam_id));
			$this->answer_id = $wpdb->insert_id;

			$email_content_temp = apply_filters('filter_form_on_submit', array('obj_form' => $this)); //, 'answer_id' => $this->answer_id, 'mail_from' => $this->email_visitor, 'mail_admin' => $this->email_admin, 'mail_subject' => $this->email_subject, 'notify_page' => $this->email_notify_page, 'arr_mail_content' => $this->arr_email_content

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
							'label' => __("Sent From", 'lang_form'),
							'value' => remove_protocol(array('url' => $_SERVER['HTTP_REFERER'], 'clean' => true, 'trim' => true))
						);
					}

					$this->process_transactional_emails();

					if(get_current_user_id() > 0)
					{
						$this->set_meta(array('id' => $this->answer_id, 'key' => 'user_id', 'value' => get_current_user_id()));
					}

					//if($intFormPaymentProvider > 0 && ($intFormPaymentCost > 0 || $dblQueryPaymentAmount_value > 0))
					if($this->check_if_has_payment())
					{
						do_log("Payment Check: ".$dblQueryPaymentAmount_value." == ".$this->page_content_data['content']['fields'][$this->payment_amount]['value']);

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form_answer SET answerID = '%d', form2TypeID = '0', answerText = %s", $this->answer_id, "101: ".__("Sent to processing", 'lang_form')));

						$intFormPaymentTest = (isset($_POST['intFormPaymentTest']) && (IS_ADMIN || isset($_GET['make_test_payment'])));

						if($intFormPaymentTest == true)
						{
							$this->set_meta(array('id' => $this->answer_id, 'key' => 'test_payment', 'value' => get_current_user_id()));
						}

						$obj_payment = new mf_form_payment($this->id);
						$out .= $obj_payment->process_passthru(array('cost' => $this->payment_cost, 'amount' => $dblQueryPaymentAmount_value, 'orderid' => $this->answer_id, 'test' => $intFormPaymentTest, 'email_visitor' => $this->email_visitor));
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

	function get_form($data = array())
	{
		global $wpdb, $wp_query, $done_text, $error_text;

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

			$this->prefix = $this->get_post_info()."_";

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

			else if($dteFormDeadline > DEFAULT_DATE && $dteFormDeadline < date("Y-m-d"))
			{
				$error_text = __("This form is not open for submissions anymore", 'lang_form');

				$out .= get_notification();
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

							$obj_form_output = new mf_form_output(array('id' => $this->id, 'result' => $r, 'in_edit_mode' => $this->edit_mode, 'query_prefix' => $this->prefix));

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
							$out .= show_textfield(array('name' => $this->prefix.'check', 'text' => __("This field should not visible", 'lang_form'), 'xtra_class' => "form_check", 'xtra' => " autocomplete='off'"))
							.apply_filters('filter_form_after_fields', '')
							."<div class='form_button_container'>
								<div class='form_button'>"
									.show_button(array('name' => "btnFormSubmit", 'text' => $strFormButtonSymbol.$strFormButtonText))
									.show_button(array('type' => "button", 'name' => "btnFormClear", 'text' => __("Clear", 'lang_form'), 'class' => "button-secondary hide"));

									//if($intFormPaymentProvider > 0 && (IS_ADMIN || isset($_GET['make_test_payment'])))
									if($this->check_if_has_payment() && (IS_ADMIN || isset($_GET['make_test_payment'])))
									{
										$out .= show_checkbox(array('name' => "intFormPaymentTest", 'text' => __("Perform test payment", 'lang_form'), 'value' => 1))
										.apply_filters('filter_form_test_payment', '');
									}

									if(isset($this->send_to) && $this->send_to != '')
									{
										$out .= input_hidden(array('name' => 'email_encrypted', 'value' => hash('sha512', $this->send_to)));
									}

								$out .= "</div>"
								.input_hidden(array('name' => 'intFormID', 'value' => $this->id))
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

			if(isset($_POST['btnFormSubmit']) && $this->is_correct_form($data))
			{
				$out .= $this->process_submit();
			}

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
					mf_enqueue_script('jquery-flot', plugins_url()."/mf_base/include/jquery.flot.min.0.7.js", '0.7'); //Should be placed in admin_init
					mf_enqueue_script('jquery-flot-pie', plugins_url()."/mf_base/include/jquery.flot.pie.min.js", '1.1');

					$js_out = $order_temp = "";
					$arr_data_pie = array();

					$i = 0;

					foreach($resultPie as $r)
					{
						$intForm2TypeID = $r->form2TypeID;
						$intFormTypeID = $r->formTypeID;
						$strFormTypeText = $r->formTypeText;
						$strForm2TypeOrder = $r->form2TypeOrder;

						switch($intFormTypeID)
						{
							case 1:
							//case 'checkbox':
								if($order_temp != '' && $strForm2TypeOrder != ($order_temp + 1))
								{
									$i++;
								}
							break;

							case 8:
							//case 'radio_button':
								if($order_temp != '' && $strForm2TypeOrder != ($order_temp + 1))
								{
									$i++;
								}
							break;

							case 10:
							//case 'select':
								$i++;
							break;
						}

						if(!isset($arr_data_pie[$i]))
						{
							$arr_data_pie[$i] = array(
								'data' => '',
							);
						}

						$order_temp = $strForm2TypeOrder;

						switch($intFormTypeID)
						{
							case 1:
							//case 'checkbox':
								$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND formTypeID = '%d' AND form2TypeID = '%d'", $this->id, $intFormTypeID, $intForm2TypeID));

								$arr_data_pie[$i]['data'] .= ($arr_data_pie[$i]['data'] != '' ? "," : "")."{label: '".shorten_text(array('string' => $strFormTypeText, 'limit' => 20))."', data: ".$intAnswerCount."}";
							break;

							case 8:
							//case 'radio_button':
								$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_answer USING (form2TypeID) INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE ".$wpdb->base_prefix."form2type.formID = '%d' AND answerSpam = '0' AND formTypeID = '%d' AND form2TypeID = '%d'", $this->id, $intFormTypeID, $intForm2TypeID));

								$arr_data_pie[$i]['data'] .= ($arr_data_pie[$i]['data'] != '' ? "," : "")."{label: '".shorten_text(array('string' => $strFormTypeText, 'limit' => 20))."', data: ".$intAnswerCount."}";
							break;

							case 10:
							//case 'select':
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
											$arr_data_pie[$i]['data'] .= ($arr_data_pie[$i]['data'] != '' ? "," : "")."{label: '".shorten_text(array('string' => $arr_option[1], 'limit' => 20))."', data: ".$intAnswerCount."}";
										}
									}
								}
							break;
						}

						$arr_data_pie[$i]['label'] = $strFormTypeText;
					}

					$out .= "<div class='flot_wrapper'>";

						foreach($arr_data_pie as $key => $arr_value)
						{
							$out .= "<div>"
								."<h3>".$arr_value['label']."</h3>"
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
		$this->base_form_url = get_site_url().$_SERVER['REQUEST_URI'].(preg_match("/\?/", $_SERVER['REQUEST_URI']) ? "&" : "?");
		$this->base_callback_url = get_site_url().$_SERVER['REQUEST_URI'].(preg_match("/\?/", $_SERVER['REQUEST_URI']) ? "&" : "?");

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

			$obj_form = new mf_form($this->form_id);

			$this->prefix = $obj_form->get_post_info()."_";

			//The callback must have a public URL
			if(is_admin())
			{
				$this->base_callback_url = get_permalink($obj_form->post_id)."?";
			}
		}
	}

	function process_passthru($data)
	{
		global $wpdb;

		$this->cost = $data['cost'];
		$this->cost_total = $this->amount = intval($data['amount']) > 0 ? $data['amount'] : 1;

		$this->orderid = $data['orderid'];
		$this->test = $data['test'];
		$this->email_visitor = $data['email_visitor'];

		if($this->cost > 0)
		{
			$this->cost_total *= $this->cost;
		}

		else
		{
			$this->cost = $this->amount;
			$this->amount = 1;
		}

		$this->tax = $this->tax_total = 0;

		if($this->payment_tax_rate > 0)
		{
			$this->tax = ($this->cost / ($this->payment_tax_rate / 100));
			$this->tax_total = ($this->cost_total / ($this->payment_tax_rate / 100));
		}

		$out = apply_filters('form_process_passthru', '', $this);

		if($this->provider > 0 && $out == '')
		{
			do_log(sprintf(__("A provider was set (%s) to passthru but there seams to be no provider extensions installed", 'lang_form'), $this->provider));
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
					$paid = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d' AND form2TypeID = '%d'", $this->answer_id, $this->payment_amount));

					call_user_func($this->payment_callback, array('paid' => $paid, 'answer_id' => $this->answer_id));
				}

				else
				{
					do_log("Function ".$this->payment_callback." not callable");
				}
			}

			else
			{
				do_log("Function ".$this->payment_callback." does not exist");
			}
		}

		/*else
		{
			do_log("No callback");
		}*/
	}

	function confirm_cancel()
	{
		global $wpdb, $error_text;

		$out = "";

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText LIKE %s", "103: ".__("User canceled", 'lang_form'), $this->answer_id, '10%'));

		$error_text = __("Your payment was cancelled", 'lang_form');

		$out .= get_notification();

		return $out;
	}

	function confirm_accept($is_verified = false)
	{
		global $wpdb, $wp_query, $done_text;

		$out = "";

		if($this->answer_id > 0)
		{
			if($is_verified)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form_answer SET answerText = %s WHERE answerID = '%d' AND form2TypeID = '0' AND answerText NOT LIKE %s", "116: ".__("Paid & Verified", 'lang_form'), $this->answer_id, '116:%'));

				if($wpdb->rows_affected > 0)
				{
					$this->run_confirm_callback();
				}

				else
				{
					do_log("Already Run");
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
					echo "<i class='fa fa-spinner fa-spin fa-3x'></i>";

					$strFormAnswerURL = get_permalink($intFormAnswerURL);

					mf_redirect($strFormAnswerURL);
				}

				/*else
				{
					do_log("Redirect not verified");
					//header("Status: 400 Bad Request");
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
			header("Status: 400 Bad Request");
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

		$out = apply_filters('form_process_callback', "<p>".__("Processing", 'lang_form')."&hellip;</p>", $this);

		if($this->provider > 0 && $out == '')
		{
			do_log(sprintf(__("A provider was set (%s) to callback but there seams to be no provider extensions installed", 'lang_form'), $this->provider));
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
				//case 17:
				case 'radio_multiple':
					list($obj_form->label, $str_select) = explode(":", $obj_form->label);
				break;
			}

			$this_row[] = stripslashes(strip_tags($obj_form->label));
		}

		$this_row[] = __("Created", 'lang_form');

		$this->data[] = $this_row;

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, formID, answerCreated FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->base_prefix."form_answer USING (answerID) WHERE formID = '%d' AND answerSpam = '0' GROUP BY answerID ORDER BY answerCreated DESC", $this->type)); //, answerIP

		foreach($result as $r)
		{
			$intAnswerID = $r->answerID;
			$intFormID = $r->formID;
			$strAnswerCreated = $r->answerCreated;
			//$strAnswerIP = $r->answerIP;

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
						//case 17:
						case 'radio_multiple':
							$strAnswerText = $obj_form->parse_select_info($strAnswerText);
						break;

						//case 11:
						case 'select_multiple':
						//case 16:
						case 'checkbox_multiple':
							$strAnswerText = $obj_form->parse_multiple_info($strAnswerText, true);
						break;

						//case 15:
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

			$this_row[] = $strAnswerCreated;

			$this->data[] = $this_row;
		}
	}
}

class mf_form_table extends mf_list_table
{
	function set_default()
	{
		$this->post_type = "mf_form";

		$this->orderby_default = "post_modified";
		$this->orderby_default_order = "DESC";

		/*$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_form';*/
	}

	function init_fetch()
	{
		global $wpdb;

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
			'spam' => __("Spam", 'lang_form'),
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

				$post_edit_url = IS_ADMIN ? admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id) : "#";

				$actions = array();

				if($post_status != 'trash')
				{
					if(IS_ADMIN)
					{
						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_form')."</a>";

						$query_answers = $obj_form->get_answer_amount(array('form_id' => $obj_form->id));

						if($query_answers == 0)
						{
							$actions['delete'] = "<a href='#delete/form/".$obj_form->id."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
						}
					}

					$actions['copy'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnFormCopy&intFormID=".$obj_form->id), 'form_copy_'.$obj_form->id, '_wpnonce_form_copy')."'>".__("Copy", 'lang_form')."</a>";

					if($post_status == 'publish' && $obj_form->id > 0)
					{
						$shortcode = "[mf_form id=".$obj_form->id."]";

						$result = get_pages_from_shortcode($shortcode);

						if(count($result) > 0)
						{
							foreach($result as $post_id_temp)
							{
								$actions['edit_page'] = "<a href='".admin_url("post.php?post=".$post_id_temp."&action=edit")."'>".__("Edit Page", 'lang_form')."</a>";
								$actions['view_page'] = "<a href='".get_permalink($post_id_temp)."'>".__("View", 'lang_form')."</a>";
							}
						}

						else
						{
							if($obj_form->get_form_status() == "publish")
							{
								$post_url = get_permalink($post_id);

								if($post_url != '')
								{
									$actions['view'] = "<a href='".$post_url."'>".__("View", 'lang_form')."</a>";
								}
							}

							//$actions['add_post'] = "<a href='".admin_url("post-new.php?post_title=".$strFormName."&content=".$shortcode)."'>".__("Add New Post", 'lang_form')."</a>";
							$actions['add_page'] = "<a href='".admin_url("post-new.php?post_type=page&post_title=".$strFormName."&content=".$shortcode)."'>".__("Add New Page", 'lang_form')."</a>";
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

				$result = $wpdb->get_results($wpdb->prepare("SELECT formEmail, formEmailConditions, formEmailNotifyPage, formEmailConfirm, formEmailConfirmPage, formPaymentProvider FROM ".$wpdb->base_prefix."form WHERE formID = '%d'", $obj_form->id));

				foreach($result as $r)
				{
					$strFormEmail = $r->formEmail;
					$strFormEmailConditions = $r->formEmailConditions;
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

					if($strFormEmailConditions != '')
					{
						$out .= "<i class='fa fa-lg fa-send grey' title='".__("Message will be sent to different e-mails because there are conditions", 'lang_form')."'></i> ";
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

				if($obj_form->is_form_field_type_used(array('display' => '0')))
				{
					$out .= "<i class='fa fa-lg fa-eye-slash grey' title='".__("There are hidden fields", 'lang_form')."'></i> ";
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
					$query_answers = $obj_form->get_answer_amount(array('form_id' => $obj_form->id));

					if($query_answers > 0)
					{
						$count_message = get_count_answer_message(array('form_id' => $obj_form->id));

						$actions = array(
							'show_answers' => "<a href='".admin_url("admin.php?page=mf_form/answer/index.php&intFormID=".$obj_form->id)."'>".__("View", 'lang_form')."</a>",
							'export_csv' => "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnExportRun&intExportType=".$obj_form->id."&strExportFormat=csv"), 'export_run', '_wpnonce_export_run')."'>".__("CSV", 'lang_form')."</a>",
						);

						if(is_plugin_active("mf_phpexcel/index.php"))
						{
							$actions['export_xls'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/list/index.php&btnExportRun&intExportType=".$obj_form->id."&strExportFormat=xls"), 'export_run', '_wpnonce_export_run')."'>".__("XLS", 'lang_form')."</a>";
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
		$this->orderby_default_order = "DESC";

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
				'0' => __("All", 'lang_form'),
				'1' => __("Spam", 'lang_form')
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeCode, formTypeText, form2TypeID FROM ".$wpdb->base_prefix."form2type INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' ORDER BY form2TypeOrder ASC", $obj_form->id));

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
					list($obj_form->label, $str_select) = explode(":", $obj_form->label);

					$label_limit = 10;
				break;

				default:
					$label_limit = 20;
				break;
			}

			$arr_columns[$intForm2TypeID2] = shorten_text(array('string' => $obj_form->label, 'limit' => $label_limit));
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

					$actions['unspam'] = "<a href='".wp_nonce_url(admin_url("admin.php?page=mf_form/answer/index.php&btnAnswerApprove&intFormID=".$obj_form->id."&intAnswerID=".$intAnswerID), 'answer_approve_'.$intAnswerID, '_wpnonce_answer_approve')."' rel='confirm'>".__("Approve", 'lang_form')."</a>";
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

				if($strAnswerText_temp != '')
				{
					list($payment_number, $rest) = explode(":", $strAnswerText_temp);

					switch($payment_number)
					{
						case 101:
						case 102:
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
					$actions['meta_data'] = "<br><strong>".__("Meta Data", 'lang_form')."</strong><br>";

					foreach($result as $r)
					{
						switch($r->metaKey)
						{
							case 'test_payment':
							case 'user_id':
								$actions['meta_data'] .= $r->metaKey.": ".get_user_info(array('id' => $r->metaValue))."<br>";
							break;

							default:
								$actions['meta_data'] .= $r->metaKey.": ".$r->metaValue."<br>";
							break;
						}
					}
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

					$resultText = $wpdb->get_results($wpdb->prepare("SELECT form2TypeID, formTypeCode, formTypeText, checkCode FROM ".$wpdb->base_prefix."form_check RIGHT JOIN ".$wpdb->base_prefix."form2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."form_type USING (formTypeID) WHERE formID = '%d' AND formTypeResult = '1' AND form2TypeID = '%d' LIMIT 0, 1", $obj_form->id, $column_name));

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
									if(strlen($strAnswerText) > 10)
									{
										$strAnswerText = format_date($strAnswerText);
									}

									else
									{
										$strAnswerText = $strAnswerText;
									}
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
							$out .= stripslashes(stripslashes($strAnswerText));
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

class mf_form_output
{
	function __construct($data)
	{
		$this->id = isset($data['id']) ? $data['id'] : 0;

		$this->row = $data['result'];
		$this->query_prefix = $data['query_prefix'];

		$this->output = "";

		$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = $this->show_template_info = false;

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
		if($this->row->formTypeFetchFrom != '')
		{
			if(substr($this->row->formTypeFetchFrom, 0, 1) == "[" && get_current_user_id() > 0)
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

			else if(isset($field_data['value']) && $field_data['value'] == '')
			{
				$field_data['value'] = check_var($this->row->formTypeFetchFrom);
			}
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

	function get_form_fields() //$data = array()
	{
		global $intFormTypeID2_temp, $intForm2TypeID2_temp;

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
			//case 1:
			case 'checkbox':
				$is_first_checkbox = false;

				if($this->row->formTypeID != $intFormTypeID2_temp)
				{
					$intForm2TypeID2_temp = $this->row->form2TypeID;

					$is_first_checkbox = true;
				}

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->row->formTypeText;
				//}

				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['value'] = 1;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->formTypeClass.($is_first_checkbox ? ($this->row->formTypeClass != '' ? " " : "")."clear" : "");

				$this->output .= show_checkbox($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			//case 2:
			case 'range':
				$arr_content = explode("|", $this->row->formTypeText);

				if($this->answer_text == '' && isset($arr_content[3]))
				{
					$this->answer_text = $arr_content[3];
				}

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $arr_content[0]." (<span>".$this->answer_text."</span>)";
				//}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = "min='".$arr_content[1]."' max='".$arr_content[2]."'".($this->row->formTypeAutofocus ? " autofocus" : "");
				$field_data['xtra_class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['type'] = "range";

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = true;
			break;

			//case 7:
			case 'datepicker':
				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->row->formTypeText;
				//}

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

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->row->formTypeText;
				//}

				$field_data['value'] = $this->row->form2TypeID;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->formTypeClass.($is_first_radio ? ($this->row->formTypeClass != '' ? " " : "")."clear" : "");

				$this->output .= show_radio_input($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			//case 17:
			case 'radio_multiple':
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->label;
				//}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_form_alternatives($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			//case 10:
			case 'select':
				if($this->row->formTypeActionShow > 0)
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_action";
					$field_data['xtra'] = "data-equals='".$this->row->formTypeActionEquals."' data-show='".$this->query_prefix.$this->row->formTypeActionShow."'";
				}

				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->label;
				//}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_remember = $this->show_copy = $this->show_template_info = true;
			break;

			//case 11:
			case 'select_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->label;
				//}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;
				$field_data['xtra'] = "class='multiselect'";

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_copy = true;
			break;

			//case 16:
			case 'checkbox_multiple':
				$field_data['name'] .= "[]";
				$field_data['data'] = $this->get_options_for_select($this->row->formTypeText);

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->label;
				//}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_form_alternatives($field_data);

				$this->show_required = true;
			break;

			//case 3:
			case 'input_field':
				if($this->row->checkCode == "zip")
				{
					$this->row->formTypeClass .= ($this->row->formTypeClass != '' ? " " : "")."form_zipcode";
				}

				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->row->formTypeText;
				//}

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

			//case 4:
			case 'textarea':
				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->row->formTypeText;
				//}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['xtra'] = ($this->row->formTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->formTypeClass.($this->row->formTypeRemember ? " remember" : "");
				$field_data['placeholder'] = $this->row->formTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = $this->show_copy = $this->show_template_info = true;
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

					$this->show_copy = $this->show_template_info = true;
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
				/*if($data['show_label'] == true)
				{*/
					$field_data['text'] = $this->row->formTypeText;
				//}

				$field_data['required'] = $this->row->formTypeRequired;
				$field_data['class'] = $this->row->formTypeClass;

				$this->output .= show_file_field($field_data);

				$this->show_required = $this->show_copy = true;
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
			$row_settings = show_checkbox(array('name' => "display_".$this->row->form2TypeID, 'text' => __("Display", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeDisplay, 'xtra' => "class='ajax_checkbox' rel='display/type/".$this->row->form2TypeID."'"));

			if($this->show_required == true)
			{
				$row_settings .= show_checkbox(array('name' => "require_".$this->row->form2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRequired, 'xtra' => "class='ajax_checkbox' rel='require/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_autofocus == true)
			{
				$row_settings .= show_checkbox(array('name' => "autofocus_".$this->row->form2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeAutofocus, 'xtra' => "class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->form2TypeID."'"));
			}

			if($this->show_remember == true)
			{
				$row_settings .= show_checkbox(array('name' => "remember_".$this->row->form2TypeID, 'text' => __("Remember Answer", 'lang_form'), 'value' => 1, 'compare' => $this->row->formTypeRemember, 'xtra' => "class='ajax_checkbox remember' rel='remember/type/".$this->row->form2TypeID."'"));
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

			$out .= "<mf-form-row id='type_".$this->row->form2TypeID."' class='flex_flow".($data['form2type_id'] == $this->row->form2TypeID ? " active" : "").($this->row->formTypeDisplay == 0 ? " hide_publicly" : "")."'>"
				.$this->output;

				if($this->row->formTypeID != 14) //custom_tag_end
				{
					$out .= "<div class='row_icons'>";

						if($row_settings != '')
						{
							$out .= "<i class='fa fa-info-circle blue'></i>";
						}

						$out .= "<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$this->id."&intForm2TypeID=".$this->row->form2TypeID)."' title='".__("Edit", 'lang_form')."'><i class='fa fa-pencil-square-o'></i></a>
					</div>";

					if($row_settings != '')
					{
						$out .= "<div class='row_settings'>".$row_settings."</div>";
					}
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

		$instance['form_heading'] = sanitize_text_field($new_instance['form_heading']);
		$instance['form_id'] = sanitize_text_field($new_instance['form_id']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$obj_form = new mf_form();

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('form_heading'), 'text' => __("Heading", 'lang_form'), 'value' => $instance['form_heading']))
			.show_select(array('data' => $obj_form->get_for_select(array('force_has_page' => false)), 'name' => $this->get_field_name('form_id'), 'value' => $instance['form_id']))
		."</div>";
	}
}