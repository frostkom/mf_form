<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
wp_enqueue_script('jquery-ui-sortable');
mf_enqueue_script('script_touch', plugins_url()."/mf_base/include/jquery.ui.touch-punch.min.js");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url()));

$folder = str_replace("plugins/mf_form/create", "", dirname(__FILE__));

$is_super_admin = current_user_can('install_plugins');

$intQueryID = check_var('intQueryID');

$intQuery2TypeID = check_var('intQuery2TypeID');
$intQuery2TypeOrder = check_var('intQuery2TypeOrder');

$intQueryEmailConfirm = isset($_POST['intQueryEmailConfirm']) ? 1 : 0;
$strQueryEmailConfirmPage = check_var('strQueryEmailConfirmPage');
$intQueryShowAnswers = isset($_POST['intQueryShowAnswers']) ? 1 : 0;
$strQueryName = check_var('strQueryName');
$strQueryURL = check_var('strQueryURL');
$strQueryAnswerURL = check_var('strQueryAnswerURL');
$strQueryEmail = check_var('strQueryEmail', 'email');
$intQueryEmailNotify = check_var('intQueryEmailNotify');
$strQueryEmailName = check_var('strQueryEmailName');
$intQueryImproveUX = isset($_POST['intQueryImproveUX']) ? 1 : 0;
$strQueryEmailCheckConfirm = check_var('strQueryEmailCheckConfirm');
$strQueryMandatoryText = check_var('strQueryMandatoryText');
$strQueryButtonText = check_var('strQueryButtonText');
$strQueryButtonSymbol = check_var('strQueryButtonSymbol');
$intQueryPaymentProvider = check_var('intQueryPaymentProvider');
$strQueryPaymentHmac = check_var('strQueryPaymentHmac');
$strQueryPaymentMerchant = check_var('strQueryPaymentMerchant');
$strQueryPaymentPassword = check_var('strQueryPaymentPassword');
$strQueryPaymentCurrency = check_var('strQueryPaymentCurrency');
//$intQueryPaymentCheck = check_var('intQueryPaymentCheck');
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

if($intQueryID > 0)
{
	$obj_form = new mf_form($intQueryID);
}

