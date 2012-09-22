<?php


global $DB_CONNECTION;
$DB_CONNECTION = false;


global $db_url, $db_type;
//error_log("in include for database, $db_type, $db_name");


function gwvpmini_DBExists()
{
	global $WEB_ROOT_FS, $BASE_URL, $data_directory, $db_type, $db_name;
	
	// oh this isnt working. poo.
	//error_log("checking for $db_name, $db_type");
	
	if($db_type == "sqlite") {
		if(file_exists($db_name)) {
			//error_log("Exists");
			return true;
		}
		else {
			//error_log("no exists");
			return false;
		}
	}
}

function gwvpmini_getUser($username=null, $email=null, $id=null)
{
	$conn = gwvpmini_ConnectDB();

	if($username != null) {
		$res = $conn->query("select * from users where user_username='$username'");
	} else if($email != null) {
		$res = $conn->query("select * from users where user_email='$email'");
	} else if($id != null) {
		$res = $conn->query("select * from users where user_id='$id'");
	} else return false;

	$returns = false;
	foreach($res as $u_res) {
		$returns["id"] = $u_res["user_id"];
		$returns["fullname"] = $u_res["user_full_name"];
		$returns["password"] = $u_res["user_password"];
		$returns["username"] = $u_res["user_username"];
		$returns["email"] = $u_res["user_email"];
		$returns["desc"] = $u_res["user_desc"];
		$returns["status"] = $u_res["user_status"];
	}

	return $returns;

}


function gwvpmini_getRepo($ownerid=null, $name=null, $id=null)
{
	$conn = gwvpmini_ConnectDB();
	
	/*
	 * 	$reposql = '
	CREATE TABLE "repos" (
	"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"repos_name" TEXT,
	"repos_description" TEXT,
	"repos_owner" INTEGER,
	"repos_readperms" TEXT,
	UNIQUE(repos_name)
	)';

	 */

	if($ownerid != null) {
		$res = $conn->query("select * from repos where repos_owner='$ownerid'");
	} else if($name != null) {
		$res = $conn->query("select * from repos where repos_name='$name'");
	} else if($id != null) {
		$res = $conn->query("select * from repos where repos_id='$id'");
	} else return false;

	$returns = false;
	if(!$res) return false;
	foreach($res as $u_res) {
		$returns["id"] = $u_res["repos_id"];
		$returns["name"] = $u_res["repos_name"];
		$returns["desc"] = $u_res["repos_description"];
		$returns["ownerid"] = $u_res["repos_owner"];
		$returns["status"] = $u_res["repos_status"];
	}

	return $returns;

}

// $rid = repo id
// $uid = user id (a for "anyone", r for "registered")
// $acc = 0 or 1, 0 = no access, 1 = read access, 2 = write
// first part of ths is the "base" repo permissions
// this is spec'd as b:t where t = a (anyone can read), r (only registered can read) or x (explicit read perms)
function gwvpmini_ChangeRepoPerm($rid, $uid, $acc)
{
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select repos_perms from repos where repos_id='$rid'";
	
	$res = $conn->query($sql);
	
	error_log("CHANGEREPOPERMS: call with $rid, $uid, $acc");
	
	$cperms_t = "";
	foreach($res as $row) {
		$cperms_t = $row[0];
	}
	
	if($cperms_t === false) return false;
	
	$permsarray = array();
	if($uid != "b") {
		if($cperms_t == "") {
			$permsarray[$uid] = $acc;
		} else {
			$permsarray = unserialize(base64_decode($cperms_t));
			$permsarray[$uid] = $acc;
			if($acc == 0) {
				error_log("PERMSUPDATE: REMOVE $uid");
				unset($permsarray[$uid]);
			}
		}
	} else {
		error_log("CHANGEREPOPERMS for b of $acc");
		$permsarray["b"] = $acc;
	}
	
	// check if base is now r or a, we can drop any 1's
	if($permsarray["b"] == "a" || $permsarray["b"] == "r") {
		foreach($permsarray as $key => $val) {
			if($val == 1) {
				error_log("CHANGEREPOPERMS removed $key $val for base perm change");
				unset($permsarray[$key]);
			}
		}
	}
	
	if(is_array($permsarray)) {
		if(!isset($permsarray["b"])) {
			// something went wrong, repalce b bit
			$permsarray["b"] = "a";
		}
	} else {
		// something went even wronger
		$permsarray["b"] = "a";
	}
	

	$encperms = base64_encode(serialize($permsarray));
	
	$sql = "update repos set repos_perms='$encperms' where repos_id='$rid'";

	error_log("PERMSARRAYNOW $sql ".print_r($permsarray,true));
	
	$conn->query($sql);
	
}

