<?php

$input = "i'm a looking at the rail:road, if you get me";

$inp = preg_replace("/[^a-zA-Z0-9 ]+/", "", $input);

echo "inp is $inp\n";

$res = explode(" ", $inp);

echo "res is ".print_r($res, true);
?>