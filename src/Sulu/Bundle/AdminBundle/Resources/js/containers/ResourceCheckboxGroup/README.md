This component uses multiple [`Checkbox` components](#checkbox) and attaches them to one of the registered
resources. The component will then load the resources with the given `resourceKey`. There are two properties describing
which properties of the fields are used, the `displayProperty` describes which property from the object is read to the
input field and the `idProperty` defines which property is used as value.

| Prop                | Description                                                                              |
|---------------------|------------------------------------------------------------------------------------------|
| `resourceKey`       | Resource whose items are displayed as checkboxes.                                        |
| `values`, `onChange`| Checked values and callback receiving the new values and, as second argument, the items. |
| `displayProperty`   | Property shown as label of a checkbox.                                                   |
| `idProperty`        | Property used as value of a checkbox. Defaults to `id`.                                  |
| `requestParameters` | Additional parameters of the request loading the items.                                  |
| `disabled`          | Disables all checkboxes.                                                                 |

As it loads data from the server, the example is not executed.

```javascript static
<ResourceCheckboxGroup
    displayProperty="name"
    onChange={setValues}
    resourceKey="roles"
    values={values}
/>
```
