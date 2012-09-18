<?php

$CALL_ME_FUNCTIONS["gitcontrol"] = "gwvpmini_gitControlCallMe";

//$MENU_ITEMS["20repos"]["text"] = "Repo Admin";
//$MENU_ITEMS["20repos"]["link"] = "$BASE_URL/admin/repos";

// TODO: we could actually change backend interface such that is
// will respond to any url's that contain "repo.git" rather then
// having to be $BASE_URL/git/repo.git
function gwvpmini_gitControlCallMe()
{
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		if(isset($qspl[0])) {
			if($qspl[0] == "git") {
				return "gwvpmini_gitBackendInterface";
			}
		} 
		else return false;
	}
	
	return false;
	
}


function gwvpmini_gitBackendInterface()
{
	// and this is where i re-code the git backend interface from scratch
	global $BASE_URL;
	
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	// TODO: we need to stop passing the repo name around as "repo.git", it needs to be just "repo"
	
	
	/* bizare git problem that ignores 403's or continues on with a push despite them 
	error_log("FLAP for ".$_SERVER["REQUEST_URI"]);
	if(isset($_REQUEST)) {
		$dump = print_r($_REQUEST, true);
		error_log("FLAP, $dump");
	}
	if(isset($_SERVER["PHP_AUTH_USER"])) {
		error_log("FLAP: donut hole");
	}*/
	

	
	$repo = "";
	$repoid = false;
	$newloc = "/";
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		// TODO do this with 
		$repo = preg_replace("/\.git$/", "", $qspl[1]);
		$repoid = gwvpmini_GetRepoId($repo);
		for($i=2; $i < count($qspl); $i++) {
			$newloc .= "/".$qspl[$i];
		}
	}
	
	if($repoid == false) {
		gwvpmini_fourZeroFour();
		return;
	}
	
	// we do an update server cause its weird and i cant figure out when it actually needs to happen
	chdir("$repo_base/$repo.git");
	exec("/usr/bin/git update-server-info");
	
	
	// so now we have the repo
	// next we determine if this is a read or a write
	$write = false;
	if(isset($_REQUEST["service"])) {
		if($_REQUEST["service"] == "git-receive-pack") {
			error_log("got write as receivepack in post");
			$write = true;
		}
	}
	if($_SERVER["REQUEST_METHOD"] == "POST") {
		$write = true;
	}
	// THIS MAY CAUSE ISSUES LATER ON but we do it cause the git client ignores our 403 when it uses git-receive-pack after an auth
	// no, this isnt a solution cause auth'd read attempts will come up as writes...
	//if(isset($_SERVER["PHP_AUTH_USER"])) {
		//$write = true;
	//}
	
	$perms = 5;
	
	// if its a write, we push for authentication
	if($write) {
		error_log("is write attempt, ask for login");
		$person = gwvpmini_checkBasicAuthLogin();
		if($person == false) {
			gwvpmini_AskForBasicAuth();
			return;
		} else {
			error_log("checking perms for $person against $repoid for repo $repo");
			// here we pass to the git backend
			error_log("perms are $perms and im allowed");
			gwvpmini_callGitBackend($person["username"], $repo);
		}
		return;
	}
	
	
	// if they're less then read, we need to then check the user auth permissions
	if($perms < 2) {
		// we ask for auth
		$person = gwvpmini_checkBasicAuthLogin();
		if($person == false) {
			gwvpmini_AskForBasicAuth();
			return;
		} else {
		}
	}
	
	// if we made it this far, we a read and we have permissions to do so, just search the file from the repo
	if(file_exists("$repo_base/$repo.git/$newloc")) {
		error_log("would ask $repo for $repo.git/$newloc from $repo_base/$repo.git/$newloc");
		$fh = fopen("$repo_base/$repo.git/$newloc", "rb");
		
		error_log("pushing file");
		while(!feof($fh)) {
			echo fread($fh, 8192);
		}
	} else {
		//echo "would ask $repo,$actual_repo_name for $repo/$newloc from $repo_base/$repo/$newloc, NE";
		gwvpmini_fourZeroFour();
		return;
	}
	
}


