<?php

/* Deprecated 180829 but needs to be here until all MF Form *** plugins are updated */
/*function get_site_language($data) //sv_SE, en_US etc.
{
	do_log("Old get_site_language() is still in use");

	$obj_form_payment = new mf_form_payment();

	return $obj_form_payment->get_site_language($data);
}*/

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

function get_form_xtra($query_xtra = "", $search = "", $prefix = " WHERE", $field_name = "formName")
{
	global $wpdb;

	$setting_form_permission_see_all = get_option('setting_form_permission_see_all');
	$is_allowed_to_see_all_forms = $setting_form_permission_see_all != '' ? current_user_can($setting_form_permission_see_all) : true;

	if(!$is_allowed_to_see_all_forms)
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : $prefix)." ".$wpdb->base_prefix."form.userID = '".get_current_user_id()."'";
	}

	if($search != '')
	{
		$query_xtra .= ($query_xtra != '' ? " AND" : $prefix)." ".$field_name." LIKE '%".$search."%'";
	}

	return $query_xtra;
}