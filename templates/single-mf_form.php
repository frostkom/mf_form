<?php

get_header();

	if(have_posts())
	{
		echo "<article class='single-mf_form'>";

			while(have_posts())
			{
				the_post();

				$post_id = $post->ID;
				$post_status = $post->post_status;
				$post_title = $post->post_title;

				if($post_status == 'publish')
				{
					global $obj_form;

					if(!isset($obj_form))
					{
						$obj_form = new mf_form();
					}

					$obj_form->id = get_post_meta($post_id, $obj_form->meta_prefix.'form_id', true);

					echo "<h1>".$post_title."</h1>
					<section>"
						.$obj_form->process_form()
					."</section>";
				}

				else
				{
					wp_redirect("/404/");
				}
			}

		echo "</article>";
	}

get_footer();