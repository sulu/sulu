This component shows a thumbnail of the recent version of the uploaded media.
Besides that, two buttons are rendered below which allow to open either the `FocusPointOverlay` or the `CropOverlay`.
The `FocusPointOverlay` makes it possible to set a focus point within the uploaded media element and the
`CropOverlay` provides a typical crop functionality.

A `ResourceStore` component has to be passed so the component knows which locale, id and so on to use. The optional
`onSuccess` callback is called after a new version was uploaded or the focus point or crop was saved. The container
loads the media via the `ResourceStore`, so it cannot be rendered in this documentation.

```jsx static
<MediaVersionUpload
    onSuccess={this.handleSuccess}
    resourceStore={resourceStore}
/>
```
