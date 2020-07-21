The `SingleMediaDropzone` component enables uploading media using drag and drop. The component doesn't handle the
upload mechanism but provides the dropped file through the `onDrop` callback. By using the `source` prop you can show
a thumbnail of the uploaded image.

```javascript
const handleDrop = (file) => {
    alert('Upload does not work in the doc. FeelsBadMan...');
};

<SingleMediaDropzone
    source="https://unsplash.it/400/400"
    uploading={false}
    progress={0}
    onDrop={handleDrop}
/>
```

If no `source` is provided an upload indicator is shown instead.

```javascript
const handleDrop = (file) => {
    alert('Upload does not work in the doc. FeelsBadMan...');
};

<SingleMediaDropzone
    uploading={false}
    progress={0}
    onDrop={handleDrop}
/>
```

The progress is shown while uploading.

```javascript
const handleDrop = (file) => {
    alert('Upload does not work in the doc. FeelsBadMan...');
};

<SingleMediaDropzone
    source="https://unsplash.it/400/400"
    uploading={true}
    progress={60}
    onDrop={handleDrop}
/>
```
