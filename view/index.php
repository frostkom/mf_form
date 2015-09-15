<?php

//wp_enqueue_style('style-forms', plugins_url()."/mf_form/include/style.css");
//wp_enqueue_script('forms-js', plugins_url()."/mf_form/include/script.js", array('jquery'), '1.0', true);

$intQueryID = check_var('intQueryID');
$intAnswerID = check_var('intAnswerID');

if(isset($_POST['btnQueryUpdate']))
{
	$obj_form = new mf_form($intQueryID);

	$strQueryPrefix = $obj_form->get_post_name()."_";

	$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, checkCode FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeID != '13' ORDER BY query2TypeOrder ASC, query2TypeCreated ASC", $intQueryID));

	$strAnswerIP = $_SERVER['REMOTE_ADDR'];

	foreach($result as $r)
	{
		$intQuery2TypeID2 = $r->query2TypeID;
		$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";

		$var = check_var($strQueryPrefix.$intQuery2TypeID2, $strCheckCode, true, '', true, 'post');

		if($var != '')
		{
			$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d' LIMIT 0, 1", $intAnswerID, $intQuery2TypeID2));
			$rowsCheck = $wpdb->num_rows;

			if($rowsCheck > 0)
			{
				$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $intAnswerID, $intQuery2TypeID2, $var));
				$rowsCheck = $wpdb->num_rows;

				if($rowsCheck == 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '%d'", $var, $intAnswerID, $intQuery2TypeID2));
				}
			}

			else
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '%d', answerText = %s", $intAnswerID, $intQuery2TypeID2, $var));
			}
		}
	}

	if(!isset($error_text) || $error_text == '')
	{
		mf_redirect("?page=mf_form/answer/index.php&intQueryID=".$intQueryID);
	}
}

echo "<div class='wrap'>
	<h2>".__("Edit", 'lang_forms')."</h2>"
	.get_notification()
	.show_query_form(array('query_id' => $intQueryID, 'answer_id' => $intAnswerID))
."</div>";