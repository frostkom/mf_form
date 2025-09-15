<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_form/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

include_once("../classes.php");

if(!isset($obj_form))
{
	$obj_form = new mf_form();
}

$json_output = ['success' => false];

$type = check_var('type', 'char');

$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_table = (isset($arr_input[1]) ? $arr_input[1] : "");
$type_id = (isset($arr_input[2]) ? $arr_input[2] : "");
$state_id = (isset($arr_input[3]) ? $arr_input[3] : "");

switch($type_action)
{
	case 'autofocus':
		if(is_user_logged_in() && $type_table == 'type')
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT formID, postID FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $type_id));

			foreach($result as $r)
			{
				//$intFormID = $r->formID;
				$intPostID = $r->postID;
			}

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET formTypeAutofocus = '0' WHERE postID = '%d'", $intPostID));

			if($state_id == "true")
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET formTypeAutofocus = '1' WHERE form2TypeID = '%d'", $type_id));
			}

			$json_output['success'] = true;
		}
	break;

	case 'delete':
		if(is_user_logged_in())
		{
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
						if(get_post_status($obj_form->post_id) != 'trash')
						{
							if(wp_trash_post($obj_form->post_id))
							{
								$json_output['success'] = true;
								$json_output['dom_id'] = $type_table."_".$type_id;
							}

							else
							{
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
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form2type WHERE form2TypeID = '%d'", $type_id));

					if($wpdb->rows_affected > 0)
					{
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."form_option WHERE form2TypeID = '%d'", $type_id));

						$json_output['success'] = true;
						$json_output['dom_id'] = $type_table."_".$type_id;
					}
				break;
			}
		}
	break;

	case 'display':
	case 'encrypt':
	case 'remember':
	case 'require':
		if(is_user_logged_in() && $type_table == 'type')
		{
			$type_field = "";

			switch($type_action)
			{
				case 'display':
				case 'remember':
					$type_field = "formType".ucfirst($type_action);
					$type_value = ($state_id == "true" ? 1 : 0);
				break;

				case 'encrypt':
					$type_field = "formType".ucfirst($type_action);
					$type_value = ($state_id == "true" ? 'yes' : 'no');
				break;

				case 'require':
					$type_field = "formTypeRequired";
					$type_value = ($state_id == "true" ? 1 : 0);
				break;

				default:
					do_log("Unknown type_action (".$type_table."->".$type_action.")");
				break;
			}

			if($type_field != '')
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET ".$type_field." = %s WHERE form2TypeID = '%d'", $type_value, $type_id));

				$json_output['debug'] = " (".$wpdb->last_query.")";

				if($wpdb->rows_affected > 0)
				{
					$json_output['success'] = true;
				}

				else
				{
					$json_output['error'] = __("No affected rows", 'lang_form');

					$json_output['error'] .= " (".$wpdb->last_query.")";
				}
			}

			else
			{
				$json_output['error'] = __("No type field", 'lang_form');
			}
		}

		else
		{
			$json_output['error'] = __("Not logged in or wrong type", 'lang_form');
		}
	break;

	case 'sortOrder':
		if(is_user_logged_in())
		{
			$updated = false;

			$strOrder = check_var('strOrder');

			$arr_ids = explode(",", trim($strOrder, ","));

			$i = 0;

			foreach($arr_ids as $str_id)
			{
				list($type, $sort_id) = explode("_", $str_id);

				if($sort_id > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."form2type SET form2TypeOrder = '%d' WHERE form2TypeID = '%d'", $i, $sort_id));

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
	break;
}

echo json_encode($json_output);