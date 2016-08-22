<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
wp_enqueue_script('jquery-ui-sortable');
mf_enqueue_script('script_touch', plugins_url()."/mf_base/include/jquery.ui.touch-punch.min.js");
mf_enqueue_script('script_forms', plugins_url()."/mf_form/include/script.js", array('plugins_url' => plugins_url(), 'plugin_url' => plugin_dir_url(__FILE__)));
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')));

$obj_form = new mf_form();

$strFormName = check_var('strFormName');
$strFormURL = check_var('strFormURL');
$dteFormDeadline = check_var('dteFormDeadline');

$intQuery2TypeID = check_var('intQuery2TypeID');
$intQuery2TypeOrder = check_var('intQuery2TypeOrder');

$intQueryEmailConfirm = isset($_POST['intQueryEmailConfirm']) ? 1 : 0;
$intQueryEmailConfirmPage = check_var('intQueryEmailConfirmPage');
$intQueryShowAnswers = isset($_POST['intQueryShowAnswers']) ? 1 : 0;
$strQueryAnswerURL = check_var('strQueryAnswerURL');
$strQueryEmail = check_var('strQueryEmail', 'email');
$intQueryEmailNotify = check_var('intQueryEmailNotify');
$intQueryEmailNotifyPage = check_var('intQueryEmailNotifyPage');
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
$strQueryTypePlaceholder = check_var('strQueryTypePlaceholder');
$strQueryTypeTag = check_var('strQueryTypeTag');
$strQueryTypeClass = check_var('strQueryTypeClass');
$strQueryTypeFetchFrom = check_var('strQueryTypeFetchFrom');
$strQueryTypeActionEquals = check_var('strQueryTypeActionEquals');
$intQueryTypeActionShow = check_var('intQueryTypeActionShow');

$strQueryTypeSelect = check_var('strQueryTypeSelect', '', true, "0|-- ".__("Choose here", 'lang_form')." --,1|".__("No", 'lang_form').",2|".__("Yes", 'lang_form'));
$strQueryTypeMin = check_var('strQueryTypeMin', '', true, "0");
$strQueryTypeMax = check_var('strQueryTypeMax', '', true, 100);
$strQueryTypeDefault = check_var('strQueryTypeDefault', '', true, 1);

