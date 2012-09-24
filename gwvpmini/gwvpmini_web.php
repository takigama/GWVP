<?php

// this function is the initial insertion point for the web calls, here we need to determine where we go
global $CALL_ME_FUNCTIONS;

// the home_page_provders bit is an array 
global $HOME_PAGE_PROVIDERS;

$MENU_ITEMS["00home"]["text"] = "Home";
$MENU_ITEMS["00home"]["link"] = "$BASE_URL";



function gwvpmini_goWeb()
{
	global $CALL_ME_FUNCTIONS;
	
	// first we determine if we have a valid setup and run the installer if not
	/*if(!gwvpmini_issetup()) {
		gwvpmini_goSetup();
		return;
	}*/
	
	// next, we go thru the CALL_ME_FUNCTIONS - the purpose of call_me_functions is to determine if a function should be called based on
	// the functions return (i.e. if function returns false, its not it, otherwise it returns a function name we have to call)
	// this is important for our plugin structure later on - the key on the array serves an an ordering method
	ksort($CALL_ME_FUNCTIONS);
	foreach($CALL_ME_FUNCTIONS as $key => $val) {
		//error_log("checking callmefunction $key as $val");
		$callme = $val();
		if($callme !== false) {
			$callme();
			return;
		}
	}
	
	// we fell-thru to the main web page builder
	gwvpmini_goMainPage();
}

function gwvpmini_SendMessage($messagetype, $message)
{
	$_SESSION["messagetype"] = $messagetype;
	$_SESSION["message"] = $message;
}

function gwvpmini_goMainPage($bodyFunction = null)
{
	// the main page will look pretty simple, a title, a menu then a body
	global $WEB_ROOT_FS, $BASE_URL;
	
	// a simple web page layout that loads any css and js files that exist in the css and js directories
	echo "<html><head><title>GWVP Mini</title>";
	
	// load css
	if(file_exists("$WEB_ROOT_FS/css")) {
		$dh = opendir("$WEB_ROOT_FS/css");
		if($dh) {
			while(($file = readdir($dh))!==false) {
				$mt = preg_match("/.*.css$/", $file);
				if($mt > 0) {
					error_log("loading css $file");
					echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$BASE_URL/css/$file\">";
					//echo "required $basedir/$file\n";
				}
			}
		}		
	}

	// load js
	if(file_exists("$WEB_ROOT_FS/js")) {
		$dh = opendir("$WEB_ROOT_FS/js");
		if($dh) {
			while(($file = readdir($dh))!==false) {
				$mt = preg_match("/.*.js$/", $file);
				if($mt > 0) {
					error_log("loading js $file");
					echo "<script type=\"text/javascript\" src=\"$BASE_URL/js/$file\"></script>";
					//echo "required $basedir/$file\n";
				}
			}
		}		
	}
	
	
	// start body
	echo "</head><body>";
	
	echo "<h1>Git over Web Via PHP - Mini Version</h2>";
	
	
	echo "<table width=\"100%\">";
	
	echo "<tr width=\"100%\" bgcolor=\"#ddddff\"><td colspan=\"2\" align=\"right\">";
	gwvpmini_SearchBuilder();
	echo "</td></tr>";
	

	if(isset($_SESSION["message"])) {
		echo "<tr width=\"100%\"><td colspan=\"2\">";
		gwvpmini_MessageBuilder();
		echo "</td></tr>";
	}
	
	echo "<tr width=\"100%\" bgcolor=\"#ddffdd\"><td>";
	gwvpmini_MenuBuilder();
	echo "</td><td align=\"right\">";
	gwvpmini_LoginBuilder();
	echo "</td>";
	
	echo "</tr>";
	
	echo "<tr><td colspan=\"2\">";
	if($bodyFunction == null) {
		gwvpmini_BodyBuilder();
	} else {
		if(function_exists($bodyFunction)) {
			$bodyFunction();
		} else {
			error_log("Got called with non-existant body function, $bodyFunction");
			gwvpmini_BodyBuilder();
		}
	}
	echo "</td></tr>";
	
	echo "<tr><td>";
	gwvpmini_TailBuilder();
	echo "</td></tr></table></body></html>";
	
}


