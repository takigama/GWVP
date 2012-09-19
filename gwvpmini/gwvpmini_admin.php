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

function gwvpmini_AdminMainPage()
{
	gwvpmini_goMainPage("gwvpmini_AdminMainPageBody");
}

function gwvpmini_AdminMainPageBody()
{
	global $BASE_URL;
	
	$totalusers = gwvpmini_GetNUsers();
	echo "<table><tr valign=\"top\"><td>";
	echo "<h2>Users - $totalusers</h2>";
	echo "<table border=\"1\">";
	echo "<tr><th>Username</th><th>Email Address</th><th>Full Name</th><th>Description</th><th>Control</th></tr>";
	foreach(gwvpmini_GetUsers() as $key => $val) {
		$id = $key;
		$un = $val["username"];
		$em = $val["email"];
		$fn = $val["fullname"];
		$ds = $val["desc"];
		echo "<tr><td>$un</td><td>$em</td><td>$fn</td><td>$ds</td><td><a href=\"$BASE_URL/admin/removeuser&id=$id\">Remove</a> <a href=\"$BASE_URL/admin/disableuser&id=$id\">Disable</a></td></tr>";
	}
	echo "</table>";
	echo "</td><td>";
	echo "<h3>Create User</h3>";
	echo "<form method=\"post\" action=\"$BASE_URL/admin/user/create\">";
	echo "<table border=\"1\">";
	echo "<tr><th>Username</th><td><input type=\"text\" name=\"username\"></td></tr>";
	echo "<tr><th>Password</th><td><input type=\"text\" name=\"password\"></td></tr>";
	echo "<tr><th>Confirm Password</th><td><input type=\"text\" name=\"confpassword\"></td></tr>";
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
?>