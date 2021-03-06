<?php 

	require_once(dirname(__FILE__) . "/lib/functions.php");

	function group_tools_init(){
		
		if(is_plugin_enabled("groups")){
			// extend css
			elgg_extend_view("css", "group_tools/css");
			elgg_extend_view("js/initialise_elgg", "group_tools/js");
			
			// extend groups page handler
			group_tools_extend_page_handler("groups", "group_tools_groups_page_handler");
			
			if(get_plugin_setting("multiple_admin", "group_tools") == "yes"){
				// add group tool option
				add_group_tool_option("group_multiple_admin_allow", elgg_echo("group_tools:multiple_admin:group_tool_option"), false);
				
				// register permissions check hook
				register_plugin_hook("permissions_check", "group", "group_tools_multiple_admin_can_edit_hook");
				
				// register on group leave
				register_elgg_event_handler("leave", "group", "group_tools_multiple_admin_group_leave");
			}
			
			// register group activity widget
			add_widget_type("group_river_widget", elgg_echo("widgets:group_river_widget:title"), elgg_echo("widgets:group_river_widget:description"), "dashboard,profile,index,groups", true);
			
			// register group members widget
			add_widget_type("group_members", elgg_echo("widgets:group_members:title"), elgg_echo("widgets:group_members:description"), "groups", false);
			if(is_callable("add_widget_title_link")){
				add_widget_title_link("group_members", "[BASEURL]pg/groups/memberlist/[GUID]");
			}
			
			// register groups invitations widget
			add_widget_type("group_invitations", elgg_echo("widgets:group_invitations:title"), elgg_echo("widgets:group_invitations:description"), "index,dashboard", false);
			if(is_callable("add_widget_title_link")){
				add_widget_title_link("group_invitations", "[BASEURL]pg/groups/invitations/[USERNAME]");
			}
			
			// group invitation
			register_action("groups/invite", false, dirname(__FILE__) . "/actions/groups/invite.php");
			register_action("groups/joinrequest", false, dirname(__FILE__) . "/actions/groups/joinrequest.php");
		}
	}
	
	function group_tools_pagesetup(){
		global $CONFIG;
		
		$user = get_loggedin_user();
		$page_owner = page_owner_entity();
		$context = get_context();
		
		if(($context == "groups") && ($page_owner instanceof ElggGroup)){
			// replace submenu
			group_tools_replace_submenu();
			
			if(!empty($user)){
				// extend profile actions
				elgg_extend_view("profile/menu/actions", "group_tools/profile_actions");
				
				// check for admin transfer
				$admin_transfer = get_plugin_setting("admin_transfer", "group_tools");
				
				if(($admin_transfer == "admin") && $user->isAdmin()){
					elgg_extend_view("forms/groups/edit", "group_tools/forms/admin_transfer", 400);
				} elseif(($admin_transfer == "owner") && (($page_owner->getOwner() == $user->getGUID()) || $user->isAdmin())){
					elgg_extend_view("forms/groups/edit", "group_tools/forms/admin_transfer", 400);
				}
				
				// check multiple admin
				if(get_plugin_setting("multiple_admin", "group_tools") == "yes"){
					// extend group members sidebar list
					elgg_extend_view("groups/members", "group_tools/group_admins", 400);
					
					// remove group tool options for group admins
					if(($page_owner->getOwner() != $user->getGUID()) && !$user->isAdmin()){
						remove_group_tool_option("group_multiple_admin_allow");
					}
				}
				
				// invitation management
				if($page_owner->canEdit()){
					$request_options = array(
						"type" => "user",
						"relationship" => "membership_request", 
						"relationship_guid" => $page_owner->getGUID(), 
						"inverse_relationship" => true, 
						"count" => true
					);
					if($requests = elgg_get_entities_from_relationship($request_options)){
						$postfix = " [" . $requests . "]";
					}
					add_submenu_item(elgg_echo("group_tools:menu:membership") . $postfix, $CONFIG->wwwroot . "pg/groups/membershipreq/" . $page_owner->getGUID(), '1groupsactions');
				}
				
				// group mail options
				if ($page_owner->canEdit() && (get_plugin_setting("mail", "group_tools") == "yes")) {
					add_submenu_item(elgg_echo("group_tools:menu:mail"), $CONFIG->wwwroot . "pg/groups/mail/" . $page_owner->getGUID(), "1groupsactions");
				}
			}	
		}
		
	}
	
	function group_tools_multiple_admin_can_edit_hook($hook, $type, $return_value, $params){
		$result = $return_value;
		
		if(!empty($params) && is_array($params) && !$result){
			if(array_key_exists("entity", $params) && array_key_exists("user", $params)){
				$entity = $params["entity"];
				$user = $params["user"];
				
				if(($entity instanceof ElggGroup) && ($user instanceof ElggUser)){
					if($entity->isMember($user) && check_entity_relationship($user->getGUID(), "group_admin", $entity->getGUID())){
						$result = true;
					}
				}
			}
		}
		
		return $result;
	}
	
	function group_tools_multiple_admin_group_leave($event, $type, $params){
		
		if(!empty($params) && is_array($params)){
			if(array_key_exists("group", $params) && array_key_exists("user", $params)){
				$entity = $params["group"];
				$user = $params["user"];
				
				if(($entity instanceof ElggGroup) && ($user instanceof ElggUser)){
					if(check_entity_relationship($user->getGUID(), "group_admin", $entity->getGUID())){
						return remove_entity_relationship($user->getGUID(), "group_admin", $entity->getGUID());
					}
				}
			}
		}
	}

	// default elgg event handlers
	register_elgg_event_handler("init", "system", "group_tools_init");
	register_elgg_event_handler("pagesetup", "system", "group_tools_pagesetup");

	// actions
	register_action("group_tools/admin_transfer", false, dirname(__FILE__) . "/actions/admin_transfer.php");
	register_action("group_tools/toggle_admin", false, dirname(__FILE__) . "/actions/toggle_admin.php");
	register_action("group_tools/kick", false, dirname(__FILE__) . "/actions/kick.php");
	register_action("group_tools/mail", false, dirname(__FILE__) . "/actions/mail.php");
	register_action("groups/email_invitation", false, dirname(__FILE__) . "/actions/groups/email_invitation.php");
	
?>