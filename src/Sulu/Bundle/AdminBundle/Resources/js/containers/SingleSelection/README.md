The `SingleSelection` is the counterpart of the [`MultiSelection`](#multiselection) for choosing exactly one entity. It
shows the selected item in a [`SingleItemSelection`](#singleitemselection) and opens a
[`SingleListOverlay`](#singlelistoverlay) to change it. The `resourceKey` defines the resource that is loaded (and used
to load the selected item by `value`), the `listKey` which list is shown in the overlay and `adapter` which list adapter
is used. As it loads data from the server, the examples below are not executed.

| Prop                            | Description                                                                          |
|---------------------------------|--------------------------------------------------------------------------------------|
| `value`                         | ID of the selected item.          |
| `onChange`                      | Called with the new ID (`undefined` if removed) and the selected item.               |
| `resourceKey`                   | Resource of the selectable items.                                                    |
| `listKey`                       | List configuration displayed in the overlay.                                         |
| `adapter`                       | List adapter used in the overlay, e.g. `table`.                                      |
| `displayProperties`             | Properties of the item that are joined to form its label.                            |
| `overlayTitle`                  | Title of the overlay.                                                                |
| `emptyText`                     | Text shown if nothing is selected.                                                   |
| `icon`                          | Icon of the button that opens the overlay. Defaults to `su-plus`.                    |
| `disabled`                      | Prevents opening the overlay and removing the item.                                  |
| `disabledIds`                   | IDs that cannot be selected in the overlay.                                          |
| `itemDisabledCondition`         | JEXL expression, evaluated per item, that disables it in the overlay.                |
| `allowDeselectForDisabledItems` | Allows to remove a selected item although it is disabled. Defaults to `false`.       |
| `locale`                        | Observable locale used for loading the items.                                        |
| `listOptions`                   | Additional options for the list in the overlay.                                      |
| `detailOptions`                 | Additional options for loading the selected item.                                    |
| `onItemClick`                   | Makes the selected item clickable, called with its ID and the item.                  |

```javascript static
const [value, setValue] = React.useState(undefined);

<SingleSelection
    adapter="table"
    displayProperties={['name']}
    emptyText="No contact selected"
    listKey="contacts"
    onChange={(id) => setValue(id)}
    overlayTitle="Select a contact"
    resourceKey="contacts"
    value={value}
/>
```

Restricting the selectable items:

```javascript static
<SingleSelection
    adapter="table"
    disabledIds={[1, 2]}
    displayProperties={['name']}
    emptyText="No contact selected"
    itemDisabledCondition="isLocked == true"
    listKey="contacts"
    onChange={handleChange}
    overlayTitle="Select a contact"
    resourceKey="contacts"
    value={5}
/>
```
