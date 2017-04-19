<?php

mf_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css", get_plugin_version(__FILE__));
wp_enqueue_script('jquery-ui-sortable');
mf_enqueue_script('script_touch', plugins_url()."/mf_base/include/jquery.ui.touch-punch.min.js", '0.2.2');
mf_enqueue_script('script_forms', plugins_url()."/mf_form/include/script.js", array('plugins_url' => plugins_url(), 'plugin_url' => plugin_dir_url(__FILE__)), get_plugin_version(__FILE__));
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')), get_plugin_version(__FILE__));

$obj_form = new mf_form();

$strFormName = check_var('strFormName');
$strFormURL = check_var('strFormURL');
$dteFormDeadline = check_var('dteFormDeadline');

$intForm2TypeID = check_var('intForm2TypeID');
$intForm2TypeOrder = check_var('intForm2TypeOrder');

$intFormEmailConfirm = isset($_POST['intFormEmailConfirm']) ? 1 : 0;
$intFormEmailConfirmPage = check_var('intFormEmailConfirmPage');
$intFormShowAnswers = isset($_POST['intFormShowAnswers']) ? 1 : 0;
$strFormAnswerURL = check_var('strFormAnswerURL');
$strFormEmail = check_var('strFormEmail', 'email');
$intFormEmailNotify = check_var('intFormEmailNotify');
$intFormEmailNotifyPage = check_var('intFormEmailNotifyPage');
$strFormEmailName = check_var('strFormEmailName');
//$strFormEmailCheckConfirm = check_var('strFormEmailCheckConfirm');
$strFormMandatoryText = check_var('strFormMandatoryText');
$strFormButtonText = check_var('strFormButtonText');
$strFormButtonSymbol = check_var('strFormButtonSymbol');
$intFormPaymentProvider = check_var('intFormPaymentProvider');
$strFormPaymentHmac = check_var('strFormPaymentHmac');
$strFormPaymentMerchant = check_var('strFormPaymentMerchant');
$strFormPaymentPassword = check_var('strFormPaymentPassword');
$strFormPaymentCurrency = check_var('strFormPaymentCurrency');
$intFormPaymentAmount = check_var('intFormPaymentAmount');
$intFormTypeID = check_var('intFormTypeID');
$strFormTypeText = isset($_POST['strFormTypeText']) ? $_POST['strFormTypeText'] : ""; //Allow HTML here
$strFormTypeText2 = check_var('strFormTypeText2');
$intCheckID = check_var('intCheckID');
$strFormTypePlaceholder = check_var('strFormTypePlaceholder');
$strFormTypeTag = check_var('strFormTypeTag');
$strFormTypeClass = check_var('strFormTypeClass');
$strFormTypeFetchFrom = check_var('strFormTypeFetchFrom');
$strFormTypeActionEquals = check_var('strFormTypeActionEquals');
$intFormTypeActionShow = check_var('intFormTypeActionShow');

$strFormTypeSelect = check_var('strFormTypeSelect', '', true, "0|-- ".__("Choose here", 'lang_form')." --,1|".__("No", 'lang_form').",2|".__("Yes", 'lang_form'));
$strFormTypeMin = check_var('strFormTypeMin', '', true, "0");
$strFormTypeMax = check_var('strFormTypeMax', '', true, 100);
$strFormTypeDefault = check_var('strFormTypeDefault', '', true, 1);

$error_text = $done_text = "";

if((isset($_POST['btnFormPublish']) || isset($_POST['btnFormDraft'])) && wp_verify_nonce($_POST['_wpnonce'], 'form_update_'.$obj_form->id))
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

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET blogID = '%d', queryEmailConfirm = '%d', queryEmailConfirmPage = %s, queryShowAnswers = '%d', queryName = %s, queryAnswerURL = %s, queryEmail = %s, queryEmailNotify = '%d', queryEmailNotifyPage = %s, queryEmailName = %s, queryMandatoryText = %s, queryButtonText = %s, queryButtonSymbol = %s, queryPaymentProvider = '%d', queryPaymentHmac = %s, queryPaymentMerchant = %s, queryPaymentPassword = %s, queryPaymentCurrency = %s, queryPaymentAmount = '%d' WHERE queryID = '%d' AND queryDeleted = '0'", $wpdb->blogid, $intFormEmailConfirm, $intFormEmailConfirmPage, $intFormShowAnswers, $strFormName, $strFormAnswerURL, $strFormEmail, $intFormEmailNotify, $intFormEmailNotifyPage, $strFormEmailName, $strFormMandatoryText, $strFormButtonText, $strFormButtonSymbol, $intFormPaymentProvider, $strFormPaymentHmac, $strFormPaymentMerchant, $strFormPaymentPassword, $strFormPaymentCurrency, $intFormPaymentAmount, $obj_form->id)); //queryEmailCheckConfirm = %s, $strFormEmailCheckConfirm, 
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
			echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'</script>";
		}
	}
}

