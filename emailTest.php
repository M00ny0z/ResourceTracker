<?php
if (isset($_SERVER["REMOTE_USER"])) {
   echo("user:" . $_SERVER["REMOTE_USER"]);
} else {
   echo("no user found.");
}

print_r($_SERVER);
?>
