The view-overlay-list is registered with the key `sulu_admin.view_overlay_list`.
It shows a List container with a toolbar. The actions that are displayed in the toolbar can be configured using the route's options.
In contrast to the default list, the view-overlay-list opens any registered view in an overlay with its own router for
adding and editing items. The page URL and the toolbar of the page do not change while the overlay is open.

| Option                               | Description                                                                   |
|--------------------------------------|-------------------------------------------------------------------------------|
| resourceKey                          | A key offered by the server, which describes a certain type of entity, for    |
|                                      | which data will be loaded.                                                    |
| listKey                              | Key of the list configuration that defines which properties of the data       |
|                                      | should be displayed.                                                          |
| addOverlayView                       | Name of the view that is rendered in the overlay when the add button is       |
|                                      | clicked. It gets the `locale` and the `parentId` as attributes.               |
| editOverlayView                      | Name of the view that is rendered in the overlay when an item of the list is  |
|                                      | clicked. It gets the `id` and the `locale` as attributes.                     |
| addOverlayTitle                      | Title (or translation key) that is displayed in the add overlay.              |
| editOverlayTitle                     | Title (or translation key) that is displayed in the edit overlay.             |
| overlaySize                          | Size of the overlay, `small` (default) or `large`.                            |
| title                                | Title (or translation key) that is displayed above the actual list.           |
| adapters                             | Array of list-adapters that are available to the user to display the list.    |
| locales                              | Array of locales that are available in the locale chooser in the toolbar of   |
|                                      | the view.                                                                     |
| toolbarActions                       | Array of toolbar-actions registered in the `ListToolbarActionRegistry` that   |
|                                      | should be shown in the toolbar of the view.                                   |
| itemActions                          | Array of item-actions registered in the `ListItemActionRegistry` that should  |
|                                      | be shown for each item of the list.                                           |
| backView                             | Route to which the user will be navigated when the back button is clicked.    |
| searchable                           | Boolean that defines if the list view should render a search field.           |
| selectable                           | Boolean that defines if the items of the list should be selectable.           |
| routerAttributesToListRequest        | Array of attributes that are passed from the [`Router`](#router) to the       |
|                                      | `ListStore`. They will be appended to the requests sent from the `ListStore`. |
