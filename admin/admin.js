/**
* This is the JS file for the admin view of ResourceTracker, it provides functionality to the
* administrator HTML page such as adding/removing/editing resources and resource categories.
* Author: Emmanuel Munoz
*/
"use strict";
(function() {
   window.addEventListener("load", main);
   const API = "admin.php/";
   const APPROVED = "APPROVED";
   const STANDBY = "STANDBY";

   let categories;
   let selectedCategories;
   let currentlyActiveCats;
   let currentResource;

   /**
    * Makes the initial request for all the resources and adds all event listeners
    */
   function main() {
      selectedCategories = [];
      fetchAllResources();
      const queryBtn = id("query-btn");
      queryBtn.addEventListener("click", searchByName);
      const updateResourceBtn = id("update-resource-btn");
      updateResourceBtn.addEventListener("click", submitResourceUpdate);
      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
      });
      $(function () {
         $('#expire-picker').datetimepicker({
            format: 'YYYY-MM-DD'
         });
      });
      fetchCategories();
   }

   /**
    * Makes a fetch PUT request to the update the informaton of a resource
    */
   function submitResourceUpdate() {
      const data = removeEmptyData(new FormData(id("resource-modal").querySelector("form")));
      const currentCategories = getSelectedTags();
      const add = arrayDifference(currentCategories, currentlyActiveCats);
      const remove = arrayDifference(currentlyActiveCats, currentCategories);
      data.append("add", JSON.stringify(add));
      data.append("remove", JSON.stringify(remove));
      $('#resource-modal').modal('hide');

      fetch(API + "resources/" + currentResource, {method: "PUT", body: data})
         .then(checkStatus)
         .then(function(data) {
            displaySuccess(data);
            fetchAllResources();
         })
         .catch(displayError);
   }

   /**
    * Removes any empty data from the FormData object
    * @param {FormData} form - The FormData object to remove data from
    */
   function removeEmptyData(form) {
      const attributes = ["name", "icon", "link", "description", "expire"];
      for (const attribute of attributes) {
         if (form.get(attribute) === "" || form.get(attribute) === " ") {
            form.delete(attribute);
         }
      }
      return form;
   }

   function displayCategoryData(categoryData) {
      const categoryTable = id("category-table").querySelector("tbody");
      categoryTable.innerHTML = "";
      for (const category of categoryData) {
         const newCategory = gen("tr");
         const newName = gen("td");
         newName.textContent = category.name;
         const editBtnCont = createEditBtn();
         const approveBtn = createStatusChangeBtn(category.status,
                                                        category.id,
                                                        changeCategoryStatus);
      }
   }

   function changeCategoryStatus(id) {
      fetch(API + id + "/status/APPROVE", {method: 'PUT'})
         .then(checkStatus)
         .then(displaySuccess);
   }

   function getSelectedTags() {
      const output = [];
      const currentlySelectedTags = qsa(".selected");
      for (const categoryEle of currentlySelectedTags) {
         output.push(categoryEle.id.split("-")[1]);
      }
      return output;
   }

   /**
    * Makes a fetch GET request to retrieve all of the current categories
    */
   function fetchCategories() {
      fetch(API + "categories")
         .then(checkStatus)
         .then(JSON.parse)
         .then(function(data) {
            categories = data;
            displayCategories(data);
         })
         .catch(displayError);
   }

   /**
    * Displays all of the category data to the page
    * @param {JSON[]} categoryData - Each item containing {id:
    *                                                      name:}
    */
   function displayCategories(categoryData) {
      let categoryCont = id("category-cont");
      categoryCont.innerHTML = "";
      for (let i = 0; i < categoryData.length; i++) {
         let newCategory = gen("span");
         addClassList(newCategory, ["border", "border-dark", "rounded-pill", "p-3", "mr-2", "tag",
                                    "mb-2"]);
         newCategory.id = "id-" + categoryData[i].id;
         newCategory.textContent = categoryData[i]["name"];
         newCategory.addEventListener("click", function() {
            addOrRemove(selectedCategories, parseInt(categoryData[i]["id"]));
         });
         newCategory.addEventListener("click", toggleSelection);
         categoryCont.appendChild(newCategory);
      }
   }

   function displayUnapprovedCategories(categoryData) {
      const categoryCont = id("uncategory-table");
      categoryCont.innerHTML = "";
      for (let i = 0; i < categoryData.lenth; i++) {
         const newCategory = gen("span");
      }
   }

   /**
    * Makes a fetch POST call to submit a new category
    */
   function submitNewCategory() {
      const data = new FormData();
      data.append("name", id("category-name").value);
      fetch(API + "categories", {body: data, method: 'POST'})
         .then(checkStatus)
         .then(displaySuccess)
         .catch(displayError);
   }

   /**
    * Toggles the category tag 'this' references between selected and non-selected
    */
   function toggleSelection() {
      this.classList.toggle("selected");
   }

   /**
    * Clears all of the currently selected categories
    */
   function clearSelected() {
      const allCategories = id("category-cont").querySelectorAll("span");
      for(let i = 0; i < allCategories.length; i++) {
         allCategories[i].classList.remove("selected");
      }
      selectedCategories = [];
   }

   /**
    * Makes a GET request to the server for all the resources
    * If an error has occurred, will display error to page
    */
   function fetchAllResources() {
      fetch(API + "resources" + "/admin")
         .then(checkStatus)
         .then(JSON.parse)
         .then((resources) => {
            displayResources(resources);
         })
         .catch(console.log);
   }

   /**
    * Makes a GET request to the server for the specific resources with a name that contains a word
    * If an error has occurred, will display error to page
    */
   function searchByName() {
      let queryInput = id("query-input");
      fetch(API + "resources" + "/admin/" + "?name=" + queryInput.value)
         .then(checkStatus)
         .then(JSON.parse)
         .then(displayResources)
         .catch(console.log);
   }

   /**
    * Adds all of the info of the resources and displays it to the page
    * @param {JSON[]} resources - The array of JSON containing, each item containing the info on a
    *                             resource
    */
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
         const changeStatusCont = createStatusChangeBtn(resources[i]["status"],
                                                        resources[i]["id"],
                                                        changeResourceStatus);
         const editBtnCont = createEditBtn(resources[i]);
         let removeBtnCont = gen("td");
         let removeBtn = createFontAwsIcon("times");
         removeBtn.addEventListener("click", () => removeResource(resources[i]["id"]));
         removeBtn.classList.add("fa-3x");
         removeBtnCont.appendChild(removeBtn);
         appendChildren(newResource, [resourceName, resourceDesc, resourceLink, resourceIcon,
                                      created, expire, changeStatusCont, editBtnCont,
                                      removeBtnCont]);
         resourceCont.appendChild(newResource);
      }
   }

   /**
    * Creates a new button which upon click, changes the status of an item
    * If the current status of the item is APPROVED, sets color green and sets text to "Standby?"
    * Else, sets the color to red and sets text to "Approve?"
    * @param {String} currentStatus - The current status of the item
    * @param {String/int} itemID - The ID of the item to change status for
    * @param {String} changeStatus - The function callback to call, upon changing t
    * @return {HTMLDOM} - The new button that changes the status of an item upon click
    */
   function createStatusChangeBtn(currentStatus, itemID, changeStatus) {
      const changeStatusCont = gen("td");
      const changeStatusBtn = gen("button");
      addClassList(changeStatusBtn, ["btn", "text-white"]);
      changeStatusBtn.addEventListener("click", () => changeStatus(itemID, currentStatus));
      if (currentStatus === APPROVED) {
         changeStatusBtn.classList.add("bg-success");
         changeStatusBtn.textContent = "Approved";
      } else {
         changeStatusBtn.classList.add("bg-danger");
         changeStatusBtn.textContent = "On-Standby";
      }
      changeStatusCont.appendChild(changeStatusBtn);
      return changeStatusCont;
   }

   function createEditBtn(info) {
      const editBtnCont = gen("td");
      const editBtn = gen("button");
      editBtn.addEventListener("click", function() {
         $('#resource-modal').modal('show');
         prepareResourceModal(info);
      });
      editBtn.textContent = "Edit";
      addClassList(editBtn, ["btn", "bg-husky", "text-white"]);
      editBtnCont.appendChild(editBtn);
      return editBtnCont;
   }

   /**
    * Prepares the resources modal for the user to edit the info of a clicked on resource
    * @param {JSON} resourceData - The associative array, {id:
                                                                 name:
                                                                 description:
                                                                 expire:}
    */
   function prepareResourceModal(resourceData) {
      clearSelected();
      addTags(resourceData.tags);
      currentlyActiveCats = resourceData.tags;
      currentResource = resourceData.id;
      const modal = id("resource-modal");
      const attributes = ["name", "icon", "link", "description", "expire"];
      for (let i = 0; i < attributes.length; i++) {
         let value = resourceData[attributes[i]];
         if (!value) {
            value = "";
         }
         document.getElementById("resource-modal-" + attributes[i]).placeholder = value;
      }
   }

   /**
    * Finds the difference between the first and the second array
    * "arr1 - arr2"
    * @param {Object[]} arr1 - The array to remove elements from
    * @param {Object[]} arr2 - The array to remove elements according to
    * @return {Object[]} - The new array containing all elements from arr1 that arent present in
    *                      arr2
    */
   function arrayDifference(arr1, arr2) {
      const output = arr1.filter(current => !(arr2.includes(current)));
      return output;
   }

   /**
    * "Toggles" a specified value inside a specified array
    * If an error has occurred, will display error to page
    * @param {String/int} array - The array to toggle the value for
    * @param {String/int} value - The value to toggle in the array
    */
   function addTags(activeTags) {
      for (const tag of activeTags) {
         id("id-" + tag).classList.add("selected");
      }
   }

   /**
    * "Toggles" a specified value inside a specified array
    * If an error has occurred, will display error to page
    * @param {String/int} array - The array to toggle the value for
    * @param {String/int} value - The value to toggle in the array
    */
   function addOrRemove(array, value) {
    let index = array.indexOf(value);
    if (index === -1) {
        array.push(value);
    } else {
        array.splice(index, 1);
    }
}

   /**
    * Makes a DELETE request to delete a resource
    * If an error has occurred, will display error to page
    * @param {String/int} id- The ID of the resource to delete
    */
   function removeResource(id) {
      fetch(API + "resources/" + id, {method: "DELETE"})
         .then(checkStatus)
         .then(function(result) {
            displaySuccess(result);
            fetchAllResources();
         })
         .catch(console.log);
   }

   function openEdit() {

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

   /**
    * Makes a PUT request to change the status of a resource
    * If the resource is currently on STANDBY, sets the status to APPROVED and vice versa
    * If an error has occurred, will display error to page
    * @param {String/int} id - The ID of the resource to change
    * @return {String} - The current status of the resource, either APPROVED or STANDBY
    */
   function changeResourceStatus(id, currentStatus) {
      let status;
      if (currentStatus === APPROVED) {
         status = STANDBY;
      } else {
         status = APPROVED;
      }
      fetch(API + "resources/" + id + "/status/"  + status, {method: "PUT"})
         .then(checkStatus)
         .then(function(result) {
            displaySuccess(result);
            fetchAllResources();
         })
         .catch(console.log);
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
