<?php
include("common.php");
header("Content-type: text/html");
$uri = explode("/", parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
$path = strtolower($uri[3]);
$method = $_SERVER["REQUEST_METHOD"];
$user = "em66@uw.edu";

if ($path === "resources") {
   // IF THEY PROVIDED AN ID
   if (isset($uri[4]) && $uri[4] != "") {
      if ($uri[4] === "approve" || $uri[4] === "standby" || $uri[4] === "admin") {
         try {
            $db = get_PDO();
            if (is_admin($db, $user)) {
               $action = $uri[4];
               $resource_id = $uri[5];
               if ($method === "PUT") {
                  if ($action === "approve") {
                     $outcome = update_resource_status($db, $resource_id, APPROVE);
                     if ($outcome) {
                        success("Successfully approved resource.");
                     } else {
                        invalid_request(RESOURCE_UPDATE_ERROR);
                     }
                  } else if ($action === "standby") {
                     $outcome = update_resource_status($db, $resource_id, STANDBY);
                     if ($outcome) {
                        success("Successfully put resource on standby.");
                     } else {
                        invalid_request(RESOURCE_UPDATE_ERROR);
                     }
                  } else {
                     invalid_request(OPERATION_ERROR);
                  }
               } else if ($method === "GET") {
                  if ($action === "admin") {
                     try {
                        $data = get_all_resources($db);
                        header("Content-type: application/json");
                        echo(json_encode($data));
                     } catch (PDOException $ex) {
                        db_error();
                     }
                  }
               } else {
                  invalid_request(OPERATION_ERROR);
               }
            } else {
               invalid_request(ADMIN_ERROR);
            }
            $db = null;
         } catch(PDOException $ex) {
            db_error();
         }
      } else {
         // IF THEY PROVIDED A RESOURCE ID
         $resource_id = $uri[4];
         if ($method === "GET") {
            echo("getting specific resource page.");
         } else if ($method === "DELETE") {
            try {
               $db = get_PDO();
               if (is_admin($db, $user)) {
                  remove_resource($db, $resource_id);
               } else {
                  invalid_request(ADMIN_ERROR);
               }
            } catch(PDOException $ex) {
               db_error();
            }
         }
      }
   } else {
      if ($method === "GET") {
         try {
            $db = get_PDO();
            $data = get_approved_resources($db);
            header("Content-type: application/json");
            echo(json_encode($data));
         } catch (PDOException $ex) {
            db_error();
         }
      } else if ($method === "POST") {
         if (isset($_POST["name"]) && isset($_POST["link"]) && isset($_POST["desc"]) &&
            isset($_POST["icon"]) && isset($_POST["tags"])) {
            $name = $_POST["name"];
            $link = $_POST["link"];
            $desc = $_POST["desc"];
            $icon = $_POST["icon"];
            $tags = $_POST["tags"];
            if (verify_resource_info($name, $link, $desc, $icon, $tags)) {
               try {
                  $db = get_PDO();
                  add_resource($db, $name, $link, $desc, $icon, $user);
                  add_tags($db, $db->lastInsertId(), $tags);
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
}

function is_admin($db, $netid) {
   return true;
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
   remove_tags($db, $params);
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
function remove_tags($db, $category) {
   $query = "DELETE FROM tag WHERE category_id = :id;";
   $stmt = $db->prepare($query);
   $stmt->execute($category);
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
   $status = APPROVED;
   $query = "SELECT DISTINCT r.id, r.name, r.link, r.description, r.icon " .
            "FROM resource r, tag t " .
            "WHERE r.status = '{$status}' AND t.resource_id = r.id ";
   $data;
   if ($categories != "") {
      $stmt = $db->prepare($query);
      $query = $query . "AND t.category_id IN ";
      $query = $query . build_categories_string($categories);
      $stmt->execute($categories);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      $stmt = null;
   } else {
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
