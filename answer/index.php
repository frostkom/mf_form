<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url()));
mf_enqueue_script('jquery-flot', plugins_url()."/mf_form/include/jquery.flot.min.js");
mf_enqueue_script('jquery-flot-pie', plugins_url()."/mf_form/include/jquery.flot.pie.min.js");

$intQueryID = check_var('intQueryID');
$intAnswerID = check_var('intAnswerID');

$paged = check_var('paged', 'int', true, '0');
$strSearch = check_var('s', 'char', true);

$intLimitAmount = 20;
$intLimitStart = $paged * $intLimitAmount;

if(!($intQueryID > 0))
{
	$intQueryID = $wpdb->get_var("SELECT queryID FROM ".$wpdb->base_prefix."query LEFT JOIN ".$wpdb->base_prefix."query2answer USING (queryID) WHERE queryDeleted = '0' ORDER BY answerCreated DESC, queryCreated DESC LIMIT 0, 1");
}

$dteQueryStartDate = check_var('dteQueryStartDate', 'char', true, date("Y-m-d", strtotime("-2 year")));
$dteQueryEndDate = check_var('dteQueryEndDate', 'char', true, date("Y-m-d", strtotime("+1 day")));

$strQuerySearch = "";
$strAnswerText2 = check_var('strAnswerText2');

if($strAnswerText2 != '')
{
	$strQuerySearch .= " AND answerText LIKE '%".$strAnswerText2."%'";
}

if($dteQueryStartDate > DEFAULT_DATE)
{
	$strQuerySearch .= " AND answerCreated >= '".$dteQueryStartDate."'";
}

if($dteQueryEndDate > DEFAULT_DATE)
{
	$strQuerySearch .= " AND answerCreated <= '".$dteQueryEndDate."'";
}

