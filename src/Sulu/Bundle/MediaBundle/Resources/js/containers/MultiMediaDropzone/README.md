This container-component transforms its child to a drag'n'drop area where a user can upload files by just dragging them ontop of the containing component.

```javascript static
<MultiMediaDropzone
    locale={locale}
    collectionId={collectionStore.id}
    onUpload={this.handleUpload}
>
    <ChildComponent />
</MultiMediaDropzone>
```
