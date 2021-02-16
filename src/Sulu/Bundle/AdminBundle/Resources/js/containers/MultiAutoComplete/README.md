This component uses the [`MultiAutoComplete` component](#multiautocomplete) and attaches it to a `MultiSelectionStore`.
This store will contain the currently selected values from the `MultiAutoComplete` container. The `displayProperty`
indicates which of the properties of the items in the `MultiSelectionStore` will be used to display the items. The
`idProperty` prop is the property of the object, that will be used to determine the unique identifier for an item. To
define which properties will be searched by the `MultiAutoComplete` the `searchProperties` prop can be used.