function gwvpmini_gitBackendInterface_old()
{
	global $BASE_URL;
	
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	$repo = "";
	$newloc = "/";
	if(isset($_REQUEST["q"])) {
		$query = $_REQUEST["q"];
		$qspl = explode("/", $query);
		$repo = $qspl[1];
		for($i=2; $i < count($qspl); $i++) {
			$newloc .= "/".$qspl[$i];
		}
	}
	
	$actual_repo_name = preg_replace("/\.git$/", "", $repo); 
	
	$user = gwvpmini_checkBasicAuthLogin();

	if(!$user) {
		error_log("User is set to false, so its anonymouse");
	} else {
		error_log("user is $user");
	}
	
	// must remember that $user of false is anonymous when we code gwvpmini_repoPerm'sCheck()
	if(!gwvpmini_repoPermissionCheck($actual_repo_name, $user)) {
		error_log("perms check fails - start auth");
		if(isset($_SERVER["PHP_AUTH_USER"])) {
			error_log("have auth - push 403");
			gwvpmini_fourZeroThree();
		} else {
			error_log("push auth");
			gwvpmini_AskForBasicAuth();
			return;
		}
	}
	
	// we need to quite a bit of parsing in here. The "repo" will always be /git/repo.git
	// but if we get here from a browser, we need to forward back to a normal repo viewer
	// the only way i can think of doing this is to check the useragent for the word "git"
	
	/*
	 * here we need to
	 * 1) figure out the repo its acessing
	 * 2) figure out the perms on the repo
	 * 3) determine if its a pull or a push
	 * - if its a pull, we just serve straight from the fs
	 * - if its a push, we go thru git-http-backend
	 * 4) if it requiers auth, we push to auth
	 * 
	 */
	$agent = "git-unknown";
	$isgitagent = false;
	
	// tested the user agent bit with jgit from eclipse and normal git... seems to work
	if(isset($_SERVER["HTTP_USER_AGENT"])) {
		$agent = $_SERVER["HTTP_USER_AGENT"];
		error_log("in git backend with user agent $agent");
		if(stristr($agent, "git")!==false) {
			$isgitagent = true;
		}
	}
	
	
		
	/* dont need this code right now
	if($isgitagent) echo "GIT: i am a git backened interface for a repo $repo, agent $agent";
	else echo "NOT GIT: i am a git backened interface for a repo $repo, agent $agent";
	*/
	
	// now we need to rebuild the actual request or do we?
	//$basegit = "$BASE_URL/git/something.git";
	//$newloc = preg_replace("/^$basegit/", "", $_SERVER["REQUEST_URI"]);
	chdir("$repo_base/$repo");
	exec("/usr/bin/git update-server-info");
	
	if($_SERVER["REQUEST_METHOD"] == "POST") {
			gwvpmini_AskForBasicAuth();
			gwvpmini_callGitBackend($repo);
			return;
	}
	
	if(isset($_REQUEST["service"])) {
		if($_REQUEST["service"] == "git-receive-pack") {
			// we are a write call - we need auth and we're going to the backend proper
			gwvpmini_AskForBasicAuth();
			gwvpmini_callGitBackend($repo);
			return;
		}
	}
	
	
	if(file_exists("$repo_base/$repo/$newloc")) {
		error_log("would ask $repo,$actual_repo_name for $repo/$newloc from $repo_base/$repo/$newloc");
		$fh = fopen("$repo_base/$repo/$newloc", "rb");
		
		error_log("pushing file");
		while(!feof($fh)) {
			echo fread($fh, 8192);
		}
	} else {
		echo "would ask $repo,$actual_repo_name for $repo/$newloc from $repo_base/$repo/$newloc, NE";
		header('HTTP/1.0 404 No Such Thing');
		return;
	}
}

function gwvpmini_canManageRepo($userid, $repoid)
{
	// only the owner or an admin can do these tasks
	error_log("Checking repoid, $repoid against userid $userid");
	
	if(gwvpmini_IsUserAdmin(null, null, $userid)) return true;
	if(gwvpmini_IsRepoOwner($userid, $repoid)) return true;
	return false;
}

