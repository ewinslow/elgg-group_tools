<?php 
	global $CONFIG;

	$q = sanitize_string(get_input("q"));
	$current_users = sanitize_string(get_input("user_guids"));
	$limit = (int) get_input("limit", 50);
	$group_guid = (int) get_input("group_guid", 0);
	$relationship = sanitize_string(get_input("relationship", "none"));
	
	$include_self = get_input("include_self", false);
	if(!empty($include_self)){
		$include_self = true;
	}
	
	$result = "";
	
	if(($user = get_loggedin_user()) && !empty($q) && !empty($group_guid)){
		if($relationship != "email"){
			// find existing users
			$query_options = array(
				"type" => "user",
				"limit" => $limit,
				"joins" => array("JOIN {$CONFIG->dbprefix}users_entity u ON e.guid = u.guid"),
				"wheres" => array("(u.name LIKE '%{$q}%' OR u.username LIKE '%{$q}%')", "u.banned = 'no'"),
				"order_by" => "u.name asc"
			);
			
			if(!$include_self){
				if(empty($current_users)){
					$current_users = $user->getGUID();
				} else {
					$current_users .= "," . $user->getGUID();
				}
			}
			
			if(!empty($current_users)){
				$query_options["wheres"][] = "e.guid NOT IN (" . $current_users . ")";
			}
			
			if($relationship == "friends"){
				$query_options["relationship"] = "friend";
				$query_options["relationship_guid"] = $user->getGUID();
			} elseif($relationship == "site"){
				$query_options["relationship"] = "member_of_site";
				$query_options["relationship_guid"] = $CONFIG->site_guid;
				$query_options["inverse_relationship"] = true;
			}
			
			if($entities = elgg_get_entities_from_relationship($query_options)){
				foreach($entities as $entity){
					if(!check_entity_relationship($entity->getGUID(), "member", $group_guid)){
						$result .= "user|" . $entity->getGUID() . "|" . $entity->name . "|" . $entity->getIcon("tiny") . "\n";
					}	
				}
			}
		} else {
			// invite by email
			$regexpr = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/";
			if(preg_match($regexpr, $q)){
				if($users = get_user_by_email($q)){
					foreach($users as $user){
						$result .= "user|" . $user->getGUID() . "|" . $user->name . "|" . $user->getIcon("tiny") . "\n";
					}
				} else {
					$result .= "email|" . $q;
				}
			}
		}
	}
	
	echo $result;

?>