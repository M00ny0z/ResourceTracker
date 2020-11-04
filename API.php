<?php
include("common.php");
include("request.php");
include("HttpMultipartParser.php");
include("functions.php");
header("Access-Control-Allow-Origin: *");
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = substr($uri, strpos($uri, "API.php/"));
$uri = substr($uri, strpos($uri, "/") + 1);
$main = $uri;
$uri = explode("/", $uri);

$endpoint_map = new RequestMap();
$method = $_SERVER["REQUEST_METHOD"];

$netid = "";

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
  * Parses through the body data and stores the key-value pairs
  * NOTE: NO KEY OR VALUE CAN HAVE A NAME OF 'name'
  * @param {String} data - The body data in one string
  * @return {String[]} - An associative array, the key-values being the body-data key-value
  *                      pairs
*/
function parse_body_data($data) {
   $output = array();
   while(strlen($data) > 0 && strpos($data, "name=")) {
      $data = substr($data, strpos($data, "="));
      $key = substr($data, 2);
      $key = substr($key, 0, strpos($key, "\""));
      $data = substr($data, strpos($data, "\n") + 3);
      $value = substr($data, 0, strpos($data, "\n"));
      $data = substr($data, strpos($data, "\n"));
      $output[$key] = $value;
   }
   return $output;
}

/**
  * Creates all of the endpoint to valid request methods and their specified function mappings
*/
function build_endpoint_map($map) {
   $map->put("((resources\/\?*(categories\[\]=\d+)*(&categories\[\]=\d+)*&*(name=\w+)*$))",
                array("GET" => 'get_resources_by_name_request'));

   $map->put("(categories$)", array("GET" => 'get_categories_request'));
}

/**
  * Handles the request to retrieve all of the approved resources from the database
  * Yes I know thiss is very similar to the normal one but I dont have time to optimize
  * Has the option of a GET parameter of 'categories' to specify approved resources from only those
  *    categories
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
  * @param {GET} categories - The resource categories to limit by
*/
function get_resources_by_name_request($uri, $user) {
   try {
      $db = get_PDO();
      remove_old_entries($db);
      $data;
      $name;
      if (isset($_GET["name"])) {
         $name = $_GET["name"];
      } else  {
         $name = "";
      }
      if (isset($_GET["categories"])) {
         $data = get_approved_resources_by_name($db, $name, $_GET["categories"]);
      } else {
         $data = get_approved_resources_by_name($db, $name);
      }
      header("Content-type: application/json");
      echo(json_encode($data));
   } catch (PDOException $ex) {
      db_error($ex);
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


?>
