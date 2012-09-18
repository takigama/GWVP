<?php
global $HOME_PAGE_PROVIDERS;


$CALL_ME_FUNCTIONS["repoview"] = "gwvpmini_RepoViewCallMe";



function gwvpmini_RepoViewCallMe()
{
	global $repo_view_call;
	
	error_log("in admin callme");
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "view") {
				if(isset($qspl[1])) {
					$repo_view_call = $qspl[1];
					return "gwvpmini_RepoViewPage";
				} else return false;
			} else return false;
		}
		else return false;
	}

	return false;
	
	
}

function gwvpmini_RepoViewPage()
{
	global $repo_view_call, $MENU_ITEMS, $BASE_URL;
	
	$MENU_ITEMS["40thisrepo"]["text"] = "$repo_view_call";
	$MENU_ITEMS["40thisrepo"]["link"] = "$BASE_URL/view/$repo_view_call";
	
	gwvpmini_goMainPage("gwvpmini_RepoViewPageBody");
}

function gwvpmini_RepoViewPageBody()
{
	global $repo_view_call, $MENU_ITEMS;

	
	echo "In repoview call for $repo_view_call";
}


?>