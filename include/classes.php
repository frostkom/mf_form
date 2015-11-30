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
			$resultInfo = $wpdb->get_results($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE query2TypeID = '%d' AND answerID = '%d' LIMIT 0, 1", $this->row->query2TypeID2, $intAnswerID));
			$rowsInfo = $wpdb->num_rows;

			if($rowsInfo > 0)
			{
				$r = $resultInfo[0];
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
				$this->output .= $this->in_edit_mode == true ? "<p class='grey".($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "")."'>(".__("Space", 'lang_forms').")</p>" : "<p".($this->row->queryTypeClass != '' ? " class='".$this->row->queryTypeClass."'" : "").">&nbsp;</p>";
			break;

			//Referer URL
			case 9:
				$referer_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

				if($this->in_edit_mode == true)
				{
					$this->output .= "<p class='grey".($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "")."'>".__("Hidden", 'lang_forms')." (".$this->row->queryTypeText.": '".$referer_url."')</p>";
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
					$this->output .= "<p class='grey".($this->row->queryTypeClass != '' ? " ".$this->row->queryTypeClass : "")."'>".__("Hidden", 'lang_forms')." (".$this->query_prefix.$this->row->query2TypeID.": ".$this->row->queryTypeText.")</p>";
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
							$out .= show_checkbox(array('name' => "require_".$this->row->query2TypeID, 'text' => __("Required", 'lang_forms'), 'value' => 1, 'compare' => $this->row->queryTypeRequired, 'xtra' => " class='ajax_checkbox' rel='require/type/".$this->row->query2TypeID."'"));
						}

						if($this->show_autofocus == true)
						{
							$out .= show_checkbox(array('name' => "autofocus_".$this->row->query2TypeID, 'text' => __("Autofocus", 'lang_forms'), 'value' => 1, 'compare' => $this->row->queryTypeAutofocus, 'xtra' => " class='ajax_checkbox autofocus' rel='autofocus/type/".$this->row->query2TypeID."'"));
						}

						$out .= "<a href='?page=mf_form/create/index.php&btnFieldCopy&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Copy", 'lang_forms')."</a> | 
						<a href='?page=mf_form/create/index.php&intQueryID=".$data['query_id']."&intQuery2TypeID=".$this->row->query2TypeID."'>".__("Edit", 'lang_forms')."</a> | 
						<a href='#delete/type/".$this->row->query2TypeID."' class='ajax_link confirm_link'>".__("Delete", 'lang_forms')."</a>
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
			'description' => __("Display a form that you've previously created", 'lang_forms')
		);

		$control_ops = array('id_base' => 'form-widget');

		$this->__construct('form-widget', __("Form", 'lang_forms'), $widget_ops, $control_ops);
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
			<label for='".$this->get_field_id('form_heading')."'>".__("Heading", 'lang_forms')."</label>
			<input type='text' name='".$this->get_field_name('form_heading')."' value='".$instance['form_heading']."' class='widefat'>
		</p>
		<p>
			<label for='".$this->get_field_id('form_id')."'>".__("Form", 'lang_forms')."</label>
			<select name='".$this->get_field_name('form_id')."' id='".$this->get_field_id('form_id')."' class='widefat'>
				<option value=''>-- ".__("Choose here", 'lang_forms')." --</option>";

				$result = $wpdb->get_results("SELECT queryID, queryName FROM ".$wpdb->base_prefix."query WHERE queryDeleted = '0'".(IS_ADMIN ? "" : " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)")." ORDER BY queryCreated DESC");

				foreach($result as $r)
				{
					echo "<option value='".$r->queryID."'".($instance['form_id'] == $r->queryID ? " selected" : "").">".$r->queryName."</option>";
				}

			echo "</select>
		</p>";
	}
}