<?php

function get_form_url($form_id)
{
	$out = "#";

	if($form_id > 0)
	{
		foreach(get_pages_from_shortcode("[mf_form id=".$form_id."]") as $post_id)
		{
			$out = get_permalink($post_id);
		}
	}

	return $out;
}