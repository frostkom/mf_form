<?php

$plugin_include_url = plugins_url()."/mf_form/include/";
$plugin_version = get_plugin_version(__FILE__);

mf_enqueue_style('style_forms_wp', $plugin_include_url."style_wp.css", $plugin_version);
mf_enqueue_script('script_forms_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_form')), $plugin_version);

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

if($obj_form->id > 0)
{
	$obj_form->check_if_has_payment();
	$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')." (".$strFormName.")</h2>"
		.get_notification()
		.$obj_form->get_pie_chart();

		$tbl_group = new mf_answer_table();

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated, answerIP, answerSpam, spamID, answerToken",
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