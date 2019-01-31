jQuery(function($)
{
	function update_range_text(selector)
	{
		if(selector.siblings("label").children("span").length == 0)
		{
			selector.siblings("label").append(" <span></span>");
		}

		selector.siblings("label").children("span").text(selector.val());
	}

	function do_form_type_action(dom_obj)
	{
		var equals = dom_obj.attr('data-equals'),
			show_obj = $("#" + dom_obj.attr('data-show')),
			show_obj_parent = false;

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
			if(dom_obj.val() == equals){	show_obj_parent.removeClass('hide');}
			else{							show_obj_parent.addClass('hide');}
		}
	}

	function check_remember_fields()
	{
		$.getScript(script_form.plugins_url + "/mf_base/include/jquery.Storage.js").done(function()
		{
			if($.Storage)
			{
				var remember_count = 0;

				function show_or_hide_clear_button()
				{
					if(remember_count > 0)
					{
						$(".form_button .button-secondary").removeClass("hide");
					}

					else
					{
						$(".form_button .button-secondary").addClass("hide");
					}
				}

				var remember_fields = $(".mf_form .remember");

				remember_fields.each(function()
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

				remember_fields.on('blur', "input, select, textarea", function()
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

				$(".form_button button[name=btnFormClear]").on('click', function()
				{
					remember_fields.each(function()
					{
						var selector = $(this).find("input, select, textarea"),
							dom_name = selector.attr('name'),
							dom_value = $.Storage.get(dom_name);

						if(typeof dom_value !== 'undefined')
						{
							selector.val('');

							$.Storage.remove(dom_name);
						}
					});

					remember_count = 0;

					show_or_hide_clear_button();
				});
			}
		});
	}

	function check_zip_code(selector)
	{
		var search = selector.val();

		if(search.length >= 5)
		{
			$.ajax(
			{
				url: script_form.plugin_url + 'api/?type=zipcode/search/' + search,
				type: 'get',
				dataType: 'json',
				success: function(data)
				{
					if(data.success)
					{
						selector.siblings("span").text(data.response);
					}
				}
			});
		}

		else
		{
			selector.siblings("span").empty();
		}
	}

	$(".mf_form input[type=range]").each(function()
	{
		update_range_text($(this));
	});

	$(".form_inline .error, .form_inline h2").each(function()
	{
		$(this).parents(".form_inline").show();
	});

	$(".mf_form .form_zipcode input").each(function()
	{
		$(this).after("<span></span>");
	});

	$(".form_action select").each(function()
	{
		do_form_type_action($(this));
	});

	if($(".mf_form .remember").length > 0)
	{
		check_remember_fields();
	}

	$(".mf_form .form_zipcode input").each(function()
	{
		check_zip_code($(this));
	});

	$(document).on('click', ".form_link", function(event)
	{
		var dom_obj_link = $(this),
			dom_obj_inline = $("#inline_form_" + dom_obj_link.attr('rel')),
			dom_overlay = $("#overlay_form > div");

		if($("#wrapper").length > 0)
		{
			if(dom_overlay.length == 0)
			{
				$("#wrapper").append("<div id='overlay_form'><div></div></div>");

				dom_overlay = $("#overlay_form > div");
			}

			if(dom_overlay.children().length > 0)
			{
				dom_overlay.html('').parent("#overlay_form").fadeOut();
			}

			else
			{
				dom_overlay.html("<i class='fa fa-times fa-2x'></i>" + dom_obj_inline.html()).parent("#overlay_form").fadeIn();
			}
		}

		else
		{
			dom_obj_inline.toggleClass('hide');
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

	$(document).on('click', "#overlay_form .fa-times", function(e)
	{
		hide_form_overlay();
	});

	/*if(script_form.reload == 'no')
	{
		$(document).on('submit', ".mf_form_submit", function()
		{
			var self = $(this),
				form_data = self.serialize();

			form_data += "&action=submit_form";

			$.ajax(
			{
				type: "post",
				dataType: "json",
				url: script_form.ajax_url,
				data: form_data,
				success: function(data)
				{
					if(data.success)
					{
						if(data.output)
						{
							self.html(data.output);
						}

						if(data.redirect)
						{
							if(typeof process_url == 'function')
							{
								process_url(data.redirect);
							}

							else
							{
								location.href = data.redirect;
							}
						}
					}

					else if(data.error)
					{
						$("h1").after("<div class='error'><p>" + data.error + "</p></div>");
					}
				}
			});

			return false;
		});
	}*/

	$(document).on('change', ".mf_form input[type=range]", function()
	{
		update_range_text($(this));
	});

	$(document).on('keyup, focusout', ".mf_form .form_zipcode input", function()
	{
		check_zip_code($(this));
	});

	$(document).on('change', ".form_action select", function()
	{
		do_form_type_action($(this));
	});

	$(document).on('click', ".mf_form_submit button.button-primary", function()
	{
		var dom_obj = $(this);

		if(dom_obj.hasClass('disabled'))
		{
			return false;
		}

		else
		{
			var dom_obj_label = dom_obj.html(),
				dom_empty_required = dom_obj.parents("form").find("[required]:visible").filter(function()
				{
					return !this.value;
				});

			if(dom_empty_required.length > 0){}

			else
			{
				dom_obj.addClass('disabled').html("<i class='fa fa-spinner fa-spin'></i> " + script_form.please_wait + "&hellip;");

				setTimeout(function()
				{
					dom_obj.removeClass('disabled').html(dom_obj_label);
				}, 5000);
			}
		}
	});
});