<?php

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

//$intQueryID = check_var('intQueryID');
//$intAnswerID = check_var('intAnswerID');

echo "<div class='wrap'>
	<h2>".__("Edit", 'lang_form')."</h2>"
	.get_notification()
	.show_query_form(array('query_id' => $obj_form->id, 'answer_id' => $obj_form->answer_id))
."</div>";