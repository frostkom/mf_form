jQuery(function($)
{
	var dom_obj_display = $(".form_display select, .form_display input");

	function do_form_type_display(dom_obj)
	{
		var data_equals = dom_obj.attr('data-equals'),
			data_display = dom_obj.attr('data-display');

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
						if(dom_obj.val() == data_equals)
						{
							show_obj_parent.removeClass('hide');
						}

						else
						{
							show_obj_parent.addClass('hide');
						}
					}

					else if(dom_obj.is("input[type='checkbox']"))
					{
						if(dom_obj.is(":checked"))
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
						if(dom_obj.val() == data_equals && dom_obj.is(":checked"))
						{
							show_obj_parent.removeClass('hide');
						}

						else
						{
							show_obj_parent.addClass('hide');
						}
					}
				}
			}
		}
	}

	dom_obj_display.each(function()
	{
		do_form_type_display($(this));
	});

	dom_obj_display.on('blur change click', function()
	{
		do_form_type_display($(this));
	});
});