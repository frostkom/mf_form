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
				if(data.success)
				{
					dom_obj.html(data.html).siblings("button").removeAttr('disabled');
				}
			}
		});
	});
});