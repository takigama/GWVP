<?php

$CALL_ME_FUNCTIONS["debug"] = "gwvpmini_DebugCallMe";

$MENU_ITEMS["99debug"]["text"] = "Debug";
$MENU_ITEMS["99debug"]["link"] = "$BASE_URL/debug";


function gwvpmini_DebugCallMe()
{

	// error_log("in admin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "debug") {
				return "gwvpmini_DebugPage";
			}
		}
	}
	
	return false;
}	

function gwvpmini_DebugPage()
{
	gwvpmini_goMainPage("gwvpmini_DebugPageBody");
}

function gwvpmini_DebugPageBody()
{
	echo "Dumping perms data:";
	
	$db = gwvpmini_ConnectDB();
	
	$res = $db->query("select * from repos");
	foreach($res as $row) {
		$repo = $row["repos_name"];
		$perms = $row["repos_perms"];
		echo "<br>Repo: $repo: <pre>";
		print_r(unserialize(base64_decode($perms)));
		echo "</pre>";
	}
}
?>