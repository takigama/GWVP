<?php
global $HOME_PAGE_PROVIDERS;


$CALL_ME_FUNCTIONS["repoadmin"] = "gwvpmini_RepoCallMe";
$HOME_PAGE_PROVIDERS["00gitlog"] = "gwvpmini_GitLogProvider";


// the home_page_provders bit is an array

$MENU_ITEMS["10repos"]["text"] = "Repos";
$MENU_ITEMS["10repos"]["link"] = "$BASE_URL/repos";


function gwvpmini_RepoCallMe()
{

	// error_log("in repoadmin callme - err?");
	// error_log(print_r($_REQUEST, true));
	if(isset($_REQUEST["q"])) {
		// error_log("in repoadmin callme, for Q");
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "repos") {
				// error_log("in repos call");
				if(isset($qspl[1])) {
					if($qspl[1] == "create") {
						return "gwvpmini_RepoCreate";
					} else {
						return "gwvpmini_RepoMainPage";
					}
				} else {
					// error_log("i got here, where next?");
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
		error_log("repos, ".print_r($repos, true));
		if(!$repos) {
			echo "You currently own no repos<br>";	
		} else {
			echo "<h2>Your Repos</h2>";
			echo "<table border=\"1\"><tr><th>Repo Name</th><th>Repo Description</th><th>Last Log</th></tr>";
			foreach($repos as $repo) {
				$name = $repo["name"];
				$desc = $repo["desc"];
				$stat = $repo["status"];
				$llog = "";
				if($stat != 0) {
					switch($stat) {
						case 1:
							$llog = "Repo Administratively Disabled";
							break;
						case 2:
							$llog = "Repo Cloning from remote";
							break;
					}
				} else {
				
					if($desc == "") $desc = "none";
					
					$repo_base = gwvpmini_getConfigVal("repodir");
					$cmd = "git --git-dir=\"$repo_base/$name.git\" log --all -1 2> /dev/null";
					// error_log("CMD: $cmd");
					//system("$cmd");
					$fls = popen($cmd, "r");
					$tks = "";
					if($fls !== false) while(!feof($fls)) {
						$tks .= fread($fls,1024);
					}
					
					if($tks == "") {
						$llog =  "No Log Info Yet";
					} else $llog = $tks;
					
				}
				echo "<tr><td><a href=\"$BASE_URL/view/$name\">$name</a></td><td>$desc</td><td>$llog</td></tr>";
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
				$stat = $repo["status"];
				if($stat != 0) {
					switch($stat) {
						case 1:
							$lastlog = "Repo Administratively Disabled";
							break;
						case 2:
							$lastlog = "Repo Cloning from remote";
							break;
					}
				} else {
					$repo_base = gwvpmini_getConfigVal("repodir");
					$cmd = "git --git-dir=\"$repo_base/$name.git\" log --all -1 2> /dev/null";
					// error_log("CMD: $cmd");
					//system("$cmd");
					$fls = popen($cmd, "r");
					$tks = "";
					if($fls !== false) while(!feof($fls)) {
						$tks .= fread($fls,1024);
					}
					
					if($tks == "") {
						$lastlog = "No Log Info Yet";
					} else $lastlog = $tks;
				}
				
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
	global $cmd_line_tool,$git_cli_cmd,$php_cli_cmd;
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
				
				if($desc == "") $desc = "-";
				echo "<tr><td><a href=\"$BASE_URL/view/$name\">$name</a></td><td>$desc</td>";
				echo "<td>";
				$repo_base = gwvpmini_getConfigVal("repodir");
				$cmd = "$git_cli_cmd --git-dir=\"$repo_base/$name.git\" log --all -1 2> /dev/null";
				// error_log("CMD: $cmd");
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
	echo "<tr><th>Repo Name</th><td><input type=\"text\" name=\"reponame\"></td><td>Name of your repo - letters, numbers _ and - only</td></tr>";
	echo "<tr><th>Repo Description</th><td><input type=\"text\" name=\"repodesc\"></td><td>Description of your repo</td></tr>";
	echo "<tr><th>Read Permissions</th><td>";
	echo "<select name=\"perms\">";
	echo "<option value=\"perms-public\">Anyone Can Read</option>";
	echo "<option value=\"perms-registered\">Must be Registered To Read</option>";
	echo "<option value=\"perms-onlywrite\">Only Writers can Read</option>";
	echo "</select></td><td>The basic permissions for the initial repo creation</td></tr>";
	echo "<tr><th>Clone From</th><td><input type=\"text\" name=\"clonefrom\"></td><td>Either a repo name (existing on this site) or a git url to clone from (blank for none)</td></tr>";
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
	
	$clonefrom = false;
	$fromremote = false;
	if(isset($_REQUEST["clonefrom"])) {
		if($_REQUEST["clonefrom"] != "") {
			$clonefrom = $_REQUEST["clonefrom"];
			if(preg_match("/git.*:\/\/.*/", $clonefrom)>0) {
				$fromremote = true;
			}
			if(preg_match("/http.*\:\/\//", $clonefrom)>0) $fromremote = true;
		}
	}
	
	if($clonefrom !== false && $fromremote == false) {
		// check the local repo exists
		$rn = gwvpmini_getRepo(null, $clonefrom, null);
		$uid = $_SESSION["id"];
		
		if($rn == false) {
			gwvpmini_SendMessage("error", "local repo $clonefrom given as upstream clone, however $clonefrom doesnt exist on this site (or you cant read it unbake)");
			header("Location: $BASE_URL/repos");
			return;
		}
		
		// resolve repo permissions on the read/clone
		if(gwvpmini_GetRepoPerm($rn["id"], $uid) < 1) {
			gwvpmini_SendMessage("error", "local repo $clonefrom given as upstream clone, however $clonefrom doesnt exist on this site (or you cant read it bake)");
			header("Location: $BASE_URL/repos");
			return;
		}
	}
	
	$defperms = "a";
	switch($_REQUEST["perms"]) {
		case "perms-registered":
			$defperms = "r";
			break;
		case "perms-onlywrite":
			$defperms = "x";
			break;
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
			if(gwvpmini_createGitRepo($_REQUEST["reponame"], $_SESSION["id"], $_REQUEST["repodesc"], $defperms, $clonefrom, $fromremote)) {
				gwvpmini_SendMessage("info", "Repo ".$_REQUEST["reponame"]." has been created");
			}
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
	
	// error_log("FROM PANTS:".print_r($repdet,true)." ----------- ".print_r($rname, true));
	
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
	// error_log("RECURSEDETELE: ".$fpath);
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

function gwvpmini_CompressCommitId($cid)
{
	$compressedcid = substr($cid, 0, 5)."...".substr($cid, strlen($cid)-5, strlen($cid));
	
	return $compressedcid;
}

function gwvpmini_GetCommitDetail($repo, $commitid)
{
	global $cmd_line_tool,$git_cli_cmd,$php_cli_cmd;
	
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git log $commitid -1 --format='%an'";	
	exec($cmd, $commitername, $returnvar);

	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git log $commitid -1 --format='%ae'";	
	exec($cmd, $commiteremail, $returnvar);

	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git log $commitid -1 --format='%ct'";	
	exec($cmd, $commitertime, $returnvar);

	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git log $commitid -1 --format='%s'";	
	exec($cmd, $commiterlog, $returnvar);

	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git log $commitid -1 --format='%b'";	
	exec($cmd, $commiterbody, $returnvar);
}

function gwvpmini_GetCommitList($repo, $branch, $num=20)
{
	global $git_cli_cmd,$git_cli_cmd,$php_cli_cmd;
	
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git log $commitid -1 --format='%an'";
	exec($cmd, $commitername, $returnvar);
	
}

function gwvpmini_GetRefList($repo)
{
	global $cmd_line_tool,$git_cli_cmd,$php_cli_cmd;
	
	$repo_base = gwvpmini_getConfigVal("repodir");

	$cmd = "$git_cli_cmd --git-dir=$repo_base/$repo.git for-each-ref $commitid --format='%(objecttype):%(objectname):%(refname)'";
	error_log("command was $cmd");
	exec($cmd, $reflist, $returnvar);
	
	return $reflist;
}
	
?>