function gwvpmini_GetRepoPerms($rid)
{
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select repos_perms from repos where repos_id='$rid'";
	
	$res = $conn->query($sql);
	
	$cperms_t = false;
	if($res !== false) foreach($res as $row) {
		$cperms_t = $row[0];
	}
	
	if($cperms_t === false) return false;
	
	$permsarray = unserialize(base64_decode($cperms_t));
	
	return $permsarray;
}

//returns 0 for none, 1 for read, 2 for writes
function gwvpmini_GetRepoPerm($rid, $uid)
{
	$conn = gwvpmini_ConnectDB();
	
	$dets = gwvpmini_getRepo(null, null, $rid);
	
	$sql = "select repos_perms from repos where repos_id='$rid'";
	
	$res = $conn->query($sql);
	
	$cperms_t = false;
	if($res !== false) foreach($res as $row) {
		$cperms_t = $row[0];
	}
	
	if($cperms_t === false) return 0;
	
	error_log("PERMSCHECK $rid, $uid:".print_r($dets, true));
	
	if($dets === false) return 0;
	
	if($dets["ownerid"] == $uid) return 2;
	
	$permsarray = unserialize(base64_decode($cperms_t));
	
	error_log("PERMSARRAY: ".print_r($permsarray,true));
	
	
	$perm = 0;
	if($uid != "a") {
		if(isset($permsarray[$uid])) {
			$perm = $permsarray[$uid];
		} else if($permsarray["b"] == "a" ||$permsarray["b"] == "r") {
			$perm = 1;
		}
	} else {
		if($permsarray["b"] == "a") $perm = 1;
	}
	
	return $perm;

}

function gwvpmini_RemoveRepoDB($id)
{
	$conn = gwvpmini_ConnectDB();
	
	if($id < 0) return;
	
	$sql = "delete from repos where repos_id='$id'";
	
	return $conn->query($sql);
	
}

function gwvpmini_RemoveUser($uid)
{
	$conn = gwvpmini_ConnectDB();
	
	if($uid < 0) return;
	
	$sql = "delete from users where user_id='$uid'";
	
	return $conn->query($sql);
}

function gwvpmini_DisableUser($uid)
{
	$conn = gwvpmini_ConnectDB();

	if($uid < 0) return;

	$sql = "update users set user_status=1 where user_id='$uid'";

	return $conn->query($sql);
}

function gwvpmini_UpdateRepoDescription($rid, $desc)
{
	$conn = gwvpmini_ConnectDB();
	
	// remove any tag elements
	$newdesc_r = preg_replace("/[\<\>\;]+/", "", $desc);
	$newdesc = preg_replace("/\n/", "<br>", $newdesc_r);
	
	$sql = "update repos set repos_description='$newdesc' where repos_id='$rid'";
	
	return $conn->query($sql);
}

function gwvpmini_EnableUser($uid)
{
	$conn = gwvpmini_ConnectDB();

	if($uid < 0) return;

	$sql = "update users set user_status=0 where user_id='$uid'";

	return $conn->query($sql);
}

function gwvpmini_DisableRepo($rid)
{
	$conn = gwvpmini_ConnectDB();
	
	if($rid < 0) return;
	
	$sql = "update repos set repos_status=1 where repos_id='$rid'";
	
	return $conn->query($sql);
}

function gwvpmini_EnableRepo($rid)
{
	$conn = gwvpmini_ConnectDB();

	if($rid < 0) return;
	
	$sql = "update repos set repos_status=0 where repos_id='$rid'";
	
	return $conn->query($sql);
}


function gwvpmini_ConnectDB()
{
	global $WEB_ROOT_FS, $BASE_URL, $data_directory, $db_type, $db_name, $DB_CONNECTION;

	// first check if $DB_CONNECTION IS live
	//error_log("in connection $db_type, $db_name");

	if($DB_CONNECTION != false) return $DB_CONNECTION;

	if($db_type == "sqlite") {
		$db_url = $db_name;
		if(!file_exists($db_name)) {
			//error_log("$db_name does not exist - problem");
			// TODO: NEED A SETUP AGENT!
			gwvpmini_dbCreateSQLiteStructure($db_name);
			gwvpmini_setConfigVal("repodir", "$data_directory/repos");
		}
	}

	// and here we go with pdo.
	//error_log("attmpting to open db, $db_type:$db_url");
	try {
		$DB_CONNECTION = new PDO("$db_type:$db_url");
	} catch(PDOException $exep) {
		error_log("execpt on db open");
		return false;
	}

	return $DB_CONNECTION;
}

