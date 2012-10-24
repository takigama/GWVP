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


function gwvpmini_CreateRepoHooks($repopath, $cmdpath, $reponame)
{
	$fp = fopen("$repopath/hooks/pre-receive", "w");
	
	if(!$fp) error_log("could not create pre-receive hook");
	
	// TODO: think about this one
	//$script = '#!/bin/bash'."\n\n".'DCOMMIT=`cat`'."\n".'START=`echo $DCOMMIT|cut -d " " -f 1`'."\n".'END=`echo $DCOMMIT|cut -d " " -f 2`'."\n".'REF=`echo $DCOMMIT|cut -d " " -f 3`'."\n\n";
	$script = "#!/bin/bash\n\nDCOMMIT=".'`cat`'."\n\nphp $cmdpath $reponame \$REMOTE_USER pre-receive \$DCOMMIT\n\n";
	fwrite($fp, $script);
	
	fclose($fp);
	
	chmod("$repopath/hooks/pre-receive", 0755);


	$fp = fopen("$repopath/hooks/update", "w");
	
	if(!$fp) error_log("could not create update hook");
	
	// TODO: think about this one
	unset($script);
	$script = "#!/bin/bash\n\nphp $cmdpath $reponame \$REMOTE_USER update \$1 \$2 \$3\n\n";
	fwrite($fp, $script);
	
	fclose($fp);
	
	chmod("$repopath/hooks/update", 0755);
}

function gwvpmini_gitBackendInterface()
{
	// and this is where i re-code the git backend interface from scratch
	global $BASE_URL, $cmd_line_tool;
	
	header_remove("Pragma");
	header_remove("Cache-Control");
	header_remove("Set-Cookie");
	header_remove("Expires");
	header_remove("X-Powered-By");
	header_remove("Vary");
	
	
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	// TODO: we need to stop passing the repo name around as "repo.git", it needs to be just "repo"
	
	
	/* bizare git problem that ignores 403's or continues on with a push despite them 
	// error_log("FLAP for ".$_SERVER["REQUEST_URI"]);
	if(isset($_REQUEST)) {
		$dump = print_r($_REQUEST, true);
		// error_log("FLAP, $dump");
	}
	if(isset($_SERVER["PHP_AUTH_USER"])) {
		// error_log("FLAP: donut hole");
	}*/
	
	error_log("REQUESTINBACKEND: ".print_r($_REQUEST, true));
	
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
	// dont believe i have to do this
	//exec("/usr/bin/git update-server-info");
	
	if(!file_exists("$repo_base/$repo.git/hooks/pre-receive") || !file_exists("$repo_base/$repo.git/hooks/update")) {
		// error_log("WRITING HOOKS");
		gwvpmini_CreateRepoHooks("$repo_base/$repo.git", $cmd_line_tool, $repo);
	}
	
	
	// so now we have the repo
	// next we determine if this is a read or a write
	
	// TODO: WE NEED TO FIX THIS, IT DOESNT ALWAYS DETECT a "WRITE"
	$write = false;
	if(isset($_REQUEST["service"])) {
		if($_REQUEST["service"] == "git-receive-pack") {
			// error_log("got write as receivepack in post");
			$write = true;
		}
	}
	if(preg_match("/.*git-receive-pack$/", $_REQUEST["q"])) $write = true;
	//$write = true;
	// THIS MAY CAUSE ISSUES LATER ON but we do it cause the git client ignores our 403 when it uses git-receive-pack after an auth
	// no, this isnt a solution cause auth'd read attempts will come up as writes...
	//if(isset($_SERVER["PHP_AUTH_USER"])) {
		//$write = true;
	//}
	
	
	$person = gwvpmini_checkBasicAuthLogin();
	//$write = true;
	// next, figure out permissions for repo
	$rid = gwvpmini_GetRepoId($repo);
	$uid = -1;
	// error_log("AT THIS POINT WE HAVE $uid, $rid, $repo $person");
	
	if(!$person) {
		if($write) {
			// error_log("ASK FOR BASIC AUTH");
			gwvpmini_AskForBasicAuth();
			return;
		} else {
			$perm = gwvpmini_GetRepoPerm($rid, "a");
			if($perm < 1) {
				// error_log("ASK FOR BASIC AUTH 2");
				gwvpmini_AskForBasicAuth();
				return;
			}
		}
	} else {
		$uid = gwvpmini_GetUserId($person);
		$perm = gwvpmini_GetRepoPerm($rid, $uid);
		if($write) {
			if($perm < 2) {
				// error_log("SEND FOFF");
				gwvpmini_fourZeroThree();
				return;
			}
		} else {
			if($perm < 1) {
				gwvpmini_fourZeroThree();
				return;
			}
		}
	}
	
	// if its a write, we push for authentication
	
	//if($write) {
	if(!$person) {
		$person = "anonymous";
	}
	
	// if its a write, we check (before and after) the branch/tag info to see if they were updated
	if($write) {
		error_log("REQUESTINBACKEND: processed as write");
	} else {
		error_log("REQUESTINBACKEND: processed as read");
	}
	
	gwvpmini_callGitBackend($person, $repo);
	
	//if($write) {
		//}
	return;
	//}

	// if we made it this far, we a read and we have permissions to do so, just search the file from the repo
	/*if(file_exists("$repo_base/$repo.git/$newloc")) {
		// error_log("would ask $repo for $repo.git/$newloc from $repo_base/$repo.git/$newloc");
		$fh = fopen("$repo_base/$repo.git/$newloc", "rb");
		
		// error_log("pushing file");
		while(!feof($fh)) {
			echo fread($fh, 8192);
		}
	} else {
		// error_log("would ask $repo for $repo/$newloc from $repo_base/$repo/$newloc, NE");
		gwvpmini_fourZeroFour();
		return;
	}*/
	
}

