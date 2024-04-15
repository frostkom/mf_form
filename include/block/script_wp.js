(function()
{
	var __ = wp.i18n.__,
		el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl;

	registerBlockType('mf/form',
	{
		title: __("Form", 'lang_form'),
		description: __("Display a Form", 'lang_form'),
		icon: 'forms', /* https://developer.wordpress.org/resource/dashicons/ */
		category: 'widgets', /* common, formatting, layout, widgets, embed */
		'attributes': /* https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/ */
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'form_id':
			{
                'type': 'string',
                'default': ''
            },/*,
			backgroundColor: {
				type: 'string',
				default: 'some-preset-background-slug',
			},
			fontSize: {
				type: 'string',
				default: 'some-value',
			},
			style: {
				dimensions: {
					aspectRatio: '16/9',
					minHeight: '50vh'
				},
				margin: 'value',
				padding: {
					top: 'value',
				},
				position: {
					type: 'sticky',
					top: '0px'
				}
			}*/
		},
		'supports': {
			/*'className': false,
			'customClassName': false,
			'anchor': true,*/
			'html': false,
			'multiple': false,
			'align': true,
			/*'align': [ 'left', 'right', 'center', 'wide', 'full' ],
			'alignWide': false,*/
			'spacing': {
				'margin': true,
				'padding': true
				/*blockGap: true,*/
			},
			'color': {
				'background': true,
				'gradients': false,
				'text': true
				/*'link': true,*/
			},
			'defaultStylePicker': true,
			'typography': {
				'fontSize': true,
				'lineHeight': true
			}/*,
			'dimensions': {
				'aspectRatio': true,
				'minHeight': true
			},
			'position': {
				'sticky': true
			}*/
		},
		/*'styles': [
			{
				'name': 'default',
				'label': __("Rounded", 'lang_form'),
				'isDefault': true
			},
			{
				'name': 'outline',
				'label': __("Outline", 'lang_form')
			},
			{
				'name': 'squared',
				'label': __("Squared", 'lang_form')
			},
		],*/
		edit: function(props)
		{
			var arr_options = [];

			jQuery.each(script_form_block_wp.form_id, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			if(arr_options.length > 0)
			{
				return el(
					'div',
					{className: "wp_mf_block " + props.className},
					el(
						SelectControl,
						{
							label: __("Select a Form", 'lang_form'),
							value: props.attributes.form_id,
							options: arr_options,
							onChange: function(value)
							{
								props.setAttributes({form_id: value});
							}
						}
					)
				);
			}

			else
			{
				return el(
					'em',
					{className: "wp_mf_block " + props.className},
					__("There are no forms yet. Create one and then you can add it here", 'lang_form')
				);
			}
		},

		save: function()
		{
			return null;
		}
	});
})();