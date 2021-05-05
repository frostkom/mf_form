<?php

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

if($obj_form->id > 0)
{
	$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')." (".$strFormName.")</h2>"
		.get_notification()
		.$obj_form->get_pie_chart();

		$tbl_group = new mf_answer_table();

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated, answerIP, answerSpam, spamID, answerToken",
			'debug' => ($_SERVER['REMOTE_ADDR'] == ""),
		));

		$tbl_group->do_display();

	echo "</div>";
}

else
{
	echo "<div class='wrap'>
		<h2>".__("Answers", 'lang_form')."</h2>
		<p><em>".sprintf(__("I could not find a form with the ID %s", 'lang_form'), "'".$obj_form->id."'")."</em></p>
	</div>";
}