This component is based on the [`ListOverlay`](#listoverlay) and allows to select only a single item from the `List`. It
almost has the same props as the `ListOverlay`, except for the `ListStore`, for whose creation this component is
responsible.

| Prop                            | Description                                                                          |
|---------------------------------|--------------------------------------------------------------------------------------|
| `resourceKey`, `listKey`        | Resource and list configuration of the displayed list.                               |
| `adapter`                       | List adapter used inside of the overlay.                                             |
| `open`, `onClose`               | Visibility and close callback.                                                       |
| `onConfirm`                     | Called with the selected item.                                                       |
| `title`, `overlayType`          | Title and `overlay` (default) or `dialog`.                                           |
| `preSelectedItem`               | Item selected when the overlay opens.                                                |
| `disabledIds`, `excludedIds`    | IDs shown disabled, or not shown at all.                                             |
| `itemDisabledCondition`         | JEXL expression, evaluated per item, which disables it.                              |
| `allowActivateForDisabledItems` | Whether disabled items can still be activated in a tree.                             |
| `clearSelectionOnClose`         | Clears the selection when the overlay closes.                                        |
| `reloadOnOpen`                  | Reloads the list every time the overlay opens.                                       |
| `confirmLoading`                | Shows a loader on the confirm button.                                                |
| `locale`, `options`, `metadataOptions` | Locale, additional list options and options for loading the list metadata.    |

As it loads data from the server, the example is not executed.

```javascript static
<SingleListOverlay
    adapter="table"
    listKey="contacts"
    onClose={handleClose}
    onConfirm={(item) => handleSelect(item.id)}
    open={open}
    resourceKey="contacts"
    title="Select a contact"
/>
```
