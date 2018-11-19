The `MediaSelectionItem` component displays a `thumbnail` or `mimeType` indicator beside a the given `children`. 
It is used by the `MediaSelection` and `SingleMediaSelection` to display the selected medias.

```javascript
const box = {
    "padding": "3px",
    "margin-bottom": "5px",
    "border": "1px solid gray",
};

<div>
    <div style={box}>
        <MediaSelectionItem
            mimeType="application/vnd.ms-excel"
            thumbnail="http://lorempixel.com/25/25"
        >
            media with thumbnail
        </MediaSelectionItem>
    </div>
    <div style={box}>
        <MediaSelectionItem
            mimeType="application/vnd.ms-excel"
        >
            media with mime type indicator
        </MediaSelectionItem>
    </div>
</div>
```
