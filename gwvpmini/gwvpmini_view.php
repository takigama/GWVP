<?php
global $HOME_PAGE_PROVIDERS;


$CALL_ME_FUNCTIONS["repoview"] = "gwvpmini_RepoViewCallMe";



function gwvpmini_RepoViewCallMe()
{
	global $repo_view_call;
	
	error_log("in admin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "view") {
				if(isset($qspl[1])) {
					$repo_view_call = $qspl[1];
					return "gwvpmini_RepoViewPage";
				} else return false;
			} else return false;
		}
		else return false;
	}

	return false;
	
	
}

function gwvpmini_RepoViewPage()
{
	global $repo_view_call, $MENU_ITEMS, $BASE_URL;
	
	$MENU_ITEMS["40thisrepo"]["text"] = "$repo_view_call";
	$MENU_ITEMS["40thisrepo"]["link"] = "$BASE_URL/view/$repo_view_call";
	
	gwvpmini_goMainPage("gwvpmini_RepoViewPageBody");
}

function gwvpmini_RepoViewPageBody()
{
	global $repo_view_call, $MENU_ITEMS, $BASE_URL;
	
	$repo_base = gwvpmini_getConfigVal("repodir");

	if($_SERVER["SERVER_PORT"] == 443) $proto="https://";
	else $proto = "http://";
	$sname = $_SERVER["SERVER_NAME"];
	
	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);
	
	$owner_name = $owner["username"];
	
	error_log("STUFF:".print_r($owner,true));
	$cloneurl = "git clone $proto$sname$BASE_URL/git/$repo_view_call.git";
	echo "<textarea rows=1 cols=".strlen($cloneurl).">$cloneurl</textarea><br>";
	
	echo "<h2>".get_gravatar($owner["email"], 30, 'mm', 'g', true)."$repo_view_call - $owner_name</h2>";
	echo "<b>$desc</b><br>";
	//echo "command: git log --git-dir=$repo_base/$repo_view_call.git --pretty=format:\"%H\" -10";
	$rs = popen("git --git-dir=$repo_base/$repo_view_call.git log --pretty=format:\"%H\" -10", "r");
	$commitids = array();
	$i = 0;
	if($rs) {
		while(!feof($rs)) {
			$flin = fgets($rs);
			if($flin !== false) {
				$commitids[$i] = trim($flin);
				$i++;
			}
		}
		fclose($rs);
	} else {
		echo "No commit logs yet<br>";
		$commitids = false;
	}
	
	if($commitids != false) {
		echo "<hr>Commits<br>";
		echo "<table border=\"1\">";
		echo "<tr><th>Committed By</th><th>Date</th><th>Commit Log Entry</th></tr>";
		foreach($commitids as $ids) {
			$rs = popen("git --git-dir=$repo_base/$repo_view_call.git log --pretty=format:\"%at%n%ce%n%an%n%s\" $ids -1", "r");
			if($rs) {
				$flin1 = trim(fgets($rs));
				$flin2 = trim(fgets($rs));
				$flin3 = trim(fgets($rs));
				while(!feof($rs)) {
					$flin4 = fread($rs, 8192);
				}
				$flon =  gwvpmini_emailToUserLink($flin2);
				if(!$flon) {
					$flon = "$flin3 (external)";
				}
			}
			echo "<tr><td>".get_gravatar($flin2, 18, 'mm', 'g', true)."$flon</td><td>$flin1</td><td>$flin4</td></tr>";
		}
		echo "</table>";
	}
}


?>