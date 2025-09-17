jQuery(function($)
{
	var dom_obj_range = $(".mf_form input[type=range]");

	function update_range_text(selector)
	{
		if(selector.siblings("label").children("span").length == 0)
		{
			selector.siblings("label").append(" <span></span>");
		}

		selector.siblings("label").children("span").text(selector.val());
	}

	dom_obj_range.each(function()
	{
		update_range_text($(this));
	});

	dom_obj_range.on('change', function()
	{
		update_range_text($(this));
	});
});