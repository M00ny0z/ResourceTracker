<?php
include("common.php");
$uri = explode("/", parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
$path = strtolower($uri[3]);
$method = $_SERVER["REQUEST_METHOD"];
$user = "em66@uw.edu";

if ($path === "resources") {
   // IF THEY PROVIDED AN ID
   if (isset($uri[4]) && $uri[4] != "") {
      $action = $uri[4];
      if ($action === "approve" || $action === "standby" || $action === "admin" ||
          $action === "tag") {
         $db = get_PDO();
         if (is_admin($db, $user)) {
            if ($method === "PUT") {
               $resource_id = $uri[5];
               if ($action === "approve") {
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
               } else if ($action === "standby") {
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
            } else if ($method === "POST") {
               if ($action === "tag") {
                  if (isset($_POST["id"]) && isset($_POST["add"]) && isset($_POST["remove"])) {
                     try {
                        $id = $_POST["id"];
                        $categories_to_add = $_POST["add"];
                        $categories_to_remove = $_POST["remove"];
                        $outcome = true;
                        if (count($categories_to_add) > 0) {
                           $outcome = $outcome && add_tags($db, $id, $categories_to_add);
                        }
                        if (count($categories_to_remove) > 0) {
                           $outcome = $outcome && remove_tags($db, $id, $categories_to_remove);
                        }
                        if ($outcome) {
                           success("Successfully updated resource tags.");
                        } else {
                           invalid_request("Please make sure that the resource ID and category " .
                                           "IDs provided are valid.");
                        }
                     } catch (PDOException $ex) {
                        db_error();
                     }
                  } else {
                     invalid_request("I need a valid ID, add, and remove values. Please see " .
                                     "the documentation for more information.");
                  }
               } else {
                  invalid_request(OPERATION_ERROR);
               }
            } else {
               invalid_request(OPERATION_ERROR);
            }
         } else {
            invalid_request(ADMIN_ERROR);
         }
         // IF THEY PROVIDED A RESOURCE ID
         $resource_id = $uri[4];
         if ($method === "DELETE") {
            try {
               $db = get_PDO();
               if (is_admin($db, $user)) {
                  remove_resource($db, $resource_id);
                  success("Successfully deleted resource.");
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
      } else if ($method === "POST") {
         if (isset($_POST["name"]) && isset($_POST["link"]) && isset($_POST["desc"]) &&
            isset($_POST["icon"])) {
            $name = $_POST["name"];
            $link = $_POST["link"];
            $desc = $_POST["desc"];
            $icon = $_POST["icon"];
            if (verify_resource_info($name, $link, $desc, $icon, $tags)) {
               try {
                  $db = get_PDO();
                  add_resource($db, $name, $link, $desc, $icon, $user);
                  if (isset($_POST["tags"])) {
                     add_tags($db, $db->lastInsertId(), $_POST["tags"]);
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
} else if ($path === "categories") {
   if ($method === "DELETE") {
      try {
         $db = get_PDO();
         if (is_admin($db, $user)) {
            if (isset($uri[4]) && $uri[4] != "") {
               $category = $uri[4];
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
   } else if ($method === "POST") {
      try {
         $db = get_PDO();
         if (is_admin($db, $user)) {
            if (isset($_POST["name"])) {
               add_category($db, $_POST["name"]);
               success("Successfully added new category.");
            } else {
               invalid_request("I need a valid name to insert.");
            }
         } else {
            invalid_request(ADMIN_ERROR);
         }
      } catch (PDOException $ex) {
         db_error();
      }
   } else if ($method === "GET") {
      try {
         $db = get_PDO();
         $data = get_categories($db);
         header("Content-type: application/json");
         echo(json_encode($data));
      } catch (PDOException $ex) {
         db_error();
      }
   } else {
      invalid_request(OPERATION_ERROR);
   }
} else if ($path === "users") {
   if (isset($uri[4]) && $uri[4] != "") {
      $action = $uri[4];
      if (isset($uri[5]) && $uri[5] != "") {
         $netid = $uri[5];
         if ($method === "POST") {
            if ($action === "block") {
               try {
                  $db = get_PDO();
                  if (is_admin($db, $user)) {
                     block_netid($db, $netid);
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
            } else if ($action === "unblock") {
               try {
                  $db = get_PDO();
                  if (is_admin($db, $user)) {
                     $outcome = unblock_netid($db, $netid);
                     if ($outcome) {
                        success("Successfully unblocked netid.");
                     } else {
                        invalid_request("Please make sure netid has not already been unblocked.");
                     }
                  } else {
                     invalid_request(ADMIN_ERROR);
                  }
               } catch (PDOException $ex) {
                  db_error();
               }
            } else {
               invalid_request(OPERATION_ERROR);
            }
         } else {
            invalid_request(OPERATION_ERROR);
         }
      } else {
         invalid_request("I need a valid netid for these actions.");
      }
   } else {
      invalid_request("I need a valid action of either block or unblock.");
   }
}

function is_admin($db, $netid) {
   return true;
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
