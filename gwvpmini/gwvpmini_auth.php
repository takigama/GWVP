<?php

session_start();

$CALL_ME_FUNCTIONS["auth"] = "gwvpmini_AuthCallMe";

function gwvpmini_AuthCallMe()
{

	error_log("in repoadmin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "login") {
				return "gwvpmini_AuthHandleLogin";
			} else if($qspl[0] == "logout") {
				return "gwvpmini_AuthHandleLogout";
			} else return false;
		}
		else return false;
	}

	return false;
}

function gwvpmini_AuthHandleLogout()
{
	global $BASE_URL;

	unset($_SESSION["isloggedin"]);
	unset($_SESSION["username"]);
	unset($_SESSION["fullname"]);
	unset($_SESSION["usertype"]);
	unset($_SESSION["id"]);
	
	gwvpmini_SendMessage("info", "Logged out");
	header("Location: $BASE_URL");
}


function gwvpmini_AuthHandleLogin()
{
	global $BASE_URL;
	
	$user = "";
	$pass = "";
	if(isset($_REQUEST["username"])) $user = $_REQUEST["username"];
	if(isset($_REQUEST["password"])) $pass = $_REQUEST["password"];
	
	if(gwvpmini_authUserPass($user, $pass) === false) {
		gwvpmini_SendMessage("error", "Login Failed");
		header("Location: $BASE_URL");
	} else {
		$details = gwvpmini_getUser($user);
		$_SESSION["isloggedin"] = true;
		$_SESSION["username"] = "$user";
		$_SESSION["fullname"] = $details["fullname"];
		$_SESSION["id"] = $details["id"];
		gwvpmini_SendMessage("info", "Welcome ".$details["fullname"]." you are logged in");
		header("Location: $BASE_URL");
		return true;
	}
	
	
}

function gwvpmini_SingleLineLoginForm()
{
	global $BASE_URL;

	echo "<form method=\"post\" action=\"$BASE_URL/login\">Username <input type=\"text\" name=\"username\" class=\"login\">";
	echo " Passowrd <input type=\"text\" name=\"password\" class=\"login\"><input type=\"submit\" name=\"login\" value=\"Login\" class=\"loginbutton\">";
	if(gwvpmini_IsRegistrationEnabled()) echo "<a href=\"$BASE_URL/register\">Register</a></form>";
	else echo "</form><br>";
}


function gwvpmini_IsRegistrationEnabled()
{
	return true;
}

function gwvpmini_isLoggedIn()
{
	global $_SESSION;
	
	if(isset($_SESSION)) {
		if(isset($_SESSION["username"])) {
			return true;
		}
	}
	
	return false;
}

function gwvpmini_AskForBasicAuth()
{
	error_log("SEND BASIC AUTH");
	header_remove("Pragma");
	header_remove("Cache-Control");
	header_remove("Set-Cookie");
	header_remove("Expires");
	header_remove("X-Powered-By");
	header_remove("Vary");
	
	header('HTTP/1.1 401 Unauthorized');
	header('WWW-Authenticate: Basic realm="GITRepo"');
}


function gwvpmini_checkBasicAuthLogin()
{
	$user = false;
	$pass = false;
	if(isset($_SERVER["PHP_AUTH_USER"])) {
		$user = $_SERVER["PHP_AUTH_USER"];
	} else return false;

	if(isset($_SERVER["PHP_AUTH_PW"])) {
		$pass = $_SERVER["PHP_AUTH_PW"];
	} else return false;

	error_log("passing basic auth for $user, $pass to backend");
	$auth = gwvpmini_authUserPass($user, $pass);
	if($auth !== false) {
		error_log("auth passes");
	} else {
		error_log("auth failes");
	}

	return $auth;
}

	
function gwvpmini_isUserAdmin($id=-1)
{
	
	
	if($id == -1) {
		if(isset($_SESSION)) if(isset($_SESSION["id"])) $id = $_SESSION["id"];
	}
	
	if($id == -1) return false;
	
	$lev = gwvpmini_userLevel($id);
	
	if($lev == 1) return true;

	return false;
}

function gwvpmini_authUserPass($user, $pass)
{
	$details = gwvpmini_getUser($user);
	if($details == false) {
		error_log("no user details for $user");
		return false;
	}
	
	if(sha1($pass)!=$details["password"]) return false;
	
	return $details["username"];
}

?>