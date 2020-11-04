<?php
include("common.php");
$netid = "";

if ($netid == null ) {
   $db = get_PDO();
   if (is_admin($db)) {
      
   } else {
      invalid_request("You need to be an admin to access this resource.");
   }
} else {
   invalid_request("You need to login to access this resource.");
}
?>