function gwvpmini_UpdateStatusFromConfirm($confirmhash)
{
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select count(*) from users where user_status='2:$confirmhash'";
	
	$res = $conn->query($sql);
	
	if(!$res) return false;
	
	foreach($res as $row) {
		$retval = $row[0];
	}
	
	if($retval > 0) {
		$sql = "update users set user_status='0' where user_status='2:$confirmhash'";
		return $conn->query($sql);
	} else return false;
}


function gwvpmini_AddUser($username, $password, $fullname, $email, $desc, $level, $status)
{
	
	
	
	$conn = gwvpmini_ConnectDB();
	
	$sql = "insert into 'users' values ( null, '$fullname', '".sha1($password)."', '$username', '$email', '$desc', '$level', '$status')";
	
	$res = $conn->query($sql);
	if(!$res) return -1;
	
	$sql = "select user_id from users where user_username='$username'";
	$res = $conn->query($sql);
	if(!$res) return -1;
	
	$retval = -1;
	foreach($res as $row) {
		$retval = $row[0];
	}
	
	return $retval;
}


function gwvpmini_dbCreateSQLiteStructure($dbloc)
{
	$usersql = '
	CREATE TABLE "users" (
	"user_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"user_full_name" TEXT,
	"user_password" TEXT,
	"user_username" TEXT,
	"user_email" TEXT,
	"user_desc" TEXT,
	"user_level" TEXT,
	"user_status" TEXT,
	UNIQUE(user_username)
	)';

	$initialuser_admin = '
	insert into "users" values ( null, "Administrator", "'.sha1("password").'", "admin", "admin@localhost", "the admin", "1", "0");
	';

	$initialuser_user = '
	insert into "users" values ( null, "User", "'.sha1("password").'", "user", "user@localhost", "the user", "0", "0");
	';
	
	$reposql = '
	CREATE TABLE "repos" (
	"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"repos_name" TEXT,
	"repos_description" TEXT,
	"repos_owner" INTEGER,
	"repos_perms" TEXT,
	"repos_status" TEXT,
	UNIQUE(repos_name)
	)';

	// this looks like null, <repoid>, <read|visible|write>, user:<uid>|group:<gid>|authed|anon
	// where authed = any authenticated user, anon = everyone (logged in, not logged in, etc)
	// read|visible|write = can clone from repo|can see repo exists and see description but not clone from it|can push to repo
	// TODO: is this sufficient? i have to think about it

	$configsql = '
	CREATE TABLE "config" (
	"config_name" TEXT,
	"config_value" TEXT
	)';

	try {
		$DB_CONNECTION = new PDO("sqlite:$dbloc");
	} catch(PDOException $exep) {
		error_log("execpt on db open");
		return false;
	}

	$DB_CONNECTION->query($usersql);
	$DB_CONNECTION->query($initialuser_admin);
	$DB_CONNECTION->query($initialuser_user);
	$DB_CONNECTION->query($reposql);
	$DB_CONNECTION->query($configsql);
}

function gwvpmini_getConfigVal($confname)
{
	/*
	 * 	$configsql = '
	CREATE TABLE "config" (
			"config_name" TEXT,
			"config_value" TEXT
	)';

	*/

	$conn = gwvpmini_ConnectDB();

	$sql = "select config_value from config where config_name='$confname'";

	$res = $conn->query($sql);

	$return = null;
	foreach($res as $val) {
		$return = $val["config_value"];
	}

	return $return;
}

function gwvpmini_eraseConfigVal($confname)
{
	/*
	 * 	$configsql = '
	CREATE TABLE "config" (
			"config_name" TEXT,
			"config_value" TEXT
	)';

	*/

	$conn = gwvpmini_ConnectDB();

	$sql = "delete from config where config_name='$confname'";

	return $conn->query($sql);
}

function gwvpmini_GetRepoId($reponame)
{

	/*
	 * 	$reposql = '
	CREATE TABLE "repos" (
	"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"repos_name" TEXT,
	"repos_description" TEXT,
	"repos_owner" INTEGER
	)';

	 */
	
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select repos_id from repos where repos_name='$reponame'";
	
	$res = $conn->query($sql);
	
	$retval = -1;
	if(!$res) return -1;
	foreach($res as $row) {
		$retval = (int)$row[0];
	}
	
	return $retval;
}

