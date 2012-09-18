<?php


$CALL_ME_FUNCTIONS["search"] = "gwvpmini_SearchCallMe";

// the home_page_provders bit is an array



function gwvpmini_SearchCallMe()
{

	error_log("in repoadmin callme - err?");
	error_log(print_r($_REQUEST, true));
	if(isset($_REQUEST["q"])) {
		error_log("in repoadmin callme, for Q");
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "search") {
				return "gwvpmini_SearchMainPage";
			} else return false;
		}
		else return false;
	}

	return false;
}


function gwvpmini_SearchBuilder()
{
	global $BASE_URL;
	
	echo "<form method=\"post\" action=\"$BASE_URL/search\">";
	echo "<input type=\"text\" name=\"searchstring\"><input type=\"submit\" name=\"Search\" value=\"Seach\">";
	echo "</form>";
		
}

function gwvpmini_SearchMainPage()
{
	gwvpmini_goMainPage("gwvpmini_SearchMainPageBody");
}

function gwvpmini_SearchMainPageBody()
{
	echo "You searched for ".$_REQUEST["searchstring"];
}

?>