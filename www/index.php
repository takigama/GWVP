<?php
// error_log("INCALLSTART ".print_r($_COOKIE, true)." -------------------- ".print_r($_SERVER,true)." ---------- ".print_r($_REQUEST, true));
$WEB_ROOT_FS = realpath(dirname(__FILE__));
$BASE_URL = dirname($_SERVER["PHP_SELF"]);

global $WEB_ROOT_FS, $BASE_URL, $data_directory, $db_type, $db_name, $db_username, $db_password, $IS_WEB_REQUEST, $cmd_line_tool;
global $git_backend_cmd, $git_cli_cmd, $php_cli_cmd, $data_directory, $cmd_line_tool;

$IS_WEB_REQUEST = true;

if(file_exists("./config.php")) require_once("./config.php");
else if(file_exists("/etc/gwvpmini/config.php")) require_once("/etc/gwvpmini/config.php");
else $noconfig = true;

if(file_exists("../gwvpmini/gwvpmini.php")) require_once("../gwvpmini/gwvpmini.php");
else if(file_exists("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php")) require_once("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php");


if(isset($noconfig)) {
	gwvpmini_goSetup();
	return;
}

// error_log("CMDLINETOOL: ".$cmd_line_tool);

// need to make this db agnostic
if(!gwvpmini_DBExists($db_name)) {
	if(!is_dir("$data_directory/repos")) mkdir("$data_directory/repos");
	
	error_log("CREATEDATABASE");
	gwvpmini_dbCreateSQLiteStructure($db_name);
	gwvpmini_setConfigVal("repodir", "$data_directory/repos");
}

// error_log("REQUEST BEGIN");
gwvpmini_goWeb();


/*echo "<pre>";
print_r($_SERVER);
print_r($_REQUEST);
print_r($_SESSION);
echo "</pre>";*/

?>