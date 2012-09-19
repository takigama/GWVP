<?php

if(gwvpmini_isLoggedIn()) if(gwvpmini_isUserAdmin()) {
	$MENU_ITEMS["20repos"]["text"] = "Administration";
	$MENU_ITEMS["20repos"]["link"] = "$BASE_URL/admin";
	$CALL_ME_FUNCTIONS["admin"] = "gwvpmini_AdminCallMe";
}



function gwvpmini_AdminCallMe()
{

	error_log("in admin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "admin") {
				if(isset($qspl[1])) {
					if($qspl[1] == "user") {
						return "gwvpmini_AdminUserCreate";
					}
					if($qspl[1] == "changereg") {
						return "gwvpmini_ChangeRegistration";
					}
					if($qspl[1] == "changeconfirm") {
						return "gwvpmini_ChangeRegConfig";
					}
					if($qspl[1] == "changefromemail") {
						return "gwvpmini_ChangeFromAddress";
					}
					if($qspl[1] == "removeuser") {
						return "gwvpmini_RemoveUserPage";
					}
					if($qspl[1] == "confremoveuser") {
						return "gwvpmini_ConfRemoveUser";
					}
				} else {
					error_log("i got here, where next?");
					return "gwvpmini_AdminMainPage";
				}
			} else return false;
		}
		else return false;
	}

	return false;
}

function gwvpmini_RemoveUserPage()
{
	gwvpmini_goMainPage("gwvpmini_RemoveUserPageBody");
	
}

function gwvpmini_AdminMainPage()
{
	gwvpmini_goMainPage("gwvpmini_AdminMainPageBody");
}

function gwvpmini_AdminMainPageBody()
{
	global $BASE_URL;
	global $can_register, $reg_reqs_confirm, $confirm_from_address;
	
	if($can_register) {
		$register = "Registration Enabled (<a href=\"$BASE_URL/admin/changereg\">Disable</a>)";
	} else {
		$register = "Registration Disabled (<a href=\"$BASE_URL/admin/changereg\">Enable</a>)";
	}
	
	if($reg_reqs_confirm) {
		$regconfirm = "Registration Requires Confirmation (<a href=\"$BASE_URL/admin/changeconfirm\">Disable</a>)";
	} else {
		$regconfirm = "Registration Doesnt Require Confirmation (<a href=\"$BASE_URL/admin/changeconfirm\">Enable</a>)";
	}
	$totalusers = gwvpmini_GetNUsers();
	echo "<table><tr valign=\"top\"><td>";
	echo "<h2>Users - $totalusers</h2>";
	echo "$register<br>$regconfirm<br>";
	echo "<form method=\"post\" action=\"$BASE_URL/admin/changefromemail\">";
	echo "Address emails are sent from <input type=\"text\" name=\"fromemail\" value=\"$confirm_from_address\"><input type=\"submit\" name=\"Update\" value=\"Update\"><br>";
	echo "</form>";	

	echo "<table border=\"1\">";
	echo "<tr><th>Username</th><th>Email Address</th><th>Full Name</th><th>Description</th><th>Status</th><th>Control</th></tr>";
	foreach(gwvpmini_GetUsers() as $key => $val) {
		$id = $key;
		$un = $val["username"];
		$em = $val["email"];
		$fn = $val["fullname"];
		$ds = $val["desc"];
		$st_t = $val["status"];
		$st_l = $val["level"];
		
		$level = "WTF";
		if($st_l == 0) $level = "User";
		if($st_l == 1) $level = "Admin";
		
		$status = "";
		if($st_t[0] == "1") {
			$status = ", disabled";
		}
		if($st_t[0] == "2") {
			$vl = explode(":", $st_t);
			error_log("VL: ".print_r($vl, true));
			$status = " Awaiting Confirmation (<a href=\"$BASE_URL/register/confirmreg/".$vl[1]."\">Confirm</a>)";
		}
		
		$st = "$level$status";
		
		$unlval = "<a href=\"$BASE_URL/user/$un\">$un</a>";
		echo "<tr><td>$unlval</td><td>$em</td><td>$fn</td><td>$ds</td><td>$st</td><td><a href=\"$BASE_URL/admin/removeuser/$id\">Remove</a> <a href=\"$BASE_URL/admin/disableuser&id=$id\">Disable</a></td></tr>";
	}
	
	
	echo "</table>";
	echo "</td><td>";
	echo "<h3>Create User</h3>";
	echo "<form method=\"post\" action=\"$BASE_URL/admin/user/create\">";
	echo "<table border=\"1\">";
	echo "<tr><th>Username</th><td><input type=\"text\" name=\"username\"></td></tr>";
	echo "<tr><th>Password</th><td><input type=\"password\" name=\"password\"></td></tr>";
	echo "<tr><th>Confirm Password</th><td><input type=\"password\" name=\"confpassword\"></td></tr>";
	echo "<tr><th>Full Name</th><td><input type=\"text\" name=\"fullname\"></td></tr>";
	echo "<tr><th>Description</th><td><input type=\"text\" name=\"desc\"></td></tr>";
	echo "<tr><th>Email</th><td><input type=\"text\" name=\"email\"></td></tr>";
	echo "<tr><th>Confirm Email</th><td><input type=\"text\" name=\"confemail\"></td></tr>";
	echo "<tr><th>Admin?</th><td><input type=\"checkbox\" name=\"isadmin\"></td></tr>";
	echo "<tr><td colspan=\"2\"><input type=\"submit\" name=\"Add\" value=\"Add\"></td></tr>";
	echo "</table>";
	echo "</form>";
	echo "</td></tr></table>";
	
	$totalrepos = gwvpmini_GetNRepos();
	echo "<h2>Repo's - $totalrepos</h2>";
	echo "<table border=\"1\">";
	echo "<tr><th>Repo Name</th><th>Repo Desc</th><th>Owner</th><th>Control</th></tr>";
	foreach(gwvpmini_GetRepos() as $key => $val) {
		$id = $key;
		$rn = $val["name"];
		$ds = $val["desc"];
		$ow = $val["owner"];
		echo "<tr><td><a href=\"$BASE_URL/view/$rn\">$rn</a></td><td>$ds</td><td>$ow</td><td><a href=\"$BASE_URL/admin/removeuser&id=$id\">Remove</a> <a href=\"$BASE_URL/admin/disableuser&id=$id\">Disable</a></td></tr>";
	}
	echo "</table>";
}


