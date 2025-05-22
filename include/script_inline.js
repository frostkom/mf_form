jQuery(function($)
{
	$(".form_inline .error, .form_inline h2").each(function()
	{
		$(this).parents(".form_inline").show();
	});

	$(document).on('click', ".form_link", function()
	{
		var dom_obj = $("#inline_form_" + $(this).attr('rel')),
			dom_overlay = $("#overlay_form > div");

		if($("#wrapper").length > 0)
		{
			if(dom_overlay.length == 0)
			{
				$("#wrapper").append("<div id='overlay_form' class='overlay_container modal'><div></div></div>");

				dom_overlay = $("#overlay_form > div");
			}

			if(dom_overlay.children().length > 0)
			{
				dom_overlay.html('').parent("#overlay_form").fadeOut();
			}

			else
			{
				dom_overlay.html("<i class='fa fa-times fa-2x'></i>" + dom_obj.html()).parent("#overlay_form").fadeIn();
			}
		}

		else
		{
			dom_obj.toggleClass('hide');
		}

		return false;
	});

	function hide_form_overlay()
	{
		$("#overlay_form").fadeOut().children("div").html('');
	}

	$(document).on('click', "#overlay_form", function(e)
	{
		if(e.target == e.currentTarget)
		{
			hide_form_overlay();
		}
	});

	$(document).on('click', "#overlay_form .fa-times", function()
	{
		hide_form_overlay();
	});
});