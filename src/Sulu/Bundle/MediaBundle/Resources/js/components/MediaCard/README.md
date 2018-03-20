The `MediaCard` is useful for displaying a list of selectable images. When the `imageSizes` and `directDownload`
properties are set a button will be shown in the header of the `MediaCard` which will open a list of copyable URLs on
click.

```
initialState = {
    selected: false,
};

const imageSizes = [
    {
        url: 'http://lorempixel.com/300/200',
        label: '300/200',
    },
    {
        url: 'http://lorempixel.com/600/300',
        label: '600/300',
    },
    {
        url: 'http://lorempixel.com/150/200',
        label: '150/200',
    }
];

const handleSelection = () => {
    setState({
        selected: !state.selected,
    });
};

const handleClick = (id) => {
    setState({
        selected: !state.selected,
    });
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
        selected={state.selected}
        meta="image/png, 3,2 MB"
        title="This is a great title that is too too long"
        image={'http://lorempixel.com/300/200'}
        imageSizes={imageSizes}
        directDownload={{
            url: 'http://lorempixel.com/300/200',
            label: 'This is a downloadable image'
        }}
        onDirectDownload={handleDirectDownload}
        downloadCopyText="Copy URL"
        showCover={state.selected}
    />
</div>
```
