This container-component transforms its child to a drag'n'drop area where a user can upload files by just dragging them on top of the containing component.

| Prop            | Description                                                                                         |
|-----------------|-----------------------------------------------------------------------------------------------------|
| `locale`        | Observable locale used for the created medias.                                                      |
| `collectionId`  | Collection receiving the files. If not set, a collection selection overlay is shown after the drop. |
| `open`          | Shows the drop overlay with the upload progress of every file.                                      |
| `onOpen`        | Called when a file is dragged over the area, set `open` here.                                       |
| `onClose`       | Called when the drop overlay should close.                                                          |
| `onUpload`      | Called with the created media objects after all uploads succeeded.                                  |
| `onUploadError` | Called with the error responses if at least one upload failed.                                      |
| `accept`        | Restricts the accepted mime type, e.g. `image/*`.                                                   |
| `disabled`      | Disables the dropzone (default `false`).                                                            |
| `className`     | Additional CSS class of the dropzone.                                                               |

```jsx static
<MultiMediaDropzone
    accept="image/*"
    collectionId={collectionStore.id}
    locale={locale}
    onClose={this.handleClose}
    onOpen={this.handleOpen}
    onUpload={this.handleUpload}
    onUploadError={this.handleUploadError}
    open={this.open}
>
    <ChildComponent />
</MultiMediaDropzone>
```
