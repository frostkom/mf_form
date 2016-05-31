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

			if(this_val == 10 || this_val == 11) //select, select_multiple
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

			if(this_val == 3 || this_val == 4 || this_val == 5 || this_val == 15) //input_field, textarea, text, file
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
	}

	function add_option()
	{
		var dom_content = $('.select_rows > div:last-child').html();

		$('.select_rows').append("<div>" + dom_content + "</div>");

		var dom_obj = $('.select_rows > div:last-child input[name=strQueryTypeSelect_id]'),
			dom_value = dom_obj.attr('value');

		if(parseInt(dom_value) == dom_value)
		{
			dom_obj.attr(
			{
				'value': parseInt(dom_value) + 1
			});
		}

		$('.select_rows > div:last-child input[name=strQueryTypeSelect_value]').attr(
		{
			'value': ''
		});
	}

	function update_select()
	{
		var select_value = "",
			i = 1;

		$('.select_rows > div').each(function()
		{
			var temp_id = $(this).find('input[name=strQueryTypeSelect_id]').val() + "",
				temp_value = $(this).find('input[name=strQueryTypeSelect_value]').val() + "";

			if(temp_value != "") //temp_id != "" && 
			{
				select_value += (select_value != '' ? "," : "") + temp_id + "|" + temp_value;
			}
		});

		$('.show_select input[name=strQueryTypeSelect]').val(select_value);
	}

	$('.toggler').on('click', function()
	{
		$(this).next().toggleClass('hide');
	});

	$('.ajax_link').on('click', function()
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
						self.parents('tr').remove();
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

	$('.ajax_checkbox').on('click', function()
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

	show_query_settings($('#intQueryTypeID').val());

	$('#intQueryTypeID').on('change', function()
	{
		show_query_settings($(this).val());
	});

	$('.select_rows').on('blur', 'input', function()
	{
		update_select();

		if($(this).parent('div').parent('div').is(':last-child'))
		{
			add_option();
		}
	});

	$('#add_option').on('click', function()
	{
		add_option();

		//update_select();

		return false;
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

	$('#intQueryEmailConfirm').on('click', function()
	{
		if(this.checked)
		{
			$('.query_email_confirm_page').show();
		}

		else
		{
			$('.query_email_confirm_page').hide();
		}
	});
});