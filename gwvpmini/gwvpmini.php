<?php

require_once("gwvpmini_web.php");
require_once("gwvpmini_auth.php");
require_once("gwvpmini_db.php");
require_once("gwvpmini_setup.php");
require_once("gwvpmini_gitrepo.php");
require_once("gwvpmini_search.php");
require_once("gwvpmini_gitbackend.php");
require_once("gwvpmini_view.php");
require_once("gwvpmini_activity.php");
require_once("gwvpmini_register.php");
require_once("gwvpmini_user.php");
require_once("gwvpmini_chat.php");
if($IS_WEB_REQUEST) {
	if(gwvpmini_isLoggedIn()) if(gwvpmini_isUserAdmin()) {
		require_once("gwvpmini_admin.php");
	}
} else {
	require_once("gwvpmini_admin.php");
}

?>