$result = $wpdb->get_results($wpdb->prepare("SELECT queryName, queryShowAnswers, queryPaymentProvider, queryPaymentAmount FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryID)); //, queryPaymentCheck

foreach($result as $r)
{
	$strQueryName = $r->queryName;
	$intQueryShowAnswers = $r->queryShowAnswers;
	$intQueryPaymentProvider = $r->queryPaymentProvider;
	//$intQueryPaymentCheck = $r->queryPaymentCheck;
	$intQueryPaymentAmount = $r->queryPaymentAmount;

	$has_payment = $intQueryPaymentProvider > 0 && $intQueryPaymentAmount > 0; // && $intQueryPaymentCheck > 0
}

echo "<div class='wrap'>
	<h2>".__("Answers in", 'lang_forms')." ".$strQueryName."</h2>"
	.get_notification();

	$intTotalAnswers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8'", $intQueryID));

	if($intTotalAnswers > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeText, query2TypeOrder FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND queryTypeID = '8' ORDER BY query2TypeOrder ASC", $intQueryID)); //, query2TypeCreated ASC
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			$out = $js_out = $order_temp = "";
			$data = array();

			$i = 0;

			foreach($result as $r)
			{
				$intQuery2TypeID2 = $r->query2TypeID;
				$strQueryTypeText2 = $r->queryTypeText;
				$strQuery2TypeOrder2 = $r->query2TypeOrder;

				if($order_temp != '' && $strQuery2TypeOrder2 != ($order_temp + 1))
				{
					$i++;
				}

				$order_temp = $strQuery2TypeOrder2;

				$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8' AND query2TypeID = '%d'", $intQueryID, $intQuery2TypeID2));

				if(!isset($data[$i])){	$data[$i] = "";}

				$data[$i] .= ($data[$i] != '' ? "," : "")."{label: '".$strQueryTypeText2."', data: ".$intAnswerCount."}";
			}

			foreach($data as $key => $value)
			{
				$out .= "<div id='flot_pie_".$key."' class='flot_pie'></div>";
				$js_out .= "$.plot($('#flot_pie_".$key."'), [".$value."], { series: { pie: { show: true }}});";
			}

			echo $out
			."<script>
				jQuery(function($)
				{"
					.$js_out
				."});
			</script>";
		}
	}

	$query_xtra = "";

	if($strSearch != '')
	{
		$query_xtra .= " AND (answerText LIKE '%".$strSearch."%' OR answerCreated LIKE '%".$strSearch."%')";
	}

	$resultPagination = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d'".$query_xtra.$strQuerySearch." GROUP BY answerID ORDER BY answerCreated DESC", $intQueryID));

	echo get_list_navigation($resultPagination);

	echo "<table class='widefat striped'>";

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryTypeID, queryTypeText, query2TypeID FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $intQueryID));

		foreach($result as $r)
		{
			$intQueryTypeID = $r->queryTypeID;
			$strQueryTypeText = $r->queryTypeText;
			$intQuery2TypeID2 = $r->query2TypeID;

			if($intQueryTypeID == 2)
			{
				list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
			}

			else if($intQueryTypeID == 8)
			{
				$intAnswerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_answer USING (query2TypeID) WHERE queryID = '%d' AND queryTypeID = '8' AND query2TypeID = '%d'", $intQueryID, $intQuery2TypeID2));

				$strQueryTypeText .= " (".$intAnswerCount.")";
			}

			else if($intQueryTypeID == 10 || $intQueryTypeID == 11)
			{
				list($strQueryTypeText, $rest) = explode(":", $strQueryTypeText);
			}

			$arr_header[] = $strQueryTypeText;
		}

		if($has_payment)
		{
			$arr_header[] = __("Payment", 'lang_forms');
		}

		$arr_header[] = __("Created", 'lang_forms');
		$arr_header[] = __("Sent e-mails", 'lang_forms');

		echo show_table_header($arr_header)
		."<tbody>";

			$obj_form = new mf_form($intQueryID);

			$strQueryPrefix = $obj_form->get_post_name()."_";

			$result = $wpdb->get_results("SELECT answerID, queryID, answerCreated, answerIP, answerToken FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '".$intQueryID."'".$query_xtra.$strQuerySearch." GROUP BY answerID ORDER BY answerCreated DESC LIMIT ".$intLimitStart.", ".$intLimitAmount);
			$rows = $wpdb->num_rows;

			if($rows == 0)
			{
				echo "<tr><td colspan='".count($arr_header)."'>".__("There is nothing to show", 'lang_forms')."</td></tr>";
			}

			else
			{
				foreach($result as $r)
				{
					$intAnswerID = $r->answerID;
					$intQueryID = $r->queryID;
					$strAnswerCreated = $r->answerCreated;
					$strAnswerIP = $r->answerIP;
					$strAnswerToken = $r->answerToken;

					echo "<tr id='answer_".$intAnswerID."'".">";

						$resultText = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, checkCode FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $intQueryID));

						$j = 0;

						foreach($resultText as $r)
						{
							$intQuery2TypeID = $r->query2TypeID;
							$intQueryTypeID = $r->queryTypeID;
							$strQueryTypeText = $r->queryTypeText;
							$strCheckCode = $r->checkCode;

							$value = 0;
							$xtra = "";

							$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d'", $intQuery2TypeID, $intAnswerID));
							$rowsAnswer = $wpdb->num_rows;

							if($rowsAnswer > 0)
							{
								if($intQueryTypeID == 8)
								{
									$strAnswerText = 1;
								}

								else
								{
									$r = $resultAnswer[0];
									$strAnswerText = $r->answerText;

									if($intQueryTypeID == 7)
									{
										$strAnswerText = wp_date_format(array('date' => $strAnswerText));
									}

									else if($intQueryTypeID == 10)
									{
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
									}

									else if($intQueryTypeID == 11)
									{
										$arr_content1 = explode(":", $strQueryTypeText);
										$arr_content2 = explode(",", $arr_content1[1]);

										$arr_answer_text = explode(",", str_replace($strQueryPrefix, "", $strAnswerText));

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
									}

									else
									{
										if($strCheckCode != '')
										{
											if($strCheckCode == "url")
											{
												$strAnswerText = "<a href='".$strAnswerText."'>".$strAnswerText."</a>";
											}

											else if($strCheckCode == "email")
											{
												$strAnswerText = "<a href='mailto:".$strAnswerText."'>".$strAnswerText."</a>";
											}
										}
									}
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
									echo "<div class='row-actions'>"
										."<a href='?page=mf_form/view/index.php&intQueryID=".$intQueryID."&intAnswerID=".$intAnswerID."'>".__("Edit", 'lang_forms')."</a> | "
										."<a href='#delete/answer/".$intAnswerID."' class='ajax_link confirm_link'>".__("Delete", 'lang_forms')."</a>
									</div>";
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
							.wp_date_format(array('date' => $strAnswerCreated, 'full_datetime' => true))
							."<div class='row-actions'>"
								.__("ID", 'lang_forms').": ".$intAnswerID
								." | ".__("IP", 'lang_forms').": ".$strAnswerIP;

								if($strAnswerToken != '')
								{
									echo " | ".__("Token", 'lang_forms').": ".$strAnswerToken;
								}

								if($has_payment == false)
								{
									$strSentTo = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '0'", $intAnswerID));

									if($strSentTo != '')
									{
										echo " | ".__("Sent to", 'lang_forms').": ".$strSentTo;
									}
								}

							echo "</div>
						</td>
						<td>";

							$result_emails = $wpdb->get_results($wpdb->prepare("SELECT answerEmail, answerSent FROM ".$wpdb->base_prefix."query_answer_email WHERE answerID = '%d'", $intAnswerID));
							$count_temp = $wpdb->num_rows;

							if($count_temp > 0)
							{
								echo $count_temp." ".__("Sent", 'lang_forms')
								."<div class='row-actions'>
									<ul>";

										foreach($result_emails as $r)
										{
											$strAnswerEmail = $r->answerEmail;
											$intAnswerSent = $r->answerSent;

											echo "<li>";

												if($intAnswerSent == 1)
												{
													echo "<i class='fa fa-check green'></i>";
												}

												else
												{
													echo "<i class='fa fa-close red'></i>";
												}

												echo " ".$strAnswerEmail
											."</li>";
										}

									echo "</ul>
								</div>";
							}

						echo "</td>
					</tr>";
				}
			}

		echo "</tbody>
	</table>
</div>";