<?php

mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_base')));

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

echo "<div class='wrap'>
	<h2>"
		.__("All forms", 'lang_form')
		."<a href='?page=mf_form/create/index.php' class='add-new-h2'>".__("Add New", 'lang_form')."</a>"
	."</h2>"
	.get_notification();

	$tbl_group = new mf_form_table();

	$tbl_group->select_data(array(
		//'select' => "*",
	));

	$tbl_group->do_display();

echo "</div>";

update_user_meta(get_current_user_id(), 'mf_forms_viewed', date("Y-m-d H:i:s"));