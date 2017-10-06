The `MediaCard` is useful for displaying a list of selectable images.

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

<div style={{backgroundColor: '#e5e5e5', padding: 20}}>
    <MediaCard
        id="What is luv?"
        icon="check"
        onSelectionChange={handleSelection}
        onClick={handleClick}
        selected={state.selected}
        meta="image/png, 3,2 MB"
        title="Lorempixel sdsdasdsd sdadasd asdasd"
        image={'http://lorempixel.com/300/200'}
        imageSizes={imageSizes}
        downloadCopyInfo="Copy URL"
        showCover={state.selected}
    />
</div>
```
