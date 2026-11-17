The `MediaCard` is useful for displaying a list of selectable media. When the `imageSizes`, `downloadUrl` and
`downloadText` props are set a button is shown in the header of the `MediaCard`, which opens a list with a download
link and copyable URLs of the image sizes. The `onDownload` callback is called with the URL of a clicked entry, and
`downloadCopyText` is the label shown for copying.

```javascript
const [selected, setSelected] = React.useState(false);

const imageSizes = [
    {
        url: 'https://unsplash.it/300/200',
        label: '300/200',
    },
    {
        url: 'https://unsplash.it/600/300',
        label: '600/300',
    },
    {
        url: 'https://unsplash.it/150/200',
        label: '150/200',
    }
];

const handleSelection = () => {
    setSelected(!selected);
};

const handleClick = () => {
    setSelected(!selected);
};

const handleDownload = (url) => {
    alert(`(Fake) Download started for: ${url}`);
};

<div style={{backgroundColor: '#e5e5e5', padding: 20, width: 300}}>
    <MediaCard
        id="1"
        icon="su-checkmark"
        onSelectionChange={handleSelection}
        onClick={handleClick}
        selected={selected}
        meta="image/png, 3,2 MB"
        mimeType="image/png"
        title="This is a great title that is too long"
        image="https://unsplash.it/300/200"
        imageSizes={imageSizes}
        downloadUrl="https://unsplash.it/300/200"
        downloadText="Download original"
        downloadCopyText="Copy URL"
        onDownload={handleDownload}
        showCover={selected}
    />
</div>
```

Without `onSelectionChange` the title is shown without a checkbox. Without `image` (or if it fails to load) the
`MimeTypeIndicator` of the `mimeType` is shown, and the `ghostLocale` prop marks media that only exists in another
locale.

```javascript
<div style={{backgroundColor: '#e5e5e5', padding: 20, width: 300}}>
    <MediaCard
        id="2"
        ghostLocale="de"
        meta="application/pdf, 1,1 MB"
        mimeType="application/pdf"
        title="Document without thumbnail"
    />
</div>
```
