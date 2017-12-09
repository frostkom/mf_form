<?php

$plugin_version = get_plugin_version(__FILE__);

mf_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css", $plugin_version);
wp_enqueue_script('jquery-ui-sortable');
mf_enqueue_script('script_touch', plugins_url()."/mf_base/include/jquery.ui.touch-punch.min.js", '0.2.2');
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')), $plugin_version);

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

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET blogID = '%d', formEmailConfirm = '%d', formEmailConfirmPage = %s, formShowAnswers = '%d', formName = %s, formAnswerURL = %s, formEmail = %s, formEmailNotify = '%d', formEmailNotifyPage = %s, formEmailName = %s, formMandatoryText = %s, formButtonText = %s, formButtonSymbol = %s, formPaymentProvider = '%d', formPaymentHmac = %s, formPaymentMerchant = %s, formPaymentPassword = %s, formPaymentCurrency = %s, formPaymentAmount = '%d' WHERE formID = '%d' AND formDeleted = '0'", $wpdb->blogid, $intFormEmailConfirm, $intFormEmailConfirmPage, $intFormShowAnswers, $strFormName, $strFormAnswerURL, $strFormEmail, $intFormEmailNotify, $intFormEmailNotifyPage, $strFormEmailName, $strFormMandatoryText, $strFormButtonText, $strFormButtonSymbol, $intFormPaymentProvider, $strFormPaymentHmac, $strFormPaymentMerchant, $strFormPaymentPassword, $strFormPaymentCurrency, $intFormPaymentAmount, $obj_form->id));

			$done_text = __("I have updated the form for you", 'lang_form');
		}

		else
		{
			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_form' AND post_title = '%d' LIMIT 0, 1", $strFormName));

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

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form SET blogID = '%d', postID = '%d', formName = %s, formCreated = NOW(), userID = '%d'", $wpdb->blogid, $obj_form->post_id, $strFormName, get_current_user_id()));
				$obj_form->id = $wpdb->insert_id;

				$done_text = __("I have created the form for you", 'lang_form');
			}
		}

		/*if($wpdb->rows_affected > 0)
		{
			echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'</script>";
		}*/
	}
}

