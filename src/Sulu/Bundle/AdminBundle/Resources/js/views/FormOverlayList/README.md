The form-overlay-list is registered with the key `sulu_admin.form_overlay_list`. 
It shows a List container with a toolbar. The actions that are displayed in the toolbar can be configured using the route's options.
In contrast to the default list, the form-overlay-list utilizes an overlay to display the form for adding and editing items.

| Option                               | Description                                                                   |
|--------------------------------------|-------------------------------------------------------------------------------|
| resourceKey                          | A key offered by the server, which describes a certain type of entity, for    |
|                                      | which data will be loaded.                                                    |     
| listKey                              | Key of the list configuration that defines which properties of the data       |
|                                      | should be displayed.                                                          |
| formKey                              | Key of the form configuration that defines which fields should be displayed   | 
|                                      | in the overlay.                                                               | 
| title                                | Title (or translation key) that is displayed above the actual list.           |
| addOverlayTitle                      | Title (or translation key) that is displayed in the add overlay.              |
| editOverlayTitle                     | Title (or translation key) that is displayed in the edit overlay.             |
| adapters                             | Array of list-adapters that are available to the user to display the list.    |
| locales                              | Array of locales that are available in the locale chooser in the toolbar of   |
|                                      | the view                                                                      |
| toolbarActions                       | Array of toolbar-action registered in the `ToolbarActionRegistry` that should |
|                                      | be shown in the toolbar of the view                                           |
| backView                             | Route to which the user will be navigated when the back button is clicked.    |
| searchable                           | Boolean that defines if the list view should render a search field.           |
| routerAttributesToListRequest        | Array of attributes that are passed from the [`Router`](#router) to the       |
|                                      | `ListStore`. They will be appended to the requests sent from the `ListStore`. |
| routerAttributesToFormRequest        | Array of attributes that are passed from the [`Router`](#router) to the       |
|                                      | `FormStore` of the overlay. They will be appended to the requests sent from   |
|                                      | the `ListStore`.                                                              |
