<?php
include("../common.php");
include("../request.php");
include("../HttpMultipartParser.php");
include("../functions.php");
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = substr($uri, strpos($uri, "admin.php/"));
$uri = substr($uri, strpos($uri, "/") + 1);
$main = $uri;
$uri = explode("/", $uri);


$endpoint_map = new RequestMap();
$method = $_SERVER["REQUEST_METHOD"];

$netid = "roseann";

if (isset($_SERVER['REMOTE_USER'])) {
   $netid = $_SERVER["REMOTE_USER"];
}

build_endpoint_map($endpoint_map);
try {
   call_user_func($endpoint_map->get($main, $method), $uri, $netid);
} catch(Exception $e) {
   invalid_request($e);
}

/**
  * Creates all of the endpoint to valid request methods and their specified function mappings
*/
function build_endpoint_map($map) {
   $map->put("(resources\/\d+$)", array("PUT" => 'update_resource_request',
                                        "DELETE" => 'remove_resource_request'));
   $map->put("(resources\/admin(\/|)$)", array("GET" => 'get_all_resources_request'));
   $map->put("(resources\/tags$)", array("PUT" => 'update_resource_tags_request'));
   $map->put("(resources\/\d+\/status\/(APPROVED)$)", array("PUT" => 'approve_resource_request'));
   $map->put("(resources\/\d+\/status\/(STANDBY)$)", array("PUT" => 'standby_resource_request'));
   $map->put("(resources\/\d+\/expire\/20[2-3]\d-(0[1-9]|1[0-2])-([0-2][0-9]|3[0-1])$)",
                array("PUT" => 'resource_expire_request'));
   $map->put("(categories$)", array("GET" => 'get_categories_request'));

}

/**
  * Handles the request to update the information of a resource
  * Needed URI params: $resource_id
  * All PUT params are not needed
  * Must be an admin
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
  * @param {PUT} $name - The new name for the resource
  * @param {PUT} $description - The new description for the resource
  * @param {PUT} $link - The new link for the resource
  * @param {PUT} $icon - The new icon for the resource
  * @param {PUT} $expire - The new expiration date for the resource
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF NOT AN ADMIN, SENDS INVALID REQUEST
*/
function update_resource_request($uri, $user) {
   $_PUT = trim_endings(HttpMultipartParser::parse_stdin()["variables"]);
   $db = get_PDO();
   try {
      if (is_admin($user)) {
         if (is_valid_resource_id($db, $uri[1])) {
            $resource_id = $uri[1];
            update_resource($db, $resource_id, $_PUT);
            $outcome = update_resource_tags($db, $resource_id, $_PUT);
            if ($outcome) {
               success("Successfully updated resource.");
            } else {
               db_error();
            }
         } else {
            invalid_request(RESOURCE_ID_ERROR);
         }
      } else {
         invalid_request(ADMIN_ERROR);
      }
   } catch (PDOException $ex) {
      db_error($ex);
   }
}

/**
  * Handles the request to remove a resource from the database
  * Must be an admin to use
  * Must have a URL parameter of the resourceID to remove
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function remove_resource_request($uri, $user) {
   $db = get_PDO();
   if (is_admin($user)) {
      if (isset($uri[1]) && is_valid_resource_id($db, $uri[1])) {
         $resource_id = $uri[1];
         try {
            remove_resource_tags($db, $resource_id);
            remove_resource($db, $resource_id);
            success("Successfully deleted resource.");
         } catch(PDOException $ex) {
            db_error($ex);
         }
      } else {
         invalid_request(RESOURCE_ID_ERROR);
      }
   } else {
      invalid_request(ADMIN_ERROR);
   }
}

/**
  * Handles the request to retrieve all resources
  * Must be an admin
  * @param {String[]} $uri - The array of strings
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF NOT AN ADMIN, SENDS INVALID REQUEST
*/
function get_all_resources_request($uri, $user) {
   $db = get_PDO();
   if (is_admin($user)) {
      $name ="";
      remove_old_entries($db);
      if (isset($_GET["name"])) {
         $name = $_GET["name"];
      }
      try {
         $data = get_all_resources($db, $name);
         for ($i = 0; $i < count($data); $i++) {
            $data[$i]["tags"] = get_resource_tags($db, $data[$i]["id"]);
         }
         header("Content-type: application/json");
         echo(json_encode($data));
      } catch (PDOException $ex) {
         db_error();
      }
   } else {
      invalid_request(ADMIN_ERROR);
   }
}

