<?php


echo "check: ".inputcheck("stuff.git")."\n";
echo "check: ".inputcheck("stuff")."\n";
echo "check: ".inputcheck("stu!@#$@#ff")."\n";
echo "check: ".inputcheck("stuff")."\n";
echo "check: ".inputcheck("stuff")."\n";
echo "check: ".inputcheck("stuff")."\n";

function inputcheck($in)
{
	$replcheck = preg_replace("/[a-zA-Z0-9_\-\.]*/", "", $in);
	if(strlen($replcheck)>0) {
		$inputcheck = false;
		return "failed repl check ($in)";
	}
		
	return $in;
}

?>