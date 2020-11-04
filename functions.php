<?php


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

function update_resource_tags($db, $resource_id, $_PUT) {
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
   return $outcome;
}

function trim_endings($arr) {
   foreach ($arr as $name => $value) {
      $arr[$name] = trim($value);
   }
   return $arr;
}

function update_resource($db, $resource_id, $_PUT) {
   $possible_keys = ["name", "link", "description", "icon", "expire"];
   $to_update = array_intersect(array_keys($_PUT), $possible_keys);
   $result = true;
   if (!empty($to_update)) {
      $query = "UPDATE resource SET ";
      $query = $query . $to_update[0] . " = :" . $to_update[0] . " ";
      for ($i = 1; $i < count($to_update); $i++) {
         $query = $query . ", " . $to_update[$i] . " = :" . $to_update[$i] . " ";
      }
      $query = $query . " WHERE id = :id;";
      $stmt = $db->prepare($query);
      // Garbage value of 0, only need to compare the keys and get the user provided values
      $params = array_intersect_key($_PUT, array_fill_keys($possible_keys, 0));
      $params["id"] = $resource_id;
      $stmt->execute($params);
      $result = $stmt->rowCount() > 0;
      $stmt->closeCursor();
      $stmt = null;
   }
   return $result;
}

function is_admin($netid) {
   return $netid == "roseann" || $netid == "uwtslwebdev" || $netid == "stusuppt" || $netid == "em66";
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
function get_all_resources($db, $name="") {
   $query = "SELECT r.id, r.name, r.link, r.description, r.icon, r.status, r.user, r.expire " .
            "FROM resource r ";
   if ($name != "") {
      $query = $query . "WHERE name LIKE '%{$name}%' OR description LIKE '%{$name}%';";
   }
   $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
   return $data;
}

/**
  * Queries the database to get a list of all the category_IDs associated with the resource
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource_id - The resource ID to retrieve categories for
  * @return {String/int[]} - The array where each item is a category_id that is associated with the
  *                          resource
*/
function get_resource_tags($db, $resource_id) {
   $query = "SELECT t.category_id " .
            "FROM tag t " .
            "WHERE t.resource_id = :id;";
   $stmt = $db->prepare($query);
   $stmt->execute(["id" => $resource_id]);
   $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
   $data = array_values($data);
   $stmt->closeCursor();
   $stmt = null;
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
  * Sends notification of a new resource that has been added as an email to the admins
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {String} resource_name - The name of the resource that has been added
*/
function new_resource_email($resource_name) {
   $subject = "NEW RESOURCE ADDED";
   $to_ros = "roseann@uw.edu";
   $to_stu_supp = "stusuppt@uw.edu";
   $headers = "From: uwtslwebdev@uw.edu";
   $text = "ResourceTracker has received a new resource named: {$resource_name}. " .
           "Please visit <a href='http://depts.washington.edu/uwtslwebdev/ResourceTracker/admin'>the admin site</a> to approve or remove.";
   mail($to_ros, $subject, $text, $headers);
   mail($to_stu_supp, $subject, $text, $headers);
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

function remove_resource_tags($db, $resource) {
   $query = "DELETE FROM tag WHERE resource_id = ?";
   $stmt = $db->prepare($query);
   $stmt->execute([$resource]);
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
  * @param {String/int []} categories - The category tags to search
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

function remove_old_entries($db) {
   $query = "SELECT r.id FROM resource r WHERE expire < NOW();";
   $data = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
   foreach ($data as $resource) {
      $resource_id = $resource["id"];
      remove_resource_tags($db, $resource_id);
      remove_resource($db, $resource_id);
   }
}

/**
  * Queries the database to get all of the currently approved resources by category and name/des
  * WILL THROW PDOEXCEPTION IF DATABASE ERROR HAS OCCURRED
  * @param {PDObject} db - The PDO Object connected to the ResourceTrakerDB
  * @param {String/int} resource - The ID of the resource to add tags to
  * @param {String/int []} categories - The category tags to add to the resource
  * @return {Boolean} - TRUE if category was added, FALSE otherwise.
*/
function get_approved_resources_by_name($db, $name, $categories = "") {
   $status = APPROVE;
   $query = "SELECT DISTINCT r.id, r.name, r.link, r.description, r.icon " .
            "FROM resource r ";
   $data;
   if ($categories != "") {
      // IF FILTERING BY CATEGORIES
      $query = $query . ", tag t " .
      $query =          "WHERE r.status = '{$status}' AND t.resource_id = r.id AND " .
                        "t.category_id IN " . build_categories_string($categories) . " ";
      if ($name != "") {
         $query = $query . "AND name LIKE '%{$name}%' OR description LIKE '%{$name}%';";
      }
      $stmt = $db->prepare($query);
      $stmt->execute($categories);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      $stmt = null;
   } else {
      // ELSE GET ALL OF THEM
      $query = $query . "WHERE r.status = '{$status}' ";
      if ($name != "") {
         $query = $query . "AND name LIKE '%{$name}%' OR description LIKE '%{$name}%';";
      }
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

   $tags_query = "INSERT INTO tag VALUES (?, {$resource_id}) ";
   for ($i = 1; $i < count($categories_added); $i++) {
      $tags_query = $tags_query . ", (?, {$resource_id})";
   }
   $stmt = $db->prepare($tags_query);
   $stmt->execute($categories_added);
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
   $status = "STANDBY";
   if (is_admin($user)) {
      $status = "APPROVED";
   }

   $query = "INSERT INTO resource(name, link, description, icon, status, user) VALUES " .
            "(:name, :link, :description, :icon, :status, :user);";
   $stmt = $db->prepare($query);
   $params = array("name" => $name,
                   "link" => $link,
                   "description" => $desc,
                   "icon" => $icon,
                   "status" => $status,
                   "user" => $user);
   $stmt->execute($params);
   $stmt->closeCursor();

   if (!is_admin($user)) {
      new_resource_email($name);
   }
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
