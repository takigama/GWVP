<?php


if($IS_WEB_REQUEST) {
	$reg = gwvpmini_getConfigVal("activityloglength");
	
	$activity_log_length = false;
	if($reg == null) {
		gwvpmini_setConfigVal("activityloglength", "100");
	} else if($reg == 1) {
		$activityloglength = true;
	} else {
		$activityloglength = false;
	}
}	


// this will add a repo activity (a commit for eg)
// and auto populate the field in the activity
// log
function gwvpmini_AddActivityForRepo($desc, $userbyid)
{
	
}

// gets the activity log as it would be viewed by
// the user id of "$forid"
function gwvpmini_GetActivityLogFor($forid)
{
	
}

?>