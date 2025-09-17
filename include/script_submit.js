jQuery(function($)
{
	$(".mf_form .api_form_nonce").each(function()
	{
		var dom_obj = $(this),
			form_id = dom_obj.parents(".mf_form").attr('id').replace('form_', '');

		$.ajax(
		{
			url: script_form_submit.ajax_url,
			type: 'post',
			dataType: 'json',
			data:
			{
				action: 'api_form_nonce',
				form_id: form_id
			},
			success: function(data)
			{
				dom_obj.html(data.html);
			}
		});
	});

	/*$(document).on('click', ".mf_form .button-primary", function()
	{
		var dom_obj = $(this);

		if(dom_obj.hasClass('loading'))
		{
			return false;
		}

		else
		{
			var dom_empty_required = dom_obj.parents("form").find("[required]:visible").filter(function()
			{
				return !this.value;
			});

			if(!(dom_empty_required.length > 0))
			{
				dom_obj.addClass('loading');

				setTimeout(function()
				{
					dom_obj.removeClass('loading');
				}, 5000);
			}
		}
	});*/
});