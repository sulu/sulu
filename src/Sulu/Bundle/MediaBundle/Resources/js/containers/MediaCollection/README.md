The `MediaCollection` is a wrapper component which combines all the needed components to represent a media view: a
collection breadcrumb and list, a media list, the upload dropzone and the dialogs for creating, editing, moving and
deleting. The `MediaCollection` is used inside the `MediaOverview`-view and inside the `Overlay`-component which is used
for the media selection. It depends on several stores and the router and therefore cannot be rendered in this
documentation.

| Prop                    | Description                                                                          |
|-------------------------|--------------------------------------------------------------------------------------|
| `collectionStore`       | `CollectionStore` holding the currently opened collection.                           |
| `collectionListStore`   | `ListStore` of the sub collections.                                                  |
| `mediaListStore`        | `ListStore` of the medias in the current collection.                                 |
| `mediaListAdapters`     | Adapters offered for the media list, e.g. `['media_card_overview', 'table']`.        |
| `mediaListRef`          | Callback receiving the ref of the media `List`.                                      |
| `locale`                | Observable locale.                                                                   |
| `onCollectionNavigate`  | Called with the id of a collection (or `undefined` for the root) when it is opened.  |
| `onMediaNavigate`       | Called with the id of a media when it is clicked.                                    |
| `onDeleteError`         | Called if deleting a collection or media failed.                                     |
| `onUploadError`         | Called with the errors of failed uploads.                                            |
| `uploadOverlayOpen`     | Controls the drag'n'drop upload overlay of the `MultiMediaDropzone`.                 |
| `onUploadOverlayOpen`   | Called when the upload overlay should open.                                          |
| `onUploadOverlayClose`  | Called when the upload overlay should close.                                         |
| `hideUploadAction`      | Hides the upload button in the toolbar (default `false`).                            |
| `overlayType`           | `overlay` (default) or `dialog`, how the collection form is shown.                   |
| `className`             | Additional CSS class.                                                                |

```jsx static
<MediaCollection
    collectionListStore={collectionListStore}
    collectionStore={collectionStore}
    locale={locale}
    mediaListAdapters={['media_card_overview', 'table']}
    mediaListStore={mediaListStore}
    onCollectionNavigate={this.handleCollectionNavigate}
    onMediaNavigate={this.handleMediaNavigate}
    onUploadOverlayClose={this.handleUploadOverlayClose}
    onUploadOverlayOpen={this.handleUploadOverlayOpen}
    uploadOverlayOpen={this.uploadOverlayOpen}
/>
```
