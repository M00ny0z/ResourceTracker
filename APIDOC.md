# ResourceTracker API Documentation
This API will allow the user to see resources and/or services that are available to them as students.
It will also allow advisors to add their own resources for approval where an admin user can then
approve them so they are visible to the main site.
Please see the Error Response piece to see how errors are shown as they are all formatted the same
way.

## Database Error Handling
If a database error has occurred in ANY endpoint, the following will be returned:
```
"A database error has occurred. Please try again later."
```

## Table of Contents
[Resources](#resources-endpoints)
1. [resources/:id](#resources/:id)
2. [resources?categories=](#resources?categories=)
3. [resources/approve/:id](#resources/approve/:id)
4. [resources/standby/:id](#resources/standby/:id)
5. [resources/admin](#resources/admin)
6. [resources/tag](#resources/tag)


[Categories](#categories)
1. [categories/](#categories/)
2. [categories/:name](#categories/:name)

[User](#user)
1. [user/block/:netid](#user/block/:netid)
2. [user/unblock/:netid](#user/unblock/:netid)

## Resources Endpoints

### *resources/:id*
**Request Format:** /resourcetracker/resources/:id

**Request Type:** GET

**Returned Data Format**: HTML

**Description:**
This endpoint will retrieve for you the specific information page for a resource of the specified
ID.


**Example Request:** /resourcetracker/resources/5

**Example Response:**

```html

```

**Error Handling:**
Will output a 400 error if the provided resourceID is not valid.

### *resources/:id*
**Request Format:** /resourcetracker/resources/:id

**Request Type:** DELETE

**Returned Data Format**: Plain Text

**Description:**
This endpoint will delete a resource of the specified ID.

NOTE: Deleting a resource IS permanent and cannot be undone.

NOTE: You must be an admin to access this endpoint.


**Example Request:** /resourcetracker/resources/5

**Example Response:**

```
"Successfully deleted resource."
```

**Error Handling:**
Will output a 400 error if the provided resourceID is not valid.

### *resources?categories=*
**Request Format:** /resourcetracker/resources?categories[]={CATEGORY ID}

**Request Type:** GET

**Returned Data Format**: JSON

**Description:**
This endpoint will retrieve all of the approved resources. This endpoint can take an optional
parameter of categories


**Example Request:** /resourcetracker/resources?categories[]=1&categories[]=2

**Example Response:**

```json
[
    {
      "id": "1",
      "name": "UWT Food Bank",
      "link": "www.google.com",
      "description": "Food bank all students have access to",
      "icon": "fa-food"
    },
    {
        "id": "3",
        "name": "Huscii",
        "link": "www.google.com",
        "description": "Coding group",
        "icon": "fa-icon"
    }
]
```

**Error Handling:**
No potential errors outside of database error.

### *resources*
**Request Format:** /resourcetracker/resources
                   form-data: (name: {NAME}, link: {LINK TO RESOURCE}, desc: {RESOURCE DESCRIPTION}
                               icon: {RESOURCE ICON}, tags: "[CATEGORY ID, ...]")

**Request Type:** POST

**Returned Data Format**: Plain Text

**Description:**
This endpoint will add a new resource to the resourcetracker database and places it on standby. It
will need to be approved by an admin before it displays.

It requires a name, link, description, icon parameters in the form-data.
The icon value should be the class name of a font from [font-awesome]("https://fontawesome.com/icons?d=gallery"),
font should be from the "Free" section.

It can take an optional parameter of "tags" which should be an array of category ID's to tag the
resource under.


**Example Request:** /resourcetracker/resources
                     form-data: (name: "Huscii", link: "www.google.com", desc: "code group",
                                 icon: "fas-code", tags: "[1, 2]")

**Example Response:**

```
"Successfully added resource."
```

**Error Handling:**
If any of the required parameters are missing, will output a 400 error saying so.

### *resources/approve/:id*
**Request Format:** /resourcetracker/resources/approve/{RESOURCE ID}

**Request Type:** PUT

**Returned Data Format**: Plain Text

**Description:**
This endpoint will update the status of an endpoint and approve it.

NOTE: You must be an admin to access this endpoint.


**Example Request:** /resourcetracker/resources/approve/1

**Example Response:**

```
"Successfully approved resource."
```

**Error Handling:**
If the resource ID is non-valid or if the resource already has the status of approved, it will
output a 400 error stating so.

### *resources/standby/:id*
**Request Format:** /resourcetracker/resources/standby/{RESOURCE ID}

**Request Type:** PUT

**Returned Data Format**: Plain Text

**Description:**
This endpoint will update the status of an endpoint and place it on standby.

NOTE: You must be an admin to access this endpoint.


**Example Request:** /resourcetracker/resources/standby/1

**Example Response:**

```
"Successfully put resource on standby."
```

**Error Handling:**
If the resource ID is non-valid or if the resource already has the status of standby, it will
output a 400 error stating so.

### *resources/admin*
**Request Format:** /resourcetracker/resources/admin

**Request Type:** GET

**Returned Data Format**: JSON

**Description:**
This endpoint will retrieve all resources.

NOTE: You must be an admin to access this endpoint.


**Example Request:** /resourcetracker/resources/admin

**Example Response:**

```json
[
    {
        "id": "1",
        "name": "UWT Food Bank",
        "link": "www.google.com",
        "description": "Food bank all students have access to",
        "icon": "fa-food",
        "status": "APPROVED",
        "user": "em66@uw.edu"
    },
    {
        "id": "2",
        "name": "Huscii",
        "link": "www.google.com",
        "description": "Coding group",
        "icon": "fa-icon",
        "status": "APPROVED",
        "user": "em66@uw.edu"
    }
]
```

**Error Handling:**
No potential errors outside of database error.

### *resources/tag/*
**Request Format:** /resourcetracker/tag

                    form-data: (id: {RESOURCE_ID}, add: [CATEGORY_ID, ...],
                                remove: [CATEGORY_ID, ...])

**Request Type:** POST

**Returned Data Format**: JSON

**Description:**
This endpoint will add/remove tags to a specified resource.
It requires the resource ID, an array of all the category IDs to add, and an array of all the
category IDs to remove.



**Example Request:** /resourcetracker/tags

                     form-data: (id: 3, add: [1, 2], remove: [3])

**Example Response:**

```
"Successfully updated resources."
```

**Error Handling:**
If missing necessary resource ID, add, or remove parameters, will output 400 invalid request.

If resource ID provided or category IDs provided are invalid, will output 400 invalid request.


## Categories Endpoints

### *categories/*
**Request Format:** /resourcetracker/categories

**Request Type:** GET

**Returned Data Format**: JSON

**Description:**
This endpoint will retrieve the info on all categories.

Includes the name and ID of that category.



**Example Request:** /resourcetracker/categories

**Example Response:**

```json
[
    {
        "id": "1",
        "name": "Housing"
    },
    {
        "id": "2",
        "name": "Food",
    }
]
```

**Error Handling:**
No potential errors outside of database error.

### *categories/*
**Request Format:** /resourcetracker/categories
                    form-data: (name: {CATEGORY NAME})

**Request Type:** POST

**Returned Data Format**: Plain Text

**Description:**
This endpoint will create a new category with the specified name.

Name of the category is a required parameter.

NOTE: You must be an admin to access this endpoint.

**Example Request:** /resourcetracker/categories
                     form-data: (name: "Mental Health")

**Example Response:**

```
"Successfully added a new category"
```

**Error Handling:**
If the name of the category to add is not provided, will output a 400 error.


### *categories/:name*
**Request Format:** /resourcetracker/categories/{CATEGORY ID}

**Request Type:** DELETE

**Returned Data Format**: Plain Text

**Description:**
This endpoint will delete the specified category.

NOTE: You must be an admin to access this endpoint.

**Example Request:** /resourcetracker/categories

**Example Response:**

```
"Successfully removed category."
```

**Error Handling:**
If the provided category ID is not valid, will output a 400 error.

## User Endpoints

### *users/block/:netid*
**Request Format:** /resourcetracker/users/block/{NETID TO BLOCK}

**Request Type:** POST

**Returned Data Format**: Plain Text

**Description:**
This endpoint will block the specified netid from submitting resources

NOTE: You must be an admin to access this endpoint.

**Example Request:** /resourcetracker/users/block/em66@uw.edu

**Example Response:**

```
"Successfully blocked netid."
```

**Error Handling:**
No potential errors outside of database error.

### *users/unblock/:netid*
**Request Format:** /resourcetracker/users/unblock/{NETID TO BLOCK}

**Request Type:** POST

**Returned Data Format**: Plain Text

**Description:**
This endpoint will unblock the specified netid from submitting resources

NOTE: You must be an admin to access this endpoint.

**Example Request:** /resourcetracker/users/unblock/em66@uw.edu

**Example Response:**

```
"Successfully unblocked netid."
```

**Error Handling:**
No potential errors outside of database error.
