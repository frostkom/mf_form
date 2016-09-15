<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url(), 'confirm_question' => __("Are you sure?", 'lang_base')));

$obj_form = new mf_form();

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

if(!($obj_form->id > 0))
{
	$obj_form->id = $wpdb->get_var("SELECT queryID FROM ".$wpdb->base_prefix."query LEFT JOIN ".$wpdb->base_prefix."query2answer USING (queryID) WHERE queryDeleted = '0' ORDER BY answerCreated DESC, queryCreated DESC LIMIT 0, 1");
}

$dteQueryStartDate = check_var('dteQueryStartDate', 'char', true, date("Y-m-d", strtotime("-2 year")));
$dteQueryEndDate = check_var('dteQueryEndDate', 'char', true, date("Y-m-d", strtotime("+1 day")));

$strQuerySearch = "";
$strAnswerText2 = check_var('strAnswerText2');

if($strAnswerText2 != '')
{
	$strQuerySearch .= " AND answerText LIKE '%".esc_sql($strAnswerText2)."%'";
}

if($dteQueryStartDate > DEFAULT_DATE)
{
	$strQuerySearch .= " AND answerCreated >= '".esc_sql($dteQueryStartDate)."'";
}

if($dteQueryEndDate > DEFAULT_DATE)
{
	$strQuerySearch .= " AND answerCreated <= '".esc_sql($dteQueryEndDate)."'";
}

