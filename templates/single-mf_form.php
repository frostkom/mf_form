<?php

get_header();

	if(have_posts())
	{
		echo "<article".(IS_ADMINISTRATOR ? " class='single-mf_form'" : "").">";

			while(have_posts())
			{
				the_post();

				$post_id = $post->ID;
				$post_status = $post->post_status;
				$post_title = $post->post_title;

				if($post_status == 'publish')
				{
					$intFormID = $wpdb->get_var($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form WHERE postID = '%d'", $post_id));

					echo "<h1>".$post_title."</h1>
					<section>"
						.apply_filters('the_content', "[mf_form id=".$intFormID."]")
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