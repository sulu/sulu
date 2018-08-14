This component uses the [`MultiAutoComplete` component](#multiautocomplete) and attaches it to one of the registered
resources. The component will then read the suggestions from the resources with the given `resourceKey`. The `value` is
the currently selected object. There are two properties describing which properties of the fields are used, the
`displayProperty` describes which property from the object is read to the input field and the `searchProperties` define
which fields of the object will be searched and displayed in the suggestion list. The `onChange` callback will be
called with the selected object when a suggestion is selected.
