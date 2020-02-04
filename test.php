<?php
include("common.php");
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = substr($uri, strpos($uri, "test.php/"));
$uri = substr($uri, strpos($uri, "/") + 1);
$uri = explode("/", $uri);
$main = array_shift($uri) . "/" . array_shift($uri);
$user = "em66@uw.edu";
// NOTE: ALL VERIFY FUNCTIONS MUST TAKE THE URI AND THE USER WHO MADE THE REQUEST

$method = $_SERVER["REQUEST_METHOD"];

$endpoint_map = create_endpoint_map();

if (array_key_exists($main, $endpoint_map)) {
   if (array_key_exists($method, $endpoint_map[$main])) {
      call_user_func($endpoint_map[$main][$method], $uri, $user);
   } else {
      invalid_request("This operation for this endpoint is not currently supported.");
   }
} else {
   invalid_request("This endpoint does not currently exist.");
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
  * @return {<REQUEST_ENDPOINT, <REQUEST_METHOD, callback>>} - An associative array, from the
  *                                                            request endpoint to a map of the
  *                                                            request method to the corresponding
  *                                                            function to call
*/
function create_endpoint_map() {
   $endpoint_map = array();
   $endpoint_map["resources/approve"] = array("PUT" => 'verify_approve_resource');
   $endpoint_map["resources/standby"] = array("PUT" => 'verify_standby_resource');
   $endpoint_map["resources/admin"] = array("GET" => 'verify_get_all_resources');
   $endpoint_map["resources/tag"] = array("PUT" => 'verify_update_resource_tags');
   $endpoint_map["resources"] = array("POST" => 'verify_add_resource',
                                       "DELETE" => 'verify_remove_resource');

   $endpoint_map["categories"] = array("GET" => 'verify_get_categories',
                                        "POST" => 'verify_add_category',
                                        "DELETE" => 'verify_remove_category');

   $endpoint_map["users/block"] = array("PUT" => 'verify_block_user');
   $endpoint_map["users/unblock"] = array("PUT" => 'verify_unblock_user');
   return $endpoint_map;
}

/**
  * Verifies the inputs required to approve a new resource
  * Must have provided a resourceID and it must be valid and not have been already approved
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF ALL REQUIRED INPUTS ARE NOT PROVIDED, SENDS INVALID REQUEST
*/
function verify_approve_resource($uri, $user) {
   $db = get_PDO();
   if (isset($uri[0]) && is_valid_resource_id($db, $uri[0])) {
      $resource_id = $uri[0];
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
      invalid_request("I need a valid resource ID for this operation on this endpoint.");
   }
}

/**
  * Verifies the inputs required to standby a new resource
  * Must have provided a resourceID and it must be valid and not have been already put on standby
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF ALL REQUIRED INPUTS ARE NOT PROVIDED, SENDS INVALID REQUEST
*/
function verify_standby_resource($uri, $user) {
   $db = get_PDO();
   if (isset($uri[0]) && is_valid_resource_id($db, $uri[0])) {
      $resource_id = $uri[0];
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
      invalid_request("I need a valid resource ID for this operation on this endpoint.");
   }
}

/**
  * Verifies the request to retrieve all resources
  * Must be an admin
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF NOT AN ADMIN, SENDS INVALID REQUEST
*/
function verify_get_all_resources($uri, $user) {
   $db = get_PDO();
   if (is_admin($user)) {
      try {
         $data = get_all_resources($db);
         header("Content-type: application/json");
         echo(json_encode($data));
      } catch (PDOException $ex) {
         db_error();
      }
   } else {
      invalid_request("You need to be an admin to access this resource.");
   }
}

function verify_update_resource_tags($uri, $user) {

}

function verify_add_resource($uri, $user) {

}

function verify_remove_resource($uri, $user) {

}

function verify_get_categories($uri, $user) {

}

function verify_add_category($uri, $user) {

}

function verify_remove_category($uri, $user) {

}

function verify_block_user($uri, $user) {
   echo("User has been blocked");
}

function verify_unblock_user($uri, $user) {

}

function is_admin($db, $netid) {
   return true;
}

function is_valid_resource_id($db, $id) {
   return check_exists($db, "resource", "id", $id);
}

/**
  * Adds a new netid to the block list so that they cannot submit any more resources
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String} netid - The netid to add to the block list
  * @return {Boolean} - TRUE if adding netid to block list was successful, FALSE otherwise
*/
function block_netid($db, $netid) {
   $query = "INSERT INTO blocked VALUES (:netid);";
   $stmt = $db->prepare($query);
   $params = array("netid" => $netid);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Removes a netid from the block list
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String} netid - The netid to remove from the block list
  * @return {Boolean} - TRUE if removing netid from block list was successful, FALSE otherwise
*/
function unblock_netid($db, $netid) {
   $query = "DELETE FROM blocked WHERE netid = :netid;";
   $stmt = $db->prepare($query);
   $params = array("netid" => $netid);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Verifies the provided details of a supposed resource to submit
  * @param {String} name - The name of the resource to verify
  * @param {String} link - The link to the resource to verify
  * @param {String} description - The description of the resource to verify
  * @param {String} icon - The icon of the resource to verify
  * @return {Boolean} - TRUE if all provided values are valid, FALSE otherwise
*/
function verify_resource_info($name, $link, $description, $icon, $tags) {
   return true;
}

/**
  * Queries the database to get all resources
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @return {JSON[]} - A JSON array, each item being a resource which includes
  *                    its ID, name, link, description, icon, status, and user
*/
function get_all_resources($db) {
   $query = "SELECT * FROM resource;";
   $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
   return $data;
}


/**
  * Queries the database to get all categories
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @return {JSON[]} - A JSON array, each item being a category which includes its name and ID
*/
function get_categories($db) {
   $query = "SELECT * FROM category;";
   $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
   return $data;
}

/**
  * Queries the database to remove a category from the database
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String} category - The categoryID to remove
  * @return {Boolean} - TRUE if category was removed, FALSE otherwise.
*/
function remove_category($db, $category) {
   $params = array("id" => $category);
   remove_category_tags($db, $params);
   $query = "DELETE FROM category WHERE id = :id;";
   $stmt = $db->prepare($query);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Queries the database to remove all tag-connections with a specified category
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String[]} category - An associative array, must have key "id" and value of category to
  *                              remove
*/
function remove_category_tags($db, $category) {
   $query = "DELETE FROM tag WHERE category_id = :id;";
   $stmt = $db->prepare($query);
   $stmt->execute($category);
}

/**
  * Queries the database to remove all tag-connections with a specified category and a specified
  * resource
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource - The
  * @param {String/int[]} categories - Each item should be a categoryID, should be at least one item
*/
function remove_tags($db, $resource, $categories) {
   $query = "DELETE FROM tag WHERE resource_id = :id AND category_id IN " .
            build_categories_string($categories);
   $stmt = $db->prepare($query);
   $stmt->execute(array_merge([$resource], $categories));
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Queries the database to get all of the currently approved resources
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource - The ID of the resource to add tags to
  * @param {String/int []} categories - The category tags to add to the resource
  * @return {Boolean} - TRUE if category was added, FALSE otherwise.
*/
function get_approved_resources($db, $categories = "") {
   $status = APPROVE;
   $query = "SELECT DISTINCT r.id, r.name, r.link, r.description, r.icon " .
            "FROM resource r ";
   $data;
   if ($categories != "") {
      // IF FILTERING BY CATEGORIES
      $query = $query . ", tag t " .
      $query =          "WHERE r.status = '{$status}' AND t.resource_id = r.id AND " .
                        "t.category_id IN " . build_categories_string($categories);
      $stmt = $db->prepare($query);
      $stmt->execute($categories);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      $stmt = null;
   } else {
      // ELSE GET ALL OF THEM
      $query = $query . "WHERE r.status = '{$status}'";
      $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
   }
   return $data;
}

/**
  * Queries the database to add category tags to a resource
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource - The ID of the resource to add tags to
  * @param {String/int []} categories - The category tags to add to the resource
  * @return {Boolean} - TRUE if category was added, FALSE otherwise.
*/
function add_tags($db, $resource, $categories) {
   $query = "INSERT INTO tag(resource_id, category_id) " .
            "SELECT r.id, c.id " .
            "FROM resource r, category c " .
            "WHERE r.id = ? AND c.id IN ";
   $query = $query . build_categories_string($categories);
   $stmt = $db->prepare($query);
   $stmt->execute(array_merge([$resource], $categories));
   $result = $stmt->rowCount() > 0;
   return $result;
}

/**
  * Queries the database to delete a resource
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} id - The ID of the resource to remove
  * @return {Boolean} - TRUE if category was added, FALSE otherwise.
*/
function remove_resource($db, $id) {
   $query = "DELETE FROM resource WHERE id = :id;";
   $stmt = $db->prepare($query);
   $params = array("id" => $id);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $stmt;
}

/**
  * Queries the database to update the status of a resource
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} id - The ID of the resource to update
  * @param {String} status - The status to set the resource to
  * @return {Boolean} - TRUE if category was added, FALSE otherwise.
*/
function update_resource_status($db, $id, $status) {
   $query = "UPDATE resource SET status = :status WHERE id = :id;";
   $stmt = $db->prepare($query);
   $params = array("status" => $status,
                   "id" => $id);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Queries the database to add a new category
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String} name - The name of the category to add
  * @return {Boolean} - TRUE if category was added, FALSE otherwise.
*/
function add_category($db, $name) {
   $query = "INSERT INTO category(name) VALUES (:name);";
   $stmt = $db->prepare($query);
   $params = array("name" => $name);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Queries the database to add a new resource
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String} name - The name of the resource to add
  * @param {String} link - The link of the website of the resource
  * @param {String} desc - The description of the resource
  * @param {String} icon - The name of the icon to represent the resource
  * @param {String} user - The netid of the user that is submitting this resource
  * @return {Boolean} - TRUE if resource was added, FALSE otherwise.
*/
function add_resource($db, $name, $link, $desc, $icon, $user) {
   $query = "INSERT INTO resource(name, link, description, icon, user) VALUES " .
            "(:name, :link, :description, :icon, :user);";
   $stmt = $db->prepare($query);
   $params = array("name" => $name,
                   "link" => $link,
                   "description" => $desc,
                   "icon" => $icon,
                   "user" => $user);
   $stmt->execute($params);
   $stmt->closeCursor();
   $stmt = null;
}

/**
  * Builds a string of question marks in parenthesis, one question mark for each category
  * THERE MUST BE AT LEAST ONE CATEGORY IN THE ARRAY PROVIDED
  * @param {String/int []} categories - The category tags to build a SQL string for
  * @return {String} - The built escape string
*/
function build_categories_string($categories) {
   $category_list = "(?";
   for ($i = 1; $i < count($categories); $i++) {
      $category_list = $category_list . ", ?";
   }
   $category_list = $category_list . ");";
   return $category_list;
}

?>
