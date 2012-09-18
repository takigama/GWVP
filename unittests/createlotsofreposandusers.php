<?php
$WEB_ROOT_FS = realpath(dirname(__FILE__));
$BASE_URL = dirname($_SERVER["PHP_SELF"]);

global $WEB_ROOT_FS, $BASE_URL, $data_directory, $db_type, $db_name, $db_username, $db_password;

require_once("../www/config.php");

if(file_exists("../gwvpmini/gwvpmini.php")) require_once("../gwvpmini/gwvpmini.php");
else if(file_exists("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php")) require_once("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php");



if(isset($noconfig)) {
	gwvpmini_goSetup();
	return;
}

// need to make this db agnostic
if(!gwvpmini_DBExists($db_name)) {
	if(!is_dir("$data_directory/repos")) mkdir("$data_directory/repos");

	error_log("CREATEDATABASE");
	gwvpmini_dbCreateSQLiteStructure($db_name);
	gwvpmini_setConfigVal("repodir", "$data_directory/repos");
}


$nrepos = 50;
$nusers = 100;

if(isset($_REQUEST["GO"])) {
	for($i = 0; $i < $nusers; $i++) {
		$username = randomStr(rand(6,12));
		$password = "Asdf";
		$fullname = "asdf";
		$email = "Aasdf@asdf";
		$desc = "asdf";
		$level = 1;
		$status = 0;
		$uid = gwvpmini_AddUser($username, $password, $fullname, $email, $desc, $level, $status);
		echo "Created user, $username with uid of $uid<br>";
	}
	
	for($i = 0; $i < $nrepos; $i++) {
		$reponame = randomStr(rand(6,12));
		$repodesc = "desc";
		$uid = rand(10,$nusers-1);
		gwvpmini_createGitRepo($reponame, $uid, $repodesc);
		echo "Created repo, $reponame owned by $uid as $repodesc<br>";
	}
} else {
	echo "click <a href=\"?GO=GO\">Go</a> to populate";
}

function randomStr($len)
{
	$strl = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
	
	$retval = "";
	for($i=0; $i<$len; $i++) {
		$retval .= $strl[rand(0,strlen($strl)-1)];
	}
	
	return $retval;
}
?>