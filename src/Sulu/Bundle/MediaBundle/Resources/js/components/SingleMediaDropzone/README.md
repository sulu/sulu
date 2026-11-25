The `SingleMediaDropzone` component enables uploading media using drag and drop. The component doesn't handle the
upload mechanism but provides the dropped file through the `onDrop` callback. By using the `image` prop you can show
a thumbnail of the uploaded image.

```javascript
const handleDrop = (file) => {
    alert('Upload does not work in the doc. FeelsBadMan...');
};

<SingleMediaDropzone
    image="https://unsplash.it/400/400"
    uploading={false}
    progress={0}
    onDrop={handleDrop}
/>
```

If no `image` and no `mimeType` is provided an empty indicator is shown, its icon can be changed with `emptyIcon`.
The `uploadText` is shown below the upload icon while dragging a file over the dropzone.

```javascript
<SingleMediaDropzone
    emptyIcon="su-user"
    onDrop={() => {}}
    uploadText="Drop your file here"
/>
```

If the media has no thumbnail the `mimeType` is used to show a `MimeTypeIndicator` instead.

```javascript
<SingleMediaDropzone
    mimeType="application/pdf"
    onDrop={() => {}}
/>
```

The progress is shown while uploading.

```javascript
<SingleMediaDropzone
    image="https://unsplash.it/400/400"
    uploading={true}
    progress={60}
    onDrop={() => {}}
/>
```

The `round` skin renders a circular dropzone, for example for avatars.

```javascript
<SingleMediaDropzone
    image="https://unsplash.it/400/400"
    onDrop={() => {}}
    skin="round"
/>
```

A disabled dropzone does not accept files and the `errorText` is shown below the dropzone, e.g. when an upload failed.
The `accept` prop restricts the accepted mime type, e.g. `image/*`.

```javascript
<SingleMediaDropzone
    accept="image/*"
    disabled={true}
    errorText="The upload failed."
    image="https://unsplash.it/400/400"
    onDrop={() => {}}
/>
```
