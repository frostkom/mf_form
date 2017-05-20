function on_load_form()
{
	jQuery('.mf_form input[type=range]').each(function()
	{
		update_range_text(jQuery(this));
	});

	jQuery('.form_inline .error, .form_inline h2').each(function()
	{
		jQuery(this).parents('.form_inline').removeClass('hide');
	});

	jQuery('.mf_form .form_zipcode input').each(function()
	{
		jQuery(this).after("<span></span>");
	});

	jQuery('.form_action select').each(function()
	{
		do_form_type_action(jQuery(this));
	});

	if(jQuery('.mf_form .remember').length > 0)
	{
		check_remember_fields();
	}

	jQuery('.mf_form .form_zipcode input').each(function()
	{
		check_zip_code(jQuery(this));
	});
}

function update_range_text(selector)
{
	selector.siblings('label').children('span').text(selector.val());
}

function do_form_type_action(selector)
{
	var equals = selector.attr('data-equals'),
		show = selector.attr('data-show'),
		show_obj = jQuery('#' + show);

	if(show_obj.is('checkbox')){		show_obj = show_obj.parents('.form_checkbox');}
	else if(show_obj.is('textarea')){	show_obj = show_obj.parents('.form_textarea');}
	else if(show_obj.is('select')){		show_obj = show_obj.parents('.form_select');}

	if(selector.val() == equals){	show_obj.removeClass('hide');}
	else{							show_obj.addClass('hide');}
}

function check_remember_fields()
{
	jQuery.getScript(script_forms.plugins_url + "/mf_base/include/jquery.Storage.js").done(function()
	{
		if(jQuery.Storage)
		{
			var remember_count = 0;

			function show_or_hide_clear_button()
			{
				if(remember_count > 0)
				{
					jQuery('.form_button .button-secondary').removeClass("hide");
				}

				else
				{
					jQuery('.form_button .button-secondary').addClass("hide");
				}
			}

			var remember_fields = jQuery('.mf_form .remember');

			remember_fields.each(function()
			{
				var selector = jQuery(this).find('input, select, textarea'),
					dom_name = selector.attr('name'),
					dom_value = jQuery.Storage.get(dom_name);

				if(typeof dom_value !== 'undefined' && selector.val() == '')
				{
					selector.val(dom_value);

					remember_count++;
				}
			});

			show_or_hide_clear_button();

			remember_fields.on('blur', 'input, select, textarea', function()
			{
				var selector = jQuery(this),
					dom_name = selector.attr('name'),
					dom_value = selector.val();

				if(dom_value != '')
				{
					jQuery.Storage.set(dom_name, dom_value);

					remember_count++;
				}

				else
				{
					var dom_value = jQuery.Storage.get(dom_name);

					if(typeof dom_value !== 'undefined')
					{
						jQuery.Storage.remove(dom_name);

						remember_count--;
					}
				}

				show_or_hide_clear_button();
			});

			jQuery('.form_button button[name=btnFormClear]').on('click', function()
			{
				remember_fields.each(function()
				{
					var selector = jQuery(this).find('input, select, textarea'),
						dom_name = selector.attr('name'),
						dom_value = jQuery.Storage.get(dom_name);

					if(typeof dom_value !== 'undefined')
					{
						selector.val('');

						jQuery.Storage.remove(dom_name);
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
		jQuery.ajax(
		{
			url: script_forms.plugin_url + 'ajax.php?type=zipcode/search/' + search,
			type: 'get',
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					selector.siblings('span').text(data.response);
				}
			}
		});
	}

	else
	{
		selector.siblings('span').empty();
	}
}

jQuery(function($)
{
	on_load_form();

	if(typeof collect_on_load == 'function')
	{
		collect_on_load('on_load_form');
	}

	$.fn.nextElementInDom = function(selector, options)
	{
		var defaults = { stopAt : 'body' };
		options = $.extend(defaults, options);

		var parent = $(this).parent(),
			found = parent.next(selector); /*find -> next*/

		switch(true)
		{
			case (found.length > 0):
			return found;

			case (parent.length === 0 || parent.is(options.stopAt)):
			return $([]);

			default:
			return parent.nextElementInDom(selector);
		}
    };

	$(document).on('click', '.form_link', function(event)
	{
		var dom_obj_link = $(this),
			dom_obj_inline = dom_obj_link.nextElementInDom('.form_inline'),
			dom_overlay = $('#overlay_form > div');

		if(dom_overlay.length == 0)
		{
			$('#wrapper').append("<div id='overlay_form'><div></div></div>");

			dom_overlay = $('#overlay_form > div');
		}

		if(dom_overlay.children().length > 0)
		{
			dom_overlay.html('').parent('#overlay_form').fadeOut();
		}

		else
		{
			dom_overlay.html("<i class='fa fa-2x fa-close'></i>" + dom_obj_inline.html()).parent('#overlay_form').fadeIn();
		}

		return false;
	});

	function hide_form_overlay()
	{
		$('#overlay_form').fadeOut().children('div').html('');
	}

	$(document).on('click', '#overlay_form', function(e)
	{
		if(e.target == e.currentTarget)
		{
			hide_form_overlay();
		}
	});

	$(document).on('click', '#overlay_form .fa-close', function(e)
	{
		hide_form_overlay();
	});

	$(document).on('change', '.mf_form input[type=range]', function()
	{
		update_range_text($(this));
	});

	$(document).on('keyup, focusout', '.mf_form .form_zipcode input', function()
	{
		check_zip_code($(this));
	});

	/* Confirm required e-mail */
	/*function has_required_email()
	{
		var form_obj = $(this).parents('.mf_form');

		if(form_obj.find(':required:invalid').length == 0)
		{
			form_obj.children('.this_is_required_email').siblings('div:not(.form_button)').addClass('hide');

			$(this).removeClass('has_required_email').off('click', has_required_email);

			form_obj.find('.form_button > div.updated').removeClass('hide');

			$('html, body').animate(
			{
				scrollTop: form_obj.offset().top - 200
			}, 750, 'swing');

			return false;
		}
	}

	$(document).on('click', '.mf_form .has_required_email', has_required_email);

	$(document).on('click', '.mf_form .show_none_email', function()
	{
		var form_obj = $(this).parents('.mf_form');

		form_obj.children('div:not(.form_button)').removeClass('hide');

		form_obj.find('.form_button button').addClass('has_required_email').on('click', has_required_email);

		form_obj.find('.form_button > div.updated').addClass('hide');

		return false;
	});*/
	/* ################## */

	$(document).on('change', '.form_action select', function()
	{
		do_form_type_action($(this));
	});
});