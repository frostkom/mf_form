jQuery(function($)
{
	/* Range */
	/* ################### */
	function update_range_text(selector)
	{
		if(selector.siblings("label").children("span").length == 0)
		{
			selector.siblings("label").append(" <span></span>");
		}

		selector.siblings("label").children("span").text(selector.val());
	}

	$(".mf_form input[type=range]").each(function()
	{
		update_range_text($(this));
	});

	$(document).on('change', ".mf_form input[type=range]", function()
	{
		update_range_text($(this));
	});
	/* ################### */

	/*  */
	/* ################### */
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
	/* ################### */

	/* Single Conditions */
	/* ################### */
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

	$(".form_display select, .form_display input").each(function()
	{
		do_form_type_display($(this));
	});

	$(document).on('blur change click', ".form_display select, .form_display input", function()
	{
		do_form_type_display($(this));
	});
	/* ################### */

	/* Multiple Conditions */
	/* ################### */
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

	$(".form_action select").each(function()
	{
		do_form_type_action($(this));
	});

	$(document).on('blur change click', ".form_action select", function()
	{
		do_form_type_action($(this));
	});
	/* ################### */

	/* Remember Field Values */
	/* ################### */
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

	if($(".mf_form .remember").length > 0)
	{
		check_remember_fields();
	}
	/* ################### */

	/* Get Municipality from Zip Code */
	/* ################### */
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

	$(".mf_form .form_zipcode input").each(function()
	{
		check_zip_code($(this));
	});

	$(document).on('blur keyup focusout', ".mf_form .form_zipcode input", function()
	{
		check_zip_code($(this));
	});
	/* ################### */

	var dom_radio_multiple = $(".mf_form .form_radio_multiple + .form_radio_multiple");

	if(dom_radio_multiple.length > 0)
	{
		var i = 0,
			selected = 0;

		dom_radio_multiple.each(function()
		{
			if($(this).find(".form_radio:selected").length > 0)
			{
				selected++;
			}

			else
			{
				$(this).addClass('inactive');
			}

			i++;
		});

		if(i > 0 && selected > 0)
		{
			$(".mf_form .form_radio_multiple:first-of-type").addClass('inactive');
		}

		$(document).on('click', ".mf_form .form_radio_multiple input", function(e)
		{
			var dom_obj_next = $(e.currentTarget).parents(".form_radio_multiple").next(".form_radio_multiple");

			if(dom_obj_next.length > 0)
			{
				dom_obj_next.removeClass('inactive').siblings(".form_radio_multiple").addClass('inactive');

				$("html, body").animate({scrollTop: dom_obj_next.offset().top}, 800);
			}
		});
	}

	$(".form_inline .error, .form_inline h2").each(function()
	{
		$(this).parents(".form_inline").show();
	});

	$(".mf_form .form_zipcode input").each(function()
	{
		$(this).after("<span></span>");
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

	$(document).on('click', ".mf_form_submit button.button-primary", function()
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

			if(dom_empty_required.length > 0){}

			else
			{
				dom_obj.addClass('loading');

				setTimeout(function()
				{
					dom_obj.removeClass('loading');
				}, 5000);
			}
		}
	});
});