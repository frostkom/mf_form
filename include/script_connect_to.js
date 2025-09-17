jQuery(function($)
{
	function do_form_type_connect_to(dom_obj)
	{
		var dom_obj_id = dom_obj.attr('id'),
			dom_obj_val = dom_obj.val(),
			dom_obj_target = $(".form_connect_to select[data-connect_to='" + dom_obj_id + "']");

		dom_obj_target.find("option").prop('disabled', false);

		if(dom_obj_val != '')
		{
			dom_obj_target.find("option[value='" + dom_obj_val + "']").prop('disabled', true);
		}

		dom_obj.find("option[disabled]").each(function()
		{
			var dom_obj_disabled_val = $(this).attr('value');

			dom_obj_target.find("option[value='" + dom_obj_disabled_val + "']").prop('disabled', true);
		});
	}

	$(".form_connect_to select").each(function()
	{
		var connect_to = $(this).attr('data-connect_to');

		$(".form_select select#" + connect_to).each(function()
		{
			do_form_type_connect_to($(this));
		});

		$(document).on('change, click', ".form_select select#" + connect_to, function()
		{
			do_form_type_connect_to($(this));
		});
	});
});