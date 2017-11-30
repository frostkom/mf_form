document.createElement("mf-form-row");

jQuery(function($)
{
	function show_query_settings(this_val)
	{
		$('.toggler, .show_range, .show_validate_as, .show_placeholder, .show_textarea, .show_fetch_from, .show_select, .show_custom_text_tag, .show_custom_tag, .show_custom_class, .show_actions').addClass('hide');

		if(this_val > 0)
		{
			if(this_val == 2) //range
			{
				$('.show_range').removeClass('hide');
			}

			if(this_val == 3) //input_field
			{
				$('.show_validate_as').removeClass('hide');
			}

			if(this_val == 3 || this_val == 4 || this_val == 7) //input_field, textarea, datepicker
			{
				$('.show_placeholder').removeClass('hide');
			}

			if(this_val != 6 && this_val != 13 && this_val != 14) //space, custom_tag, custom_tag_end
			{
				$('.show_textarea').removeClass('hide');
			}

			if(this_val == 2 || this_val == 3 || this_val == 4 || this_val == 7 || this_val == 10 || this_val == 12) //range, input_field, textarea, datepicker, select, hidden_field,
			{
				$('.show_fetch_from').removeClass('hide');
				$('.toggler').removeClass('hide').next().addClass('hide');
			}

			if(this_val == 10 || this_val == 11 || this_val == 16) //select, select_multiple, checkbox_multiple
			{
				$('.show_select').removeClass('hide');
			}

			if(this_val == 5) //text
			{
				$('.show_custom_text_tag').removeClass('hide');
				$('.toggler').removeClass('hide').next().addClass('hide');
			}

			if(this_val == 13 || this_val == 14) //custom_tag, custom_tag_end
			{
				$('.show_custom_tag').removeClass('hide');
			}

			if(this_val == 3 || this_val == 4 || this_val == 5 || this_val == 13 || this_val == 15) //input_field, textarea, text, custom_tag, file
			{
				$('.show_custom_class').removeClass('hide');
				$('.toggler').removeClass('hide').next().addClass('hide');
			}

			if(this_val == 10) //select
			{
				$('.show_actions').removeClass('hide');
				$('.toggler').removeClass('hide').next().addClass('hide');
			}
		}

		if($(".show_validate_as").parent("div").children("div:not(.hide)").length > 0)
		{
			$(".show_validate_as").parent("div").removeClass('hide');
		}

		else
		{
			$(".show_validate_as").parent("div").addClass('hide');
		}
	}

	function add_option()
	{
		var dom_content = $('.select_rows .option:last-child').html();

		$('.select_rows').append("<div class='option'>" + dom_content + "</div>");

		var dom_obj = $('.select_rows .option:last-child input[name=strFormTypeSelect_id]'),
			dom_value = dom_obj.attr('value');

		if(parseInt(dom_value) == dom_value)
		{
			dom_obj.attr(
			{
				'value': parseInt(dom_value) + 1
			});
		}

		$('.select_rows .option:last-child input[name=strFormTypeSelect_value]').attr(
		{
			'value': ''
		});
	}

	function update_select()
	{
		var select_value = "";

		$('.select_rows .option').each(function()
		{
			var dom_obj = $(this),
				temp_id = dom_obj.find("input[name=strFormTypeSelect_id]").val() + "",
				temp_value = dom_obj.find("input[name=strFormTypeSelect_value]").val() + "",
				temp_limit = dom_obj.find("input[name=intFormTypeSelect_limit]").val() + "";

			console.log(dom_obj.find("input[name=intFormTypeSelect_limit]"));

			if(temp_value != "")
			{
				select_value += (select_value != '' ? "," : "") + temp_id + "|" + temp_value + "|" + temp_limit;
			}
		});

		$('.show_select input[name=strFormTypeSelect]').val(select_value);
	}

	$(document).on('click', '.ajax_link', function()
	{
		var self = $(this),
			type = $(this).attr('href').substring(1);

		if($(this).hasClass("confirm_link") && !confirm(script_forms_wp.confirm_question))
		{
			return false;
		}

		$.ajax(
		{
			url: script_forms_wp.plugins_url + '/mf_form/include/ajax.php?type=' + type,
			type: 'get',
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					if(data.dom_id)
					{
						if($('#' + data.dom_id).length > 0)
						{
							$('#' + data.dom_id).remove();
						}

						else
						{
							self.parents('tr').remove();
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

	$(document).on('click', '.ajax_checkbox', function()
	{
		var dom_obj = $(this),
			type = dom_obj.attr('rel'),
			is_checked = dom_obj.is(":checked");

		$.ajax(
		{
			url: script_forms_wp.plugins_url + '/mf_form/include/ajax.php?type=' + type + '/' + is_checked,
			type: 'get',
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					if(dom_obj.hasClass('autofocus'))
					{
						$('input[type=checkbox].autofocus').prop("checked", false);

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

	show_query_settings($('#intFormTypeID').val());

	$(document).on('change', '#intFormTypeID', function()
	{
		show_query_settings($(this).val());
	});

	$(document).on('blur', '.select_rows', 'input', function()
	{
		update_select();

		if($(this).parent('div').parent('div').is(':last-child'))
		{
			add_option();
		}
	});

	$(document).on('click', "mf-form-row", function(e)
	{
		$(e.currentTarget).toggleClass('active').siblings("mf-form-row").removeClass('active');
	});

	if($('.mf_form.mf_sortable').length > 0)
	{
		$('.mf_form.mf_sortable').sortable(
		{
			opacity: .7,
			update: function()
			{
				var post_data = $(this).sortable('toArray');

				$.ajax(
				{
					url: script_forms_wp.plugins_url + '/mf_form/include/ajax.php?type=sortOrder',
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

	$(document).on('click', '#intFormEmailConfirm', function()
	{
		if(this.checked)
		{
			$('.query_email_confirm_page').removeClass('hide');
		}

		else
		{
			$('.query_email_confirm_page').addClass('hide');
		}
	});

	$(document).on('click', '#intFormEmailNotify', function()
	{
		if(this.checked)
		{
			$('.query_email_notify_page').removeClass('hide');
		}

		else
		{
			$('.query_email_notify_page').addClass('hide');
		}
	});
});