else if(isset($_POST['btnFormAdd']) && wp_verify_nonce($_POST['_wpnonce'], 'form_add_'.$obj_form->id))
{
	//Clean up settings if not used for the specific type of field
	################
	if($intFormTypeID != 3)
	{
		$intCheckID = "";
	}
	################

	if(($intFormTypeID == 10 || $intFormTypeID == 11) && $strFormTypeSelect == "")
	{
		$error_text = __("Please, enter all required fields", 'lang_form');
	}

	else
	{
		switch($intFormTypeID)
		{
			case 2:
				$strFormTypeText = str_replace("|", "", $strFormTypeText)."|".str_replace("|", "", $strFormTypeMin)."|".str_replace("|", "", $strFormTypeMax)."|".str_replace("|", "", $strFormTypeDefault);
			break;

			case 10:
			case 11:
				$strFormTypeText = str_replace(":", "", $strFormTypeText).":".str_replace(":", "", $strFormTypeSelect);
			break;

			case 13:
			case 14:
				$strFormTypeText = $strFormTypeText2;
			break;
		}

		if($intForm2TypeID > 0)
		{
			if($intFormTypeID > 0 && ($intFormTypeID == 6 || $intFormTypeID == 9 || $strFormTypeText != ''))
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, queryTypeFetchFrom = %s, queryTypeActionEquals = %s, queryTypeActionShow = %s, userID = '%d' WHERE query2TypeID = '%d'", $intFormTypeID, $strFormTypeText, $strFormTypePlaceholder, $intCheckID, $strFormTypeTag, $strFormTypeClass, $strFormTypeFetchFrom, $strFormTypeActionEquals, $intFormTypeActionShow, get_current_user_id(), $intForm2TypeID));

				if($intFormTypeID == 13)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeText = %s, userID = '%d' WHERE query2TypeID2 = '%d'", $strFormTypeText, get_current_user_id(), $intForm2TypeID)); //, queryTypeClass = %s, queryTypeFetchFrom = %s, $strFormTypeClass, $strFormTypeFetchFrom
				}

				$intForm2TypeID = $intFormTypeID = $strFormTypeText = $strFormTypePlaceholder = $intCheckID = $strFormTypeTag = $strFormTypeClass = $strFormTypeFetchFrom = $strFormTypeActionEquals = $intFormTypeActionShow = "";
			}

			else
			{
				$error_text = __("Couldn't update the field", 'lang_form');
			}
		}

		else
		{
			if($obj_form->id > 0 && $intFormTypeID > 0 && ($intFormTypeID == 6 || $intFormTypeID == 9 || $strFormTypeText != ''))
			{
				$intForm2TypeOrder = $wpdb->get_var($wpdb->prepare("SELECT query2TypeOrder + 1 FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeOrder DESC", $obj_form->id));

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET queryID = '%d', queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, queryTypeFetchFrom = %s, queryTypeActionEquals = %s, queryTypeActionShow = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $obj_form->id, $intFormTypeID, $strFormTypeText, $strFormTypePlaceholder, $intCheckID, $strFormTypeTag, $strFormTypeClass, $strFormTypeFetchFrom, $strFormTypeActionEquals, $intFormTypeActionShow, $intForm2TypeOrder, get_current_user_id()));

				if($intFormTypeID == 13)
				{
					$intForm2TypeID = $wpdb->insert_id;
					$intFormTypeID = 14;
					$intForm2TypeOrder++;

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET query2TypeID2 = '%d', queryID = '%d', queryTypeID = '%d', queryTypeText = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $intForm2TypeID, $obj_form->id, $intFormTypeID, $strFormTypeText, $intForm2TypeOrder, get_current_user_id()));
				}

				if($wpdb->rows_affected > 0)
				{
					$intForm2TypeID = $intFormTypeID = $strFormTypeText = $strFormTypePlaceholder = $intCheckID = $strFormTypeTag = $strFormTypeClass = $strFormTypeFetchFrom = $strFormTypeActionEquals = $intFormTypeActionShow = "";
				}
			}

			else
			{
				$error_text = __("I could not insert the new field for you", 'lang_form');
			}
		}
	}

	if($intFormTypeID == 0)
	{
		echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'</script>";
	}
}

