jQuery(function($)
{
	$(document).on('click', ".ajax_link", function()
	{
		var self = $(this),
			type = self.attr('href').substring(1);

		if($(this).hasClass("confirm_link") && !confirm(script_form_wp.confirm_question))
		{
			return false;
		}

		$.ajax(
		{
			url: script_form_wp.plugins_url + '/mf_form/include/api/?type=' + type,
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
			url: script_form_wp.plugins_url + '/mf_form/include/api/?type=' + type + '/' + is_checked,
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

	function show_query_settings(this_val)
	{
		$(".toggler, .show_range, .show_checkbox, .show_validate_as, .show_placeholder, .show_textarea, .show_fetch_from, .show_select, .show_actions, .show_custom_text_tag, .show_custom_tag, .show_custom_class, .show_custom_length").addClass('hide');

		if(this_val > 0)
		{
			switch(this_val)
			{
				case '1': /* checkbox */
					$(".show_checkbox, .show_actions").removeClass('hide');
				break;

				case '2': /* range */
					$(".show_range, .show_fetch_from").removeClass('hide');
				break;

				case '3': /* input_field */
					$(".show_validate_as, .show_custom_class, .show_placeholder, .show_fetch_from, .show_custom_length").removeClass('hide');
				break;

				case '4': /* textarea */
					$(".show_placeholder, .show_custom_class, .show_fetch_from, .show_custom_length").removeClass('hide');
				break;

				case '5': /* text */
					$(".show_custom_class, .show_custom_text_tag").removeClass('hide');
				break;

				case '7': /* datepicker */
					$(".show_placeholder, .show_fetch_from").removeClass('hide');
				break;

				case '10': /* select */
					$(".show_select, .show_actions, .show_custom_class, .show_fetch_from").removeClass('hide');
				break;

				case '11': /* select_multiple */
					$(".show_select, .show_actions, .show_custom_class, .show_fetch_from").removeClass('hide');
				break;

				case '12': /* hidden_field */
					$(".show_fetch_from").removeClass('hide');
				break;

				case '13': /* custom_tag */
					if($("#strFormTypeText2").val() == 'fieldset')
					{
						$(".show_placeholder").removeClass('hide');
					}

					$(".show_custom_tag, .show_custom_class").removeClass('hide');
				break;

				case '14': /* custom_tag_end */
					$(".show_custom_tag").removeClass('hide');
				break;

				case '15': /* file */
					$(".show_custom_class").removeClass('hide');
				break;

				case '16': /* checkbox_multiple */
				case '17': /* radio_multiple */
					$(".show_fetch_from, .show_select, .show_actions").removeClass('hide');
				break;
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

	show_query_settings($("input[name='intFormTypeID']:checked").val());

	$(document).on('change', "input[name=intFormTypeID]", function()
	{
		show_query_settings($(this).val());
	});

	$(document).on('change', "#strFormTypeText2", function()
	{
		show_query_settings('13');
	});

	function make_options_sortable()
	{
		$(".select_rows").sortable(
		{
			opacity: .7,
			update: function(){}
		});
	}

	function add_option()
	{
		if($(".select_rows .option").length > 0)
		{
			var dom_parent = $(".select_rows .option:last-child");

			if(dom_parent.find(".option_value input").val() != '')
			{
				var dom_key = dom_parent.find(".option_key input").attr('value');

				$(".select_rows .option").each(function()
				{
					var dom_option_key = $(this).find(".option_key input").attr('value');

					if(dom_option_key > dom_key)
					{
						dom_key = dom_option_key;
					}
				});

				$(".select_rows").append("<div class='option'>" + dom_parent.html() + "</div>");

				var dom_option_last = $(".select_rows .option:last-child");

				dom_option_last.find("input, select").val('').attr({'value': ''}).removeAttr('readonly');

				if(parseInt(dom_key) == dom_key)
				{
					dom_option_last.find(".option_key input").attr({'value': parseInt(dom_key) + 1});
				}

				make_options_sortable();
			}
		}
	}

	add_option();

	$(document).on('blur', ".select_rows input", function()
	{
		add_option();
	});

	$(document).on('dblclick', ".select_rows .option_value input", function(e)
	{
		$(this).removeAttr('readonly');
	});

	$(document).on('click', ".show_checkbox li", function()
	{
		$("#strFormTypeText").val($(this).text());

		return false;
	});

	$(document).on('click', ".form_row .row_icons", function(e)
	{
		$(e.currentTarget).parents(".form_row").toggleClass('active').siblings(".form_row").removeClass('active');
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
					url: script_form_wp.plugins_url + '/mf_form/include/api/?type=sortOrder',
					type: 'post',
					dataType: 'json',
					data: 'strOrder=' + post_data,
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
});