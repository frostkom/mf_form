<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
wp_enqueue_script('jquery-ui-sortable');
mf_enqueue_script('script_touch', plugins_url()."/mf_base/include/jquery.ui.touch-punch.min.js");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_base')));

$obj_form = new mf_form();

$strFormName = check_var('strFormName');
$strFormURL = check_var('strFormURL');
$dteFormDeadline = check_var('dteFormDeadline');

$intQuery2TypeID = check_var('intQuery2TypeID');
$intQuery2TypeOrder = check_var('intQuery2TypeOrder');

$intQueryEmailConfirm = isset($_POST['intQueryEmailConfirm']) ? 1 : 0;
$strQueryEmailConfirmPage = check_var('strQueryEmailConfirmPage');
$intQueryShowAnswers = isset($_POST['intQueryShowAnswers']) ? 1 : 0;
$strQueryAnswerURL = check_var('strQueryAnswerURL');
$strQueryEmail = check_var('strQueryEmail', 'email');
$intQueryEmailNotify = check_var('intQueryEmailNotify');
$strQueryEmailName = check_var('strQueryEmailName');
$strQueryEmailCheckConfirm = check_var('strQueryEmailCheckConfirm');
$strQueryMandatoryText = check_var('strQueryMandatoryText');
$strQueryButtonText = check_var('strQueryButtonText');
$strQueryButtonSymbol = check_var('strQueryButtonSymbol');
$intQueryPaymentProvider = check_var('intQueryPaymentProvider');
$strQueryPaymentHmac = check_var('strQueryPaymentHmac');
$strQueryPaymentMerchant = check_var('strQueryPaymentMerchant');
$strQueryPaymentPassword = check_var('strQueryPaymentPassword');
$strQueryPaymentCurrency = check_var('strQueryPaymentCurrency');
$intQueryPaymentAmount = check_var('intQueryPaymentAmount');
$intQueryTypeID = check_var('intQueryTypeID');
$strQueryTypeText = isset($_POST['strQueryTypeText']) ? $_POST['strQueryTypeText'] : ""; //Allow HTML here
$strQueryTypeText2 = check_var('strQueryTypeText2');
$intCheckID = check_var('intCheckID');
$strQueryTypeTag = check_var('strQueryTypeTag');
$strQueryTypeClass = check_var('strQueryTypeClass');
$strQueryTypePlaceholder = check_var('strQueryTypePlaceholder');

$strQueryTypeSelect = check_var('strQueryTypeSelect', '', true, "0|-- Choose here --,1|Nej,2|Ja");
$strQueryTypeMin = check_var('strQueryTypeMin', '', true, "0");
$strQueryTypeMax = check_var('strQueryTypeMax', '', true, 100);
$strQueryTypeDefault = check_var('strQueryTypeDefault', '', true, 1);

$error_text = $done_text = "";

