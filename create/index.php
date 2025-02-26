<?php

$obj_base = new mf_base();
$obj_form = new mf_form(array('type' => 'create'));

if($obj_form->check_allow_edit())
{
	$obj_form->fetch_request();

	do_action('fetch_form_request');

	echo $obj_form->save_data();

	$obj_form->post_status = $obj_form->get_form_status();

	echo "<div class='wrap'>
		<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." <span>".$obj_form->name."</span>" : __("Add New", 'lang_form'))."</h2>"
		.get_notification()
		."<div id='poststuff'>";

			if($obj_form->id > 0)
			{
				$form2type_id_example = 123;

				$form_email_page_shortcodes = "&post_title=".sprintf(__("Title Example | Ticket: %s | %s: %s", 'lang_form'), "[answer_id]", "[label_".($obj_form->form2type_id > 0 ? $obj_form->form2type_id : 123)."]", "[answer_".$form2type_id_example."]")
					."&content=".sprintf(__("Ticket: %s, Answers: %s", 'lang_form'), "[answer_id]", "[form_fields]");

				if(is_plugin_active("mf_webshop/index.php"))
				{
					$form_email_page_shortcodes .= ($form_email_page_shortcodes != '' ? ", " : "").sprintf(__("Document Types: %s, Products: %s, Product Name: %s, Yes Link: %s, No Link: %s", 'lang_form'), "[doc_types]", "[products]", "[product]", "[link_yes]", "[link_no]");
				}

				$form_answer_page_shortcodes = "&content=".sprintf(__("%sDisplay this...%s%s...or this%s", 'lang_form'), "[if id > 1]", "[end_if]", "[else]", "[end_else]");

				$arr_data_pages = array();
				get_post_children(array('add_choose_here' => true), $arr_data_pages);

				$has_single_action = ($obj_form->type_action_equals != '' && $obj_form->type_action_show > 0);
				$has_multiple_action = false;

				if($obj_form->form_option_exists)
				{
					foreach($obj_form->arr_type_select_action as $key => $value)
					{
						if($value > 0)
						{
							$has_multiple_action = true;
							break;
						}
					}
				}

				echo "<div id='post-body' class='columns-2'>
					<div id='post-body-content'>
						<div class='postbox".($obj_form->form2type_id > 0 ? " active" : "")."'>
							<h3 class='hndle'><span>".__("Content", 'lang_form')."</span></h3>
							<form method='post' action='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."' class='mf_form mf_settings inside'>
								<div".($has_single_action == false || $has_multiple_action == true ? "" : " class='flex_flow'").">"
									."<div>"
										.show_form_alternatives(array('data' => $obj_form->get_form_types_for_select(array('form_type_id' => $obj_form->type_id)), 'name' => 'intFormTypeID', 'value' => $obj_form->type_id, 'class' => "fontawesome"))
									."</div>
									<div>"
										.show_textarea(array('name' => 'strFormTypeText', 'text' => __("Text", 'lang_form'), 'value' => $obj_form->type_text, 'class' => "show_textarea hide", 'placeholder' => __("Text", 'lang_form')))
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
											<label>".__("Value", 'lang_form')." <i class='fa fa-info-circle' title='".__("Enter ID, Name, Limit and Field to be displayed if option is chosen", 'lang_form')."'></i></label>";

											if($obj_form->type_connect_to > 0)
											{
												$notice_text = sprintf(__("This field is connected to %s and cannot be edited", 'lang_form'), "<strong>".$obj_form->get_field_name($obj_form->type_connect_to)."</strong>");

												echo get_notification();
											}

											echo "<div class='select_rows".($obj_form->type_connect_to > 0 ? " is_disabled" : "")."'>";

												$count_temp = count($obj_form->arr_type_select_value);

												if($count_temp == 0)
												{
													if($obj_form->form_option_exists)
													{
														$obj_form->arr_type_select_id = array('', '', '');
														$obj_form->arr_type_select_key = array('0', '1', '2');
														$obj_form->arr_type_select_value = array("-- ".__("Choose Here", 'lang_form')." --", __("No", 'lang_form'), __("Yes", 'lang_form'));
														$obj_form->arr_type_select_limit = array('', '', '');
														$obj_form->arr_type_select_action = array('', '', '');
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
												."Limit: ".var_export($obj_form->arr_type_select_limit, true)."<br>"
												."Action: ".var_export($obj_form->arr_type_select_action, true)."<br>";*/

												if($obj_form->form_option_exists)
												{
													$arr_data_show = array();

													if($has_single_action == false || $has_multiple_action == true)
													{
														list($result, $rows) = $obj_form->get_form_type_info(array('query_type_code' => array('checkbox', 'range', 'input_field', 'textarea', 'text', 'datepicker', 'radio_button', 'select', 'select_multiple', 'custom_tag', 'checkbox_multiple', 'radio_multiple'), 'query_exclude_id' => $obj_form->form2type_id));

														if($rows > 0)
														{
															$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));
														}
													}

													for($i = 0; $i < $count_temp; $i++)
													{
														if(isset($obj_form->arr_type_select_id[$i]))
														{
															$is_select_value_used = $obj_form->is_select_value_used(array('form2type_id' => $obj_form->form2type_id, 'option_id' => $obj_form->arr_type_select_id[$i]));

															echo "<div class='option'>"
																//.show_textfield(array('name' => 'arrFormTypeSelect_key[]', 'value' => $obj_form->arr_type_select_key[$i], 'placeholder' => __("Key", 'lang_form'), 'xtra_class' => "option_key")) //, 'readonly' => $is_select_value_used //input text is needed when using payment price as ID
																.input_hidden(array('name' => 'arrFormTypeSelect_key[]', 'value' => $obj_form->arr_type_select_key[$i]))
																.show_textfield(array('name' => 'arrFormTypeSelect_value[]', 'value' => $obj_form->arr_type_select_value[$i], 'placeholder' => __("Enter Option Here", 'lang_form'), 'xtra_class' => "option_value", 'readonly' => $is_select_value_used, 'xtra' => ($is_select_value_used ? " title='".__("This option has been chosen in a previous answer, so be careful with what you change it to. If you still want to edit this option, just double click on the field.", 'lang_form')."'" : "")))
																.show_textfield(array('type' => 'number', 'name' => 'arrFormTypeSelect_limit[]', 'value' => $obj_form->arr_type_select_limit[$i], 'xtra' => " min='0'", 'xtra_class' => "option_limit"));

																if(count($arr_data_show) > 0)
																{
																	echo show_select(array('data' => $arr_data_show, 'name' => 'arrFormTypeSelect_action[]', 'value' => $obj_form->arr_type_select_action[$i], 'multiple' => false, 'class' => "option_action"));
																}

																echo input_hidden(array('name' => 'arrFormTypeSelect_id[]', 'value' => $obj_form->arr_type_select_id[$i]))
															."</div>";
														}
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
											.show_textfield(array('name' => 'strFormTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $obj_form->type_class, 'placeholder' => "bold italic aligncenter alignleft alignright flex_flow", 'maxlength' => 50, 'xtra_class' => "show_custom_class hide"))
											.show_textfield(array('type' => 'number', 'name' => 'intFormTypeLength', 'text' => __("Max Length", 'lang_form'), 'value' => $obj_form->type_length, 'xtra_class' => "show_custom_length hide", 'xtra' => " min='0'"));

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

												echo show_textarea(array('name' => 'strFormTypeFetchFrom', 'text' => __("Change Default Value", 'lang_form')." <i class='fa fa-info-circle' title='custom_handle_that_you_can_name_whatever, [user_display_name], [user_email] ".__("or", 'lang_form')." [user_address]'></i>", 'value' => $obj_form->type_fetch_from, 'maxlength' => 50, 'placeholder' => sprintf(__("Assign handle or shortcode", 'lang_form'), $handle_temp), 'xtra_class' => "show_fetch_from hide", 'description' => $description_temp));

												// Connect to another select
												##############################
												if($obj_form->form_option_exists)
												{
													list($result, $rows) = $obj_form->get_form_type_info(array('query_type_code' => array('select'), 'query_exclude_id' => $obj_form->form2type_id));

													if($rows > 0)
													{
														$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

														echo "<div class='show_select'>"
															.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeConnectTo', 'text' => __("Connect to", 'lang_form'), 'value' => $obj_form->type_connect_to))
														."</div>";
													}
												}
												##############################

												// Actions
												##############################
												if($has_single_action == true || $has_multiple_action == false)
												{
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
																.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeActionShow', 'text' => __("...display this...", 'lang_form'), 'value' => $obj_form->type_action_show))
															."</div>";
														}

														/*else
														{
															echo "Nope: ".$wpdb->last_query;
														}*/
													}
												}
												##############################
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

						echo "<div class='postbox'>
							<h3 class='hndle'><span>".__("Overview", 'lang_form')."</span></h3>
							<div class='inside'>";

								if($form_output != '')
								{
									echo $form_output;
								}

								else
								{
									echo "<em>".__("There are no fields in this form so far. Add a few and they will display here.", 'lang_form')."</em>";
								}

							echo "</div>
						</div>";

					echo "</div>
					<div id='postbox-container-1'>
						<form method='post' action='' class='mf_form mf_settings'>
							<div class='postbox'>
								<h3 class='hndle'><span>".__("Save", 'lang_form')."</span></h3>
								<div class='inside'>";

									/*echo show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $obj_form->name, 'maxlength' => 100, 'required' => true, 'xtra' => ($obj_form->form2type_id > 0 ? "" : "autofocus")))
									.show_textfield(array('name' => 'strFormURL', 'text' => __("URL", 'lang_form'), 'value' => $obj_form->url, 'maxlength' => 100));*/

									if($form_output != '')
									{
										echo show_button(array('name' => 'btnFormPublish', 'text' => ($obj_form->post_status == 'publish' ? __("Save", 'lang_form') : __("Publish", 'lang_form'))));
									}

									echo show_button(array('name' => 'btnFormDraft', 'text' => __("Save Draft", 'lang_form'), 'class' => "button"))
									.input_hidden(array('name' => 'intFormID', 'value' => $obj_form->id))
									.wp_nonce_field('form_update_'.$obj_form->id, '_wpnonce_form_update', true, false);

									if($obj_form->post_status == 'publish' && $obj_form->id > 0)
									{
										echo "<div".get_form_button_classes("display_on_hover").">";

											$actions = $obj_form->filter_actions(array('class' => "button"));

											foreach($actions as $key => $value)
											{
												echo " ".$value;
											}

										echo "</div>";
									}

								echo "</div>
							</div>";

							echo "<div class='postbox'>
								<h3 class='hndle'><span>".__("E-mail", 'lang_form')."</span></h3>
								<div class='inside'>";

									if($obj_form->email_name != '')
									{
										echo show_textfield(array('name' => 'strFormEmailName', 'text' => __("Subject", 'lang_form'), 'value' => $obj_form->email_name, 'maxlength' => 100));
									}

									echo show_checkbox(array('name' => 'intFormEmailNotify', 'text' => __("Send to Admin", 'lang_form'), 'value' => 1, 'compare' => $obj_form->email_notify))
									.show_textfield(array('name' => 'strFormEmail', 'text' => __("Send To", 'lang_form'), 'value' => $obj_form->email_admin, 'maxlength' => 100, 'placeholder' => get_bloginfo('admin_email')));

									/*if($obj_form->email_admin != '' && strpos($obj_form->email_admin, "<") == false && strpos($obj_form->email_admin, ",") == false)
									{
										echo show_textfield(array('name' => 'strFormFromName', 'text' => __("Send To", 'lang_form')." (".__("Name", 'lang_form').")", 'value' => $obj_form->email_admin_name, 'maxlength' => 100, 'placeholder' => get_bloginfo('name')));
									}*/

									echo show_select(array('data' => $obj_form->get_email_notify_from_for_select(), 'name' => 'strFormEmailNotifyFrom', 'value' => $obj_form->email_notify_from, 'text' => __("From", 'lang_form')))
									."<div class='email_notify_div'>"
										.show_textfield(array('name' => 'strFormEmailNotifyFromEmail', 'text' => __("Send From", 'lang_form'), 'value' => $obj_form->email_notify_from_email, 'maxlength' => 100)) //, 'placeholder' => get_bloginfo('admin_email')
										.show_textfield(array('name' => 'strFormEmailNotifyFromEmailName', 'text' => __("Send From", 'lang_form')." (".__("Name", 'lang_form').")", 'value' => $obj_form->email_notify_from_email_name, 'maxlength' => 100)) //, 'placeholder' => get_bloginfo('name')
									."</div>"
									.show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailNotifyPage', 'value' => $obj_form->email_notify_page, 'text' => __("Template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_email_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>"));

									$int_email_fields = $obj_form->has_email_field();

									if($int_email_fields > 0)
									{
										echo show_checkbox(array('name' => 'intFormEmailConfirm', 'text' => __("Send to Visitor", 'lang_form'), 'value' => 1, 'compare' => $obj_form->email_confirm))
										."<div class='email_confirm_div'>"
											.show_textfield(array('name' => 'strFormEmailConfirmFromEmail', 'text' => __("Send From", 'lang_form'), 'value' => $obj_form->email_confirm_from_email, 'maxlength' => 100)) //, 'placeholder' => get_bloginfo('admin_email')
											.show_textfield(array('name' => 'strFormEmailConfirmFromEmailName', 'text' => __("Send From", 'lang_form')." (".__("Name", 'lang_form').")", 'value' => $obj_form->email_confirm_from_email_name, 'maxlength' => 100)) //, 'placeholder' => get_bloginfo('name')
										."</div>";

										if($int_email_fields > 1)
										{
											echo show_select(array('data' => $obj_form->get_email_fields_for_select(), 'name' => 'intFormEmailConfirmID', 'value' => $obj_form->email_confirm_id, 'text' => __("Field", 'lang_form')));
										}

										echo show_select(array('data' => $arr_data_pages, 'name' => 'intFormEmailConfirmPage', 'value' => $obj_form->email_confirm_page, 'text' => __("Template", 'lang_form')." <a href='".admin_url("post-new.php?post_type=page".$form_email_page_shortcodes)."'><i class='fa fa-plus-circle fa-lg'></i></a>"));
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
											echo show_password_field(array('name' => 'strFormPaymentPassword', 'text' => __("Password"), 'value' => $obj_form->payment_password, 'maxlength' => 100, 'xtra' => " autocomplete='new-password'"));
										}

										if(in_array('secret_key', $arr_fields))
										{
											echo show_password_field(array('name' => 'strFormPaymentHmac', 'text' => __("Secret Key", 'lang_form')." / ".__("Signature", 'lang_form'), 'value' => $obj_form->payment_hmac, 'xtra' => " autocomplete='new-password'", 'maxlength' => 200));
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
							.show_textfield(array('name' => 'strFormName', 'text' => __("Name", 'lang_form'), 'value' => $obj_form->name, 'maxlength' => 100, 'required' => true, 'xtra' => ($obj_form->form2type_id > 0 ? "" : "autofocus")))
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