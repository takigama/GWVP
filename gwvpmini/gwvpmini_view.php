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
			if($qspl[0] == "repoaddreader") {
				return "gwvpmini_AddRepoReader";
			}
			if($qspl[0] == "repoaddcontrib") {
				return "gwvpmini_AddRepoContributor";
			}
			if($qspl[0] == "reporemovereaders") {
				return "gwvpmini_RemoveRepoReader";
			}
			if($qspl[0] == "reporemovecontribs") {
				return "gwvpmini_RemoveRepoContributor";
			}
			if($qspl[0] == "repoupdatedesc") {
				return "gwvpmini_RepoUpdateDescription";
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

	$bperms_f = gwvpmini_GetRepoPerms(gwvpmini_GetRepoId($repo_view_call));
	$bperms = $bperms_f["b"];
	
	$owner_view = false;
	
	if($_SERVER["SERVER_PORT"] == 443) $proto="https://";
	else $proto = "http://";
	$sname = $_SERVER["SERVER_NAME"];
	
	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);
	$editdesc = preg_replace("/\<br\>/", "\n", $desc);
	
	$rid = gwvpmini_GetRepoId($repo_view_call);
	
	$owner_name = $owner["username"];
	
	// TODO: fix this so that if user has no read access to repo, they cant see it
	if(isset($_SESSION["id"])) {
		if($owner["id"] == $_SESSION["id"]) {
			$owner_view = true;
		} else if ($bperms != "r") {
			// check user level perms
			$perm = gwvpmini_GetRepoPerm($rid, $_SESSION["id"]);
			if($perm < 1) {
				header("Location: $BASE_URL");
				return;
			}
		}
	} else {
		if($bperms != "a") {
			header("Location: $BASE_URL");
			return;
		}
	}
	
	
	error_log("STUFF:".print_r($owner,true));
	if($bperms != "a") $login = $_SESSION["username"].":password@";
	else $login = "";
	$cloneurl = "git clone $proto$login$sname$BASE_URL/git/$repo_view_call.git";
	echo "<textarea rows=1 cols=".strlen($cloneurl).">$cloneurl</textarea><br>";
	
	if($owner_view) $owner_extra = " (YOU)";
	else $owner_extra = "";
	
	echo "<h2>".get_gravatar($owner["email"], 30, 'mm', 'g', true)."$repo_view_call - $owner_name$owner_extra</h2>";
	if(!$owner_view) echo "<b>$desc</b><br>";
	
	if($owner_view) {
		echo "<form method=\"post\" action=\"$BASE_URL/repoupdatedesc/$repo_view_call\">";
		echo "<h3>Description<h3><textarea name=\"desc\" cols=\"120\" rows=\"5\">$editdesc</textarea><br><input type=\"submit\" name=\"Update\" value=\"Update\">";
		echo "</form><br>";
		
		

		
		$anyo = "";
		$regd = "";
		$expl = "";
		if($bperms == "a") $anyo = " selected";
		if($bperms == "r") $regd = " selected";
		if($bperms == "x") $expl = " selected";
		
		error_log("BPERMS: $bperms");
		
		if($bperms == "x") $cspan = 3;
		else $cspan = 2;
		
		echo "<table border=\"1\"><tr valign=\"top\"><tr><th colspan=\"$cspan\">Permissions</th></tr><td>";
		echo "<form method=\"post\" action=\"$BASE_URL/updaterepobaseperms/$repo_view_call\">";
		echo "<select name=\"base_perms\">";
		echo "<option value=\"a\"$anyo>Anyone can read</option>";
		echo "<option value=\"r\"$regd>Only registered users can read</option>";
		echo "<option value=\"x\"$expl>Explicit read permissions</option>";
		echo "</select>";
		echo "<input type=\"submit\" name=\"Set\" value=\"Set\">";
		echo "</form>";
		if($bperms == "x") {
			echo "</td><td><b>Readers</b><br>";
			echo "<form method=\"post\" action=\"$BASE_URL/reporemovereaders/$repo_view_call\">";
			$nl = 0;
			foreach($bperms_f as $key=>$val) {
				if($val == 1) {
					$dets = gwvpmini_getUser(null, null, $key);
					echo get_gravatar($dets["email"], 18, 'mm', 'g', true)." <input type=\"checkbox\" name=\"$key\"> ".$dets["username"]."<br>";
					$nl = 1;
				}
			}
			if($nl==1) echo "<input type=\"submit\" name=\"remove\" value=\"Remove Selected\">";
			echo "</form>";
			echo "<form method=\"post\" action=\"$BASE_URL/repoaddreader/$repo_view_call\">";
			echo "<input type=\"text\" name=\"readerusername\"> <input type=\"submit\" name=\"Add\" value=\"Add\">";
			echo "</form><br>";
		}
		
		echo "</td><td><b>Contributors</b><br>";
		echo "<form method=\"post\" action=\"$BASE_URL/reporemovecontribs/$repo_view_call\">";
		$nl = 0;
		foreach($bperms_f as $key=>$val) {
			if($val == 2) {
				$dets = gwvpmini_getUser(null, null, $key);
				echo get_gravatar($dets["email"], 18, 'mm', 'g', true)." <input type=\"checkbox\" name=\"$key\"> ".$dets["username"]."<br>";
				$nl = 1;
			}
		}
		if($nl==1) echo "<input type=\"submit\" name=\"remove\" value=\"Remove Selected\">";
		echo "</form>";
		
		echo "<form method=\"post\" action=\"$BASE_URL/repoaddcontrib/$repo_view_call\">";
		echo "<input type=\"text\" name=\"contribusername\"> <input type=\"submit\" name=\"Add\" value=\"Add\">";
		echo "</form><br>";
		echo "</td></tr></table>";
		
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

function gwvpmini_AddRepoReader()
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
		// TODO: btw, this makes no sense
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
	if(!$owner_view) {
		gwvpmini_SendMessage("error", "failure updating permission for repo");
		error_log("PLOOP: attempt to update from non-owner");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	$auid = gwvpmini_GetUserId($_REQUEST["readerusername"]);
	
	if($auid == $_SESSION["id"]) {
		gwvpmini_SendMessage("error", "You cannot add yourself as a reader as you already own the repo");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	if($auid > 0)  {
		$rid = gwvpmini_GetRepoId($repo_view_call);
		
		gwvpmini_ChangeRepoPerm($rid, $auid, 1);
		gwvpmini_SendMessage("info", "Added user ".$_REQUEST["readerusername"]." as a reader");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	} else {
		gwvpmini_SendMessage("error", "Couldnt find user with username of ".$_REQUEST["readerusername"]);
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	
}

function gwvpmini_AddRepoContributor()
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
		// TODO: btw, this makes no sense
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
	if(!$owner_view) {
		gwvpmini_SendMessage("error", "failure updating permission for repo");
		error_log("PLOOP: attempt to update from non-owner");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	$auid = gwvpmini_GetUserId($_REQUEST["contribusername"]);
	
	if($auid == $_SESSION["id"]) {
		gwvpmini_SendMessage("error", "You cannot add yourself as a contributor as you already own the repo");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	if($auid > 0)  {
		$rid = gwvpmini_GetRepoId($repo_view_call);
	
		gwvpmini_ChangeRepoPerm($rid, $auid, 2);
		gwvpmini_SendMessage("info", "Added user ".$_REQUEST["contribusername"]." as a contributor");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	} else {
		gwvpmini_SendMessage("error", "Couldnt find user with username of ".$_REQUEST["contribusername"]);
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
}

function gwvpmini_RemoveRepoContributor()
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
		// TODO: btw, this makes no sense
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	
	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);
	
	$owner_name = $owner["username"];
	
	$owner_view = false;
	if(isset($_SESSION["id"])) {
		if($owner["id"] == $_SESSION["id"]) {
			$owner_view = true;
		}
	}
	if(!$owner_view) {
		gwvpmini_SendMessage("error", "failure updating permission for repo");
		error_log("PLOOP: attempt to update from non-owner");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	$rid = gwvpmini_GetRepoId($repo_view_call);
	
	$bperms_f = gwvpmini_GetRepoPerms($rid);
	
	foreach($bperms_f as $key=>$val) {
		if($val == 2) {
			if(isset($_REQUEST["$key"])) {
				gwvpmini_ChangeRepoPerm($rid, $key, 0);
			}
		}
	}
	
	gwvpmini_SendMessage("info", "Repo permissions updated");
	header("Location: $BASE_URL/view/$repo_view_call");
	return;
	
}


function gwvpmini_RemoveRepoReader()
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
		// TODO: btw, this makes no sense
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}


	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);

	$owner_name = $owner["username"];

	$owner_view = false;
	if(isset($_SESSION["id"])) {
		if($owner["id"] == $_SESSION["id"]) {
			$owner_view = true;
		}
	}
	if(!$owner_view) {
		gwvpmini_SendMessage("error", "failure updating permission for repo");
		error_log("PLOOP: attempt to update from non-owner");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}

	$rid = gwvpmini_GetRepoId($repo_view_call);

	$bperms_f = gwvpmini_GetRepoPerms($rid);

	foreach($bperms_f as $key=>$val) {
		if($val == 1) {
			if(isset($_REQUEST["$key"])) {
				gwvpmini_ChangeRepoPerm($rid, $key, 0);
			}
		}
	}

	gwvpmini_SendMessage("info", "Repo permissions updated");
	header("Location: $BASE_URL/view/$repo_view_call");
	return;

}

function gwvpmini_RepoUpdateDescription()
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
		// TODO: btw, this makes no sense
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	
	$owner = gwvpmini_GetRepoOwnerDetailsFromName($repo_view_call);
	$desc = gwvpmini_GetRepoDescFromName($repo_view_call);
	
	$owner_name = $owner["username"];
	
	$owner_view = false;
	if(isset($_SESSION["id"])) {
		if($owner["id"] == $_SESSION["id"]) {
			$owner_view = true;
		}
	}
	if(!$owner_view) {
		gwvpmini_SendMessage("error", "failure updating description for repo");
		error_log("PLOOP: attempt to update from non-owner");
		header("Location: $BASE_URL/view/$repo_view_call");
		return;
	}
	
	$rid = gwvpmini_GetRepoId($repo_view_call);
	
	gwvpmini_UpdateRepoDescription($rid, $_REQUEST["desc"]);
		
	gwvpmini_SendMessage("info", "Repo description updated");
	header("Location: $BASE_URL/view/$repo_view_call");
	return;
}

?>