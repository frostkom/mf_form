<?php

wp_enqueue_style('style_forms_wp', plugins_url()."/mf_form/include/style_wp.css");
mf_enqueue_script('script_forms_wp', plugins_url()."/mf_form/include/script_wp.js", array('plugins_url' => plugins_url()));

$folder = str_replace("plugins/mf_form/list", "", dirname(__FILE__));

$intQueryID = check_var('intQueryID');

$paged = check_var('paged', 'int', true, '0');
$strSearch = check_var('s', 'char', true);

$intLimitAmount = 20;
$intLimitStart = $paged * $intLimitAmount;

if(isset($_GET['btnQueryCopy']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_copy'))
{
	$inserted = true;

	$result_temp = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryID));
	$rows = $wpdb->num_rows;

	if($rows > 0)
	{
		$fields = ", queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailName, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentCurrency, blogID"; //, queryImproveUX //, queryPaymentCheck, queryPaymentAmount has to be checked for new values since the queryType2ID is new for this form

		$obj_form = new mf_form();

		$strQueryName = $obj_form->get_form_name($intQueryID);

		$post_data = array(
			'post_type' => 'mf_form',
			'post_status' => 'publish',
			'post_title' => $strQueryName,
		);

		$intPostID = wp_insert_post($post_data);

		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query (queryName, postID".$fields.", queryCreated, userID) (SELECT CONCAT(queryName, ' (".__("copy", 'lang_forms').")'), '%d'".$fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0')", $intPostID, $intQueryID));
		$intQueryID_new = $wpdb->insert_id;

		if($intQueryID_new > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeID DESC", $intQueryID));

			foreach($result as $r)
			{
				$intQuery2TypeID = $r->query2TypeID;

				$fields = "queryTypeID, queryTypeText, checkID, queryTypeRequired, queryTypeAutofocus, query2TypeOrder";

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query2type (queryID, ".$fields.", query2TypeCreated, userID) (SELECT %d, ".$fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d')", $intQueryID_new, $intQuery2TypeID));

				if(!($wpdb->insert_id > 0))
				{
					$inserted = false;
				}
			}
		}

		else
		{
			$inserted = false;
		}
	}

	if($inserted == false)
	{
		$error_text = __("Something went wrong. Contact your admin and add this URL as reference", 'lang_forms');
	}

	else
	{
		$done_text = __("Wow! The form was copied successfully!", 'lang_forms');
	}
}

