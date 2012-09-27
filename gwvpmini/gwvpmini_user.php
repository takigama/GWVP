<?php
$CALL_ME_FUNCTIONS["userview"] = "gwvpmini_UserViewCallMe";

if($IS_WEB_REQUEST) {
	$reg = gwvpmini_getConfigVal("gravatarenabled");
	
	$use_gravatar = false;
	if($reg == null) {
		// disable grav's by default
		gwvpmini_setConfigVal("gravatarenabled", "0");
	} else if($reg == 1) {
		$use_gravatar = true;
	} else {
		$use_gravatar = false;
	}
	
	global $use_gravatar;
	
	$reg = gwvpmini_getConfigVal("forcessl");

	$force_ssl = false;
	if($reg == null) {
		// dont force ssl by default
		gwvpmini_setConfigVal("forcessl", "0");
	} else if($reg == 1) {
		$force_ssl = true;
	} else {
		$force_ssl = false;
	}

	global $force_ssl;
	
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
					} else {
						if(isset($qspl[2])) {
							if($qspl[2] == "updateuserdesc") {
								return "gwvpmini_ViewUpdateUserDesc";
							}
							if($qspl[2] == "updateuserpassword") {
								return "gwvpmini_ViewUpdateUserPassword";
							}
							if($qspl[2] == "updateuseremail") {
								return "gwvpmini_ViewUpdateUserEmail";
							}
						}
						return "gwvpmini_UserViewPage";
					}
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
	
	if($isme || gwvpmini_isUserAdmin()) {
		echo "<form method=\"post\" action=\"$BASE_URL/user/$user_view_call/updateuserdesc\">";
		echo "Your Description<br><textarea name=\"desc\" cols=\"100\" rows=\"4\">".$dets["desc"]."</textarea><br>";
		echo "<input type=\"submit\" name=\"Update\" value=\"Update\">";
		echo "</form>";
		
		echo "<h3>New Password</h3>";
		echo "<form method=\"post\" action=\"$BASE_URL/user/$user_view_call/updateuserpassword\">";
		echo "<table>";
		if($isme) echo "<tr><td>Old Password</td><td><input type=\"password\" name=\"oldpassword\"></td></tr>";
		echo "<tr><td>New Password</td><td><input type=\"password\" name=\"newpassword1\"></td></tr>";
		echo "<tr><td>Confirm New Password</td><td><input type=\"password\" name=\"newpassword2\"></td></tr></table>";
		echo "<input type=\"submit\" name=\"Update\" value=\"Update\">";
		echo "</form>";
		
		echo "<h3>New Email Address</h3>";
		echo "<form method=\"post\" action=\"$BASE_URL/user/$user_view_call/updateuseremail\">";
		echo "<table><tr><td>New Email Address</td><td><input type=\"text\" name=\"newemail1\"></td></tr>";
		echo "<tr><td>Confirm New Email Address</td><td><input type=\"text\" name=\"newemail2\"></td></tr></table>";
		echo "<input type=\"submit\" name=\"Update\" value=\"Update\">";
		echo "</form>";
	} else {
		echo $dets["desc"]."<br>";
	}
}

function gwvpmini_ViewUpdateUserPassword()
{
	global $user_view_call, $BASE_URL;
	
	$newpass1 = $_REQUEST["newpassword1"];
	$newpass2 = $_REQUEST["newpassword2"];
	$oldpass = $_REQUEST["oldpassword"];
	
	$authd = gwvpmini_authUserPass($user_view_call, $oldpass);
		
	$doupdate = false;
	
	if(isset($_SESSION["username"])) if($_SESSION["username"] == $user_view_call && $authd !== false) {
		$doupdate = true;
	}
	
	if(gwvpmini_isUserAdmin()) {
		$doupdate = true;
	}
	
	
	if($newpass1 != $newpass2) {
		gwvpmini_SendMessage("error", "Password and confirmation dont match");
	} else if(!$doupdate) {
		gwvpmini_SendMessage("error", "Could not update user desc, are you logged in?");
	} else {
		// do update
		$uid = gwvpmini_GetUserId($user_view_call);
		gwvpmini_UpdateUserPassword($uid, $newpass1);
		gwvpmini_SendMessage("info", "Password Updated");
	}
	
	header("Location: $BASE_URL/user/$user_view_call");
}

function gwvpmini_ViewUpdateUserDesc()
{
	global $user_view_call, $BASE_URL;
	
	$newdesc = $_REQUEST["desc"];
	$doupdate = false;
	
	if(isset($_SESSION["username"])) if($_SESSION["username"] == $user_view_call) {
		$doupdate = true;
	}
	
	if(gwvpmini_isUserAdmin()) {
		$doupdate = true;
	}
	
	if(!$doupdate) {
		gwvpmini_SendMessage("error", "Could not update user desc, are you logged in?");
	} else {
		$uid = gwvpmini_GetUserId($user_view_call);
		gwvpmini_UpdateUserDesc($uid, $newdesc);
		gwvpmini_SendMessage("info", "Description Updated");
	}
	
	header("Location: $BASE_URL/user/$user_view_call");
	}

function gwvpmini_ViewUpdateUserEmail()
{
	global $user_view_call, $BASE_URL;
	
	$newem1 = $_REQUEST["newemail1"];
	$newem2 = $_REQUEST["newemail2"];
	$doupdate = false;
	
	if(isset($_SESSION["username"])) if($_SESSION["username"] == $user_view_call) {
		$doupdate = true;
	}
	
	if(gwvpmini_isUserAdmin()) {
		$doupdate = true;
	}
	
	if($newem1 != $newem2) {
		gwvpmini_SendMessage("error", "Email and confirmation did not match");
	} else if(!$doupdate) {
		gwvpmini_SendMessage("error", "Could not update user desc, are you logged in?");
	} else {
		$uid = gwvpmini_GetUserId($user_view_call);
		gwvpmini_UpdateUserEmail($uid, $newem1);
		gwvpmini_SendMessage("info", "Email Address Updated");
	}
	
	header("Location: $BASE_URL/user/$user_view_call");
	
}

?>