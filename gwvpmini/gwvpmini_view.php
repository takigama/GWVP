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
	
	echo "<br><h2>$repo_view_call by owner</h2>";
	echo "<b>Desc</b><br>";
	echo "<textarea rows=1 cols=100>git clone $proto$sname$BASE_URL/git/$repo_view_call.git</textarea><br>";
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
			$rs = popen("git --git-dir=$repo_base/$repo_view_call.git log --pretty=format:\"%at%n%ce%n%s\" $ids -1", "r");
			if($rs) {
				$flin1 = trim(fgets($rs));
				$flin2 = gwvpmini_emailToUserLink(trim(fgets($rs)));
				while(!feof($rs)) {
					$flin3 = fread($rs, 8192);
				}
			}
			echo "<tr><td>$flin2</td><td>$flin1</td><td>$flin3</td></tr>";
		}
		echo "</table>";
	}
	
}


?>