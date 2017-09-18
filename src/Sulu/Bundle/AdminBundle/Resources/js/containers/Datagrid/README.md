The Datagrid is responsible for rendering data in a table view. One of its parameters is the `store`, which has to be
created outside, and be passed to the Datagrid. The `DatagridStore` is responsible for loading a page from a REST API.

```javascript static
const store = new DatagridStore('snippets', '/admin/api/snippets');

<Datagrid store={store} />

store.selections; // returns the IDs of the selected items
store.destroy();
```

The Datagrid also takes control of the store, and handles loading other pages and selecting of items. The `selections`
property can be used to retrieve the IDs of the currently selected items.

After the store is not used anymore, its `destroy` method should be called, because there are some observations, which
have to be cancelled.

The `Datagrid` component also takes an `onRowEditClick` callback, which is executed when a row has been clicked with
the intent of editing it. The callback gets one parameter, which is the ID of the row to edit.
