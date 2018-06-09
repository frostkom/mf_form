<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_form/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$json_output = array();

$type = check_var('type', 'char');

$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_table = isset($arr_input[1]) ? $arr_input[1] : "";
$type_id = isset($arr_input[2]) ? $arr_input[2] : "";
$state_id = isset($arr_input[3]) ? $arr_input[3] : "";

if(get_current_user_id() > 0)
{
	if($type_action == 'delete')
	{
		if($type_table == 'form')
		{
			$obj_form = new mf_form($type_id);

			if($obj_form->get_answer_amount(array('form_id' => $obj_form->id)) == 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET formDeleted = '1', formDeletedDate = NOW(), formDeletedID = '".get_current_user_id()."' WHERE formID = '%d' AND formDeleted = '0'", $type_id));

				if($wpdb->rows_affected > 0)
				{
					if(wp_trash_post($obj_form->post_id))
					{
						$json_output['success'] = true;
						$json_output['dom_id'] = $type_table."_".$type_id;
					}

					else
					{
						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form SET formDeleted = '0', formDeletedDate = '', formDeletedID = '' WHERE formID = '%d' AND formDeleted = '1'", $type_id));

						$json_output['formID'] = $type_id;
						$json_output['postID'] = $obj_form->post_id;
					}
				}

				else
				{
					$json_output['error'] = __("It looks like the form already has been deleted", 'lang_form');
				}
			}

			else
			{
				$json_output['error'] = __("You don't have permission to delete this form", 'lang_form');
			}
		}

		else if($type_table == 'answer')
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE answerID = '%d'", $type_id));
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer_email WHERE answerID = '%d'", $type_id));
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2answer WHERE answerID = '%d'", $type_id));

			if($wpdb->rows_affected > 0)
			{
				$json_output['success'] = true;
				$json_output['dom_id'] = $type_table."_".$type_id;
			}
		}

		else if($type_table == 'type')
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $type_id));

			if($wpdb->rows_affected > 0)
			{
				//$wpdb->query(wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d'", $type_id));

				$json_output['success'] = true;
				$json_output['dom_id'] = $type_table."_".$type_id;
			}
		}
	}

	else if(in_array($type_action, array('display', 'require', 'remember')))
	{
		if($type_table == 'type')
		{
			switch($type_action)
			{
				case 'display':
					$type_field = "formTypeDisplay";
				break;

				case 'require':
					$type_field = "formTypeRequired";
				break;

				case 'remember':
					$type_field = "formTypeRemember";
				break;
			}

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET ".$type_field." = '%d' WHERE form2TypeID = '%d'", ($state_id == "true" ? 1 : 0), $type_id));

			if($wpdb->rows_affected > 0)
			{
				$json_output['success'] = true;
			}

			else
			{
				$json_output['error'] = mysql_error();
			}
		}
	}

	else if($type_action == 'autofocus')
	{
		if($type_table == 'type')
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT formID FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $type_id));

			foreach($result as $r)
			{
				$intFormID = $r->formID;
			}

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeAutofocus = '0' WHERE formID = '%d'", $intFormID));

			if($state_id == "true")
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET formTypeAutofocus = '1' WHERE form2TypeID = '%d'", $type_id));
			}

			$json_output['success'] = true;
		}
	}

	else if($type_action == 'sortOrder')
	{
		$updated = false;

		$strOrder = check_var('strOrder');

		//$json_output['strOrder'] = $strOrder;

		$arr_ids = explode(",", trim($strOrder, ","));

		$i = 0;

		foreach($arr_ids as $str_id)
		{
			list($type, $sort_id) = explode("_", $str_id);

			//$json_output['sort_id'] = $sort_id;

			if($sort_id > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."form2type SET form2TypeOrder = '%d' WHERE form2TypeID = '%d'", $i, $sort_id));

				$i++;

				if($wpdb->rows_affected > 0)
				{
					$updated = true;
				}
			}
		}

		if($updated == true)
		{
			$json_output['success'] = true;
		}
	}
}

if($type_action == 'zipcode')
{
	if(is_plugin_active('mf_cache/index.php'))
	{
		$obj_cache = new mf_cache();
		$obj_cache->fetch_request();
		$obj_cache->get_or_set_file_content('json');
	}

	$search = str_replace(" ", "", $type_id);
	$city_name = "";

	if(get_bloginfo('language') == "sv-SE")
	{
		include_once("class_zipcode.php");
		$obj_zipcode = new mf_zipcode();

		$city_name = $obj_zipcode->get_city($search);
	}

	if($city_name != '')
	{
		$json_output['success'] = true;
		$json_output['response'] = $city_name;
	}
}

echo json_encode($json_output);