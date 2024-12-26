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
		icon: 'forms',
		category: 'widgets',
		'attributes':
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
		'supports':
		{
			'html': false,
			'multiple': true,
			'align': true,
			/*'align': [ 'left', 'right', 'center', 'wide', 'full' ],
			'alignWide': false,*/
			'spacing':
			{
				'margin': true,
				'padding': true
				/*blockGap: true,*/
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
				/*'link': true,*/
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}/*,
			'dimensions':
			{
				'aspectRatio': true,
				'minHeight': true
			},
			'position':
			{
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
			return el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Select a Form", 'lang_form'),
						value: props.attributes.form_id,
						options: convert_php_array_to_block_js(script_form_block_wp.form_id),
						onChange: function(value)
						{
							props.setAttributes({form_id: value});
						}
					}
				)
			);
		},
		save: function()
		{
			return null;
		}
	});
})();