<?php
include("common.php");

$db = get_PDO();
update_resource_status($db, 1, APPROVED);
success("done");

function delete_resource($db)

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
