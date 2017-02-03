<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_base')));

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

if($obj_form->id > 0)
{
	$obj_form->check_if_has_payment();

	//$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')."</h2>" //." ".$strFormName
		.get_notification()
		.$obj_form->get_pie_chart();

		$tbl_group = new mf_answer_table();

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated, answerIP, answerSpam, answerToken",
			//'debug' => true,
		));

		$tbl_group->do_display();

	echo "</div>";

	update_user_meta(get_current_user_id(), 'answer_viewed', date("Y-m-d H:i:s"));
}

else
{
	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')."</h2>
		<p><em>".sprintf(__("I could not find a form with the ID '%d'", 'lang_form'), $obj_form->id)."</em></p>
	</div>";
}