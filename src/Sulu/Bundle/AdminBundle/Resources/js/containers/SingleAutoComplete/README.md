This component uses the [`SingleAutoComplete` component](#singleautocomplete) and attaches it to a `SingleSelectionStore`. 
This store will contain the currently selected values of the `SingleAutoComplete` container.
The `displayProperty` indicates which of the properties of the items in the `MultiSelectionStore` will be used to display the items.
To define which properties will be searched by the `MultiAutoComplete` the `searchProperties` prop can be used.

| Prop               | Description                                                                              |
|--------------------|------------------------------------------------------------------------------------------|
| `selectionStore`   | The `SingleSelectionStore` holding the selected item. Its `resourceKey` is searched.     |
| `displayProperty`  | Property shown as label of an item.                                                      |
| `searchProperties` | Properties the search is executed on.                                                    |
| `options`          | Additional parameters of the search request, e.g. `{locale: 'en'}`.                      |
| `disabled`         | Disables the input.                                                                      |
| `id`               | `id` attribute of the input.                                                             |

As it loads data from the server, the example is not executed.

```javascript static
const selectionStore = new SingleSelectionStore('contacts');

<SingleAutoComplete
    displayProperty="fullName"
    searchProperties={['firstName', 'lastName']}
    selectionStore={selectionStore}
/>
```
