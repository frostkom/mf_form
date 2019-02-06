document.createElement("mf-form-row");

jQuery(function($)
{
	function show_query_settings(this_val)
	{
		$(".toggler, .show_range, .show_checkbox, .show_validate_as, .show_placeholder, .show_textarea, .show_fetch_from, .show_select, .show_custom_text_tag, .show_custom_tag, .show_custom_class, .show_actions").addClass('hide');

		if(this_val > 0)
		{
			if(this_val == 1) /* checkbox */
			{
				$(".show_checkbox").removeClass('hide');
			}

			else if(this_val == 2) /* range */
			{
				$(".show_range").removeClass('hide');
			}

			else if(this_val == 3) /* input_field */
			{
				$(".show_validate_as").removeClass('hide');
			}

			else if(this_val == 5) /* text */
			{
				$(".show_custom_text_tag").removeClass('hide');
			}

			else if(this_val == 10) /* select */
			{
				$(".show_actions").removeClass('hide');
			}

			if(this_val == 3 || this_val == 4 || this_val == 7) /* input_field, textarea, datepicker */
			{
				$(".show_placeholder").removeClass('hide');
			}

			if(this_val == 2 || this_val == 3 || this_val == 4 || this_val == 7 || this_val == 10 || this_val == 11 || this_val == 12 || this_val == 16 || this_val == 17) /* range, input_field, textarea, datepicker, select, select_multiple, hidden_field, checkbox_multiple, radio_multiple */
			{
				$(".show_fetch_from").removeClass('hide');
			}

			if(this_val == 10 || this_val == 11 || this_val == 16 || this_val == 17) /* select, select_multiple, checkbox_multiple, radio_multiple */
			{
				$(".show_select").removeClass('hide');
			}

			if(this_val == 13 || this_val == 14) /* custom_tag, custom_tag_end */
			{
				$(".show_custom_tag").removeClass('hide');
			}

			if(this_val == 3 || this_val == 4 || this_val == 5 || this_val == 13 || this_val == 15) /* input_field, textarea, text, custom_tag, file */
			{
				$(".show_custom_class").removeClass('hide');
			}

			if(this_val != 6 && this_val != 9) /* space, referer_url */
			{
				if($(".toggle_container").children("div:not(.hide)").length == 0)
				{
					$(".toggler").addClass('hide');
				}

				else
				{
					$(".toggler").removeClass('hide');
				}
			}

			if(this_val != 6 && this_val != 13 && this_val != 14) /* space, custom_tag, custom_tag_end */
			{
				$(".show_textarea").removeClass('hide');
			}
		}
	}

	function add_option()
	{
		if($(".select_rows .option").length > 0)
		{
			var dom_parent = $(".select_rows .option:last-child");

			if(dom_parent.find(".option_value input").val() != '')
			{
				var dom_value = dom_parent.find(".option_key input").attr('value');

				$(".select_rows").append("<div class='option'>" + dom_parent.html() + "</div>");

				var dom_parent_new = $(".select_rows .option:last-child");

				dom_parent_new.find("input").val('').attr({'value': ''}).removeAttr('readonly');

				if(parseInt(dom_value) == dom_value)
				{
					dom_parent_new.find(".option_key input").attr({'value': parseInt(dom_value) + 1});
				}
			}
		}
	}

	/*function update_select()
	{
		var select_value = "";

		$(".select_rows .option").each(function()
		{
			var dom_obj = $(this),
				temp_value = dom_obj.find("input[name=arrFormTypeSelect_value]").val() + "";

			if(temp_value != "")
			{
				var temp_id = dom_obj.find("input[name=arrFormTypeSelect_id]").val() + "",
					temp_limit = dom_obj.find("input[name=arrFormTypeSelect_limit]").val() + "";

				select_value += (select_value != '' ? "," : "") + temp_id + "|" + temp_value + "|" + temp_limit;
			}
		});

		$(".show_select input[name=strFormTypeSelect]").val(select_value);
	}*/

	$(document).on('click', ".ajax_link", function()
	{
		var self = $(this),
			type = self.attr('href').substring(1);

		if($(this).hasClass("confirm_link") && !confirm(script_forms_wp.confirm_question))
		{
			return false;
		}

		$.ajax(
		{
			url: script_forms_wp.plugins_url + '/mf_form/include/api/?type=' + type,
			type: 'get',
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					if(data.dom_id)
					{
						var selector = $("#" + data.dom_id);

						if(selector.length > 0)
						{
							selector.remove();
						}

						else
						{
							self.parents("tr").remove();
						}
					}
				}

				else
				{
					alert(data.error);
				}
			}
		});

		return false;
	});

	$(document).on('click', ".ajax_checkbox", function()
	{
		var dom_obj = $(this),
			type = dom_obj.attr('rel'),
			is_checked = dom_obj.is(":checked");

		$.ajax(
		{
			url: script_forms_wp.plugins_url + '/mf_form/include/api/?type=' + type + '/' + is_checked,
			type: 'get',
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					if(dom_obj.hasClass('autofocus'))
					{
						$("input[type=checkbox].autofocus").prop("checked", false);

						if(is_checked)
						{
							dom_obj.prop("checked", true);
						}
					}
				}

				else
				{
					alert(data.error);
				}
			}
		});
	});

	show_query_settings($("input[name=intFormTypeID]:checked").val());

	$(document).on('change', "input[name=intFormTypeID]", function()
	{
		show_query_settings($(this).val());
	});

	add_option();

	$(document).on('blur', ".select_rows input", function()
	{
		/*update_select();*/

		add_option();
	});

	$(document).on('click', ".show_checkbox li", function()
	{
		$("#strFormTypeText").val($(this).text());

		return false;
	});

	$(document).on('click', "mf-form-row .row_icons", function(e)
	{
		$(e.currentTarget).parents("mf-form-row").toggleClass('active').siblings("mf-form-row").removeClass('active');
	});

	var dom_sortable = $(".mf_form.mf_sortable");

	if(dom_sortable.length > 0)
	{
		dom_sortable.sortable(
		{
			opacity: .7,
			update: function()
			{
				var post_data = $(this).sortable('toArray');

				$.ajax(
				{
					url: script_forms_wp.plugins_url + '/mf_form/include/api/?type=sortOrder',
					type: 'post',
					data: 'strOrder=' + post_data,
					dataType: 'json',
					success: function(data)
					{
						if(data.success){}

						else if(data.error)
						{
							alert(data.error);
						}
					}
				});
			}
		});
	}

	var dom_email_confirm = $("#intFormEmailConfirm"),
		dom_email_confirm_parent = $("#intFormEmailConfirmPage").parent(".form_select"),
		dom_email_notify = $("#intFormEmailNotify"),
		dom_email_notify_parent = $("#intFormEmailNotifyPage").parent(".form_select"),
		dom_form_email_parent = $("#strFormEmail").parent(".form_textfield"),
		dom_form_email_conditions_parent = $("#strFormEmailConditions").parent(".form_textarea");

	function toggle_email_settings()
	{
		var display_dom_form_email = false;

		if(dom_email_confirm.is(":checked"))
		{
			dom_email_confirm_parent.removeClass('hide');

			display_dom_form_email = true;
		}

		else
		{
			dom_email_confirm_parent.addClass('hide');
		}

		if(dom_email_notify.is(":checked"))
		{
			dom_email_notify_parent.removeClass('hide');

			display_dom_form_email = true;
		}

		else
		{
			dom_email_notify_parent.addClass('hide');
		}

		if(display_dom_form_email == true)
		{
			dom_form_email_parent.removeClass('hide');
			dom_form_email_conditions_parent.removeClass('hide');
		}

		else
		{
			dom_form_email_parent.addClass('hide');
			dom_form_email_conditions_parent.addClass('hide');
		}
	}

	toggle_email_settings();

	$(document).on('click', "#intFormEmailConfirm, #intFormEmailNotify", function()
	{
		toggle_email_settings();
	});

	var dom_form_email_conditions = $("#strFormEmailConditions");

	$(document).on('click', ".add2condition a", function()
	{
		var dom_form_email_conditions_value = dom_form_email_conditions.val(),
			dom_condition_rel = $(this).parent("p").attr('rel');

		if(dom_condition_rel != '')
		{
			if(dom_form_email_conditions_value != '')
			{
				dom_form_email_conditions_value += '\n';
			}

			dom_form_email_conditions_value += dom_condition_rel + '|[value]|[e-mail]';

			dom_form_email_conditions.val(dom_form_email_conditions_value);
		}

		return false;
	});
});