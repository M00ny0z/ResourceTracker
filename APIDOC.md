# ResourceTracker API Documentation
This API will allow the user to see resources and/or services that are available to them as students.
It will also allow advisors to add their own resources for approval where an admin user can then
approve them so they are visible to the main site.
Please see the Error Response piece to see how errors are shown as they are all formatted the same
way.

## Database Error Handling
If a database error has occurred in ANY endpoint, the following will be returned:
```json
{
  error: "A database error has occurred. Please try again later."
}
```