$error_text = $done_text = "";

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

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET blogID = '%d', queryEmailCheckConfirm = %s, queryEmailConfirm = '%d', queryEmailConfirmPage = %s, queryShowAnswers = '%d', queryName = %s, queryAnswerURL = %s, queryEmail = %s, queryEmailNotify = '%d', queryEmailNotifyPage = %s, queryEmailName = %s, queryMandatoryText = %s, queryButtonText = %s, queryButtonSymbol = %s, queryPaymentProvider = '%d', queryPaymentHmac = %s, queryPaymentMerchant = %s, queryPaymentPassword = %s, queryPaymentCurrency = %s, queryPaymentAmount = '%d' WHERE queryID = '%d' AND queryDeleted = '0'", $wpdb->blogid, $strQueryEmailCheckConfirm, $intQueryEmailConfirm, $intQueryEmailConfirmPage, $intQueryShowAnswers, $strFormName, $strQueryAnswerURL, $strQueryEmail, $intQueryEmailNotify, $intQueryEmailNotifyPage, $strQueryEmailName, $strQueryMandatoryText, $strQueryButtonText, $strQueryButtonSymbol, $intQueryPaymentProvider, $strQueryPaymentHmac, $strQueryPaymentMerchant, $strQueryPaymentPassword, $strQueryPaymentCurrency, $intQueryPaymentAmount, $obj_form->id));
		}

		else
		{
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
			echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intQueryID=".$obj_form->id)."'</script>";
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
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, queryTypeFetchFrom = %s, queryTypeActionEquals = %s, queryTypeActionShow = %s, userID = '%d' WHERE query2TypeID = '%d'", $intQueryTypeID, $strQueryTypeText, $strQueryTypePlaceholder, $intCheckID, $strQueryTypeTag, $strQueryTypeClass, $strQueryTypeFetchFrom, $strQueryTypeActionEquals, $intQueryTypeActionShow, get_current_user_id(), $intQuery2TypeID));

				if($intQueryTypeID == 13)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeText = %s, userID = '%d' WHERE query2TypeID2 = '%d'", $strQueryTypeText, get_current_user_id(), $intQuery2TypeID)); //, queryTypeClass = %s, queryTypeFetchFrom = %s, $strQueryTypeClass, $strQueryTypeFetchFrom
				}

				$intQuery2TypeID = $intQueryTypeID = $strQueryTypeText = $strQueryTypePlaceholder = $intCheckID = $strQueryTypeTag = $strQueryTypeClass = $strQueryTypeFetchFrom = $strQueryTypeActionEquals = $intQueryTypeActionShow = "";
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

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET queryID = '%d', queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, queryTypeFetchFrom = %s, queryTypeActionEquals = %s, queryTypeActionShow = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $obj_form->id, $intQueryTypeID, $strQueryTypeText, $strQueryTypePlaceholder, $intCheckID, $strQueryTypeTag, $strQueryTypeClass, $strQueryTypeFetchFrom, $strQueryTypeActionEquals, $intQueryTypeActionShow, $intQuery2TypeOrder, get_current_user_id()));

				if($intQueryTypeID == 13)
				{
					$intQuery2TypeID = $wpdb->insert_id;
					$intQueryTypeID = 14;
					$intQuery2TypeOrder++;

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET query2TypeID2 = '%d', queryID = '%d', queryTypeID = '%d', queryTypeText = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $intQuery2TypeID, $obj_form->id, $intQueryTypeID, $strQueryTypeText, $intQuery2TypeOrder, get_current_user_id())); //, queryTypeClass = %s, queryTypeFetchFrom = %s, $strQueryTypeClass, $strQueryTypeFetchFrom
				}

				if($wpdb->rows_affected > 0)
				{
					$intQuery2TypeID = $intQueryTypeID = $strQueryTypeText = $strQueryTypePlaceholder = $intCheckID = $strQueryTypeTag = $strQueryTypeClass = $strQueryTypeFetchFrom = $strQueryTypeActionEquals = $intQueryTypeActionShow = "";
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
		echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intQueryID=".$obj_form->id)."'</script>";
	}
}

