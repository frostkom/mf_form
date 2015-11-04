<?php

$wp_root = '../../../..';

if(file_exists($wp_root.'/wp-load.php'))
{
	require_once($wp_root.'/wp-load.php');
}

else
{
	require_once($wp_root.'/wp-config.php');
}

$json_output = array();

$type = check_var('type', 'char');

$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_table = isset($arr_input[1]) ? $arr_input[1] : "";
$type_id = isset($arr_input[2]) ? $arr_input[2] : "";
$state_id = isset($arr_input[3]) ? $arr_input[3] : "";

if($type_action == "delete" && get_current_user_id() > 0)
{
	if($type_table == "query")
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryDeleted = '1', queryDeletedDate = NOW(), queryDeletedID = '".get_current_user_id()."' WHERE queryID = '%d' AND queryDeleted = '0'", $type_id));

		if($wpdb->rows_affected > 0)
		{
			$obj_form = new mf_form();

			$intPostID = $obj_form->get_post_id($type_id);

			if(wp_trash_post($intPostID))
			{
				$json_output['success'] = true;
				$json_output['dom_id'] = $type_table."_".$type_id;
			}

			else
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query SET queryDeleted = '0', queryDeletedDate = '', queryDeletedID = '' WHERE queryID = '%d' AND queryDeleted = '1'", $type_id));

				$json_output['queryID'] = $type_id;
				$json_output['postID'] = $intPostID;
			}
		}
	}

	else if($type_table == "answer")
	{
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d'", $type_id));
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query2answer WHERE answerID = '%d'", $type_id));

		if($wpdb->rows_affected > 0)
		{
			$json_output['success'] = true;
			$json_output['dom_id'] = $type_table."_".$type_id;
		}
	}

	else if($type_table == "type")
	{
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $type_id));

		if($wpdb->rows_affected > 0)
		{
			$json_output['success'] = true;
			$json_output['dom_id'] = $type_table."_".$type_id;
		}
	}
}

else if($type_action == "require" && get_current_user_id() > 0)
{
	if($type_table == "type")
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeRequired = '%d' WHERE query2TypeID = '%d'", ($state_id == "true" ? 1 : 0), $type_id));

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

else if($type_action == "autofocus" && get_current_user_id() > 0)
{
	if($type_table == "type")
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT queryID FROM ".$wpdb->base_prefix."query2type WHERE query2TypeID = '%d'", $type_id));

		foreach($result as $r)
		{
			$intQueryID = $r->queryID;
		}

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeAutofocus = '0' WHERE queryID = '%d'", $intQueryID));

		if($state_id == "true")
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET queryTypeAutofocus = '1' WHERE query2TypeID = '%d'", $type_id));
		}

		$json_output['success'] = true;
	}
}

else if($type_action == "sortOrder" && get_current_user_id() > 0)
{
	$updated = false;

	$strOrder = check_var('strOrder');

	$json_output['strOrder'] = $strOrder;

	$arr_ids = explode(",", trim($strOrder, ","));

	$i = 0;

	foreach($arr_ids as $str_id)
	{
		list($type, $sort_id) = explode("_", $str_id);

		$json_output['sort_id'] = $sort_id;

		if($sort_id > 0)
		{
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2type SET query2TypeOrder = '%d' WHERE query2TypeID = '%d'", $i, $sort_id));

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

else if($type_action == "zipcode")
{
	$search = str_replace(" ", "", $type_id);

	$result = $wpdb->get_results($wpdb->prepare("SELECT cityName, municipalityName FROM ".$wpdb->base_prefix."query_zipcode WHERE addressZipCode = '%d'", $search));

	foreach($result as $r)
	{
		$strCityName = $r->cityName;
		$strMunicipalityName = $r->municipalityName;

		/*$exclude = array('&Aring;',	'&aring;',	'&Auml;',	'&auml;',	'&Ouml;',	'&ouml;');
		$include = array('Å',			'å',		'Ä',		'ä',		'Ö',		'ö');

		$strCityName = str_replace($exclude, $include, $strCityName);
		$strMunicipalityName = str_replace($exclude, $include, $strMunicipalityName);*/

		$json_output['success'] = true;
		$json_output['response'] = $strCityName.($strMunicipalityName != '' && $strMunicipalityName != $strCityName ? ", ".$strMunicipalityName : "");
	}
}

echo json_encode($json_output);