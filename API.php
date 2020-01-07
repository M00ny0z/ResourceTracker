<?php
include("common.php");

$db = get_PDO();
$tags = array(1, 2);
add_tags($db, 2, $tags);
success("done");

function get_approved_resources($db) {
   $query = "SELECT * " .
            "FROM resource " .
            "WHERE status = APPROVED;";

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
   $category_list = "(?";
   for ($i = 1; $i < count($categories); $i++) {
      $category_list = $category_list . ", ?";
   }
   $category_list = $category_list . ");";
   $query = $query . $category_list;
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
function delete_resource($db, $id) {
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
  * @return {Boolean} - TRUE if resource was added, FALSE otherwise.
*/
function add_resource($db, $name, $link, $desc, $icon) {
   $query = "INSERT INTO resource(name, link, description, icon) VALUES " .
            "(:name, :link, :description, :icon);";
   $stmt = $db->prepare($query);
   $params = array("name" => $name,
                   "link" => $link,
                   "description" => $desc,
                   "icon" => $icon);
   $stmt->execute($params);
   $result = $stmt->rowCount() > 0;
   $stmt->closeCursor();
   $stmt = null;
   return $result;
}

?>
