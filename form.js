(function() {
   window.addEventListener("load", main);

   const API = "API.php/";

   function main() {
      let form = qs("form");
      form.addEventListener("submit", function(e) {
         e.preventDefault();
         submitResource();
      });
      fetchCategories();
   }

   function submitResource() {
      let data = new FormData(qs("form"));
      fetch(API + "resources", {method: "POST", body: data})
         .then(checkStatus)
         .then(console.log)
         .catch(displayError);
   }

   /**
    * Makes a fetch GET request to retrieve all of the categories currently offered
    */
   function fetchCategories() {
      fetch(API + "categories/")
         .then(checkStatus)
         .then(JSON.parse)
         .then(addCategoryOptions)
         .catch(displayError);
   }

   /**
    * Adds all of the categories as checkboxs with their name to the page
    * @param {JSON[]} categories - The categories to add, each item containing its name and ID
    */
   function addCategoryOptions(categories) {
      let categoryContainer = id("category-container");
      categoryContainer.innerHTML = "";
      for (let i = 0; i < categories.length; i++) {
         let checkBox = createCheckbox(categories[i]["id"], categories[i]["name"], i);
         categoryContainer.appendChild(checkBox);
         if (i === categories.length - 1) {
            checkBox = createCheckbox(0, "Other", i + 1);
            checkBox.addEventListener("click", function() {
               id("other-input").parentElement.classList.remove("hidden");
            });
            categoryContainer.appendChild(checkBox);
         }
      }
   }

   /**
    * Creates and returns a new checkbox option
    *
    * @param {String} checkID - The ID to assign to the checkbox
    * @param {String} name - The text to show alongside the checkbox
    * @param {String} number - The overall current checkbox count
    * @return {HTMLDOM} - The new div element, containing the checkbox and its info
    */
   function createCheckbox(checkID, name, number) {
      let checkBoxContainer = gen("div");
      addClassList(checkBoxContainer, ["custom-control", "custom-checkbox"]);
      let input = gen("input");
      input.classList.add("custom-control-input");
      input.type = "checkbox";
      input.value = checkID;
      input.id = "checkbox-" + number;
      input.name = "tags[]";
      let label = gen("label");
      label.classList.add("custom-control-label");
      label.setAttribute("for", input.id);
      label.textContent = name;
      checkBoxContainer.appendChild(input);
      checkBoxContainer.appendChild(label);
      return checkBoxContainer;
   }

   /**
    * Adds an error alert message to the page
    * @param {String} message - The message of the alert
    */
   function displayError(message) {
      displayMessage(message, "danger");
   }

   /**
    * Adds an success alert message to the page
    * @param {String} message - The message of the alert
    */
   function displaySuccess(message) {
      displayMessage(message, "success");
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
