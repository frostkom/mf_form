<?php

$obj_form = new mf_form(array('type' => 'create'));

if($obj_form->check_allow_edit())
{
	$obj_form->fetch_request();

	do_action('fetch_form_request');

	echo $obj_form->save_data();

	$form_status = $obj_form->get_form_status();

	echo "<div class='wrap'>
		<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." (".$obj_form->name.")" : __("Add New", 'lang_form'))."</h2>"
		.get_notification()
		."<div id='poststuff'>";

			if($obj_form->id > 0)
			{
				$obj_form->form2type_id_example = $obj_form->form2type_id > 0 ? $obj_form->form2type_id : 123;

				$form_email_page_shortcodes = "&post_title=".sprintf(__("Title Example | Ticket: %s | %s: %s", 'lang_form'), "[answer_id]", "[label_".$obj_form->form2type_id_example."]", "[answer_".$obj_form->form2type_id_example."]")
					."&content=".sprintf(__("Ticket: %s, Answers: %s", 'lang_form'), "[answer_id]", "[form_fields]");

				if(is_plugin_active("mf_webshop/index.php"))
				{
					$form_email_page_shortcodes .= ($form_email_page_shortcodes != '' ? ", " : "").sprintf(__("Document Types: %s, Products: %s, Product Name: %s, Yes Link: %s, No Link: %s", 'lang_form'), "[doc_types]", "[products]", "[product]", "[link_yes]", "[link_no]");
				}

				$form_answer_page_shortcodes = "&content=".sprintf(__("%sDisplay this...%s%s...or this%s", 'lang_form'), "[if id > 1]", "[end_if]", "[else]", "[end_else]");

				$arr_data_pages = $obj_form->get_pages_for_select();

				echo "<div id='post-body' class='columns-2'>
					<div id='post-body-content'>
						<div class='postbox".($obj_form->form2type_id > 0 ? " active" : "")."'>
							<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
							<form method='post' action='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."' class='mf_form mf_settings inside'>
								<div class='flex_flow'>
									<div>"
										.show_form_alternatives(array('data' => $obj_form->get_form_types_for_select(array('form_type_id' => $obj_form->type_id)), 'name' => 'intFormTypeID', 'value' => $obj_form->type_id, 'class' => "fontawesome"))
									."</div>
									<div>"
										.show_textarea(array('name' => 'strFormTypeText', 'value' => $obj_form->type_text, 'class' => "show_textarea hide", 'placeholder' => __("Text", 'lang_form')))
										."<div class='show_checkbox hide'>
											<h4>".__("Examples", 'lang_form')."</h4>
											<ol class='pointer'>
												<li>".__("I consent to having this website store my submitted information, so that they can respond to my inquiry", 'lang_form')."</li>
												<li>".__("By submitting this form I am aware that I will be sent to another website for payment", 'lang_form')."</li>
											</ol>
										</div>"
										.show_select(array('data' => $obj_form->get_form_checks_for_select(), 'name' => 'intCheckID', 'value' => $obj_form->check_id, 'text' => __("Validate as", 'lang_form'), 'class' => "show_validate_as hide"))
										.show_textfield(array('name' => 'strFormTypePlaceholder', 'text' => __("Placeholder Text", 'lang_form'), 'value' => $obj_form->type_placeholder, 'placeholder' => __("Feel free to write anything you like here", 'lang_form'), 'maxlength' => 100, 'xtra_class' => "show_placeholder"))
										.show_select(array('data' => $obj_form->get_form_tag_types_for_select(), 'name' => 'strFormTypeText2', 'value' => $obj_form->type_text, 'text' => __("Type", 'lang_form'), 'class' => "show_custom_tag hide"))
										."<div class='show_range flex_flow hide'>"
											.show_textfield(array('name' => 'strFormTypeMin', 'text' => __("Min value", 'lang_form'), 'value' => $obj_form->type_min, 'maxlength' => 3, 'size' => 5))
											.show_textfield(array('name' => 'strFormTypeMax', 'text' => __("Max value", 'lang_form'), 'value' => $obj_form->type_max, 'maxlength' => 3, 'size' => 5))
											.show_textfield(array('name' => 'strFormTypeDefault', 'text' => __("Default value", 'lang_form'), 'value' => $obj_form->type_default, 'maxlength' => 3, 'size' => 5))
										."</div>"
										."<div class='show_select'>
											<label>".__("Value", 'lang_form')." <i class='fa fa-info-circle' title='".__("Enter ID, Name and Limit (optional)", 'lang_form')."'></i></label>
											<div class='select_rows'>";

												$count_temp = count($obj_form->arr_type_select_value);

												if($count_temp == 0)
												{
													if($obj_form->form_option_exists)
													{
														$obj_form->arr_type_select_id = array('', '', '');
														$obj_form->arr_type_select_key = array('0', '1', '2');
														$obj_form->arr_type_select_value = array("-- ".__("Choose Here", 'lang_form')." --", __("No", 'lang_form'), __("Yes", 'lang_form'));
														$obj_form->arr_type_select_limit = array('', '', '');
													}

													else
													{
														$obj_form->arr_type_select_id = array('0', '1', '2');
														$obj_form->arr_type_select_value = array("-- ".__("Choose Here", 'lang_form')." --", __("No", 'lang_form'), __("Yes", 'lang_form'));
														$obj_form->arr_type_select_limit = array('', '', '');
													}

													$count_temp = count($obj_form->arr_type_select_value);
												}

												/*echo "ID: ".var_export($obj_form->arr_type_select_id, true)."<br>"
												."Key: ".var_export($obj_form->arr_type_select_key, true)."<br>"
												."Value: ".var_export($obj_form->arr_type_select_value, true)."<br>"
												."Limit: ".var_export($obj_form->arr_type_select_limit, true)."<br>";*/

												if($obj_form->form_option_exists)
												{
													for($i = 0; $i < $count_temp; $i++)
													{
														$is_select_value_used = $obj_form->is_select_value_used(array('form2type_id' => $obj_form->form2type_id, 'option_id' => $obj_form->arr_type_select_id[$i]));

														echo "<div class='option'>"
															.show_textfield(array('name' => 'arrFormTypeSelect_key[]', 'value' => $obj_form->arr_type_select_key[$i], 'placeholder' => __("Key", 'lang_form'), 'xtra_class' => "option_key")) //, 'readonly' => $is_select_value_used //input text is needed when using payment price as ID
															.show_textfield(array('name' => 'arrFormTypeSelect_value[]', 'value' => $obj_form->arr_type_select_value[$i], 'placeholder' => __("Enter Option Here", 'lang_form'), 'xtra_class' => "option_value", 'readonly' => $is_select_value_used, 'xtra' => ($is_select_value_used ? " title='".__("This option has been chosen in a previous answer, so be careful with what you change it to. If you still want to edit this option, just double click on the field.", 'lang_form')."'" : "")))
															.show_textfield(array('type' => 'number', 'name' => 'arrFormTypeSelect_limit[]', 'value' => $obj_form->arr_type_select_limit[$i], 'xtra' => " min='0'", 'xtra_class' => "option_limit"))
															.input_hidden(array('name' => 'arrFormTypeSelect_id[]', 'value' => $obj_form->arr_type_select_id[$i]))
														."</div>";
													}
												}

												else
												{
													for($i = 0; $i < $count_temp; $i++)
													{
														$is_select_value_used = $obj_form->is_select_value_used(array('form2type_id' => $obj_form->form2type_id, 'option_id' => $obj_form->arr_type_select_id[$i]));

														echo "<div class='option'>"
															.show_textfield(array('name' => 'arrFormTypeSelect_id[]', 'value' => $obj_form->arr_type_select_id[$i], 'placeholder' => __("Key", 'lang_form'), 'xtra_class' => "option_key", 'readonly' => $is_select_value_used)) //input text is needed when using payment price as ID
															.show_textfield(array('name' => 'arrFormTypeSelect_value[]', 'value' => $obj_form->arr_type_select_value[$i], 'placeholder' => __("Enter Option Here", 'lang_form'), 'xtra_class' => "option_value")) //, 'readonly' => $is_select_value_used
															.show_textfield(array('type' => 'number', 'name' => 'arrFormTypeSelect_limit[]', 'value' => $obj_form->arr_type_select_limit[$i], 'xtra' => " min='0'", 'xtra_class' => "option_limit"))
														."</div>";
													}
												}

											echo "</div>
										</div>";

										//Advanced
										#################
										echo get_toggler_container(array('type' => 'start', 'text' => __("Advanced", 'lang_form'), 'rel' => $obj_form->id))
											.show_select(array('data' => $obj_form->get_tags_for_select(), 'name' => 'strFormTypeTag', 'value' => $obj_form->type_tag, 'text' => __("Custom HTML Tag", 'lang_form'), 'class' => "show_custom_text_tag hide"))
											.show_textfield(array('name' => 'strFormTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $obj_form->type_class, 'placeholder' => "bold italic aligncenter alignleft alignright", 'maxlength' => 50, 'xtra_class' => "show_custom_class hide"));

											if($obj_form->form2type_id > 0)
											{
												$handle_temp = $obj_form->get_post_info()."_".$obj_form->form2type_id;

												$description_temp = '';

												if(substr($obj_form->type_fetch_from, 0, 1) == "[")
												{
													$description_temp .= sprintf(__("Try it out by %sgoing here%s", 'lang_form'), "<a href='".get_permalink($obj_form->post_id)."'>", "</a>");
												}

												else
												{
													$description_temp .= sprintf(__("Try it out by %sgoing here%s", 'lang_form'), "<a href='".get_permalink($obj_form->post_id)."?".($obj_form->type_fetch_from != '' ? $obj_form->type_fetch_from : $handle_temp)."=2'>", "</a>");
												}

												echo show_textfield(array('name' => 'strFormTypeFetchFrom', 'text' => __("Change Default Value", 'lang_form')." <i class='fa fa-info-circle' title='custom_handle_that_you_can_name_whatever, [user_display_name], [user_email] ".__("or", 'lang_form')." [user_address]'></i>", 'value' => $obj_form->type_fetch_from, 'maxlength' => 50, 'placeholder' => sprintf(__("Assign handle or shortcode", 'lang_form'), $handle_temp), 'xtra_class' => "show_fetch_from hide", 'description' => $description_temp));

												$arr_data_equals = array();

												if($obj_form->form_option_exists)
												{
													switch($obj_form->type_id)
													{
														case 1:
														//case 'checkbox':
															$arr_data_equals[0] = __("No", 'lang_form');
															$arr_data_equals[1] = __("Yes", 'lang_form');
														break;

														default:
															$count_temp = count($obj_form->arr_type_select_value);

															for($i = 0; $i < $count_temp; $i++)
															{
																$arr_data_equals[$obj_form->arr_type_select_id[$i]] = $obj_form->arr_type_select_value[$i];
															}
														break;
													}
												}

												else
												{
													$count_temp = count($obj_form->arr_type_select_value);

													for($i = 0; $i < $count_temp; $i++)
													{
														$arr_data_equals[$obj_form->arr_type_select_id[$i]] = $obj_form->arr_type_select_value[$i];
													}
												}

												if(count($arr_data_equals) > 1)
												{
													list($result, $rows) = $obj_form->get_form_type_info(array('query_type_code' => array('checkbox', 'range', 'input_field', 'textarea', 'text', 'datepicker', 'radio_button', 'select', 'select_multiple', 'custom_tag', 'checkbox_multiple', 'radio_multiple'), 'query_exclude_id' => $obj_form->form2type_id));

													if($rows > 0)
													{
														$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

														echo "<div class='show_actions'>"
															.show_select(array('data' => $arr_data_equals, 'name' => 'strFormTypeActionEquals', 'text' => __("If this equals...", 'lang_form'), 'value' => $obj_form->type_action_equals))
															.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeActionShow', 'text' => __("...show this...", 'lang_form'), 'value' => $obj_form->type_action_show))
														."</div>";
													}

													else
													{
														echo "Nope: ".$wpdb->last_query;
													}
												}
											}

										echo get_toggler_container(array('type' => 'end'));
										#################

									echo "</div>
								</div>"
								.show_button(array('name' => 'btnFormAdd', 'text' => ($obj_form->form2type_id > 0 ? __("Update", 'lang_form') : __("Add", 'lang_form'))));

								if($obj_form->form2type_id > 0)
								{
									echo "&nbsp;<a href='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'>"
										.show_button(array('type' => 'button', 'text' => __("Cancel", 'lang_form'), 'class' => "button"))
									."</a>"
									.input_hidden(array('name' => 'intForm2TypeID', 'value' => $obj_form->form2type_id));
								}

								echo input_hidden(array('name' => 'intFormID', 'value' => $obj_form->id))
								.wp_nonce_field('form_add_'.$obj_form->id, '_wpnonce_form_add', true, false)
							."</form>
						</div>";

						$form_output = $obj_form->process_form(array('edit' => true, 'form2type_id' => $obj_form->form2type_id));

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
									.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $obj_form->name, 'maxlength' => 100, 'required' => 1, 'xtra' => ($obj_form->form2type_id > 0 ? "" : "autofocus")))
									.show_textfield(array('name' => 'strFormURL', 'text' => __("URL", 'lang_form'), 'value' => $obj_form->url, 'maxlength' => 100));

									if($form_output != '')
									{
										echo show_button(array('name' => 'btnFormPublish', 'text' => ($form_status == 'publish' ? __("Save", 'lang_form') : __("Publish", 'lang_form'))));
									}

									echo show_button(array('name' => 'btnFormDraft', 'text' => __("Save Draft", 'lang_form'), 'class' => "button"))
									.input_hidden(array('name' => 'intFormID', 'value' => $obj_form->id))
									.wp_nonce_field('form_update_'.$obj_form->id, '_wpnonce_form_update', true, false);

									if($form_status == 'publish' && $obj_form->id > 0) //post_status -> form_status
									{
										$shortcode = "[mf_form id=".$obj_form->id."]";

										echo show_textfield(array('text' => __("Shortcode", 'lang_form'), 'value' => $shortcode, 'xtra' => "readonly onclick='this.select()'"));

										$result = get_pages_from_shortcode($shortcode);

										if(count($result) > 0)
										{
											foreach($result as $post_id_temp)
											{
												if($obj_form->check_allow_edit())
												{
													echo " <a href='".admin_url("post.php?post=".$post_id_temp."&action=edit")."'>".__("Edit Page", 'lang_form')."</a>";
												}

												$actions['view_page'] = " <a href='".get_permalink($post_id_temp)."'>".__("View", 'lang_form')."</a>";
											}
										}

										else
										{
											if($form_status == 'publish')
											{
												$post_url = get_permalink($obj_form->post_id);

												if($post_url != '')
												{
													$actions['view'] = " <a href='".$post_url."'>".__("View", 'lang_form')."</a>";
												}
											}

											echo " <a href='".admin_url("post-new.php?post_title=".$obj_form->name."&content=".$shortcode)."'>".__("Add New Post", 'lang_form')."</a>";
											echo " <a href='".admin_url("post-new.php?post_type=page&post_title=".$obj_form->name."&content=".$shortcode)."'>".__("Add New Page", 'lang_form')."</a>";
										}
									}

								echo "</div>
							</div>
							<div class='postbox'>
								<h3 class='hndle'><span>".__("Settings", 'lang_form')."</span></h3>
								<div class='inside'>".
									show_select(array('data' => $arr_data_pages, 'name' => 'strFormAnswerURL', 'value' => $obj_form->answer_url, 'text' => __("Confirmation Page", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_answer_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>")) //, 'suffix' => get_option_page_suffix(array('value' => $obj_form->answer_url))
									.show_textfield(array('name' => 'strFormMandatoryText', 'text' => __("Text when mandatory fields have not been entered", 'lang_form'), 'value' => $obj_form->mandatory_text, 'placeholder' => __("Please, enter all required fields", 'lang_form'), 'maxlength' => 100))
									."<div class='flex_flow'>"
										.show_select(array('data' => $obj_form->get_icons_for_select(), 'name' => 'strFormButtonSymbol', 'value' => $obj_form->button_symbol, 'text' => __("Button Symbol", 'lang_form')))
										.show_textfield(array('name' => 'strFormButtonText', 'text' => __("Text", 'lang_form'), 'value' => $obj_form->button_text, 'placeholder' => __("Submit", 'lang_form'), 'maxlength' => 100))
									."</div>"
									.show_textfield(array('type' => 'date', 'name' => 'dteFormDeadline', 'text' => __("Deadline", 'lang_form'), 'value' => $obj_form->deadline, 'xtra' => "min='".date("Y-m-d", strtotime("+1 day"))."'"))
									."<div class='flex_flow'>"
										.show_select(array('data' => get_yes_no_for_select(), 'name' => 'strFormAcceptDuplicates', 'value' => $obj_form->accept_duplicates, 'text' => __("Accept Duplicates", 'lang_form')));

										if($obj_form->is_poll())
										{
											echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => 'intFormShowAnswers', 'text' => __("Show Answers", 'lang_form'), 'value' => $obj_form->show_answers));
										}

										echo show_select(array('data' => get_yes_no_for_select(), 'name' => 'strFormSaveIP', 'value' => $obj_form->save_ip, 'text' => __("Save IP", 'lang_form')))
									."</div>"
								."</div>
							</div>
							<div class='postbox'>
								<h3 class='hndle'><span>".__("E-mail", 'lang_form')."</span></h3>
								<div class='inside'>";

									if($obj_form->email_name != '')
									{
										echo show_textfield(array('name' => 'strFormEmailName', 'text' => __("Subject", 'lang_form'), 'value' => $obj_form->email_name, 'maxlength' => 100));
									}

									echo show_checkbox(array('name' => 'intFormEmailNotify', 'text' => __("Send to Admin", 'lang_form'), 'value' => 1, 'compare' => $obj_form->email_notify))
									.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailNotifyPage', 'value' => $obj_form->email_notify_page, 'text' => __("Template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_email_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>"));

									if($obj_form->has_email_field() > 0)
									{
										echo show_checkbox(array('name' => 'intFormEmailConfirm', 'text' => __("Send to Visitor", 'lang_form'), 'value' => 1, 'compare' => $obj_form->email_confirm))
										.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailConfirmPage', 'value' => $obj_form->email_confirm_page, 'text' => __("Template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_email_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
									}

									echo show_textfield(array('name' => 'strFormEmail', 'text' => __("Send From/To", 'lang_form'), 'value' => $obj_form->email, 'maxlength' => 100, 'placeholder' => get_bloginfo('admin_email')));

									if($obj_form->email != '' && strpos($obj_form->email, "<") == false && strpos($obj_form->email, ",") == false)
									{
										echo show_textfield(array('name' => 'strFormFromName', 'text' => __("Send From/To", 'lang_form')." (".__("Name", 'lang_form').")", 'value' => $obj_form->from_name, 'maxlength' => 100, 'placeholder' => get_bloginfo('name')));
									}

									echo show_textarea(array('name' => 'strFormEmailConditions', 'text' => __("Conditions", 'lang_form'), 'value' => $obj_form->email_conditions, 'placeholder' => "[field_id]|[field_value]|".get_bloginfo('admin_email')))
								."</div>
							</div>";

							$arr_data_providers = $obj_form->get_payment_providers_for_select();

							if(count($arr_data_providers) > 1)
							{
								echo "<div class='postbox'>
									<h3 class='hndle'><span>".__("Payment", 'lang_form')."</span></h3>
									<div class='inside'>"
										.show_select(array('data' => $arr_data_providers, 'name' => 'intFormPaymentProvider', 'value' => $obj_form->payment_provider, 'text' => __("Provider", 'lang_form')));

										$arr_fields = apply_filters('form_payment_fields', array(), $obj_form->payment_provider);

										if(in_array('merchant_id', $arr_fields))
										{
											echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Merchant ID", 'lang_form')." / ".__("E-mail", 'lang_form'), 'value' => $obj_form->payment_merchant, 'maxlength' => 100));
										}

										if(in_array('merchant_username', $arr_fields))
										{
											echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Username", 'lang_form'), 'value' => $obj_form->payment_merchant, 'maxlength' => 100));
										}

										if(in_array('merchant_store', $arr_fields))
										{
											echo show_textfield(array('name' => 'strFormPaymentMerchant', 'text' => __("Store ID", 'lang_form'), 'value' => $obj_form->payment_merchant, 'maxlength' => 100));
										}

										if(in_array('password', $arr_fields))
										{
											echo show_password_field(array('name' => 'strFormPaymentPassword', 'text' => __("Password"), 'value' => $obj_form->payment_password, 'maxlength' => 100));
										}

										if(in_array('secret_key', $arr_fields))
										{
											echo show_password_field(array('name' => 'strFormPaymentHmac', 'text' => __("Secret Key", 'lang_form')." / ".__("Signature", 'lang_form'), 'value' => $obj_form->payment_hmac, 'maxlength' => 200));
										}

										if(in_array('terms_page', $arr_fields))
										{
											$arr_data = array();
											get_post_children(array('add_choose_here' => true), $arr_data);

											$post_title = __("Terms", 'lang_form');

											echo show_select(array('data' => $arr_data, 'name' => 'intFormTermsPage', 'text' => __("Terms Page", 'lang_form'), 'value' => $obj_form->terms_page, 'required' => true, 'suffix' => get_option_page_suffix(array('value' => $obj_form->terms_page, 'title' => $post_title))));
										}

										do_action('display_form_fields', $obj_form);

										if($obj_form->payment_provider > 0 && ($obj_form->payment_merchant != '' || $obj_form->payment_hmac != ''))
										{
											echo show_select(array('data' => $obj_form->get_payment_currency_for_select($obj_form->payment_provider), 'name' => 'strFormPaymentCurrency', 'value' => $obj_form->payment_currency, 'text' => __("Currency", 'lang_form')))
											.show_textfield(array('type' => 'number', 'name' => 'dblFormPaymentCost', 'value' => $obj_form->payment_cost, 'text' => __("Payment Cost", 'lang_form')." (".__("excl. taxes", 'lang_form').")", 'xtra' => "min='0' step='0.1'"));

											$arr_data_amount = $obj_form->get_payment_amount_for_select();

											if(count($arr_data_amount) > 1)
											{
												echo show_select(array('data' => $arr_data_amount, 'name' => 'intFormPaymentAmount', 'value' => $obj_form->payment_amount, 'text' => __("Field for Payment Amount", 'lang_form')));
											}

											echo show_textfield(array('type' => 'number', 'name' => 'intFormPaymentTax', 'value' => $obj_form->payment_tax, 'text' => __("Tax", 'lang_form'), 'xtra' => " min='0' max='25'"));

											$description = "";

											if($obj_form->payment_callback != '' && !function_exists($obj_form->payment_callback))
											{
												$description = "<i class='fa fa-exclamation-triangle yellow'></i> ".__("The action that you have entered either does not exist or is not accessible when the success is triggered", 'lang_form');
											}

											echo show_textfield(array('name' => 'strFormPaymentCallback', 'text' => __("Action on Successful Payment", 'lang_form'), 'value' => $obj_form->payment_callback, 'maxlength' => 100, 'description' => $description));
										}

									echo "</div>
								</div>";
							}

							else if($obj_form->payment_provider > 0)
							{
								do_log(sprintf("There are no installed provider extension even though it seams like a provider has been set (%s)", $obj_form->payment_provider));
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
							.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $obj_form->name, 'maxlength' => 100, 'required' => 1, 'xtra' => ($obj_form->form2type_id > 0 ? "" : "autofocus")))
							.show_textarea(array('name' => 'strFormImport', 'text' => __("Import Form Fields", 'lang_form'), 'value' => $obj_form->import, 'placeholder' => "3,".__("Name", 'lang_form').","))
							.show_button(array('name' => 'btnFormPublish', 'text' => __("Add", 'lang_form')))
							.input_hidden(array('name' => 'intFormID', 'value' => $obj_form->id))
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
	wp_die(__("You do not have permission to edit this form", 'lang_form'));
}