else if(isset($_POST['btnFormAdd']) && wp_verify_nonce($_POST['_wpnonce'], 'form_add_'.$obj_form->id))
{
	//Clean up settings if not used for the specific type of field
	################
	if($intFormTypeID != 3) //'input_field'
	{
		$intCheckID = "";
	}
	################

	if(in_array($intFormTypeID, array(10, 11, 16, 17)) && $strFormTypeSelect == "") //'select', 'select_multiple', 'checkbox_multiple', 'radio_multiple'
	{
		$error_text = __("Please, enter all required fields", 'lang_form');
	}

	else
	{
		switch($intFormTypeID)
		{
			case 2:
			//case 'range':
				$strFormTypeText = str_replace("|", "", $strFormTypeText)."|".str_replace("|", "", $strFormTypeMin)."|".str_replace("|", "", $strFormTypeMax)."|".str_replace("|", "", $strFormTypeDefault);
			break;

			case 10:
			//case 'select':
			case 11:
			//case 'select_multiple':
			case 16:
			//case 'checkbox_multiple':
			case 17:
			//case 'radio_multiple':
				$obj_form->formTypeSelect = $strFormTypeSelect;
				$obj_form->validate_select_array();

				$strFormTypeText = str_replace(":", "", $strFormTypeText).":".str_replace(":", "", $obj_form->formTypeSelect);
			break;

			case 13:
			//case 'custom_tag':
			case 14:
			//case 'custom_tag_end':
				$strFormTypeText = $strFormTypeText2;
			break;
		}

		if($intForm2TypeID > 0)
		{
			if($intFormTypeID > 0 && ($intFormTypeID == 6 || $intFormTypeID == 9 || $strFormTypeText != '')) //'space', 'referer_url'
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeFetchFrom = %s, formTypeActionEquals = %s, formTypeActionShow = %s, userID = '%d' WHERE form2TypeID = '%d'", $intFormTypeID, $strFormTypeText, $strFormTypePlaceholder, $intCheckID, $strFormTypeTag, $strFormTypeClass, $strFormTypeFetchFrom, $strFormTypeActionEquals, $intFormTypeActionShow, get_current_user_id(), $intForm2TypeID));

				if($intFormTypeID == 13) //'custom_tag'
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeText = %s, userID = '%d' WHERE form2TypeID2 = '%d'", $strFormTypeText, get_current_user_id(), $intForm2TypeID));
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
			if($obj_form->id > 0 && $intFormTypeID > 0 && ($intFormTypeID == 6 || $intFormTypeID == 9 || $strFormTypeText != '')) //'space', 'referer_url'
			{
				$intForm2TypeOrder = $wpdb->get_var($wpdb->prepare("SELECT form2TypeOrder + 1 FROM ".$wpdb->base_prefix."form2type WHERE formID = '%d' ORDER BY form2TypeOrder DESC", $obj_form->id));

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type SET formID = '%d', formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeFetchFrom = %s, formTypeActionEquals = %s, formTypeActionShow = %s, form2TypeOrder = '%d', form2TypeCreated = NOW(), userID = '%d'", $obj_form->id, $intFormTypeID, $strFormTypeText, $strFormTypePlaceholder, $intCheckID, $strFormTypeTag, $strFormTypeClass, $strFormTypeFetchFrom, $strFormTypeActionEquals, $intFormTypeActionShow, $intForm2TypeOrder, get_current_user_id()));

				if($intFormTypeID == 13) //'custom_tag'
				{
					$intForm2TypeID = $wpdb->insert_id;
					$intFormTypeID = 14;
					$intForm2TypeOrder++;

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type SET form2TypeID2 = '%d', formID = '%d', formTypeID = '%d', formTypeText = %s, form2TypeOrder = '%d', form2TypeCreated = NOW(), userID = '%d'", $intForm2TypeID, $obj_form->id, $intFormTypeID, $strFormTypeText, $intForm2TypeOrder, get_current_user_id()));
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
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET formDeleted = '0' WHERE formID = '%d'", $obj_form->id));
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmPage, formShowAnswers, formAnswerURL, formEmail, formEmailNotify, formEmailNotifyPage, formEmailName, formMandatoryText, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formPaymentMerchant, formPaymentPassword, formPaymentCurrency, formPaymentAmount, formCreated FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $obj_form->id));

	if($wpdb->num_rows > 0)
	{
		$r = $result[0];
		$intFormEmailConfirm = $r->formEmailConfirm;
		$intFormEmailConfirmPage = $r->formEmailConfirmPage;
		$intFormShowAnswers = $r->formShowAnswers;
		$strFormAnswerURL = $r->formAnswerURL;
		$strFormEmail = $r->formEmail;
		$intFormEmailNotify = $r->formEmailNotify;
		$intFormEmailNotifyPage = $r->formEmailNotifyPage;
		$strFormEmailName = $r->formEmailName;
		$strFormMandatoryText = $r->formMandatoryText;
		$strFormButtonText = $r->formButtonText;
		$strFormButtonSymbol = $r->formButtonSymbol;
		$intFormPaymentProvider = $r->formPaymentProvider;
		$strFormPaymentHmac = $r->formPaymentHmac;
		$strFormPaymentMerchant = $r->formPaymentMerchant;
		$strFormPaymentPassword = $r->formPaymentPassword;
		$strFormPaymentCurrency = $r->formPaymentCurrency;
		$intFormPaymentAmount = $r->formPaymentAmount;
		$strFormCreated = $r->formCreated;

		$strFormName = $obj_form->get_post_info(array('select' => "post_title"));
		$strFormURL = $obj_form->get_post_info();
		$dteFormDeadline = $obj_form->meta(array('action' => 'get', 'key' => 'deadline'));
	}

	else
	{
		$error_text = __("I could not find the form you were looking for. If the problem persists, please contact an admin", 'lang_form');
	}
}

if($intForm2TypeID > 0)
{
	$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $intForm2TypeID));
	$r = $result[0];
	$intFormTypeID = $r->formTypeID;
	$strFormTypeText = $r->formTypeText;
	$strFormTypePlaceholder = $r->formTypePlaceholder;
	$intCheckID = $r->checkID;
	$strFormTypeTag = $r->formTypeTag;
	$strFormTypeClass = $r->formTypeClass;
	$strFormTypeFetchFrom = $r->formTypeFetchFrom;
	$strFormTypeActionEquals = $r->formTypeActionEquals;
	$intFormTypeActionShow = $r->formTypeActionShow;

	switch($intFormTypeID)
	{
		case 2:
		//case 'range':
			list($strFormTypeText, $strFormTypeMin, $strFormTypeMax, $strFormTypeDefault) = explode("|", $strFormTypeText);
		break;

		case 10:
		//case 'select':
		case 11:
		//case 'select_multiple':
		case 16:
		//case 'checkbox_multiple':
		case 17:
		//case 'radio_multiple':
			list($strFormTypeText, $strFormTypeSelect) = explode(":", $strFormTypeText);
		break;
	}

	if(isset($_GET['btnFieldCopy']))
	{
		$intForm2TypeID = "";
	}
}

