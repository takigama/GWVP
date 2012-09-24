<?php
$CALL_ME_FUNCTIONS["userview"] = "gwvpmini_UserViewCallMe";

if($IS_WEB_REQUEST) {
	$reg = gwvpmini_getConfigVal("gravatarenabled");
	
	$use_gravatar = false;
	if($reg == null) {
		gwvpmini_setConfigVal("gravatarenabled", "1");
	} else if($reg == 1) {
		$use_gravatar = true;
	} else {
		$use_gravatar = false;
	}
	
	global $use_gravatar;
}


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
	global $user_view_call, $BASE_URL;
	
	
	$dets = gwvpmini_getUser($user_view_call);
	//error_log("show view of user with $user_view_call: ". print_r($dets, true));

	echo "<h2>".$dets["fullname"]."</h2><br>";
	echo gwvpmini_HtmlGravatar($dets["email"],80);
	echo "<br>";
	
	$isme = false;
	if(isset($_SESSION["id"])) {
		if($_SESSION["id"] == $dets["id"]) {
			$isme = true;
		}
	}
	
	if($isme) {
		echo "<form method=\"post\" action=\"$BASE_URL/user/updateuserdesc\">";
		echo "Your Description<br><textarea name=\"desc\" cols=\"100\" rows=\"4\">".$dets["desc"]."</textarea><br>";
		echo "<input type=\"submit\" name=\"Update\" value=\"Update\">";
		echo "</form>";
		
		echo "<h3>New Password</h3>";
		echo "<form method=\"post\" action=\"$BASE_URL/user/updateuserpassword\">";
		echo "<table><tr><td>Old Password</td><td><input type=\"password\" name=\"oldpassword\"></td></tr>";
		echo "<tr><td>New Password</td><td><input type=\"password\" name=\"newpassword1\"></td></tr>";
		echo "<tr><td>Confirm New Password</td><td><input type=\"password\" name=\"newpassword2\"></td></tr></table>";
		echo "<input type=\"submit\" name=\"Update\" value=\"Update\">";
		echo "</form>";
	} else {
		echo $dets["desc"]."<br>";
	}
}

?>