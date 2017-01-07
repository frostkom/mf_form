<?php

class mf_form
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : check_var('intQueryID');

		$this->post_status = "";
		$this->query2type_id = $this->post_id = 0;

		$this->meta_prefix = "mf_form_";

		if($this->id > 0)
		{
			$this->get_post_id();
		}
	}

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

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		if(isset($_GET['btnFormCopy']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_copy'))
		{
			$inserted = true;

			$result_temp = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$copy_fields = ", blogID, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailNotifyPage, queryEmailName, queryEmailCheckConfirm, queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryMandatoryText, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentCurrency, queryPaymentCheck, queryPaymentAmount";

				$strFormName = $this->get_form_name($this->id);

				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strFormName,
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query (queryName, postID".$copy_fields.", queryCreated, userID) (SELECT CONCAT(queryName, ' (".__("copy", 'lang_form').")'), '%d'".$copy_fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0')", $intPostID, $this->id));
				$intQueryID_new = $wpdb->insert_id;

				if($intQueryID_new > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeID DESC", $this->id));

					foreach($result as $r)
					{
						$intQuery2TypeID = $r->query2TypeID;

						$copy_fields = "queryTypeID, queryTypeText, queryTypePlaceholder, checkID, queryTypeTag, queryTypeClass, queryTypeFetchFrom, queryTypeActionEquals, queryTypeActionShow, queryTypeRequired, queryTypeAutofocus, queryTypeRemember, query2TypeOrder";

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type (queryID, ".$copy_fields.", query2TypeCreated, userID) (SELECT %d, ".$copy_fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d')", $intQueryID_new, $intQuery2TypeID));

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

			$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, checkCode FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeID != '13' ORDER BY query2TypeOrder ASC", $this->id));

			$strAnswerIP = $_SERVER['REMOTE_ADDR'];

			foreach($result as $r)
			{
				$intQuery2TypeID2 = $r->query2TypeID;
				$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";

				$var = check_var($strFormPrefix.$intQuery2TypeID2, $strCheckCode, true, '', true, 'post');

				if($var != '')
				{
					$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $intQuery2TypeID2));
					$rowsCheck = $wpdb->num_rows;

					if($rowsCheck > 0)
					{
						$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $this->answer_id, $intQuery2TypeID2, $var));
						$rowsCheck = $wpdb->num_rows;

						if($rowsCheck == 0)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '%d'", $var, $this->answer_id, $intQuery2TypeID2));
						}
					}

					else
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '%d', answerText = %s", $this->answer_id, $intQuery2TypeID2, $var));
					}
				}
			}

			if(!isset($error_text) || $error_text == '')
			{
				mf_redirect("?page=mf_form/answer/index.php&intQueryID=".$this->id);
			}
		}

		$obj_export = new mf_form_export();

		return $out;
	}

	function count_forms($data = array())
	{
		global $wpdb;

		if(!isset($data['post_status'])){	$data['post_status'] = "";}

		$wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_form'".($data['post_status'] != '' ? " AND post_status = '".esc_sql($data['post_status'])."'" : ""));

		return $wpdb->num_rows;
	}

	function is_poll()
	{
		global $wpdb;

		$not_poll_content_amount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(query2TypeID) FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND queryTypeID != '5' AND queryTypeID != '8' LIMIT 0, 1", $this->id));

		return ($not_poll_content_amount == 0 ? true : false);
	}

	function check_if_duplicate()
	{
		global $wpdb;

		$strAnswerIP = $_SERVER['REMOTE_ADDR'];

		$dup_ip = false;

		if($this->is_poll())
		{
			$rowsIP = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2answer WHERE queryID = '%d' AND answerIP = %s LIMIT 0, 1", $this->id, $strAnswerIP));

			if($rowsIP > 0)
			{
				$dup_ip = true;
			}
		}

		else
		{
			$rowsIP = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2answer WHERE queryID = '%d' AND answerIP = %s AND answerCreated > DATE_SUB(NOW(), INTERVAL 30 SECOND) LIMIT 0, 1", $this->id, $strAnswerIP));

			if($rowsIP > 0)
			{
				$dup_ip = true;
			}
		}

		return $dup_ip;
	}

	function is_correct_form($data)
	{
		$email_encrypted = check_var('email_encrypted', 'char');

		$log_text = "";

		if($data['query_id'] != $this->id)
		{
			$log_text = $data['query_id']." != ".$this->id;
		}

		if(isset($data['send_to']) && $data['send_to'] != '' && $email_encrypted != hash('sha512', $data['send_to']))
		{
			$log_text = $email_encrypted." != ".hash('sha512', $data['send_to']);
		}

		if($log_text != '')
		{
			do_log(__("The form wasn't sent correctly", 'lang_form'))." (".$log_text.")";

			return false;
		}

		else
		{
			return true;
		}
	}

	function contains_html($string)
	{
		$string_decoded = htmlspecialchars_decode($string);

		if($string != strip_tags($string) || $string_decoded != strip_tags($string_decoded))
        {
			return true;
		}

		else
		{
			return false;
		}
	}

	function contains_urls($string)
	{
		if(preg_match("/(http|https|ftp|ftps)\:/i", $string))
        {
			return true;
		}

		else
		{
			return false;
		}
	}

	function has_template()
	{
		global $wpdb;

		$query_where = "";

		if($this->id > 0)
		{
			$query_where .= " AND queryID = '".$this->id."'";
		}

		$intQueryID = $wpdb->get_var("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE (queryEmailNotifyPage > 0 OR queryEmailConfirmPage > 0)".$query_where);

		return ($intQueryID > 0 ? true : false);
	}

	function get_form_status($data = array())
	{
		global $wpdb;

		if($this->post_status == '' || isset($data['query_id']) && $data['query_id'] != $this->id || isset($data['post_id']) && $data['post_id'] != $this->post_id)
		{
			if(isset($data['query_id']) && $data['query_id'] > 0){		$this->id = $data['query_id'];}
			if(isset($data['post_id']) && $data['post_id'] > 0){		$this->post_id = $data['post_id'];}

			if($this->id > 0)
			{
				$post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."query.postID = ".$wpdb->posts.".ID WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
			}

			else
			{
				$post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM ".$wpdb->posts." WHERE ID = '%d'", $this->post_id));
			}

			$this->post_status = $post_status;
		}

		return $this->post_status;
	}

	function get_form_array($check_from_form = true)
	{
		global $wpdb;

		$arr_data = array();
		$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

		$result = $wpdb->get_results("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryDeleted = '0'".(IS_ADMIN ? "" : " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)")." ORDER BY queryCreated DESC");

		foreach($result as $r)
		{
			$result2 = get_page_from_form($r->queryID);

			if(count($result2) > 0 || $check_from_form == false)
			{
				$obj_form = new mf_form($r->queryID);
				$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

				$arr_data[$r->queryID] = $strFormName;
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

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE postID = '%d'", $id));

		return $this->id;
	}

	function get_form_id_from_type($id)
	{
		global $wpdb;

		$this->query2type_id = $id;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $id));

		return $this->id;
	}

	function get_post_id($id = 0)
	{
		global $wpdb;

		if($id > 0){	$this->id = $id;}

		if(!($this->post_id > 0))
		{
			$this->post_id = $wpdb->get_var($wpdb->prepare("SELECT postID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $this->id));
		}

		return $this->post_id;
	}

	function get_post_info($data = array())
	{
		global $wpdb;

		if(!isset($data['select'])){	$data['select'] = "post_name";}

		if(isset($data['query_id']) && $data['query_id'] > 0)
		{
			$this->id = $data['query_id'];
		}

		if($this->id > 0)
		{
			$post_name = $wpdb->get_var($wpdb->prepare("SELECT ".$data['select']." FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."query.postID = ".$wpdb->posts.".ID WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
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

		return $wpdb->get_var($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND checkID = '5'", $this->id));
	}

	function get_answer_email($intAnswerID)
	{
		global $wpdb;

		$intQuery2TypeID = $this->get_form_email_field();

		return $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d'", $intAnswerID, $intQuery2TypeID));
	}

	function get_city_from_zip($zip)
	{
		global $wpdb;

		$out = "";

		if(get_bloginfo('language') == "sv-SE")
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT cityName FROM ".$wpdb->base_prefix."query_zipcode WHERE addressZipCode = '%d'", $zip)); //, municipalityName

			foreach($result as $r)
			{
				$strCityName = $r->cityName;
				//$strMunicipalityName = $r->municipalityName;

				$out = $strCityName; //.($strMunicipalityName != '' && $strMunicipalityName != $strCityName ? ", ".$strMunicipalityName : "");
				break;
			}
		}

		return $out;
	}

	function is_form_field_type_used($data)
	{
		global $wpdb;

		$query_join = $query_where = "";

		if(isset($data['query_type_id']) && $data['query_type_id'] > 0)
		{
			$query_where .= " AND queryTypeID = '".$data['query_type_id']."'";
		}

		if(isset($data['required']) && $data['required'] != '')
		{
			$query_where .= " AND queryTypeRequired = '".$data['required']."'";
		}

		if(isset($data['autofocus']) && $data['autofocus'] != '')
		{
			$query_where .= " AND queryTypeAutofocus = '".$data['autofocus']."'";
		}

		if(isset($data['remember']) && $data['remember'] != '')
		{
			$query_where .= " AND queryTypeRemember = '".$data['remember']."'";
		}

		if(isset($data['']) && $data[''] != '')
		{
			$query_where .= " AND queryTypeAutofocus = '".$data['']."'";
		}

		if(isset($data['check_code']) && $data['check_code'] != '')
		{
			$query_join .= " INNER JOIN ".$wpdb->base_prefix."query_check USING (checkID)";
			$query_where .= " AND checkCode = '".$data['check_code']."'";
		}

		$intQuery2TypeID = $wpdb->get_var($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type".$query_join." WHERE queryID = '%d'".$query_where, $this->id));

		return $intQuery2TypeID > 0 ? true : false;
	}

	function get_type_name($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT queryTypeName FROM ".$wpdb->base_prefix."query_type WHERE queryTypeID = '%d'", $id));
	}

	function get_form_type_info($data = array())
	{
		global $wpdb;

		if(!isset($data['query_id'])){			$data['query_id'] = 0;}
		if(!isset($data['query_type_id'])){		$data['query_type_id'] = array();}
		if(!isset($data['query_exclude_id'])){	$data['query_exclude_id'] = 0;}

		if($data['query_id'] > 0)
		{
			$this->id = $data['query_id'];
		}

		$query_where = "";

		if(count($data['query_type_id']) > 0)
		{
			$i = 0;

			$query_where .= " AND (";

				foreach($data['query_type_id'] as $query_type_id)
				{
					$query_where .= ($i > 0 ? " OR " : "")."queryTypeID = '".$query_type_id."'";

					$i++;
				}

			$query_where .= ")";
		}

		if($data['query_exclude_id'] > 0)
		{
			$query_where .= " AND query2TypeID != '".$data['query_exclude_id']."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, query2TypeOrder FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d'".$query_where." ORDER BY query2TypeOrder ASC", $this->id));

		return array($result, $wpdb->num_rows);
	}

	function render_mail_content($data)
	{
		if(!isset($data['answer_id'])){	$data['answer_id'] = 0;}
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

						$out_doc_types .= " ".$arr_value['value'];

						$out_doc_types .= "<br>";
					}
				break;

				case 'products':
					foreach($arr_types as $product)
					{
						//$out_products .= "- ".$product['label'];

						if($product['value'] != '')
						{
							/*if(substr($product['label'], -1) != ":")
							{
								$out_products .= ":";
							}*/

							$out_products .= "- ".$product['value']."<br>";

							$intProductID = $product['id'];
							$strProductName = $product['value'];
						}

						//$out_products .= "<br>";
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
				."&answer_id=".$data['answer_id']
				."&product_id=".$intProductID
				."&hash=".md5(NONCE_SALT."_".$data['answer_id']."_".$intProductID);

			$arr_exclude = array(
				"[form_fields]",
				"[doc_types]",
				"[products]",
				"[product]",
				"[link_yes]",
				"[link_no]",
			);

			$arr_include = array(
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

		if($data['page_id'] > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_content FROM ".$wpdb->posts." WHERE ID = '%d'", $data['page_id']));

			foreach($result as $r)
			{
				$data['subject'] = $r->post_title;
				$mail_template = apply_filters('the_content', $r->post_content);

				$mail_content = $this->render_mail_content(array('answer_id' => $data['answer_id'], 'mail_to' => $data['mail_to'], 'array' => $data['content'], 'template' => $mail_template));

				//This is already in mf_form_mail()
				//add_filter('wp_mail_content_type', 'set_html_content_type');
			}
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

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(queryTypeID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_check USING (checkID) WHERE queryID = '%d' AND queryTypeID = '3' AND checkCode = 'email'", $this->id));
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
			if(in_array($r->queryTypeID, array(10, 11)))
			{
				list($strQueryTypeText, $rest) = explode(":", $r->queryTypeText);
			}

			else
			{
				$strQueryTypeText = $r->queryTypeText;
			}

			$arr_data[$r->query2TypeID] = $strQueryTypeText;
		}

		return $arr_data;
	}

	function get_form_type_result()
	{
		global $wpdb;

		if($this->query2type_id > 0)
		{
			$query_where = "query2typeID = '%d'";
			$query_where_id = $this->query2type_id;
		}

		else
		{
			$query_where = "queryID = '%d'";
			$query_where_id = $this->id;
		}

		return $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeCode, checkCode, checkPattern, queryTypeText, queryTypePlaceholder, queryTypeRequired, queryTypeAutofocus, queryTypeRemember, queryTypeTag, queryTypeClass, queryTypeFetchFrom, queryTypeActionEquals, queryTypeActionShow FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE ".$query_where." GROUP BY ".$wpdb->base_prefix."query2type.query2TypeID ORDER BY query2TypeOrder ASC", $query_where_id));
	}
}

if(!class_exists('mf_form_payment'))
{
	class mf_form_payment
	{
		function __construct($data = array())
		{
			global $wpdb;

			$this->query_id = $data['query_id'];
			$this->base_callback_url = get_site_url().$_SERVER['REQUEST_URI'];

			$result = $wpdb->get_results($wpdb->prepare("SELECT queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryAnswerURL FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $this->query_id));

			foreach($result as $r)
			{
				$this->provider = $r->queryPaymentProvider;
				$this->hmac = $r->queryPaymentHmac;
				$this->merchant = $r->queryPaymentMerchant;
				$this->password = $r->queryPaymentPassword;
				$this->currency = $r->queryPaymentCurrency;
				$this->answer_url = $r->queryAnswerURL;

				$obj_form = new mf_form($this->query_id);

				$this->prefix = $obj_form->get_post_info()."_";
			}
		}

		function PPHttpPost($methodName_, $nvpStr_, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature) //, $PayPalMode
		{
			// Set up your API credentials, PayPal end point, and API version.
			$API_UserName = urlencode($PayPalApiUsername);
			$API_Password = urlencode($PayPalApiPassword);
			$API_Signature = urlencode($PayPalApiSignature);

			$PayPalMode = $this->test == 1 ? 'sandbox' : 'live';
			$paypalmode = ($PayPalMode == 'sandbox') ? '.sandbox' : '';

			$API_Endpoint = "https://api-3t".$paypalmode.".paypal.com/nvp";
			$version = urlencode('109.0');

			// Set the curl parameters.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);

			// Turn off the server and peer verification (TrustManager Concept).
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);

			// Set the API operation, version, and API signature in the request.
			$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";

			// Set the request as a POST FIELD for curl.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

			// Get response from the server.
			$httpResponse = curl_exec($ch);

			if(!$httpResponse) {
				exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
			}

			// Extract the response details.
			$httpResponseAr = explode("&", $httpResponse);

			$httpParsedResponseAr = array();
			foreach ($httpResponseAr as $i => $value) {
				$tmpAr = explode("=", $value);
				if(sizeof($tmpAr) > 1) {
					$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
				}
			}

			if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
				exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
			}

			return $httpParsedResponseAr;
		}

		function process_passthru($data)
		{
			global $wpdb;

			$out = "";

			$this->amount = $data['amount'];
			$this->orderid = $data['orderid'];

			$this->test = $data['test'];

			if($this->provider == 1)
			{
				$out .= $this->process_passthru_dibs();
			}

			else if($this->provider == 3)
			{
				$out .= $this->process_passthru_paypal();
			}

			else
			{
				$out .= $this->process_passthru_skrill();
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
				$out .= "<a href='http://tech.dibspayment.com/toolbox/test_information_cards'>".__("See DIBS test info", 'lang_base')."</a><br>
				<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_base')."</button>";
			}

			else
			{
				$out .= "<script>document.form_payment.submit();</script>";
			}

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '0' AND answerText LIKE '10%'", "102: ".__("Sent to payment", 'lang_base'), $this->orderid));

			return $out;
		}

		function save_token_with_answer_id()
		{
			global $wpdb;

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2answer SET answerToken = %s WHERE answerID = '%d'", urldecode($this->token), $this->orderid));
		}

		//https://developer.paypal.com/webapps/developer/docs/classic/express-checkout/integration-guide/ECCustomizing/
		function process_passthru_paypal()
		{
			global $wpdb;

			$out = "";

			//$PayPalMode = $this->test == 1 ? 'sandbox' : 'live';

			$PayPalReturnURL = $this->base_callback_url."?accept";
			$PayPalCancelURL = $this->base_callback_url."?cancel";

			$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "last"));

			//Parameters for SetExpressCheckout, which will be sent to PayPal
			$padata = '&METHOD=SetExpressCheckout'.
				'&RETURNURL='.urlencode($PayPalReturnURL).
				'&CANCELURL='.urlencode($PayPalCancelURL).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				//'&L_PAYMENTREQUEST_0_AMT0='.urlencode($this->amount).
				'&NOSHIPPING=0'. //set 1 to hide buyer's shipping address, in-case products that does not require shipping
				//'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this->amount).
				'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount).
				//'&L_PAYMENTREQUEST_0_QTY0=1'.
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency).
				'&LOCALECODE='.$this->language; //PayPal pages to match the language on your website.
				//'&LOGOIMG='."http://". //site logo
				//'&CARTBORDERCOLOR=FFFFFF'. //border color of cart
				//'&ALLOWNOTE=1';

			//We need to execute the "SetExpressCheckOut" method to obtain paypal token
			$httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $padata, $this->merchant, $this->password, $this->hmac); //, $PayPalMode

			//Respond according to message we receive from Paypal
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
			{
				$this->token = $httpParsedResponseAr["TOKEN"];

				$this->action = 'https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$this->token;

				$this->save_token_with_answer_id();

				mf_redirect($this->action);

				/*$out .= "<form name='form_payment' action='".$this->action."' method='get'></form>";

				if(isset($this->test) && $this->test == 1)
				{
					$out .= "<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_base')."</button>";
				}

				else
				{
					$out .= "<script>document.form_payment.submit();</script>";
				}*/
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
			global $wpdb;

			$out = "";

			if($this->currency == ''){	$this->currency = "USD";}

			$instance = array();

			$this->action = "https://pay.skrill.com";
			$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "first")); //"EN"; //get_bloginfo('language') [sv_SE, en_US etc]

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
				$out .= "<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_base')."</button>";
			}

			else
			{
				$out .= "<script>document.form_payment.submit();</script>";
			}

			return $out;
		}

		function confirm_cancel()
		{
			global $wpdb;

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '0' AND answerText LIKE '10%'", "103: ".__("User canceled", 'lang_base'), $this->answer_id));

			mf_redirect(get_site_url());
		}

		function confirm_accept()
		{
			global $wpdb;

			if($this->answer_id > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '0' AND answerText LIKE '10%'", "104: ".__("User has paid. Waiting for confirmation...", 'lang_base'), $this->answer_id));

				if($this->answer_url != '' && preg_match("/_/", $this->answer_url))
				{
					list($blog_id, $intQueryAnswerURL) = explode("_", $this->answer_url);
				}

				else
				{
					$blog_id = 0;
					$intQueryAnswerURL = $this->answer_url;
				}

				if($intQueryAnswerURL > 0)
				{
					//Switch to temp site
					####################
					$wpdbobj = clone $wpdb;
					$wpdb->blogid = $blog_id;
					$wpdb->set_prefix($wpdb->base_prefix);
					####################

					if($intQueryAnswerURL != $wp_query->post->ID)
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '0' AND answerText LIKE '10%'", "105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base'), $this->answer_id));

						$strQueryAnswerURL = get_permalink($intQueryAnswerURL);

						mf_redirect($strQueryAnswerURL);
					}

					/*else
					{
						header("Status: 400 Bad Request");
					}*/

					//Switch back to orig site
					###################
					$wpdb = clone $wpdbobj;
					###################
				}

				/*else
				{
					header("Status: 400 Bad Request");
				}*/
			}

			else
			{
				header("Status: 400 Bad Request");
			}
		}

		function confirm_paid($message)
		{
			global $wpdb;

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '0'", "116: ".$message, $this->answer_id));

			header("Status: 200 OK");
		}

		function confirm_error($message)
		{
			global $wpdb;

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '0'", "115: ".$message, $this->answer_id));

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
			$file_suffix = "unknown";

			if($this->is_accept){			$file_suffix = "accept";}
			else if($this->is_callback){	$file_suffix = "callback";}
			else if($this->is_cancel){		$file_suffix = "cancel";}

			$file = date("YmdHis")."_".$file_suffix;
			$debug = "URI: ".$_SERVER['REQUEST_URI']."\n\n"
				."GET: ".var_export($_GET, true)."\n\n"
				."POST: ".var_export($_POST, true)."\n\n"
				."THIS: ".var_export($this, true)."\n\n";

			list($upload_path, $upload_url) = get_uploads_folder("mf_form");

			$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => trim($debug)));
			##################

			$this->amount = check_var('amount', 'int');

			$out .= __("Processing...", 'lang_base');

			if($this->provider == 1)
			{
				$out .= $this->process_callback_dibs();
			}

			else if($this->provider == 3)
			{
				$out .= $this->process_callback_paypal();
			}

			else
			{
				$out .= $this->process_callback_skrill();
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
				$this->confirm_accept();
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
					$this->confirm_paid(__("Payment done", 'lang_base')." (".($this->amount / 100).")");
				}

				else
				{
					$this->confirm_error(__("Payment done", 'lang_base')." (".__("But could not verify", 'lang_base').", ".$mac." != ".$_POST['MAC'].")");
				}
			}

			else if($this->is_cancel)
			{
				//Is the ID really sent with the cancel request?
				$this->confirm_cancel();
			}

			return $out;
		}

		function get_info_from_token()
		{
			global $wpdb;

			$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, queryPaymentAmount FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->base_prefix."query2answer USING (queryID) WHERE answerToken = %s", $this->token));
			$r = $result[0];
			$this->answer_id = $r->answerID;
			$intQueryPaymentAmount = $r->queryPaymentAmount;

			$this->amount = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d'", $this->answer_id, $intQueryPaymentAmount));
		}

		function process_callback_paypal()
		{
			global $wpdb;

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
					$this->confirm_accept();

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
						$this->confirm_paid(__("Payment done", 'lang_base')." (".$this->amount.")");
					}

					else
					{
						$this->confirm_error(__("Payment done", 'lang_base')." (".__("But could not verify", 'lang_base').", Success - ".$this->token.")");

						/*$out .= "<div class='error'>
							<p>GetTransactionDetails failed: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
							<p>".var_export($httpParsedResponseAr, true)."</p>
						</div>";*/

					}
				}

				else
				{
					$this->confirm_error(__("Payment done", 'lang_base')." (".__("But could not verify", 'lang_base').", ".$this->token.")");

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
				$this->confirm_accept();
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
					case -2:		$payment_status_text = __("Failed", 'lang_base');			break;
					case 2:			$payment_status_text = __("Processed", 'lang_base');		break;
					case 0:			$payment_status_text = __("Pending", 'lang_base');			break;
					case -1:		$payment_status_text = __("Cancelled", 'lang_base');		break;
				}

				if($is_valid_mac)
				{
					$this->confirm_paid($status.": ".$payment_status_text." (".$this->amount." ".$currency.")");
				}

				else
				{
					$this->confirm_error($status.": ".$payment_status_text." (".__("But could not verify", 'lang_base').", ".$md5sig." != ".$md5calc.") (".$this->amount." ".$currency.")");
				}
			}

			else if($this->is_cancel)
			{
				$this->confirm_cancel();
			}

			return $out;
		}
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
		global $wpdb, $error_text;

		$obj_form = new mf_form($this->type);
		$this->name = $obj_form->get_post_info(array('select' => 'post_title'));

		$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $this->type));

		$this_row = array();

		foreach($result as $r)
		{
			$intQuery2TypeID = $r->query2TypeID;
			$intQueryTypeID = $r->queryTypeID;
			$strQueryTypeText = $r->queryTypeText;

			switch($intQueryTypeID)
			{
				case 2:
					list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
				break;

				case 10:
				case 11:
					list($strQueryTypeText, $rest) = explode(":", $strQueryTypeText);
				break;
			}

			$this_row[] = stripslashes(strip_tags($strQueryTypeText));
		}

		$this_row[] = __("Created", 'lang_form');

		$this->data[] = $this_row;

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, queryID, answerCreated, answerIP FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID ORDER BY answerCreated DESC", $this->type));

		foreach($result as $r)
		{
			$intAnswerID = $r->answerID;
			$intQueryID = $r->queryID;
			$strAnswerCreated = $r->answerCreated;
			$strAnswerIP = $r->answerIP;

			$this_row = array();

			$resultText = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $intQueryID));

			foreach($resultText as $r)
			{
				$intQuery2TypeID = $r->query2TypeID;
				$intQueryTypeID = $r->queryTypeID;
				$strQueryTypeText = $r->queryTypeText;

				$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d'", $intQuery2TypeID, $intAnswerID));
				$rowsAnswer = $wpdb->num_rows;

				if($rowsAnswer > 0)
				{
					$r = $resultAnswer[0];
					$strAnswerText = $r->answerText;

					switch($intQueryTypeID)
					{
						case 8:
							$strAnswerText = 1;
						break;

						case 7:
							$strAnswerText = format_date($strAnswerText);
						break;

						case 10:
							$arr_content1 = explode(":", $strQueryTypeText);
							$arr_content2 = explode(",", $arr_content1[1]);

							foreach($arr_content2 as $str_content)
							{
								$arr_content3 = explode("|", $str_content);

								if($strAnswerText == $arr_content3[0])
								{
									$strAnswerText = $arr_content3[1];
								}
							}
						break;

						case 11:
							$arr_content1 = explode(":", $strQueryTypeText);
							$arr_content2 = explode(",", $arr_content1[1]);

							$arr_answer_text = explode(",", $strAnswerText);

							$strAnswerText = "";

							foreach($arr_content2 as $str_content)
							{
								$arr_content3 = explode("|", $str_content);

								if(in_array($arr_content3[0], $arr_answer_text))
								{
									$strAnswerText .= ($strAnswerText != '' ? ", " : "").$arr_content3[1];
								}
							}
						break;

						case 15:
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

		if($this->search != '')
		{
			$this->query_where .= get_form_xtra("", $this->search, "", "post_title");
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

				$post_edit_url = IS_ADMIN ? "?page=mf_form/create/index.php&intQueryID=".$obj_form->id : "#";

				$actions = array();

				if($post_status != 'trash')
				{
					if(IS_ADMIN)
					{
						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_form')."</a>";
						$actions['delete'] = "<a href='#delete/query/".$obj_form->id."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
					}

					$actions['copy'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnFormCopy&intQueryID=".$obj_form->id, 'form_copy')."'>".__("Copy", 'lang_form')."</a>";

					if($post_status == 'publish' && $obj_form->id > 0)
					{
						$shortcode = "[mf_form id=".$obj_form->id."]";

						$result = get_page_from_form($obj_form->id);

						if(count($result) > 0)
						{
							foreach($result as $r)
							{
								$post_id_temp = $r['post_id'];

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
					echo "<i class='fa fa-lg fa-link grey' title='".__("Public", 'lang_form')."'></i> ";
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT queryEmail, queryEmailNotifyPage, queryEmailConfirm, queryEmailConfirmPage, queryPaymentProvider FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $obj_form->id));

				foreach($result as $r)
				{
					$strQueryEmail = $r->queryEmail;
					$intQueryEmailNotifyPage = $r->queryEmailNotifyPage;
					$intQueryEmailConfirm = $r->queryEmailConfirm;
					$intQueryEmailConfirmPage = $r->queryEmailConfirmPage;
					$intQueryPaymentProvider = $r->queryPaymentProvider;

					if($strQueryEmail != '')
					{
						if($intQueryEmailNotifyPage > 0)
						{
							echo "<i class='fa fa-lg fa-send grey' title='".sprintf(__("A notification email based on a template will be sent to %s", 'lang_form'), $strQueryEmail)."'></i> ";
						}

						else
						{
							echo "<i class='fa fa-lg fa-send grey' title='".sprintf(__("Emails will be sent to %s on every answer", 'lang_form'), $strQueryEmail)."'></i> ";
						}
					}

					if($intQueryEmailConfirm > 0)
					{
						if($intQueryEmailConfirmPage > 0)
						{
							echo "<i class='fa fa-lg fa-send-o grey' title='".__("A confirmation email based on a template will be sent to the visitor", 'lang_form')."'></i> ";
						}

						else
						{
							echo "<i class='fa fa-lg fa-send-o grey' title='".__("A confirmation email will be sent to the visitor", 'lang_form')."'></i> ";
						}
					}

					if($intQueryPaymentProvider > 0)
					{
						switch($intQueryPaymentProvider)
						{
							case 3:
								$icon = "fa-paypal";
							break;

							default:
								$icon = "fa-shopping-cart";
							break;
						}

						echo "<i class='fa fa-lg ".$icon." grey' title='".__("Provider", 'lang_form')."'></i> ";
					}
				}

				if($obj_form->is_form_field_type_used(array('required' => true)))
				{
					echo "<i class='fa fa-lg fa-asterisk grey' title='".__("There are required fields", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('autofocus' => true)))
				{
					echo "<i class='fa fa-lg fa-i-cursor grey' title='".__("There are autofocus fields", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('remember' => true)))
				{
					echo "<i class='fa fa-lg fa-refresh grey' title='".__("There are remembered fields", 'lang_form')."'></i> ";
				}

				echo "<br>";
				
				if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'check_code' => 'email')))
				{
					echo "<i class='fa fa-lg fa-at grey' title='".__("There is a field for entering email adress", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 1)))
				{
					echo "<i class='fa fa-lg fa-check-square-o grey' title='".__("Checkbox", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 2)))
				{
					echo "<i class='fa fa-lg fa-sliders grey' title='".__("Range", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 7)))
				{
					echo "<i class='fa fa-lg fa-calendar grey' title='".__("Datepicker", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 8)))
				{
					echo "<i class='fa fa-lg fa-circle-o grey' title='".__("Radio button", 'lang_form')."'></i> ";
				}

				if($obj_form->is_form_field_type_used(array('query_type_id' => 15)))
				{
					echo "<i class='fa fa-lg fa-file-o grey' title='".__("File", 'lang_form')."'></i> ";
				}
			break;

			case 'answers':
				if($post_status != 'trash')
				{
					$wpdb->query($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID", $obj_form->id));
					$query_answers = $wpdb->num_rows;

					if($query_answers > 0)
					{
						$count_message = get_count_message($obj_form->id);

						$actions = array();

						$actions['show_answers'] = "<a href='?page=mf_form/answer/index.php&intQueryID=".$obj_form->id."'>".__("Show", 'lang_form')."</a>"; 

						$actions['export_csv'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnExportRun&intExportType=".$obj_form->id."&strExportAction=csv", 'export_run')."'>".__("CSV", 'lang_form')."</a>";

						if(is_plugin_active("mf_phpexcel/index.php"))
						{
							$actions['export_xls'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnExportRun&intExportType=".$obj_form->id."&strExportAction=xls", 'export_run')."'>".__("XLS", 'lang_form')."</a>";
						}

						echo $query_answers
						.$count_message
						.$this->row_actions($actions);
					}
				}
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

class mf_form_output
{
	function __construct($data)
	{
		$this->row = $data['result'];
		$this->query_prefix = $data['query_prefix'];
		$this->queryEmailCheckConfirm = isset($data['email_check_confirm']) ? $data['email_check_confirm'] : 'no';

		$this->output = "";

		$this->show_required = $this->show_autofocus = $this->show_remember = $this->has_required_email = false;

		$this->answer_text = "";

		$this->in_edit_mode = $data['in_edit_mode'];
	}

	function calculate_value($intAnswerID)
	{
		global $wpdb;

		$this->is_required_email = $this->row->queryTypeID == 3 && $this->row->checkCode == 'email' && $this->row->queryTypeRequired == 1;

		if($this->queryEmailCheckConfirm == 'yes' && $this->is_required_email)
		{
			$this->has_required_email = true;
		}

		if($intAnswerID > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d' LIMIT 0, 1", $this->row->query2TypeID, $intAnswerID));

			foreach($result as $r)
			{
				switch($this->row->queryTypeID)
				{
					default:
						$this->answer_text = stripslashes(stripslashes($r->answerText));
					break;

					case 8:
						$this->answer_text = $this->row->query2TypeID;
					break;
				}
			}
		}

		if($this->answer_text == '')
		{
			$this->answer_text = check_var($this->query_prefix.$this->row->query2TypeID, 'char');
		}
	}

	function filter_form_fields(&$field_data)
	{
		if($this->row->queryTypeFetchFrom != '' && isset($field_data['value']) && $field_data['value'] == '')
		{
			$field_data['value'] = check_var($this->row->queryTypeFetchFrom);
		}
	}

	function get_form_fields($data = array())
	{
		global $intQueryTypeID2_temp, $intQuery2TypeID2_temp;

		if(!isset($data['show_label'])){		$data['show_label'] = true;}
		if(!isset($data['ignore_required'])){	$data['ignore_required'] = false;}

		if($data['ignore_required'] == true)
		{
			$this->row->queryTypeRequired = false;
		}

		$field_data = array(
			'name' => $this->query_prefix.$this->row->query2TypeID,
		);

		$class_output = $this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "";
		$class_output_small = ($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "");

		switch($this->row->queryTypeCode) //$this->row->queryTypeID
		{
			//case 1:
			case 'checkbox':
				$is_first_checkbox = false;

				if($this->row->queryTypeID != $intQueryTypeID2_temp)
				{
					$intQuery2TypeID2_temp = $this->row->query2TypeID;

					$is_first_checkbox = true;
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['value'] = 1;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->queryTypeClass.($is_first_checkbox ? " clear" : "");

				$this->output .= show_checkbox($field_data);

				$this->show_required = true;
			break;

			//case 2:
			case 'range':
				$arr_content = explode("|", $this->row->queryTypeText);

				if($this->answer_text == '' && isset($arr_content[3]))
				{
					$this->answer_text = $arr_content[3];
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content[0]." (<span>".$this->answer_text."</span>)";
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = "min='".$arr_content[1]."' max='".$arr_content[2]."'".($this->row->queryTypeAutofocus ? " autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass.($this->row->queryTypeRemember ? " remember" : "");
				$field_data['type'] = "range";

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 7:
			case 'datepicker':
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass.($this->row->queryTypeRemember ? " remember" : "");
				$field_data['type'] = "date";
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 8:
			case 'radio_button':
				$is_first_radio = false;

				if($this->row->queryTypeID != $intQueryTypeID2_temp)
				{
					$intQuery2TypeID2_temp = $this->row->query2TypeID;

					$is_first_radio = true;
				}

				if(isset($_POST["radio_".$intQuery2TypeID2_temp]))
				{
					$this->answer_text = check_var($_POST["radio_".$intQuery2TypeID2_temp], 'int', false);
				}

				else if($this->answer_text == '' && $this->row->queryTypeRequired == 1)
				{
					$this->answer_text = $this->row->query2TypeID;
				}

				$field_data['name'] = "radio_".$intQuery2TypeID2_temp;

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->row->query2TypeID;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->queryTypeClass.($is_first_radio ? " clear" : "");

				$this->output .= show_radio_input($field_data);

				$this->show_required = true;
			break;

			//case 10:
			case 'select':
				if($this->row->queryTypeActionShow > 0) //$this->row->queryTypeActionEquals != '' && 
				{
					$this->row->queryTypeClass .= ($this->row->queryTypeClass != '' ? " " : "")."form_action";
					$field_data['xtra'] = "data-equals='".$this->row->queryTypeActionEquals."' data-show='".$this->query_prefix.$this->row->queryTypeActionShow."'";
				}

				$arr_content1 = explode(":", $this->row->queryTypeText);
				$arr_content2 = explode(",", $arr_content1[1]);

				$arr_data = array();

				foreach($arr_content2 as $str_content)
				{
					$arr_content3 = explode("|", $str_content);

					$arr_data[$arr_content3[0]] = $arr_content3[1];
				}

				$field_data['data'] = $arr_data;

				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content1[0];
				}

				$field_data['compare'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass.($this->row->queryTypeRemember ? " remember" : "");

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = $this->show_remember = true;
			break;

			//case 11:
			case 'select_multiple':
				$arr_content1 = explode(":", $this->row->queryTypeText);
				$arr_content2 = explode(",", $arr_content1[1]);

				$arr_data = array();

				foreach($arr_content2 as $str_content)
				{
					$arr_content3 = explode("|", $str_content);

					$arr_data[$arr_content3[0]] = $arr_content3[1];
				}

				$field_data['name'] .= "[]";
				$field_data['data'] = $arr_data;

				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content1[0];
				}

				$field_data['compare'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = true;
			break;

			//case 3:
			case 'input_field':
				if($this->row->checkCode == "zip")
				{
					$this->row->queryTypeClass .= ($this->row->queryTypeClass != '' ? " " : "")."form_zipcode";
				}

				if($this->has_required_email && $this->is_required_email)
				{
					$this->row->queryTypeClass .= ($this->row->queryTypeClass != '' ? " " : "")."this_is_required_email";
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['maxlength'] = 200;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass.($this->row->queryTypeRemember ? " remember" : "");
				$field_data['type'] = $this->row->checkCode;
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;
				$field_data['pattern'] = $this->row->checkPattern;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 4:
			case 'textarea':
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->queryTypeClass.($this->row->queryTypeRemember ? " remember" : "");
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = $this->show_remember = true;
			break;

			//case 5:
			case 'text':
				if($this->row->queryTypeTag != '')
				{
					$this->output .= "<".$this->row->queryTypeTag.$class_output." id='".$field_data['name']."'>"
						.$this->row->queryTypeText
					."</".$this->row->queryTypeTag.">";
				}

				else
				{
					$this->output .= "<div".$class_output." id='".$field_data['name']."'>
						<p>".$this->row->queryTypeText."</p>
					</div>";
				}
			break;

			//case 16:
			case 'email_text':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p".$class_output.">"
						.($this->in_edit_mode == true ? __("Only in e-mail", 'lang_form').": " : "")
						.$this->row->queryTypeText
					."</p>";
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
					$this->output .= "<p class='grey".$class_output_small."'>".__("Hidden", 'lang_form')." (".$this->row->queryTypeText.": '".$referer_url."')</p>";
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
					$this->output .= "<p class='grey".$class_output_small."'>".__("Hidden", 'lang_form')." (".$this->query_prefix.$this->row->query2TypeID.": ".$this->row->queryTypeText.")</p>";
				}

				else
				{
					$field_data['value'] = ($this->answer_text != '' ? $this->answer_text : $this->row->queryTypeText);

					$this->filter_form_fields($field_data);
					$this->output .= input_hidden($field_data);
				}
			break;

			//case 13:
			case 'custom_tag':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;".$this->row->queryTypeText.$class_output."&gt;</p>";
				}

				else
				{
					$this->output .= "<".$this->row->queryTypeText.$class_output." id='".$field_data['name']."'>";
				}
			break;

			//case 14:
			case 'custom_tag_end':
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;/".$this->row->queryTypeText."&gt;</p>";
				}

				else
				{
					$this->output .= "</".$this->row->queryTypeText.">";
				}
			break;

			//case 15:
			case 'file':
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass;

				$this->output .= show_file_field($field_data);

				$this->show_required = true;
			break;

			default:
				do_log(__("There was no code for this type", 'lang_form')." (".$this->row->queryTypeCode.")");
			break;
		}

		$intQueryTypeID2_temp = $this->row->queryTypeID;
	}

	function get_output($data = array())
	{
		$out = "";

		if(!isset($this->in_edit_mode)){	$this->in_edit_mode = false;}

		if($this->in_edit_mode == true)
		{
			$out .= "<mf-form-row id='type_".$this->row->query2TypeID."' class='flex_flow".($data['query2type_id'] == $this->row->query2TypeID ? " active" : "")."'>"
				.$this->output;

				/*if($this->row->queryTypeID != 14)
				{*/
					$out .= "<div class='row_settings'>";

						if($this->show_required == true)
						{
							$out .= show_checkbox(array('name' => "require_".$this->row->query2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeRequired, 'xtra' => " class='ajax_checkbox' rel='require/type/".$this->row->query2TypeID."'"));
						}

						if($this->show_autofocus == true)
						{
							$out .= show_checkbox(array('name' => "autofocus_".$this->row->query2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeAutofocus, 'xtra' => " class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->query2TypeID."'"));
						}

						if($this->show_remember == true)
						{
							$out .= show_checkbox(array('name' => "remember_".$this->row->query2TypeID, 'text' => __("Remember answer", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeRemember, 'xtra' => " class='ajax_checkbox remember' rel='remember/type/".$this->row->query2TypeID."'"));
						}

						$out .= "<a href='?page=mf_form/create/index.php&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Edit", 'lang_form')."</a> | 
						<a href='#delete/type/".$this->row->query2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a> | <a href='?page=mf_form/create/index.php&btnFieldCopy&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Copy", 'lang_form')."</a>
					</div>";
				//}

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
	function widget_form()
	{
		$widget_ops = array(
			'classname' => 'form',
			'description' => __("Display a form that you've previously created", 'lang_form')
		);

		$control_ops = array('id_base' => 'form-widget');

		$this->__construct('form-widget', __("Form", 'lang_form'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		if($instance['form_id'] > 0)
		{
			echo $before_widget;

				if($instance['form_heading'] != '')
				{
					echo $before_title
						.$instance['form_heading']
					.$after_title;
				}

				echo show_query_form(array('query_id' => $instance['form_id']))
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['form_heading'] = strip_tags($new_instance['form_heading']);
		$instance['form_id'] = strip_tags($new_instance['form_id']);

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$defaults = array(
			'form_heading' => "",
			'form_id' => "",
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		$obj_form = new mf_form();
		$arr_data = $obj_form->get_form_array(false);

		echo "<p>"
			.show_textfield(array('name' => $this->get_field_name('form_heading'), 'text' => __("Heading", 'lang_form'), 'value' => $instance['form_heading'], 'xtra' => "class='widefat'"))
		."</p>
		<p>"
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('form_id'), 'value' => $instance['form_id']))
		."</p>";
	}
}