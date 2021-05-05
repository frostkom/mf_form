<?php

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

echo "<div class='wrap'>
	<h2>"
		.__("Forms", 'lang_form')
		."<a href='".admin_url("admin.php?page=mf_form/create/index.php")."' class='add-new-h2'>".__("Add New", 'lang_form')."</a>"
	."</h2>"
	.get_notification();

	$tbl_group = new mf_form_table(array(
		'remember_search' => true,
	));

	$tbl_group->select_data(array(
		//'select' => "*",
		'debug' => ($_SERVER['REMOTE_ADDR'] == ""),
	));

	$tbl_group->do_display();

echo "</div>";

update_user_meta(get_current_user_id(), 'meta_forms_viewed', date("Y-m-d H:i:s"));
update_option('option_form_list_viewed', date("Y-m-d H:i:s"), 'no'); //Temporary timestamp for updating wp_query -> wp_form