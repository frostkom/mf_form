<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_base')));

$obj_form = new mf_form();

if($obj_form->id > 0)
{
	$query_pie = $query_table = false;

	$query_answers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND (queryTypeID = '8' OR queryTypeID = '10')", $obj_form->id));

	if($query_answers > 0)
	{
		list($resultPie, $rowsPie) = $obj_form->get_form_type_info(array('query_type_id' => array(8, 10)));

		if($rowsPie > 0)
		{
			$query_pie = true;

			mf_enqueue_script('jquery-flot', plugins_url()."/mf_base/include/jquery.flot.min.0.7.js");
			mf_enqueue_script('jquery-flot-pie', plugins_url()."/mf_base/include/jquery.flot.pie.min.js");
		}
	}

	$paged = check_var('paged', 'int', true, '0');
	$strSearch = check_var('s', 'char', true);

	$intLimitAmount = 20;
	$intLimitStart = $paged * $intLimitAmount;

	/*if(!($obj_form->id > 0))
	{
		$obj_form->id = $wpdb->get_var("SELECT queryID FROM ".$wpdb->base_prefix."query LEFT JOIN ".$wpdb->base_prefix."query2answer USING (queryID) WHERE queryDeleted = '0' ORDER BY answerCreated DESC, queryCreated DESC LIMIT 0, 1");
	}*/

	$result = $wpdb->get_results($wpdb->prepare("SELECT queryShowAnswers, queryPaymentProvider, queryPaymentAmount FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id));

	foreach($result as $r)
	{
		$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

		$intFormShowAnswers = $r->queryShowAnswers;
		$intFormPaymentProvider = $r->queryPaymentProvider;
		$intFormPaymentAmount = $r->queryPaymentAmount;

		$obj_form->has_payment = $intFormPaymentProvider > 0 && $intFormPaymentAmount > 0;
	}

	echo "<div class='wrap'>
		<h2>".__("Answers in", 'lang_form')." ".$strFormName."</h2>"
		.get_notification();

		if($query_pie == true)
		{
			$out = $js_out = $order_temp = "";
			$data = array();

			$i = 0;

			foreach($resultPie as $r)
			{
				$intForm2TypeID2 = $r->query2TypeID;
				$intFormTypeID = $r->queryTypeID;
				$strFormTypeText2 = $r->queryTypeText;
				$strForm2TypeOrder2 = $r->query2TypeOrder;

				switch($intFormTypeID)
				{
					case 8:
						if($order_temp != '' && $strForm2TypeOrder2 != ($order_temp + 1))
						{
							$i++;
						}
					break;

					case 10:
						$i++;
					break;
				}

				if(!isset($data[$i])){	$data[$i] = "";}

				$order_temp = $strForm2TypeOrder2;

				switch($intFormTypeID)
				{
					case 8:
						$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '%d' AND query2TypeID = '%d'", $obj_form->id, $intFormTypeID, $intForm2TypeID2));

						$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".shorten_text($strFormTypeText2, 20)."', data: ".$intAnswerCount."}";
					break;

					case 10:
						list($strFormTypeText2, $strFormTypeSelect) = explode(":", $strFormTypeText2);
						$arr_select_rows = explode(",", $strFormTypeSelect);

						foreach($arr_select_rows as $select_row)
						{
							$arr_select_row_content = explode("|", $select_row);

							if($arr_select_row_content[0] > 0 && $arr_select_row_content[1] != '')
							{
								$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '%d' AND query2TypeID = '%d' AND answerText = %s", $obj_form->id, $intFormTypeID, $intForm2TypeID2, $arr_select_row_content[0]));

								if($intAnswerCount > 0)
								{
									$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".shorten_text($arr_select_row_content[1], 20)."', data: ".$intAnswerCount."}";
								}
							}
						}
					break;
				}
			}

			foreach($data as $key => $value)
			{
				$out .= "<div id='flot_pie_".$key."' class='flot_pie'></div>";
				$js_out .= "$.plot($('#flot_pie_".$key."'), [".$value."],
				{
					series:
					{
						pie:
						{
							innerRadius: 0.3,
							show: true,
							radius: 1,
							label:
							{
								show: true,
								radius: 3/5,
								formatter: function(label, series)
								{
									return series.data[0][1]; /*Math.round(series.percent) + '%'*/
								},
								/*threshold: 0.1,*/
								background:
								{
									opacity: 0.5
								}
							}
						}
					},
					legend: {
						show: true
					}
				});";
				
				/*pie:
				{
					combine:
					{
						color: '#999',
						label: '".__("Other", 'lang_form')."',
						threshold: 0.1
					}
				}*/

				/*series:{},
				grid:
				{
					hoverable: true,
					clickable: true
				}*/
			}

			echo $out
			."<script>
				jQuery(function($)
				{"
					.$js_out
				."});
			</script>";
		}

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