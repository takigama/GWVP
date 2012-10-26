<?php

// this config file is going to reduce down to just db connectivity - thats all
// all other config will be kept in the db, but not just yet

// the config file, this is as exciting as it gets really
// no longer valid here $repo_base = "/tmp/";
$data_directory = "$WEB_ROOT_FS/../data";
$db_type = "sqlite"; // could be mysql or pgsql - but not yet
$db_name = "$data_directory/gwvpmini.db"; // just a file for sqlite, for anything else is a pdo url without driver, i.e. host=localhost;dbname=whatever;user=asdf;password=asdf
$db_username = "";
$db_password = "";

$cmd_line_tool = realpath("../bin/gwvpminicmdtool.php");

$git_backend_cmd = "/usr/lib/git-core/git-http-backend";
$git_cli_cmd = "/usr/bin/git";
$php_cli_cmd = "/usr/bin/php";

//error_log("included config file");
?>