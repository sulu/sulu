The `MediaCard` is useful for displaying a list of selectable images. When the `imageSizes` and `directDownload`
properties are set a button will be shown in the header of the `MediaCard` which will open a list of copyable URLs on
click.

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

const handleClick = (id) => {
    setSelected(!selected);
};

const handleDirectDownload = (url) => {
    alert(`(Fake) Download started for: ${url}`);
};

<div style={{backgroundColor: '#e5e5e5', padding: 20}}>
    <MediaCard
        id="What is luv?"
        icon="su-checkmark"
        onSelectionChange={handleSelection}
        onClick={handleClick}
        selected={selected}
        meta="image/png, 3,2 MB"
        title="This is a great title that is too too long"
        image={'https://unsplash.it/300/200'}
        imageSizes={imageSizes}
        directDownload={{
            url: 'https://unsplash.it/300/200',
            label: 'This is a downloadable image'
        }}
        onDirectDownload={handleDirectDownload}
        downloadCopyText="Copy URL"
        showCover={selected}
    />
</div>
```
