<?php

$obj_form = new mf_form();

if($obj_form->check_allow_edit())
{
	$strFormName = check_var('strFormName');
	$strFormImport = check_var('strFormImport');

	$strFormURL = check_var('strFormURL');
	$dteFormDeadline = check_var('dteFormDeadline');

	$intForm2TypeID = check_var('intForm2TypeID');
	$intForm2TypeOrder = check_var('intForm2TypeOrder');

	$intFormEmailConfirm = isset($_POST['intFormEmailConfirm']) ? 1 : 0;
	$intFormEmailConfirmPage = check_var('intFormEmailConfirmPage');
	$intFormShowAnswers = isset($_POST['intFormShowAnswers']) ? 1 : 0;
	$strFormSaveIP = check_var('strFormSaveIP');
	$strFormAnswerURL = check_var('strFormAnswerURL');
	$strFormEmail = check_var('strFormEmail', 'email');
	$strFormEmailConditions = check_var('strFormEmailConditions');
	$intFormEmailNotify = check_var('intFormEmailNotify');
	$intFormEmailNotifyPage = check_var('intFormEmailNotifyPage');
	$strFormEmailName = check_var('strFormEmailName');
	$strFormMandatoryText = check_var('strFormMandatoryText');
	$strFormButtonText = check_var('strFormButtonText');
	$strFormButtonSymbol = check_var('strFormButtonSymbol');
	$intFormPaymentProvider = check_var('intFormPaymentProvider');
	$strFormPaymentHmac = check_var('strFormPaymentHmac');
	$intFormTermsPage = check_var('intFormTermsPage');
	$strFormPaymentMerchant = check_var('strFormPaymentMerchant');
	$strFormPaymentPassword = check_var('strFormPaymentPassword');
	$strFormPaymentCurrency = check_var('strFormPaymentCurrency');
	$intFormPaymentCost = check_var('intFormPaymentCost');
	$intFormPaymentAmount = check_var('intFormPaymentAmount');
	$intFormPaymentTax = check_var('intFormPaymentTax');
	$strFormPaymentCallback = check_var('strFormPaymentCallback');
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

	$strFormTypeSelect = check_var('strFormTypeSelect', '', true, "0|-- ".__("Choose Here", 'lang_form')." --,1|".__("No", 'lang_form').",2|".__("Yes", 'lang_form'));
	$strFormTypeMin = check_var('strFormTypeMin', '', true, "0");
	$strFormTypeMax = check_var('strFormTypeMax', '', true, 100);
	$strFormTypeDefault = check_var('strFormTypeDefault', '', true, 1);

	do_action('fetch_form_request');

	$error_text = $done_text = '';

	if((isset($_POST['btnFormPublish']) || isset($_POST['btnFormDraft'])) && wp_verify_nonce($_POST['_wpnonce_form_update'], 'form_update_'.$obj_form->id))
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

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET blogID = '%d', formEmailConfirm = '%d', formEmailConfirmPage = %s, formShowAnswers = '%d', formName = %s, formSaveIP = %s, formAnswerURL = %s, formEmail = %s, formEmailConditions = %s, formEmailNotify = '%d', formEmailNotifyPage = %s, formEmailName = %s, formMandatoryText = %s, formButtonText = %s, formButtonSymbol = %s, formPaymentProvider = '%d', formPaymentHmac = %s, formTermsPage = '%d', formPaymentMerchant = %s, formPaymentPassword = %s, formPaymentCurrency = %s, formPaymentCost = '%d', formPaymentAmount = '%d', formPaymentTax = '%d', formPaymentCallback = %s WHERE formID = '%d' AND formDeleted = '0'", $wpdb->blogid, $intFormEmailConfirm, $intFormEmailConfirmPage, $intFormShowAnswers, $strFormName, $strFormSaveIP, $strFormAnswerURL, $strFormEmail, $strFormEmailConditions, $intFormEmailNotify, $intFormEmailNotifyPage, $strFormEmailName, $strFormMandatoryText, $strFormButtonText, $strFormButtonSymbol, $intFormPaymentProvider, $strFormPaymentHmac, $intFormTermsPage, $strFormPaymentMerchant, $strFormPaymentPassword, $strFormPaymentCurrency, $intFormPaymentCost, $intFormPaymentAmount, $intFormPaymentTax, $strFormPaymentCallback, $obj_form->id));

				do_action('update_form_fields', $obj_form);

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

					if($strFormImport != '')
					{
						$arr_import_rows = explode("\n", $strFormImport);

						foreach($arr_import_rows as $import_row)
						{
							list($intFormTypeID, $strFormTypeText, $strFormTypePlaceholder, $intCheckID, $strFormTypeTag, $strFormTypeClass, $strFormTypeFetchFrom, $intFormTypeDisplay, $intFormTypeRequired, $intFormTypeAutofocus, $intFormTypeRemember, $intForm2TypeOrder) = explode(",", $import_row); //, $strFormTypeCode

							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."form2type SET formID = '%d', formTypeID = '%d', formTypeText = %s, formTypePlaceholder = %s, checkID = '%d', formTypeTag = %s, formTypeClass = %s, formTypeFetchFrom = %s, formTypeDisplay = '%d', formTypeRequired = '%d', formTypeAutofocus = '%d', formTypeRemember = '%d', form2TypeOrder = '%d', userID = '%d'", $obj_form->id, $intFormTypeID, $strFormTypeText, $strFormTypePlaceholder, $intCheckID, $strFormTypeTag, $strFormTypeClass, $strFormTypeFetchFrom, $intFormTypeDisplay, $intFormTypeRequired, $intFormTypeAutofocus, $intFormTypeRemember, $intForm2TypeOrder, get_current_user_id()));
						}
					}

					$done_text = __("I have created the form for you", 'lang_form');
				}
			}

			/*if($wpdb->rows_affected > 0)
			{
				echo "<script>location.href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'</script>";
			}*/
		}
	}

	else if(isset($_POST['btnFormAdd']) && wp_verify_nonce($_POST['_wpnonce_form_add'], 'form_add_'.$obj_form->id))
	{
		//Clean up settings if not used for the specific type of field
		################
		/*if($intFormTypeID != 3) //'input_field'
		{
			$intCheckID = "";
		}*/
		################

		switch($intFormTypeID)
		{
			case 2: // range
				$strFormTypeText = str_replace("|", "", $strFormTypeText)."|".str_replace("|", "", $strFormTypeMin)."|".str_replace("|", "", $strFormTypeMax)."|".str_replace("|", "", $strFormTypeDefault);
			break;

			/*case 6: // space
			case 9: // referer_url

			break;*/

			case 10: // select
			case 11: // select_multiple
			case 16: // checkbox_multiple
			case 17: // radio_multiple
				if($strFormTypeSelect == '')
				{
					$error_text = __("Please, enter all required fields", 'lang_form');
				}

				else
				{
					$obj_form->formTypeSelect = $strFormTypeSelect;
					$obj_form->validate_select_array();

					$strFormTypeText = str_replace(":", "", $strFormTypeText).":".str_replace(":", "", $obj_form->formTypeSelect);
				}
			break;

			case 13: // custom_tag
			case 14: // custom_tag_end
				$strFormTypeText = $strFormTypeText2;
			break;
		}

		if($error_text == '')
		{
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

	if(!isset($_POST['btnFormPublish']) && !isset($_POST['btnFormDraft']) && $obj_form->id > 0)
	{
		if(isset($_GET['recover']))
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET formDeleted = '0' WHERE formID = '%d'", $obj_form->id));
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT formEmailConfirm, formEmailConfirmPage, formShowAnswers, formSaveIP, formAnswerURL, formEmail, formEmailConditions, formEmailNotify, formEmailNotifyPage, formEmailName, formMandatoryText, formButtonText, formButtonSymbol, formPaymentProvider, formPaymentHmac, formTermsPage, formPaymentMerchant, formPaymentPassword, formPaymentCurrency, formPaymentCost, formPaymentAmount, formPaymentTax, formPaymentCallback, formCreated FROM ".$wpdb->base_prefix."form WHERE formID = '%d' AND formDeleted = '0'", $obj_form->id));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intFormEmailConfirm = $r->formEmailConfirm;
				$intFormEmailConfirmPage = $r->formEmailConfirmPage;
				$intFormShowAnswers = $r->formShowAnswers;
				$strFormSaveIP = $r->formSaveIP;
				$strFormAnswerURL = $r->formAnswerURL;
				$strFormEmail = $r->formEmail;
				$strFormEmailConditions = $r->formEmailConditions;
				$intFormEmailNotify = $r->formEmailNotify;
				$intFormEmailNotifyPage = $r->formEmailNotifyPage;
				$strFormEmailName = $r->formEmailName;
				$strFormMandatoryText = $r->formMandatoryText;
				$strFormButtonText = $r->formButtonText;
				$strFormButtonSymbol = $r->formButtonSymbol;
				$intFormPaymentProvider = $r->formPaymentProvider;
				$strFormPaymentHmac = $r->formPaymentHmac;
				$intFormTermsPage = $r->formTermsPage;
				$strFormPaymentMerchant = $r->formPaymentMerchant;
				$strFormPaymentPassword = $r->formPaymentPassword;
				$strFormPaymentCurrency = $r->formPaymentCurrency;
				$intFormPaymentCost = $r->formPaymentCost;
				$intFormPaymentAmount = $r->formPaymentAmount;
				$intFormPaymentTax = $r->formPaymentTax;
				$strFormPaymentCallback = $r->formPaymentCallback;
				$strFormCreated = $r->formCreated;
			}

			$strFormName = $obj_form->get_post_info(array('select' => "post_title"));
			$strFormURL = $obj_form->get_post_info();
			$dteFormDeadline = $obj_form->meta(array('action' => 'get', 'key' => 'deadline'));
		}

		else
		{
			$error_text = __("I could not find the form you were looking for. If the problem persists, please contact an admin", 'lang_form');
		}
	}

	if(!isset($_POST['btnFormAdd']) && $intForm2TypeID > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT formTypeID, formTypeText, formTypePlaceholder, checkID, formTypeTag, formTypeClass, formTypeFetchFrom, formTypeActionEquals, formTypeActionShow FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $intForm2TypeID));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
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
					case 2: // range
						list($strFormTypeText, $strFormTypeMin, $strFormTypeMax, $strFormTypeDefault) = explode("|", $strFormTypeText);
					break;

					case 10: // select
					case 11: // select_multiple
					case 16: // checkbox_multiple
					case 17: // radio_multiple
						list($strFormTypeText, $strFormTypeSelect) = explode(":", $strFormTypeText);
					break;
				}

				if(isset($_GET['btnFieldCopy']))
				{
					$intForm2TypeID = "";
				}
			}
		}

		else
		{
			do_log("No results from btnFormAdd (".$obj_form->id.", ".$wpdb->last_query.")");
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

				$form_page_shortcodes = "&post_title=".sprintf(__("Title Example | Ticket: %s | %s: %s", 'lang_form'), "[answer_id]", "[label_".$intForm2TypeID_example."]", "[answer_".$intForm2TypeID_example."]")
					."&content=".sprintf(__("Ticket: %s, Answers: %s", 'lang_form'), "[answer_id]", "[form_fields]");

				if(is_plugin_active("mf_webshop/index.php"))
				{
					$form_page_shortcodes .= ($form_page_shortcodes != '' ? ", " : "").sprintf(__("Document Types: %s, Products: %s, Product Name: %s, Yes Link: %s, No Link: %s", 'lang_form'), "[doc_types]", "[products]", "[product]", "[link_yes]", "[link_no]");
				}

				//Stop fetching the last one, this makes the chooseable fields to be filtered
				/*if($intFormTypeID == '')
				{
					$intFormTypeID = $wpdb->get_var($wpdb->prepare("SELECT formTypeID FROM ".$wpdb->base_prefix."form2type WHERE userID = '%d' ORDER BY form2TypeCreated DESC", get_current_user_id()));
				}*/

				if($strFormTypeSelect == '')
				{
					$strFormTypeSelect = "|";
				}

				if(does_table_exist($wpdb->base_prefix."form_option") && $intForm2TypeID > 0)
				{
					$result_select = $wpdb->get_results($wpdb->prepare("SELECT formOptionID, formOptionValue, formOptionLimit FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d' ORDER BY formOptionOrder ASC", $intForm2TypeID));
				}

				else
				{
					$arr_select_rows = explode(",", $strFormTypeSelect);
				}

				$arr_data_pages = $obj_form->get_pages_for_select();

				echo "<div id='post-body' class='columns-2'>
					<div id='post-body-content'>
						<div class='postbox".($intForm2TypeID > 0 ? " active" : "")."'>
							<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
							<form method='post' action='".admin_url("admin.php?page=mf_form%2Fcreate%2Findex.php&intFormID=".$obj_form->id)."' class='mf_form mf_settings inside'>
								<div class='flex_flow'>
									<div>"
										.show_form_alternatives(array('data' => $obj_form->get_form_types_for_select(array('form_type_id' => $intFormTypeID)), 'name' => 'intFormTypeID', 'value' => $intFormTypeID, 'class' => "fontawesome"))
									."</div>
									<div>"
										.show_textarea(array('name' => 'strFormTypeText', 'value' => $strFormTypeText, 'class' => "show_textarea hide", 'placeholder' => __("Text", 'lang_form')))
										."<div class='show_checkbox hide'>
											<h4>".__("Examples", 'lang_form')."</h4>
											<ol class='pointer'>
												<li>".__("I consent to having this website store my submitted information, so that they can respond to my inquiry", 'lang_form')."</li>
												<li>".__("By submitting this form I am aware that I will be sent to another website for payment", 'lang_form')."</li>
											</ol>
										</div>"
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

												if(does_table_exist($wpdb->base_prefix."form_option") && $intForm2TypeID > 0)
												{
													foreach($result_select as $r)
													{
														//$is_select_value_used = $obj_form->is_select_value_used(array('form2type_id' => $intForm2TypeID, 'option_id' => $r->formOptionID));

														echo "<div class='option'>"
															.show_textfield(array('name' => 'strFormTypeSelect_value', 'value' => $r->formOptionValue, 'placeholder' => __("Enter option here", 'lang_form'))) //, 'readonly' => $is_select_value_used
															.show_textfield(array('type' => 'number', 'name' => 'intFormTypeSelect_limit', 'value' => $r->formOptionLimit))
															.input_hidden(array('name' => 'strFormTypeSelect_id', 'value' => $r->formOptionID))
														."</div>";
													}
												}

												else
												{
													foreach($arr_select_rows as $select_row)
													{
														@list($option_id, $option_value, $option_limit) = explode("|", $select_row, 3);

														$is_select_value_used = $obj_form->is_select_value_used(array('form2type_id' => $intForm2TypeID, 'option_id' => $option_id));

														echo "<div class='option'>"
															.show_textfield(array('name' => 'strFormTypeSelect_id', 'value' => $option_id, 'placeholder' => __("ID", 'lang_form'), 'readonly' => $is_select_value_used)) //input text is needed when using payment price as ID
															.show_textfield(array('name' => 'strFormTypeSelect_value', 'value' => $option_value, 'placeholder' => __("Enter option here", 'lang_form'))) //, 'readonly' => $is_select_value_used
															.show_textfield(array('type' => 'number', 'name' => 'intFormTypeSelect_limit', 'value' => $option_limit))
														."</div>";
													}
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

												$description_temp = '';

												if(substr($strFormTypeFetchFrom, 0, 1) == "[")
												{
													$description_temp .= sprintf(__("Try it out by %sgoing here%s", 'lang_form'), "<a href='".get_permalink($obj_form->post_id)."'>", "</a>");
												}

												else
												{
													$description_temp .= sprintf(__("Try it out by %sgoing here%s", 'lang_form'), "<a href='".get_permalink($obj_form->post_id)."?".($strFormTypeFetchFrom != '' ? $strFormTypeFetchFrom : $handle_temp)."=2'>", "</a>");
												}

												echo show_textfield(array('name' => 'strFormTypeFetchFrom', 'text' => __("Change Default Value", 'lang_form')." <i class='fa fa-info-circle' title='custom_handle_that_you_can_name_whatever, [user_display_name], [user_email] ".__("or", 'lang_form')." [user_address]'></i>", 'value' => $strFormTypeFetchFrom, 'maxlength' => 50, 'placeholder' => sprintf(__("Assign handle or shortcode", 'lang_form'), $handle_temp), 'xtra_class' => "show_fetch_from hide", 'description' => $description_temp));

												$arr_data_equals = array();

												if(does_table_exist($wpdb->base_prefix."form_option") && $intForm2TypeID > 0)
												{
													foreach($result_select as $r)
													{
														$arr_data_equals[$r->formOptionID] = $r->formOptionValue;
													}
												}

												else
												{
													foreach($arr_select_rows as $str_option)
													{
														list($option_id, $option_value) = explode("|", $str_option);

														$arr_data_equals[$option_id] = $option_value;
													}
												}

												if(count($arr_data_equals) > 1)
												{
													list($result, $rows) = $obj_form->get_form_type_info(array('query_type_id' => array(1, 2, 3, 4, 5, 7, 8, 10, 11, 13, 16, 17), 'query_exclude_id' => $intForm2TypeID)); //'checkbox', 'range', 'input_field', 'textarea', 'text', 'datepicker', 'radio_button', 'select', 'select_multiple', 'custom_tag', 'checkbox_multiple', 'radio_multiple'

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
									echo "&nbsp;<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'>"
										.show_button(array('type' => 'button', 'text' => __("Cancel", 'lang_form'), 'class' => "button"))
									."</a>"
									.input_hidden(array('name' => 'intForm2TypeID', 'value' => $intForm2TypeID));
								}

								echo input_hidden(array('name' => 'intFormID', 'value' => $obj_form->id))
								.wp_nonce_field('form_add_'.$obj_form->id, '_wpnonce_form_add', true, false)
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
									.show_textfield(array('name' => 'strFormURL', 'text' => __("Permalink", 'lang_form'), 'value' => $strFormURL, 'maxlength' => 100));

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
									.wp_nonce_field('form_update_'.$obj_form->id, '_wpnonce_form_update', true, false)
								."</div>
							</div>
							<div class='postbox'>
								<h3 class='hndle'><span>".__("Settings", 'lang_form')."</span></h3>
								<div class='inside'>"
									.show_select(array('data' => $arr_data_pages, 'name' => 'strFormAnswerURL', 'value' => $strFormAnswerURL, 'text' => __("Confirmation Page", 'lang_form'), 'suffix' => get_option_page_suffix(array('value' => $strFormAnswerURL)))); //get_option_page_suffix(array('value' => $option))." <a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-plus-circle fa-lg'></i></a>"

									if($obj_form->is_poll())
									{
										echo show_checkbox(array('name' => 'intFormShowAnswers', 'text' => __("Show Answers", 'lang_form'), 'value' => 1, 'compare' => $intFormShowAnswers));
									}

									echo "<div class='flex_flow'>"
										.show_select(array('data' => $obj_form->get_icons_for_select(), 'name' => 'strFormButtonSymbol', 'value' => $strFormButtonSymbol, 'text' => __("Button Symbol", 'lang_form')))
										.show_textfield(array('name' => 'strFormButtonText', 'text' => __("Text", 'lang_form'), 'value' => $strFormButtonText, 'placeholder' => __("Submit", 'lang_form'), 'maxlength' => 100))
									."</div>"
									.show_textfield(array('type' => 'date', 'name' => 'dteFormDeadline', 'text' => __("Deadline", 'lang_form'), 'value' => $dteFormDeadline, 'xtra' => "min='".date("Y-m-d", strtotime("+1 day"))."'"))
									.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strFormSaveIP', 'value' => $strFormSaveIP, 'text' => __("Save IP", 'lang_form')))
								."</div>
							</div>
							<div class='postbox'>
								<h3 class='hndle'><span>".__("E-mail", 'lang_form')."</span></h3>
								<div class='inside'>";

									if($strFormEmailName != '')
									{
										echo show_textfield(array('name' => 'strFormEmailName', 'text' => __("Subject", 'lang_form'), 'value' => $strFormEmailName, 'maxlength' => 100));
									}

									echo show_checkbox(array('name' => 'intFormEmailNotify', 'text' => __("Send to Admin", 'lang_form'), 'value' => 1, 'compare' => $intFormEmailNotify))
									.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailNotifyPage', 'value' => $intFormEmailNotifyPage, 'text' => __("Template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>"));

									if($obj_form->has_email_field() > 0)
									{
										echo show_checkbox(array('name' => 'intFormEmailConfirm', 'text' => __("Send to Visitor", 'lang_form'), 'value' => 1, 'compare' => $intFormEmailConfirm))
										.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailConfirmPage', 'value' => $intFormEmailConfirmPage, 'text' => __("Template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
									}

									echo show_textfield(array('name' => 'strFormEmail', 'text' => __("Send From/To", 'lang_form'), 'value' => $strFormEmail, 'maxlength' => 100, 'placeholder' => get_bloginfo('admin_email')))
									.show_textarea(array('name' => 'strFormEmailConditions', 'text' => __("Conditions", 'lang_form'), 'value' => $strFormEmailConditions, 'placeholder' => "[field_id]|[field_value]|".get_bloginfo('admin_email')))
								."</div>
							</div>";

							$arr_data_providers = $obj_form->get_payment_providers_for_select();

							if(count($arr_data_providers) > 1)
							{
								echo "<div class='postbox'>
									<h3 class='hndle'><span>".__("Payment", 'lang_form')."</span></h3>
									<div class='inside'>"
										.show_select(array('data' => $arr_data_providers, 'name' => 'intFormPaymentProvider', 'value' => $intFormPaymentProvider, 'text' => __("Provider", 'lang_form')));

										$arr_fields = apply_filters('form_payment_fields', array(), $intFormPaymentProvider);

										if(in_array('merchant_id', $arr_fields))
										{
											echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Merchant ID", 'lang_form')." / ".__("E-mail", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100));
										}

										if(in_array('merchant_username', $arr_fields))
										{
											echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Username", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100));
										}

										if(in_array('merchant_store', $arr_fields))
										{
											echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Store ID", 'lang_form'), 'value' => $strFormPaymentMerchant, 'maxlength' => 100));
										}

										if(in_array('password', $arr_fields))
										{
											echo show_password_field(array('name' => 'strFormPaymentPassword', 'text' => __("Password", 'lang_form'), 'value' => $strFormPaymentPassword, 'maxlength' => 100));
										}

										if(in_array('secret_key', $arr_fields))
										{
											echo show_password_field(array('name' => 'strFormPaymentHmac', 'text' => __("Secret Key", 'lang_form')." / ".__("Signature", 'lang_form'), 'value' => $strFormPaymentHmac, 'maxlength' => 200));
										}

										if(in_array('terms_page', $arr_fields))
										{
											$arr_data = array();
											get_post_children(array('add_choose_here' => true), $arr_data);

											$post_title = __("Terms", 'lang_form');

											echo show_select(array('data' => $arr_data, 'name' => 'intFormTermsPage', 'text' => __("Terms Page", 'lang_form'), 'value' => $intFormTermsPage, 'required' => true, 'suffix' => get_option_page_suffix(array('value' => $intFormTermsPage, 'title' => $post_title))));
										}

										do_action('display_form_fields', $obj_form);

										if($intFormPaymentProvider > 0 && ($strFormPaymentMerchant != '' || $strFormPaymentHmac != ''))
										{
											echo show_select(array('data' => $obj_form->get_payment_currency_for_select($intFormPaymentProvider), 'name' => 'strFormPaymentCurrency', 'value' => $strFormPaymentCurrency, 'text' => __("Currency", 'lang_form')))
											.show_textfield(array('type' => 'number', 'name' => 'intFormPaymentCost', 'value' => $intFormPaymentCost, 'text' => __("Payment Cost", 'lang_form')));

											$arr_data_amount = $obj_form->get_payment_amount_for_select();

											if(count($arr_data_amount) > 1)
											{
												echo show_select(array('data' => $arr_data_amount, 'name' => 'intFormPaymentAmount', 'value' => $intFormPaymentAmount, 'text' => __("Field for Payment Amount", 'lang_form')));
											}

											echo show_textfield(array('type' => 'number', 'name' => 'intFormPaymentTax', 'value' => $intFormPaymentTax, 'text' => __("Tax", 'lang_form'), 'xtra' => " min='0' max='25'"));

											$description = "";

											if($strFormPaymentCallback != '' && !function_exists($strFormPaymentCallback))
											{
												$description = "<i class='fa fa-exclamation-triangle yellow'></i> ".__("The action that you have entered either does not exist or is not accessible when the success is triggered", 'lang_form');
											}

											echo show_textfield(array('name' => 'strFormPaymentCallback', 'text' => __("Action on Successful Payment", 'lang_form'), 'value' => $strFormPaymentCallback, 'maxlength' => 100, 'description' => $description));
										}

									echo "</div>
								</div>";
							}

							else if($intFormPaymentProvider > 0)
							{
								do_log(sprintf(__("There are no installed provider extension even though it seams like a provider has been set (%s)", 'lang_form'), $intFormPaymentProvider));
							}

						echo "</form>
					</div>
				</div>";
			}

			else
			{
				echo "<form method='post' action='' class='mf_form mf_settings'>
					<div class='postbox'>
						<div class='inside'>"
							.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $strFormName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intForm2TypeID > 0 ? "" : "autofocus")))
							.show_textarea(array('name' => 'strFormImport', 'text' => __("Import Form Fields", 'lang_form'), 'value' => $strFormImport, 'placeholder' => "3,".__("Name", 'lang_form').","))
							.show_button(array('name' => "btnFormPublish", 'text' => __("Add", 'lang_form')))
							.input_hidden(array('name' => "intFormID", 'value' => $obj_form->id))
							.wp_nonce_field('form_update_'.$obj_form->id, '_wpnonce_form_update', true, false)
						."</div>
					</div>
				</form>";
			}

		echo "</div>
	</div>";
}

else
{
	wp_die(__("You don't have permission to edit this form", 'lang_form'));
}