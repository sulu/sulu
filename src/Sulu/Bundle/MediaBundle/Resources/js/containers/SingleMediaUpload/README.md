The `SingleMediaUpload` container uses the [`SingleMediaDropzone`](#singlemediadropzone) component to offer an upload
mechanism including uploading the dropped file. A `MediaUploadStore` has to be passed, so that the container knows which
locale, id and so on to use. If the store has no media yet, a `collectionId` is required to create a new one.
Below the dropzone buttons to download and delete an already uploaded media are shown.

| Prop               | Description                                                       |
|--------------------|-------------------------------------------------------------------|
| `collectionId`     | Collection in which a new media is created.                       |
| `deletable`        | Shows the delete button (default `true`).                         |
| `downloadable`     | Shows the download button (default `true`).                       |
| `disabled`         | Disables the dropzone and hides the buttons.                      |
| `emptyIcon`        | Icon shown if there is no media.                                  |
| `imageSize`        | Thumbnail format used for the preview (default `sulu-400x400`).   |
| `skin`             | `default` or `round`.                                             |
| `uploadText`       | Text shown while dragging a file over the dropzone.               |
| `onUploadComplete` | Called with the media after an upload or deletion has finished.   |

The examples use simple objects instead of a real `MediaUploadStore`.

```javascript
const mediaUploadStore = {
    id: 1,
    media: {id: 1},
    mimeType: 'image/jpeg',
    progress: 0,
    uploading: false,
    downloadUrl: '#',
    getThumbnail: () => 'https://unsplash.it/400/400',
    update: () => Promise.resolve(),
    delete: () => Promise.resolve(),
};

<div style={{width: 300}}>
    <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="Drop your file here" />
</div>
```

Without a media only the dropzone is shown, here with `round` skin and a custom `emptyIcon`.

```javascript
const mediaUploadStore = {
    id: undefined,
    media: undefined,
    mimeType: undefined,
    progress: 0,
    uploading: false,
    getThumbnail: () => undefined,
    create: () => Promise.resolve(),
};

<div style={{width: 200}}>
    <SingleMediaUpload
        collectionId={1}
        emptyIcon="su-user"
        mediaUploadStore={mediaUploadStore}
        skin="round"
    />
</div>
```

With `deletable={false}` and `downloadable={false}` no buttons are shown.

```javascript
const mediaUploadStore = {
    id: 1,
    media: {id: 1},
    mimeType: 'image/jpeg',
    progress: 0,
    uploading: false,
    getThumbnail: () => 'https://unsplash.it/400/400',
    update: () => Promise.resolve(),
    delete: () => Promise.resolve(),
};

<div style={{width: 300}}>
    <SingleMediaUpload deletable={false} downloadable={false} mediaUploadStore={mediaUploadStore} />
</div>
```
