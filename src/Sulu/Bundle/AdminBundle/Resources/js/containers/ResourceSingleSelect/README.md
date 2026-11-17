This component uses the [`SingleSelect` component](#singleselect) and attaches it to one of the registered
resources. The component will then load the resources with the given `resourceKey`. There are two properties describing
which properties of the fields are used, the `displayProperty` describes which property from the object is read to the 
input field and the `idProperty` defines which property is used as value.

| Prop                | Description                                                                              |
|---------------------|------------------------------------------------------------------------------------------|
| `resourceKey`       | Resource whose items are listed.                                                         |
| `value`, `onChange` | Selected value (`idProperty` of an item) and callback receiving the new one.             |
| `displayProperty`   | Property shown as label of an item.                                                      |
| `idProperty`        | Property used as value of an item. The default of the container is `id`.                 |
| `requestParameters` | Additional parameters of the request loading the items.                                  |
| `disabled`          | Disables the select.                                                                     |
| `editable`          | Adds an action to the select that opens an overlay to edit the items of the resource.    |
| `overlayTitle`      | Title of that overlay.                                                                   |

As it loads data from the server, the example is not executed.

```javascript static
<ResourceSingleSelect
    displayProperty="name"
    editable={true}
    onChange={setValue}
    overlayTitle="Edit types"
    requestParameters={{locale: 'en'}}
    resourceKey="categories"
    value={value}
/>
```
