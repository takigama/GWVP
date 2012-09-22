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
	global $BASE_URL;
	
	$search = $_REQUEST["searchstring"];
	
	$reps = gwvpmini_findReposLike($search);
	$ppls = gwvpmini_findPeopleLike($search);
	
	
	
	/*echo "<pre>repos\n\n";
	print_r($reps);
	echo "\n\nppl\n\n";
	print_r($ppls);
	echo "</pre>";*/
	
	// we need to process the search string into tokens here
	if($search == "") {
		echo "You need to enter a search string<br>";
		return;
	}
	
	$vl = gwvpmini_SearchTokenizeString($search);
	$search_str = $vl["str"];
	
	echo "<h2>Results</h2>";
	echo "Searching for \"$search_str\"<br>Note: Search does not look INSIDE repos<br>";
	echo "<table><tr><td bgcolor=\"#eeeeff\"><h3>Repo's</h3></td><td bgcolor=\"#eeffee\"><h3>People</h3></td></tr>";
	// repos
	echo "<tr valign=\"top\"><td>";
	
	
	
	if($reps != false) {
		echo "<table border=\"1\">";
		foreach($reps as $rep) {
			$ownerinfo = gwvpmini_getUser(null, null, $rep["owner"]);
			$userdets = gwvpmini_HtmlGravatar($ownerinfo["email"], 40, "<br>")."<a href=\"$BASE_URL/user/".$ownerinfo["username"]."\">".$ownerinfo["username"]."</a>";
			
			$repodets = "<b><a href=\"$BASE_URL/view/".$rep["name"]."\">".$rep["name"]."</a></b><br>".$rep["desc"];
			echo "<tr><td>$userdets</td><td>$repodets</td></tr>";
		}
		echo "</table>";
	} else echo "No Repo's Match";
	
	
	echo "</td><td>";
	// people
	if($ppls != false) {
		echo "<table border=\"1\">";
		$ownedrepos = "BLAHBLAH";
		foreach($ppls as $ppl) {
			$userdets = gwvpmini_HtmlGravatar($ppl["email"], 40, "<br>")."<a href=\"$BASE_URL/user/".$ppl["username"]."\">".$ppl["username"]."</a>";
			$repos = gwvpmini_GetOwnedRepos($ppl["username"]);
			if($repos == false) $ownedrepos = "No Repos";
			else {
				$ownedrepos = "";
				foreach($repos as $repo) {
					$ownedrepos .= "<b><a href=\"$BASE_URL/view/".$repo["name"]."\">".$repo["name"]."</a></b> - ".$repo["desc"]."<br>";
				}
			}
			echo "<tr><td>$userdets</td><td>$ownedrepos</td></tr>";
		}
		
		echo "</table>";
	} else echo "No People Match";
	
	
	echo "</table>";
}

function gwvpmini_SearchTokenizeString($search)
{
	$inp = preg_replace("/[^a-zA-Z0-9 ]+/", "", $search);
	
	$res = preg_split("/ +/", trim($inp));

	$ret["str"] = "";
	$i = 0;
	foreach($res as $r) {
		if($i == 0) $ret["str"] = "<b>$r</b>";
		else $ret["str"] .= " <i>and</i> <b>$r</b>";
		$ret["words"][$i] = $r;
		$i++;
	}
	
	/*echo "<pre>";
	print_r($ret);
	echo "</pre>";*/
	
	return $ret;
	
}

?>