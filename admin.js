(function() {
   window.addEventListener("load", main);
   const API = "API.php/";
   const APPROVED = "APPROVED";
   const STANDBY = "STANDBY";

   function main() {
      fetchAllResources();
   }

   function fetchAllResources() {
      fetch(API + "resources" + "/admin")
         .then(checkStatus)
         .then(JSON.parse)
         .then(displayResources)
         .catch(console.log);
   }

   function displayResources(resources) {
      let resourceCont = id("resources-table").querySelector("tbody");
      resourceCont.innerHTML = "";
      for (let i = 0; i < resources.length; i++) {
         let newResource = gen("tr");
         let resourceName = createDataCell(resources[i]["name"]);
         let resourceDesc = createDataCell(resources[i]["description"]);
         let resourceLink = gen("td");
         let link = createButtonLink(resources[i]["link"]);
         resourceLink.appendChild(link);
         let resourceIcon = gen("td");
         let icon = createFontAwsIcon(resources[i]["icon"]);
         resourceIcon.appendChild(icon);
         let created = createDataCell(resources[i]["user"]);
         let expire = createDataCell(giveExpire(resources[i]["expire"]));
         let changeStatusCont = gen("td");
         let changeStatusBtn = gen("button");
         addClassList(changeStatusBtn, ["btn", "text-white"]);
         changeStatusBtn.addEventListener("click", () =>
                                          changeResourceStatus(resources[i]["id"],
                                                               resources[i]["status"]));
         if (resources[i]["status"] === APPROVED) {
            changeStatusBtn.classList.add("bg-danger");
            changeStatusBtn.textContent = "Standby?";
         } else {
            changeStatusBtn.classList.add("bg-success");
            changeStatusBtn.textContent = "Approve?";
         }
         changeStatusCont.appendChild(changeStatusBtn);
         let editBtnCont = gen("td");
         let editBtn = gen("button");
         editBtn.textContent = "Edit";
         addClassList(editBtn, ["btn", "bg-husky", "text-white"]);
         editBtnCont.appendChild(editBtn);
         let removeBtnCont = gen("td");
         let removeBtn = createFontAwsIcon("times");
         removeBtn.classList.add("fa-3x");
         removeBtnCont.appendChild(removeBtn);
         appendChildren(newResource, [resourceName, resourceDesc, resourceLink, resourceIcon,
                                      created, expire, changeStatusCont, editBtnCont,
                                      removeBtnCont]);
         resourceCont.appendChild(newResource);
      }
   }

   /**
    * Appends all specified children elements to a parent in order
    * @param {HTMLDOM} parent - The element to append all children to
    * @param {HTMLDOM[]} children - The list of children to append to the parent
    */
   function appendChildren(parent, children) {
      for (let i = 0; i < children.length; i++) {
         parent.appendChild(children[i]);
      }
   }

   /**
    * If given expiration date is null, returns "N/A" for not-applicable
    * @param {String} expire - The expiration date being validated
    * @return {String} - If no expiration date is given, returns "N/A"
    *                    otherwise, returns the given expiration date
    */
   function giveExpire(expire) {
      if (expire === null) {
         return "N/A";
      } else {
         return expire;
      }
   }

   function changeResourceStatus(id,currentStatus) {

   }

   /**
    * Creates a font awesome icon with the specified name
    * @param {String} icon - The name/src to give the icon
    * @return {HTMLDOM} Returns the icon
    */
   function createFontAwsIcon(icon) {
      let newIcon = gen("i");
      addClassList(newIcon, ["fas", "fa-" + icon]);
      return newIcon;
   }

   /**
    * Creates a new data cell
    * @param {String/int} data - The data to place inside of the table cell
    * @return {HTMLDOM} Returns the new table cell with the specified data
    */
   function createDataCell(data) {
      let newCell = gen("td");
      newCell.textContent = data;
      return newCell;
   }

   /**
    * Creates a new button that is a link to a website
    * @param {String} link - The link the button sends the user to
    * @return {HTMLDOM} Returns the link that contains a button inside
    */
   function createButtonLink(link) {
      let newLink = gen("a");
      let newBtn = gen("button");
      newLink.href = link;
      newBtn.textContent = "Link";
      newBtn.type = "button";
      addClassList(newBtn, ["btn", "bg-husky", "text-white"]);
      newLink.appendChild(newBtn);
      return newLink;
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
