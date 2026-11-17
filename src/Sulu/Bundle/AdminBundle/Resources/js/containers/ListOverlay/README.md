This component displays a [`List`](#list) inside of an [`Overlay`](#overlay) to offer the possibility to select a few resources.
The `onConfirm` callback is called with the selected IDs as soon as the confirm button is clicked. This component also
gets a `ListStore` passed, and is not responsible for its creation. The `clearSelectionOnClose` prop defines whether or
not the selection of the `List` should be cleared after the `Overlay` has been closed.

The component mainly serves as base for the [`SingleListOverlay`](#singlelistoverlay) and
[`MultiListOverlay`](#multilistoverlay) components.

| Prop                            | Description                                                                           |
|---------------------------------|---------------------------------------------------------------------------------------|
| `listStore`                     | The `ListStore` of the displayed list.                                                |
| `adapter`                       | List adapter used inside of the overlay.                                              |
| `open`, `onClose`, `onConfirm`  | Visibility and callbacks for closing and confirming.                                  |
| `title`                         | Title of the overlay.                                                                 |
| `overlayType`                   | `overlay` (default) or `dialog`.                                                      |
| `confirmLoading`                | Shows a loader on the confirm button.                                                 |
| `preSelectedItems`              | Items selected when the overlay opens.                                                |
| `disabledIds`                   | IDs which cannot be selected.                                                         |
| `itemDisabledCondition`         | JEXL expression, evaluated per item, which disables it.                               |
| `allowActivateForDisabledItems` | Whether disabled items can still be activated in a tree. Defaults to `true`.          |
| `clearSelectionOnClose`         | Clears the selection when the overlay closes. Defaults to `false`.                    |
| `reloadOnOpen`                  | Reloads the list every time the overlay opens. Defaults to `false`.                   |

```javascript static
const store = new ListStore('contacts', 'contacts', 'contacts', {page: observable.box(1)});

<ListOverlay
    adapter="table"
    listStore={store}
    onClose={handleClose}
    onConfirm={handleConfirm}
    open={open}
    title="Select contacts"
/>
```
