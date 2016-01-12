<?php

class mf_form
{
	function mf_form($id = 0)
	{
		if($id > 0)
		{
			$this->id = $id;
		}

		else
		{
			$this->id = check_var('intQueryID');
		}

		$this->query2type_id = 0;
	}

	function fetch_request()
	{
		$this->answer_id = check_var('intAnswerID');
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		if(isset($_GET['btnQueryCopy']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_copy'))
		{
			$inserted = true;

			$result_temp = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$fields = ", queryEmailConfirm, queryEmailConfirmPage, queryShowAnswers, queryAnswerURL, queryEmail, queryEmailNotify, queryEmailName, queryButtonText, queryButtonSymbol, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentCurrency, blogID"; //, queryImproveUX //, queryPaymentCheck, queryPaymentAmount has to be checked for new values since the queryType2ID is new for this form

				//$obj_form = new mf_form();

				$strQueryName = $this->get_form_name($this->id);

				$post_data = array(
					'post_type' => 'mf_form',
					'post_status' => 'publish',
					'post_title' => $strQueryName,
				);

				$intPostID = wp_insert_post($post_data);

				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query (queryName, postID".$fields.", queryCreated, userID) (SELECT CONCAT(queryName, ' (".__("copy", 'lang_form').")'), '%d'".$fields.", NOW(), '".get_current_user_id()."' FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0')", $intPostID, $this->id));
				$intQueryID_new = $wpdb->insert_id;

				if($intQueryID_new > 0)
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' ORDER BY query2TypeID DESC", $this->id));

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
				$error_text = __("Something went wrong. Contact your admin and add this URL as reference", 'lang_form');
			}

			else
			{
				$done_text = __("Wow! The form was copied successfully!", 'lang_form');
			}
		}

		else if(isset($_GET['btnQueryExport']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_export'))
		{
			list($upload_path, $upload_url) = get_uploads_folder("mf_forms");

			$dir_exists = true;

			if(!is_dir($upload_path))
			{
				if(!mkdir($upload_path, 0755, true))
				{
					$dir_exists = false;
				}
			}

			if($dir_exists == false)
			{
				$error_text = __("Could not create a folder in uploads. Please add the correct rights for the script to create a new subfolder", 'lang_form');
			}

			else
			{
				$done_text = "";

				$strExportDate = wp_date_format(array('date' => date("Y-m-d H:i:s"), 'full_datetime' => true));

				$result = $wpdb->get_results($wpdb->prepare("SELECT queryName FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));

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

					$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText FROM ".$wpdb->base_prefix."query2type INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeResult = '1' ORDER BY query2TypeOrder ASC", $this->id));

					foreach($result as $r)
					{
						$intQuery2TypeID = $r->query2TypeID;
						$intQueryTypeID = $r->queryTypeID;
						$strQueryTypeText = preg_replace("/(\r\n|\r|\n|".$field_separator.")/", " ", $r->queryTypeText);

						switch($intQueryTypeID)
						{
							case 2:
								list($strQueryTypeText, $rest) = explode("|", $strQueryTypeText);
							break;

							case 10:
							case 11:
								list($strQueryTypeText, $rest) = explode(":", $strQueryTypeText);
							break;
						}

						$out .= ($i > 0 ? $field_separator : "").stripslashes(strip_tags($strQueryTypeText));

						$i++;
					}

					$out .= $field_separator.__("Created", 'lang_form').$row_separator;

					$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, queryID, answerCreated, answerIP FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID ORDER BY answerCreated DESC", $this->id));
					$rows = $wpdb->num_rows;

					if($rows == 0)
					{
						$error_text = __("There were no answers to export", 'lang_form');
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

									/*if($intQueryTypeID == 8)
									{
										$strAnswerText = 1;
									}

									else
									{*/
										$strAnswerText = $r->answerText;

										switch($intQueryTypeID)
										{
											case 8:
												$strAnswerText = 1;
											break;

											case 7:
												$strAnswerText = wp_date_format(array('date' => $strAnswerText));
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
											break;

											case 15:
												$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $strAnswerText));

												foreach($result as $r)
												{
													$strAnswerText = "<a href='".$r->guid."' rel='external'>".$r->post_title."</a>";
												}
										break;
										}
									//}

									$strAnswerText = preg_replace("/(\r\n|\r|\n|".$field_separator.")/", " ", $strAnswerText);

									$out .= $strAnswerText;
								}

								$i++;
							}

							$out .= $field_separator.$strAnswerCreated.$row_separator;
						}

						$out .= $row_separator.__("Row count", 'lang_form').": ".$rows.$row_separator.__("Date", 'lang_form').": ".$strExportDate;

						$file = $file_base.$file_type;

						$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => trim($out)));

						if($success == true)
						{
							$done_text = __("The form was successfully exported to", 'lang_form')." <a href='".$upload_url.$file."'>".$file."</a>";
						}

						else
						{
							$error_text = __("It was not possible to export all answers from", 'lang_form')." ".$strQueryName;
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
						$objWriter->save($upload_path.$file);

						$done_text .= " ".__("and", 'lang_form')." <a href='".$upload_url.$file."'>".$file."</a>";

						//echo "Current memory usage: " , (memory_get_usage(true) / 1024 / 1024) , " MB";
						//echo "Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB";
					}
					#######################
				}
			}

			get_file_info(array('path' => $upload_path, 'callback' => "delete_old_files"));
		}