echo "<div class='wrap'>";

	if(isset($_POST['btnFormExport']))
	{
		$db_info = "";

		$arr_cols = array("queryTypeID", "queryTypeText", "checkID", "queryTypeTag", "queryTypeClass", "queryTypeRequired", "queryTypeAutofocus", "query2TypeOrder");

		$result = $wpdb->get_results($wpdb->prepare("SELECT ".implode(", ", $arr_cols)." FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d'", $intQueryID));

		foreach($result as $r)
		{
			$db_info .= "queryID = '[query_id]'";

				foreach($arr_cols as $str_col)
				{
					//$r->$str_col = str_replace("\n", "\\n", addslashes($r->$str_col));

					$db_info .= ", ".$str_col." = ".(isset($r->$str_col) ? "'".str_replace("\n", "[nl]", $r->$str_col)."'" : "'NULL'");
				}

			$db_info .= ", query2TypeCreated = NOW(), userID = [user_id]\n";
		}

		if($db_info != '')
		{
			$strQueryURL = $obj_form->get_post_name();

			$file = $strQueryURL."_".date("YmdHis").".sql";

			$success = set_file_content(array('file' => $folder."/uploads/".$file, 'mode' => 'a', 'content' => trim($db_info)));

			$done_text = "Download exported file at <a href='../wp-content/uploads/".$file."'>".$file."</a>";
		}

		else
		{
			$error_text = "It was not possible to export the form";
		}
	}

	else if(isset($_POST['btnFormImport']))
	{
		if(isset($_FILES['strFileForm']))
		{
			$file_name = $_FILES['strFileForm']['name'];
			$file_location = $_FILES['strFileForm']['tmp_name'];

			if($file_name == '')
			{
				$error_text = "You have to submit a file";
			}

			else if(!is_uploaded_file($file_location))
			{
				$error_text = "Could not upload the file for import";
			}

			else
			{
				$inserted = 0;

				$content = get_file_content(array('file' => $file_location));

				$content = str_replace("[query_id]", $intQueryID, $content);
				$content = str_replace("[user_id]", get_current_user_id(), $content);

				$arr_row = explode("\n", trim($content));

				foreach($arr_row as $str_row)
				{
					if($str_row != '')
					{
						$wpdb->query("INSERT INTO ".$wpdb->base_prefix."query2type SET ".str_replace("[nl]", "\n", $str_row));

						if($wpdb->rows_affected > 0)
						{
							$inserted++;
						}
					}
				}

				if($inserted > 0)
				{
					$done_text = $inserted." fields imported to the form";
				}

				else
				{
					$error_text = "No fields were imported";
				}
			}
		}

		else
		{
			$error_text = "There is no file to import";
		}
	}

	else if((isset($_POST['btnFormPublish']) || isset($_POST['btnFormDraft'])) && wp_verify_nonce($_POST['_wpnonce'], 'form_update'))
	{
		if($strQueryName == '')
		{
			$error_text = __("Please, enter all required fields", 'lang_forms');
		}

		else
		{
			if($intQueryID > 0)
			{
				$intPostID = $wpdb->get_var($wpdb->prepare("SELECT postID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryID));

				$post_data = array(
					'ID' => $intPostID,
					//'post_type' => 'mf_form',
					'post_status' => isset($_POST['btnFormPublish']) ? 'publish' : 'draft',
					'post_title' => $strQueryName,
					'post_name' => $strQueryURL
				);

				wp_update_post($post_data);

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET blogID = '".$wpdb->blogid."', queryImproveUX = '%d', queryEmailCheckConfirm = %s, queryEmailConfirm = '%d', queryEmailConfirmPage = %s, queryShowAnswers = '%d', queryName = %s, queryAnswerURL = %s, queryEmail = %s, queryEmailNotify = '%d', queryEmailName = %s, queryMandatoryText = %s, queryButtonText = %s, queryButtonSymbol = %s, queryPaymentProvider = '%d', queryPaymentHmac = %s, queryPaymentMerchant = %s, queryPaymentPassword = %s, queryPaymentCurrency = %s, queryPaymentAmount = '%d' WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryImproveUX, $strQueryEmailCheckConfirm, $intQueryEmailConfirm, $strQueryEmailConfirmPage, $intQueryShowAnswers, $strQueryName, $strQueryAnswerURL, $strQueryEmail, $intQueryEmailNotify, $strQueryEmailName, $strQueryMandatoryText, $strQueryButtonText, $strQueryButtonSymbol, $intQueryPaymentProvider, $strQueryPaymentHmac, $strQueryPaymentMerchant, $strQueryPaymentPassword, $strQueryPaymentCurrency, $intQueryPaymentAmount, $intQueryID)); //, queryPaymentCheck = '%d', $intQueryPaymentCheck
			}

			else
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryName = '%d'", $strQueryName));

				if($wpdb->num_rows > 0)
				{
					$error_text = "There is already a form with that name. Try with another one.";
				}

				else
				{
					$post_data = array(
						'post_type' => 'mf_form',
						'post_status' => isset($_POST['btnFormPublish']) ? 'publish' : 'draft',
						'post_title' => $strQueryName,
					);

					$intPostID = wp_insert_post($post_data);

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query SET blogID = '%d', postID = '%d', queryName = %s, queryCreated = NOW(), userID = '%d'", $wpdb->blogid, $intPostID, $strQueryName, get_current_user_id()));
					$intQueryID = $wpdb->insert_id;
				}
			}

			if($wpdb->rows_affected > 0)
			{
				echo "<script>location.href='".get_site_url()."/wp-admin/admin.php?page=mf_form/create/index.php&intQueryID=".$intQueryID."'</script>";
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
			$error_text = __("Please, enter all required fields", 'lang_forms');
		}

		else
		{
			if($intQueryTypeID == 2)
			{
				$strQueryTypeText = str_replace("|", "", $strQueryTypeText)."|".str_replace("|", "", $strQueryTypeMin)."|".str_replace("|", "", $strQueryTypeMax)."|".str_replace("|", "", $strQueryTypeDefault);
			}

			else if($intQueryTypeID == 10 || $intQueryTypeID == 11)
			{
				$strQueryTypeText = str_replace(":", "", $strQueryTypeText).":".str_replace(":", "", $strQueryTypeSelect);
			}

			else if($intQueryTypeID == 13 || $intQueryTypeID == 14)
			{
				$strQueryTypeText = $strQueryTypeText2;
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
					$error_text = __("Couldn't update the field", 'lang_forms');
				}
			}

			else
			{
				if($intQueryID > 0 && $intQueryTypeID > 0 && ($intQueryTypeID == 6 || $intQueryTypeID == 9 || $strQueryTypeText != ''))
				{
					$intQuery2TypeOrder = $wpdb->get_var($wpdb->prepare("SELECT query2TypeOrder + 1 FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeOrder DESC", $intQueryID));

					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET queryID = '%d', queryTypeID = '%d', queryTypeText = %s, queryTypePlaceholder = %s, checkID = '%d', queryTypeTag = %s, queryTypeClass = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $intQueryID, $intQueryTypeID, $strQueryTypeText, $strQueryTypePlaceholder, $intCheckID, $strQueryTypeTag, $strQueryTypeClass, $intQuery2TypeOrder, get_current_user_id()));

					if($intQueryTypeID == 13)
					{
						$intQuery2TypeID = $wpdb->insert_id;
						$intQueryTypeID = 14;
						$intQuery2TypeOrder++;

						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type SET query2TypeID2 = '%d', queryID = '%d', queryTypeID = '%d', queryTypeText = %s, queryTypeClass = %s, query2TypeOrder = '%d', query2TypeCreated = NOW(), userID = '%d'", $intQuery2TypeID, $intQueryID, $intQueryTypeID, $strQueryTypeText, $strQueryTypeClass, $intQuery2TypeOrder, get_current_user_id()));
					}

					if($wpdb->rows_affected > 0)
					{
						$intQuery2TypeID = $intQueryTypeID = $strQueryTypeText = $strQueryTypePlaceholder = $intCheckID = $strQueryTypeTag = $strQueryTypeClass = "";
					}
				}

				else
				{
					$error_text = __("Couldn't insert the new field", 'lang_forms');
				}
			}
		}

		if($intQueryTypeID == 0)
		{
			echo "<script>location.href='".get_site_url()."/wp-admin/admin.php?page=mf_form/create/index.php&intQueryID=".$intQueryID."'</script>";
		}
	}

	if($intQueryID > 0)
	{
		if(isset($_GET['recover']))
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryDeleted = '0' WHERE queryID = '%d'", $intQueryID));
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryImproveUX, queryEmailCheckConfirm, queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryName, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailName, queryMandatoryText, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryPaymentAmount, queryCreated FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryID)); //, queryPaymentCheck
		$r = $result[0];
		$intQueryImproveUX = $r->queryImproveUX;
		$strQueryEmailCheckConfirm = $r->queryEmailCheckConfirm;
		$intQueryEmailConfirm = $r->queryEmailConfirm;
		$strQueryEmailConfirmPage = $r->queryEmailConfirmPage;
		$intQueryShowAnswers = $r->queryShowAnswers;
		$strQueryName = $r->queryName;
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
		//$intQueryPaymentCheck = $r->queryPaymentCheck;
		$intQueryPaymentAmount = $r->queryPaymentAmount;
		$strQueryCreated = $r->queryCreated;

		$strQueryURL = $obj_form->get_post_name();
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

		if($intQueryTypeID == 2)
		{
			list($strQueryTypeText, $strQueryTypeMin, $strQueryTypeMax, $strQueryTypeDefault) = explode("|", $strQueryTypeText);
		}

		else if($intQueryTypeID == 10 || $intQueryTypeID == 11)
		{
			list($strQueryTypeText, $strQueryTypeSelect) = explode(":", $strQueryTypeText);
		}

		if(isset($_GET['btnFieldCopy']))
		{
			$intQuery2TypeID = "";
		}
	}

	echo "<h2>".($intQueryID > 0 ? __("Update", 'lang_forms')." ".$strQueryName : __("Add New", 'lang_forms'))."</h2>"
	.get_notification()
	."<div id='poststuff'>";

		if($intQueryID > 0)
		{
			echo "<div class='columns-2' id='post-body'>
				<div id='post-body-content'>
					<div class='postbox".($intQuery2TypeID > 0 ? " active" : "")."'>
						<h3 class='hndle'><span>".__("Content", 'lang_forms')."</span></h3>
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

										echo show_textfield(array('name' => 'intQueryTypeID_name', 'text' => __("Type", 'lang_forms'), 'value' => $strQueryTypeName, 'xtra' => "readonly"))
										.input_hidden(array('name' => 'intQueryTypeID', 'value' => $intQueryTypeID, 'xtra' => "id='intQueryTypeID'"));
									}

									else
									{
										$arr_data = array();

										$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");

										$result = $wpdb->get_results("SELECT queryTypeID, queryTypeName, COUNT(queryTypeID) AS queryType_amount FROM ".$wpdb->base_prefix."query_type LEFT JOIN ".$wpdb->base_prefix."query2type USING (queryTypeID) WHERE queryTypePublic = 'yes' GROUP BY queryTypeID ORDER BY queryType_amount DESC, queryTypeName ASC");

										foreach($result as $r)
										{
											if($intQueryTypeID > 0 || $r->queryTypeID != 13)
											{
												$arr_data[] = array($r->queryTypeID, $r->queryTypeName);
											}
										}

										echo show_select(array('data' => $arr_data, 'name' => 'intQueryTypeID', 'compare' => $intQueryTypeID, 'text' => __("Type", 'lang_forms')));
									}

									echo show_textarea(array('name' => 'strQueryTypeText', 'text' => __("Text", 'lang_forms'), 'value' => $strQueryTypeText, 'class' => "tr_text")); //, 'wysiwyg' => true, 'size' => 'small'

									$arr_data = array();

									//$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");

									$arr_data[] = array('div', "div");
									$arr_data[] = array('fieldset', "fieldset");

									echo show_select(array('data' => $arr_data, 'name' => 'strQueryTypeText2', 'compare' => $strQueryTypeText, 'text' => __("Type", 'lang_forms'), 'class' => "tr_tag2"))
								."</div>
								<div class='alignright'>";

									$arr_data = array();

									$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");

									$result = $wpdb->get_results("SELECT checkID, checkName FROM ".$wpdb->base_prefix."query_check WHERE checkPublic = '1' ORDER BY checkName ASC");
									$rows = $wpdb->num_rows;

									foreach($result as $r)
									{
										$arr_data[] = array($r->checkID, __($r->checkName, 'lang_forms'));
									}

									echo show_select(array('data' => $arr_data, 'name' => "intCheckID", 'compare' => $intCheckID, 'text' => __("Validate as", 'lang_forms'), 'class' => "tr_check"))
									.show_textfield(array('name' => 'strQueryTypePlaceholder', 'text' => __("Placeholder Text", 'lang_forms'), 'value' => $strQueryTypePlaceholder, 'placeholder' => __("Feel free to write anything you like here", 'lang_forms'), 'maxlength' => 100, 'xtra_class' => "tr_placeholder"))
									.show_textfield(array('name' => 'strQueryTypeTag', 'text' => __("Custom HTML Tag", 'lang_forms'), 'value' => $strQueryTypeTag, 'placeholder' => "h1, h2, h3, h4, h5, p, blockquote", 'maxlength' => 20, 'xtra_class' => "tr_tag"))
									.show_textfield(array('name' => 'strQueryTypeClass', 'text' => __("Custom CSS class", 'lang_forms'), 'value' => $strQueryTypeClass, 'placeholder' => "bold italic", 'maxlength' => 50))
									."<div class='tr_range'>"
										.show_textfield(array('name' => 'strQueryTypeMin', 'text' => __("Min value", 'lang_forms'), 'value' => $strQueryTypeMin, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strQueryTypeMax', 'text' => __("Max value", 'lang_forms'), 'value' => $strQueryTypeMax, 'maxlength' => 3, 'size' => 5))
										.show_textfield(array('name' => 'strQueryTypeDefault', 'text' => __("Default value", 'lang_forms'), 'value' => $strQueryTypeDefault, 'maxlength' => 3, 'size' => 5))
									."</div>
									<div class='tr_select'>
										<label>".__("Value", 'lang_forms')."</label>
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
													.show_textfield(array('name' => 'strQueryTypeSelect_value', 'value' => $arr_select_row_content[1], 'placeholder' => __("Enter option here", 'lang_forms')))
												."</div>";
											}

										echo "</div>"
										.input_hidden(array('name' => 'strQueryTypeSelect', 'value' => $strQueryTypeSelect))
									."</div>
								</div>
								<div class='clear'></div>"
								.show_submit(array('name' => "btnFormAdd", 'text' => ($intQuery2TypeID > 0 ? __("Update", 'lang_forms') : __("Add", 'lang_forms')), 'class' => "button-primary"));

								if($intQuery2TypeID > 0)
								{
									echo "&nbsp;<a href='?page=mf_form/create/index.php&intQueryID=".$intQueryID."'>"
										.show_submit(array('text' => __("Cancel", 'lang_forms'), 'type' => "button", 'class' => "button"))
									."</a>"
									.input_hidden(array('name' => 'intQuery2TypeID', 'value' => $intQuery2TypeID));
								}

								echo input_hidden(array('name' => 'intQueryID', 'value' => $intQueryID))
								.wp_nonce_field('form_add', '_wpnonce', true, false)
							."</form>
						</div>
					</div>";

					$form_output = show_query_form(array('query_id' => $intQueryID, 'edit' => true, 'query2type_id' => $intQuery2TypeID));

					if($form_output != '')
					{
						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("Overview", 'lang_forms')."</span></h3>
							<div class='inside'>"
								.$form_output
							."</div>
						</div>";
					}

				echo "</div>
				<div id='postbox-container-1'>
					<form method='post' action='' class='mf_form mf_settings'>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Publish", 'lang_forms')."</span></h3>
							<div class='inside'>"
								.show_textfield(array('name' => 'strQueryName', 'text' => __("Name", 'lang_forms'), 'value' => $strQueryName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intQuery2TypeID > 0 ? "" : "autofocus")))
								.show_textfield(array('name' => 'strQueryURL', 'text' => __("Permalink", 'lang_forms'), 'value' => $strQueryURL, 'maxlength' => 100, 'required' => 1))
								."<div>"
									.show_submit(array('name' => "btnFormPublish", 'text' =>  __("Publish", 'lang_forms'), 'class' => "button-primary"))."&nbsp;"
									.show_submit(array('name' => "btnFormDraft", 'text' => __("Save Draft", 'lang_forms'), 'class' => "button"))
								."</div>"
								.input_hidden(array('name' => "intQueryID", 'value' => $intQueryID))
								.wp_nonce_field('form_update', '_wpnonce', true, false);

								//$obj_form = new mf_form();

								$intPostID = $obj_form->get_post_id(); //$intQueryID

								if($obj_form->is_published(array('post_id' => $intPostID)))
								{
									$post_url = get_permalink($intPostID);

									if($post_url != '')
									{
										echo "<br>
										<a href='".$post_url."'>".__("View form", 'lang_forms')."</a>";
									}
								}

							echo "</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Settings", 'lang_forms')."</span></h3>
							<div class='inside'>";

								$arr_data = array();

								$arr_data[] = array("", "-- ".__("Choose page here", 'lang_forms')." --");

								$arr_sites = array();

								if(is_multisite())
								{
									$result = $wpdb->get_results("SELECT blog_id, domain FROM ".$wpdb->base_prefix."blogs ORDER BY blog_id ASC");

									foreach($result as $r)
									{
										$blog_id = $r->blog_id;
										$domain = $r->domain;

										if($is_super_admin || $blog_id == $wpdb->blogid)
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

								echo show_select(array('data' => $arr_data, 'name' => 'strQueryAnswerURL', 'compare' => $strQueryAnswerURL, 'text' => __("Confirmation page", 'lang_forms')));

								$has_email_field = $wpdb->get_var($wpdb->prepare("SELECT COUNT(queryTypeID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_check USING (checkID) WHERE queryID = '%d' AND queryTypeID = '3' AND checkCode = 'email'", $intQueryID));

								if($has_email_field > 0)
								{
									echo show_checkbox(array('name' => 'intQueryEmailConfirm', 'text' => __("Send e-mail confirmation to questionnaire", 'lang_forms'), 'value' => 1, 'compare' => $intQueryEmailConfirm))
									.show_select(array('data' => $arr_data, 'name' => 'strQueryEmailConfirmPage', 'compare' => $strQueryEmailConfirmPage, 'text' => __("E-mail confirmation content", 'lang_forms'), 'class' => "query_email_confirm_page".($intQueryEmailConfirm == 1 ? " " : " hide"), 'description' => __("If you don't choose a page, the content of the form will be sent as content", 'lang_forms')));
								}

								$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID", $intQueryID));
								$intQueryTotal = $wpdb->num_rows;

								$is_poll = is_poll(array('query_id' => $intQueryID));

								if($intQueryImproveUX == 1 || current_user_can('update_core'))
								{
									echo show_checkbox(array('name' => 'intQueryImproveUX', 'text' => __("Improve UX", 'lang_forms'), 'value' => 1, 'compare' => $intQueryImproveUX));
								}
								
								if($obj_form->is_form_field_type_used(array('query_type_id' => 3, 'required' => true, 'check_code' => 'email')))
								{
									echo show_checkbox(array('name' => 'strQueryEmailCheckConfirm', 'text' => __("Make questionnaire confirm their e-mail", 'lang_forms'), 'value' => 'yes', 'compare' => $strQueryEmailCheckConfirm));
								}

								if($is_poll)
								{
									echo show_checkbox(array('name' => 'intQueryShowAnswers', 'text' => __("Show Answers", 'lang_forms'), 'value' => 1, 'compare' => $intQueryShowAnswers));
								}

								echo show_textfield(array('name' => 'strQueryEmail', 'text' => __("Send e-mail from/to", 'lang_forms'), 'value' => $strQueryEmail, 'maxlength' => 100));

								if($strQueryEmail != '')
								{
									echo show_checkbox(array('name' => 'intQueryEmailNotify', 'text' => __("Send notification on new answer", 'lang_forms'), 'value' => 1, 'compare' => $intQueryEmailNotify));
								}

								else
								{
									echo input_hidden(array('name' => "intQueryEmailNotify", 'value' => 1));
								}

								if($strQueryEmailName != '')
								{
									echo show_textfield(array('name' => 'strQueryEmailName', 'text' => __("Subject", 'lang_forms'), 'value' => $strQueryEmailName, 'maxlength' => 100));
								}

							echo "</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Button", 'lang_forms')."</span></h3>
							<div class='inside'>"
								.show_textfield(array('name' => 'strQueryButtonText', 'text' => __("Text", 'lang_forms'), 'value' => $strQueryButtonText, 'placeholder' => __("Submit", 'lang_forms'), 'maxlength' => 100));

								$arr_data = array();

								$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");

								$obj_font_icons = new mf_font_icons();
								$arr_icons = $obj_font_icons->get_array();

								foreach($arr_icons as $icon)
								{
									$arr_data[] = $icon;
								}

								echo show_select(array('data' => $arr_data, 'name' => 'strQueryButtonSymbol', 'compare' => $strQueryButtonSymbol, 'text' => __("Symbol", 'lang_forms')))
							."</div>
						</div>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("Payment", 'lang_forms')."</span></h3>
							<div class='inside'>";

								$arr_data = array();

								$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");
								$arr_data[] = array(1, __("DIBS", 'lang_forms'));
								$arr_data[] = array(3, __("Paypal", 'lang_forms'));
								$arr_data[] = array(2, __("Skrill", 'lang_forms'));

								echo show_select(array('data' => $arr_data, 'name' => 'intQueryPaymentProvider', 'compare' => $intQueryPaymentProvider, 'text' => __("Provider", 'lang_forms')));

								if($intQueryPaymentProvider == 1)
								{
									echo show_textfield(array('name' => 'strQueryPaymentMerchant', 'text' => __("Merchant ID", 'lang_forms'), 'value' => $strQueryPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentHmac', 'text' => __("HMAC key", 'lang_forms'), 'value' => $strQueryPaymentHmac, 'maxlength' => 200));
								}

								else if($intQueryPaymentProvider == 3)
								{
									echo show_textfield(array('name' => 'strQueryPaymentMerchant', 'text' => __("Username", 'lang_forms'), 'value' => $strQueryPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentPassword', 'text' => __("Password", 'lang_forms'), 'value' => $strQueryPaymentPassword, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentHmac', 'text' => __("Signature", 'lang_forms'), 'value' => $strQueryPaymentHmac, 'maxlength' => 200));
								}

								else if($intQueryPaymentProvider == 2)
								{
									echo show_textfield(array('name' => 'strQueryPaymentMerchant', 'text' => __("Merchant E-mail", 'lang_forms'), 'value' => $strQueryPaymentMerchant, 'maxlength' => 100))
									.show_textfield(array('name' => 'strQueryPaymentHmac', 'text' => __("Secret word", 'lang_forms'), 'value' => $strQueryPaymentHmac, 'maxlength' => 200));
								}

								if($intQueryPaymentProvider > 0 && $strQueryPaymentMerchant != '' && $strQueryPaymentHmac != '')
								{
									$arr_data = array();

									$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");

									switch($intQueryPaymentProvider)
									{
										case 1:
											$arr_data[] = array(208, __("Danish Krone", 'lang_forms')." (DKK)");
											$arr_data[] = array(978, __("Euro", 'lang_forms')." (EUR)");
											$arr_data[] = array(840, __("US Dollar", 'lang_forms')." (USD)");
											$arr_data[] = array(826, __("English Pound", 'lang_forms')." (GBP)");
											$arr_data[] = array(752, __("Swedish Krona", 'lang_forms')." (SEK)");
											$arr_data[] = array(036, __("Australian Dollar", 'lang_forms')." (AUD)");
											$arr_data[] = array(124, __("Canadian Dollar", 'lang_forms')." (CAD)");
											$arr_data[] = array(352, __("Icelandic Krona", 'lang_forms')." (ISK)");
											$arr_data[] = array(392, __("Japanese Yen", 'lang_forms')." (JPY)");
											$arr_data[] = array(554, __("New Zealand Dollar", 'lang_forms')." (NZD)");
											$arr_data[] = array(578, __("Norwegian Krone", 'lang_forms')." (NOK)");
											$arr_data[] = array(756, __("Swiss Franc", 'lang_forms')." (CHF)");
											$arr_data[] = array(949, __("Turkish Lira", 'lang_forms')." (TRY)");
										break;

										case 2:
										case 3:
											$arr_data[] = array("DKK", __("Danish Krone", 'lang_forms')." (DKK)");
											$arr_data[] = array("EUR", __("Euro", 'lang_forms')." (EUR)");
											$arr_data[] = array("USD", __("US Dollar", 'lang_forms')." (USD)");
											$arr_data[] = array("GBP", __("English Pound", 'lang_forms')." (GBP)");
											$arr_data[] = array("SEK", __("Swedish Krona", 'lang_forms')." (SEK)");
											$arr_data[] = array("AUD", __("Australian Dollar", 'lang_forms')." (AUD)");
											$arr_data[] = array("CAD", __("Canadian Dollar", 'lang_forms')." (CAD)");
											$arr_data[] = array("ISK", __("Icelandic Krona", 'lang_forms')." (ISK)");
											$arr_data[] = array("JPY", __("Japanese Yen", 'lang_forms')." (JPY)");
											$arr_data[] = array("NZD", __("New Zealand Dollar", 'lang_forms')." (NZD)");
											$arr_data[] = array("NOK", __("Norwegian Krone", 'lang_forms')." (NOK)");
											$arr_data[] = array("CHF", __("Swiss Franc", 'lang_forms')." (CHF)");
											$arr_data[] = array("TRY", __("Turkish Lira", 'lang_forms')." (TRY)");
										break;
									}

									$arr_data = array_sort(array('array' => $arr_data, 'on' => 1));

									echo show_select(array('data' => $arr_data, 'name' => 'strQueryPaymentCurrency', 'compare' => $strQueryPaymentCurrency, 'text' => __("Currency", 'lang_forms')));

									$arr_data = array();

									$arr_data[] = array("", "-- ".__("Choose here", 'lang_forms')." --");

									//$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, query2TypeOrder FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND (queryTypeID = '10' OR queryTypeID = '12') ORDER BY query2TypeOrder ASC", $intQueryID));
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

									echo show_select(array('data' => $arr_data, 'name' => 'intQueryPaymentAmount', 'compare' => $intQueryPaymentAmount, 'text' => __("Field for payment cost", 'lang_forms')));
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
					<h3 class='hndle'><span>".__("Add new", 'lang_forms')."</span></h3>
					<div class='inside'>"
						.show_textfield(array('name' => 'strQueryName', 'text' => __("Name", 'lang_forms'), 'value' => $strQueryName, 'maxlength' => 100, 'required' => 1, 'xtra' => ($intQuery2TypeID > 0 ? "" : "autofocus")))
						.show_submit(array('name' => "btnFormPublish", 'text' => __("Add", 'lang_forms'), 'class' => "button-primary"))
						.input_hidden(array('name' => "intQueryID", 'value' => $intQueryID))
						.wp_nonce_field('form_update', '_wpnonce', true, false)
					."</div>
				</div>
			</form>";
		}

	echo "</div>
</div>";