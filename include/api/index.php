<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_form/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

include_once("../classes.php");
require_once("../functions.php");

$obj_form = new mf_form();

$json_output = array();

$type = check_var('type', 'char');

$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_table = isset($arr_input[1]) ? $arr_input[1] : "";
$type_id = isset($arr_input[2]) ? $arr_input[2] : "";
$state_id = isset($arr_input[3]) ? $arr_input[3] : "";

if(get_current_user_id() > 0)
{
	switch($type_action)
	{
		case 'autofocus':
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
		break;

		case 'delete':
			switch($type_table)
			{
				case 'answer':
					if($obj_form->delete_answer($type_id) > 0)
					{
						$json_output['success'] = true;
						$json_output['dom_id'] = $type_table."_".$type_id;
					}
				break;

				case 'form':
					$obj_form->id = $type_id;

					$obj_form->get_post_id();

					if(!($obj_form->post_id > 0))
					{
						$json_output['error'] = __("The form can not be deleted because I could not find the post ID", 'lang_form');
					}

					else if($obj_form->get_answer_amount(array('form_id' => $obj_form->id)) > 0)
					{
						$json_output['error'] = __("The form can not be deleted because there are answers", 'lang_form');
					}

					else
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
				break;

				case 'type':
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form2type WHERE form2TypeID = '%d'", $type_id));

					if($wpdb->rows_affected > 0)
					{
						if($obj_form->form_option_exists)
						{
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_option WHERE form2TypeID = '%d'", $type_id));
						}

						//$wpdb->query(wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."form_answer WHERE form2TypeID = '%d'", $type_id));

						$json_output['success'] = true;
						$json_output['dom_id'] = $type_table."_".$type_id;
					}
				break;
			}
		break;

		case 'display':
		case 'remember':
		case 'require':
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
		break;

		case 'sortOrder':
			$updated = false;

			$strOrder = check_var('strOrder');

			$arr_ids = explode(",", trim($strOrder, ","));

			$i = 0;

			foreach($arr_ids as $str_id)
			{
				list($type, $sort_id) = explode("_", $str_id);

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
		break;
	}
}

if($type_action == 'zipcode')
{
	do_action('run_cache', array('suffix' => 'json'));

	$search = str_replace(" ", "", $type_id);
	$city_name = "";

	if(get_bloginfo('language') == "sv-SE")
	{
		include_once("../class_zipcode.php");
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