		else if(isset($_POST['btnQueryUpdate']))
		{
			$strQueryPrefix = $this->get_post_name()."_";

			$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, checkCode FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE queryID = '%d' AND queryTypeID != '13' ORDER BY query2TypeOrder ASC", $this->id));

			$strAnswerIP = $_SERVER['REMOTE_ADDR'];

			foreach($result as $r)
			{
				$intQuery2TypeID2 = $r->query2TypeID;
				$strCheckCode = $r->checkCode != '' ? $r->checkCode : "char";

				$var = check_var($strQueryPrefix.$intQuery2TypeID2, $strCheckCode, true, '', true, 'post');

				if($var != '')
				{
					$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d' LIMIT 0, 1", $this->answer_id, $intQuery2TypeID2));
					$rowsCheck = $wpdb->num_rows;

					if($rowsCheck > 0)
					{
						$result_temp = $wpdb->get_results($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d' AND answerText = %s LIMIT 0, 1", $this->answer_id, $intQuery2TypeID2, $var));
						$rowsCheck = $wpdb->num_rows;

						if($rowsCheck == 0)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = %s WHERE answerID = '%d' AND query2TypeID = '%d'", $var, $this->answer_id, $intQuery2TypeID2));
						}
					}

					else
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."query_answer SET answerID = '%d', query2TypeID = '%d', answerText = %s", $this->answer_id, $intQuery2TypeID2, $var));
					}
				}
			}

			if(!isset($error_text) || $error_text == '')
			{
				mf_redirect("?page=mf_form/answer/index.php&intQueryID=".$this->id);
			}
		}

		return $out;
	}

	function is_published($data = array())
	{
		global $wpdb;

		if(isset($data['query_id']) && $data['query_id'] > 0)
		{
			$this->id = $data['query_id'];
		}

		if($this->id > 0)
		{
			$post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."query.postID = ".$wpdb->posts.".ID WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
		}

		else
		{
			$post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM ".$wpdb->posts." WHERE ID = '%d'", $data['post_id']));
		}

		return $post_status == 'publish' ? true : false;
	}

	function get_form_name($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		return $wpdb->get_var($wpdb->prepare("SELECT queryName FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $this->id));
	}

	function get_form_id($id)
	{
		global $wpdb;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query WHERE postID = '%d'", $id));

		return $this->id;
	}

	function get_form_id_from_type($id)
	{
		global $wpdb;

		$this->query2type_id = $id;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $id));

		return $this->id;
	}

	function get_post_id($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		return $wpdb->get_var($wpdb->prepare("SELECT postID FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $this->id));
	}

	function get_post_name($data = array())
	{
		global $wpdb;

		if(isset($data['query_id']) && $data['query_id'] > 0)
		{
			$this->id = $data['query_id'];
		}

		if($this->id > 0)
		{
			$post_name = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->posts." ON ".$wpdb->base_prefix."query.postID = ".$wpdb->posts.".ID WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
		}

		else
		{
			$post_name = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM ".$wpdb->posts." WHERE ID = '%d'", $data['post_id']));
		}

		return $post_name != '' ? $post_name : "field";
	}

	function get_form_id_from_post_content($post_id)
	{
		global $wpdb;

		$post_content = mf_get_post_content($post_id);

		$form_id = get_match("/\[mf_form id=(.*?)\]/", $post_content, false);

		if($form_id > 0)
		{
			$this->id = $form_id;
		}
	}

	function get_form_email_field()
	{
		global $wpdb;

		$intQuery2TypeID = $wpdb->get_var($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND checkID = '5'", $this->id));

		return $this->get_post_name()."_".$intQuery2TypeID;
	}

	function is_form_field_type_used($data)
	{
		global $wpdb;

		$query_join = $query_where = "";

		if(isset($data['required']) && $data['required'] != '')
		{
			$query_where .= " AND queryTypeRequired = '".$data['required']."'";
		}

		if(isset($data['check_code']) && $data['check_code'] != '')
		{
			$query_join .= " INNER JOIN ".$wpdb->base_prefix."query_check USING (checkID)";
			$query_where .= " AND checkCode = '".$data['check_code']."'";
		}

		$intQuery2TypeID = $wpdb->get_var($wpdb->prepare("SELECT query2TypeID FROM ".$wpdb->base_prefix."query2type".$query_join." WHERE queryID = '%d' AND queryTypeID = '%d'".$query_where, $this->id, $data['query_type_id']));

		return $intQuery2TypeID > 0 ? true : false;
	}

	function get_type_name($id)
	{
		global $wpdb;

		return $wpdb->get_var($wpdb->prepare("SELECT queryTypeName FROM ".$wpdb->base_prefix."query_type WHERE queryTypeID = '%d'", $id));
	}

	function get_form_type_info($data)
	{
		global $wpdb;

		if(isset($data['query_id']) && $data['query_id'] > 0)
		{
			$this->id = $data['query_id'];
		}

		$query_where = "";

		foreach($data['query_type_id'] as $query_type_id)
		{
			$query_where .= ($query_where != '' ? " OR " : "")."queryTypeID = '".$query_type_id."'";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, queryTypeText, query2TypeOrder FROM ".$wpdb->base_prefix."query2type WHERE queryID = '%d' AND (".$query_where.") ORDER BY query2TypeOrder ASC", $this->id));

		return array($result, $wpdb->num_rows);
	}

	function get_form_type_result()
	{
		global $wpdb;

		if($this->query2type_id > 0)
		{
			$query_where = "query2typeID = '%d'";
			$query_where_id = $this->query2type_id;
		}

		else
		{
			$query_where = "queryID = '%d'";
			$query_where_id = $this->id;
		}

		return $wpdb->get_results($wpdb->prepare("SELECT query2TypeID, queryTypeID, checkCode, checkPattern, queryTypeText, queryTypePlaceholder, queryTypeRequired, queryTypeAutofocus, queryTypeTag, queryTypeClass FROM ".$wpdb->base_prefix."query_check RIGHT JOIN ".$wpdb->base_prefix."query2type USING (checkID) INNER JOIN ".$wpdb->base_prefix."query_type USING (queryTypeID) WHERE ".$query_where." GROUP BY ".$wpdb->base_prefix."query2type.query2TypeID ORDER BY query2TypeOrder ASC", $query_where_id));
	}
}

class mf_form_table extends mf_list_table
{
	function set_default()
	{
		global $wpdb;

		$this->post_type = "mf_form";

		$this->orderby_default = "post_modified";
		$this->orderby_default_order = "desc";

		/*$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_form';*/

		if($this->search != '')
		{
			$this->query_where .= get_form_xtra($this->search);
		}

		$this->set_views(array(
			'db_field' => 'post_status',
			'types' => array(
				'all' => __("All", 'lang_form'),
				'publish' => __("Public", 'lang_form'),
				'draft' => __("Draft", 'lang_form'),
				'trash' => __("Trash", 'lang_form')
			),
		));

		$this->set_columns(array(
			//'cb' => '<input type="checkbox">',
			'post_title' => __("Name", 'lang_form'),
			'shortcode' => __("Shortcode", 'lang_form'),
			'answers' => __("Answers", 'lang_form'),
			'post_modified' => __("Modified", 'lang_form'),
		));

		$this->set_sortable_columns(array(
			'post_title',
			'post_modified',
		));
	}

	function column_default($item, $column_name)
	{
		global $wpdb;

		$out = "";

		$post_id = $item['ID'];
		$post_status = $item['post_status'];

		$obj_form = new mf_form();
		$intQueryID = $obj_form->get_form_id($post_id);

		switch($column_name)
		{
			case 'post_title':
				$strFormName = $item[$column_name];

				$post_edit_url = "?page=mf_form/create/index.php&intQueryID=".$intQueryID;

				$actions = array();

				if($post_status != 'trash')
				{
					if(IS_ADMIN)
					{
						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_form')."</a>";
						$actions['delete'] = "<a href='#delete/query/".$intQueryID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a>";
						$actions['copy'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnQueryCopy&intQueryID=".$intQueryID, 'form_copy')."'>".__("Copy", 'lang_form')."</a>";
					}

					if($obj_form->is_published(array('post_id' => $post_id)))
					{
						$post_url = get_permalink($post_id);

						if($post_url != '')
						{
							$actions['view'] = "<a href='".$post_url."'>".__("View form", 'lang_form')."</a>";
						}
					}
				}

				else
				{
					$actions['recover'] = "<a href='".$post_edit_url."&recover'>".__("Recover", 'lang_form')."</a>";
				}

				$out .= "<a href='".$post_edit_url."'>"
					.$strFormName
				."</a>"
				.$this->row_actions($actions);
			break;

			case 'shortcode':
				if($post_status == 'publish' && $intQueryID > 0)
				{
					$strQueryShortcode = "[mf_form id=".$intQueryID."]";

					$actions = array();

					$result = get_page_from_form($intQueryID);

					if(count($result) > 0)
					{
						foreach($result as $r)
						{
							$post_id_temp = $r['post_id'];

							$actions['edit_page'] = "<a href='".get_site_url()."/wp-admin/post.php?post=".$post_id_temp."&action=edit'>".__("Edit Page", 'lang_form')."</a> | <a href='".get_permalink($post_id_temp)."'>".__("View page", 'lang_form')."</a>";
						}
					}

					else
					{
						$actions['add_page'] = "<a href='".get_site_url()."/wp-admin/post-new.php?post_type=page&content=".$strQueryShortcode."'>".__("Add New Page", 'lang_form')."</a>";
					}

					echo $strQueryShortcode
					.$this->row_actions($actions);
				}
			break;

			case 'answers':
				if($post_status != 'trash')
				{
					$wpdb->query($wpdb->prepare("SELECT answerID FROM ".$wpdb->base_prefix."query2answer INNER JOIN ".$wpdb->base_prefix."query_answer USING (answerID) WHERE queryID = '%d' GROUP BY answerID", $intQueryID));
					$query_answers = $wpdb->num_rows;

					if($query_answers > 0)
					{
						$count_message = get_count_message($intQueryID);

						$actions = array();

						$actions['show_answers'] = "<a href='?page=mf_form/answer/index.php&intQueryID=".$intQueryID."'>".__("Show Answers", 'lang_form')."</a>"; 
						$actions['export_answers'] = "<a href='".wp_nonce_url("?page=mf_form/list/index.php&btnQueryExport&intQueryID=".$intQueryID, 'form_export')."'>".__("Export Answers", 'lang_form')."</a>";

						echo $query_answers
						.$count_message
						.$this->row_actions($actions);
					}
				}
			break;

			default:
				if(isset($item[$column_name]))
				{
					$out .= $item[$column_name];
				}
			break;
		}

		return $out;
	}
}

class mf_form_output
{
	function __construct($data)
	{
		$this->row = $data['result'];
		$this->query_prefix = $data['query_prefix'];
		$this->queryEmailCheckConfirm = isset($data['email_check_confirm']) ? $data['email_check_confirm'] : 'no';

		$this->output = "";

		$this->show_required = $this->show_autofocus = $this->has_required_email = false;

		$this->answer_text = "";

		$this->in_edit_mode = $data['in_edit_mode'];
	}

	function calculate_value($intAnswerID)
	{
		global $wpdb;

		$this->is_required_email = $this->row->queryTypeID == 3 && $this->row->checkCode == 'email' && $this->row->queryTypeRequired == 1;

		if($this->queryEmailCheckConfirm == 'yes' && $this->is_required_email)
		{
			$this->has_required_email = true;
		}

		if($intAnswerID > 0)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d' LIMIT 0, 1", $this->row->query2TypeID, $intAnswerID));

			foreach($result as $r)
			{
				$this->answer_text = $r->answerText;
			}
		}

		if($this->answer_text == '')
		{
			$this->answer_text = check_var($this->query_prefix.$this->row->query2TypeID, 'char');
		}
	}

	function get_form_fields($data = array())
	{
		global $intQueryTypeID2_temp, $intQuery2TypeID2_temp;

		if(!isset($data['show_label'])){	$data['show_label'] = true;}

		$field_data = array(
			'name' => $this->query_prefix.$this->row->query2TypeID,
		);

		switch($this->row->queryTypeID)
		{
			//Checkbox
			case 1:
				$is_first_checkbox = false;

				if($this->row->queryTypeID != $intQueryTypeID2_temp)
				{
					$intQuery2TypeID2_temp = $this->row->query2TypeID;

					$is_first_checkbox = true;
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['value'] = 1;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->queryTypeClass.($is_first_checkbox ? " clear" : "");

				$this->output .= show_checkbox($field_data);

				$this->show_required = true;
			break;

			//Input range
			case 2:
				$arr_content = explode("|", $this->row->queryTypeText);

				if($this->answer_text == '' && isset($arr_content[3]))
				{
					$this->answer_text = $arr_content[3];
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content[0]." (<span>".$this->answer_text."</span>)";
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = "min='".$arr_content[1]."' max='".$arr_content[2]."'".($this->row->queryTypeAutofocus ? " autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass;
				$field_data['type'] = "range";

				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Input date
			case 7:
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass;
				$field_data['type'] = "date";
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;

				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Radio button
			case 8:
				$is_first_radio = false;

				if($this->row->queryTypeID != $intQueryTypeID2_temp)
				{
					$intQuery2TypeID2_temp = $this->row->query2TypeID;

					$is_first_radio = true;
				}

				if(isset($_POST["radio_".$intQuery2TypeID2_temp]))
				{
					$this->answer_text = check_var($_POST["radio_".$intQuery2TypeID2_temp], 'int', false);
				}

				else if($this->answer_text == '' && $this->row->queryTypeRequired == 1)
				{
					$this->answer_text = $this->row->query2TypeID;
				}

				$field_data['name'] = "radio_".$intQuery2TypeID2_temp;

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->row->query2TypeID;
				$field_data['compare'] = $this->answer_text;
				$field_data['xtra_class'] = $this->row->queryTypeClass.($is_first_radio ? " clear" : "");

				$this->output .= show_radio_input($field_data);

				$this->show_required = true;
			break;

			//Select
			case 10:
				$arr_content1 = explode(":", $this->row->queryTypeText);
				$arr_content2 = explode(",", $arr_content1[1]);

				$arr_data = array();

				foreach($arr_content2 as $str_content)
				{
					$arr_content3 = explode("|", $str_content);

					$arr_data[] = array($arr_content3[0], $arr_content3[1]);
				}

				$field_data['data'] = $arr_data;
				
				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content1[0];
				}

				$field_data['compare'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass;

				$this->output .= show_select($field_data);

				$this->show_required = true;
			break;

			//Select (multiple)
			case 11:
				$arr_content1 = explode(":", $this->row->queryTypeText);
				$arr_content2 = explode(",", $arr_content1[1]);

				$arr_data = array();

				foreach($arr_content2 as $str_content)
				{
					$arr_content3 = explode("|", $str_content);

					$arr_data[] = array($arr_content3[0], $arr_content3[1]);
				}
				
				$field_data['name'] .= "[]";
				$field_data['data'] = $arr_data;

				if($data['show_label'] == true)
				{
					$field_data['text'] = $arr_content1[0];
				}

				$field_data['compare'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass;

				$this->output .= show_select($field_data);

				$this->show_required = true;
			break;

			//Textfield
			case 3:
				if($this->row->checkCode == "zip")
				{
					$this->row->queryTypeClass .= ($this->row->queryTypeClass != '' ? " " : "")."form_zipcode";
				}
				
				if($this->has_required_email && $this->is_required_email)
				{
					$this->row->queryTypeClass .= ($this->row->queryTypeClass != '' ? " " : "")."this_is_required_email";
				}

				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['maxlength'] = 200;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['xtra_class'] = $this->row->queryTypeClass;
				$field_data['type'] = $this->row->checkCode;
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;
				$field_data['pattern'] = $this->row->checkPattern;

				$this->output .= show_textfield($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Textarea
			case 4:
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['value'] = $this->answer_text;
				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['xtra'] = ($this->row->queryTypeAutofocus ? "autofocus" : "");
				$field_data['class'] = $this->row->queryTypeClass;
				$field_data['placeholder'] = $this->row->queryTypePlaceholder;

				$this->output .= show_textarea($field_data);

				$this->show_required = $this->show_autofocus = true;
			break;

			//Text
			case 5:
				if($this->row->queryTypeTag != '')
				{
					$this->output .= "<".$this->row->queryTypeTag.($this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "").">"
						.$this->row->queryTypeText
					."</".$this->row->queryTypeTag.">";
				}

				else
				{
					$this->output .= "<div".($this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "").">
						<p>".$this->row->queryTypeText."</p>
					</div>";
				}
			break;

			//Space
			case 6:
				$this->output .= $this->in_edit_mode == true ? "<p class='grey".($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "")."'>(".__("Space", 'lang_form').")</p>" : "<p".($this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "").">&nbsp;</p>";
			break;

			//Referer URL
			case 9:
				$referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey".($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "")."'>".__("Hidden", 'lang_form')." (".$this->row->queryTypeText.": '".$referer_url."')</p>";
				}

				else
				{
					$field_data['value'] = $referer_url;

					$this->output .= input_hidden($field_data);
				}
			break;

			//Hidden field
			case 12:
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey".($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "")."'>".__("Hidden", 'lang_form')." (".$this->query_prefix.$this->row->query2TypeID.": ".$this->row->queryTypeText.")</p>";
				}

				else
				{
					$field_data['value'] = ($this->answer_text != '' ? $this->answer_text : $this->row->queryTypeText);

					$this->output .= input_hidden($field_data);
				}
			break;

			//Custom tag (start)
			case 13:
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;".$this->row->queryTypeText.($this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "")."&gt;</p>";
				}

				else
				{
					$this->output .= "<".$this->row->queryTypeText.($this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "").">";
				}
			break;

			//Custom tag (end)
			case 14:
				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey'>&lt;/".$this->row->queryTypeText."&gt;</p>";
				}

				else
				{
					$this->output .= "</".$this->row->queryTypeText.">";
				}
			break;

			//File
			case 15:
				if($data['show_label'] == true)
				{
					$field_data['text'] = $this->row->queryTypeText;
				}

				$field_data['required'] = $this->row->queryTypeRequired;
				$field_data['class'] = $this->row->queryTypeClass;

				$this->output .= show_file_field($field_data);

				$this->show_required = true;
			break;
		}

		$intQueryTypeID2_temp = $this->row->queryTypeID;
	}

	function get_output($data = array())
	{
		$out = "";

		if(!isset($this->in_edit_mode)){	$this->in_edit_mode = false;}

		if($this->in_edit_mode == true)
		{
			$out .= "<mf-form-row id='type_".$this->row->query2TypeID."'".($data['query2type_id'] == $this->row->query2TypeID ? " class='active'" : "").">"
				.$this->output;

				if($this->row->queryTypeID != 14)
				{
					$out .= "<div class='form_buttons'>";

						if($this->show_required == true)
						{
							$out .= show_checkbox(array('name' => "require_".$this->row->query2TypeID, 'text' => __("Required", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeRequired, 'xtra' => " class='ajax_checkbox' rel='require/type/".$this->row->query2TypeID."'"));
						}

						if($this->show_autofocus == true)
						{
							$out .= show_checkbox(array('name' => "autofocus_".$this->row->query2TypeID, 'text' => __("Autofocus", 'lang_form'), 'value' => 1, 'compare' => $this->row->queryTypeAutofocus, 'xtra' => " class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->query2TypeID."'"));
						}

						$out .= "<a href='?page=mf_form/create/index.php&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Edit", 'lang_form')."</a> | 
						<a href='#delete/type/".$this->row->query2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_form')."</a> | <a href='?page=mf_form/create/index.php&btnFieldCopy&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Copy", 'lang_form')."</a>
					</div>";
				}

			$out .= "</mf-form-row>";
		}

		else
		{
			$out .= $this->output;
		}

		return $out;
	}
}

class widget_form extends WP_Widget
{
	function widget_form()
	{
		$widget_ops = array(
			'classname' => 'form',
			'description' => __("Display a form that you've previously created", 'lang_form')
		);

		$control_ops = array('id_base' => 'form-widget');

		$this->__construct('form-widget', __("Form", 'lang_form'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		if($instance['form_id'] > 0)
		{
			echo $before_widget;

				if($instance['form_heading'] != '')
				{
					echo $before_title
						.$instance['form_heading']
					.$after_title;
				}

				echo show_query_form(array('query_id' => $instance['form_id']))
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['form_heading'] = strip_tags($new_instance['form_heading']);
		$instance['form_id'] = strip_tags($new_instance['form_id']);

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$defaults = array(
			'form_heading' => "",
			'form_id' => "",
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo "<p>
			<label for='".$this->get_field_id('form_heading')."'>".__("Heading", 'lang_form')."</label>
			<input type='text' name='".$this->get_field_name('form_heading')."' value='".$instance['form_heading']."' class='widefat'>
		</p>
		<p>
			<label for='".$this->get_field_id('form_id')."'>".__("Form", 'lang_form')."</label>
			<select name='".$this->get_field_name('form_id')."' id='".$this->get_field_id('form_id')."' class='widefat'>
				<option value=''>-- ".__("Choose here", 'lang_form')." --</option>";

				$result = $wpdb->get_results("SELECT queryID, queryName FROM ".$wpdb->base_prefix."query WHERE queryDeleted = '0'".(IS_ADMIN ? "" : " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)")." ORDER BY queryCreated DESC");

				foreach($result as $r)
				{
					echo "<option value='".$r->queryID."'".($instance['form_id'] == $r->queryID ? " selected" : "").">".$r->queryName."</option>";
				}

			echo "</select>
		</p>";
	}
}