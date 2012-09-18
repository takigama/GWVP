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
					if($qspl[1] == "create") {
						return "gwvpmini_RepoCreate";
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
	
	echo "<h2>Users</h2>";
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
}

?>