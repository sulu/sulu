This component uses the [`MultiSelect` component](#multiselect) and attaches it to one of the registered
resources. The component will then load the resources with the given `resourceKey`. There are two properties describing
which properties of the fields are used, the `displayProperty` describes which property from the object is read to the 
input field and the `idProperty` defines which property is used as value.

| Prop                | Description                                                                              |
|---------------------|------------------------------------------------------------------------------------------|
| `resourceKey`       | Resource whose items are listed.                                                         |
| `values`, `onChange`| Selected values and callback receiving the new values and, as second argument, the items.|
| `displayProperty`   | Property shown as label of an item.                                                      |
| `idProperty`        | Property used as value of an item. Defaults to `id`.                                     |
| `requestParameters` | Additional parameters of the request loading the items.                                  |
| `disabled`          | Disables the select.                                                                     |
| `allSelectedText`   | Text of the button if all items are selected.                                            |
| `noneSelectedText`  | Text of the button if no item is selected.                                               |
| `onClose`           | Called when the list of items is closed.                                                 |

As it loads data from the server, the example is not executed.

```javascript static
<ResourceMultiSelect
    allSelectedText="All roles"
    displayProperty="name"
    noneSelectedText="No role"
    onChange={setValues}
    resourceKey="roles"
    values={values}
/>
```
