jQuery(function($)
{
	var dom_obj_widget = $(".widget.form .mf_form");

	if($.isArray(script_form_fetch_info.arr_form_input_type))
	{
		var arr_fields = [];

		$.each(script_form_fetch_info.arr_form_input_type, function(key, value)
		{
			var dom_obj_type = dom_obj_widget.find(".mf_form_field[data-fetch_info='" + value + "']");

			dom_obj_type.each(function()
			{
				var dom_obj = $(this);

				if(dom_obj.val() == '')
				{
					arr_fields.push([dom_obj.attr('id'), value]);
				}
			});
		});

		if(arr_fields.length > 0)
		{
			$.ajax(
			{
				url: script_form_fetch_info.ajax_url,
				type: 'post',
				dataType: 'json',
				data:
				{
					action: 'api_form_fetch_info',
					arr_fields: arr_fields
				},
				success: function(data)
				{
					if(data.success)
					{
						$.each(data.response_fields, function(key, value)
						{
							dom_obj_widget.find("#" + value.id).val(value.value);
						});
					}
				}
			});
		}
	}
});