if($obj_form->id > 0)
{
	if(isset($_GET['recover']))
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryDeleted = '0' WHERE queryID = '%d'", $obj_form->id));
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailNotifyPage, queryEmailName, queryMandatoryText, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryPaymentAmount, queryCreated FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id)); //queryEmailCheckConfirm, 
	$r = $result[0];
	//$strFormEmailCheckConfirm = $r->queryEmailCheckConfirm;
	$intFormEmailConfirm = $r->queryEmailConfirm;
	$intFormEmailConfirmPage = $r->queryEmailConfirmPage;
	$intFormShowAnswers = $r->queryShowAnswers;
	$strFormAnswerURL = $r->queryAnswerURL;
	$strFormEmail = $r->queryEmail;
	$intFormEmailNotify = $r->queryEmailNotify;
	$intFormEmailNotifyPage = $r->queryEmailNotifyPage;
	$strFormEmailName = $r->queryEmailName;
	$strFormMandatoryText = $r->queryMandatoryText;
	$strFormButtonText = $r->queryButtonText;
	$strFormButtonSymbol = $r->queryButtonSymbol;
	$intFormPaymentProvider = $r->queryPaymentProvider;
	$strFormPaymentHmac = $r->queryPaymentHmac;
	$strFormPaymentMerchant = $r->queryPaymentMerchant;
	$strFormPaymentPassword = $r->queryPaymentPassword;
	$strFormPaymentCurrency = $r->queryPaymentCurrency;
	$intFormPaymentAmount = $r->queryPaymentAmount;
	$strFormCreated = $r->queryCreated;

	$strFormName = $obj_form->get_post_info(array('select' => "post_title"));
	$strFormURL = $obj_form->get_post_info();
	$dteFormDeadline = $obj_form->meta(array('action' => 'get', 'key' => 'deadline'));
}