function gwvpmini_AdminUserCreate()
{
	global $BASE_URL;
	
	$name = $_REQUEST["username"];
	$pass1 = $_REQUEST["password"];
	$pass2 = $_REQUEST["confpassword"];
	$fname = $_REQUEST["fullname"];
	$desc = $_REQUEST["desc"];
	$email1 = $_REQUEST["email"];
	$email2 = $_REQUEST["confemail"];
	if(isset($_REQUEST["isadmin"])) $level = 1;
	else $level = 0;
	
	$id = gwvpmini_GetUserId($name);
	
	if(!$id) {
		if($pass1 != $pass2) {
			gwvpmini_SendMessage("error", "Passwords dont match");
			header("Location: $BASE_URL/admin");
			return;
		}
		if($email1 != $email2) {
			gwvpmini_SendMessage("error", "Email Addresses dont match");
			header("Location: $BASE_URL/admin");
			return;
		}
		
		gwvpmini_AddUser($name, $pass1, $fname, $email1, $desc, $level, 0);
		gwvpmini_SendMessage("info", "User $fname created");
	} else {
		gwvpmini_SendMessage("error", "User $name already exists, cant create");
	}
	
	header("Location: $BASE_URL/admin");
	return;
	
}


function gwvpmini_ChangeRegistration()
{
	global $can_register, $BASE_URL;
	
	if($can_register) {
		gwvpmini_setConfigVal("canregister", "0");
		gwvpmini_SendMessage("info", "Registration disabled");
	} else {
		gwvpmini_setConfigVal("canregister", "1");
		gwvpmini_SendMessage("info", "Registration enabled");
	}
	
	header("Location: $BASE_URL/admin");
}


function gwvpmini_ChangeRegConfig()
{
	global $reg_reqs_confirm, $BASE_URL;
	
	if($reg_reqs_confirm) {
		gwvpmini_setConfigVal("registerrequiresconfirm", "0");
		gwvpmini_SendMessage("info", "Registration Confirmation disabled");
	} else {
		gwvpmini_setConfigVal("registerrequiresconfirm", "1");
		gwvpmini_SendMessage("info", "Registration Confirmation enabled");
	}
	
	header("Location: $BASE_URL/admin");
}

function gwvpmini_ChangeFromAddress()
{
	global $BASE_URL;
	
	$newfrom = $_REQUEST["fromemail"];
	
	gwvpmini_setConfigVal("eamilfromaddress", "$newfrom");
	gwvpmini_SendMessage("info", "Email from address updated to \"$newfrom\"");
	
	header("Location: $BASE_URL/admin");
	
}

function gwvpmini_RemoveUserPageBody()
{
	global $BASE_URL;
	
	$uid = -1;
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[2])) {
			$uid = $qspl[2];
		}
	}
	
	if($uid != -1) {
		$details = gwvpmini_GetUsers($uid, 1);
		$username = $details[$uid]["username"];
		$fullname = $details[$uid]["fullname"];
		$email = $details[$uid]["email"];
		$desc = $details[$uid]["desc"];
		
		error_log("user dets:".print_r($details, true));
		
		echo "<h2>Remove User?</h2>";
		echo "Are you sure you wish to remove the user, $username ($uid) - $fullname - $email - $desc?<br>";
		echo "<a href=\"$BASE_URL/admin/confremoveuser/$uid\">Yes</a> <a href=\"$BASE_URL/admin\">No</a><br>";
	} else {
		echo "<h2>How?</h2>";
		echo "You got here in a weird way or the uid of the user you were trying to delete is invalid<br>";
		echo "<a href=\"$BASE_URL/admin\">Go Back</a>";
	}
	
}

function gwvpmini_ConfRemoveUser()
{
	global $BASE_URL;

	$uid = -1;
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[2])) {
			$uid = $qspl[2];
		}
	}

	if($uid > 0) {
		$details = gwvpmini_getUser(null, null, $uid);
		$uname = $details["username"];
		gwvpmini_RemoveUser($uid);
		gwvpmini_SendMessage("info", "User $uname ($uid) has been removed");
	} else {
		gwvpmini_SendMessage("info", "Problem deleteing user with uid $uid");
	}

	header("Location: $BASE_URL/admin");
}

?>