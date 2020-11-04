<?php
include("common.php");
include("request.php");

if (preg_match("(resources\/admin(\/\?name=(\w)+)?$)", "resources/admin/?name=boby")) {
   echo("it works");
} else {
   echo("it doesnt work");
}

?>