function gwvpmini_canManageRepo($userid, $repoid)
{
	// only the owner or an admin can do these tasks
	// error_log("Checking repoid, $repoid against userid $userid");
	
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
				//// error_log("adding, $var from $key");
				if($qs == "") $qs.="$key=$var";
				else $qs.="&$key=$var";
			}
		}
		
		//sleep(2);
		
		$userdets = gwvpmini_getUser($username);
		
		// this is where the fun, it ends.
		$myoutput = "";
		unset($myoutput);
		
		// this be nasty!
		
		// setup env
		if(isset($procenv))	unset($procenv);
		$procenv["GATEWAY_INTERFACE"] = "CGI/1.1";
		$procenv["PATH_TRANSLATED"] = "/$repo_base/$repo.git/$euri";
		$procenv["REQUEST_METHOD"] = "$rmeth";
		$procenv["GIT_COMMITTER_NAME"] = $userdets["fullname"];
		$procenv["GIT_COMMITTER_EMAIL"] = $userdets["email"];
		$procenv["GIT_HTTP_EXPORT_ALL"] = "1";
		$procenv["QUERY_STRING"] = "$qs";
		$procenv["HTTP_USER_AGENT"] = "git/1.7.1";
		$procenv["REMOTE_USER"] = "$username";
		$procenv["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
		$procenv["AUTH_TYPE"] = "Basic";
		
		//// error_log("PROCENV: ".print_r($procenv,true));
		
		if(isset($_SERVER["CONTENT_TYPE"])) { 
			$procenv["CONTENT_TYPE"] = $_SERVER["CONTENT_TYPE"];
		} else {
			//$procenv["CONTENT_TYPE"] = "";
		}
		if(isset($_SERVER["CONTENT_LENGTH"])) { 
			$procenv["CONTENT_LENGTH"] = $_SERVER["CONTENT_LENGTH"];
		}
		
		// error_log("path trans'd is /$repo_base/$repo.git/$euri from $ruri with ".$_REQUEST["q"]." $strrem");
		
		
		

		$pwd = "/$repo_base/";
		
		$proc = proc_open("/usr/lib/git-core/git-http-backend", array(array("pipe","rb"),array("pipe","wb"),array("file","/tmp/err", "a")), $pipes, $pwd, $procenv);
		
		$untilblank = false;
		while(!$untilblank&&!feof($pipes[1])) {
			$lines_t = fgets($pipes[1]);
			$lines = trim($lines_t);
			// error_log("got line: $lines");
			if($lines_t == "\r\n") {
				$untilblank = true;
				// error_log("now blank");
			} else header($lines);
			if($lines === false) {
				// error_log("got an unexpexted exit...");
				exit(0);
			}
			
		}
		

		$firstline = true;
		$continue = true;
		
		if(!stream_set_blocking($fh,0)) {
			// error_log("cant set input non-blocking");
		}

		if(!stream_set_blocking($pipes[1],0)) {
			// error_log("cant set pipe1 non-blocking");
		}
		
		
		$stlimit = 0;
		$fp = fopen("/tmp/gitup.".rand(0,4000000), "w");
		// i was going to use stream_select, but i feel this works better like this
		while($continue) {
			// do client
			if(!feof($fh)) {
				$from_client_data = fread($fh,8192);
				if($from_client_data !== false) {
					fwrite($pipes[0], $from_client_data);
					fwrite($fp, $from_client_data);
				}
				fflush($pipes[0]);
				//fwrite($fl, $from_client_data);
				$client_len = strlen($from_client_data);
			} else {
				// error_log("client end");
				$client_len = 0;
				//$continue = false;
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
							// error_log("MOOOKS - we did trunc");
						} else {
							// error_log("MOOOKS - we did not trunc");
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
				// error_log("cgi end");
				$cgi_len = 0;
			}
			
			if(feof($pipes[1])) $continue = false;
			else {
				if($client_len == 0 && $cgi_len == 0) {
					usleep(200000);
					// error_log("sleep tick");
					$stlimit++;
					if($stlimit > 50) $continue = false;
				} else {
					$stlimit = 0;
					// error_log("sizes: $client_len, $cgi_len");
					if($cgi_len > 0) {
						// error_log("from cgi: \"$from_cgi_data\"");
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
function gwvpmini_createGitRepo($name, $ownerid, $desc, $defperms, $clonefrom, $isremoteclone)
{
	$repo_base = gwvpmini_getConfigVal("repodir");
	
	if($clonefrom !== false) {
		error_log("how did i end up in clonefrom? $clonefrom");
		if(!$isremoteclone) {
			exec("/usr/bin/git clone --bare $repo_base/$clonefrom.git $repo_base/$name.git >> /tmp/gitlog 2>&1");
			gwvpmini_AddRepo($name, $desc, $ownerid, $defperms, $clonefrom);
		} else {
			// we do this from an outside call in the background
			gwvpmini_SendMessage("error", "Cant clone from remote repos yet");
			return false;
		}
	} else {
	
	// phew, this works, but i tell you this - bundles arent quite as nice as they should be
	// error_log("would create $repo_base/$name.git");
		exec("/usr/bin/git init $repo_base/$name.git --bare >> /tmp/gitlog 2>&1");
		chdir("$repo_base/$name.git");
		exec("/usr/bin/git update-server-info");
	
		// gwvpmini_AddRepo($reponame, $repodesc, $repoowner, $defaultperms = 0)
		gwvpmini_AddRepo($name, $desc, $ownerid, $defperms, $clonefrom);
	}
	
	return true;
}


?>