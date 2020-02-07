<?php
/*
* Request object handles a map of a regex string of the endpoint to a map of the request methods
* to the name of the corresponding function to call
*/
class RequestMap {

   // The overall map
   private $endpoint_map;

   public function __construct() {
      $endpoint_map = array();
   }

   /**
     * Places a new endpoint regex map of endpoint regex to a map of the request methods to the
     * corresponding functions
     * @param {String} regex - The regex to match the endpoint
     * @param {<String, String>} - A map of the request method to the name of the function to call
   */
   public function put($regex, $request_map) {
      $this->endpoint_map[$regex] = $request_map;
   }

   /**
     * Places a new endpoint regex map of endpoint regex to a map of the request methods to the
     * corresponding functions
     * @param {String} request- The string of the request made
     * @return {<String, String>} - A map of the request method to the name of the function to call
     * @throws Exception - If request endpoint does not match any currently existing endpoints,
     *                     throws an exception
   */
   public function get($request, $method) {
      foreach ($this->endpoint_map as $pattern => $request_map) {
         if (preg_match($pattern, $request)) {
            if (isset($request_map[$method])) {
               return $request_map[$method];
            } else {
               throw new Exception("That operation is not currently supported by this endpoint.");
            }
         }
      }
      throw new Exception("That endpoint does not currently exist.");
   }

   /**
     * Returns a string of all the current endpoints
   */
   public function get_endpoints() {
      return array_keys($this->endpoint_map);
   }
}
?>
