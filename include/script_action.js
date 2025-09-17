jQuery(function($)
{
	var dom_obj_action = $(".form_action select");

	function do_form_type_action(dom_obj)
	{
		var data_equals = dom_obj.val();

		dom_obj.children("option").each(function()
		{
			var dom_option = $(this),
				data_display = dom_option.attr('data-action');

			if(data_display != '')
			{
				var show_obj = $("#" + data_display),
					show_obj_parent = false;

				if(show_obj.length > 0)
				{
					if(show_obj.is("input[type=checkbox]")){	show_obj_parent = show_obj.parents(".form_checkbox");}
					else if(show_obj.is("input")){				show_obj_parent = show_obj.parents(".form_textfield");}
					else if(show_obj.is("input[type=radio]")){	show_obj_parent = show_obj.parents(".form_radio");}
					else if(show_obj.is("select")){				show_obj_parent = show_obj.parents(".form_select");}
					else if(show_obj.is("textarea")){			show_obj_parent = show_obj.parents(".form_textarea");}
					else
					{
						show_obj_parent = show_obj;
					}

					if(show_obj_parent !== false)
					{
						if(dom_obj.is("select"))
						{
							if(dom_option.attr('value') == data_equals)
							{
								show_obj_parent.removeClass('hide');
							}

							else
							{
								show_obj_parent.addClass('hide');
							}
						}

						/* Not tested yet */
						/*else if(dom_obj.is("input[type='checkbox']"))
						{
							if(dom_option.is(":checked"))
							{
								show_obj_parent.removeClass('hide');
							}

							else
							{
								show_obj_parent.addClass('hide');
							}
						}

						else if(dom_obj.is("input[type='radio']"))
						{
							if(dom_option.attr('value') == data_equals && dom_option.is(":checked"))
							{
								show_obj_parent.removeClass('hide');
							}

							else
							{
								show_obj_parent.addClass('hide');
							}
						}*/
					}
				}
			}
		});
	}

	dom_obj_action.each(function()
	{
		do_form_type_action($(this));
	});

	dom_obj_action.on('blur change click', function()
	{
		do_form_type_action($(this));
	});
});