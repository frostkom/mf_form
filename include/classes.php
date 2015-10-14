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

		return $wpdb->get_var($wpdb->prepare("SELECT queryName FROM ".$wpdb->base_prefix."query WHERE queryID = '%d' AND queryDeleted = '0'", $this->id));
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

		$is_super_admin = current_user_can('install_plugins');

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

				$result = $wpdb->get_results("SELECT queryID, queryName FROM ".$wpdb->base_prefix."query WHERE queryDeleted = '0'".($is_super_admin ? "" : " AND (blogID = '".$wpdb->blogid."' OR blogID IS null)")." ORDER BY queryCreated DESC");

				foreach($result as $r)
				{
					echo "<option value='".$r->queryID."'".($instance['form_id'] == $r->queryID ? " selected" : "").">".$r->queryName."</option>";
				}

			echo "</select>
		</p>";
	}
}