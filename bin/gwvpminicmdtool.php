<?php

$WEB_ROOT_FS = realpath(dirname(__FILE__));
$BASE_URL = "/";

global $WEB_ROOT_FS, $BASE_URL, $IS_WEB_REQUEST, $data_directory, $db_type, $db_name, $db_username, $db_password, $IS_WEB_REQUEST, $cmd_line_tool;
$IS_WEB_REQUEST = false;

if(file_exists("$WEB_ROOT_FS/../www/config.php")) require_once("$WEB_ROOT_FS/../www/config.php");
else if(file_exists("/etc/gwvpmini/config.php")) require_once("/etc/gwvpmini/config.php");
else $noconfig = true;

if(file_exists("$WEB_ROOT_FS/../gwvpmini/gwvpmini.php")) require_once("$WEB_ROOT_FS/../gwvpmini/gwvpmini.php");
else if(file_exists("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php")) require_once("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php");

if(isset($argv["1"])) {
	switch($argv["1"]) {
		case "update":
			gwvpcmdtool_UpdateHook();
			break;
		case "pre-receive":
			gwvpcmdtool_PreReceive();
			break;
		default:
			gwvpcmdtool_Usage();
	}
} else gwvpcmdtool_Usage();
return;



function gwvpcmdtool_Usage()
{
	global $argv;
	
	echo "Usage: ".$argv[0]."\n";
	echo "\tupdatehook <user> <ref> <firstupdate> <lastupdate>\n";
}


// update will log things like branch and tag creations
function gwvpcmdtool_UpdateHook()
{
	global $argv;
	//echo "got ".$argv[2].", ".$argv[3].", ".$argv[4]."\n";
	if(preg_match("/^000000+$/", $argv[3])) {
		// createion of tag or branch
		$vals = explode("/", $argv[2]);
		$type = "unknowncreate";
		if($vals == "heads") $type = "branchcreate";
		if($vals == "tags") $type = "tagcreate";
		gwvpmini_AddActivityLog("$type", "1", "1", $argv[4], "somelogs");
	}
	//gwvpmini_AddActivityLog($type, $userid, $repoid, $commitid, $commitlog)
}

// pre-receive logs all commit info
function gwvpcmdtool_PreReceive()
{
	global $argv;

	//echo "got from prereceive ".$argv[2].", ".$argv[3].", ".$argv[4]."\n";
	
	//passthru("git rev-list --reverse ".$argv[3]." --not --all ");
}
?>