function gwvpmini_GetRepoDescFromName($reponame)
{

	/*
	 * 	$reposql = '
	CREATE TABLE "repos" (
			"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
			"repos_name" TEXT,
			"repos_description" TEXT,
			"repos_owner" INTEGER
	)';

	*/

	$conn = gwvpmini_ConnectDB();

	$sql = "select repos_description from repos where repos_name='$reponame'";
	error_log("desc for name sql: $sql");

	$res = $conn->query($sql);

	$retval = -1;
	if(!$res) return -1;
	foreach($res as $row) {
		$retval = $row[0];
	}

	return $retval;
}

function gwvpmini_GetRepoOwnerDetailsFromName($reponame)
{

	/*
	 * 	$reposql = '
	CREATE TABLE "repos" (
			"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
			"repos_name" TEXT,
			"repos_description" TEXT,
			"repos_owner" INTEGER
	)';
	
		"user_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"user_full_name" TEXT,
	"user_password" TEXT,
	"user_username" TEXT,
	"user_email" TEXT,
	"user_desc" TEXT,
	"user_level" TEXT,
	"user_status" TEXT,
	UNIQUE(user_username)

	*/

	$conn = gwvpmini_ConnectDB();

	$sql = "select users.* from repos,users where repos_name='$reponame' and repos_owner=user_id";

	$res = $conn->query($sql);

	$retval = -1;
	if(!$res) return -1;
	foreach($res as $row) {
		$retval = array();
		error_log("STUFF2: ".print_r($row,true));
		$retval["id"] = $row["user_id"];
		$retval["fullname"] = $row["user_full_name"];
		$retval["username"] = $row["user_username"];
		$retval["email"] = $row["user_email"];
		$retval["desc"] = $row["user_desc"];
		$retval["level"] = $row["user_level"];
		$retval["status"] = $row["user_status"];
	}

	return $retval;
}

function gwvpmini_setConfigVal($confname, $confval)
{
	/*
	 * 	$configsql = '
	CREATE TABLE "config" (
			"config_name" TEXT,
			"config_value" TEXT
	)';

	*/
	gwvpmini_eraseConfigVal($confname);

	$conn = gwvpmini_ConnectDB();
	
	$sql = "delete from config where config_name='$confname'";
	$conn->query($sql);

	$sql = "insert into config values('$confname', '$confval')";

	return $conn->query($sql);
}

function gwvpmini_AddRepo($name, $desc, $ownerid)
{
	
	error_log("addrepo in db for $name, $desc, $ownerid");
	$conn = gwvpmini_ConnectDB();
	
	$perms["b"] = "a";
	
	$encperms = base64_encode(serialize($perms));
	
	$sql = "insert into repos values (null, '$name', '$desc', '$ownerid', '$encperms', 0)";
	
	$conn->query($sql);
}

function gwvpmini_GetUserId($username)
{
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select user_id from users where user_username='$username'";

	error_log("userid sql $sql");
	
	$res = $conn->query($sql);
	
	$retval = false;
	foreach($res as $row) {
		$retval = $row[0];
	}
	
	return $retval;
}

function gwvpmini_GetUserNameFromEmail($email)
{
	$conn = gwvpmini_ConnectDB();

	$sql = "select user_username from users where user_email='$email'";

	error_log("username sql $sql");

	$res = $conn->query($sql);

	$retval = false;
	foreach($res as $row) {
		$retval = $row[0];
	}

	return $retval;
}
function gwvpmini_GetOwnedRepos($username)
{
	/*
	 * 	CREATE TABLE "repos" (
	"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"repos_name" TEXT,
	"repos_description" TEXT,
	"repos_owner" INTEGER
	)';

	 */
	$conn = gwvpmini_ConnectDB();
	
	
	$uid = gwvpmini_GetUserId($username);
	$sql = "select * from repos where repos_owner='$uid'";
	error_log("owned repos sql $sql for username $username");
	$res = $conn->query($sql);
	if($username == "") return false;
	
	$retval = false;
	foreach($res as $row) {
		$id = $row["repos_id"];
		$retval[$id]["name"] = $row["repos_name"];
		$retval[$id]["desc"] = $row["repos_description"];
		$retval[$id]["id"] = $row["repos_id"];
		error_log(print_r($row, true));
	}
	
	error_log(print_r($retval, true));
	return $retval;
}

function gwvpmini_userLevel($id)
{
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select user_level from users where user_id='$id'";
	
	$res = $conn->query($sql);
	
	$lev = -1;
	if(!$res) return -1;
	foreach($res as $row) {
		$lev = (int)$row[0];
	}
	
	return $lev;
}

