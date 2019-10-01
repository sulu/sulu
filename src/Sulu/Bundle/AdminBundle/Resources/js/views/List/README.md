The list is registered with the key `sulu_admin.list`. It shows a List container with a toolbar.  The toolbar shows an
add and a delete button, whereby the latter one only appears enabled if something in the List has been selected.

The view uses the options passed via the current route to adapt its behavior. The following table explains the meanings
of the available options:

| Option                               | Description                                                                   |
|--------------------------------------|-------------------------------------------------------------------------------|
| resourceKey                          | A key offered by the server, which describes a certain type of entity, for    |
|                                      | which data will be loaded.                                                    |
| listKey                              | Key of the list configuration that defines which properties of the data       |
|                                      | should be displayed.                                                          |
| title                                | Title (or translation key) that is displayed above the actual list.           |
| adapters                             | Array of list-adapters that are available to the user to display the list.    |
| locales                              | Array of locales that are available in the locale chooser in the toolbar      |
|                                      | of the view                                                                   |
| toolbarActions                       | Array of toolbar-action registered in the `ToolbarActionRegistry` that should |
|                                      | be shown in the toolbar of the view                                           |
| backView                             | Route to which the user will be navigated when the back button is clicked.    |
| addView                              | Route to which the user will be navigated when the add button is clicked.     |
| editView                             | Route to which the user will be navigated when the edit button of an item in  |
|                                      | the list is clicked.                                                          |
| searchable                           | Boolean that defines if the list view should render a search field.           |
| routerAttributesToListRequest        | Array of attributes that are passed from the [`Router`](#router) to the       |
|                                      | `ListStore`. They will be appended to the requests sent from the `ListStore`. |