$result = $wpdb->get_results($wpdb->prepare("SELECT queryShowAnswers, queryPaymentProvider, queryPaymentAmount FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $obj_form->id));

foreach($result as $r)
{
	$strFormName = $obj_form->get_post_info(array('select' => "post_title"));

	$intQueryShowAnswers = $r->queryShowAnswers;
	$intQueryPaymentProvider = $r->queryPaymentProvider;
	$intQueryPaymentAmount = $r->queryPaymentAmount;

	$has_payment = $intQueryPaymentProvider > 0 && $intQueryPaymentAmount > 0;
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
			$intQuery2TypeID2 = $r->query2TypeID;
			$intQueryTypeID = $r->queryTypeID;
			$strQueryTypeText2 = $r->queryTypeText;
			$strQuery2TypeOrder2 = $r->query2TypeOrder;

			switch($intQueryTypeID)
			{
				case 8:
					if($order_temp != '' && $strQuery2TypeOrder2 != ($order_temp + 1))
					{
						$i++;
					}
				break;

				case 10:
					$i++;
				break;
			}

			if(!isset($data[$i])){	$data[$i] = "";}

			$order_temp = $strQuery2TypeOrder2;

			switch($intQueryTypeID)
			{
				case 8:
					$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '%d' AND query2TypeID = '%d'", $obj_form->id, $intQueryTypeID, $intQuery2TypeID2));

					$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".shorten_text($strQueryTypeText2, 20)."', data: ".$intAnswerCount."}";
				break;

				case 10:
					list($strQueryTypeText2, $strQueryTypeSelect) = explode(":", $strQueryTypeText2);
					$arr_select_rows = explode(",", $strQueryTypeSelect);

					foreach($arr_select_rows as $select_row)
					{
						$arr_select_row_content = explode("|", $select_row);

						if($arr_select_row_content[0] > 0 && $arr_select_row_content[1] != '')
						{
							$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '%d' AND query2TypeID = '%d' AND answerText = %s", $obj_form->id, $intQueryTypeID, $intQuery2TypeID2, $arr_select_row_content[0]));

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

	$query_xtra = "";

	if($strSearch != '')
	{
		$query_xtra .= " AND (answerText LIKE '%".esc_sql($strSearch)."%' OR answerCreated LIKE '%".esc_sql($strSearch)."%')";
	}

	$resultPagination = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d'".$query_xtra.$strQuerySearch." GROUP BY answerID ORDER BY answerCreated DESC", $obj_form->id));

	echo get_list_navigation($resultPagination)
	."<table class='wp-list-table widefat striped'>";

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryTypeID, queryTypeText, query2TypeID FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $obj_form->id));

		foreach($result as $r)
		{
			$intQueryTypeID = $r->queryTypeID;
			$strQueryTypeText = $r->queryTypeText;
			$intQuery2TypeID2 = $r->query2TypeID;

			switch($intQueryTypeID)
			{
				case 2:
					list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
				break;

				/*case 8:
					$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8' AND query2TypeID = '%d'", $obj_form->id, $intQuery2TypeID2));

					$strQueryTypeText .= " (".$intAnswerCount.")";
				break;*/

				case 10:
				case 11:
					list($strQueryTypeText, $rest) = explode(":", $strQueryTypeText);
				break;
			}

			$arr_header[] = $strQueryTypeText;
		}

		if($has_payment)
		{
			$arr_header[] = __("Payment", 'lang_form');
		}

		$arr_header[] = __("Created", 'lang_form');
		$arr_header[] = __("Sent e-mails", 'lang_form');

		echo show_table_header($arr_header)
		."<tbody>";

			$strFormPrefix = $obj_form->get_post_info()."_";

			$result = $wpdb->get_results("SELECT answerID, answerCreated, answerIP, answerToken FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '".$obj_form->id."'".$query_xtra.$strQuerySearch." GROUP BY answerID ORDER BY answerCreated DESC LIMIT ".esc_sql($intLimitStart).", ".esc_sql($intLimitAmount));
			$rows = $wpdb->num_rows;

			if($rows == 0)
			{
				echo "<tr><td colspan='".count($arr_header)."'>".__("There is nothing to show", 'lang_form')."</td></tr>";
			}

			else
			{
				foreach($result as $r)
				{
					$intAnswerID = $r->answerID;
					$strAnswerCreated = $r->answerCreated;
					$strAnswerIP = $r->answerIP;
					$strAnswerToken = $r->answerToken;

					echo "<tr>";

						$resultText = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, checkCode FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $obj_form->id));

						$j = 0;

						foreach($resultText as $r)
						{
							$intQuery2TypeID = $r->query2TypeID;
							$intQueryTypeID = $r->queryTypeID;
							$strQueryTypeText = $r->queryTypeText;
							$strCheckCode = $r->checkCode;

							$value = 0;
							$xtra = $row_actions = "";

							$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d'", $intQuery2TypeID, $intAnswerID));
							$rowsAnswer = $wpdb->num_rows;

							if($rowsAnswer > 0)
							{
								$r = $resultAnswer[0];
								$strAnswerText = $r->answerText;

								switch($intQueryTypeID)
								{
									case 8:
										$strAnswerText = 1;
									break;

									case 7:
										$strAnswerText = format_date($strAnswerText);
									break;

									case 10:
										$arr_content1 = explode(":", $strQueryTypeText);
										$arr_content2 = explode(",", $arr_content1[1]);

										foreach($arr_content2 as $str_content)
										{
											$arr_content3 = explode("|", $str_content);

											if($strAnswerText == $arr_content3[0])
											{
												$strAnswerText = $arr_content3[1];
											}
										}
									break;

									case 11:
										$arr_content1 = explode(":", $strQueryTypeText);
										$arr_content2 = explode(",", $arr_content1[1]);

										$arr_answer_text = explode(",", str_replace($strFormPrefix, "", $strAnswerText));

										$strAnswerText = "";

										foreach($arr_content2 as $str_content)
										{
											$arr_content3 = explode("|", $str_content);

											if(in_array($arr_content3[0], $arr_answer_text))
											{
												$strAnswerText .= ($strAnswerText != '' ? ", " : "").$arr_content3[1];
											}
										}

										if($strAnswerText == '')
										{
											$strAnswerText = implode(",", $arr_answer_text);
										}
									break;

									case 15:
										$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

										foreach($result as $r)
										{
											$strAnswerText = "<a href='".$r->guid."' rel='external'>".$r->post_title."</a>";
										}
									break;

									default:
										if($strCheckCode != '')
										{
											switch($strCheckCode)
											{
												case 'url':
													$strAnswerText = "<a href='".$strAnswerText."'>".$strAnswerText."</a>";
												break;

												case 'email':
													$strAnswerText = "<a href='mailto:".$strAnswerText."'>".$strAnswerText."</a>";
												break;

												case 'zip':
													$obj_form = new mf_form();

													$row_actions = $obj_form->get_city_from_zip($strAnswerText);
												break;
											}
										}
									break;
								}
							}

							else
							{
								$strAnswerText = "";
							}

							echo "<td>";

								if($strAnswerText != 1)
								{
									if($value == 2)
									{
										echo "<span class='red'>";
									}

									else if($value == 1)
									{
										echo "<span class='green'>";
									}
								}

								echo $strAnswerText;

								if($strAnswerText != 1 && $value > 0)
								{
									echo "</span>";
								}

								if($j == 0)
								{
									$row_actions = "<span class='edit'><a href='?page=mf_form/view/index.php&intQueryID=".$obj_form->id."&intAnswerID=".$intAnswerID."'>".__("Edit", 'lang_form')."</a></span> | "
									."<span class='delete'><a href='#delete/answer/".$intAnswerID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a></span>";
								}

								if($row_actions != '')
								{
									echo "<div class='row-actions'>".$row_actions."</div>";
								}

							echo "</td>";

							$j++;
						}

						if($has_payment)
						{
							$strAnswerText_temp = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '0'", $intAnswerID));

							echo "<td>".$strAnswerText_temp."</td>";
						}

						echo "<td>"
							.format_date($strAnswerCreated)
							."<div class='row-actions'>"
								.__("ID", 'lang_form').": ".$intAnswerID
								." | ".__("IP", 'lang_form').": ".$strAnswerIP;

								if($strAnswerToken != '')
								{
									echo " | ".__("Token", 'lang_form').": ".$strAnswerToken;
								}

								if($has_payment == false)
								{
									$strSentTo = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '0'", $intAnswerID));

									if($strSentTo != '')
									{
										echo " | ".__("Sent to", 'lang_form').": ".$strSentTo;
									}
								}

							echo "</div>
						</td>
						<td>";

							$result_emails = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerSent FROM ".$wpdb->base_prefix."query_answer_email WHERE answerID = '%d'", $intAnswerID));
							$count_temp = $wpdb->num_rows;

							if($count_temp > 0)
							{
								$li_out = "";
								$sent_successfully = 0;

								foreach($result_emails as $r)
								{
									$strAnswerEmail = $r->answerEmail;
									$intAnswerSent = $r->answerSent;

									$li_out .= "<li>";

										if($intAnswerSent == 1)
										{
											$li_out .= "<i class='fa fa-check green'></i>";

											$sent_successfully++;
										}

										else
										{
											$li_out .= "<i class='fa fa-close red'></i>";
										}

										$li_out .= " ".$strAnswerEmail
									."</li>";
								}

								echo $sent_successfully.($count_temp != $sent_successfully ? "/".$count_temp : "")
								."<div class='row-actions'>
									<ul>".$li_out."</ul>
								</div>";
							}

						echo "</td>
					</tr>";
				}
			}

		echo "</tbody>
	</table>
</div>";

update_user_meta(get_current_user_id(), 'answer_viewed', date("Y-m-d H:i:s"));