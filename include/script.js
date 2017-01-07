function update_range_text(dom_obj)
{
	dom_obj.siblings('label').children('span').text(dom_obj.val());
}

jQuery(function($)
{
	$('.mf_form_link').on('click', function()
	{
		var dom_obj = $(this).parent().next('.mf_form_inline'),
			is_visible = dom_obj.is(':visible');

		$('.mf_form_inline').hide();

		if(is_visible == false)
		{
			dom_obj.show();
		}

		return false;
	});

	$('.mf_form_inline .error').each(function()
	{
		if($(this).length > 0)
		{
			$(this).parents('.mf_form_inline').show();
		}
	});

	var range_inputs = $('.mf_form input[type=range]');

	range_inputs.each(function()
	{
		update_range_text($(this));
	});

	range_inputs.on('change', function()
	{
		update_range_text($(this));
	});

	var zipcode_inputs = $('.mf_form .form_zipcode input');

	zipcode_inputs.each(function()
	{
		$(this).after("<span></span>");
	});

	zipcode_inputs.on('focusout', function()
	{
		var dom_obj = $(this),
			search = dom_obj.val();

		$.ajax(
		{
			url: script_forms.plugin_url + 'ajax.php?type=zipcode/search/' + search,
			type: 'get',
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					dom_obj.siblings('span').text(data.response);
				}
			}
		});
	});

	/* Confirm required e-mail */
	function has_required_email()
	{
		var this_form = $(this).parents('.mf_form');

		if(this_form.find(':required:invalid').length == 0)
		{
			this_form.children('.this_is_required_email').siblings('div:not(.form_button)').addClass('hide');

			$(this).removeClass('has_required_email').off('click', has_required_email);

			this_form.find('.form_button > div.updated').removeClass('hide');

			/* Animate to top of form */
			$('html, body').animate(
			{
				scrollTop: this_form.offset().top - 200
			}, 750, 'swing');

			return false;
		}
	}

	$('.mf_form .has_required_email').on('click', has_required_email);

	$('.mf_form .show_none_email').on('click', function()
	{
		var this_form = $(this).parents('.mf_form');

		this_form.children('div:not(.form_button)').removeClass('hide');

		this_form.find('.form_button button').addClass('has_required_email').on('click', has_required_email);

		this_form.find('.form_button > div.updated').addClass('hide');

		return false;
	});
	/* ################## */

	/* Form actions */
	var action_selects = $('.form_action select');

	function do_form_type_action(dom_obj)
	{
		var equals = dom_obj.attr('data-equals'),
			show = dom_obj.attr('data-show'),
			show_obj = $('#' + show);

		if(show_obj.is('checkbox')){		show_obj = show_obj.parents('.form_checkbox');}
		else if(show_obj.is('textarea')){	show_obj = show_obj.parents('.form_textarea');}
		else if(show_obj.is('select')){		show_obj = show_obj.parents('.form_select');}

		if(dom_obj.val() == equals){	show_obj.removeClass('hide');}
		else{							show_obj.addClass('hide');}
	}

	action_selects.on('change', function()
	{
		do_form_type_action($(this));
	});

	action_selects.each(function()
	{
		do_form_type_action($(this));
	});
	/* ################## */

	/* Remember input */
	var remember_fields = $('.mf_form .remember');

	if(remember_fields.length > 0)
	{
		$.getScript(script_forms.plugins_url + "/mf_base/include/jquery.Storage.js").done(function()
		{
			if($.Storage)
			{
				var remember_count = 0;

				function show_or_hide_clear_button()
				{
					if(remember_count > 0)
					{
						$('.form_button .button-secondary').removeClass("hide");
					}

					else
					{
						$('.form_button .button-secondary').addClass("hide");
					}
				}

				remember_fields.each(function()
				{
					var dom_obj = $(this).find('input, select, textarea'),
						dom_name = dom_obj.attr('name'),
						dom_value = $.Storage.get(dom_name);

					if(typeof dom_value !== 'undefined')
					{
						dom_obj.val(dom_value);

						remember_count++;
					}
				});

				show_or_hide_clear_button();

				remember_fields.on('blur', 'input, select, textarea', function()
				{
					var dom_obj = $(this),
						dom_name = dom_obj.attr('name'),
						dom_value = dom_obj.val();

					if(dom_value != '')
					{
						$.Storage.set(dom_name, dom_value);

						remember_count++;
					}

					else
					{
						var dom_value = $.Storage.get(dom_name);

						if(typeof dom_value !== 'undefined')
						{
							$.Storage.remove(dom_name);

							remember_count--;
						}
					}

					show_or_hide_clear_button();
				});

				$('.form_button button[name=btnFormClear]').on('click', function()
				{
					remember_fields.each(function()
					{
						var dom_obj = $(this).find('input, select, textarea'),
							dom_name = dom_obj.attr('name'),
							dom_value = $.Storage.get(dom_name);

						if(typeof dom_value !== 'undefined')
						{
							dom_obj.val('');

							$.Storage.remove(dom_name);
						}
					});

					remember_count = 0;

					show_or_hide_clear_button();
				});
			}
		});
	}
	/* ################## */
});