echo "<div class='wrap'>";

	if((isset($_POST['btnFormPublish']) || isset($_POST['btnFormDraft'])) && wp_verify_nonce($_POST['_wpnonce'], 'form_update'))
	{
		if($strFormName == '')
		{
			$error_text = __("Please, enter all required fields", 'lang_form');
		}

		else
		{
			if($obj_form->id > 0)
			{
				$post_data = array(
					'ID' => $obj_form->post_id,
					//'post_type' => 'mf_form',
					'post_status' => isset($_POST['btnFormPublish']) ? 'publish' : 'draft',
					'post_title' => $strFormName,
					'post_name' => $strFormURL,
				);

				wp_update_post($post_data);

				$obj_form->meta(array('action' => 'update', 'key' => 'deadline', 'value' => $dteFormDeadline));

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET blogID = '%d', queryEmailCheckConfirm = %s, queryEmailConfirm = '%d', queryEmailConfirmPage = %s, queryShowAnswers = '%d', queryName = %s, queryAnswerURL = %s, queryEmail = %s, queryEmailNotify = '%d', queryEmailName = %s, queryMandatoryText = %s, queryButtonText = %s, queryButtonSymbol = %s, queryPaymentProvider = '%d', queryPaymentHmac = %s, queryPaymentMerchant = %s, queryPaymentPassword = %s, queryPaymentCurrency = %s, queryPaymentAmount = '%d' WHERE queryID = '%d' AND queryDeleted = '0'", $wpdb->blogid, $strQueryEmailCheckConfirm, $intQueryEmailConfirm, $strQueryEmailConfirmPage, $intQueryShowAnswers, $strFormName, $strQueryAnswerURL, $strQueryEmail, $intQueryEmailNotify, $strQueryEmailName, $strQueryMandatoryText, $strQueryButtonText, $strQueryButtonSymbol, $intQueryPaymentProvider, $strQueryPaymentHmac, $strQueryPaymentMerchant, $strQueryPaymentPassword, $strQueryPaymentCurrency, $intQueryPaymentAmount, $obj_form->id));
			}

			else
			{
				//$result = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryName = '%d'", $strFormName));
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_form' AND post_title = '%d'", $strFormName));

				if($wpdb->num_rows > 0)
				{
					$error_text = __("There is already a form with that name. Try with another one.", 'lang_form');
				}

				else
				{
					$post_data = array(
						'post_type' => 'mf_form',
						'post_status' => isset($_POST['btnFormPublish']) ? 'publish' : 'draft',
						'post_title' => $strFormName,
					);

					$obj_form->post_id = wp_insert_post($post_data);

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query SET blogID = '%d', postID = '%d', queryName = %s, queryCreated = NOW(), userID = '%d'", $wpdb->blogid, $obj_form->post_id, $strFormName, get_current_user_id()));
					$obj_form->id = $wpdb->insert_id;
				}
			}

			if($wpdb->rows_affected > 0)
			{
				echo "<script>location.href='".get_site_url()."/wp-admin/admin.php?page=mf_form/create/index.php&intQueryID=".$obj_form->id."'</script>";
			}
		}
	}

	else if(isset($_POST['btnFormAdd']) && wp_verify_nonce($_POST['_wpnonce'], 'form_add'))
	{
		//Clean up settings if not used for the specific type of field
		################
		if($intQueryTypeID != 3)
		{
			$intCheckID = "";
		}
		################

		if(($intQueryTypeID == 10 || $intQueryTypeID == 11) && $strQueryTypeSelect == "")
		{
			$error_text = __("Please, enter all required fields", 'lang_form');
		}

		else
		{
			switch($intQueryTypeID)
			{
				case 2:
					$strQueryTypeText = str_replace("|", "", $strQueryTypeText)."|".str_replace("|", "", $strQueryTypeMin)."|".str_replace("|", "", $strQueryTypeMax)."|".str_replace("|", "", $strQueryTypeDefault);
				break;

				case 10:
				case 11:
					$strQueryTypeText = str_replace(":", "", $strQueryTypeText).":".str_replace(":", "", $strQueryTypeSelect);
				break;

				case 13:
				case 14:
					$strQueryTypeText = $strQueryTypeText2;
				break;
			}

			if($intQuery2TypeID > 0)
			{
				if($intQueryTypeID > 0 && ($intQueryTypeID == 6 || $intQueryTypeID == 9 || $strQueryTypeText != ''))
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, userID = '%d' WHERE query2TypeID = '%d'", $intQueryTypeID, $strQueryTypeText, $strQueryTypePlaceholder, $intCheckID, $strQueryTypeTag, $strQueryTypeClass, get_current_user_id(), $intQuery2TypeID));

					if($intQueryTypeID == 13)
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeText = %s, queryTypeClass = %s, userID = '%d' WHERE query2TypeID2 = '%d'", $strQueryTypeText, $strQueryTypeClass, get_current_user_id(), $intQuery2TypeID));
					}

					$intQuery2TypeID = $intQueryTypeID = $strQueryTypeText = $strQueryTypePlaceholder = $intCheckID = $strQueryTypeTag = $strQueryTypeClass = "";
				}

				else
				{
					$error_text = __("Couldn't update the field", 'lang_form');
				}
			}

			else
			{
				if($obj_form->id > 0 && $intQueryTypeID > 0 && ($intQueryTypeID == 6 || $intQueryTypeID == 9 || $strQueryTypeText != ''))
				{
					$intQuery2TypeOrder = $wpdb->get_var($wpdb->prepare("SELECT query2TypeOrder + 1 FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeOrder DESC", $obj_form->id));

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET queryID = '%d', queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $obj_form->id, $intQueryTypeID, $strQueryTypeText, $strQueryTypePlaceholder, $intCheckID, $strQueryTypeTag, $strQueryTypeClass, $intQuery2TypeOrder, get_current_user_id()));

					if($intQueryTypeID == 13)
					{
						$intQuery2TypeID = $wpdb->insert_id;
						$intQueryTypeID = 14;
						$intQuery2TypeOrder++;

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET query2TypeID2 = '%d', queryID = '%d', queryTypeID = '%d', queryTypeText = %s, queryTypeClass = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $intQuery2TypeID, $obj_form->id, $intQueryTypeID, $strQueryTypeText, $strQueryTypeClass, $intQuery2TypeOrder, get_current_user_id()));
					}

					if($wpdb->rows_affected > 0)
					{
						$intQuery2TypeID = $intQueryTypeID = $strQueryTypeText = $strQueryTypePlaceholder = $intCheckID = $strQueryTypeTag = $strQueryTypeClass = "";
					}
				}

				else
				{
					$error_text = __("Couldn't insert the new field", 'lang_form');
				}
			}
		}

		if($intQueryTypeID == 0)
		{
			echo "<script>location.href='".get_site_url()."/wp-admin/admin.php?page=mf_form/create/index.php&intQueryID=".$obj_form->id."'</script>";
		}
	}

	if($obj_form->id > 0)
	{
		if(isset($_GET['recover']))
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryDeleted = '0' WHERE queryID = '%d'", $obj_form->id));
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryEmailCheckConfirm, queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailName, queryMandatoryText, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryPaymentAmount, queryCreated FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id));
		$r = $result[0];
		$strQueryEmailCheckConfirm = $r->queryEmailCheckConfirm;
		$intQueryEmailConfirm = $r->queryEmailConfirm;
		$strQueryEmailConfirmPage = $r->queryEmailConfirmPage;
		$intQueryShowAnswers = $r->queryShowAnswers;
		$strQueryAnswerURL = $r->queryAnswerURL;
		$strQueryEmail = $r->queryEmail;
		$intQueryEmailNotify = $r->queryEmailNotify;
		$strQueryEmailName = $r->queryEmailName;
		$strQueryMandatoryText = $r->queryMandatoryText;
		$strQueryButtonText = $r->queryButtonText;
		$strQueryButtonSymbol = $r->queryButtonSymbol;
		$intQueryPaymentProvider = $r->queryPaymentProvider;
		$strQueryPaymentHmac = $r->queryPaymentHmac;
		$strQueryPaymentMerchant = $r->queryPaymentMerchant;
		$strQueryPaymentPassword = $r->queryPaymentPassword;
		$strQueryPaymentCurrency = $r->queryPaymentCurrency;
		$intQueryPaymentAmount = $r->queryPaymentAmount;
		$strQueryCreated = $r->queryCreated;

		$strFormName = $obj_form->get_post_info(array('select' => "post_title"));
		$strFormURL = $obj_form->get_post_info();
		$dteFormDeadline = $obj_form->meta(array('action' => 'get', 'key' => 'deadline'));
	}

	if($intQuery2TypeID > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT queryTypeID, queryTypeText, queryTypePlaceholder, checkID, queryTypeTag, queryTypeClass FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $intQuery2TypeID));
		$r = $result[0];
		$intQueryTypeID = $r->queryTypeID;
		$strQueryTypeText = $r->queryTypeText;
		$intCheckID = $r->checkID;
		$strQueryTypeTag = $r->queryTypeTag;
		$strQueryTypeClass = $r->queryTypeClass;
		$strQueryTypePlaceholder = $r->queryTypePlaceholder;

		switch($intQueryTypeID)
		{
			case 2:
				list($strQueryTypeText, $strQueryTypeMin, $strQueryTypeMax, $strQueryTypeDefault) = explode("|", $strQueryTypeText);
			break;

			case 10:
			case 11:
				list($strQueryTypeText, $strQueryTypeSelect) = explode(":", $strQueryTypeText);
			break;
		}

		if(isset($_GET['btnFieldCopy']))
		{
			$intQuery2TypeID = "";
		}
	}

	echo "<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." ".$strFormName : __("Add New", 'lang_form'))."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		if($obj_form->id > 0)
		{
			echo "<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox".($intQuery2TypeID > 0 ? " active" : "")."'>
						<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
						<div class='inside'>
							<form method='post' action='' class='mf_form mf_settings'>
								<div class='alignleft'>";

									if($intQueryTypeID == '')
									{
										$intQueryTypeID = $wpdb->get_var("SELECT queryTypeID FROM ".$wpdb->base_prefix."query2type WHERE userID = '".get_current_user_id()."' ORDER BY query2TypeCreated DESC");
									}

									if($intQueryTypeID == 13)
									{
										$strQueryTypeName = $obj_form->get_type_name($intQueryTypeID);

										echo show_textfield(array('name' => 'intQueryTypeID_name', 'text' => __("Type", 'lang_form'), 'value' => $strQueryTypeName, 'xtra' => "readonly"))
										.input_hidden(array('name' => 'intQueryTypeID', 'value' => $intQueryTypeID, 'xtra' => "id='intQueryTypeID'"));
									}

									else
									{
										$arr_data = array();

										$arr_data[] = array("", "-- ".__("Choose here", 'lang_form')." --");

										$result = $wpdb->get_results("SELECT queryTypeID, queryTypeName, COUNT(queryTypeID) AS queryType_amount FROM ".$wpdb->base_prefix."query_type LEFT JOIN ".$wpdb->base_prefix."query2type USING (queryTypeID) WHERE queryTypePublic = 'yes' GROUP BY queryTypeID ORDER BY queryType_amount DESC, queryTypeName ASC");

										foreach($result as $r)
										{
											if($intQueryTypeID > 0 || $r->queryTypeID != 13)
											{
												$arr_data[] = array($r->queryTypeID, $r->queryTypeName);
											}
										}

										echo show_select(array('data' => $arr_data, 'name' => 'intQueryTypeID', 'compare' => $intQueryTypeID, 'text' => __("Type", 'lang_form')));
									}

									echo show_textarea(array('name' => 'strQueryTypeText', 'text' => __("Text", 'lang_form'), 'value' => $strQueryTypeText, 'class' => "tr_text")); //, 'wysiwyg' => true, 'size' => 'small'

									$arr_data = array();

									$arr_data[] = array('div', "div");
									$arr_data[] = array('fieldset', "fieldset");

									echo show_select(array('data' => $arr_data, 'name' => 'strQueryTypeText2', 'compare' => $strQueryTypeText, 'text' => __("Type", 'lang_form'), 'class' => "tr_tag2"))
								."</div>
								<div class='alignright'>";

									$arr_data = array();

									$arr_data[] = array("", "-- ".__("Choose here", 'lang_form')." --");

									$result = $wpdb->get_results("SELECT checkID, checkName FROM ".$wpdb->base_prefix."query_check WHERE checkPublic = '1' ORDER BY checkName ASC");
									$rows = $wpdb->num_rows;

									foreach($result as $r)
									{
										$arr_data[] = array($r->checkID, __($r->checkName, 'lang_form'));
									}

									echo show_select(array('data' => $arr_data, 'name' => "intCheckID", 'compare' => $intCheckID, 'text' => __("Validate as", 'lang_form'), 'class' => "tr_check"))
									.show_textfield(array('name' => 'strQueryTypePlaceholder', 'text' => __("Placeholder Text", 'lang_form'), 'value' => $strQueryTypePlaceholder, 'placeholder' => __("Feel free to write anything you like here", 'lang_form'), 'maxlength' => 100, 'xtra_class' => "tr_placeholder"))
									.show_textfield(array('name' => 'strQueryTypeTag', 'text' => __("Custom HTML Tag", 'lang_form'), 'value' => $strQueryTypeTag, 'placeholder' => "h1, h2, h3, h4, h5, p, blockquote", 'maxlength' => 20, 'xtra_class' => "tr_tag"))
									.show_textfield(array('name' => 'strQueryTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $strQueryTypeClass, 'placeholder' => "bold italic", 'maxlength' => 50))
									."<div class='tr_range'>"
										.show_textfield(array('name' => 'strQueryTypeMin', 'text' => __("Min value", 'lang_form'), 'value' => $strQueryTypeMin, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strQueryTypeMax', 'text' => __("Max value", 'lang_form'), 'value' => $strQueryTypeMax, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strQueryTypeDefault', 'text' => __("Default value", 'lang_form'), 'value' => $strQueryTypeDefault, 'maxlength' => 3, 'size' => 5))
									."</div>
									<div class='tr_select'>
										<label>".__("Value", 'lang_form')."</label>
										<div class='select_rows'>";

											if($strQueryTypeSelect == '')
											{
												$strQueryTypeSelect = "|";
											}

											$arr_select_rows = explode(",", $strQueryTypeSelect);

											foreach($arr_select_rows as $select_row)
											{
												$arr_select_row_content = explode("|", $select_row);

												echo "<div>"
													//input text is needed when using select as payment price
													.show_textfield(array('name' => 'strQueryTypeSelect_id', 'value' => $arr_select_row_content[0]))
													//.input_hidden(array('name' => 'strQueryTypeSelect_id', 'value' => $arr_select_row_content[0]))
													.show_textfield(array('name' => 'strQueryTypeSelect_value', 'value' => $arr_select_row_content[1], 'placeholder' => __("Enter option here", 'lang_form')))
												."</div>";
											}

										echo "</div>"
										.input_hidden(array('name' => 'strQueryTypeSelect', 'value' => $strQueryTypeSelect))
									."</div>
								</div>
								<div class='clear'></div>"
								.show_submit(array('name' => "btnFormAdd", 'text' => ($intQuery2TypeID > 0 ? __("Update", 'lang_form') : __("Add", 'lang_form'))));

								if($intQuery2TypeID > 0)
								{
									echo "&nbsp;<a href='?page=mf_form/create/index.php&intQueryID=".$obj_form->id."'>"
										.show_submit(array('text' => __("Cancel", 'lang_form'), 'type' => "button", 'class' => "button"))
									."</a>"
									.input_hidden(array('name' => 'intQuery2TypeID', 'value' => $intQuery2TypeID));
								}

								echo input_hidden(array('name' => 'intQueryID', 'value' => $obj_form->id))
								.wp_nonce_field('form_add', '_wpnonce', true, false)
							."</form>
						</div>
					</div>";

					$form_output = show_query_form(array('query_id' => $obj_form->id, 'edit' => true, 'query2type_id' => $intQuery2TypeID));

					if($form_output != '')
					{
						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("Overview", 'lang_form')."</span></h3>
							<div class='inside'>"
								.$form_output
							."</div>
						</div>";
					}

				echo "</div>
				<div id='postbox-container-1'>
					<form method='post' action='' class='mf_form mf_settings'>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Publish", 'lang_form')."</span></h3>
							<div class='inside'>"
								.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $strFormName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intQuery2TypeID > 0 ? "" : "autofocus")))
								.show_textfield(array('name' => 'strFormURL', 'text' => __("Permalink", 'lang_form'), 'value' => $strFormURL, 'maxlength' => 100, 'required' => 1));

								if($obj_form->is_published() == "publish")
								{
									echo show_textfield(array('name' => 'dteFormDeadline', 'text' => __("Deadline", 'lang_form'), 'value' => $dteFormDeadline, 'type' => "date"));
								}

								echo "<div>"
									.show_submit(array('name' => "btnFormPublish", 'text' =>  __("Publish", 'lang_form')))."&nbsp;"
									.show_submit(array('name' => "btnFormDraft", 'text' => __("Save Draft", 'lang_form'), 'class' => "button"))
								."</div>"
								.input_hidden(array('name' => "intQueryID", 'value' => $obj_form->id))
								.wp_nonce_field('form_update', '_wpnonce', true, false);

								if($obj_form->is_published() == "publish")
								{
									$post_url = get_permalink($obj_form->post_id);

									if($post_url != '')
									{
										echo "<br>
										<a href='".$post_url."'>".__("View form", 'lang_form')."</a>";
									}
								}

							echo "</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Settings", 'lang_form')."</span></h3>
							<div class='inside'>";

								$arr_data = array();

								$arr_data[] = array("", "-- ".__("Choose page here", 'lang_form')." --");

								$arr_sites = array();

								if(is_multisite())
								{
									$result = $wpdb->get_results("SELECT blog_id, domain FROM ".$wpdb->base_prefix."blogs ORDER BY blog_id ASC");

									foreach($result as $r)
									{
										$blog_id = $r->blog_id;
										$domain = $r->domain;

										if(IS_ADMIN || $blog_id == $wpdb->blogid)
										{
											$arr_sites[$blog_id] = $domain;
										}
									}
								}

								else
								{
									$arr_sites[0] = "";
								}

								foreach($arr_sites as $key => $value)
								{
									$blog_id = $key;
									$domain = $value;

									if($blog_id > 0)
									{
										//Switch to temp site
										####################
										$wpdbobj = clone $wpdb;
										$wpdb->blogid = $blog_id;
										$wpdb->set_prefix($wpdb->base_prefix);
										####################

										$post_prefix = $blog_id."_";
									}

									else
									{
										$post_prefix = "";
									}

									$resultPosts = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND post_parent = '0' AND post_title != '' ORDER BY menu_order ASC");

									if($wpdb->num_rows > 0)
									{
										if($blog_id > 0)
										{
											$arr_data[] = array("opt_start", $domain);
										}

											foreach($resultPosts as $r)
											{
												$post_id = $r->ID;
												$post_title = $r->post_title;

												$arr_data[] = array($post_prefix.$post_id, $post_title);

												$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND post_parent = '%d' AND post_title != '' ORDER BY menu_order ASC", $post_id));

												foreach($result2 as $r)
												{
													$post_id = $r->ID;
													$post_title = $r->post_title;

													$arr_data[] = array($post_prefix.$post_id, "&nbsp;&nbsp;&nbsp;&nbsp;".$post_title);
												}
											}

										if($blog_id > 0)
										{
											$arr_data[] = array("opt_end", "");
										}
									}

									if($blog_id > 0)
									{
										//Switch back to orig site
										###################
										$wpdb = clone $wpdbobj;
										###################
									}
								}

								echo show_select(array('data' => $arr_data, 'name' => 'strQueryAnswerURL', 'compare' => $strQueryAnswerURL, 'text' => __("Confirmation page", 'lang_form')));

								$has_email_field = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queryTypeID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_check USING (checkID) WHERE queryID = '%d' AND queryTypeID = '3' AND checkCode = 'email'", $obj_form->id));

								if($has_email_field > 0)
								{
									echo show_checkbox(array('name' => 'intQueryEmailConfirm', 'text' => __("Send e-mail confirmation to questionnaire", 'lang_form'), 'value' => 1, 'compare' => $intQueryEmailConfirm))
									.show_select(array('data' => $arr_data, 'name' => 'strQueryEmailConfirmPage', 'compare' => $strQueryEmailConfirmPage, 'text' => __("E-mail confirmation content", 'lang_form'), 'class' => "query_email_confirm_page".($intQueryEmailConfirm == 1 ? " " : " hide"), 'description' => __("If you don't choose a page, the content of the form will be sent as content", 'lang_form')));
								}

								$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID", $obj_form->id));
								$intQueryTotal = $wpdb->num_rows;
								
								if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'required' => true, 'check_code' => 'email')))
								{
									echo show_checkbox(array('name' => 'strQueryEmailCheckConfirm', 'text' => __("Make questionnaire confirm their e-mail", 'lang_form'), 'value' => 'yes', 'compare' => $strQueryEmailCheckConfirm));
								}

								$is_poll = $obj_form->is_poll(); //array('query_id' => $obj_form->id)

								if($is_poll)
								{
									echo show_checkbox(array('name' => 'intQueryShowAnswers', 'text' => __("Show Answers", 'lang_form'), 'value' => 1, 'compare' => $intQueryShowAnswers));
								}

								echo show_textfield(array('name' => 'strQueryEmail', 'text' => __("Send e-mail from/to", 'lang_form'), 'value' => $strQueryEmail, 'maxlength' => 100));

								if($strQueryEmail != '')
								{
									echo show_checkbox(array('name' => 'intQueryEmailNotify', 'text' => __("Send notification on new answer", 'lang_form'), 'value' => 1, 'compare' => $intQueryEmailNotify));
								}

								else
								{
									echo input_hidden(array('name' => "intQueryEmailNotify", 'value' => 1));
								}

								if($strQueryEmailName != '')
								{
									echo show_textfield(array('name' => 'strQueryEmailName', 'text' => __("Subject", 'lang_form'), 'value' => $strQueryEmailName, 'maxlength' => 100));
								}

							echo "</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Button", 'lang_form')."</span></h3>
							<div class='inside'>";

								$arr_data = array();

								$arr_data[] = array("", "-- ".__("Choose here", 'lang_form')." --");

								$obj_font_icons = new mf_font_icons();
								$arr_icons = $obj_font_icons->get_array();

								foreach($arr_icons as $icon)
								{
									$arr_data[] = $icon;
								}

								echo show_select(array('data' => $arr_data, 'name' => 'strQueryButtonSymbol', 'compare' => $strQueryButtonSymbol, 'text' => __("Symbol", 'lang_form')))
								.show_textfield(array('name' => 'strQueryButtonText', 'text' => __("Text", 'lang_form'), 'value' => $strQueryButtonText, 'placeholder' => __("Submit", 'lang_form'), 'maxlength' => 100))
							."</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Payment", 'lang_form')."</span></h3>
							<div class='inside'>";

								$arr_data = array();

								$arr_data[] = array("", "-- ".__("Choose here", 'lang_form')." --");
								$arr_data[] = array(1, __("DIBS", 'lang_form'));
								$arr_data[] = array(3, __("Paypal", 'lang_form'));
								$arr_data[] = array(2, __("Skrill", 'lang_form'));

								echo show_select(array('data' => $arr_data, 'name' => 'intQueryPaymentProvider', 'compare' => $intQueryPaymentProvider, 'text' => __("Provider", 'lang_form')));

								if($intQueryPaymentProvider == 1)
								{
									echo show_textfield(array('name' => 'strQueryPaymentMerchant', 'text' => __("Merchant ID", 'lang_form'), 'value' => $strQueryPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentHmac', 'text' => __("HMAC key", 'lang_form'), 'value' => $strQueryPaymentHmac, 'maxlength' => 200));
								}

								else if($intQueryPaymentProvider == 3)
								{
									echo show_textfield(array('name' => 'strQueryPaymentMerchant', 'text' => __("Username", 'lang_form'), 'value' => $strQueryPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentPassword', 'text' => __("Password", 'lang_form'), 'value' => $strQueryPaymentPassword, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentHmac', 'text' => __("Signature", 'lang_form'), 'value' => $strQueryPaymentHmac, 'maxlength' => 200));
								}

								else if($intQueryPaymentProvider == 2)
								{
									echo show_textfield(array('name' => 'strQueryPaymentMerchant', 'text' => __("Merchant E-mail", 'lang_form'), 'value' => $strQueryPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentHmac', 'text' => __("Secret word", 'lang_form'), 'value' => $strQueryPaymentHmac, 'maxlength' => 200));
								}

								if($intQueryPaymentProvider > 0 && $strQueryPaymentMerchant != '' && $strQueryPaymentHmac != '')
								{
									$arr_data = array();

									$arr_data[] = array("", "-- ".__("Choose here", 'lang_form')." --");

									switch($intQueryPaymentProvider)
									{
										case 1:
											$arr_data[] = array(208, __("Danish Krone", 'lang_form')." (DKK)");
											$arr_data[] = array(978, __("Euro", 'lang_form')." (EUR)");
											$arr_data[] = array(840, __("US Dollar", 'lang_form')." (USD)");
											$arr_data[] = array(826, __("English Pound", 'lang_form')." (GBP)");
											$arr_data[] = array(752, __("Swedish Krona", 'lang_form')." (SEK)");
											$arr_data[] = array(036, __("Australian Dollar", 'lang_form')." (AUD)");
											$arr_data[] = array(124, __("Canadian Dollar", 'lang_form')." (CAD)");
											$arr_data[] = array(352, __("Icelandic Krona", 'lang_form')." (ISK)");
											$arr_data[] = array(392, __("Japanese Yen", 'lang_form')." (JPY)");
											$arr_data[] = array(554, __("New Zealand Dollar", 'lang_form')." (NZD)");
											$arr_data[] = array(578, __("Norwegian Krone", 'lang_form')." (NOK)");
											$arr_data[] = array(756, __("Swiss Franc", 'lang_form')." (CHF)");
											$arr_data[] = array(949, __("Turkish Lira", 'lang_form')." (TRY)");
										break;

										case 2:
										case 3:
											$arr_data[] = array("DKK", __("Danish Krone", 'lang_form')." (DKK)");
											$arr_data[] = array("EUR", __("Euro", 'lang_form')." (EUR)");
											$arr_data[] = array("USD", __("US Dollar", 'lang_form')." (USD)");
											$arr_data[] = array("GBP", __("English Pound", 'lang_form')." (GBP)");
											$arr_data[] = array("SEK", __("Swedish Krona", 'lang_form')." (SEK)");
											$arr_data[] = array("AUD", __("Australian Dollar", 'lang_form')." (AUD)");
											$arr_data[] = array("CAD", __("Canadian Dollar", 'lang_form')." (CAD)");
											$arr_data[] = array("ISK", __("Icelandic Krona", 'lang_form')." (ISK)");
											$arr_data[] = array("JPY", __("Japanese Yen", 'lang_form')." (JPY)");
											$arr_data[] = array("NZD", __("New Zealand Dollar", 'lang_form')." (NZD)");
											$arr_data[] = array("NOK", __("Norwegian Krone", 'lang_form')." (NOK)");
											$arr_data[] = array("CHF", __("Swiss Franc", 'lang_form')." (CHF)");
											$arr_data[] = array("TRY", __("Turkish Lira", 'lang_form')." (TRY)");
										break;
									}

									$arr_data = array_sort(array('array' => $arr_data, 'on' => 1));

									echo show_select(array('data' => $arr_data, 'name' => 'strQueryPaymentCurrency', 'compare' => $strQueryPaymentCurrency, 'text' => __("Currency", 'lang_form')));

									$arr_data = array();

									$arr_data[] = array("", "-- ".__("Choose here", 'lang_form')." --");

									list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(10, 12)));

									foreach($result as $r)
									{
										if($r->queryTypeID == 10)
										{
											list($strQueryTypeText_temp, $rest) = explode(":", $r->queryTypeText);
										}

										else
										{
											$strQueryTypeText_temp = $r->queryTypeText;
										}

										$arr_data[] = array($r->query2TypeID, $strQueryTypeText_temp);
									}

									echo show_select(array('data' => $arr_data, 'name' => 'intQueryPaymentAmount', 'compare' => $intQueryPaymentAmount, 'text' => __("Field for payment cost", 'lang_form')));
								}

							echo "</div>
						</div>
					</form>
				</div>
			</div>";
		}

		else
		{
			echo "<form method='post' action='' class='mf_form mf_settings'>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Add new", 'lang_form')."</span></h3>
					<div class='inside'>"
						.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $strFormName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intQuery2TypeID > 0 ? "" : "autofocus")))
						.show_submit(array('name' => "btnFormPublish", 'text' => __("Add", 'lang_form')))
						.input_hidden(array('name' => "intQueryID", 'value' => $obj_form->id))
						.wp_nonce_field('form_update', '_wpnonce', true, false)
					."</div>
				</div>
			</form>";
		}

	echo "</div>
</div>";