if($obj_form->id > 0)
{
	if(isset($_GET['recover']))
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryDeleted = '0' WHERE queryID = '%d'", $obj_form->id));
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT queryEmailCheckConfirm, queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailNotifyPage, queryEmailName, queryMandatoryText, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryPaymentAmount, queryCreated FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id));
	$r = $result[0];
	$strQueryEmailCheckConfirm = $r->queryEmailCheckConfirm;
	$intQueryEmailConfirm = $r->queryEmailConfirm;
	$intQueryEmailConfirmPage = $r->queryEmailConfirmPage;
	$intQueryShowAnswers = $r->queryShowAnswers;
	$strQueryAnswerURL = $r->queryAnswerURL;
	$strQueryEmail = $r->queryEmail;
	$intQueryEmailNotify = $r->queryEmailNotify;
	$intQueryEmailNotifyPage = $r->queryEmailNotifyPage;
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
	$result = $wpdb->get_results($wpdb->prepare("SELECT queryTypeID, queryTypeText, queryTypePlaceholder, checkID, queryTypeTag, queryTypeClass, queryTypeFetchFrom, queryTypeActionEquals, queryTypeActionShow FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $intQuery2TypeID));
	$r = $result[0];
	$intQueryTypeID = $r->queryTypeID;
	$strQueryTypeText = $r->queryTypeText;
	$strQueryTypePlaceholder = $r->queryTypePlaceholder;
	$intCheckID = $r->checkID;
	$strQueryTypeTag = $r->queryTypeTag;
	$strQueryTypeClass = $r->queryTypeClass;
	$strQueryTypeFetchFrom = $r->queryTypeFetchFrom;
	$strQueryTypeActionEquals = $r->queryTypeActionEquals;
	$intQueryTypeActionShow = $r->queryTypeActionShow;

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

echo "<div class='wrap'>
	<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." ".$strFormName : __("Add New", 'lang_form'))."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		if($obj_form->id > 0)
		{
			echo "<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox".($intQuery2TypeID > 0 ? " active" : "")."'>
						<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
						<form method='post' action='".admin_url("admin.php?page=mf_form%2Fcreate%2Findex.php&intQueryID=".$obj_form->id)."' class='mf_form mf_settings inside'>
							<div class='flex_flow'>
								<div>";

									if($intQueryTypeID == '')
									{
										$intQueryTypeID = $wpdb->get_var($wpdb->prepare("SELECT queryTypeID FROM ".$wpdb->base_prefix."query2type WHERE userID = '%d' ORDER BY query2TypeCreated DESC", get_current_user_id()));
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
										$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

										$result = $wpdb->get_results("SELECT queryTypeID, queryTypeName, COUNT(queryTypeID) AS queryType_amount FROM ".$wpdb->base_prefix."query_type LEFT JOIN ".$wpdb->base_prefix."query2type USING (queryTypeID) WHERE queryTypePublic = 'yes' GROUP BY queryTypeID ORDER BY queryType_amount DESC, queryTypeName ASC");

										foreach($result as $r)
										{
											if($intQueryTypeID > 0 || $r->queryTypeID != 13)
											{
												$arr_data[$r->queryTypeID] = $r->queryTypeName;
											}
										}

										echo show_select(array('data' => $arr_data, 'name' => 'intQueryTypeID', 'compare' => $intQueryTypeID, 'text' => __("Type", 'lang_form')));
									}

									echo show_textarea(array('name' => 'strQueryTypeText', 'text' => __("Text", 'lang_form'), 'value' => $strQueryTypeText, 'class' => "show_textarea hide")) //, 'wysiwyg' => true, 'size' => 'small'
								."</div>
								<div>";

									$result = $wpdb->get_results("SELECT checkID, checkName FROM ".$wpdb->base_prefix."query_check WHERE checkPublic = '1' ORDER BY checkName ASC");
									
									if($wpdb->num_rows > 0)
									{
										$arr_data = array();
										$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

										foreach($result as $r)
										{
											$arr_data[$r->checkID] = __($r->checkName, 'lang_form');
										}

										echo show_select(array('data' => $arr_data, 'name' => "intCheckID", 'compare' => $intCheckID, 'text' => __("Validate as", 'lang_form'), 'class' => "show_validate_as hide"));
									}

									echo show_textfield(array('name' => 'strQueryTypePlaceholder', 'text' => __("Placeholder Text", 'lang_form'), 'value' => $strQueryTypePlaceholder, 'placeholder' => __("Feel free to write anything you like here", 'lang_form'), 'maxlength' => 100, 'xtra_class' => "show_placeholder"));

									$arr_data = array(
										'div' => "div",
										'fieldset' => "fieldset",
									);

									echo show_select(array('data' => $arr_data, 'name' => 'strQueryTypeText2', 'compare' => $strQueryTypeText, 'text' => __("Type", 'lang_form'), 'class' => "show_custom_tag hide"))
									."<div class='show_range flex_flow hide'>"
										.show_textfield(array('name' => 'strQueryTypeMin', 'text' => __("Min value", 'lang_form'), 'value' => $strQueryTypeMin, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strQueryTypeMax', 'text' => __("Max value", 'lang_form'), 'value' => $strQueryTypeMax, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strQueryTypeDefault', 'text' => __("Default value", 'lang_form'), 'value' => $strQueryTypeDefault, 'maxlength' => 3, 'size' => 5))
									."</div>"
									."<div class='show_select'>
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
									."</div>";

									//Advanced
									#################
									echo "<a href='#' class='toggler'><h3>".__("Advanced", 'lang_form')."</h3></a>
									<div>";

										$arr_data = array(
											'' => "-- ".__("Choose here", 'lang_form')." --",
											'h1' => "h1",
											'h2' => "h2",
											'h3' => "h3",
											'h4' => "h4",
											'h5' => "h5",
											'p' => "p",
											'blockquote' => "blockquote",
										);

										echo show_select(array('data' => $arr_data, 'name' => 'strQueryTypeTag', 'compare' => $strQueryTypeTag, 'text' => __("Custom HTML Tag", 'lang_form'), 'class' => "show_custom_text_tag hide"))
										.show_textfield(array('name' => 'strQueryTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $strQueryTypeClass, 'placeholder' => "bold italic aligncenter alignleft alignright", 'maxlength' => 50, 'xtra_class' => "show_custom_class hide"))
										.show_textfield(array('name' => 'strQueryTypeFetchFrom', 'text' => __("Fetch From ID", 'lang_form'), 'value' => $strQueryTypeFetchFrom, 'maxlength' => 50, 'xtra_class' => "show_fetch_from hide"));

										if($intQuery2TypeID > 0)
										{
											$arr_data_equals = array();

											foreach($arr_select_rows as $select_row)
											{
												$arr_select_row_content = explode("|", $select_row);

												$arr_data_equals[$arr_select_row_content[0]] = $arr_select_row_content[1];
											}

											if(count($arr_data_equals) > 0)
											{
												list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(1, 4, 5, 10, 11, 13), 'query_exclude_id' => $intQuery2TypeID));
												$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

												if(count($arr_data_show) > 0)
												{
													echo "<div class='show_actions'>"
														.show_select(array('data' => $arr_data_equals, 'name' => 'strQueryTypeActionEquals', 'text' => __("If this equals...", 'lang_form'), 'compare' => $strQueryTypeActionEquals))
														.show_select(array('data' => $arr_data_show, 'name' => 'intQueryTypeActionShow', 'compare' => $intQueryTypeActionShow, 'text' => __("...show this...", 'lang_form')))
													."</div>";
												}
											}
										}

									echo "</div>";
									#################

								echo "</div>
							</div>"
							.show_submit(array('name' => "btnFormAdd", 'text' => ($intQuery2TypeID > 0 ? __("Update", 'lang_form') : __("Add", 'lang_form'))));

							if($intQuery2TypeID > 0)
							{
								echo "&nbsp;<a href='?page=mf_form/create/index.php&intQueryID=".$obj_form->id."'>"
									.show_submit(array('type' => 'button', 'text' => __("Cancel", 'lang_form'), 'class' => "button"))
								."</a>"
								.input_hidden(array('name' => 'intQuery2TypeID', 'value' => $intQuery2TypeID));
							}

							echo input_hidden(array('name' => 'intQueryID', 'value' => $obj_form->id))
							.wp_nonce_field('form_add', '_wpnonce', true, false)
						."</form>
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

								$form_status = $obj_form->get_form_status();

								if($form_status == "publish")
								{
									echo show_textfield(array('name' => 'dteFormDeadline', 'text' => __("Deadline", 'lang_form'), 'value' => $dteFormDeadline, 'type' => 'date'));
								}

								if($form_output != '')
								{
									echo show_submit(array('name' => "btnFormPublish", 'text' => ($form_status == "publish" ? __("Save", 'lang_form') : __("Publish", 'lang_form'))));
								}

								echo show_submit(array('name' => "btnFormDraft", 'text' => __("Save Draft", 'lang_form'), 'class' => "button"));

								if($form_status == "publish")
								{
									$post_url = get_permalink($obj_form->post_id);

									if($post_url != '')
									{
										echo "<a href='".$post_url."' class='button'>".__("View form", 'lang_form')."</a>";
									}
								}

								echo input_hidden(array('name' => "intQueryID", 'value' => $obj_form->id))
								.wp_nonce_field('form_update', '_wpnonce', true, false)
							."</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Settings", 'lang_form')."</span></h3>
							<div class='inside'>";

								//$arr_data_pages = $obj_form->get_pages_for_select();

								$arr_data = array();
								$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

								get_post_children(array('output_array' => true), $arr_data);

								$arr_data_pages = $arr_data;

								echo show_select(array('data' => $arr_data_pages, 'name' => 'strQueryAnswerURL', 'compare' => $strQueryAnswerURL, 'text' => __("Confirmation page", 'lang_form')));

								if($obj_form->is_poll())
								{
									echo show_checkbox(array('name' => 'intQueryShowAnswers', 'text' => __("Show Answers", 'lang_form'), 'value' => 1, 'compare' => $intQueryShowAnswers));
								}

								echo "<h4>".__("Email", 'lang_form')."</h4>"
								.show_textfield(array('name' => 'strQueryEmail', 'text' => __("Send from/to", 'lang_form'), 'value' => $strQueryEmail, 'maxlength' => 100, 'placeholder' => get_bloginfo('admin_email')));

								if($strQueryEmailName != '')
								{
									echo show_textfield(array('name' => 'strQueryEmailName', 'text' => __("Subject", 'lang_form'), 'value' => $strQueryEmailName, 'maxlength' => 100));
								}

								if($strQueryEmail != '')
								{
									echo show_checkbox(array('name' => 'intQueryEmailNotify', 'text' => __("Notification on new answer", 'lang_form'), 'value' => 1, 'compare' => $intQueryEmailNotify));
								}

								else
								{
									echo input_hidden(array('name' => "intQueryEmailNotify", 'value' => 1));
								}

								echo show_select(array('data' => $arr_data_pages, 'name' => 'intQueryEmailNotifyPage', 'compare' => $intQueryEmailNotifyPage, 'text' => __("Notification template", 'lang_form'), 'class' => "query_email_notify_page".($intQueryEmailNotify == 1 ? " " : " hide")))
								."<h4>".__("Email to visitor", 'lang_form')."</h4>";

								if($obj_form->has_email_field() > 0)
								{
									echo show_checkbox(array('name' => 'intQueryEmailConfirm', 'text' => __("Send confirmation to questionnaire", 'lang_form'), 'value' => 1, 'compare' => $intQueryEmailConfirm))
									.show_select(array('data' => $arr_data_pages, 'name' => 'intQueryEmailConfirmPage', 'compare' => $intQueryEmailConfirmPage, 'text' => __("Confirmation template", 'lang_form'), 'class' => "query_email_confirm_page".($intQueryEmailConfirm == 1 ? " " : " hide"))); //, 'description' => __("If you don't choose a page, the content of the form will be sent as content", 'lang_form')
								}
								
								if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'required' => true, 'check_code' => 'email')))
								{
									echo show_checkbox(array('name' => 'strQueryEmailCheckConfirm', 'text' => __("Make questionnaire confirm their address", 'lang_form'), 'value' => 'yes', 'compare' => $strQueryEmailCheckConfirm));
								}

								echo "<h4>".__("Button", 'lang_form')."</h4>
								<div class='flex_flow'>"
									.show_select(array('data' => $obj_form->get_icons_for_select(), 'name' => 'strQueryButtonSymbol', 'compare' => $strQueryButtonSymbol, 'text' => __("Symbol", 'lang_form')))
									.show_textfield(array('name' => 'strQueryButtonText', 'text' => __("Text", 'lang_form'), 'value' => $strQueryButtonText, 'placeholder' => __("Submit", 'lang_form'), 'maxlength' => 100))
								."</div>
							</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Payment", 'lang_form')."</span></h3>
							<div class='inside'>";

								$arr_data = array();

								$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";
								$arr_data[1] = __("DIBS", 'lang_form');
								$arr_data[3] = __("Paypal", 'lang_form');
								$arr_data[2] = __("Skrill", 'lang_form');

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

									$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

									switch($intQueryPaymentProvider)
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

										case 2:
										case 3:
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

									echo show_select(array('data' => $arr_data, 'name' => 'strQueryPaymentCurrency', 'compare' => $strQueryPaymentCurrency, 'text' => __("Currency", 'lang_form')));

									list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(10, 12)));
									$arr_data = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

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