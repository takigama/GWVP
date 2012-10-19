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


echo "ARGS: ".print_r($argv,true);
echo "CWD: ".getcwd()."\n";

if(isset($argv["3"])) {
	switch($argv["3"]) {
		case "update":
			gwvpcmdtool_UpdateHook();
			break;
		case "pre-receive":
			gwvpcmdtool_PreReceive();
			break;
		case "backgroundclone":
			gwvpcmdtool_BackGroundClone();
			break;
		default:
			gwvpcmdtool_Usage();
	}
} else gwvpcmdtool_Usage();
return;


/*
 * remote: ARGS: Array
remote: (
remote:     [0] => /nfs/export/src/local/eclipse-workspace/gwvp-mini/bin/gwvpminicmdtool.php
remote:     [1] => asfd
remote:     [2] => admin
remote:     [3] => pre-receive
remote:     [4] => fc781c4ef5bfeae8ec01bb527db1b6ce6f65d03c
remote:     [5] => 7d45d43f04276fc9addb77ba8bf753329eab018d
remote:     [6] => refs/heads/master
remote: )
remote: ARGS: Array
remote: (
remote:     [0] => /nfs/export/src/local/eclipse-workspace/gwvp-mini/bin/gwvpminicmdtool.php
remote:     [1] => asfd
remote:     [2] => admin
remote:     [3] => pre-receive
remote:     [4] => /nfs/export/src/local/eclipse-workspace/gwvp-mini/bin/gwvpminicmdtool.php
remote:     [5] => asfd
remote:     [6] => admin
remote:     [7] => update
remote:     [8] => refs/heads/master
remote:     [9] => fc781c4ef5bfeae8ec01bb527db1b6ce6f65d03c
remote:     [10] => 7d45d43f04276fc9addb77ba8bf753329eab018d

 */


function gwvpcmdtool_Usage()
{
	global $argv;
	
	echo "Usage: ".$argv[0]." this tool should not be called directly by user\n";
}


function gwvpcmdtool_BackGroundClone()
{
	// here we parse arguments and have stuff with things and its 6am why am i doing this right now?
}

// update will log things like branch and tag creations
function gwvpcmdtool_UpdateHook()
{
	global $argv;
	//echo "got ".$argv[2].", ".$argv[3].", ".$argv[4]."\n";
	if(preg_match("/^000000+$/", $argv[5])) {
		// createion of tag or branch
		$vals = explode("/", $argv[4]);
		$type = "unknowncreate";
		if($vals[1] == "heads") $type = "branchcreate";
		if($vals[1] == "tags") $type = "tagcreate";
		
		//gwvpmini_AddRefActivityForRepo();
		gwvpmini_AddRefActivityForRepo($argv[1], $argv[2], $vals[2], $type);
		echo "REFSUP: ".$vals[2].", $type\n";
		
	}
	//gwvpmini_AddActivityLog($type, $userid, $repoid, $commitid, $commitlog)
	//gwvpmini_AddRefActivityForRepo($reponame, $byusername, $branchname, $acttype="branch")
}

// pre-receive logs all commit info
function gwvpcmdtool_PreReceive()
{
	global $argv;

	//echo "got from prereceive ".$argv[2].", ".$argv[3].", ".$argv[4]."\n";
	
	$lns = 0;
	$ref = $argv[6];

	$regspl = explode("/", $ref);
	$branch = $regspl[2];
	
	$fp = popen("git rev-list --reverse ".$argv[5]." --not --all ", "r");
	if($fp) while(!feof($fp)) {
		$line = trim(fgets($fp));
		if($line != "") {
			$cdd[$lns] = gwvpcmdtool_getCommitIdDetails($line);
			echo "FORCID $line we get \n".print_r($cdd[$lns], true);
			gwvpmini_AddCommitActivityForRepo($argv[1], $argv[2], $line, $cdd[$lns]["log"], $branch);
			$lns++;
		}
	}
	
	
	//echo "Called git rev-list --reverse ".$argv[5]." --not --all\n\n";
	//gwvpmini_AddCommitActivityForRepo($reponame, $byusername, $commitid, $desc)
}

function gwvpcmdtool_getCommitIdDetails($commitid)
{
	$rs = popen("git log --pretty=format:\"%at%n%ce%n%an%n%s\" $commitid -1 2> /dev/null", "r");
	$ret = array();
	if($rs) {
		$ret["date"] = trim(fgets($rs));
		$ret["email"] = trim(fgets($rs));
		$ret["fullname"] = trim(fgets($rs));
		$ret["log"] = "";
		while(!feof($rs)) {
			$ret["log"] .= fread($rs, 8192);
		}
	} else {
		$ret = false;
	}
	
	return $ret;
}
?>