/**
  * Handles the request to update the associated tags of a resource
  * Must be an admin
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
  * @param {PUT} $id - The resource_ID to update tags for
  * @param {PUT} $add - The tags to add to the resource
  * @param {PUT} $remove - The tags to remove from the resource
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF NOT AN ADMIN, SENDS INVALID REQUEST
*/
function update_resource_tags_request($uri, $user) {
   $_PUT = parse_body_data(file_get_contents("php://input"));
   $db = get_PDO();
   if (is_admin($user)) {
      if (isset($_PUT["id"]) && (isset($_PUT["add"]) || isset($_PUT["remove"]))) {
         try {
            $resource_id = $_PUT["id"];
            $categories_to_add = array();
            $categories_to_remove = array();
            if (isset($_PUT["add"])) {
               $categories_to_add = json_decode($_PUT["add"]);
            }
            if (isset($_PUT["remove"])) {
               $categories_to_remove = json_decode($_PUT["remove"]);
            }

            $outcome = true;
            if (!empty($categories_to_add)) {
               $outcome = $outcome && add_tags($db, $resource_id, $categories_to_add);
            }
            if (!empty($categories_to_remove)) {
               $outcome = $outcome && remove_tags($db, $resource_id, $categories_to_remove);
            }

            if ($outcome) {
               success("Successfully updated resource tags.");
            } else {
               invalid_request("Please make sure that the resource ID and category " .
                               "IDs provided are valid.");
            }
         } catch (PDOException $ex) {
            db_error($ex);
         }
      } else {
         invalid_request("I need a valid ID, add, and remove values. Please see " .
                         "the documentation for more information.");
      }
   } else {
      invalid_request(ADMIN_ERROR);
   }
}

/**
  * Handles the request to retrieve all of the categories from the database
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function get_categories_request($uri, $user) {
   try {
      $db = get_PDO();
      $data = get_categories($db);
      header("Content-type: application/json");
      echo(json_encode($data));
   } catch (PDOException $ex) {
      db_error();
   }
}

/**
  * Verifies the inputs required to approve a new resource
  * Must have provided a resourceID and it must be valid and not have been already approved
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF ALL REQUIRED INPUTS ARE NOT PROVIDED, SENDS INVALID REQUEST
*/
function approve_resource_request($uri, $user) {
   if (is_admin($user)) {
      $db = get_PDO();
      if (isset($uri[1]) && is_valid_resource_id($db, $uri[1])) {
         $resource_id = $uri[1];
         try {
            $outcome = update_resource_status($db, $resource_id, APPROVE);
            if ($outcome) {
               success("Successfully approved resource.");
            } else {
               invalid_request(RESOURCE_UPDATE_ERROR);
            }
         } catch (PDOException $ex) {
            db_error();
         }
      } else {
         invalid_request(RESOURCE_ID_ERROR);
      }
   }
}

/**
  * Verifies the inputs required to standby a new resource
  * Must have provided a resourceID and it must be valid and not have been already put on standby
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF ALL REQUIRED INPUTS ARE NOT PROVIDED, SENDS INVALID REQUEST
*/
function standby_resource_request($uri, $user) {
   $db = get_PDO();
   if (is_admin($user)) {
      if (isset($uri[1]) && is_valid_resource_id($db, $uri[1])) {
         $resource_id = $uri[1];
         try {
            $outcome = update_resource_status($db, $resource_id, STANDBY);
            if ($outcome) {
               success("Successfully put resource on standby.");
            } else {
               invalid_request(RESOURCE_UPDATE_ERROR);
            }
         } catch (PDOException $ex) {
            db_error();
         }
      } else {
         invalid_request(RESOURCE_ID_ERROR);
      }
   }
}

/**
  * Handles the request to update/add an expiration date to a resource
  * Expects a URL parameter of the resourceID and the date for it to expire
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function resource_expire_request($uri, $user) {
   $db = get_PDO();
   if (is_admin($user)) {
      $resource_id = $uri[1];
      $expire = $uri[3];
      try {
         $outcome = update_resource_expire($db, $resource_id, $expire);
         if ($outcome) {
            success("Successfully updated expiration date of resource.");
         } else {
            invalid_request("Please make sure the provided resource ID is valid and " .
                            "the expiration date has not already been set to the provided value.");
         }
      } catch(PDOException $ex) {
         db_error();
      }
   } else {
      invalid_request(ADMIN_ERROR);
   }
}
?>
