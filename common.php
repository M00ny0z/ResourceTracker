<?php
  /*
   * Configuration/common file for the ProgressTracker API
   */

  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  const STANDBY = "STANDBY";
  const APPROVED = "APPROVED";
  const DATABASE_ERROR = "Something has occurred with database. Please try again later.";

  /**
   * Returns a PDO object connected to the speclister database. Throws
   * a PDOException if an error occurs when connecting to database.
   * @return {PDO}
   */
  function get_PDO() {
    $host =  "localhost";
    $port = "3306";
    $user = "root";
    $password = "root";
    $dbname = "resourcetrackerDB";

      $ds = "mysql:host={$host}:{$port};dbname={$dbname};charset=utf8";

    try {
      $db = new PDO($ds, $user, $password);
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $db;
    } catch (PDOException $ex) {
      handle_error("Can not connect to the database. Please try again later.", $ex);
    }
  }

/**
  * Checks a specific table to see if a specific value for a specific attribute exists
  * @param {PDObject} db - The PDO Object connected to the HuskyQuizzerDB
  * @param {String} table - The table that is being looked into
  * @param {String} attribute - The specific column that is being looked at
  * @param {String} value - The specific value that is being looked for
  * @return {Boolean} - TRUE if exists, false otherwise
*/
function check_exists($db, $table, $attribute, $value) {
  try {
    $query = "SELECT count(*) AS cnt FROM {$table} WHERE {$attribute}=:$attribute;";
    $stmt = $db->prepare($query);
    $params = array($attribute => $value);
    $stmt->execute($params);
    $result = $stmt->fetchAll();
    $stmt->closeCursor();
    $stmt = null;
    foreach($result as $row) {
      return !($row["cnt"] == 0);
    }
  } catch(PDOException $ex) {
    checking_error($ex);
  }
}

/**
  * Outputs a successfull text message
  * @param {String} msg - The success message with the corresponding action
*/
function success($msg) {
   header("Content-type: text/plain");
   echo($msg);
}

/**
 * Prints out a plain text 400 error message given $msg. If given a second (optional) argument as
 * an PDOException, prints details about the cause of the exception.
 * @param $msg {string} - Plain text 400 message to output
 * @param $ex {PDOException} - (optional) Exception object with additional exception details to print
 */
 // TODO: MAKE SO IT CHECKS ERROR CODE AND OUTPUTS CORRESPONDING ERROR MESSAGE
function db_error($ex="") {
   header("HTTP/1.1 503 Invalid Request");
   header("Content-type: text/plain");
   die(DATABASE_ERROR . "Error details: {$ex}");
}

  /**
   * Prints out a plain text 400 error message given $msg. If given a second (optional) argument as
   * an PDOException, prints details about the cause of the exception.
   * @param $msg {string} - Plain text 400 message to output
   * @param $ex {PDOException} - (optional) Exception object with additional exception details to print
   */
  function handle_error($msg, $ex=NULL) {
    header("HTTP/1.1 400 Invalid Request");
    header("Content-type: text/plain");
    if ($ex) {
      print ("Error details: $ex \n");
    }
    die("{$msg}\n");
  }

  /**
   * Replaces a specified string with another specified string within a provided string
   * @param $input {string} - The string to replace pieces of
   * @param $to_replace {string} - The string to replaced  within the input
   * @param $replace_with {string} - The string to replace with
   */
  function replace_all($input, $to_replace, $replace_with) {
    $output = "";
    $occurrences = substr_count($input, $to_replace);
    for($i = 0; $i < substr_count($input, $to_replace); $i++) {
      $input = str_replace($to_replace, $replace_with, $input);
      $i--;
    }
    return $input;
  }

?>
