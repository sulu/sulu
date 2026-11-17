This component uses the [`MultiAutoComplete` component](#multiautocomplete) and attaches it to a `MultiSelectionStore`.
This store will contain the currently selected values from the `MultiAutoComplete` container. The `displayProperty`
indicates which of the properties of the items in the `MultiSelectionStore` will be used to display the items. The
`idProperty` prop is the property of the object, that will be used to determine the unique identifier for an item. To
define which properties will be searched by the `MultiAutoComplete` the `searchProperties` prop can be used.

| Prop               | Description                                                                              |
|--------------------|------------------------------------------------------------------------------------------|
| `selectionStore`   | The `MultiSelectionStore` holding the selected items. Its `resourceKey` is searched.     |
| `displayProperty`  | Property shown as label of an item.                                                      |
| `idProperty`       | Property used as unique identifier. Defaults to `id`.                                    |
| `searchProperties` | Properties the search is executed on.                                                    |
| `allowAdd`         | Allows to add values that do not exist yet. Defaults to `false`.                         |
| `options`          | Additional parameters of the search request, e.g. `{locale: 'en'}`.                      |
| `disabled`         | Disables the input.                                                                      |
| `id`, `inputRef`   | `id` attribute and ref callback of the input.                                            |

As it loads data from the server, the example is not executed.

```javascript static
const selectionStore = new MultiSelectionStore('tags', []);

<MultiAutoComplete
    allowAdd={true}
    displayProperty="name"
    searchProperties={['name']}
    selectionStore={selectionStore}
/>
```
