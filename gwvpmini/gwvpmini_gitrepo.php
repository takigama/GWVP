<?php
global $HOME_PAGE_PROVIDERS;


$CALL_ME_FUNCTIONS["repoadmin"] = "gwvpmini_RepoCallMe";
$HOME_PAGE_PROVIDERS["gitlog"] = "gwvpmini_GitLogProvider";


// the home_page_provders bit is an array

$MENU_ITEMS["10repos"]["text"] = "Repos";
$MENU_ITEMS["10repos"]["link"] = "$BASE_URL/repos";


function gwvpmini_RepoCallMe()
{

	error_log("in repoadmin callme - err?");
	error_log(print_r($_REQUEST, true));
	if(isset($_REQUEST["q"])) {
		error_log("in repoadmin callme, for Q");
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "repos") {
				error_log("in repos call");
				if(isset($qspl[1])) {
					if($qspl[1] == "create") {
						return "gwvpmini_RepoCreate";
					} else {
						return "gwvpmini_RepoMainPage";
					}
				} else {
					error_log("i got here, where next?");
					return "gwvpmini_RepoMainPage";
				}
			} else return false;
		}
		else return false;
	}

	return false;
}


function gwvpmini_RepoMainPage()
{
	gwvpmini_goMainPage("gwvpmini_RepoMainPageBody");
}


function gwvpmini_RepoMainPageBody()
{
	gwvpmini_GitCreateRepoForm();
	if(gwvpmini_isLoggedIn()) {
		$repos = gwvpmini_GetOwnedRepos($_SESSION["username"]);
		if(!$repos) {
			echo "You currently own no repos<br>";	
		} else {
			echo "<h2>Your Repos</h2>";
			echo "<table border=\"1\"><tr><th>Repo Name</th><th>Repo Description</th><th>Last Log</th></tr>";
			foreach($repos as $repo) {
				$name = $repo["name"];
				$desc = $repo["desc"];
				echo "<tr><td>$name</td><td>$desc</td>";
				echo "<td>";
				$repo_base = gwvpmini_getConfigVal("repodir");
				$cmd = "git --git-dir=\"$repo_base/$name.git\" log -1 2>&1";
				error_log("CMD: $cmd");
				system("$cmd");
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
	return true;
}


function gwvpmini_GitLogProvider()
{
	/*
	 * The home page provider will:
	* 1) show the last 10 commits for every repository - though, excluding private repos
	* 2) if loged in, show the last commit on any repo's the user owns
	*
	* So i need a table thats going to list "writes" by user - as in POST writes but only
	* put that info into the stats (doesnt exist) db if the repo is publically readable
	*
	* Or... should we instead just list every repo?
	*/
	echo "<h2>Repo Activity</h2>";
	if(gwvpmini_isLoggedIn()) {
		$repos = gwvpmini_GetOwnedRepos($_SESSION["username"]);
		if(!$repos) {
			echo "You currently own no repos<br>";	
		} else {
			echo "<h2>Your Repos</h2>";
			echo "<table border=\"1\"><tr><th>Repo Name</th><th>Repo Description</th><th>Repo Log</th></tr>";
			foreach($repos as $repo) {
				$name = $repo["name"];
				$desc = $repo["desc"];
				echo "<tr><td>$name</td><td>$desc</td>";
				echo "<td>";
				$repo_base = gwvpmini_getConfigVal("repodir");
				$cmd = "git --git-dir=\"$repo_base/$name.git\" log -1 2>&1";
				error_log("CMD: $cmd");
				system("$cmd");
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}

function gwvpmini_GitCreateRepoForm()
{
	global $BASE_URL;
	
	echo "<form method=\"post\" action=\"$BASE_URL/repos/create\">";
	echo "<table border=\"1\">";
	echo "<tr><th colspan=\"2\">Create Repo</th></tr>";
	echo "<tr><th>Repo Name</th><td><input type=\"text\" name=\"reponame\"></td></tr>";
	echo "<tr><th>Repo Description</th><td><input type=\"text\" name=\"repodesc\"></td></tr>";
	echo "<tr><td colspan=\"2\"><input type=\"submit\" name=\"Create\" value=\"Create\"></td></tr>";
	echo "</table>";
	echo "</form>";
}

function gwvpmini_RepoCreate()
{
	
	global $BASE_URL;
	
	if(gwvpmini_isLoggedIn()) {
		//gwvpmini_createGitRepo($name, $ownerid, $desc, $bundle=null, $defaultperms=0)
		if(gwvpmini_HaveRepo($_REQUEST["reponame"])) {
			gwvpmini_SendMessage("error", "Repo ".$_REQUEST["reponame"]." already exists");
			header("Location: $BASE_URL/repos");
		} else {
			gwvpmini_createGitRepo($_REQUEST["reponame"], $_SESSION["id"], $_REQUEST["repodesc"]);
			gwvpmini_SendMessage("info", "Repo ".$_REQUEST["reponame"]." has been created");
			header("Location: $BASE_URL/repos");
		}
	} else {
		gwvpmini_SendMessage("info", "Must be logged in to create repo");
		header("Location: $BASE_URL/repos");
	}
}

function gwvpmini_HaveRepo($reponame)
{
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	if(file_exists("$repo_base/$reponame.git")) return true;
}

?>