// builds the message builder if its needed
function gwvpmini_MessageBuilder()
{
	$message = "";
	$messagetype = "info";
	if(isset($_SESSION["message"])) $message = $_SESSION["message"];
	if(isset($_SESSION["messagetype"])) $messagetype = $_SESSION["messagetype"];
	
	if($message != "") {
		switch($messagetype) {
			case "info":
				echo "<table border=\"1\" width=\"100%\"><tr width=\"100%\"><td bgcolor=\"#AAFFAA\">$message</td></tr></table>";
				break;
			case "error":
				echo "<table border=\"1\" width=\"100%\"><tr width=\"100%\"><td bgcolor=\"#FFAAAA\">$message</td></tr></table>";
				break;
		}
		unset($_SESSION["message"]);
		if(isset($_SESSION["messagetype"])) unset($_SESSION["messagetype"]);
	}
}

// builds the menu structure
function gwvpmini_MenuBuilder()
{
	global $MENU_ITEMS, $BASE_URL;
	
	ksort($MENU_ITEMS);
	
	echo "<table border=\"1\"><tr><td><b><i>Menu</i></b></td>";
	foreach($MENU_ITEMS as $key => $val) {
		$link = $val["link"];
		$text = $val["text"];
		
		// TODO: redo this bit with stristr to find urls - special case for home
		$menucolor = "";
		if(isset($_REQUEST["q"])) {
			$extlink = str_replace("$BASE_URL/", "", $link);
			error_log("trying to do replace of $BASE_URL in $link, got $extlink for ".$_REQUEST["q"]);
			if(stristr($_REQUEST["q"], $extlink)!==false) {
				$menucolor = " bgcolor=\"#ffdddd\"";
				
			}
		} else {
			// special case for home
			if($link == $BASE_URL) $menucolor = " bgcolor=\"#ffdddd\"";
		}
		
		
		
		
		if(isset($val["userlevel"])) {
			if(gwvpmini_CheckAuthLevel($val["userlevel"])) {
				echo "<td$menucolor><a href=\"$link\">$text</a></td>";
			}
			
		} else {
			echo "<td$menucolor><a href=\"$link\">$text</a></td>";
		}
	}
	echo "</tr></table>";
	
}

function gwvpmini_LoginBuilder()
{
	global $WEB_ROOT_FS, $BASE_URL;
	
	$login = gwvpmini_IsLoggedIn();
	if($login === false) {
		gwvpmini_SingleLineLoginForm();
	} else {
		echo "Hello <a href=\"$BASE_URL/user/".$_SESSION["username"]."\">".$_SESSION["fullname"]."</a> <a href=\"$BASE_URL/logout\">logout</a>";
	}
}

// builds the body structure
function gwvpmini_BodyBuilder()
{
	global $HOME_PAGE_PROVIDERS;
	
	echo "I AM THE MAIN BODY, FEAR ME!!!! - have no idea whats going to go here";
	if(isset($HOME_PAGE_PROVIDERS)) {
		ksort($HOME_PAGE_PROVIDERS);
		foreach($HOME_PAGE_PROVIDERS as $provider) {
			error_log("Loading home_page_provider, $provider");
			$provider();
		}
	}
}

// builds the tail structure
function gwvpmini_TailBuilder()
{
	echo "<br><br><hr><font size=\"-1\"><b><a href=\"http://github.com/takigama/GWVP\">GWVP</a></b> - <i>Copyright 2011, 2012 PJR - <a href=\"http://www.gnu.org/copyleft/gpl.html\">GPL</a></i></font>";
}

function gwvpmini_emailToUserLink($email)
{
	global $BASE_URL;
	
	$username = gwvpmini_GetUserNameFromEmail($email);
	
	if($username !== false) {
		return "<a href=\"$BASE_URL/user/$username\">$username</a>";
	} else {
		return false;
	}
}

function gwvpmini_fourZeroThree()
{
	error_log("403 called");
	header("HTTP/1.1 403 Permission Denied");
}

function gwvpmini_fourZeroFour()
{
	error_log("404 called");
	header("HTTP/1.1 404 No Such Thing");
}


/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source http://gravatar.com/site/implement/images/php/
 */
function gwvpmini_HtmlGravatar($email, $size, $htmlappend="")
{
	
	global $use_gravatar;
	
	if($use_gravatar) {
		error_log("call to gravatar with yes");
	} else {
		error_log("call to gravatar with no");
	}
	
	if($use_gravatar == false) return "";
	return get_gravatar( $email, $size, 'mm', 'g', true)."$htmlappend";
}

function get_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
	$url = 'http://en.gravatar.com/avatar/';
	$url .= md5( strtolower( trim( $email ) ) );
	$url .= "?s=$s&d=$d&r=$r";
	if ( $img ) {
		$url = '<img src="' . $url . '"';
		foreach ( $atts as $key => $val )
			$url .= ' ' . $key . '="' . $val . '"';
		$url .= ' />';
	}
	return $url;
}


?>