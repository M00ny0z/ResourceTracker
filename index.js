/*
 *  Name: Manny Munoz
 *  Date: 01.14.19
 *  This is the index.js file for the ResourceTracker website. It provides functionality for the
 *  student side of the webpage.
 *  It allows students to get all categories offered and get all resources offered
 */
"use strict";
(function() {
   const API = "API.php/";
   const COLORS = ["primary", "secondary", "tertiary"];

   let categoryMap;
   window.addEventListener("load", main);

   function main() {
      fetchCategories();
      fetchResources();
      const queryBtn = id("query-btn");
      queryBtn.addEventListener("click", searchByName);
   }

   /**
    * Makes a GET request to the server for the specific resources with a name that contains a word
    * If an error has occurred, will display error to page
    */
   function searchByName() {
      const categories = qsa(".active");
      let categoriesString = "";
      if (categories.length > 0) {
         for (let i = 0; i < categories.length - 1; i++) {
            let currentCategory = categoryMap.get(categories[i].textContent);
            categoriesString = categoriesString + "categories[]=" + currentCategory + "&";
         }
         categoriesString = categoriesString + "categories[]=" +
                            categoryMap.get(categories[categories.length - 1].textContent);
      }
      const queryInput = id("query-input");
      let query = API + "resources/?" + categoriesString;

      if (categories.length > 0) {
         query = query + "&";
      }

      query = query + "name=" + queryInput.value;
      fetch(query)
         .then(checkStatus)
         .then(JSON.parse)
         .then(displayResources)
         .catch(console.log);
   }

   /**
    * Makes a fetch GET request to retrieve all of the categories currently offered
    */
   function fetchCategories() {
      fetch(API + "categories")
         .then(checkStatus)
         .then(JSON.parse)
         .then(function(categories) {
            displayCategories(categories);
            mapCategories(categories);
         })
         .catch(displayError);
   }

   /**
    * Maps all of the categories from name to its corresponding ID
    * @param {JSON[]} categories - The array of categories, each item containing its name and ID
    */
   function mapCategories(categories) {
      categoryMap = new Map();
      for (let i = 0 ; i < categories.length; i++) {
         categoryMap.set(categories[i]["name"], categories[i]["id"]);
      }
   }

   /**
    * Makes a fetch GET request to retrieve all of the categories currently offered
    */
   function fetchResources() {
      let categories = qsa(".active");
      let categoriesString = "";
      if (categories.length > 0) {
         for (let i = 0; i < categories.length - 1; i++) {
            let currentCategory = categoryMap.get(categories[i].textContent);
            categoriesString = categoriesString + "categories[]=" + currentCategory + "&";
         }
         categoriesString = categoriesString + "categories[]=" +
                            categoryMap.get(categories[categories.length - 1].textContent);
      }
      let query = API + "resources/?" + categoriesString;
      fetch(query)
         .then(checkStatus)
         .then(JSON.parse)
         .then(displayResources)
         .catch(displayError);
   }

   /**
    * Displays to the page resources fetched
    * @param {JSON[]} categories - The array of categories, each item containing its name and ID
    */
   function displayResources(resources) {
      console.log(resources);
      let resourceContainer = qs("main section");
      resourceContainer.innerHTML = "";
      if (resources.length > 0) {
         for (let i = 0; i < resources.length; i++) {
            let currentResource = gen("div");
            addClassList(currentResource, ["resource", "mr-4"]);
            let link = gen("a");
            link.setAttribute("data-toggle", "modal");
            link.setAttribute("data-target", "#info-modal");
            link.addEventListener("click", function() {
               changeModalInfo(resources[i]["name"], resources[i]["description"],
                               resources[i]["link"]);
            })
            let icon = gen("i");
            addClassList(icon, ["fas", "fa-" + resources[i]["icon"], "fa-9x", COLORS[(i % 3)]]);
            let name = gen("p");
            name.textContent = resources[i]["name"];
            link.appendChild(icon);
            currentResource.appendChild(link);
            currentResource.appendChild(name);
            resourceContainer.appendChild(currentResource);
         }
      }
   }

   /**
    * Changes the information modal to display the currently selected resource's info
    * @param {String} name - The name of the resource to display
    * @param {String} description - The description of the resource to display
    * @param {String} link - The link to the resource
    */
   function changeModalInfo(name, description, link) {
      id("resource-desc").textContent = "Description: " + description;
      id("resource-link").href = link;
      id("info-modal-label").textContent = name;
   }

   /**
    * Displays to the page all of the categories
    * @param {JSON[]} categories - The array of categories, each item containing its name and ID
    */
   function displayCategories(categories) {
      let categoriesContainer = qs("ul");
      for (let i = 0; i < categories.length; i++) {
         let newCategory = gen("li");
         addClassList(newCategory, ["list-group-item", "rounded-pill"]);
         newCategory.textContent = categories[i]["name"];
         newCategory.addEventListener("click", toggleCategory);
         categoriesContainer.appendChild(newCategory);
      }
   }

   /**
    * Toggles whether the category appears as active or not
    */
   function toggleCategory() {
      this.classList.toggle("active");
      fetchResources();
   }

   /**
    * Adds an success alert message to the page
    * @param {String} message - The message of the alert
    */
   function displaySuccess(message) {
      displayMessage(message, "success");
   }

   /**
    * Adds an error alert message to the page
    * @param {String} message - The message of the alert
    */
   function displayError(message) {
      displayMessage(message, "danger");
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
   async function checkStatus(response) {
     if (response.status >= 200 && response.status < 300 || response.status === 0) {
       return response.text();
     } else {
       let errorMessage = await response.json();
       return Promise.reject(new Error(errorMessage.error));
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