function gwvpmini_GetUsers($startat = 0, $num = 10)
{
	$conn = gwvpmini_ConnectDB();
	
	/*
	 * 	CREATE TABLE "users" (
	"user_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"user_full_name" TEXT,
	"user_password" TEXT,
	"user_username" TEXT,
	"user_email" TEXT,
	"user_desc" TEXT,
	"user_level" TEXT,
	"user_status" TEXT

	 */
	
	$sql = "select * from users where user_id>='$startat' order by user_id asc limit $num";
	
	$res = $conn->query($sql);
	
	$retval = false;
	foreach($res as $row) {
		$id = $row["user_id"];
		$retval[$id]["fullname"] = $row["user_full_name"];
		$retval[$id]["username"] = $row["user_username"];
		$retval[$id]["email"] = $row["user_email"];
		$retval[$id]["desc"] = $row["user_desc"];
		$retval[$id]["level"] = $row["user_level"];
		$retval[$id]["status"] = $row["user_status"];
		$retval[$id]["id"] = $row["user_id"];
	}
	
	return $retval;
}

function gwvpmini_findPeopleLike($search)
{
	$conn = gwvpmini_ConnectDB();
	
	$sql = "select * from users where user_username like '%$search%' or user_full_name like '%$search%'";
	
	$res = $conn->query($sql);
	
	if(!$res) {
		error_log("SERACHUSER: $sql returned false");
		return false;
	} else {
		error_log("SERACHUSER: $sql returned true");
	}
	
	$retval = false;
	foreach($res as $row) {
		$id = $row["user_id"];
		$retval[$id]["fullname"] = $row["user_full_name"];
		$retval[$id]["username"] = $row["user_username"];
		$retval[$id]["email"] = $row["user_email"];
		$retval[$id]["desc"] = $row["user_desc"];
		$retval[$id]["level"] = $row["user_level"];
		$retval[$id]["status"] = $row["user_status"];
		$retval[$id]["id"] = $row["user_id"];
	}
	
	error_log("SEARCHUSER: array is ".print_r($retval, true));
	return $retval;
}

function gwvpmini_GetRepos($startat=0, $num=200)
{
	$conn = gwvpmini_ConnectDB();
	
	/*
	 * 	CREATE TABLE "repos" (
	"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"repos_name" TEXT,
	"repos_description" TEXT,
	"repos_owner" INTEGER
	)';
	
	 		*/
	
	$sql = "select * from repos where repos_id > '$startat' order by repos_id asc limit $num";
	
	$res = $conn->query($sql);
	
	$retval = false;
	foreach($res as $row) {
		$id = $row["repos_id"];
		$retval[$id]["name"] = $row["repos_name"];
		$retval[$id]["desc"] = $row["repos_description"];
		$retval[$id]["owner"] = $row["repos_owner"];
		$retval[$id]["id"] = $row["repos_id"];
		$retval[$id]["status"] = $row["repos_status"];		
	}
	
	return $retval;
	
	
}

function gwvpmini_findReposLike($search)
{
	$conn = gwvpmini_ConnectDB();
	
	/*
	 * 	CREATE TABLE "repos" (
	"repos_id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"repos_name" TEXT,
	"repos_description" TEXT,
	"repos_owner" INTEGER
	)';
	
	 		*/
	
	$sql = "select * from repos where repos_name like '%$search%' or repos_description like '%$search%'";
	
	$res = $conn->query($sql);
	
	if(!$res) return false;
	
	$retval = false;
	foreach($res as $row) {
		$id = $row["repos_id"];
		$retval[$id]["name"] = $row["repos_name"];
		$retval[$id]["desc"] = $row["repos_description"];
		$retval[$id]["owner"] = $row["repos_owner"];
		$retval[$id]["id"] = $row["repos_id"];
		$retval[$id]["status"] = $row["repos_status"];
	}
	
	return $retval;
	
}

function gwvpmini_GetNRepos()
{
	$conn = gwvpmini_ConnectDB();

	$sql = "select count(*) from repos";
	
	$res = $conn->query($sql);
	
	$retval = -1;
	foreach($res as $row) {
		$retval = $row[0];
	}
	
	return $retval;
}


function gwvpmini_GetNUsers()
{
	$conn = gwvpmini_ConnectDB();

	$sql = "select count(*) from users";

	$res = $conn->query($sql);

	$retval = -1;
	foreach($res as $row) {
		$retval = $row[0];
	}

	return $retval;
}


?>