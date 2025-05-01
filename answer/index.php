<?php

$obj_form = new mf_form();
$obj_form->fetch_request();
echo $obj_form->save_data();

if($obj_form->id > 0)
{
	$strFormName = $obj_form->get_post_info(array('select' => 'post_title'));

	echo "<div class='wrap'>
		<h2>"
			.__("Answers", 'lang_form')." <span>".$strFormName."</span>";

			$export_url = "admin.php?page=mf_form/answer/index.php&btnFormAnswerExport&btnExportRun&intExportType=".$obj_form->id; //&intFormID=".$obj_form->id."

			$search = check_var('s');

			if($search != '')
			{
				$export_url .= "&s=".$search;
			}

			$export_url .= "&strExportFormat=";

			echo "<a href='".wp_nonce_url(admin_url($export_url."csv"), 'export_run', '_wpnonce_export_run')."' class='add-new-h2'>".sprintf(__("Export as %s", 'lang_form'), "CSV")."</a>";

			if(is_plugin_active("mf_phpexcel/index.php"))
			{
				echo "<a href='".wp_nonce_url(admin_url($export_url."xls"), 'export_run', '_wpnonce_export_run')."' class='add-new-h2'>XLS</a>";
			}

		echo "</h2>"
		.get_notification();

		$tbl_group = new mf_answer_table();

		$tbl_group->select_data(array(
			'select' => "answerID, answerCreated, answerIP, answerSpam, spamID",
			//'debug' => true,
		));

		$tbl_group->do_display();

		if($tbl_group->search == '')
		{
			echo $obj_form->get_pie_chart();
		}

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