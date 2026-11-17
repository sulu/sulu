The `RestoreFormOverlay` shows an [`Overlay`](#overlay) with a form to restore an item from the trash. The form for the
given `formKey` is loaded together with the data of the trash item, and `onConfirm` is called with the (possibly
adjusted) data, which is then used to restore the item. The form is loaded from the API, so it cannot be rendered in
this documentation.

| Prop             | Description                                                      |
|------------------|------------------------------------------------------------------|
| `open`           | Shows the overlay.                                               |
| `formKey`        | Key of the form used to edit the restore data.                   |
| `trashItemId`    | Id of the trash item whose data is loaded.                       |
| `confirmLoading` | Shows a loader on the confirm button (default `false`).          |
| `onClose`        | Called when the overlay is closed.                               |
| `onConfirm`      | Called with the form data.                                       |

```jsx static
<RestoreFormOverlay
    confirmLoading={false}
    formKey="page_restore"
    onClose={this.handleClose}
    onConfirm={this.handleConfirm}
    open={true}
    trashItemId={1}
/>
```
