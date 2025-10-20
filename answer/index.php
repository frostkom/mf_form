<?php

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

if($obj_form->id > 0)
{
	$obj_form->init();

	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')." <span>".$obj_form->get_form_name()."</span>"."</h2>"
		.get_notification();

		$tbl_group = new mf_answer_table();

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated, answerIP, answerSpam, spamID",
			//'debug' => true,
		));

		$tbl_group->do_display();

	echo "</div>";

	update_user_meta(get_current_user_id(), 'meta_forms_viewed', date("Y-m-d H:i:s"));
}

else
{
	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')."</h2>
		<p><em>".sprintf(__("I could not find a form with the ID %s", 'lang_form'), "'".$obj_form->id."'")."</em></p>
	</div>";
}