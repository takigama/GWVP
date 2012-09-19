<?php
$CALL_ME_FUNCTIONS["register"] = "gwvpmini_RegisterCallMe";

global $can_register, $reg_reqs_confirm, $confirm_from_address;

$reg = gwvpmini_getConfigVal("canregister");
$reg2 = gwvpmini_getConfigVal("registerrequiresconfirm");
$reg3 = gwvpmini_getConfigVal("eamilfromaddress");

if($reg == null) {
	gwvpmini_setConfigVal("canregister", "1");
} else if($reg == 1) {
	$can_register = true;
} else {
	$can_register = false;
}

if($reg2 == null) {
	gwvpmini_setConfigVal("registerrequiresconfirm", "0");
} else if($reg2 == 1) {
	$reg_reqs_confirm = true;
} else {
	$reg_reqs_confirm = false;
}

if($reg3 == null) {
	gwvpmini_setConfigVal("eamilfromaddress", "admin@localhost");
	$confirm_from_address = "admin@localhost";
} else {
	$confirm_from_address = $reg3;
}


function gwvpmini_RegisterCallMe()
{
	
	
	error_log("in admin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "register") {
				if(isset($qspl[1])) {
					if($qspl[1] == "sendinfo") {
						return "gwvpmini_RegisterUser";
					}
					if($qspl[1] == "confirmreg") {
						return "gwvpmini_ConfirmRegistration";
					}
				} else return "gwvpmini_RegisterPage";
			} else return false;
		}
		else return false;
	}

	return false;
	
	
}

function gwvpmini_RegisterPage()
{
	global $user_view_call, $MENU_ITEMS, $BASE_URL;
	
	$MENU_ITEMS["40thisuser"]["text"] = "Register";
	$MENU_ITEMS["40thisuser"]["link"] = "$BASE_URL/register";
	
	gwvpmini_goMainPage("gwvpmini_RegisterPageBody");
}

function gwvpmini_RegisterPageBody()
{
	global $user_view_call, $can_register, $BASE_URL, $reg_reqs_confirm;
	
	echo "<h2>Registration</h2>";
	echo "Complete the following form for registration<br>";
	if($reg_reqs_confirm) {
		echo "Email address will be confirmed after this form is completed, so make sure its available and viewable<br>";
	}
	echo "<form method=\"post\" action=\"$BASE_URL/register/sendinfo\">";
	echo "<table border=\"1\">";
	echo "<tr><th>Name to go by (full name/nickname/etc)</th><td><input type=\"text\" name=\"fullname\"></td></tr>";
	echo "<tr><th>Username (desired username for login)</th><td><input type=\"text\" name=\"username\"></td></tr>";
	echo "<tr><th>Password</th><td><input type=\"password\" name=\"password\"></td></tr>";
	echo "<tr><th>Confirm Password</th><td><input type=\"password\" name=\"confpassword\"></td></tr>";
	echo "<tr><th>Description of yourself</th><td><input type=\"text\" name=\"desc\"></td></tr>";
	echo "<tr><th>Email</th><td><input type=\"text\" name=\"email\"></td></tr>";
	echo "<tr><th>Confirm Email</th><td><input type=\"text\" name=\"confemail\"></td></tr>";
	echo "<tr><td colspan=\"2\"><input type=\"submit\" name=\"Add\" value=\"Add\"></td></tr>";
	echo "</table>";
	echo "</form>";
	
}

function gwvpmini_RegisterUser()
{
	global $can_register, $BASE_URL, $reg_reqs_confirm;
	
	$reg_succeeded = true;
	$failed_error = "oops";
	
	$uname = $_REQUEST["username"];
	$fname = $_REQUEST["fullname"];
	$pass1 = $_REQUEST["password"];
	$pass2 = $_REQUEST["confpassword"];
	$email1 = $_REQUEST["email"];
	$email2 = $_REQUEST["confemail"];
	$desc = $_REQUEST["desc"];
	
	if($pass1 != $pass2) {
		$failed_error = "Password and confirmation password differ (hit back to try again)";
		$reg_succeeded = false;
	}
	
	if($email1 != $email2) {
		$failed_error = "email and confirmation email differ (hit back to try again)";
		$reg_succeeded = false;
	}
	
	if(gwvpmini_GetUserId($uname) !== false) {
		$failed_error = "Username already in use (hit back and try a new one)";
		$reg_succeeded = false;
	}
	
	if(!$reg_succeeded) {
		gwvpmini_SendMessage("error", $failed_error);
	} else {
		//function gwvpmini_AddUser($username, $password, $fullname, $email, $desc, $level, $status)
		if($reg_reqs_confirm) {
			$hash = gwvpmini_GenerateHash();
			$s = "2:$hash";
			gwvpmini_SendMessage("info", "An email has been sent to the registered email address with details to continue the registration process $hash");
		} else {
			gwvpmini_SendMessage("info", "Congratulations, you are now registered, login to continue");
			$s = 0;
		}
		
		gwvpmini_AddUser($uname, $pass1, $fname, $email1, $desc, 0, $s);
		
	}
	
	header("Location: $BASE_URL");
}

function gwvpmini_GenerateHash()
{
	$hashlen = 64;
	$hashchars = "abcdefghijlkmnopqrstuvwxyz01234567890";
	
	$hash = "";
	for($i=0; $i<$hashlen; $i++) {
		$hash .= $hashchars[rand(0,strlen($hashchars)-1)];
	}
	
	return $hash;
}

function gwvpmini_ConfirmRegistration()
{
	global $can_register, $BASE_URL, $reg_reqs_confirm;
	
	$hash = "";
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[2])) {
			$hash = $qspl[2];
		}
	}
	
	if($hash == "") {
		gwvpmini_SendMessage("error", "Confirmation failed, Confirm the url you used and try again");
	} else if(gwvpmini_UpdateStatusFromConfirm($hash)) {
		gwvpmini_SendMessage("info", "Confirmation succeeded, you may now login with your username and password");
	} else {
		gwvpmini_SendMessage("error", "Confirmation failed, Confirm the url you used and try again");
	}
	
	header("Location: $BASE_URL");
}

?>