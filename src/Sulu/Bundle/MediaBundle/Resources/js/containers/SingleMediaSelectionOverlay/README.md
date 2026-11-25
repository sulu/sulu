The `SingleMediaSelectionOverlay` component builds on the [`MediaSelectionOverlay`](#mediaselectionoverlay) and creates
the needed stores itself. It allows selecting a single media, which is passed to `onConfirm`. It loads the collections
and medias from the API and cannot be rendered in this documentation.

| Prop          | Description                                                    |
|---------------|----------------------------------------------------------------|
| `open`        | Shows the overlay.                                             |
| `locale`      | Observable locale.                                             |
| `types`       | Restricts the selectable media types, e.g. `['image']`.        |
| `classifications` | Restricts the selectable medias to these classifications, e.g. `['data_sheet']`. |
| `excludedIds` | Ids of medias which cannot be selected.                        |
| `onClose`     | Called when the overlay is closed.                             |
| `onConfirm`   | Called with the selected media object.                         |

```jsx static
<SingleMediaSelectionOverlay
    excludedIds={[1]}
    locale={locale}
    onClose={this.handleClose}
    onConfirm={this.handleConfirm}
    open={this.open}
    types={['image']}
/>
```