if($intForm2TypeID > 0)
{
	$result = $wpdb->get_results($wpdb->prepare("SELECT queryTypeID, queryTypeText, queryTypePlaceholder, checkID, queryTypeTag, queryTypeClass, queryTypeFetchFrom, queryTypeActionEquals, queryTypeActionShow FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $intForm2TypeID));
	$r = $result[0];
	$intFormTypeID = $r->queryTypeID;
	$strFormTypeText = $r->queryTypeText;
	$strFormTypePlaceholder = $r->queryTypePlaceholder;
	$intCheckID = $r->checkID;
	$strFormTypeTag = $r->queryTypeTag;
	$strFormTypeClass = $r->queryTypeClass;
	$strFormTypeFetchFrom = $r->queryTypeFetchFrom;
	$strFormTypeActionEquals = $r->queryTypeActionEquals;
	$intFormTypeActionShow = $r->queryTypeActionShow;

	switch($intFormTypeID)
	{
		case 2:
			list($strFormTypeText, $strFormTypeMin, $strFormTypeMax, $strFormTypeDefault) = explode("|", $strFormTypeText);
		break;

		case 10:
		case 11:
			list($strFormTypeText, $strFormTypeSelect) = explode(":", $strFormTypeText);
		break;
	}

	if(isset($_GET['btnFieldCopy']))
	{
		$intForm2TypeID = "";
	}
}

echo "<div class='wrap'>
	<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." (".$strFormName.")" : __("Add New", 'lang_form'))."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		if($obj_form->id > 0)
		{
			echo "<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox".($intForm2TypeID > 0 ? " active" : "")."'>
						<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
						<form method='post' action='".admin_url("admin.php?page=mf_form%2Fcreate%2Findex.php&intFormID=".$obj_form->id)."' class='mf_form mf_settings inside'>
							<div class='flex_flow'>
								<div>";

									if($intFormTypeID == '')
									{
										$intFormTypeID = $wpdb->get_var($wpdb->prepare("SELECT queryTypeID FROM ".$wpdb->base_prefix."query2type WHERE userID = '%d' ORDER BY query2TypeCreated DESC", get_current_user_id()));
									}

									if($intFormTypeID == 13)
									{
										$strFormTypeName = $obj_form->get_type_name($intFormTypeID);

										echo show_textfield(array('name' => 'intFormTypeID_name', 'text' => __("Type", 'lang_form'), 'value' => $strFormTypeName, 'xtra' => "readonly"))
										.input_hidden(array('name' => 'intFormTypeID', 'value' => $intFormTypeID, 'xtra' => "id='intFormTypeID'"));
									}

									else
									{
										$arr_data = array();
										$arr_data[''] = "-- ".__("Choose here", 'lang_form')." --";

										$result = $wpdb->get_results("SELECT queryTypeID, queryTypeName, COUNT(queryTypeID) AS queryType_amount FROM ".$wpdb->base_prefix."query_type LEFT JOIN ".$wpdb->base_prefix."query2type USING (queryTypeID) WHERE queryTypePublic = 'yes' GROUP BY queryTypeID ORDER BY queryType_amount DESC, queryTypeName ASC");

										foreach($result as $r)
										{
											if($intFormTypeID > 0 || $r->queryTypeID != 13)
											{
												$arr_data[$r->queryTypeID] = $r->queryTypeName;
											}
										}

										echo show_select(array('data' => $arr_data, 'name' => 'intFormTypeID', 'value' => $intFormTypeID, 'text' => __("Type", 'lang_form')));
									}

									echo show_textarea(array('name' => 'strFormTypeText', 'text' => __("Text", 'lang_form'), 'value' => $strFormTypeText, 'class' => "show_textarea hide")) //, 'wysiwyg' => true, 'size' => 'small'
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

										echo show_select(array('data' => $arr_data, 'name' => "intCheckID", 'value' => $intCheckID, 'text' => __("Validate as", 'lang_form'), 'class' => "show_validate_as hide"));
									}

									echo show_textfield(array('name' => 'strFormTypePlaceholder', 'text' => __("Placeholder Text", 'lang_form'), 'value' => $strFormTypePlaceholder, 'placeholder' => __("Feel free to write anything you like here", 'lang_form'), 'maxlength' => 100, 'xtra_class' => "show_placeholder"));

									$arr_data = array(
										'div' => "div",
										'fieldset' => "fieldset",
									);

									echo show_select(array('data' => $arr_data, 'name' => 'strFormTypeText2', 'value' => $strFormTypeText, 'text' => __("Type", 'lang_form'), 'class' => "show_custom_tag hide"))
									."<div class='show_range flex_flow hide'>"
										.show_textfield(array('name' => 'strFormTypeMin', 'text' => __("Min value", 'lang_form'), 'value' => $strFormTypeMin, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strFormTypeMax', 'text' => __("Max value", 'lang_form'), 'value' => $strFormTypeMax, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strFormTypeDefault', 'text' => __("Default value", 'lang_form'), 'value' => $strFormTypeDefault, 'maxlength' => 3, 'size' => 5))
									."</div>"
									."<div class='show_select'>
										<label>".__("Value", 'lang_form')."</label>
										<div class='select_rows'>";

											if($strFormTypeSelect == '')
											{
												$strFormTypeSelect = "|";
											}

											$arr_select_rows = explode(",", $strFormTypeSelect);

											foreach($arr_select_rows as $select_row)
											{
												$arr_select_row_content = explode("|", $select_row);

												echo "<div>"
													//input text is needed when using select as payment price
													.show_textfield(array('name' => 'strFormTypeSelect_id', 'value' => $arr_select_row_content[0]))
													//.input_hidden(array('name' => 'strFormTypeSelect_id', 'value' => $arr_select_row_content[0]))
													.show_textfield(array('name' => 'strFormTypeSelect_value', 'value' => $arr_select_row_content[1], 'placeholder' => __("Enter option here", 'lang_form')))
												."</div>";
											}

										echo "</div>"
										.input_hidden(array('name' => 'strFormTypeSelect', 'value' => $strFormTypeSelect))
									."</div>";

									//Advanced
									#################
									echo get_toggler_container(array('type' => 'start', 'text' => __("Advanced", 'lang_form')));

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

										echo show_select(array('data' => $arr_data, 'name' => 'strFormTypeTag', 'value' => $strFormTypeTag, 'text' => __("Custom HTML Tag", 'lang_form'), 'class' => "show_custom_text_tag hide"))
										.show_textfield(array('name' => 'strFormTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $strFormTypeClass, 'placeholder' => "bold italic aligncenter alignleft alignright", 'maxlength' => 50, 'xtra_class' => "show_custom_class hide"))
										.show_textfield(array('name' => 'strFormTypeFetchFrom', 'text' => __("Fetch From ID", 'lang_form'), 'value' => $strFormTypeFetchFrom, 'maxlength' => 50, 'xtra_class' => "show_fetch_from hide"));

										if($intForm2TypeID > 0)
										{
											$arr_data_equals = array();

											foreach($arr_select_rows as $select_row)
											{
												$arr_select_row_content = explode("|", $select_row);

												$arr_data_equals[$arr_select_row_content[0]] = $arr_select_row_content[1];
											}

											if(count($arr_data_equals) > 0)
											{
												list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(1, 4, 5, 10, 11, 13), 'query_exclude_id' => $intForm2TypeID));
												$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

												if(count($arr_data_show) > 0)
												{
													echo "<div class='show_actions'>"
														.show_select(array('data' => $arr_data_equals, 'name' => 'strFormTypeActionEquals', 'text' => __("If this equals...", 'lang_form'), 'value' => $strFormTypeActionEquals))
														.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeActionShow', 'value' => $intFormTypeActionShow, 'text' => __("...show this...", 'lang_form')))
													."</div>";
												}
											}
										}

									echo get_toggler_container(array('type' => 'end'));
									#################

								echo "</div>
							</div>"
							.show_button(array('name' => "btnFormAdd", 'text' => ($intForm2TypeID > 0 ? __("Update", 'lang_form') : __("Add", 'lang_form'))));

							if($intForm2TypeID > 0)
							{
								echo "&nbsp;<a href='?page=mf_form/create/index.php&intFormID=".$obj_form->id."'>"
									.show_button(array('type' => 'button', 'text' => __("Cancel", 'lang_form'), 'class' => "button"))
								."</a>"
								.input_hidden(array('name' => 'intForm2TypeID', 'value' => $intForm2TypeID));
							}

							echo input_hidden(array('name' => 'intFormID', 'value' => $obj_form->id))
							.wp_nonce_field('form_add_'.$obj_form->id, '_wpnonce', true, false)
						."</form>
					</div>";

					$form_output = $obj_form->process_form(array('edit' => true, 'query2type_id' => $intForm2TypeID));

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
								.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $strFormName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intForm2TypeID > 0 ? "" : "autofocus")))
								.show_textfield(array('name' => 'strFormURL', 'text' => __("Permalink", 'lang_form'), 'value' => $strFormURL, 'maxlength' => 100, 'required' => 1));

								$form_status = $obj_form->get_form_status();

								if($form_status == "publish")
								{
									echo show_textfield(array('name' => 'dteFormDeadline', 'text' => __("Deadline", 'lang_form'), 'value' => $dteFormDeadline, 'type' => 'date'));
								}

								if($form_output != '')
								{
									echo show_button(array('name' => "btnFormPublish", 'text' => ($form_status == "publish" ? __("Save", 'lang_form') : __("Publish", 'lang_form'))));
								}

								echo show_button(array('name' => "btnFormDraft", 'text' => __("Save Draft", 'lang_form'), 'class' => "button"));

								if($form_status == "publish")
								{
									$post_url = get_permalink($obj_form->post_id);

									if($post_url != '')
									{
										echo "<a href='".$post_url."' class='button'>".__("View form", 'lang_form')."</a>";
									}
								}

								echo input_hidden(array('name' => "intFormID", 'value' => $obj_form->id))
								.wp_nonce_field('form_update_'.$obj_form->id, '_wpnonce', true, false)
							."</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Settings", 'lang_form')."</span></h3>
							<div class='inside'>";

								//$arr_data_pages = $obj_form->get_pages_for_select();

								$arr_data = array();
								get_post_children(array('add_choose_here' => true), $arr_data);

								$arr_data_pages = $arr_data;

								echo show_select(array('data' => $arr_data_pages, 'name' => 'strFormAnswerURL', 'value' => $strFormAnswerURL, 'text' => __("Confirmation page", 'lang_form')));

								if($obj_form->is_poll())
								{
									echo show_checkbox(array('name' => 'intFormShowAnswers', 'text' => __("Show Answers", 'lang_form'), 'value' => 1, 'compare' => $intFormShowAnswers));
								}

								echo "<h4>".__("E-mail", 'lang_form')."</h4>"
								.show_textfield(array('name' => 'strFormEmail', 'text' => __("Send from/to", 'lang_form'), 'value' => $strFormEmail, 'maxlength' => 100, 'placeholder' => get_bloginfo('admin_email')));

								if($strFormEmailName != '')
								{
									echo show_textfield(array('name' => 'strFormEmailName', 'text' => __("Subject", 'lang_form'), 'value' => $strFormEmailName, 'maxlength' => 100));
								}

								/*if($strFormEmail != '')
								{*/
									echo show_checkbox(array('name' => 'intFormEmailNotify', 'text' => __("Notification on new answer", 'lang_form'), 'value' => 1, 'compare' => $intFormEmailNotify));
								/*}

								else
								{
									echo input_hidden(array('name' => "intFormEmailNotify", 'value' => 1));
								}*/

								echo show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailNotifyPage', 'value' => $intFormEmailNotifyPage, 'text' => __("Notification template", 'lang_form'), 'class' => "query_email_notify_page".($intFormEmailNotify == 1 ? " " : " hide")))
								."<h4>".__("E-mail to visitor", 'lang_form')."</h4>";

								if($obj_form->has_email_field() > 0)
								{
									echo show_checkbox(array('name' => 'intFormEmailConfirm', 'text' => __("Send confirmation to questionnaire", 'lang_form'), 'value' => 1, 'compare' => $intFormEmailConfirm))
									.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailConfirmPage', 'value' => $intFormEmailConfirmPage, 'text' => __("Confirmation template", 'lang_form'), 'class' => "query_email_confirm_page".($intFormEmailConfirm == 1 ? " " : " hide"))); //, 'description' => __("If you don't choose a page, the content of the form will be sent as content", 'lang_form')
								}

								/*if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'required' => true, 'check_code' => 'email')))
								{
									echo show_checkbox(array('name' => 'strFormEmailCheckConfirm', 'text' => __("Make questionnaire confirm their address", 'lang_form'), 'value' => 'yes', 'compare' => $strFormEmailCheckConfirm));
								}*/

								echo "<h4>".__("Button", 'lang_form')."</h4>
								<div class='flex_flow'>"
									.show_select(array('data' => $obj_form->get_icons_for_select(), 'name' => 'strFormButtonSymbol', 'value' => $strFormButtonSymbol, 'text' => __("Symbol", 'lang_form')))
									.show_textfield(array('name' => 'strFormButtonText', 'text' => __("Text", 'lang_form'), 'value' => $strFormButtonText, 'placeholder' => __("Submit", 'lang_form'), 'maxlength' => 100))
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

								echo show_select(array('data' => $arr_data, 'name' => 'intFormPaymentProvider', 'value' => $intFormPaymentProvider, 'text' => __("Provider", 'lang_form')));

								if($intFormPaymentProvider == 1)
								{
									echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Merchant ID", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strFormPaymentHmac', 'text' => __("HMAC key", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
								}

								else if($intFormPaymentProvider == 3)
								{
									echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Username", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
									.show_password_field(array('name' => 'strFormPaymentPassword', 'text' => __("Password", 'lang_form'), 'value' => $strFormPaymentPassword, 'maxlength' => 100))
									.show_textfield(array('name' => 'strFormPaymentHmac', 'text' => __("Signature", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
								}

								else if($intFormPaymentProvider == 2)
								{
									echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Merchant E-mail", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strFormPaymentHmac', 'text' => __("Secret word", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
								}

								if($intFormPaymentProvider > 0 && $strFormPaymentMerchant != '' && $strFormPaymentHmac != '')
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

									echo show_select(array('data' => $arr_data, 'name' => 'strFormPaymentCurrency', 'value' => $strFormPaymentCurrency, 'text' => __("Currency", 'lang_form')));

									list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(10, 12)));
									$arr_data = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

									echo show_select(array('data' => $arr_data, 'name' => 'intFormPaymentAmount', 'value' => $intFormPaymentAmount, 'text' => __("Field for payment cost", 'lang_form')));
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
					<h3 class='hndle'><span>".__("Add New", 'lang_form')."</span></h3>
					<div class='inside'>"
						.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $strFormName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intForm2TypeID > 0 ? "" : "autofocus")))
						.show_button(array('name' => "btnFormPublish", 'text' => __("Add", 'lang_form')))
						.input_hidden(array('name' => "intFormID", 'value' => $obj_form->id))
						.wp_nonce_field('form_update_'.$obj_form->id, '_wpnonce', true, false)
					."</div>
				</div>
			</form>";
		}

	echo "</div>
</div>";