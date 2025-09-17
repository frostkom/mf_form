jQuery(function($)
{
	var dom_obj_remember = $(".mf_form .remember");

	function check_remember_fields()
	{
		$.getScript(script_form_remember.plugins_url + "/mf_base/include/jquery.Storage.js").done(function()
		{
			if($.Storage)
			{
				var remember_count = 0;

				function show_or_hide_clear_button()
				{
					if(remember_count > 0)
					{
						$(".form_button .button-secondary, .wp-block-button .button-secondary").removeClass("hide");
					}

					else
					{
						$(".form_button .button-secondary, .wp-block-button .button-secondary").addClass("hide");
					}
				}

				dom_obj_remember.each(function()
				{
					var selector = $(this).find("input, select, textarea"),
						dom_name = selector.attr('name'),
						dom_value = $.Storage.get(dom_name);

					if(typeof dom_value !== 'undefined' && selector.val() == '')
					{
						selector.val(dom_value);

						remember_count++;
					}
				});

				show_or_hide_clear_button();

				dom_obj_remember.on('blur', "input, select, textarea", function()
				{
					var selector = $(this),
						dom_name = selector.attr('name'),
						dom_value = selector.val();

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
			}
		});
	}

	if(dom_obj_remember.length > 0)
	{
		check_remember_fields();
	}
});