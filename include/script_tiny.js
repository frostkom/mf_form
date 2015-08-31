

/*(function()
{
    tinymce.create('tinymce.plugins.Shortcodes',
	{
        init : function(ed, url)
		{
			alert('Test -1');

			ed.addButton('dropcap', {
                title : 'DropCap',
                cmd : 'dropcap',
                image : url + '/dropcap.jpg'
            });
 
            ed.addButton('showrecent', {
                title : 'Add recent posts shortcode',
                cmd : 'showrecent',
                image : url + '/recent.jpg'
            });
 
            ed.addCommand('dropcap', function() {
                var selected_text = ed.selection.getContent();
                var return_text = '';
                return_text = '<span class="dropcap">' + selected_text + '</span>';
                ed.execCommand('mceInsertContent', 0, return_text);
            });
 
            ed.addCommand('showrecent', function() {
                var number = prompt("How many posts you want to show ? "),
                    shortcode;
                if (number !== null) {
                    number = parseInt(number);
                    if (number > 0 && number <= 20) {
                        shortcode = '[recent-post number="' + number + '"/]';
                        ed.execCommand('mceInsertContent', 0, shortcode);
                    }
                    else {
                        alert("The number value is invalid. It should be from 0 to 20.");
                    }
                }
            });
		},
        createControl : function(n, cm)
		{
			alert('Test 0');

            if(n=='Shortcodes')
			{
				alert('Test 1');

                var mlb = cm.createListBox('Shortcodes',
				{
                    title : 'Shortcodes',
                    onselect : function(v)
					{
                        if(v == 'shortcode 1')
						{
                            selected = tinyMCE.activeEditor.selection.getContent();

                            if( selected )
							{
                                //If text is selected when button is clicked
                                //Wrap shortcode around it.
                                content =  '[thirdwidth]'+selected+'[/thirdwidth]';
                            }
							
							else
							{
                                content =  '[thirdwidth][/thirdwidth]';
                            }

                            tinymce.execCommand('mceInsertContent', false, content);
                        }

                        if(v == 'shortcode 2')
						{
                            selected = tinyMCE.activeEditor.selection.getContent();

                            if( selected ){
                                //If text is selected when button is clicked
                                //Wrap shortcode around it.
                                content =  '[12]'+selected+'[/12]';
                            }else{
                                content =  '[12][/12]';
                            }

                            tinymce.execCommand('mceInsertContent', false, content);

                        }


                     }
                });


                // Add some menu items
                var my_shortcodes = ['shortcode 1','shortcode 2'];

                for(var i in my_shortcodes)
                    mlb.add(my_shortcodes[i],my_shortcodes[i]);

                return mlb;
            }
            return null;
        }


    });
    tinymce.PluginManager.add('Shortcodes', tinymce.plugins.Shortcodes);
})();*/