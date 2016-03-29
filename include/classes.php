<?php

class mf_form
{
	function mf_form($id = 0)
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

		if(isset($_GET['btnQueryCopy']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_copy'))
		{
			$inserted = true;

			$result_temp = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$fields = ", queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailName, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentCurrency, blogID";

				$strFormName = $this->get_form_name($this->id);

				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strFormName,
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query (queryName, postID".$fields.", queryCreated, userID) (SELECT CONCAT(queryName, ' (".__("copy", 'lang_form').")'), '%d'".$fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0')", $intPostID, $this->id));
				$intQueryID_new = $wpdb->insert_id;

				if($intQueryID_new > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeID DESC", $this->id));

					foreach($result as $r)
					{
						$intQuery2TypeID = $r->query2TypeID;

						$fields = "queryTypeID, queryTypeText, checkID, queryTypeRequired, queryTypeAutofocus, query2TypeOrder";

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type (queryID, ".$fields.", query2TypeCreated, userID) (SELECT %d, ".$fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d')", $intQueryID_new, $intQuery2TypeID));

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

		else if(isset($_POST['btnQueryUpdate']))
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

	function is_published($data = array())
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
			$result = get_page_from_form($r->queryID);

			if(count($result) > 0 || $check_from_form == false)
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

		$intQuery2TypeID = $wpdb->get_var($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND checkID = '5'", $this->id));

		return $this->get_post_info()."_".$intQuery2TypeID;
	}

	function is_form_field_type_used($data)
	{
		global $wpdb;

		$query_join = $query_where = "";

		if(isset($data['required']) && $data['required'] != '')
		{
			$query_where .= " AND queryTypeRequired = '".$data['required']."'";
		}

		if(isset($data['check_code']) && $data['check_code'] != '')
		{
			$query_join .= " INNER JOIN ".$wpdb->base_prefix."query_check USING (checkID)";
			$query_where .= " AND checkCode = '".$data['check_code']."'";
		}

		$intQuery2TypeID = $wpdb->get_var($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type".$query_join." WHERE queryID = '%d' AND queryTypeID = '%d'".$query_where, $this->id, $data['query_type_id']));

		return $intQuery2TypeID > 0 ? true : false;
	}

	function get_type_name($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT queryTypeName FROM ".$wpdb->base_prefix."query_type WHERE queryTypeID = '%d'", $id));
	}

	function get_form_type_info($data)
	{
		global $wpdb;

		if(isset($data['query_id']) && $data['query_id'] > 0)
		{
			$this->id = $data['query_id'];
		}

		$query_where = "";

		foreach($data['query_type_id'] as $query_type_id)
		{
			$query_where .= ($query_where != '' ? " OR " : "")."queryTypeID = '".$query_type_id."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, query2TypeOrder FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND (".$query_where.") ORDER BY query2TypeOrder ASC", $this->id));

		return array($result, $wpdb->num_rows);
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

		return $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, checkCode, checkPattern, queryTypeText, queryTypePlaceholder, queryTypeRequired, queryTypeAutofocus, queryTypeTag, queryTypeClass, queryTypeFetchFrom FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE ".$query_where." GROUP BY ".$wpdb->base_prefix."query2type.query2TypeID ORDER BY query2TypeOrder ASC", $query_where_id));
	}
}

if(!class_exists('mf_form_payment'))
{
	class mf_form_payment
	{
		function mf_form_payment($data = array())
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

		function PPHttpPost($methodName_, $nvpStr_, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode)
		{
			// Set up your API credentials, PayPal end point, and API version.
			$API_UserName = urlencode($PayPalApiUsername);
			$API_Password = urlencode($PayPalApiPassword);
			$API_Signature = urlencode($PayPalApiSignature);

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

			$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '102: ".__("Sent to payment", 'lang_base')."' WHERE answerID = '".$this->orderid."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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

			$PayPalMode = $this->test == 1 ? 'sandbox' : 'live';

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
			$httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $padata, $this->merchant, $this->password, $this->hmac, $PayPalMode);

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

			$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

			mf_redirect(get_site_url());
		}

		function confirm_accept()
		{
			global $wpdb;

			if($this->answer_id > 0)
			{
				$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '104: ".__("User has paid. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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
						$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '116: ".$message."' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

			header("Status: 200 OK");
		}

		function confirm_error($message)
		{
			global $wpdb;

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '115: ".$message."' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

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
				$httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $padata, $this->merchant, $this->password, $this->hmac, $PayPalMode);

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

					$httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $padata, $this->merchant, $this->password, $this->hmac, $PayPalMode);

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
			'shortcode' => __("Shortcode", 'lang_form'),
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

					$actions['copy'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnQueryCopy&intQueryID=".$obj_form->id, 'form_copy')."'>".__("Copy", 'lang_form')."</a>";

					if($obj_form->is_published() == "publish")
					{
						$post_url = get_permalink($post_id);

						if($post_url != '')
						{
							$actions['view'] = "<a href='".$post_url."'>".__("View form", 'lang_form')."</a>";
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

			case 'shortcode':
				if($post_status == 'publish' && $obj_form->id > 0)
				{
					$strQueryShortcode = "[mf_form id=".$obj_form->id."]";

					$actions = array();

					$result = get_page_from_form($obj_form->id);

					if(count($result) > 0)
					{
						foreach($result as $r)
						{
							$post_id_temp = $r['post_id'];

							$actions['edit_page'] = "<a href='".admin_url("post.php?post=".$post_id_temp."&action=edit")."'>".__("Edit Page", 'lang_form')."</a> | <a href='".get_permalink($post_id_temp)."'>".__("View page", 'lang_form')."</a>";
						}
					}

					else
					{
						$actions['add_page'] = "<a href='".admin_url("post-new.php?post_type=page&content=".$strQueryShortcode)."'>".__("Add New Page", 'lang_form')."</a>";
					}

					echo $strQueryShortcode
					.$this->row_actions($actions);
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

		$this->show_required = $this->show_autofocus = $this->has_required_email = false;

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
				$this->answer_text = $r->answerText;
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

		if(!isset($data['show_label'])){	$data['show_label'] = true;}

		$field_data = array(
			'name' => $this->query_prefix.$this->row->query2TypeID,
		);

		$class_output = $this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "";
		$class_output_small = ($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "");

		switch($this->row->queryTypeID)
		{
			//Checkbox
			case 1:
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

			//Input range
			case 2:
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
				$field_data['xtra_class'] = $this->row->queryTypeClass;
				$field_data['type'] = "range";

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Input date
			case 7:
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass;
				$field_data['type'] = "date";
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Radio button
			case 8:
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

			//Select
			case 10:
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
				$field_data['class'] = $this->row->queryTypeClass;

				$this->filter_form_fields($field_data);
				$this->output .= show_select($field_data);

				$this->show_required = true;
			break;

			//Select (multiple)
			case 11:
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

			//Textfield
			case 3:
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
				$field_data['xtra_class'] = $this->row->queryTypeClass;
				$field_data['type'] = $this->row->checkCode;
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;
				$field_data['pattern'] = $this->row->checkPattern;

				$this->filter_form_fields($field_data);
				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Textarea
			case 4:
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->queryTypeClass;
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;

				$this->filter_form_fields($field_data);
				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Text
			case 5:
				if($this->row->queryTypeTag != '')
				{
					$this->output .= "<".$this->row->queryTypeTag.$class_output.">"
						.$this->row->queryTypeText
					."</".$this->row->queryTypeTag.">";
				}

				else
				{
					$this->output .= "<div".$class_output.">
						<p>".$this->row->queryTypeText."</p>
					</div>";
				}
			break;

			//Space
			case 6:
				$this->output .= $this->in_edit_mode == true ? "<p class='grey".$class_output_small."'>(".__("Space", 'lang_form').")</p>" : "<p".$class_output.">&nbsp;</p>";
			break;

			//Referer URL
			case 9:
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

			//Hidden field
			case 12:
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

			//Custom tag (start)
			case 13:
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;".$this->row->queryTypeText.$class_output."&gt;</p>";
				}

				else
				{
					$this->output .= "<".$this->row->queryTypeText.$class_output.">";
				}
			break;

			//Custom tag (end)
			case 14:
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;/".$this->row->queryTypeText."&gt;</p>";
				}

				else
				{
					$this->output .= "</".$this->row->queryTypeText.">";
				}
			break;

			//File
			case 15:
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass;

				$this->output .= show_file_field($field_data);

				$this->show_required = true;
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
			$out .= "<mf-form-row id='type_".$this->row->query2TypeID."'".($data['query2type_id'] == $this->row->query2TypeID ? " class='active'" : "").">"
				.$this->output;

				if($this->row->queryTypeID != 14)
				{
					$out .= "<div class='row_settings form_buttons'>";

						if($this->show_required == true)
						{
							$out .= show_checkbox(array('name' => "require_".$this->row->query2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeRequired, 'xtra' => " class='ajax_checkbox' rel='require/type/".$this->row->query2TypeID."'"));
						}

						if($this->show_autofocus == true)
						{
							$out .= show_checkbox(array('name' => "autofocus_".$this->row->query2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeAutofocus, 'xtra' => " class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->query2TypeID."'"));
						}

						$out .= "<a href='?page=mf_form/create/index.php&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Edit", 'lang_form')."</a> | 
						<a href='#delete/type/".$this->row->query2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a> | <a href='?page=mf_form/create/index.php&btnFieldCopy&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Copy", 'lang_form')."</a>
					</div>";
				}

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
		$arr_data = $obj_form->get_form_array();

		echo "<p>
			<label for='".$this->get_field_id('form_heading')."'>".__("Heading", 'lang_form')."</label>
			<input type='text' name='".$this->get_field_name('form_heading')."' value='".$instance['form_heading']."' class='widefat'>
		</p>
		<p>"
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_id('form_id'), 'compare' => $instance['form_id']))
		."</p>";
	}
}