if(isset($_GET['btnQueryExport']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_export'))
{
	$upload_path = "/uploads/mf_forms/";

	$dir_exists = true;

	if(!is_dir($folder.$upload_path))
	{
		if(!mkdir($folder.$upload_path, 0755, true))
		{
			$dir_exists = false;
		}
	}

	if($dir_exists == false)
	{
		$error_text = __("Could not create a folder in uploads. Please add the correct rights for the script to create a new subfolder", 'lang_forms');
	}

	else
	{
		$done_text = "";

		$strExportDate = wp_date_format(array('date' => date("Y-m-d H:i:s"), 'full_datetime' => true));

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryName FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $intQueryID));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$strQueryName = $r->queryName;
			}

			$file_base = sanitize_title_with_dashes(sanitize_title($strQueryName))."_".date("YmdHis").".";

			//Export to CSV
			#######################
			$file_type = "csv";
			$field_separator = ",";
			$row_separator = "\n";

			$i = 0;
			$out = "";

			$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $intQueryID));

			foreach($result as $r)
			{
				$intQuery2TypeID = $r->query2TypeID;
				$intQueryTypeID = $r->queryTypeID;
				$strQueryTypeText = preg_replace("/(\r\n|\r|\n|".$field_separator.")/", " ", $r->queryTypeText);

				if($intQueryTypeID == 2)
				{
					list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
				}

				else if($intQueryTypeID == 10 || $intQueryTypeID == 11)
				{
					list($strQueryTypeText, $rest) = explode(":", $strQueryTypeText);
				}

				$out .= ($i > 0 ? $field_separator : "").stripslashes(strip_tags($strQueryTypeText));

				$i++;
			}

			$out .= $field_separator.__("Created", 'lang_forms').$row_separator;

			$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, queryID, answerCreated, answerIP FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID ORDER BY answerCreated DESC", $intQueryID));
			$rows = $wpdb->num_rows;

			if($rows == 0)
			{
				$error_text = __("There were no answers to export", 'lang_forms');
			}

			else
			{
				foreach($result as $r)
				{
					$intAnswerID = $r->answerID;
					$intQueryID = $r->queryID;
					$strAnswerCreated = $r->answerCreated;
					$strAnswerIP = $r->answerIP;

					$resultText = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $intQueryID));

					$i = 0;

					foreach($resultText as $r)
					{
						$intQuery2TypeID = $r->query2TypeID;
						$intQueryTypeID = $r->queryTypeID;
						$strQueryTypeText = $r->queryTypeText;

						$resultAnswer = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d'", $intQuery2TypeID, $intAnswerID));
						$rowsAnswer = $wpdb->num_rows;

						if($i > 0){$out .= $field_separator;}

						if($rowsAnswer > 0)
						{
							$r = $resultAnswer[0];

							if($intQueryTypeID == 8)
							{
								$strAnswerText = 1;
							}

							else
							{
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

									$arr_answer_text = explode(",", $strAnswerText);

									$strAnswerText = "";

									foreach($arr_content2 as $str_content)
									{
										$arr_content3 = explode("|", $str_content);

										if(in_array($arr_content3[0], $arr_answer_text))
										{
											$strAnswerText .= ($strAnswerText != '' ? ", " : "").$arr_content3[1];
										}
									}
								}
							}

							$strAnswerText = preg_replace("/(\r\n|\r|\n|".$field_separator.")/", " ", $strAnswerText);

							$out .= $strAnswerText;
						}

						$i++;
					}

					$out .= $field_separator.$strAnswerCreated.$row_separator;
				}

				$out .= $row_separator.__("Row count", 'lang_forms').": ".$rows.$row_separator.__("Date", 'lang_forms').": ".$strExportDate;

				$file = $file_base.$file_type;

				$success = set_file_content(array('file' => $folder.$upload_path.$file, 'mode' => 'a', 'content' => trim($out)));

				if($success == true)
				{
					$done_text = __("The form was successfully exported to", 'lang_forms')." <a href='../wp-content".$upload_path.$file."'>".$file."</a>";
				}

				else
				{
					$error_text = __("It was not possible to export all answers from", 'lang_forms')." ".$strQueryName;
				}
			}
			#######################

			//Export to XLS
			#######################
			if(is_plugin_active("mf_phpexcel/index.php"))
			{
				$arr_alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

				$file_type = "xls";

				$objPHPExcel = new PHPExcel();

				//$objPHPExcel->getProperties()->setCreator("")->setLastModifiedBy("")->setTitle("")->setSubject("")->setDescription("")->setKeywords("")->setCategory("");

				$arr_rows = explode("\n", $out);

				foreach($arr_rows as $row_key => $row_value)
				{
					$arr_cols = explode(",", $row_value);

					foreach($arr_cols as $col_key => $col_value)
					{
						$cell = "";

						$count_temp = count($arr_alphabet);

						while($col_key >= $count_temp)
						{
							$cell .= $arr_alphabet[floor($col_key / $count_temp) - 1];

							$col_key = $col_key % $count_temp;
						}

						$cell .= $arr_alphabet[$col_key].($row_key + 1);

						$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell, $col_value);
					}
				}

				/*$objPHPExcel->getActiveSheet()->getRowDimension(8)->setRowHeight(-1);
				$objPHPExcel->getActiveSheet()->getStyle('A8')->getAlignment()->setWrapText(true);*/

				//$objPHPExcel->getActiveSheet()->setTitle($strQueryName);

				$file = $file_base.$file_type;

				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); //XLSX: Excel2007
				$objWriter->save($folder.$upload_path.$file);

				$done_text .= " ".__("and", 'lang_forms')." <a href='../wp-content".$upload_path.$file."'>".$file."</a>";

				//echo "Current memory usage: " , (memory_get_usage(true) / 1024 / 1024) , " MB";
				//echo "Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB";
			}
			#######################
		}
	}

	get_file_info(array('path' => $folder.$upload_path, 'callback' => "delete_old_files"));
}

