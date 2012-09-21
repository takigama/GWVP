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
			}
			if($qspl[0] == "updaterepobaseperms") {
				return "gwvpmini_UpdateRepoBasePerms";
			} 
			return false;
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

	$owner_view = false;
	
	if($_SERVER["SERVER_PORT"] == 443) $proto="https://";
	else $proto = "http://";
	$sname = $_SERVER["SERVER_NAME"];
	
	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);
	
	$owner_name = $owner["username"];
	
	
	if(isset($_SESSION["id"])) {
		if($owner["id"] == $_SESSION["id"]) {
			$owner_view = true;
		}
	}
	
	
	error_log("STUFF:".print_r($owner,true));
	$cloneurl = "git clone $proto$sname$BASE_URL/git/$repo_view_call.git";
	echo "<textarea rows=1 cols=".strlen($cloneurl).">$cloneurl</textarea><br>";
	
	if($owner_view) $owner_extra = " (YOU)";
	else $owner_extra = "";
	
	echo "<h2>".get_gravatar($owner["email"], 30, 'mm', 'g', true)."$repo_view_call - $owner_name$owner_extra</h2>";
	echo "<b>$desc</b><br>";
	
	if($owner_view) {
		$bperms = gwvpmini_GetRepoPerm(gwvpmini_GetRepoId($repo_view_call), "b");
		
		$anyo = "";
		$regd = "";
		$expl = "";
		if($bperms == "a") $anyo = " selected";
		if($bperms == "r") $regd = " selected";
		if($bperms == "x") $expl = " selected";
		
		error_log("BPERMS: $bperms");
		
		echo "<form method=\"post\" action=\"$BASE_URL/updaterepobaseperms/$repo_view_call\">";
		echo "Base Permissions ";
		echo "<select name=\"base_perms\">";
		echo "<option value=\"a\"$anyo>Anyone can read</option>";
		echo "<option value=\"r\"$regd>Only registered users can read</option>";
		echo "<option value=\"x\"$expl>Explicit read permissions</option>";
		echo "</select>";
		echo "<input type=\"submit\" name=\"Set\" value=\"Set\">";
		echo "</form>";
	}
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
		$commitids = false;
	}
	
	if($commitids != false) {
		echo "<hr>Commits<br>";
		echo "<table border=\"1\">";
		echo "<tr><th>Committed By</th><th>Date</th><th>Commit Log Entry</th></tr>";
		foreach($commitids as $ids) {
			$rs = popen("git --git-dir=$repo_base/$repo_view_call.git log --pretty=format:\"%at%n%ce%n%an%n%s\" $ids -1 2> /dev/null", "r");
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
	} else {
		echo "No commit information available yet<br>";
	}
}

function gwvpmini_UpdateRepoBasePerms()
{
	global $BASE_URL, $repo_view_call;
	
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		error_log("PLOOP:qview".print_r($qspl, true));
	}
	
	if(isset($qspl[1])) $repo_view_call = $qspl[1];
	else {
		error_log("PLOOP: no repo name");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	$newperms = $_REQUEST["base_perms"];
	
	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);
	
	$owner_name = $owner["username"];
	
	$owner_view = false;
	if(isset($_SESSION["id"])) {
		if($owner["id"] == $_SESSION["id"]) {
			$owner_view = true;
		}
	}
	
	$rid = gwvpmini_GetRepoId($repo_view_call);
	
	if(!$owner_view) {
		gwvpmini_SendMessage("error", "failure updating permission for repo");
		error_log("PLOOP: attempt to update from non-owner");
	} else {
		error_log("PLOOP: updateds: ".print_r($_REQUEST, true));
		gwvpmini_ChangeRepoPerm($rid, "b", $_REQUEST["base_perms"]);
		gwvpmini_SendMessage("info", "Base permissions for repo updated");
	}
	
	header("Location: $BASE_URL/view/$repo_view_call");
}

?>