<?php

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

echo "<div class='wrap'>
	<h2>".__("Edit", 'lang_form')."</h2>"
	.get_notification()
	.$obj_form->get_form(array('answer_id' => $obj_form->answer_id))
."</div>";