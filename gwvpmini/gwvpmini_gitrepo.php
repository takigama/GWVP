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
	global $BASE_URL;
	
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
				$repo_base = gwvpmini_getConfigVal("repodir");
				$cmd = "git --git-dir=\"$repo_base/$name.git\" log --all -1 2> /dev/null";
				echo "<tr><td><a href=\"$BASE_URL/view/$name\">$name</a></td><td>$desc</td>";
				echo "<td>";
				error_log("CMD: $cmd");
				//system("$cmd");
				$fls = popen($cmd, "r");
				$tks = "";
				if($fls !== false) while(!feof($fls)) {
					$tks .= fread($fls,1024);
				}
				
				if($tks == "") {
					echo "No Log Info Yet";
				} else echo $tks;
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		gwvpmini_GitCreateRepoForm();
		
		
		$contreps = gwvpmini_GetContributedRepos($_SESSION["username"]);
		
		if($contreps !== false) {
			echo "<h2>Repos you contribute to</h2>";
			echo "<table border=\"1\"><tr><th>Repo Name</th><th>Owner</th><th>Repo Description</th><th>Last Log</th></tr>";
			foreach($contreps as $repo) {
				$name = $repo["name"];
				$desc = $repo["desc"];
				$repo_base = gwvpmini_getConfigVal("repodir");
				$cmd = "git --git-dir=\"$repo_base/$name.git\" log --all -1 2> /dev/null";
				error_log("CMD: $cmd");
				//system("$cmd");
				$fls = popen($cmd, "r");
				$tks = "";
				if($fls !== false) while(!feof($fls)) {
					$tks .= fread($fls,1024);
				}
				
				if($tks == "") {
					$lastlog = "No Log Info Yet";
				} else $lastlog = $tks;
				
				$owner = gwvpmini_getUser(null, null, $repo["owner"]);
				$repname = "<a href=\"$BASE_URL/view/$name\">$name</a>";
				$repown = gwvpmini_HtmlGravatar($owner["email"], 30, "<br>")."<a href=\"$BASE_URL/user/".$owner["username"]."\">".$owner["username"]."</a>";
				
				
				echo "<tr><td>$repname</td><td>$repown</td><td>$desc</td><td>$lastlog</td></tr>";
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
	
	global $BASE_URL;
	
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
				echo "<tr><td><a href=\"$BASE_URL/view/$name\">$name</a></td><td>$desc</td>";
				echo "<td>";
				$repo_base = gwvpmini_getConfigVal("repodir");
				$cmd = "git --git-dir=\"$repo_base/$name.git\" log --all -1 2> /dev/null";
				error_log("CMD: $cmd");
				//system("$cmd");
				$fls = popen($cmd, "r");
				$tks = "";
				if($fls !== false) while(!feof($fls)) {
					$tks .= fread($fls,1024);
				}
				
				if($tks == "") {
					echo "No Log Info Yet";
				} else echo $tks;
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
	echo "<tr><th>Read Permissions</th><td>";
	echo "<select name=\"perms\">";
	echo "<option value=\"perms-public\">Anyone Can Read</option>";
	echo "<option value=\"perms-registered\">Must be Registered To Read</option>";
	echo "<option value=\"perms-onlywrite\">Only Writers can Read</option>";
	echo "</select>";
	echo "</td></tr>";
	echo "<tr><td colspan=\"2\"><input type=\"submit\" name=\"Create\" value=\"Create\"></td></tr>";
	echo "</table>";
	echo "</form>";
}

function gwvpmini_RepoCreate()
{
	
	global $BASE_URL;
	
	// TODO: check the stuff out
	// first reponame
	$inputcheck = true;

	// remove a .git at the end if it was input
	$_REQUEST["reponame"] = preg_replace("/\.git$/", "", $_REQUEST["reponame"]);
	
	// check for valid chars
	$replcheck = preg_replace("/[a-zA-Z0-9_\-\.]/", "", $_REQUEST["reponame"]);
	if(strlen($replcheck)>0) {
		$inputcheck = false;
		$inputcheckerror = "Repo name contains invalid characters, repos can only contain a-z, A-Z, 0-9, _, - and .";
	}
	
	if(!$inputcheck) {
		gwvpmini_SendMessage("error", "$inputcheckerror");
		header("Location: $BASE_URL/repos");
	} else	if(gwvpmini_isLoggedIn()) {
		//gwvpmini_createGitRepo($name, $ownerid, $desc, $bundle=null, $defaultperms=0)
		if(gwvpmini_HaveRepo($_REQUEST["reponame"])) {
			gwvpmini_SendMessage("error", "Repo ".$_REQUEST["reponame"]." already exists");
			header("Location: $BASE_URL/repos");
		} else {
			gwvpmini_createGitRepo($_REQUEST["reponame"], $_SESSION["id"], $_REQUEST["repodesc"], $_REQUEST["perms"]);
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


function gwvpmini_RemoveRepo($rid)
{
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	$repdet = gwvpmini_getRepo(null, null, $rid);
	
	$rname = $repdet["name"];
	
	error_log("FROM PANTS:".print_r($repdet,true)." ----------- ".print_r($rname, true));
	
	if($repdet != false && $rname != "") {
		if(file_exists("$repo_base/$rname.git")) {
			// recursive remove - frightening
			if(gwvpmini_RecursiveDelete("$repo_base/$rname.git")) {
				gwvpmini_RemoveRepoDB($rid);
			}
		}
	} return false;
}

function gwvpmini_RecursiveDelete($fpath)
{
	error_log("RECURSEDETELE: ".$fpath);
	if(is_file($fpath)){
		return @unlink($fpath);
	}
	elseif(is_dir($fpath)){
		$scan = glob(rtrim($fpath,'/').'/*');
		foreach($scan as $index=>$path){
			gwvpmini_RecursiveDelete($path);
		}
		return @rmdir($fpath);
	}
}
	
?>