The `FileUploadButton` is a button that opens a file dialog when it is clicked. After choosing a file the `onUpload`
callback is called with the file as its argument:

```javascript
const handleUpload = (file) => {
    alert('Uploaded file with ' + file.size + ' bytes');
};

<FileUploadButton onUpload={handleUpload}>
    Upload something
</FileUploadButton>
```

This component also accepts a `icon` and `skin` prop to allow customizing the displayed button:

```javascript
const handleUpload = (file) => {
    alert('Uploaded file with ' + file.size + ' bytes');
};

<FileUploadButton icon="su-image" onUpload={handleUpload} skin="link">
    Upload something
</FileUploadButton>
```
