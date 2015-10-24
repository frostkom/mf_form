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

	$('.mf_form input[type=range]').each(function()
	{
		update_range_text($(this));
	});

	$('.mf_form input[type=range]').on('change', function()
	{
		update_range_text($(this));
	});

	$('.mf_form :required').each(function()
	{
		$(this).siblings('label').append(' *');
	});

	$('.mf_form .form_zipcode input').each(function()
	{
		$(this).after("<span></span>");
	});

	$('.mf_form .form_zipcode input').on('focusout', function()
	{
		var dom_obj = $(this),
			search = dom_obj.val();

		$.ajax(
		{
			url: script_forms.plugins_url + '/mf_form/include/ajax.php?type=zipcode/search/' + search,
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

	$('input.mf_datepicker, div.mf_datepicker input').datepicker(
	{
		dateFormat : 'yy-mm-dd',
		constrainInput: true,
		showOtherMonths: true,
		selectOtherMonths: true,
		showWeek: true,
		//changeYear: true,
		//yearRange: '-2:2',
		//changeMonth: true,
		firstDay: 1
	});
});