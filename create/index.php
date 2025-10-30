<?php

$obj_base = new mf_base();
$obj_form = new mf_form(array('type' => 'create'));

if($obj_form->check_allow_edit())
{
	$obj_form->fetch_request();

	//do_action('fetch_form_request');

	echo $obj_form->save_data();

	$obj_form->init();

	if(!($obj_form->post_id > 0))
	{
		$obj_form->post_id = $obj_form->get_post_id($obj_form->id);
	}

	$obj_form->post_status = get_post_status($obj_form->post_id);

	echo "<div class='wrap'>
		<h2>".($obj_form->id > 0 ? __("Update", 'lang_form')." <span>".$obj_form->form_name."</span>" : __("Add New", 'lang_form'))."</h2>"
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

				$arr_data_pages = [];
				get_post_children(array('add_choose_here' => true), $arr_data_pages);

				$has_single_action = ($obj_form->type_action_equals != '' && $obj_form->type_action_show > 0);
				$has_multiple_action = false;

				foreach($obj_form->arr_type_select_action as $key => $value)
				{
					if($value > 0)
					{
						$has_multiple_action = true;
						break;
					}
				}

				echo "<div id='post-body'>
					<div class='postbox".($obj_form->form2type_id > 0 ? " active" : "")."'>
						<h3 class='hndle'>"
							."<span>".__("Content", 'lang_form')."</span> "
							."<a href='".admin_url("post.php?post=".$obj_form->post_id."&action=edit")."'>".__("Edit settings", 'lang_form')."</a> ";

							$block_code = '<!-- wp:mf/form {"form_id":"'.$obj_form->id.'"} /-->';
							$arr_ids = apply_filters('get_page_from_block_code', [], $block_code);

							if(count($arr_ids) > 0)
							{
								foreach($arr_ids as $post_id)
								{
									echo "<a href='".get_permalink($post_id)."'>".__("View", 'lang_form')."</a> ";
								}
							}

						echo "</h3>
						<div class='inside'>
							<form".apply_filters('get_form_attr', " action='".admin_url("admin.php?page=mf_form/create/index.php&intFormID=".$obj_form->id)."'").">
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
													$obj_form->arr_type_select_id = array('', '', '');
													$obj_form->arr_type_select_key = array('0', '1', '2');
													$obj_form->arr_type_select_value = array("-- ".__("Choose Here", 'lang_form')." --", __("No", 'lang_form'), __("Yes", 'lang_form'));
													$obj_form->arr_type_select_limit = array('', '', '');
													$obj_form->arr_type_select_action = array('', '', '');

													$count_temp = count($obj_form->arr_type_select_value);
												}

												/*echo "ID: ".var_export($obj_form->arr_type_select_id, true)."<br>"
												."Key: ".var_export($obj_form->arr_type_select_key, true)."<br>"
												."Value: ".var_export($obj_form->arr_type_select_value, true)."<br>"
												."Limit: ".var_export($obj_form->arr_type_select_limit, true)."<br>"
												."Action: ".var_export($obj_form->arr_type_select_action, true)."<br>";*/

												$arr_data_show = [];

												if($has_single_action == false || $has_multiple_action == true)
												{
													$result = $obj_form->get_form_type_info(array('query_type_code' => array('checkbox', 'range', 'input_field', 'textarea', 'text', 'datepicker', 'radio_button', 'select', 'select_multiple', 'custom_tag', 'checkbox_multiple', 'radio_multiple'), 'query_exclude_id' => $obj_form->form2type_id));

													if(count($result) > 0)
													{
														$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));
													}
												}

												for($i = 0; $i < $count_temp; $i++)
												{
													if(isset($obj_form->arr_type_select_id[$i]) && isset($obj_form->arr_type_select_key[$i]))
													{
														$is_select_value_used = $obj_form->is_select_value_used(array('form2type_id' => $obj_form->form2type_id, 'option_id' => $obj_form->arr_type_select_id[$i]));

														echo "<div class='option'>"
															//.input_hidden(array('name' => 'arrFormTypeSelect_key[]', 'value' => $obj_form->arr_type_select_key[$i]))
															.show_textfield(array('type' => 'number', 'name' => 'arrFormTypeSelect_key[]', 'value' => $obj_form->arr_type_select_key[$i]))
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

											echo "</div>
										</div>";

										//Advanced
										#################
										echo get_toggler_container(array('type' => 'start', 'text' => __("Advanced", 'lang_form')))
											.show_select(array('data' => $obj_form->get_tags_for_select(), 'name' => 'strFormTypeTag', 'value' => $obj_form->type_tag, 'text' => __("Custom HTML Tag", 'lang_form'), 'class' => "show_custom_text_tag hide"))
											.show_textfield(array('name' => 'strFormTypeClass', 'text' => __("Custom CSS class", 'lang_form'), 'value' => $obj_form->type_class, 'placeholder' => "strong italic aligncenter alignleft alignright flex_flow", 'maxlength' => 50, 'xtra_class' => "show_custom_class hide"))
											.show_textfield(array('type' => 'number', 'name' => 'intFormTypeLength', 'text' => __("Max Length", 'lang_form'), 'value' => $obj_form->type_length, 'xtra_class' => "show_custom_length hide", 'xtra' => " min='0'"));

											if($obj_form->form2type_id > 0)
											{
												$handle_temp = $obj_form->get_post_info(array('select' => 'post_name'))."_".$obj_form->form2type_id;

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
												$result = $obj_form->get_form_type_info(array('query_type_code' => array('select'), 'query_exclude_id' => $obj_form->form2type_id));

												if(count($result) > 0)
												{
													$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

													echo "<div class='show_select'>"
														.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeConnectTo', 'text' => __("Connect to", 'lang_form'), 'value' => $obj_form->type_connect_to))
													."</div>";
												}
												##############################

												// Actions
												##############################
												if($has_single_action == true || $has_multiple_action == false)
												{
													$arr_data_equals = [];

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
																if(isset($obj_form->arr_type_select_id[$i]) && isset($obj_form->arr_type_select_value[$i]))
																{
																	$arr_data_equals[$obj_form->arr_type_select_id[$i]] = $obj_form->arr_type_select_value[$i];
																}
															}
														break;
													}

													if(count($arr_data_equals) > 1)
													{
														$result = $obj_form->get_form_type_info(array('query_type_code' => array('checkbox', 'range', 'input_field', 'textarea', 'text', 'datepicker', 'radio_button', 'select', 'select_multiple', 'custom_tag', 'checkbox_multiple', 'radio_multiple'), 'query_exclude_id' => $obj_form->form2type_id));

														if(count($result) > 0)
														{
															$arr_data_show = $obj_form->get_form_type_for_select(array('result' => $result, 'add_choose_here' => true));

															echo "<div class='show_actions'>"
																.show_select(array('data' => $arr_data_equals, 'name' => 'strFormTypeActionEquals', 'text' => __("If this equals...", 'lang_form'), 'value' => $obj_form->type_action_equals))
																.show_select(array('data' => $arr_data_show, 'name' => 'intFormTypeActionShow', 'text' => __("...display this...", 'lang_form'), 'value' => $obj_form->type_action_show))
															."</div>";
														}
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
						</div>
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
					</div>
				</div>";
			}

			else
			{
				do_log("There was no formID");
			}

		echo "</div>
	</div>";
}

else
{
	wp_die(__("You do not have permission to edit this form", 'lang_form'));
}