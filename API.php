<?php
include("common.php");
include("request.php");
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri = substr($uri, strpos($uri, "test.php/"));
$uri = substr($uri, strpos($uri, "/") + 1);
$main = $uri;
$uri = explode("/", $uri);
$user = "em66@uw.edu";

$endpoint_map = new RequestMap();
$method = $_SERVER["REQUEST_METHOD"];

build_endpoint_map($endpoint_map);

try {
   call_user_func($endpoint_map->get($main, $method), $uri, $user);
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
   $map->put("(resources$)", array("POST" => 'create_resource_request'));
   $map->put("(resources\/\d+$)", array("PUT" => 'approve_resource_request'));
   $map->put("(resources\/admin$)", array("GET" => 'get_all_resources_request'));
   $map->put("(resources\/tags$)", array("PUT" => 'update_resource_tags_request'));
   $map->put("(resources\/\d+\/status\/(APPROVED)$)", array("PUT" => 'approve_resource_request'));
   $map->put("(resources\/\d+\/status\/(STANDBY)$)", array("PUT" => 'standby_resource_request'));
   $map->put("((resources\/\?*(categories\[\]=\d)*(&categories\[\]=\d)*$))",
                array("GET" => 'get_resources_request'));
   $map->put("(resources\/\d+\/expire\/20[2-3]\d-(0[1-9]|1[0-2])-([0-2][0-9]|3[0-1])$)",
                array("PUT" => 'resource_expire_request'));

   $map->put("(categories$)", array("GET" => 'get_categories_request',
                                    "POST" => 'add_category_request'));

   $map->put("(categories\/\d+$)", array("DELETE" => 'remove_category_request'));
   $map->put("(categories\/\d+\/status\/APPROVE$)", array("PUT" => 'approve_category_request'));

   $map->put("(users\/([a-z]|[A-Z]|\d)+\/access\/BLOCK$)", array("PUT" => 'block_user_request'));
   $map->put("(users\/([a-z]|[A-Z]|\d)+\/access\/UNBLOCK$)", array("PUT" => 'unblock_user_request'));
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

/**
  * Adds an expiration date to the resource
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource_id - The ID of the resource
  * @param {String} expire - The expiration date of resource
  * @return {Boolean} - TRUE if a resource was updated with an expiration date, FALSE otherwise
*/
function add_expire_to_resource($db, $resource_id, $expire) {
   $query = "UPDATE resource SET expire = :expire WHERE id = :id";
   $stmt = $db->prepare($query);
   $params = array("expire" => $expire,
                   "id" => $resource_id);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Adds a list of categories to the database and makes them unnapproved
  * Also tags the categories to the resource
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String[]} categories - The categories to add the database
*/
function add_categories($db, $categories, $resource_id) {
   $categories_added = array();
   for ($i = 0; $i < count($categories); $i++) {
      $query = "INSERT INTO category(name) SELECT :category";
      $stmt = $db->prepare($query);
      $stmt->execute(["category" => $categories[$i]]);
      array_push($categories_added, $db->lastInsertId());
   }

   $unapprove_query = "INSERT INTO unapproved_category VALUES ";
   $unapprove_query = $unapprove_query . "(?) ";
   for ($i = 1; $i < count($categories_added); $i++) {
      $unapprove_query = $unapprove_query . ", (?)";
   }
   $stmt = $db->prepare($unapprove_query);
   $stmt->execute($categories_added);

   $tags_query = "INSERT INTO tag VALUES (?, {$resource_id}) ";
   for ($i = 1; $i < count($categories_added); $i++) {
      $tags_query = $tags_query . ", (?, {$resource_id})";
   }
   $stmt = $db->prepare($tags_query);
   $stmt->execute($categories_added);
}

/**
  * Verifies the inputs required to approve a new resource
  * Must have provided a resourceID and it must be valid and not have been already approved
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF ALL REQUIRED INPUTS ARE NOT PROVIDED, SENDS INVALID REQUEST
*/
function approve_resource_request($uri, $user) {
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
function standby_resource_request($uri, $user) {
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
  * Handles the request to retrieve all resources
  * Must be an admin
  * @param {String[]} $uri - The array of strings
  * THROWS PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * IF NOT AN ADMIN, SENDS INVALID REQUEST
*/
function get_all_resources_request($uri, $user) {
   $db = get_PDO();
   if (is_admin($db, $user)) {
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
   if (is_admin($db, $user)) {
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
      invalid_request("You must be an admin to utilize this endpoint.");
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
*/
function add_resource_request($uri, $user) {
   $db = get_PDO();
   if (is_admin($db, $user)) {
      if (isset($_POST["name"]) && isset($_POST["link"]) && isset($_POST["desc"]) &&
         isset($_POST["icon"])) {
         $name = $_POST["name"];
         $link = $_POST["link"];
         $desc = $_POST["desc"];
         $icon = $_POST["icon"];
         if (resource_info($name, $link, $desc, $icon)) {
            try {
               add_resource($db, $name, $link, $desc, $icon, $user);
               if (isset($_POST["tags"])) {
                  add_tags($db, $db->lastInsertId(), json_decode($_POST["tags"]));
               }
               success("Successfully added resource.");
            } catch (PDOException $ex) {
               db_error($ex);
            }
         } else {
            invalid_request(RESOURCE_VALID_ERROR);
         }
      } else {
         invalid_request(RESOURCE_VALID_ERROR);
      }
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
   if (is_admin($db, $user)) {
      if (isset($uri[0]) && is_valid_resource_id($db, $uri[0])) {
         try {
            remove_resource($db, $resource_id);
            success("Successfully deleted resource.");
         } catch(PDOException $ex) {
            db_error();
         }
      }
   }
}

/**
  * Handles the request to retrieve all of the approved resources from the database
  * Has the option of a GET parameter of 'categories' to specify approved resources from only those
  *    categories
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
  * @param {GET} categories - The resource categories to limit by
*/
function get_resources_request($uri, $user) {
   try {
      $db = get_PDO();
      $data;
      if (isset($_GET["categories"])) {
         $data = get_approved_resources($db, $_GET["categories"]);
      } else {
         $data = get_approved_resources($db);
      }
      header("Content-type: application/json");
      echo(json_encode($data));
   } catch (PDOException $ex) {
      db_error();
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
   if (is_admin($db, $user)) {
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
   }
}

/**
  * Queries the database to set the expiration date of a specified resourceID
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource_id - The resourceID to update
  * @param {String} expire - The date for the resource to expire
  * @return {Boolean} - TRUE if a resource was updated, FALSE otherwise.
*/
function update_resource_expire($db, $resource_id, $expire) {
   $query = "UPDATE resource SET expire = :expire WHERE id = :id;";
   $stmt = $db->prepare($query);
   $params = array("expire" => $expire,
                   "id" => $resource_id);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
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
  * Handles the request to add a new category
  * If the user is not an admin, makes the category unapproved
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
  * @param {POST} name - The category name to add
*/
function add_category_request($uri, $user) {
   try {
      $db = get_PDO();
      if (isset($_POST["name"])) {
         add_category($db, $_POST["name"]);
         if (is_admin($db, $user)) {
            make_category_unapproved($db, $db->lastInsertId());
         }
         success("Successfully added new category.");
      } else {
         invalid_request("I need a valid name to insert.");
      }
   } catch (PDOException $ex) {
      db_error();
   }
}

/**
  * Handles the request to approve a new category
  * If the user is not an admin, makes the category unapproved
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function approve_category_request($uri, $user) {
   try {
      $db = get_PDO();
      if (is_admin($db, $user)) {
         $category_id = $uri[0];
         approve_category($db, $category_id);
         success("Successfully approved category.");
      } else {
         invalid_request(ADMIN_ERROR);
      }
   } catch (PDOException $ex) {
      db_error();
   }
}

/**
  * Handles the request to remove a category from the database
  * Must be an admin to use
  * If
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function remove_category_request($uri, $user) {
   try {
      $db = get_PDO();
      if (is_admin($db, $user)) {
         if (isset($uri[0]) && $uri[0] != "") {
            $category = $uri[0];
            remove_category($db, $category);
            success("Successfully removed category.");
         } else {
            invalid_request("A valid category ID is required.");
         }
      } else {
         invalid_request(ADMIN_ERROR);
      }
   } catch (PDOException $ex) {
      db_error();
   }
}

/**
  * Handles the request to block a user from adding resources to the database
  * Must be an admin to use
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function block_user_request($uri, $user) {
   try {
      $db = get_PDO();
      if (is_admin($db, $user)) {
         block_netid($db, $uri[1]);
         success("Successfully blocked netid.");
      } else {
         invalid_request(ADMIN_ERROR);
      }
   } catch (PDOException $ex) {
      if ($ex->getCode() === "23000") {
         invalid_request("Please make sure netid is not already blocked.");
      } else {
         db_error();
      }
   }
}

/**
  * Handles the request to unblock a user from adding resources to the database
  * Must be an admin to use
  * @param {String[]} $uri - The array of strings of the request URI, does not include the first
  *                          two pieces
  * @param {String} $user - The user who has made the request
*/
function unblock_user_request($uri, $user) {
   try {
      $db = get_PDO();
      if (is_admin($db, $user)) {
         unblock_netid($db, $uri[1]);
         success("Successfully blocked netid.");
      } else {
         invalid_request(ADMIN_ERROR);
      }
   } catch (PDOException $ex) {
      if ($ex->getCode() === "23000") {
         invalid_request("Please make sure netid is not already unblocked.");
      } else {
         db_error();
      }
   }
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
function verify_resource_info($name, $link, $description, $icon) {
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
   $query = "SELECT * FROM category WHERE id NOT IN (SELECT * FROM unapproved_category);";
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
   $query = "DELETE FROM tag WHERE resource_id = ? AND category_id IN " .
            build_categories_string($categories);
   $stmt = $db->prepare($query);
   $stmt->execute(array_merge([$resource], $categories));
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Sets the status of a category to unapproved
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} category_id - The category to unapprove
  * @return {Boolean} - TRUE if unapproving the category was successful, FALSE otherwise
*/
function unapprove_category($db, $category_id) {
   $query = "INSERT INTO unapproved_category VALUES (?);";
   $stmt = $db->prepare($query);
   $stmt->execute([$category_id]);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

/**
  * Approves a category into the database
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} category_id - The category to approve
  * @return {Boolean} - TRUE if approving the category was successful, FALSE otherwise
*/
function approve_category($db, $category_id) {
   $query = "DELETE FROM unapproved_category WHERE id = ?;";
   $stmt = $db->prepare($query);
   $stmt->execute([$category_id]);
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
