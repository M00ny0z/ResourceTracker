(function() {
   window.addEventListener("load", main);

   function main() {
      let form = qs("form");
      form.addEventListener("submit", function(e) {
         e.preventDefault();
         submitResource();
      });
   }

   function submitResource() {
      let data = new FormData(qs("form"));
      fetch("API.php/resources", {method: "POST", body: data})
         .then(checkStatus)
         .then(console.log)
         .catch(displayError);
   }

   /**
    * Adds an alert message to a specified parent element
    * @param {HTMLDOM} parent - The parent element to add the alert to
    * @param {String} message - The message of the alert
    * @param {String} type - The type of alert (either 'success' or 'error')
    */
   function displayMessage(message, type) {
      let containerElement = id("alert-placement");
      let messageContainer = gen("div");
      messageContainer.setAttribute("role", "alert");
      addClassList(messageContainer, ["alert", "alert-" + type]);
      messageContainer.textContent = message;
      containerElement.appendChild(messageContainer);
      let removeWait = setTimeout(function() {
         clearTimeout(removeWait);
         messageContainer.remove();
         removeWait = null;
      }, 2000);
   }

   /**
     * Checks and reports on the status of the fetch call
     * @param {String} response - The response from the fetch that was made previously
     * @return {Promise/String} - The success code OR The error promise that resulted from the fetch
   */
   function checkStatus(response) {
      if (response.status >= 200 && response.status < 300 || response.status === 0) {
         return response.text();
      } else {
         return Promise.reject(new Error(response.status + ": " + response.statusText));
      }
   }

   /**
    * Adds an array of classes to classlist of given HTMLDOM object
    * @param {HTMLDOM} object - The object to add classes to
    * @param {String[]} classes - The array of classes to add
    * @returns {HTMLDOM} - The HTMLDOM object with the specified classes added
    */
   function addClassList(object, classes) {
      for (let i = 0; i < classes.length; i++) {
         object.classList.add(classes[i]);
      }
      return object;
   }

   /**
    * Returns the HTMLDOM element with given id
    * @param {String} name - The ID of the element we are retrieving
    * @returns {HTMLDOM} - The HTMLDOM object of the element with given ID
    */
   function id(name) {
      return document.getElementById(name);
   }

   /**
    * Returns the first HTMLDOM element with given selector
    * @param {String} selector - The selector of the element we are retrieving
    * @returns {HTMLDOM} - The HTMLDOM object of the element with given selector
    */
   function qs(selector) {
      return document.querySelector(selector);
   }

   /**
    * Returns an array of all the HTMLDOM elements that match given selector
    * @param {String} selector - The selector of the element we are retrieving
    * @returns {HTMLDOM[]} - All the HTMLDOM elements that match the selector
    */
   function qsa(selector) {
      return document.querySelectorAll(selector);
   }

   /**
    * Returns a new element with the tag of the specified string
    * @param {String} element - The tag of the element we are creating
    * @returns {HTMLDOM} - The new HTMLDOM element with the specified tag
    */
   function gen(element) {
      return document.createElement(element);
   }
})();
