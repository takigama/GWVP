<?php

$WEB_ROOT_FS = realpath(dirname(__FILE__));
$BASE_URL = "/";

global $WEB_ROOT_FS, $BASE_URL;

if(file_exists("../www/config.php")) require_once("../www/config.php");
else if(file_exists("/etc/gwvpmini/config.php")) require_once("/etc/gwvpmini/config.php");
else $noconfig = true;

if(file_exists("../gwvpmini/gwvpmini.php")) require_once("../gwvpmini/gwvpmini.php");
else if(file_exists("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php")) require_once("/usr/share/gwvpmini/lib/gwvpmini/gwvpmini.php");

global $IS_WEB_REQUEST;

$IS_WEB_REQUEST = false;

?>