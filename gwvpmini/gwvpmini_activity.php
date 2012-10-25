<?php
global $HOME_PAGE_PROVIDERS;


$HOME_PAGE_PROVIDERS["10activity"] = "gwvpmini_HomePageActivityLog";


if($IS_WEB_REQUEST) {
	$reg = gwvpmini_getConfigVal("activityloglength");
	
	$activity_log_length = false;
	if($reg == null) {
		gwvpmini_setConfigVal("activityloglength", "100");
	} else if($reg == 1) {
		$activityloglength = true;
	} else {
		$activityloglength = false;
	}
	
	global $activity_log_length;
}	


// this will add a repo activity (a commit for eg)
// and auto populate the field in the activity
// log
function gwvpmini_AddCommitActivityForRepo($reponame, $byusername, $commitid, $desc, $branch)
{
	// gwvpmini_AddActivityLog($type, $userid, $repoid, $commitid, $commitlog, $visibleto="a")
	$rid = gwvpmini_GetRepoId($reponame);
	$uid = gwvpmini_GetUserId($byusername);
	
	if($rid < 1 || $uid < 1) return false;
	
	$vis = gwvpmini_GetVisibilityForRepo($rid);
	
	gwvpmini_AddActivityLog("commit", $uid, $rid, "$branch:$commitid", $desc, $vis);
}

function gwvpmini_AddRefActivityForRepo($reponame, $byusername, $branchname, $acttype="branch")
{
	$rid = gwvpmini_GetRepoId($reponame);
	$uid = gwvpmini_GetUserId($byusername);
	
	if($rid < 1 || $uid < 1) return false;
	
	$vis = gwvpmini_GetVisibilityForRepo($rid);
	
	gwvpmini_AddActivityLog("refs", $uid, $rid, "$acttype:$branchname", "", $vis);
}

function gwvpmini_GetVisibilityForRepo($repoid)
{
	$perms = gwvpmini_GetRepoPerms($repoid);
	$dets = gwvpmini_getRepo($repoid);
	$oid = $dets["ownerid"];
	
	if($perms["b"] == "a") return "a";
	if($perms["b"] == "r") return "r";
	
	$st = ":$oid:";
	foreach($perms as $key => $val) {
		if($key!="b") $st .= "$key:";
	}
	return $st;
}

// gets the activity log as it would be viewed by
// the user id of "$forid"
function gwvpmini_HomePageActivityLog()
{
	global $BASE_URL;
	
	$id = -1;
	if(isset($_SESSION["id"])) $id = $_SESSION["id"];
	
	if($id < 0) {
		$ents = gwvpmini_GetActivityLog();
	} else { 
		$ents = gwvpmini_GetActivityLog(20, $id);
	}
	
	echo "<h2>News</h2>";
	echo "<table border=\"1\">";
	if($ents != null) foreach($ents as $vals) {
		/*
		 * 		$ret[$nent]["type"] = $vals["activity_type"];
		$ret[$nent]["date"] = $vals["activity_date"];
		$ret[$nent]["userid"] = $vals["activity_user"];
		$ret[$nent]["repoid"] = $vals["activity_repo"];
		$ret[$nent]["commitid"] = $vals["activity_commitid"];
		$ret[$nent]["commitlog"] = $vals["activitiy_commitlog"];

		 */
		$type = $vals["type"];
		//$rest = $vals["date"].", ".$vals["userid"].", ".$vals["repoid"].", ".$vals["commitid"].", ".$vals["commitlog"];
		
		if($vals["type"] == "commit") {
			$udets = gwvpmini_getUser(null, null, $vals["userid"]);
			$rdets = gwvpmini_getRepo(null, null, $vals["repoid"]);
			$reponame = $rdets["name"];
			$uname = $udets["username"];
			$fname = $udets["fullname"];
			$br_spl = explode(":", $vals["commitid"]);
			$branch = $br_spl[0];
			$cid = $br_spl[1];
			$compressedcid = gwvpmini_CompressCommitId($cid);
			$log = $vals["commitlog"];
			$tdiff = gwvpmini_TimeDiffText($vals["date"]);
			$col1 = "<font size=\"+1\"><a href=\"$BASE_URL/view/$reponame\">$reponame</a></font><br>".gwvpmini_HtmlGravatar($udets["email"], 30, "<br>")."<a href=\"$BASE_URL/user/$uname\">$uname</a>";
			$col2 = $tdiff."<br>Commited change <b>$compressedcid</b><br><table border=\"1\"><tr><td bgcolor=\"#eeeeee\"><pre>$log</pre></td></tr></table><br>";
		} else if($vals["type"] == "refs") {
			$udets = gwvpmini_getUser(null, null, $vals["userid"]);
			$rdets = gwvpmini_getRepo(null, null, $vals["repoid"]);
			$reponame = $rdets["name"];
			$uname = $udets["username"];
			$fname = $udets["fullname"];
			$tdiff = gwvpmini_TimeDiffText($vals["date"]);
			$tp_spl = explode(":", $vals["commitid"]);
			$col1 = "<font size=\"+1\"><a href=\"$BASE_URL/view/$reponame\">$reponame</a></font><br>".gwvpmini_HtmlGravatar($udets["email"], 30, "<br>")."<a href=\"$BASE_URL/user/$uname\">$uname</a>";
			if($tp_spl[0] == "tagcreate") {
				$colapp = "$tdiff<br>Created Tag <b>".$tp_spl[1]."</b>";
			} else if($tp_spl[0] == "branchcreate") {
				$colapp = "$tdiff<br>Created Branch <b>".$tp_spl[1]."</b>";
			} else {
				$colapp = "$tdiff<br>Performed some unknown action.";
			}
			$col2 = $colapp;
			
		}
		
		echo "<tr><td>$col1</td><td>$col2</td></tr>";
	}
	echo "</table>";
}

function gwvpmini_TimeDiffText($time)
{
	$tdiff = time() - $time;
	
	if($tdiff < 10) return "Now";
	if($tdiff < 60) return "$tdiff Seconds Ago";
	if($tdiff < 3600) return "".(int)($tdiff/60)." Minutes Ago";
	if($tdiff < 86400) return "".(int)($tdiff/3600)." Hours Ago";
	if($tdiff < 2592000) {
		return "".(int)($tdiff/86400)." Days Ago";
	}
	if($tdiff < 31536000) {
		$months = (int)($tdiff/2592000);
		$days = (int)(($tdiff%2592000)/86400);
		
		$txt = "$months Month";
		if($months > 1) $txt .= "s";
		if($days > 0) $txt .= " and $days Day";
		if($days > 1) $txt .= "s";
		return $txt." ago";
	}
	
	$years = (int)($tdiff/31536000);
	$months = (int)(($tdiff%31536000)/2592000);
	$txt = "$years Year";
	if($years > 1) $txt .= "s";
	if($months > 0) $txt .= " and $months Month";
	if($months > 1) $txt .= "s";
	
	return $txt." ago";
	
}

?>