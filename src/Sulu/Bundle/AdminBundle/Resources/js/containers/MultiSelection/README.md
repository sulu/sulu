The `MultiSelection` can be used to make any kind of selection. For assigning an entity a
[`ListOverlay`](#listoverlay) is used. What kind of entities are available is defined by the `resourceKey` prop
of the component. The `displayProperties` prop allows to define which properties of the loaded objects should be used
to build the items in the selection list. Finally the `adapter` option allows to define which list adapter should be
used in the overlay.

Like most other fields the `value` prop and the `onChange` callback are used to define the current value, in this case
the IDs of the assigned entities. The `title` attribute can be used to define the title of the Overlay, whereby `icon`
and `label` are used in the head of the [`MultiItemSelection`](#multiitemselection) used by this component.

The examples below load data from the server and are therefore not executed.

| Prop                            | Description                                                                         |
|---------------------------------|-------------------------------------------------------------------------------------|
| `value`, `onChange`             | IDs of the assigned items and the callback receiving the new IDs.                   |
| `resourceKey`, `listKey`        | Resource of the items and list configuration shown in the overlay.                  |
| `adapter`                       | List adapter used in the overlay.                                                   |
| `displayProperties`             | Properties used to build the label of each item.                                    |
| `overlayTitle`, `label`, `icon` | Overlay title, header label and the icon of the add button (default `su-plus`).     |
| `sortable`                      | Allows to reorder the items by drag and drop. Defaults to `true`.                   |
| `disabled`                      | Prevents changes.                                                                   |
| `disabledIds`                   | IDs that cannot be selected in the overlay.                                         |
| `itemDisabledCondition`         | JEXL expression, evaluated per item, which disables it in the overlay.              |
| `allowDeselectForDisabledItems` | Allows to remove selected items that are disabled. Defaults to `false`.             |
| `locale`                        | Observable locale used for loading the items.                                       |
| `options`                       | Additional options for the list in the overlay.                                     |
| `onItemClick`                   | Makes the items clickable, called with the ID and the item.                         |

```javascript static
const [value, setValue] = React.useState([]);

<MultiSelection
    adapter="table"
    displayProperties={['name']}
    label="Contacts"
    listKey="contacts"
    onChange={setValue}
    overlayTitle="Select contacts"
    resourceKey="contacts"
    sortable={false}
    value={value}
/>
```
