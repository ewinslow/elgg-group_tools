<?php
	/**
	 * Elgg groups plugin
	 * 
	 * @package ElggGroups
	 */

	$group = $vars['entity'];
	$forward_url = $group->getURL();
	
	if ($friends = get_loggedin_user()->getFriends("", false)) {
		$friendspicker .= elgg_view("friends/picker", array("entities" => $friends, "internalname" => "user_guid", "highlight" => "all"));
		$friendspicker .= "<br />";	
	}
	
	$form_data = elgg_view("input/hidden", array("internalname" => "forward_url", "value" => $forward_url));
	$form_data .= elgg_view("input/hidden", array("internalname" => "group_guid", "value" => $group->getGUID()));
	
	$invite_site_members = get_plugin_setting("invite", "group_tools");
	$invite_email = get_plugin_setting("invite_email", "group_tools");
	
	if(($invite_site_members == "yes") || ($invite_email == "yes")){
		// friends and all users
		$form_data .= "<div id='elgg_horizontal_tabbed_nav'>";
		$form_data .= "<ul>";
		$form_data .= "<li class='selected' rel='friends'><a href='javascript:void(0);' onclick='group_tools_group_invite_switch_tab(\"friends\");'>" . elgg_echo("friends") . "</a></li>";
		if($invite_site_members == "yes"){
			$form_data .= "<li rel='users'><a href='javascript:void(0);' onclick='group_tools_group_invite_switch_tab(\"users\");'>" . elgg_echo("group_tools:group:invite:users") . "</a></li>";
		}
		if($invite_email == "yes"){
			$form_data .= "<li rel='email'><a href='javascript:void(0);' onclick='group_tools_group_invite_switch_tab(\"email\");'>" . elgg_echo("group_tools:group:invite:email") . "</a></li>";
		}
		$form_data .= "</ul>";
		$form_data .= "</div>";
		
		$form_data .= "<div id='group_tools_group_invite_friends'>";
		$form_data .= $friendspicker;
		$form_data .= "</div>";

		if($invite_site_members == "yes"){
			$form_data .= "<div id='group_tools_group_invite_users'>";
			$form_data .= "<div>" . elgg_echo("group_tools:group:invite:users:description") . "</div>";
			$form_data .= elgg_view("input/group_invite_autocomplete", array("internalname" => "user_guid", 
																				"internalid" => "group_tools_group_invite_autocomplete",
																				"group_guid" => $group->getGUID(),
																				"relationship" => "site"));
			$form_data .= "</div>";
		}
		
		if($invite_email == "yes"){
			$form_data .= "<div id='group_tools_group_invite_email'>";
			$form_data .= "<div>" . elgg_echo("group_tools:group:invite:email:description") . "</div>";
			$form_data .= elgg_view("input/group_invite_autocomplete", array("internalname" => "user_guid", 
																				"internalid" => "group_tools_group_invite_autocomplete_email",
																				"group_guid" => $group->getGUID(),
																				"relationship" => "email"));
			$form_data .= "</div>";
		}
		
		
	} else {
		// only friends
		$form_data .= $friendspicker;
	}
	
	$form_data .= "<div><label>" . elgg_echo("group_tools:group:invite:text") . "</label></div>";
	$form_data .= elgg_view("input/longtext", array("internalname" => "comment"));
	
	$form_data .= "<div>";
	$form_data .= elgg_view("input/submit", array("internalname" => "submit", "value" => elgg_echo("invite")));
	if(isadminloggedin()){
		$form_data .= "&nbsp;";
		$form_data .= elgg_view("input/submit", array("internalname" => "submit", "value" => elgg_echo("group_tools:add_users"), "js" => "onclick='return confirm(\"" . elgg_echo("group_tools:group:invite:add:confirm") . "\");'"));
	}
	$form_data .= "</div>";
	
	$form = elgg_view("input/form", array("body" => $form_data,
											"action" => $vars["url"] . "action/groups/invite"));
	
	echo elgg_view("page_elements/contentwrapper", array("body" => $form));
?>
<script type="text/javascript">
	function group_tools_group_invite_switch_tab(tab){
		$('#elgg_horizontal_tabbed_nav li').removeClass('selected');

		$('#elgg_horizontal_tabbed_nav li[rel="' + tab + '"]').addClass('selected');

		switch(tab){
			case "users":
				$('#group_tools_group_invite_friends').hide();
				$('#group_tools_group_invite_email').hide();
				$('#group_tools_group_invite_users').show();
				break;
			case "email":
				$('#group_tools_group_invite_friends').hide();
				$('#group_tools_group_invite_users').hide();
				$('#group_tools_group_invite_email').show();
				break;
			default:
				$('#group_tools_group_invite_users').hide();
				$('#group_tools_group_invite_email').hide();
				$('#group_tools_group_invite_friends').show();
				break;
		}
	}
</script>