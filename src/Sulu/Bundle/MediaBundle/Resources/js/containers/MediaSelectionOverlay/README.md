This component uses a [`MediaCollection`](#mediacollection) in an [`Overlay`](#overlay) to offer the possibility to select a medias.
The `onConfirm` callback is called with the media objects as soon as the confirm button is clicked.

This component mainly serves as base for the [`SingleMediaSelectionOverlay`](#singlemediaselectionoverlay) and
[`MultiMediaSelectionOverlay`](#multimediaselectionoverlay) components. It is not responsible for creating the stores,
so it cannot be rendered in this documentation.

| Prop                  | Description                                                                 |
|-----------------------|-----------------------------------------------------------------------------|
| `open`                | Shows the overlay.                                                          |
| `locale`              | Observable locale.                                                          |
| `collectionId`        | Observable id of the currently opened collection.                           |
| `collectionListStore` | `ListStore` of the collections, see `createCollectionListStore`.            |
| `mediaListStore`      | `ListStore` of the medias, its selection is passed to `onConfirm`.          |
| `confirmLoading`      | Shows a loader on the confirm button (default `false`).                     |
| `onClose`             | Called when the overlay is closed.                                          |
| `onConfirm`           | Called with the array of selected media objects.                            |

```jsx static
<MediaSelectionOverlay
    collectionId={collectionId}
    collectionListStore={collectionListStore}
    confirmLoading={false}
    locale={locale}
    mediaListStore={mediaListStore}
    onClose={this.handleClose}
    onConfirm={this.handleConfirm}
    open={this.open}
/>
```
