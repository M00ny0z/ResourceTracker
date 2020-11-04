<?php
include("../common.php");
include("../request.php");
include("../HttpMultipartParser.php");
include("../functions.php");
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = substr($uri, strpos($uri, "API.php/"));
$uri = substr($uri, strpos($uri, "/") + 1);
$main = $uri;
$uri = explode("/", $uri);

$endpoint_map = new RequestMap();
$method = $_SERVER["REQUEST_METHOD"];

$netid = "";

if (isset($_SERVER["REMOTE_USER"])) {
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
   $map->put("(resources$)", array("POST" => 'create_resource_request'));
   $map->put("(categories$)", array("GET" => 'get_categories_request'));
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
  * Handles the request to add a new resource to the database
  * Resource added is put on standby
  * Must be an admin to use
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
  * @param {POST} name - The name of the resource
  * @param {POST} link - The link to the resource
  * @param {POST} description - The description of the resource
  * @param {POST} icon - The icon of the resource
  * @param {POST} tags - The associated tags for the resource IF APPLICABLE
  * @param {POST} categories - The custom categories to add to the database and to the resource
  *                            IF APPLICABLE
*/
function create_resource_request($uri, $user) {
   $db = get_PDO();
   try {
      if (isset($_POST["description"]) && isset($_POST["name"]) && isset($_POST["link"])
         && isset($_POST["icon"])) {
         $name = $_POST["name"];
         $desc = $_POST["description"];
         $link = $_POST["link"];
         $icon = $_POST["icon"];
         add_resource($db, $name, $link, $desc, $icon, $user);
         $resource_id = $db->lastInsertId();
         if (isset($_POST["expire"])) {
            add_expire_to_resource($db, $resource_id, $_POST["expire"]);
         }

         if (isset($_POST["tags"])) {
            add_tags($db, $resource_id, $_POST["tags"]);
         }

         if (isset($_POST["categories"])) {
            add_categories($db, $_POST["categories"], $resource_id);
         }

         success("Successfully added resource.");
      } else {
         invalid_request("I need a valid resource name, link, description, icon, and any tags.");
      }
   } catch (PDOException $ex) {
      db_error($ex);
   }
}
?>
