This component is based on the [`ListOverlay`](#listoverlay) and allows to select multiple items from the
`List`. It almost has the same props as the `ListOverlay`, except for the `ListStore`, for whose creation
this component is responsible.

| Prop                            | Description                                                                          |
|---------------------------------|--------------------------------------------------------------------------------------|
| `resourceKey`, `listKey`        | Resource and list configuration of the displayed list.                               |
| `adapter`                       | List adapter used inside of the overlay.                                             |
| `open`, `onClose`               | Visibility and close callback.                                                       |
| `onConfirm`                     | Called with the array of selected items.                                             |
| `title`, `overlayType`          | Title and `overlay` (default) or `dialog`.                                           |
| `preSelectedItems`              | Items selected when the overlay opens.                                               |
| `preloadSelectedItems`          | Loads the pre-selected items so they are selected on all pages. Defaults to `true`.  |
| `disabledIds`, `excludedIds`    | IDs shown disabled, or not shown at all.                                             |
| `itemDisabledCondition`         | JEXL expression, evaluated per item, which disables it.                              |
| `allowActivateForDisabledItems` | Whether disabled items can still be activated in a tree.                             |
| `clearSelectionOnClose`         | Clears the selection when the overlay closes.                                        |
| `reloadOnOpen`                  | Reloads the list every time the overlay opens.                                       |
| `confirmLoading`                | Shows a loader on the confirm button.                                                |
| `locale`, `options`             | Locale and additional options for the list.                                          |

As it loads data from the server, the example is not executed.

```javascript static
<MultiListOverlay
    adapter="table"
    listKey="contacts"
    onClose={handleClose}
    onConfirm={(items) => handleSelect(items.map((item) => item.id))}
    open={open}
    preSelectedItems={selectedItems}
    resourceKey="contacts"
    title="Select contacts"
/>
```