$form_status = $obj_form->get_form_status();

echo "<div class='wrap'>
	<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." (".$strFormName.")" : __("Add New", 'lang_form'))."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		if($obj_form->id > 0)
		{
			$intForm2TypeID_example = $intForm2TypeID > 0 ? $intForm2TypeID : 123;

			$form_page_shortcodes = "&post_title=".sprintf(__("Title Example | Ticket: %s | %s: %s", 'lang_forms'), "[answer_id]", "[label_".$intForm2TypeID_example."]", "[answer_".$intForm2TypeID_example."]")
				."&content=".sprintf(__("Ticket: %s, Answers: %s", 'lang_forms'), "[answer_id]", "[form_fields]");

			if(is_plugin_active("mf_webshop/index.php"))
			{
				$form_page_shortcodes .= ($form_page_shortcodes != '' ? ", " : "").sprintf(__("Document Types: %s, Products: %s, Product Name: %s, Yes Link: %s, No Link: %s", 'lang_forms'), "[doc_types]", "[products]", "[product]", "[link_yes]", "[link_no]");
			}

			if($intFormTypeID == '')
			{
				$intFormTypeID = $wpdb->get_var($wpdb->prepare("SELECT formTypeID FROM ".$wpdb->base_prefix."form2type WHERE userID = '%d' ORDER BY form2TypeCreated DESC", get_current_user_id()));
			}

			if($strFormTypeSelect == '')
			{
				$strFormTypeSelect = "|";
			}

			$arr_select_rows = explode(",", $strFormTypeSelect);

			$arr_data_pages = $obj_form->get_pages_for_select();

			echo "<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox".($intForm2TypeID > 0 ? " active" : "")."'>
						<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
						<form method='post' action='".admin_url("admin.php?page=mf_form%2Fcreate%2Findex.php&intFormID=".$obj_form->id)."' class='mf_form mf_settings inside'>
							<div class='flex_flow'>
								<div>";

									if($intFormTypeID == 13) //'custom_tag'
									{
										echo show_textfield(array('name' => 'intFormTypeID_name', 'text' => __("Type", 'lang_form'), 'value' => $obj_form->get_type_name($intFormTypeID), 'xtra' => "readonly"))
										.input_hidden(array('name' => 'intFormTypeID', 'value' => $intFormTypeID, 'xtra' => "id='intFormTypeID'"));
									}

									else
									{
										echo show_form_alternatives(array('data' => $obj_form->get_form_types_for_select(), 'name' => 'intFormTypeID', 'value' => $intFormTypeID, 'class' => "fontawesome")); //, 'text' => __("Type", 'lang_form')
									}

								echo "</div>
								<div>"
									.show_textarea(array('name' => 'strFormTypeText', 'value' => $strFormTypeText, 'class' => "show_textarea hide", 'placeholder' => __("Text", 'lang_form')))
									.show_select(array('data' => $obj_form->get_form_checks_for_select(), 'name' => "intCheckID", 'value' => $intCheckID, 'text' => __("Validate as", 'lang_form'), 'class' => "show_validate_as hide"))
									.show_textfield(array('name' => 'strFormTypePlaceholder', 'text' => __("Placeholder Text", 'lang_form'), 'value' => $strFormTypePlaceholder, 'placeholder' => __("Feel free to write anything you like here", 'lang_form'), 'maxlength' => 100, 'xtra_class' => "show_placeholder"))
									.show_select(array('data' => array('div' => "div", 'fieldset' => "fieldset"), 'name' => 'strFormTypeText2', 'value' => $strFormTypeText, 'text' => __("Type", 'lang_form'), 'class' => "show_custom_tag hide"))
									."<div class='show_range flex_flow hide'>"
										.show_textfield(array('name' => 'strFormTypeMin', 'text' => __("Min value", 'lang_form'), 'value' => $strFormTypeMin, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strFormTypeMax', 'text' => __("Max value", 'lang_form'), 'value' => $strFormTypeMax, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strFormTypeDefault', 'text' => __("Default value", 'lang_form'), 'value' => $strFormTypeDefault, 'maxlength' => 3, 'size' => 5))
									."</div>"
									."<div class='show_select'>
										<label>".__("Value", 'lang_form')." <i class='fa fa-info-circle' title='".__("Enter ID, Name and Limit (optional)", 'lang_form')."'></i></label>
										<div class='select_rows'>";

											foreach($arr_select_rows as $select_row)
											{
												@list($option_id, $option_value, $option_limit) = explode("|", $select_row, 3);

												echo "<div class='option'>"
													.show_textfield(array('name' => 'strFormTypeSelect_id', 'value' => $option_id)) //input text is needed when using payment price as ID
													.show_textfield(array('name' => 'strFormTypeSelect_value', 'value' => $option_value, 'placeholder' => __("Enter option here", 'lang_form')))
													.show_textfield(array('type' => 'number', 'name' => 'intFormTypeSelect_limit', 'value' => $option_limit))
												."</div>";
											}

										echo "</div>"
										.input_hidden(array('name' => 'strFormTypeSelect', 'value' => $strFormTypeSelect))
									."</div>";

									//Advanced
									#################
									echo get_toggler_container(array('type' => 'start', 'text' => __("Advanced", 'lang_form'), 'rel' => $obj_form->id))
										.show_select(array('data' => $obj_form->get_tags_for_select(), 'name' => 'strFormTypeTag', 'value' => $strFormTypeTag, 'text' => __("Custom HTML Tag", 'lang_form'), 'class' => "show_custom_text_tag hide"))
										.show_textfield(array('name' => 'strFormTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $strFormTypeClass, 'placeholder' => "bold italic aligncenter alignleft alignright", 'maxlength' => 50, 'xtra_class' => "show_custom_class hide"));

										if($intForm2TypeID > 0)
										{
											$handle_temp = $obj_form->get_post_info()."_".$intForm2TypeID;

											$placeholder_temp = sprintf(__("Default is %s but you can assign a custom handle", 'lang_form'), $handle_temp);
											$description_temp = sprintf(__("Try it out by %sgoing here%s", 'lang_form'), "<a href='".get_permalink($obj_form->post_id)."?".($strFormTypeFetchFrom != '' ? $strFormTypeFetchFrom : $handle_temp)."=2'>", "</a>");

											echo show_textfield(array('name' => 'strFormTypeFetchFrom', 'text' => __("Change Default Value", 'lang_form'), 'value' => $strFormTypeFetchFrom, 'maxlength' => 50, 'placeholder' => $placeholder_temp, 'xtra_class' => "show_fetch_from hide", 'description' => $description_temp));

											$arr_data_equals = array();

											foreach($arr_select_rows as $str_option)
											{
												list($option_id, $option_value) = explode("|", $str_option);

												$arr_data_equals[$option_id] = $option_value;
											}

											if(count($arr_data_equals) > 1)
											{
												list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(1, 2, 3, 4, 5, 7, 8, 10, 11, 16, 17), 'query_exclude_id' => $intForm2TypeID)); //'checkbox', 'range', 'input_field', 'textarea', 'text', 'datepicker', 'radio_button', 'select', 'select_multiple', 'checkbox_multiple', 'radio_multiple':

												if($rows > 0)
												{
													$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

													echo "<div class='show_actions'>"
														.show_select(array('data' => $arr_data_equals, 'name' => 'strFormTypeActionEquals', 'text' => __("If this equals...", 'lang_form'), 'value' => $strFormTypeActionEquals))
														.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeActionShow', 'text' => __("...show this...", 'lang_form'), 'value' => $intFormTypeActionShow))
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

					$form_output = $obj_form->process_form(array('edit' => true, 'form2type_id' => $intForm2TypeID));

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
							<h3 class='hndle'><span>".__("Save", 'lang_form')."</span></h3>
							<div class='inside'>"
								.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $strFormName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intForm2TypeID > 0 ? "" : "autofocus")))
								.show_textfield(array('name' => 'strFormURL', 'text' => __("Permalink", 'lang_form'), 'value' => $strFormURL, 'maxlength' => 100, 'required' => 1));

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
							<div class='inside'>"
								.show_select(array('data' => $arr_data_pages, 'name' => 'strFormAnswerURL', 'value' => $strFormAnswerURL, 'text' => __("Confirmation page", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-lg fa-plus'></i></a>"));

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

								echo show_checkbox(array('name' => 'intFormEmailNotify', 'text' => __("Notification on new answer", 'lang_form'), 'value' => 1, 'compare' => $intFormEmailNotify))
								.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailNotifyPage', 'value' => $intFormEmailNotifyPage, 'text' => __("Notification template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_page_shortcodes)."'><i class='fa fa-lg fa-plus'></i></a>", 'class' => "query_email_notify_page".($intFormEmailNotify == 1 ? "" : " hide")));

								if($obj_form->has_email_field() > 0)
								{
									echo "<h4>".__("E-mail to visitor", 'lang_form')."</h4>"
									.show_checkbox(array('name' => 'intFormEmailConfirm', 'text' => __("Send confirmation to questionnaire", 'lang_form'), 'value' => 1, 'compare' => $intFormEmailConfirm))
									.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailConfirmPage', 'value' => $intFormEmailConfirmPage, 'text' => __("Confirmation template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_page_shortcodes)."'><i class='fa fa-lg fa-plus'></i></a>", 'class' => "query_email_confirm_page".($intFormEmailConfirm == 1 ? " " : " hide")));
								}

								echo "<h4>".__("Button", 'lang_form')."</h4>
								<div class='flex_flow'>"
									.show_select(array('data' => $obj_form->get_icons_for_select(), 'name' => 'strFormButtonSymbol', 'value' => $strFormButtonSymbol, 'text' => __("Symbol", 'lang_form')))
									.show_textfield(array('name' => 'strFormButtonText', 'text' => __("Text", 'lang_form'), 'value' => $strFormButtonText, 'placeholder' => __("Submit", 'lang_form'), 'maxlength' => 100))
								."</div>";

								if($form_status == "publish")
								{
									echo show_textfield(array('type' => 'date', 'name' => 'dteFormDeadline', 'text' => __("Deadline", 'lang_form'), 'value' => $dteFormDeadline));
								}

							echo "</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Payment", 'lang_form')."</span></h3>
							<div class='inside'>"
								.show_select(array('data' => $obj_form->get_payment_providers_for_select(), 'name' => 'intFormPaymentProvider', 'value' => $intFormPaymentProvider, 'text' => __("Provider", 'lang_form')));

								switch($intFormPaymentProvider)
								{
									case 1:
										echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Merchant ID", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
										.show_textfield(array('name' => 'strFormPaymentHmac', 'text' => __("HMAC key", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
									break;

									case 2:
										echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Merchant E-mail", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
										.show_textfield(array('name' => 'strFormPaymentHmac', 'text' => __("Secret word", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
									break;

									case 3:
										echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Username", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
										.show_password_field(array('name' => 'strFormPaymentPassword', 'text' => __("Password", 'lang_form'), 'value' => $strFormPaymentPassword, 'maxlength' => 100))
										.show_textfield(array('name' => 'strFormPaymentHmac', 'text' => __("Signature", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
									break;

									case 4:
										echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Store ID", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100))
										.show_password_field(array('name' => 'strFormPaymentHmac', 'text' => __("Secret Key", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 100));
									break;
								}

								if($intFormPaymentProvider > 0 && $strFormPaymentMerchant != '' && $strFormPaymentHmac != '')
								{
									echo show_select(array('data' => $obj_form->get_payment_currency_for_select($intFormPaymentProvider), 'name' => 'strFormPaymentCurrency', 'value' => $strFormPaymentCurrency, 'text' => __("Currency", 'lang_form')))
									.show_select(array('data' => $obj_form->get_payment_amount_for_select(), 'name' => 'intFormPaymentAmount', 'value' => $intFormPaymentAmount, 'text' => __("Field for payment cost", 'lang_form')));
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