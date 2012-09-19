<?php
$CALL_ME_FUNCTIONS["userview"] = "gwvpmini_UserViewCallMe";



function gwvpmini_UserViewCallMe()
{
	global $user_view_call;
	
	error_log("in admin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "user") {
				if(isset($qspl[1])) {
					$user_view_call = $qspl[1];
					if(!gwvpmini_GetUserId($user_view_call)) {
						gwvpmini_SendMessage("error", "No such user, $user_view_call");
						return false;
					}
					return "gwvpmini_UserViewPage";
				} else return false;
			} else return false;
		}
		else return false;
	}

	return false;
	
	
}

function gwvpmini_UserViewPage()
{
	global $user_view_call, $MENU_ITEMS, $BASE_URL;
	
	$MENU_ITEMS["40thisuser"]["text"] = "$user_view_call";
	$MENU_ITEMS["40thisuser"]["link"] = "$BASE_URL/user/$user_view_call";
	
	gwvpmini_goMainPage("gwvpmini_UserViewPageBody");
}

function gwvpmini_UserViewPageBody()
{
	global $user_view_call;
	
	echo "Want to see $user_view_call eh?";
}

?>