echo "<div class='wrap'>
	<h2>"
		.__("All forms", 'lang_forms')
		."<a href='?page=mf_form/create/index.php' class='add-new-h2'>".__("Add New", 'lang_forms')."</a>"
	."</h2>"
	.get_notification();

	$query_where = get_form_xtra($strSearch);

	echo get_post_filter(array(
		'plugin' => 'mf_form',
		'db_field' => 'post_status',
		'types' => array(
			'all' => __("All", 'lang_forms'),
			'publish' => __("Public", 'lang_forms'),
			'draft' => __("Draft", 'lang_forms'),
			'trash' => __("Trash", 'lang_forms')
		),
	), $query_where);

	$resultPagination = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_form'".$query_where);

	echo get_list_navigation($resultPagination)
	."<table class='widefat striped'>";

		$arr_header[] = __("Name", 'lang_forms');

		if(IS_EDITOR)
		{
			$arr_header[] = __("Shortcode", 'lang_forms');
		}

		$arr_header[] = __("Answers", 'lang_forms');

		echo show_table_header($arr_header)
		."<tbody>";

			//$result = $wpdb->get_results("SELECT ID, post_title, post_date FROM ".$wpdb->posts." WHERE post_type = 'mf_form'".$query_where." ORDER BY post_date DESC LIMIT ".$intLimitStart.", ".$intLimitAmount);
			$result = $wpdb->get_results("SELECT queryID, postID, queryName, queryDeleted FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."query.postID = ".$wpdb->posts.".ID ".$query_where." GROUP BY queryID ORDER BY queryDeleted ASC, queryCreated DESC LIMIT ".$intLimitStart.", ".$intLimitAmount);

			if($wpdb->num_rows == 0)
			{
				echo "<tr><td colspan='".count($arr_header)."'>".__("There is nothing to show", 'lang_forms')."</td></tr>";
			}

			else
			{
				foreach($result as $r)
				{
					/*$intPostID = $r->ID;
					$strQueryName = $r->post_title;

					$obj_form = new mf_form();
					$intQueryID = $obj_form->get_form_id($intPostID);*/

					$intQueryID = $r->queryID;
					$intPostID = $r->postID;
					$strQueryName = $r->queryName;
					$intQueryDeleted = $r->queryDeleted;

					$resultContent = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' ORDER BY query2TypeCreated ASC", $intQueryID));

					$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID", $intQueryID));
					$intQueryTotal = $wpdb->num_rows;

					$result_temp = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' LIMIT 0, 1", $intQueryID));
					$rowsQuery = $wpdb->num_rows;

					$class = "";

					if($intQueryDeleted == 1)
					{
						$class .= ($class != '' ? " " : "")."deleted";
					}

					echo "<tr id='query_".$intQueryID."'".($class != '' ? " class='".$class."'" : "").">
						<td>
							<a href='?page=mf_form/create/index.php&intQueryID=".$intQueryID."'>".$strQueryName."</a>
							<div class='row-actions'>";

								if($intQueryDeleted == 0)
								{
									echo "<a href='?page=mf_form/create/index.php&intQueryID=".$intQueryID."'>".__("Edit", 'lang_forms')."</a> | 
									<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnQueryCopy&intQueryID=".$intQueryID, 'form_copy')."'>".__("Copy", 'lang_forms')."</a> | 
									<a href='#delete/query/".$intQueryID."' class='ajax_link confirm_link'>".__("Delete", 'lang_forms')."</a>";

									$obj_form = new mf_form();

									if($obj_form->is_published(array('post_id' => $intPostID)))
									{
										$post_url = get_permalink($intPostID);

										if($post_url != '')
										{
											echo " | <a href='".$post_url."'>".__("View form", 'lang_forms')."</a>";
										}
									}
								}

								else
								{
									echo "<a href='?page=mf_form/create/index.php&intQueryID=".$intQueryID."&recover'>".__("Recover", 'lang_forms')."</a>";
								}

							echo "</div>
						</td>";

						if(IS_EDITOR)
						{
							echo "<td class='nowrap'>";

								if($intQueryDeleted == 0)
								{
									$strQueryShortcode = "[mf_form id=".$intQueryID."]";

									$row_actions = "";

									$result = get_page_from_form($intQueryID);

									if(count($result) > 0)
									{
										foreach($result as $r)
										{
											$post_id = $r['post_id'];

											$row_actions .= ($row_actions != '' ? " | " : "")."<a href='".get_site_url()."/wp-admin/post.php?post=".$post_id."&action=edit'>".__("Edit Page", 'lang_forms')."</a> | <a href='".get_permalink($post_id)."'>".__("View page", 'lang_forms')."</a>";
										}
									}

									else
									{
										$row_actions .= ($row_actions != '' ? " | " : "")."<a href='".get_site_url()."/wp-admin/post-new.php?post_type=page&content=".$strQueryShortcode."'>".__("Add New Page", 'lang_forms')."</a>";
									}

									echo $strQueryShortcode
									."<div class='row-actions'>".$row_actions."</div>";
								}

							echo "</td>";
						}

						echo "<td>";

							if($intQueryDeleted == 0)
							{
								$count_message = get_count_message($intQueryID);

								echo $intQueryTotal.$count_message;

								if($intQueryTotal > 0)
								{
									echo "<div class='row-actions'>
										<a href='?page=mf_form/answer/index.php&intQueryID=".$intQueryID."'>".__("Show Answers", 'lang_forms')."</a> | 
										<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnQueryExport&intQueryID=".$intQueryID, 'form_export')."'>".__("Export Answers", 'lang_forms')."</a>
									</div>";
								}
							}

						echo "</td>
					</tr>";
				}
			}

		echo "</tbody>
	</table>
</div>";

update_user_meta(get_current_user_id(), 'mf_forms_viewed', date("Y-m-d H:i:s"));