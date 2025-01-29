(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('mf/form',
	{
		title: script_form_block_wp.block_title,
		description: script_form_block_wp.block_description,
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
            }
		},
		'supports':
		{
			'html': false,
			'multiple': true,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
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
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						InspectorControls,
						'div',
						el(
							SelectControl,
							{
								label: script_form_block_wp.form_id_label,
								value: props.attributes.form_id,
								options: convert_php_array_to_block_js(script_form_block_wp.form_id),
								onChange: function(value)
								{
									props.setAttributes({form_id: value});
								}
							}
						),
						/*props.attributes.form_id && el(
							'a',
							{
								href: script_form_block_wp.form_edit_link + '${props.attributes.form_id}',
								target: '_blank',
								rel: 'noopener noreferrer'
							},
							script_form_block_wp.form_edit_link_label
						),*/
						/*el(
							'a',
							{
								href: script_form_block_wp.form_list_link,
							},
							script_form_block_wp.form_list_link_label
						),*/
					),
					el(
						'strong',
						{className: props.className},
						script_form_block_wp.block_title
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});
})();