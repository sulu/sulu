This component displays a [`Form`](#form) inside of an [`Overlay`](#overlay). It expects a `FormStore` that contains 
the data and the schema of the form that should be displayed. Additionally, it supports multiple parameters to
adjust the [`Overlay`](#overlay) that contains the form.

The component is used in various places to enable the input of data inside of an overlay.

| Prop                            | Description                                                                     |
|---------------------------------|---------------------------------------------------------------------------------|
| `formStore`                     | The `FormStore` (e.g. `ResourceFormStore` or `MemoryFormStore`) with the form. |
| `open`, `onClose`               | Visibility and close callback.                                                  |
| `onConfirm`                     | Called after the form was submitted successfully.                               |
| `title`, `confirmText`          | Title of the overlay and label of the confirm button.                           |
| `confirmDisabled`               | Disables the confirm button.                                                    |
| `confirmLoading`                | Shows a loader on the confirm button.                                           |
| `size`                          | Size of the overlay: `small` or `large`.                             |
| `router`                        | The `Router` passed to the form for fields that need it.                        |
| `onFieldFinish`                 | Called with data path and schema path when the user finishes editing a field.   |

```javascript static
const formStore = new MemoryFormStore({}, schema, jsonSchema);

<FormOverlay
    confirmText="Apply"
    formStore={formStore}
    onClose={handleClose}
    onConfirm={handleConfirm}
    open={open}
    size="small"
    title="Edit entry"
/>
```