function gwvpmini_callGitBackend($username, $repo)
{
	// this is where things become a nightmare
		$fh   = fopen('php://input', "r");
		
		$repo_base = gwvpmini_getConfigVal("repodir");
		
		
		$ruri = $_SERVER["REQUEST_URI"];
		$strrem = "git/$repo.git";
		$euri = str_replace($strrem, "", $_REQUEST["q"]);
		//$euri = preg_replace("/^git\/$repo\.git/", "", $_REQUEST["q"]);
		
		
		
		$rmeth = $_SERVER["REQUEST_METHOD"];
		
		$qs = "";
		foreach($_REQUEST as $key => $var) {
			if($key != "q") {
				//error_log("adding, $var from $key");
				if($qs == "") $qs.="$key=$var";
				else $qs.="&$key=$var";
			}
		}
		
		//sleep(2);
		
		
		
		// this is where the fun, it ends.
		$myoutput = "";
		unset($myoutput);
		
		// this be nasty!
		
		// setup env
		if(isset($procenv))	unset($procenv);
		$procenv["GATEWAY_INTERFACE"] = "CGI/1.1";
		$procenv["PATH_TRANSLATED"] = "/$repo_base/$repo.git/$euri";
		$procenv["REQUEST_METHOD"] = "$rmeth";
		$procenv["GIT_HTTP_EXPORT_ALL"] = "1";
		$procenv["QUERY_STRING"] = "$qs";
		$procenv["HTTP_USER_AGENT"] = "git/1.7.1";
		$procenv["REMOTE_USER"] = "$username";
		$procenv["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
		$procenv["AUTH_TYPE"] = "Basic";
		
		if(isset($_SERVER["CONTENT_TYPE"])) { 
			$procenv["CONTENT_TYPE"] = $_SERVER["CONTENT_TYPE"];
		} else {
			//$procenv["CONTENT_TYPE"] = "";
		}
		if(isset($_SERVER["CONTENT_LENGTH"])) { 
			$procenv["CONTENT_LENGTH"] = $_SERVER["CONTENT_LENGTH"];
		}
		
		error_log("path trans'd is /$repo_base/$repo.git/$euri from $ruri with ".$_REQUEST["q"]." $strrem");
		
		
		

		$pwd = "/$repo_base/";
		
		$proc = proc_open("/usr/lib/git-core/git-http-backend", array(array("pipe","rb"),array("pipe","wb"),array("file","/tmp/err", "a")), $pipes, $pwd, $procenv);
		
		$untilblank = false;
		while(!$untilblank&&!feof($pipes[1])) {
			$lines_t = fgets($pipes[1]);
			$lines = trim($lines_t);
			error_log("got line: $lines");
			if($lines_t == "\r\n") {
				$untilblank = true;
				error_log("now blank");
			} else header($lines);
			if($lines === false) {
				error_log("got an unexpexted exit...");
				exit(0);
			}
			
		}
		

		$firstline = true;
		$continue = true;
		
		if(!stream_set_blocking($fh,0)) {
			error_log("cant set input non-blocking");
		}

		if(!stream_set_blocking($pipes[1],0)) {
			error_log("cant set pipe1 non-blocking");
		}
		
		// i was going to use stream_select, but i feel this works better like this
		while($continue) {
			// do client
			if(!feof($fh)) {
				$from_client_data = fread($fh,8192);
				if($from_client_data !== false) fwrite($pipes[0], $from_client_data);
				fflush($pipes[0]);
				//fwrite($fl, $from_client_data);
				$client_len = strlen($from_client_data);
			} else {
				error_log("client end");
				$client_len = 0;
			}
			
			// do cgi
			// sometimes, we get a \r\n from the cgi, i do not know why she swallowed the fly,
			// but i do know that the fgets for the headers above should have comsued that
			if(!feof($pipes[1])) {
				$from_cgi_data_t = fread($pipes[1],8192);
				$from_cgi_data = $from_cgi_data_t;
				
				// i dont know if this will solve it... it coudl cause some serious issues elsewhere
				// TODO: this is a hack, i need to know why the fgets above doesn consume the \r\n even tho it reads it
				// i.e. why the pointer doesnt increment over it, cause the freads above then get them again.
				if($firstline) {
					if(strlen($from_cgi_data_t)>0) {
						// i dont get why this happens, and its very frustrating.. im not sure if its a bug in php
						// or something the git-http-backend thing is doing..
						// TODO: find out why this happens
						$from_cgi_data = preg_replace("/^\r\n/", "", $from_cgi_data_t);
						if(strlen($from_cgi_data)!=strlen($from_cgi_data_t)) {
							error_log("MOOOKS - we did trunc");
						} else {
							error_log("MOOOKS - we did not trunc");
						}
						$firstline = false;
					}
				}
				
				if($from_cgi_data !== false) {
					echo $from_cgi_data;
					flush();
				}
				$cgi_len = strlen($from_cgi_data);
			} else {
				error_log("cgi end");
				$cgi_len = 0;
			}
			
			if(feof($pipes[1])) $continue = false;
			else {
				if($client_len == 0 && $cgi_len == 0) {
					usleep(200000);
					error_log("sleep tick");
				} else {
					error_log("sizes: $client_len, $cgi_len");
					if($cgi_len > 0) {
						error_log("from cgi: \"$from_cgi_data\"");
					}
				}
			}
			
		}
		
		
		//fclose($fl);
		fclose($fh);
		fclose($pipes[1]);
		fclose($pipes[0]);	
}



function gwvpmini_repoExists($name)
{
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	if(file_exists("$repo_base/$name.git")) return true;
	else return false;
}

// default perms:
// 0 - anyone can clone/read, only owner can write
// 1 - noone can clone/read, repo is visible (i.e. name), only owner can read/write repo
// 2 - only owner can see anything
function gwvpmini_createGitRepo($name, $ownerid, $desc)
{
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	// phew, this works, but i tell you this - bundles arent quite as nice as they should be
	error_log("would create $repo_base/$name.git");
	exec("/usr/bin/git init $repo_base/$name.git --bare > /tmp/gitlog 2>&1");
	chdir("$repo_base/$name.git");
	exec("/usr/bin/git update-server-info");

	// gwvpmini_AddRepo($reponame, $repodesc, $repoowner, $defaultperms = 0)
	gwvpmini_AddRepo($name, $desc, $ownerid);
	
	return true;
}


?>