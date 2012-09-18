<?php
$WEB_ROOT_FS = realpath(dirname(__FILE__));
$BASE_URL = dirname($_SERVER["PHP_SELF"]);

global $WEB_ROOT_FS, $BASE_URL, $data_directory, $db_type, $db_name, $db_username, $db_password;

if(file_exists("./config.php")) require_once("./config.php");
else if(file_exists("/etc/gwvpmini/config.php")) require_once("/etc/gwvpmini/config.php");
else $noconfig = true;

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

gwvpmini_goWeb();

/*
echo "<pre>";
print_r($_SERVER);
print_r($_REQUEST);
print_r($_SESSION);
echo "</pre>";
*/
?>